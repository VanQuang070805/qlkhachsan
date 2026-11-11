import crypto from 'crypto';
import { AIMessage, BaseMessage, HumanMessage } from '@langchain/core/messages';
import { Redis } from 'ioredis';

export interface SerializedMessage {
  type: 'human' | 'ai';
  content: string;
}

export function conversationFromClient(history: unknown): BaseMessage[] {
  if (!Array.isArray(history)) return [];
  return history.slice(-8).flatMap((item) => {
    if (!item || typeof item !== 'object') return [];
    const role = item.role;
    const content = typeof item.content === 'string' ? item.content.trim().slice(0, 1000) : '';
    if (!content) return [];
    if (role === 'user') return [new HumanMessage(content)];
    if (role === 'assistant') return [new AIMessage(content)];
    return [];
  });
}

export interface ThreadRecord {
  thread_id: string;
  created_at: string;
  updated_at: string;
  metadata: Record<string, unknown>;
  values: {
    messages: BaseMessage[];
    documents: unknown[];
  };
}

interface StoredThreadPayload {
  thread_id: string;
  created_at: string;
  updated_at: string;
  metadata: Record<string, unknown>;
  messages: SerializedMessage[];
  documents: unknown[];
}

const THREAD_TTL_SECONDS = 60 * 60 * 2; // 2 hours
const MAX_LOCAL_THREADS = 500;
const localThreads = new Map<string, StoredThreadPayload>();

let redisClient: Redis | null = null;
let redisAttempted = false;

export function getRedisQueueClient(): Redis | null {
  if (redisClient) return redisClient;
  if (redisAttempted) return null;
  redisAttempted = true;

  const redisUrl = (process.env.REDIS_QUEUE_URL || '').trim();
  const redisHost = (process.env.REDIS_QUEUE_HOST || '').trim();
  if (!redisUrl && !redisHost) {
    return null;
  }

  try {
    redisClient = redisUrl
      ? new Redis(redisUrl, {
          maxRetriesPerRequest: null,
          enableReadyCheck: false,
          lazyConnect: true,
        })
      : new Redis({
          host: redisHost,
          port: Number(process.env.REDIS_QUEUE_PORT || 6379),
          password: process.env.REDIS_QUEUE_PASSWORD || undefined,
          db: Number(process.env.REDIS_QUEUE_DB || 0),
          maxRetriesPerRequest: null,
          enableReadyCheck: false,
          lazyConnect: true,
        });

    redisClient.on('error', () => {
      // Handled gracefully per operation
    });
    return redisClient;
  } catch {
    return null;
  }
}

function hydrateThread(payload: StoredThreadPayload): ThreadRecord {
  return {
    thread_id: payload.thread_id,
    created_at: payload.created_at,
    updated_at: payload.updated_at,
    metadata: payload.metadata || {},
    values: {
      messages: (payload.messages || []).map((m) =>
        m.type === 'human'
          ? new HumanMessage(m.content)
          : new AIMessage(m.content),
      ),
      documents: payload.documents || [],
    },
  };
}

function dehydrateThread(record: ThreadRecord): StoredThreadPayload {
  return {
    thread_id: record.thread_id,
    created_at: record.created_at,
    updated_at: record.updated_at,
    metadata: record.metadata || {},
    messages: (record.values.messages || []).slice(-10).map((m) => ({
      type: m instanceof HumanMessage ? 'human' : 'ai',
      content: String(m.content || ''),
    })),
    documents: (record.values.documents || []).slice(-6),
  };
}

function threadKey(threadId: string): string {
  return `royal:lg:thread:${threadId}`;
}

export async function getOrCreateThread(
  threadId?: string,
  metadata: Record<string, unknown> = {},
): Promise<ThreadRecord> {
  const id = threadId || crypto.randomUUID();
  const redis = getRedisQueueClient();

  if (redis) {
    try {
      const raw = await redis.get(threadKey(id));
      if (raw) {
        const parsed = JSON.parse(raw) as StoredThreadPayload;
        parsed.updated_at = new Date().toISOString();
        await redis.set(
          threadKey(id),
          JSON.stringify(parsed),
          'EX',
          THREAD_TTL_SECONDS,
        );
        return hydrateThread(parsed);
      }
    } catch {
      // Fallback to in-memory store
    }
  }

  const existingLocal = localThreads.get(id);
  if (existingLocal) {
    existingLocal.updated_at = new Date().toISOString();
    return hydrateThread(existingLocal);
  }

  const createdPayload: StoredThreadPayload = {
    thread_id: id,
    created_at: new Date().toISOString(),
    updated_at: new Date().toISOString(),
    metadata,
    messages: [],
    documents: [],
  };

  if (localThreads.size >= MAX_LOCAL_THREADS) {
    const oldestKey = localThreads.keys().next().value;
    if (oldestKey) localThreads.delete(oldestKey);
  }
  localThreads.set(id, createdPayload);

  if (redis) {
    try {
      await redis.set(
        threadKey(id),
        JSON.stringify(createdPayload),
        'EX',
        THREAD_TTL_SECONDS,
      );
    } catch {
      // Ignore Redis write error when falling back
    }
  }

  return hydrateThread(createdPayload);
}

export async function saveThread(record: ThreadRecord): Promise<void> {
  record.updated_at = new Date().toISOString();
  const payload = dehydrateThread(record);
  localThreads.set(record.thread_id, payload);

  const redis = getRedisQueueClient();
  if (redis) {
    try {
      await redis.set(
        threadKey(record.thread_id),
        JSON.stringify(payload),
        'EX',
        THREAD_TTL_SECONDS,
      );
    } catch {
      // Ignore Redis write failure
    }
  }
}

export async function deleteThreadById(threadId: string): Promise<void> {
  localThreads.delete(threadId);
  const redis = getRedisQueueClient();
  if (redis) {
    try {
      await redis.del(threadKey(threadId));
    } catch {
      // Ignore
    }
  }
}

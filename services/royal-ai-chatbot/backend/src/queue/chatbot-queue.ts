import { EventEmitter } from 'events';
import { Queue } from 'bullmq';
import { AIMessage, HumanMessage } from '@langchain/core/messages';
import { graph as retrievalGraph } from '../retrieval_graph/graph.js';
import { sanitizePublicSource } from '../retrieval_graph/utils.js';
import { isRetryableAIProviderError } from '../shared/utils.js';
import {
  getOrCreateThread,
  getRedisQueueClient,
  saveThread,
  conversationFromClient,
} from '../shared/thread-store.js';

export interface ChatbotJobPayload {
  threadId: string;
  query: string;
  fingerprint: string;
  config: Record<string, any>;
  history?: unknown;
}

export interface StreamEventItem {
  event: 'updates' | 'messages/partial' | 'end' | 'error';
  data: any;
}

type InProcessStream = { emitter: EventEmitter; events: StreamEventItem[] };
const activeInProcessStreams = new Map<string, InProcessStream>();
const completedInProcessStreams = new Map<string, { events: StreamEventItem[]; expires: number }>();
let bullQueue: Queue<ChatbotJobPayload> | null = null;

async function rememberReplayedAnswer(payload: ChatbotJobPayload, events: StreamEventItem[]): Promise<void> {
  const finalChunk = [...events].reverse().find((item) => item.event === 'messages/partial');
  const answer = String(finalChunk?.data?.[0]?.content || '').trim();
  if (!answer) return;
  const thread = await getOrCreateThread(payload.threadId);
  const messages = thread.values.messages;
  const lastUser = messages[messages.length - 2];
  const lastAnswer = messages[messages.length - 1];
  if (lastUser instanceof HumanMessage && String(lastUser.content) === payload.query &&
      lastAnswer instanceof AIMessage && String(lastAnswer.content) === answer) return;
  const previous = (messages.length ? messages : conversationFromClient(payload.history)).slice(-8);
  thread.values.messages = [...previous, new HumanMessage(payload.query), new AIMessage(answer)];
  await saveThread(thread);
}

export function getChatbotBullQueue(): Queue<ChatbotJobPayload> | null {
  if (bullQueue) return bullQueue;
  const redis = getRedisQueueClient();
  if (!redis || process.env.USE_BULLMQ_WORKER !== 'true') {
    return null;
  }

  bullQueue = new Queue<ChatbotJobPayload>('royal-chatbot-runs', {
    connection: redis as any,
    defaultJobOptions: {
      attempts: 2,
      backoff: {
        type: 'exponential',
        delay: 1000,
      },
      removeOnComplete: 100,
      removeOnFail: 200,
    },
  });
  return bullQueue;
}

export function streamKey(fingerprint: string): string {
  return `royal:chat:stream:${fingerprint}`;
}

/**
 * Execute a single retrievalGraph run and broadcast events to an EventEmitter
 * and optionally to a Redis Stream (`royal:chat:stream:{fingerprint}`).
 */
export async function executeRetrievalRunAndBroadcast(
  payload: ChatbotJobPayload,
  emitter?: EventEmitter,
): Promise<void> {
  const startedAt = Date.now();
  let firstTokenMs: number | null = null;
  let chunkCount = 0;
  let route = 'unknown';
  const redis = getRedisQueueClient();
  const sKey = streamKey(payload.fingerprint);
  const thread = await getOrCreateThread(payload.threadId);
  const previousMessages = (thread.values.messages.length
    ? thread.values.messages
    : conversationFromClient(payload.history)).slice(-8);

  const emitEvent = async (item: StreamEventItem) => {
    if (emitter) {
      emitter.emit('item', item);
    }
    if (redis) {
      try {
        await redis.xadd(
          sKey,
          'MAXLEN',
          '~',
          200,
          '*',
          'event',
          item.event,
          'data',
          JSON.stringify(item.data),
        );
      } catch {
        // Ignore Redis stream write error
      }
    }
  };

  try {
    const eventStream = retrievalGraph.streamEvents(
      {
        query: payload.query,
        messages: previousMessages,
      },
      {
        ...payload.config,
        version: 'v2',
      },
    );

    let accumulatedAi = '';
    let retrievedDocs: any[] = [];

    for await (const ev of eventStream) {
      if (ev.event === 'on_chain_end' && ev.name === 'checkQueryType') {
        route = String(ev.data?.output?.route || 'unknown');
      }
      if (
        ev.event === 'on_chain_end' &&
        ev.name === 'retrieveDocuments' &&
        ev.data?.output?.documents
      ) {
        retrievedDocs = ev.data.output.documents;
        await emitEvent({
          event: 'updates',
          data: {
            retrieveDocuments: {
              documents: retrievedDocs.map((d: any) => ({
                pageContent: d.pageContent,
                metadata: sanitizePublicSource(d.metadata),
              })),
            },
          },
        });
      }

      if (
        ev.event === 'on_chat_model_stream' &&
        (ev.metadata?.langgraph_node === 'generateResponse' ||
          ev.metadata?.langgraph_node === 'directAnswer')
      ) {
        const delta = String(ev.data?.chunk?.content || '').replace(
          /\*\*/g,
          '',
        );
        if (delta) {
          firstTokenMs ??= Date.now() - startedAt;
          chunkCount += 1;
          accumulatedAi += delta;
          await emitEvent({
            event: 'messages/partial',
            data: [
              {
                type: 'ai',
                content: accumulatedAi,
                delta,
              },
            ],
          });
        }
      }

      // The clock answer is produced locally, so it has no model token stream.
      if (ev.event === 'on_chain_end' && ev.name === 'directAnswer' && !accumulatedAi) {
        const messages = ev.data?.output?.messages;
        const answer = Array.isArray(messages) ? String(messages[messages.length - 1]?.content || '') : '';
        if (answer) {
          firstTokenMs ??= Date.now() - startedAt;
          chunkCount += 1;
          accumulatedAi = answer;
          await emitEvent({
            event: 'messages/partial',
            data: [{ type: 'ai', content: answer, delta: answer }],
          });
        }
      }
    }

    if (accumulatedAi.trim()) {
      thread.values.messages = [
        ...previousMessages,
        new HumanMessage(payload.query),
        new AIMessage(accumulatedAi.trim()),
      ];
      await saveThread(thread);
    }

    await emitEvent({
      event: 'end',
      data: {
        done: true,
        thread_id: thread.thread_id,
        sources: retrievedDocs.map((d: any) =>
          sanitizePublicSource(d.metadata),
        ),
      },
    });
    console.info(JSON.stringify({
      event: 'chatbot_run', correlation_id: payload.config?.configurable?.correlationId || 'unknown',
      route, status: 'completed', first_token_ms: firstTokenMs,
      duration_ms: Date.now() - startedAt, chunks: chunkCount, sources: retrievedDocs.length,
    }));
  } catch (error) {
    const providerUnavailable = isRetryableAIProviderError(error);
    console.warn(JSON.stringify({
      event: 'chatbot_run', correlation_id: payload.config?.configurable?.correlationId || 'unknown',
      route, status: providerUnavailable ? 'provider_unavailable' : 'failed',
      first_token_ms: firstTokenMs, duration_ms: Date.now() - startedAt, chunks: chunkCount,
    }));
    await emitEvent({
      event: 'error',
      data: {
        error: providerUnavailable
          ? 'AI_PROVIDER_UNAVAILABLE'
          : 'UPSTREAM_EXECUTION_ERROR',
        message: providerUnavailable
          ? 'Dịch vụ AI đang bận. Vui lòng thử lại sau ít phút.'
          : 'Không thể xử lý yêu cầu.',
        retryable: providerUnavailable,
        correlation_id:
          payload.config?.configurable?.correlationId || 'unknown',
      },
    });
  } finally {
    if (redis) {
      try {
        await redis.expire(sKey, 60);
      } catch {
        // Ignore
      }
    }
    if (emitter) {
      emitter.emit('close');
    }
  }
}

/**
 * Subscribe to a deduplicated run by fingerprint.
 * Multiple concurrent requests with the same fingerprint share the same execution.
 */
export async function subscribeOrRunDeduplicated(
  payload: ChatbotJobPayload,
  onItem: (item: StreamEventItem) => void,
): Promise<void> {
  const fp = payload.fingerprint;
  const completed = completedInProcessStreams.get(fp);
  if (completed && completed.expires > Date.now()) {
    completed.events.forEach(onItem);
    try { await rememberReplayedAnswer(payload, completed.events); } catch {
      // Client history remains a bounded fallback if thread persistence is unavailable.
    }
    return;
  }
  if (completed) completedInProcessStreams.delete(fp);
  const existing = activeInProcessStreams.get(fp);

  if (existing) {
    return new Promise<void>((resolve) => {
      const handler = (item: StreamEventItem) => onItem(item);
      const closeHandler = async () => {
        existing.emitter.off('item', handler);
        try {
          if (existing.events.some((item) => item.event === 'end')) {
            await rememberReplayedAnswer(payload, existing.events);
          }
        } catch {
          // The response was already streamed; a later client history can restore context.
        } finally {
          resolve();
        }
      };
      existing.events.forEach(onItem);
      existing.emitter.on('item', handler);
      existing.emitter.once('close', closeHandler);
    });
  }

  const queue = getChatbotBullQueue();
  const redis = getRedisQueueClient();

  if (queue && redis) {
    try {
      await queue.add('chat-query', payload, {
        deduplication: {
          id: fp,
          ttl: 15000,
        },
      });

      const sKey = streamKey(fp);
      let lastId = '0-0';
      const deadline =
        Date.now() + Number(process.env.LANGGRAPH_TIMEOUT || 60) * 1000;

      while (Date.now() < deadline) {
        const res = await (redis as any).xread(
          'COUNT',
          20,
          'BLOCK',
          1000,
          'STREAMS',
          sKey,
          lastId,
        );
        if (!res) continue;

        for (const [, entries] of res) {
          for (const [id, fields] of entries) {
            lastId = id;
            const eventIdx = fields.indexOf('event');
            const dataIdx = fields.indexOf('data');
            const evName = eventIdx >= 0 ? fields[eventIdx + 1] : 'updates';
            const rawData = dataIdx >= 0 ? fields[dataIdx + 1] : '{}';
            const parsed = JSON.parse(rawData);
            onItem({ event: evName, data: parsed });
            if (evName === 'end' || evName === 'error') {
              return;
            }
          }
        }
      }
      return;
    } catch {
      // Fallback to in-process deduplicated execution if BullMQ/Redis is unreachable
    }
  }

  const emitter = new EventEmitter();
  emitter.setMaxListeners(200);
  const inProcess: InProcessStream = { emitter, events: [] };
  emitter.on('item', (item: StreamEventItem) => {
    if (inProcess.events.length < 300) inProcess.events.push(item);
  });
  activeInProcessStreams.set(fp, inProcess);

  const donePromise = new Promise<void>((resolve) => {
    const handler = (item: StreamEventItem) => onItem(item);
    const closeHandler = () => {
      emitter.off('item', handler);
      activeInProcessStreams.delete(fp);
      if (inProcess.events.some((item) => item.event === 'end')) {
        completedInProcessStreams.set(fp, { events: inProcess.events, expires: Date.now() + 60_000 });
        if (completedInProcessStreams.size > 200) {
          const oldest = completedInProcessStreams.keys().next().value;
          if (oldest) completedInProcessStreams.delete(oldest);
        }
      }
      resolve();
    };
    emitter.on('item', handler);
    emitter.once('close', closeHandler);
  });

  void executeRetrievalRunAndBroadcast(payload, emitter);

  return donePromise;
}

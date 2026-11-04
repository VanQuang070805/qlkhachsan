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
} from '../shared/thread-store.js';

export interface ChatbotJobPayload {
  threadId: string;
  query: string;
  fingerprint: string;
  config: Record<string, any>;
}

export interface StreamEventItem {
  event: 'updates' | 'messages/partial' | 'end' | 'error';
  data: any;
}

const activeInProcessStreams = new Map<string, EventEmitter>();
let bullQueue: Queue<ChatbotJobPayload> | null = null;

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
  const redis = getRedisQueueClient();
  const sKey = streamKey(payload.fingerprint);
  const thread = await getOrCreateThread(payload.threadId);

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
        messages: thread.values.messages.slice(-8),
      },
      {
        ...payload.config,
        version: 'v2',
      },
    );

    let accumulatedAi = '';
    let retrievedDocs: any[] = [];

    for await (const ev of eventStream) {
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
    }

    if (accumulatedAi.trim()) {
      thread.values.messages = [
        ...thread.values.messages.slice(-8),
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
  } catch (error) {
    const providerUnavailable = isRetryableAIProviderError(error);
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
  const existingEmitter = activeInProcessStreams.get(fp);

  if (existingEmitter) {
    return new Promise<void>((resolve) => {
      const handler = (item: StreamEventItem) => onItem(item);
      const closeHandler = () => {
        existingEmitter.off('item', handler);
        resolve();
      };
      existingEmitter.on('item', handler);
      existingEmitter.once('close', closeHandler);
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
  activeInProcessStreams.set(fp, emitter);

  const donePromise = new Promise<void>((resolve) => {
    const handler = (item: StreamEventItem) => onItem(item);
    const closeHandler = () => {
      emitter.off('item', handler);
      activeInProcessStreams.delete(fp);
      resolve();
    };
    emitter.on('item', handler);
    emitter.once('close', closeHandler);
  });

  void executeRetrievalRunAndBroadcast(payload, emitter);

  return donePromise;
}

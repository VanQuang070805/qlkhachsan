import dotenv from 'dotenv';
dotenv.config();

import http from 'http';
import crypto from 'crypto';
import { AIMessage, HumanMessage } from '@langchain/core/messages';
import { graph as ingestionGraph } from './ingestion_graph/graph.js';
import { graph as retrievalGraph } from './retrieval_graph/graph.js';
import { sanitizePublicSource } from './retrieval_graph/utils.js';
import { isRetryableAIProviderError } from './shared/utils.js';
import {
  deleteThreadById,
  getOrCreateThread,
  saveThread,
  conversationFromClient,
} from './shared/thread-store.js';
import { subscribeOrRunDeduplicated } from './queue/chatbot-queue.js';
import { answerClockQuestion } from './retrieval_graph/conversation.js';

function checkInternalAuth(req: http.IncomingMessage): {
  allowed: boolean;
  misconfigured: boolean;
} {
  const configuredKey = (process.env.LANGGRAPH_INTERNAL_API_KEY || '').trim();
  const requireKey =
    process.env.REQUIRE_INTERNAL_API_KEY === 'true' ||
    process.env.NODE_ENV === 'production';

  if (!configuredKey) {
    if (requireKey) {
      return { allowed: false, misconfigured: true };
    }
    return { allowed: true, misconfigured: false };
  }

  const authHeader = String(req.headers.authorization || '');
  const apiKeyHeader = String(
    req.headers['x-internal-api-key'] || req.headers['x-api-key'] || '',
  );
  const bearer = authHeader.startsWith('Bearer ')
    ? authHeader.slice(7).trim()
    : '';
  const candidate = bearer || apiKeyHeader;
  if (!candidate || candidate.length !== configuredKey.length) {
    return { allowed: false, misconfigured: false };
  }

  const matches = crypto.timingSafeEqual(
    Buffer.from(candidate),
    Buffer.from(configuredKey),
  );
  return { allowed: matches, misconfigured: false };
}

async function readJsonBody(
  req: http.IncomingMessage,
): Promise<Record<string, any>> {
  return new Promise((resolve, reject) => {
    let raw = '';
    req.on('data', (chunk) => {
      raw += chunk;
      if (raw.length > 64 * 1024) {
        reject(new Error('PAYLOAD_TOO_LARGE'));
      }
    });
    req.on('end', () => {
      if (!raw.trim()) return resolve({});
      try {
        resolve(JSON.parse(raw));
      } catch (e) {
        reject(e);
      }
    });
    req.on('error', reject);
  });
}

function sendJson(
  res: http.ServerResponse,
  status: number,
  payload: unknown,
  correlationId?: string,
): void {
  const headers: Record<string, string> = {
    'Content-Type': 'application/json; charset=utf-8',
    'Cache-Control': 'no-store',
  };
  if (correlationId) {
    headers['X-Correlation-ID'] = correlationId;
  }
  res.writeHead(status, headers);
  res.end(JSON.stringify(payload));
}

export function createLangGraphHttpServer(): http.Server {
  return http.createServer(async (req, res) => {
    const url = new URL(req.url || '/', 'http://127.0.0.1');
    const pathname = url.pathname;
    const method = req.method || 'GET';
    const correlationId = String(
      req.headers['x-correlation-id'] || crypto.randomUUID(),
    );

    if (method === 'GET' && (pathname === '/ok' || pathname === '/health')) {
      return sendJson(
        res,
        200,
        {
          ok: true,
          service: 'royal-ai-langgraph',
          gemini_configured: Boolean(
            (
              process.env.GOOGLE_API_KEY ||
              process.env.GEMINI_API_KEY ||
              ''
            ).trim(),
          ),
          vector_provider: process.env.VECTOR_PROVIDER || 'mysql',
        },
        correlationId,
      );
    }

    const authResult = checkInternalAuth(req);
    if (authResult.misconfigured) {
      return sendJson(
        res,
        503,
        {
          error: 'INTERNAL_AUTH_UNCONFIGURED',
          message: 'Service internal key is not configured.',
          correlation_id: correlationId,
        },
        correlationId,
      );
    }

    if (!authResult.allowed) {
      return sendJson(
        res,
        401,
        {
          error: 'UNAUTHORIZED',
          message: 'Unauthorized.',
          correlation_id: correlationId,
        },
        correlationId,
      );
    }

    try {
      // POST /threads -> create thread
      if (method === 'POST' && pathname === '/threads') {
        const body = await readJsonBody(req);
        const thread = await getOrCreateThread(
          body.thread_id,
          body.metadata || {},
        );
        return sendJson(
          res,
          200,
          {
            thread_id: thread.thread_id,
            created_at: thread.created_at,
            updated_at: thread.updated_at,
            metadata: thread.metadata,
            status: 'idle',
          },
          correlationId,
        );
      }

      // GET /threads/:id
      const threadMatch = pathname.match(/^\/threads\/([^/]+)$/);
      if (method === 'GET' && threadMatch) {
        const thread = await getOrCreateThread(threadMatch[1]);
        return sendJson(res, 200, thread, correlationId);
      }

      // DELETE /threads/:id
      if (method === 'DELETE' && threadMatch) {
        await deleteThreadById(threadMatch[1]);
        return sendJson(res, 200, { deleted: true }, correlationId);
      }

      // POST /threads/:id/runs/wait
      const waitMatch = pathname.match(/^\/threads\/([^/]+)\/runs\/wait$/);
      if (method === 'POST' && waitMatch) {
        const thread = await getOrCreateThread(waitMatch[1]);
        const body = await readJsonBody(req);
        const assistantId = String(body.assistant_id || 'retrieval_graph');
        const input = body.input || {};
        const config = body.config || {};

        if (assistantId === 'ingestion_graph') {
          const result = await ingestionGraph.invoke(input, config);
          return sendJson(res, 200, result, correlationId);
        }

        const stateInput = {
          query: String(input.query || ''),
          messages: (thread.values.messages.length ? thread.values.messages : conversationFromClient(input.history)).slice(-8),
        };
        const result = await retrievalGraph.invoke(stateInput, config);
        thread.values.messages = (result.messages || []).slice(-10);
        thread.values.documents = result.documents || [];
        await saveThread(thread);

        const lastMessage =
          result.messages?.[result.messages.length - 1]?.content || '';
        const sources = Array.isArray(result.documents)
          ? result.documents.map((doc: any) =>
              sanitizePublicSource(doc.metadata),
            )
          : [];

        return sendJson(
          res,
          200,
          {
            thread_id: thread.thread_id,
            reply: String(lastMessage).replace(/\*\*/g, '').trim(),
            sources,
            values: {
              messages: thread.values.messages.map((m) => ({
                type: m instanceof HumanMessage ? 'human' : 'ai',
                content: String(m.content || ''),
              })),
            },
          },
          correlationId,
        );
      }

      // POST /threads/:id/runs/stream
      const streamMatch = pathname.match(/^\/threads\/([^/]+)\/runs\/stream$/);
      if (method === 'POST' && streamMatch) {
        const thread = await getOrCreateThread(streamMatch[1]);
        const body = await readJsonBody(req);
        const input = body.input || {};
        const config = body.config || {};
        const query = String(input.query || '').trim();

        if (!query || query.length > 1000) {
          return sendJson(
            res,
            422,
            {
              error: 'INVALID_QUERY',
              message: 'Query is required and must be <= 1000 characters.',
              correlation_id: correlationId,
            },
            correlationId,
          );
        }

        const clockReply = answerClockQuestion(query);
        if (clockReply) {
          const previous = thread.values.messages.length
            ? thread.values.messages : conversationFromClient(input.history);
          thread.values.messages = [
            ...previous.slice(-8), new HumanMessage(query), new AIMessage(clockReply),
          ];
          await saveThread(thread);
          res.writeHead(200, {
            'Content-Type': 'text/event-stream; charset=utf-8',
            'Cache-Control': 'no-cache, no-transform',
            'X-Accel-Buffering': 'no',
            'X-Correlation-ID': correlationId,
          });
          res.write(`event: messages/partial\ndata: ${JSON.stringify({ event: 'messages/partial', data: [{ type: 'ai', content: clockReply, delta: clockReply }] })}\n\n`);
          res.end(`event: end\ndata: ${JSON.stringify({ event: 'end', data: { done: true, sources: [] } })}\n\n`);
          return;
        }

        const fingerprint =
          String(config?.configurable?.dedupeFingerprint || '').trim() ||
          crypto
            .createHash('sha256')
            .update(`${thread.thread_id}|${query.toLowerCase()}`)
            .digest('hex');

        res.writeHead(200, {
          'Content-Type': 'text/event-stream; charset=utf-8',
          'Cache-Control': 'no-cache, no-transform',
          Connection: 'keep-alive',
          'X-Accel-Buffering': 'no',
          'X-Correlation-ID': correlationId,
        });
        res.flushHeaders();
        res.write(': connected\n\n');

        let clientClosed = false;
        res.on('close', () => {
          clientClosed = true;
        });
        const heartbeat = setInterval(() => {
          if (!clientClosed && !res.writableEnded) res.write(': keepalive\n\n');
        }, 10_000);
        heartbeat.unref();

        const writeSse = (event: string, data: unknown) => {
          if (clientClosed || res.writableEnded) return;
          res.write(`event: ${event}\n`);
          res.write(`data: ${JSON.stringify({ event, data })}\n\n`);
        };

        try {
          await subscribeOrRunDeduplicated(
            {
              threadId: thread.thread_id,
              query,
              fingerprint,
              config,
              history: Array.isArray(input.history) ? input.history : [],
            },
            (item) => {
              writeSse(item.event, item.data);
            },
          );
        } catch (error) {
          writeSse('error', {
            error: isRetryableAIProviderError(error)
              ? 'AI_PROVIDER_UNAVAILABLE'
              : 'UPSTREAM_EXECUTION_ERROR',
            retryable: isRetryableAIProviderError(error),
            correlation_id: correlationId,
          });
        } finally {
          clearInterval(heartbeat);
          if (!res.writableEnded) {
            res.end();
          }
        }
        return;
      }

      return sendJson(
        res,
        404,
        { error: 'NOT_FOUND', correlation_id: correlationId },
        correlationId,
      );
    } catch (error) {
      const providerUnavailable = isRetryableAIProviderError(error);
      return sendJson(
        res,
        providerUnavailable ? 503 : 500,
        {
          error: providerUnavailable
            ? 'AI_PROVIDER_UNAVAILABLE'
            : 'INTERNAL_SERVER_ERROR',
          message: providerUnavailable
            ? 'Dịch vụ AI đang bận. Vui lòng thử lại sau ít phút.'
            : 'Không thể xử lý yêu cầu.',
          retryable: providerUnavailable,
          correlation_id: correlationId,
        },
        correlationId,
      );
    }
  });
}

const isEntryPoint =
  process.argv[1] &&
  (process.argv[1].replace(/\\/g, '/').endsWith('src/server.ts') ||
    process.argv[1].replace(/\\/g, '/').endsWith('src/server.js'));

if (isEntryPoint) {
  const host = process.env.LANGGRAPH_HOST || '127.0.0.1';
  const port = Number(process.env.LANGGRAPH_PORT || 2024);
  const server = createLangGraphHttpServer();
  server.listen(port, host, () => {
    console.log(
      `[royal-ai-langgraph] Listening on http://${host}:${port} (provider=${process.env.VECTOR_PROVIDER || 'mysql'})`,
    );
  });
}

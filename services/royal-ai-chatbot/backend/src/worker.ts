import dotenv from 'dotenv';
dotenv.config();

import { Worker } from 'bullmq';
import {
  ChatbotJobPayload,
  executeRetrievalRunAndBroadcast,
} from './queue/chatbot-queue.js';
import { getRedisQueueClient } from './shared/thread-store.js';

const redis = getRedisQueueClient();
if (!redis) {
  console.error(
    '[royal-chatbot-worker] REDIS_QUEUE_HOST or REDIS_QUEUE_URL is required to run the BullMQ worker.',
  );
  process.exit(1);
}

const concurrency = Math.max(
  1,
  Number(process.env.CHATBOT_WORKER_CONCURRENCY || 2),
);

const worker = new Worker<ChatbotJobPayload>(
  'royal-chatbot-runs',
  async (job) => {
    await executeRetrievalRunAndBroadcast(job.data);
  },
  {
    connection: redis as any,
    concurrency,
    limiter: {
      max: Number(process.env.CHATBOT_WORKER_RATE_MAX || 30),
      duration: 60000,
    },
  },
);

worker.on('completed', (job) => {
  console.log(
    JSON.stringify({
      service: 'royal-chatbot-worker',
      event: 'job_completed',
      job_id: job.id,
      fingerprint: job.data.fingerprint,
    }),
  );
});

worker.on('failed', (job, err) => {
  console.error(
    JSON.stringify({
      service: 'royal-chatbot-worker',
      event: 'job_failed',
      job_id: job?.id,
      error_code: err?.name || 'WORKER_ERROR',
    }),
  );
});

const shutdown = async () => {
  await worker.close();
  await redis.quit();
  process.exit(0);
};

process.on('SIGTERM', shutdown);
process.on('SIGINT', shutdown);

console.log(
  `[royal-chatbot-worker] BullMQ worker started (concurrency=${concurrency})`,
);

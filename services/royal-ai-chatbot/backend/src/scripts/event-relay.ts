import dotenv from 'dotenv';
dotenv.config();

import mysql from 'mysql2/promise';
import { Kafka } from 'kafkajs';

const brokers = (process.env.KAFKA_BROKERS || 'kafka:9092')
  .split(',')
  .map((b) => b.trim())
  .filter(Boolean);

const kafka = new Kafka({
  clientId: process.env.KAFKA_CLIENT_ID || 'royal-hotel-outbox-relay',
  brokers,
});

const producer = kafka.producer();
let running = true;

async function createDbPool(): Promise<mysql.Pool> {
  return mysql.createPool({
    host: process.env.MYSQL_HOST || process.env.DB_HOST || '127.0.0.1',
    port: Number(process.env.MYSQL_PORT || process.env.DB_PORT || 3306),
    user: process.env.MYSQL_USER || process.env.DB_USERNAME || 'root',
    password: process.env.MYSQL_PASSWORD || process.env.DB_PASSWORD || '',
    database:
      process.env.MYSQL_DATABASE || process.env.DB_DATABASE || 'qlkhachsan',
    waitForConnections: true,
    connectionLimit: 4,
  });
}

async function relayBatch(pool: mysql.Pool): Promise<number> {
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();
    const [rows] = await conn.query<mysql.RowDataPacket[]>(
      `SELECT id, event_id, topic, event_key, payload, attempts
       FROM integration_outbox
       WHERE published_at IS NULL AND available_at <= NOW()
       ORDER BY id ASC
       LIMIT 25
       FOR UPDATE`,
    );

    if (!rows.length) {
      await conn.commit();
      return 0;
    }

    let publishedCount = 0;
    for (const row of rows) {
      try {
        const valueStr =
          typeof row.payload === 'string'
            ? row.payload
            : JSON.stringify(row.payload);

        await producer.send({
          topic: String(row.topic),
          messages: [
            {
              key: String(row.event_key || row.event_id),
              value: valueStr,
              headers: {
                event_id: String(row.event_id),
              },
            },
          ],
        });

        await conn.execute(
          `UPDATE integration_outbox
           SET published_at = NOW(), updated_at = NOW(), last_error = NULL
           WHERE id = ?`,
          [row.id],
        );
        publishedCount += 1;
      } catch (err: any) {
        const attempts = Number(row.attempts || 0) + 1;
        const backoffSeconds = Math.min(300, Math.pow(2, Math.min(8, attempts)));
        await conn.execute(
          `UPDATE integration_outbox
           SET attempts = ?,
               available_at = DATE_ADD(NOW(), INTERVAL ? SECOND),
               last_error = ?,
               updated_at = NOW()
           WHERE id = ?`,
          [
            attempts,
            backoffSeconds,
            String(err?.code || err?.name || 'KAFKA_PUBLISH_FAILED').slice(
              0,
              250,
            ),
            row.id,
          ],
        );
      }
    }

    await conn.commit();
    return publishedCount;
  } catch (error: any) {
    await conn.rollback();
    console.warn(JSON.stringify({
      service: 'royal-chatbot-event-relay',
      event: 'outbox_batch_failed',
      error_code: String(error?.code || error?.name || 'OUTBOX_BATCH_FAILED'),
    }));
    return 0;
  } finally {
    conn.release();
  }
}

async function main() {
  const pool = await createDbPool();

  const stop = async () => {
    running = false;
    try {
      await producer.disconnect();
    } catch {
      // Ignore
    }
    await pool.end();
    process.exit(0);
  };

  process.on('SIGTERM', stop);
  process.on('SIGINT', stop);

  while (running) {
    try {
      await producer.connect();
      const count = await relayBatch(pool);
      await new Promise((r) => setTimeout(r, count > 0 ? 500 : 2500));
    } catch {
      // Kafka or MySQL temporarily unreachable; wait and retry without crashing web/chatbot
      await new Promise((r) => setTimeout(r, 5000));
    }
  }
}

void main();

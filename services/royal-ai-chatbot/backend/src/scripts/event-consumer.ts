import dotenv from 'dotenv';
dotenv.config();

import { Kafka } from 'kafkajs';

const brokers = (process.env.KAFKA_BROKERS || 'kafka:9092')
  .split(',')
  .map((b) => b.trim())
  .filter(Boolean);

const kafka = new Kafka({
  clientId: process.env.KAFKA_CLIENT_ID || 'royal-hotel-event-consumer',
  brokers,
});

const consumer = kafka.consumer({
  groupId: process.env.KAFKA_CONSUMER_GROUP || 'royal-hotel-audit-consumer-v1',
});

const processedEventIds = new Set<string>();
const MAX_PROCESSED_CACHE = 5000;

async function main() {
  const stop = async () => {
    try {
      await consumer.disconnect();
    } catch {
      // Ignore
    }
    process.exit(0);
  };

  process.on('SIGTERM', stop);
  process.on('SIGINT', stop);

  while (true) {
    try {
      await consumer.connect();
      await consumer.subscribe({
        topics: [
          'royal.chatbot.events.v1',
          'royal.hotel.catalog.v1',
          'royal.security.audit.v1',
        ],
        fromBeginning: false,
      });

      await consumer.run({
        eachMessage: async ({ topic, message }) => {
          const raw = message.value?.toString() || '{}';
          const parsed = JSON.parse(raw);
          const eventId = String(parsed.event_id || '');

          if (eventId && processedEventIds.has(eventId)) {
            return;
          }
          if (eventId) {
            if (processedEventIds.size >= MAX_PROCESSED_CACHE) {
              const first = processedEventIds.values().next().value;
              if (first) processedEventIds.delete(first);
            }
            processedEventIds.add(eventId);
          }

          console.log(
            JSON.stringify({
              service: 'royal-event-consumer',
              topic,
              event_id: eventId,
              event_type: parsed.event_type,
              correlation_id: parsed.correlation_id,
              occurred_at: parsed.occurred_at,
            }),
          );
        },
      });
      break;
    } catch {
      await new Promise((r) => setTimeout(r, 5000));
    }
  }
}

void main();

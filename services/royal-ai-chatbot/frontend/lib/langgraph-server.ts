import { Client } from '@langchain/langgraph-sdk';
import { LangGraphBase } from './langgraph-base';

let clientInstance: LangGraphBase | null = null;

export const createServerClient = () => {
  if (clientInstance) {
    return clientInstance;
  }

  const apiUrl =
    process.env.LANGGRAPH_API_URL ||
    process.env.NEXT_PUBLIC_LANGGRAPH_API_URL ||
    'http://127.0.0.1:2024';

  const defaultHeaders: Record<string, string> = {
    'Content-Type': 'application/json',
  };

  if (process.env.LANGCHAIN_API_KEY) {
    defaultHeaders['X-Api-Key'] = process.env.LANGCHAIN_API_KEY;
  }

  if (process.env.LANGGRAPH_INTERNAL_API_KEY) {
    defaultHeaders['Authorization'] =
      `Bearer ${process.env.LANGGRAPH_INTERNAL_API_KEY}`;
  }

  const client = new Client({
    apiUrl,
    defaultHeaders,
  });

  clientInstance = new LangGraphBase(client);
  return clientInstance;
};
export const langGraphServerClient = {
  createThread: () => createServerClient().createThread(),
  getThreadState: (threadId: string) =>
    createServerClient().getThreadState(threadId),
  get client() {
    return createServerClient().client;
  },
};

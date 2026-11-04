import { BaseChatModel } from '@langchain/core/language_models/chat_models';
import { BaseLanguageModelInput } from '@langchain/core/language_models/base';
import { BaseMessage } from '@langchain/core/messages';
import { ChatGoogleGenerativeAI } from '@langchain/google-genai';
import { initChatModel } from 'langchain/chat_models/universal';

const SUPPORTED_PROVIDERS = [
  'openai',
  'anthropic',
  'azure_openai',
  'cohere',
  'google-vertexai',
  'google-vertexai-web',
  'google-genai',
  'ollama',
  'together',
  'fireworks',
  'mistralai',
  'groq',
  'bedrock',
  'cerebras',
  'deepseek',
  'xai',
] as const;

export function getGoogleApiKey(): string {
  const key = (
    process.env.GOOGLE_API_KEY ||
    process.env.GEMINI_API_KEY ||
    ''
  ).trim();
  if (!key) {
    throw new Error(
      'Missing GOOGLE_API_KEY in backend/.env. Please configure a Google Gemini API key.',
    );
  }
  return key;
}

export function getDefaultChatModelName(): string {
  const configured = (
    process.env.GEMINI_CHAT_MODEL || 'gemini-flash-lite-latest'
  ).trim();
  return configured.includes('/') ? configured : `google-genai/${configured}`;
}

export function getGeminiRequestTimeoutMs(): number {
  const configured = Number(process.env.GEMINI_REQUEST_TIMEOUT_MS || 20000);
  return Number.isFinite(configured)
    ? Math.min(60000, Math.max(5000, configured))
    : 20000;
}

export function isRetryableAIProviderError(error: unknown): boolean {
  const candidate = error as {
    status?: number;
    code?: number | string;
    response?: { status?: number };
    name?: string;
    message?: string;
  };
  const status = Number(
    candidate?.status ?? candidate?.response?.status ?? candidate?.code,
  );
  const message =
    `${candidate?.name || ''} ${candidate?.message || ''}`.toLowerCase();

  return (
    [429, 500, 502, 503, 504].includes(status) ||
    /high demand|service unavailable|resource exhausted|rate limit|timeout|timed out|aborterror/.test(
      message,
    )
  );
}

export async function withAIRequestTimeout<T>(
  operation: (signal: AbortSignal) => Promise<T>,
): Promise<T> {
  const controller = new AbortController();
  const timeoutMs = getGeminiRequestTimeoutMs();
  let timer: ReturnType<typeof setTimeout> | undefined;
  const timeout = new Promise<never>((_, reject) => {
    timer = setTimeout(() => {
      controller.abort();
      const error = new Error(`AI request timed out after ${timeoutMs}ms`);
      error.name = 'TimeoutError';
      reject(error);
    }, timeoutMs);
  });

  try {
    return await Promise.race([operation(controller.signal), timeout]);
  } finally {
    if (timer) clearTimeout(timer);
  }
}

export async function loadChatModel(
  fullySpecifiedName?: string,
  temperature = 0.2,
): Promise<BaseChatModel> {
  const resolvedName = (
    fullySpecifiedName && !fullySpecifiedName.startsWith('openai/')
      ? fullySpecifiedName
      : getDefaultChatModelName()
  ).trim();

  const index = resolvedName.indexOf('/');
  const provider = index === -1 ? 'google-genai' : resolvedName.slice(0, index);
  const model = index === -1 ? resolvedName : resolvedName.slice(index + 1);

  if (provider === 'google-genai') {
    const apiKey = getGoogleApiKey();
    return new ChatGoogleGenerativeAI({
      apiKey,
      model,
      temperature,
      maxOutputTokens: 1024,
      maxRetries: Math.min(
        3,
        Math.max(0, Number(process.env.GEMINI_MAX_RETRIES || 0)),
      ),
    });
  }

  if (
    !SUPPORTED_PROVIDERS.includes(
      provider as (typeof SUPPORTED_PROVIDERS)[number],
    )
  ) {
    throw new Error(`Unsupported provider: ${provider}`);
  }

  return await initChatModel(model, {
    modelProvider: provider,
    temperature,
  });
}

export async function invokeChatModelWithFallback(
  input: BaseLanguageModelInput,
  fullySpecifiedName?: string,
  temperature = 0.2,
): Promise<BaseMessage> {
  const primaryName = (
    fullySpecifiedName && !fullySpecifiedName.startsWith('openai/')
      ? fullySpecifiedName
      : getDefaultChatModelName()
  ).trim();
  const primary = await loadChatModel(primaryName, temperature);

  try {
    return await withAIRequestTimeout((signal) =>
      primary.invoke(input, { signal }),
    );
  } catch (error) {
    if (
      !primaryName.startsWith('google-genai/') ||
      !isRetryableAIProviderError(error)
    ) {
      throw error;
    }

    const configuredFallback = (
      process.env.GEMINI_FALLBACK_CHAT_MODEL || 'gemini-3.1-flash-lite'
    ).trim();
    const fallbackName = configuredFallback.includes('/')
      ? configuredFallback
      : `google-genai/${configuredFallback}`;
    if (fallbackName === primaryName) {
      throw error;
    }

    const fallback = await loadChatModel(fallbackName, temperature);
    return await withAIRequestTimeout((signal) =>
      fallback.invoke(input, { signal }),
    );
  }
}

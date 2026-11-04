import {
  getGeminiRequestTimeoutMs,
  isRetryableAIProviderError,
} from '../../src/shared/utils.js';

describe('AI provider resilience', () => {
  it('recognizes transient Gemini failures', () => {
    expect(isRetryableAIProviderError({ status: 503 })).toBe(true);
    expect(isRetryableAIProviderError({ status: 429 })).toBe(true);
    expect(
      isRetryableAIProviderError(
        new Error('This model is currently experiencing high demand'),
      ),
    ).toBe(true);
    expect(isRetryableAIProviderError({ status: 400 })).toBe(false);
  });

  it('clamps the request timeout to a safe range', () => {
    const original = process.env.GEMINI_REQUEST_TIMEOUT_MS;
    process.env.GEMINI_REQUEST_TIMEOUT_MS = '100';
    expect(getGeminiRequestTimeoutMs()).toBe(5000);
    process.env.GEMINI_REQUEST_TIMEOUT_MS = '120000';
    expect(getGeminiRequestTimeoutMs()).toBe(60000);
    if (original === undefined) {
      delete process.env.GEMINI_REQUEST_TIMEOUT_MS;
    } else {
      process.env.GEMINI_REQUEST_TIMEOUT_MS = original;
    }
  });
});

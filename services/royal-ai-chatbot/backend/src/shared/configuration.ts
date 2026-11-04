import { Annotation } from '@langchain/langgraph';
import { RunnableConfig } from '@langchain/core/runnables';

export type RetrieverProviderType = 'mysql' | 'supabase' | 'memory';

export const BaseConfigurationAnnotation = Annotation.Root({
  retrieverProvider: Annotation<RetrieverProviderType>,
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  filterKwargs: Annotation<Record<string, any>>,
  k: Annotation<number>,
});

export function ensureBaseConfiguration(
  config: RunnableConfig,
): typeof BaseConfigurationAnnotation.State {
  const configurable = (config?.configurable || {}) as Partial<
    typeof BaseConfigurationAnnotation.State
  >;
  const defaultProvider =
    (process.env.VECTOR_PROVIDER as RetrieverProviderType) || 'mysql';
  return {
    retrieverProvider: configurable.retrieverProvider || defaultProvider,
    filterKwargs: configurable.filterKwargs || { visibility: 'public' },
    k: configurable.k || 5,
  };
}

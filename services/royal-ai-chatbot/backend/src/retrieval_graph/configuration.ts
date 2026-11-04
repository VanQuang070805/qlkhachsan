import { Annotation } from '@langchain/langgraph';
import { RunnableConfig } from '@langchain/core/runnables';
import {
  BaseConfigurationAnnotation,
  ensureBaseConfiguration,
} from '../shared/configuration.js';
import { getDefaultChatModelName } from '../shared/utils.js';

export const AgentConfigurationAnnotation = Annotation.Root({
  ...BaseConfigurationAnnotation.spec,
  queryModel: Annotation<string>,
  capabilityToken: Annotation<string>,
  actorScope: Annotation<string>,
  correlationId: Annotation<string>,
  dedupeFingerprint: Annotation<string>,
});

export function ensureAgentConfiguration(
  config: RunnableConfig,
): typeof AgentConfigurationAnnotation.State {
  const configurable = (config?.configurable || {}) as Partial<
    typeof AgentConfigurationAnnotation.State
  >;
  const baseConfig = ensureBaseConfiguration(config);
  const requestedModel = configurable.queryModel || '';
  const safeModel =
    requestedModel && !requestedModel.startsWith('openai/')
      ? requestedModel
      : getDefaultChatModelName();
  return {
    ...baseConfig,
    queryModel: safeModel,
    capabilityToken: String(configurable.capabilityToken || ''),
    actorScope: String(configurable.actorScope || 'public'),
    correlationId: String(configurable.correlationId || ''),
    dedupeFingerprint: String(configurable.dedupeFingerprint || ''),
  };
}

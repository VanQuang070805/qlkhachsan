import { AgentConfiguration, IndexConfiguration } from '@/types/graphTypes';

type StreamConfigurables = AgentConfiguration;
type IndexConfigurables = IndexConfiguration;

export const retrievalAssistantStreamConfig: StreamConfigurables = {
  queryModel: 'google-genai/gemini-2.5-flash',
  retrieverProvider: 'mysql',
  k: 5,
};

export const indexConfig: IndexConfigurables = {
  useSampleDocs: false,
  retrieverProvider: 'mysql',
};

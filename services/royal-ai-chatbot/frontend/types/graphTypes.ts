import { Document } from '@langchain/core/documents';

export type documentType =
  | PDFDocument[]
  | { [key: string]: any }[]
  | string[]
  | string
  | 'delete';
export interface AgentState {
  query?: string;
  route?: string;
  messages: Array<{
    content: string;
    additional_kwargs: Record<string, any>;
    response_metadata: Record<string, any>;
    id: string;
    type: 'human' | 'assistant';
  }>;
  documents: documentType;
}

export interface RetrieveDocumentsNodeUpdates {
  retrieveDocuments: {
    documents: documentType;
  };
}

export type PDFDocument = Document & {
  metadata?: {
    loc?: {
      lines?: {
        from: number;
        to: number;
      };
      pageNumber?: number;
    };
    uuid?: string;
    source?: string;
    title?: string;
    version?: string;
  };
};

export interface BaseConfiguration {
  retrieverProvider?: 'mysql' | 'supabase' | 'memory';
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  filterKwargs?: Record<string, any>;
  k?: number;
}

export interface AgentConfiguration extends BaseConfiguration {
  queryModel?: string;
}

export interface IndexConfiguration extends BaseConfiguration {
  docsFile?: string;
  useSampleDocs?: boolean;
}

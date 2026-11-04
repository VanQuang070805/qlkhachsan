import {
  VectorStore,
  VectorStoreRetriever,
} from '@langchain/core/vectorstores';
import { EmbeddingsInterface } from '@langchain/core/embeddings';
import { GoogleGenerativeAIEmbeddings } from '@langchain/google-genai';
import { SupabaseVectorStore } from '@langchain/community/vectorstores/supabase';
import { createClient } from '@supabase/supabase-js';
import { RunnableConfig } from '@langchain/core/runnables';
import { Document } from '@langchain/core/documents';
import mysql from 'mysql2/promise';
import crypto from 'crypto';
import {
  BaseConfigurationAnnotation,
  ensureBaseConfiguration,
} from './configuration.js';

export function getEmbeddingModelName(): string {
  return (process.env.GEMINI_EMBEDDING_MODEL || 'gemini-embedding-001').trim();
}

export function createGeminiEmbeddings(): EmbeddingsInterface {
  const apiKey = (
    process.env.GOOGLE_API_KEY ||
    process.env.GEMINI_API_KEY ||
    ''
  ).trim();

  if (!apiKey) {
    // Provide a deterministic fallback embedding generator only when no key is configured
    // during offline tests/sync, while marking embedding_model appropriately.
    return {
      embedDocuments: async (documents: string[]): Promise<number[][]> => {
        return documents.map(() => []);
      },
      embedQuery: async (_document: string): Promise<number[]> => {
        return [];
      },
    };
  }

  return new GoogleGenerativeAIEmbeddings({
    apiKey,
    model: getEmbeddingModelName(),
    maxRetries: Math.min(
      3,
      Math.max(0, Number(process.env.GEMINI_MAX_RETRIES || 0)),
    ),
  });
}

function cosineSimilarity(left: number[], right: number[]): number {
  if (!left.length || left.length !== right.length) return 0;
  let dot = 0;
  let leftNorm = 0;
  let rightNorm = 0;
  for (let i = 0; i < left.length; i += 1) {
    dot += left[i] * right[i];
    leftNorm += left[i] * left[i];
    rightNorm += right[i] * right[i];
  }
  return leftNorm > 0 && rightNorm > 0
    ? dot / (Math.sqrt(leftNorm) * Math.sqrt(rightNorm))
    : 0;
}

function normalizeVietnamese(text: string): string {
  return text
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/đ/g, 'd')
    .replace(/[^a-z0-9\s]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();
}

function lexicalScore(query: string, target: string): number {
  const normQuery = normalizeVietnamese(query);
  const normTarget = normalizeVietnamese(target);
  if (!normQuery || !normTarget) return 0;
  const terms = Array.from(
    new Set(normQuery.split(' ').filter((term) => term.length > 2)),
  );
  let total = 0;
  for (const term of terms) {
    let idx = normTarget.indexOf(term);
    while (idx !== -1) {
      total += 1;
      idx = normTarget.indexOf(term, idx + term.length);
    }
  }
  return total;
}

export interface MySQLVectorStoreConfig {
  host?: string;
  port?: number;
  user?: string;
  password?: string;
  database?: string;
  tableName?: string;
}

let sharedPool: mysql.Pool | null = null;

export function getMySQLPool(config: MySQLVectorStoreConfig = {}): mysql.Pool {
  if (sharedPool) return sharedPool;
  sharedPool = mysql.createPool({
    host:
      config.host ||
      process.env.MYSQL_HOST ||
      process.env.DB_HOST ||
      '127.0.0.1',
    port: Number(
      config.port || process.env.MYSQL_PORT || process.env.DB_PORT || 3306,
    ),
    user:
      config.user ||
      process.env.MYSQL_USER ||
      process.env.DB_USERNAME ||
      'root',
    password:
      config.password ??
      process.env.MYSQL_PASSWORD ??
      process.env.DB_PASSWORD ??
      '',
    database:
      config.database ||
      process.env.MYSQL_DATABASE ||
      process.env.DB_DATABASE ||
      'qlkhachsan',
    waitForConnections: true,
    connectionLimit: 5,
    charset: 'utf8mb4',
  });
  return sharedPool;
}

export class MySQLKnowledgeVectorStore extends VectorStore {
  private pool: mysql.Pool;
  private tableName: string;
  private embeddingModelName: string;

  _vectorstoreType(): string {
    return 'mysql_knowledge_chunks';
  }

  constructor(
    embeddings: EmbeddingsInterface,
    config: MySQLVectorStoreConfig = {},
  ) {
    super(embeddings, config);
    this.pool = getMySQLPool(config);
    this.tableName = config.tableName || 'knowledge_chunks';
    this.embeddingModelName = getEmbeddingModelName();
  }

  async addDocuments(documents: Document[]): Promise<void> {
    if (!documents.length) return;
    const texts = documents.map((doc) => doc.pageContent);
    let vectors: number[][] = [];
    try {
      vectors = await this.embeddings.embedDocuments(texts);
    } catch {
      vectors = documents.map(() => []);
    }
    await this.addVectors(vectors, documents);
  }

  async addVectors(vectors: number[][], documents: Document[]): Promise<void> {
    if (!documents.length) return;
    const now = new Date();

    for (let i = 0; i < documents.length; i += 1) {
      const doc = documents[i];
      const meta = doc.metadata || {};
      const source = String(meta.source || 'Rosaliza Hotel');
      const title = String(meta.title || meta.section || 'Chính sách');
      const content = String(doc.pageContent || '').trim();
      if (!content) continue;

      const contentHash = String(
        meta.content_hash ||
          meta.uuid ||
          crypto
            .createHash('sha256')
            .update(`${source}|${title}|${content}`)
            .digest('hex'),
      ).slice(0, 64);

      const vector = vectors[i] && vectors[i].length > 0 ? vectors[i] : null;
      const embeddingJson = vector ? JSON.stringify(vector) : null;
      const modelName = vector ? this.embeddingModelName : null;

      await this.pool.execute(
        `INSERT INTO \`${this.tableName}\`
          (\`source\`, \`title\`, \`content\`, \`content_hash\`, \`embedding\`, \`embedding_model\`, \`created_at\`, \`updated_at\`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
          \`source\` = VALUES(\`source\`),
          \`title\` = VALUES(\`title\`),
          \`content\` = VALUES(\`content\`),
          \`embedding\` = VALUES(\`embedding\`),
          \`embedding_model\` = VALUES(\`embedding_model\`),
          \`updated_at\` = VALUES(\`updated_at\`)`,
        [
          source,
          title,
          content,
          contentHash,
          embeddingJson,
          modelName,
          now,
          now,
        ],
      );
    }
  }

  async deleteStaleHashes(activeHashes: string[]): Promise<void> {
    if (!activeHashes.length) return;
    const placeholders = activeHashes.map(() => '?').join(', ');
    await this.pool.execute(
      `DELETE FROM \`${this.tableName}\` WHERE \`content_hash\` NOT IN (${placeholders})`,
      activeHashes,
    );
  }

  async similaritySearchVectorWithScore(
    queryVector: number[],
    k: number,
    filter?: Record<string, unknown>,
  ): Promise<[Document, number][]> {
    const [rows] = await this.pool.query<mysql.RowDataPacket[]>(
      `SELECT \`id\`, \`source\`, \`title\`, \`content\`, \`content_hash\`, \`embedding\`, \`embedding_model\`
       FROM \`${this.tableName}\``,
    );

    const lastQueryText = String(filter?.__queryText || '');

    const scored: Array<{ doc: Document; score: number }> = [];
    for (const row of rows) {
      let rowVector: number[] = [];
      if (row.embedding) {
        try {
          rowVector =
            typeof row.embedding === 'string'
              ? JSON.parse(row.embedding)
              : Array.isArray(row.embedding)
                ? row.embedding
                : [];
        } catch {
          rowVector = [];
        }
      }

      const semantic =
        queryVector.length > 0 && rowVector.length === queryVector.length
          ? cosineSimilarity(queryVector, rowVector)
          : 0;

      const titleLex = lastQueryText
        ? lexicalScore(lastQueryText, String(row.title || ''))
        : 0;
      const contentLex = lastQueryText
        ? lexicalScore(lastQueryText, String(row.content || ''))
        : 0;

      const combinedScore = Number(
        (
          semantic * 100 +
          Math.min(contentLex * 8, 40) +
          Math.min(titleLex * 16, 64)
        ).toFixed(4),
      );

      if (
        combinedScore > 0 &&
        (semantic >= 0.18 || contentLex > 0 || titleLex > 0)
      ) {
        scored.push({
          score: combinedScore,
          doc: new Document({
            pageContent: String(row.content || ''),
            metadata: {
              uuid: String(row.content_hash || row.id),
              source: String(row.source || 'Rosaliza Hotel'),
              title: String(row.title || ''),
              version: 'approved-v1',
              visibility: 'public',
              score: combinedScore,
              semantic_score: Number(semantic.toFixed(4)),
            },
          }),
        });
      }
    }

    scored.sort((a, b) => b.score - a.score);
    return scored.slice(0, k).map((item) => [item.doc, item.score]);
  }

  async similaritySearch(
    query: string,
    k = 5,
    filter?: Record<string, unknown>,
  ): Promise<Document[]> {
    let queryVector: number[] = [];
    try {
      queryVector = await this.embeddings.embedQuery(query);
    } catch {
      queryVector = [];
    }
    const results = await this.similaritySearchVectorWithScore(queryVector, k, {
      ...(filter || {}),
      __queryText: query,
    });
    return results.map(([doc]) => doc);
  }
}

const memoryDocuments = new Map<string, { doc: Document; vector: number[] }>();

export class InMemoryKnowledgeVectorStore extends VectorStore {
  _vectorstoreType(): string {
    return 'memory_knowledge';
  }

  constructor(embeddings: EmbeddingsInterface) {
    super(embeddings, {});
  }

  async addDocuments(documents: Document[]): Promise<void> {
    if (!documents.length) return;
    let vectors: number[][] = [];
    try {
      vectors = await this.embeddings.embedDocuments(
        documents.map((d) => d.pageContent),
      );
    } catch {
      vectors = documents.map(() => []);
    }
    await this.addVectors(vectors, documents);
  }

  async addVectors(vectors: number[][], documents: Document[]): Promise<void> {
    documents.forEach((doc, idx) => {
      const id = String(
        doc.metadata?.uuid ||
          doc.metadata?.id ||
          crypto.createHash('sha256').update(doc.pageContent).digest('hex'),
      );
      memoryDocuments.set(id, {
        doc,
        vector: vectors[idx] || [],
      });
    });
  }

  async similaritySearchVectorWithScore(
    queryVector: number[],
    k: number,
    filter?: Record<string, unknown>,
  ): Promise<[Document, number][]> {
    const queryText = String(filter?.__queryText || '');
    const results: [Document, number][] = [];

    for (const { doc, vector } of memoryDocuments.values()) {
      const semantic =
        queryVector.length && vector.length === queryVector.length
          ? cosineSimilarity(queryVector, vector)
          : 0;
      const lex = queryText ? lexicalScore(queryText, doc.pageContent) : 0;
      const score = semantic * 100 + lex * 10;
      if (score > 0 || !queryText) {
        results.push([doc, score]);
      }
    }

    results.sort((a, b) => b[1] - a[1]);
    return results.slice(0, k);
  }

  async similaritySearch(
    query: string,
    k = 5,
    filter?: Record<string, unknown>,
  ): Promise<Document[]> {
    let queryVector: number[] = [];
    try {
      queryVector = await this.embeddings.embedQuery(query);
    } catch {
      queryVector = [];
    }
    const pairs = await this.similaritySearchVectorWithScore(queryVector, k, {
      ...(filter || {}),
      __queryText: query,
    });
    return pairs.map(([doc]) => doc);
  }
}

export async function makeSupabaseRetriever(
  configuration: typeof BaseConfigurationAnnotation.State,
): Promise<VectorStoreRetriever> {
  if (!process.env.SUPABASE_URL || !process.env.SUPABASE_SERVICE_ROLE_KEY) {
    throw new Error(
      'SUPABASE_URL or SUPABASE_SERVICE_ROLE_KEY environment variables are not defined',
    );
  }
  const embeddings = createGeminiEmbeddings();
  const supabaseClient = createClient(
    process.env.SUPABASE_URL ?? '',
    process.env.SUPABASE_SERVICE_ROLE_KEY ?? '',
  );
  const vectorStore = new SupabaseVectorStore(embeddings, {
    client: supabaseClient,
    tableName: 'documents',
    queryName: 'match_documents',
  });
  return vectorStore.asRetriever({
    k: configuration.k,
    filter: configuration.filterKwargs,
  });
}

export async function makeMySQLRetriever(
  configuration: typeof BaseConfigurationAnnotation.State,
): Promise<VectorStoreRetriever> {
  const embeddings = createGeminiEmbeddings();
  const vectorStore = new MySQLKnowledgeVectorStore(embeddings);
  return vectorStore.asRetriever({
    k: configuration.k,
    filter: configuration.filterKwargs,
  });
}

export async function makeMemoryRetriever(
  configuration: typeof BaseConfigurationAnnotation.State,
): Promise<VectorStoreRetriever> {
  const embeddings = createGeminiEmbeddings();
  const vectorStore = new InMemoryKnowledgeVectorStore(embeddings);
  return vectorStore.asRetriever({
    k: configuration.k,
    filter: configuration.filterKwargs,
  });
}

export async function makeRetriever(
  config: RunnableConfig,
): Promise<VectorStoreRetriever> {
  const configuration = ensureBaseConfiguration(config);
  switch (configuration.retrieverProvider) {
    case 'mysql':
      return makeMySQLRetriever(configuration);
    case 'supabase':
      return makeSupabaseRetriever(configuration);
    case 'memory':
      return makeMemoryRetriever(configuration);
    default:
      throw new Error(
        `Unsupported retriever provider: ${configuration.retrieverProvider}`,
      );
  }
}

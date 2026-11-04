import dotenv from 'dotenv';
dotenv.config();

import fs from 'fs/promises';
import path from 'path';
import crypto from 'crypto';
import { fileURLToPath } from 'url';
import { Document } from '@langchain/core/documents';
import { graph as ingestionGraph } from '../ingestion_graph/graph.js';
import {
  createGeminiEmbeddings,
  MySQLKnowledgeVectorStore,
} from '../shared/retrieval.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const APPROVED_KNOWLEDGE_FILES = [
  'royal-hotel.md',
  'royal-hotel-room-types.md',
  'royal-hotel-concierge.md',
];

export async function loadApprovedHotelDocuments(): Promise<{
  documents: Document[];
  activeHashes: string[];
}> {
  const knowledgeDir = path.resolve(
    __dirname,
    '../../../../resources/knowledge',
  );

  const documents: Document[] = [];
  const activeHashes: string[] = [];

  for (const fileName of APPROVED_KNOWLEDGE_FILES) {
    const filePath = path.join(knowledgeDir, fileName);
    let rawContent = '';
    try {
      rawContent = (await fs.readFile(filePath, 'utf-8')).trim();
    } catch {
      console.warn(`[sync-knowledge] Skipping missing file: ${fileName}`);
      continue;
    }

    if (!rawContent) {
      throw new Error(
        `[sync-knowledge] Approved knowledge file ${fileName} is empty. Aborting sync to preserve active index.`,
      );
    }

    const sections = rawContent.split(/(?=^##\s+)/m).filter((s) => s.trim());

    for (const section of sections) {
      const headingMatch = section.match(/^##\s+(.+)$/m);
      const sectionTitle = (
        headingMatch?.[1] || path.basename(fileName, '.md')
      ).trim();
      const body = section.replace(/^##\s+.+$/m, '').trim();
      if (!body) continue;

      const paragraphs = body.split(/\n\s*\n/u).filter((p) => p.trim());
      const chunks: string[] = [];
      let buffer = '';

      for (const paragraph of paragraphs) {
        const candidate = `${buffer}\n\n${paragraph}`.trim();
        if (candidate.length > 1200 && buffer !== '') {
          chunks.push(buffer);
          buffer = paragraph.trim();
        } else {
          buffer = candidate;
        }
      }
      if (buffer !== '') {
        chunks.push(buffer);
      }

      const publicSource = `Rosaliza Hotel · ${sectionTitle}`;
      chunks.forEach((chunkContent, chunkIndex) => {
        const contentHash = crypto
          .createHash('sha256')
          .update(`${publicSource}|${sectionTitle}|${chunkContent}`)
          .digest('hex');

        activeHashes.push(contentHash);
        documents.push(
          new Document({
            pageContent: chunkContent,
            metadata: {
              uuid: contentHash,
              source: publicSource,
              title: sectionTitle,
              section: sectionTitle,
              chunk_index: chunkIndex,
              version: '2026-11-02',
              effective_date: '2026-11-02',
              status: 'approved',
              content_hash: contentHash,
              visibility: 'public',
            },
          }),
        );
      });
    }
  }

  return { documents, activeHashes };
}

export async function syncApprovedHotelKnowledge(): Promise<{
  indexedCount: number;
  provider: string;
}> {
  const { documents, activeHashes } = await loadApprovedHotelDocuments();
  if (!documents.length) {
    throw new Error('No approved knowledge chunks found to index.');
  }

  const provider = (process.env.VECTOR_PROVIDER || 'mysql') as
    | 'mysql'
    | 'supabase'
    | 'memory';

  await ingestionGraph.invoke(
    { docs: documents },
    {
      configurable: {
        retrieverProvider: provider,
        useSampleDocs: false,
        docsFile: '',
        filterKwargs: {},
        k: 5,
      },
    },
  );

  if (provider === 'mysql') {
    const store = new MySQLKnowledgeVectorStore(createGeminiEmbeddings());
    await store.deleteStaleHashes(activeHashes);
  }

  return { indexedCount: documents.length, provider };
}

if (process.argv[1] && path.resolve(process.argv[1]) === __filename) {
  syncApprovedHotelKnowledge()
    .then(({ indexedCount, provider }) => {
      console.log(
        `[sync-knowledge] Indexed ${indexedCount} approved chunks into ${provider} vector store.`,
      );
      process.exit(0);
    })
    .catch((err) => {
      console.error('[sync-knowledge] Failed:', err);
      process.exit(1);
    });
}

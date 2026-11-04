import { Document } from '@langchain/core/documents';

function escapeXmlText(input: string): string {
  return input
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

export function sanitizePublicSource(meta?: Record<string, unknown>): {
  source: string;
  title: string;
  version: string;
} {
  const rawSource = String(meta?.source || 'Rosaliza Hotel');
  const rawTitle = String(meta?.title || meta?.section || 'Chính sách công khai');
  const rawVersion = String(meta?.version || 'approved-v1');
  // Strip any file paths if present
  const cleanSource = rawSource.replace(/[\\/]+/g, ' ').trim();
  const cleanTitle = rawTitle.replace(/[\\/]+/g, ' ').trim();
  return {
    source: cleanSource || 'Rosaliza Hotel',
    title: cleanTitle || 'Chính sách công khai',
    version: rawVersion,
  };
}

export function formatDoc(doc: Document): string {
  const safeMeta = sanitizePublicSource(doc.metadata);
  const safeContent = escapeXmlText(String(doc.pageContent || '').slice(0, 2000));
  return `<document source="${escapeXmlText(safeMeta.source)}" title="${escapeXmlText(safeMeta.title)}" version="${escapeXmlText(safeMeta.version)}">\n${safeContent}\n</document>`;
}

export function formatDocs(docs?: Document[]): string {
  if (!docs || docs.length === 0) {
    return '<documents></documents>';
  }
  const formatted = docs.map(formatDoc).join('\n');
  return `<documents>\n${formatted}\n</documents>`;
}

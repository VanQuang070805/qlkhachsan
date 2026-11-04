<?php

namespace App\Console\Commands;

use App\Models\KnowledgeChunk;
use App\Services\RoyalKnowledgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class IndexRoyalKnowledge extends Command
{
    protected $signature = 'knowledge:index {--no-embeddings : Chỉ lập chỉ mục văn bản, không gọi Gemini embedding API}';
    protected $description = 'Phân đoạn và lập chỉ mục kho kiến thức Rosaliza Hotel bằng Gemini Embedding';

    public function handle(RoyalKnowledgeService $knowledge): int
    {
        $chunks = $knowledge->documentChunks();
        $embeddings = $this->option('no-embeddings') ? [] : $knowledge->embedMany(array_column($chunks, 'content'));
        $hashes = [];

        foreach ($chunks as $index => $chunk) {
            $hash = hash('sha256', $chunk['source'].'|'.$chunk['title'].'|'.$chunk['content']);
            $hashes[] = $hash;
            KnowledgeChunk::query()->updateOrCreate(['content_hash' => $hash], [
                'source' => $chunk['source'],
                'title' => $chunk['title'],
                'content' => $chunk['content'],
                'embedding' => $embeddings[$index] ?? null,
                'embedding_model' => isset($embeddings[$index]) ? config('services.gemini.embedding_model') : null,
            ]);
        }

        KnowledgeChunk::query()->whereNotIn('content_hash', $hashes)->delete();
        Cache::forget('royal-knowledge-v2');
        $version = app(\App\Services\CacheVersionService::class)->bumpKnowledgeVersion();
        app(\App\Services\OutboxService::class)->recordCatalogMutation('knowledge_indexed', [
            'actor_type' => 'system',
            'knowledge_version' => $version,
            'chunk_count' => count($chunks),
        ]);
        $embedded = count($embeddings);
        $this->info('Đã lập chỉ mục '.count($chunks)." đoạn; {$embedded} đoạn có Gemini embedding.");
        if (! config('services.gemini.api_key') && ! $this->option('no-embeddings')) {
            $this->warn('Chưa có GOOGLE_API_KEY nên hệ thống lưu kho văn bản đã duyệt và dùng hybrid lexical retrieval cho đến khi cấu hình Gemini key.');
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Services;

use App\Models\KnowledgeChunk;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RoyalKnowledgeService
{
    public function search(string $query, int $limit = 3): array
    {
        if ($remote = $this->searchVectorStore($query, $limit)) {
            return $remote;
        }

        $chunks = $this->indexedChunks();
        $queryEmbedding = collect($chunks)->contains(fn (array $chunk) => ! empty($chunk['embedding']))
            ? $this->queryEmbedding($query)
            : null;

        return collect($chunks)->map(function (array $chunk) use ($query, $queryEmbedding) {
            $lexical = $this->score($query, $chunk['title'].' '.$chunk['content']);
            $semantic = $queryEmbedding && ! empty($chunk['embedding'])
                ? $this->cosine($queryEmbedding, $chunk['embedding'])
                : 0.0;

            return $chunk + [
                'score' => round(($semantic * 100) + min($lexical * 8, 40), 4),
                'semantic_score' => round($semantic, 4),
            ];
        })
            ->filter(fn (array $chunk) => $chunk['score'] > 0 && ($chunk['semantic_score'] >= .18 || $this->score($query, $chunk['content']) > 0))
            ->sortByDesc('score')
            ->take($limit)
            ->map(fn (array $chunk) => collect($chunk)->except('embedding')->all())
            ->values()
            ->all();
    }

    public function embedMany(array $texts): array
    {
        $key = config('services.royal_ai.key');
        if (! $key || $texts === []) {
            return [];
        }

        try {
            $response = Http::withToken($key)->acceptJson()->timeout(30)->retry(1, 250)
                ->post('https://api.openai.com/v1/embeddings', [
                    'model' => config('services.royal_ai.embedding_model'),
                    'input' => array_values($texts),
                    'encoding_format' => 'float',
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('data', []))->sortBy('index')->pluck('embedding')->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public function documentChunks(): array
    {
        $files = glob(resource_path('knowledge/*.md')) ?: [];

        return collect($files)->flatMap(function (string $path) {
            $document = trim((string) file_get_contents($path));
            $sections = preg_split('/(?=^##\s+)/m', $document) ?: [];

            return collect($sections)->filter()->flatMap(function (string $section) use ($path) {
                preg_match('/^##\s+(.+)$/m', $section, $match);
                $title = trim($match[1] ?? pathinfo($path, PATHINFO_FILENAME));
                $body = trim(preg_replace('/^##\s+.+$/m', '', $section));
                $paragraphs = preg_split('/\n\s*\n/u', $body) ?: [];
                $chunks = [];
                $buffer = '';
                foreach ($paragraphs as $paragraph) {
                    $candidate = trim($buffer."\n\n".$paragraph);
                    if (mb_strlen($candidate) > 1200 && $buffer !== '') {
                        $chunks[] = $buffer;
                        $buffer = trim($paragraph);
                    } else {
                        $buffer = $candidate;
                    }
                }
                if ($buffer !== '') $chunks[] = $buffer;

                return collect($chunks)->map(fn (string $content) => [
                    'title' => $title,
                    'source' => 'Royal Hotel · '.$title,
                    'content' => $content,
                ]);
            });
        })->values()->all();
    }

    private function searchVectorStore(string $query, int $limit): array
    {
        $storeId = config('services.royal_ai.vector_store_id');
        $key = config('services.royal_ai.key');
        if (! $storeId || ! $key) {
            return [];
        }

        try {
            $response = Http::withToken($key)->acceptJson()->timeout(8)
                ->post("https://api.openai.com/v1/vector_stores/{$storeId}/search", [
                    'query' => $query,
                    'max_num_results' => $limit,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return collect($response->json('data', []))->map(function (array $result) {
                $text = collect($result['content'] ?? [])->pluck('text')->filter()->implode("\n");

                return [
                    'title' => $result['filename'] ?? 'Tài liệu Royal Hotel',
                    'source' => $result['filename'] ?? 'Royal Hotel knowledge base',
                    'content' => Str::limit($text, 1600, ''),
                    'score' => (float) ($result['score'] ?? 0),
                ];
            })->filter(fn (array $item) => $item['content'] !== '')->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function indexedChunks(): array
    {
        return Cache::remember('royal-knowledge-v2', now()->addMinutes(10), function () {
            if (Schema::hasTable('knowledge_chunks') && KnowledgeChunk::query()->exists()) {
                return KnowledgeChunk::query()->get(['source', 'title', 'content', 'embedding'])->map(fn (KnowledgeChunk $chunk) => [
                    'source' => $chunk->source,
                    'title' => $chunk->title,
                    'content' => $chunk->content,
                    'embedding' => $chunk->embedding,
                ])->all();
            }

            return $this->documentChunks();
        });
    }

    private function queryEmbedding(string $query): ?array
    {
        return Cache::remember('royal-query-embedding-'.sha1($query), now()->addHour(), function () use ($query) {
            return $this->embedMany([$query])[0] ?? null;
        });
    }

    private function cosine(array $left, array $right): float
    {
        if (count($left) !== count($right) || $left === []) return 0.0;
        $dot = $leftNorm = $rightNorm = 0.0;
        foreach ($left as $index => $value) {
            $dot += $value * $right[$index];
            $leftNorm += $value ** 2;
            $rightNorm += $right[$index] ** 2;
        }

        return $leftNorm > 0 && $rightNorm > 0 ? $dot / (sqrt($leftNorm) * sqrt($rightNorm)) : 0.0;
    }

    private function score(string $query, string $content): int
    {
        $normalise = fn (string $value) => Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z0-9\s]/', ' ')->squish()->toString();
        $haystack = $normalise($content);
        $terms = array_unique(array_filter(explode(' ', $normalise($query)), fn (string $term) => mb_strlen($term) > 2));

        return array_sum(array_map(fn (string $term) => substr_count($haystack, $term), $terms));
    }
}

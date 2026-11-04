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
        $chunks = $this->indexedChunks();
        $queryEmbedding = collect($chunks)->contains(fn (array $chunk) => ! empty($chunk['embedding']))
            ? $this->queryEmbedding($query)
            : null;

        return collect($chunks)->map(function (array $chunk) use ($query, $queryEmbedding) {
            $titleLexical = $this->score($query, $chunk['title']);
            $contentLexical = $this->score($query, $chunk['content']);
            $semantic = $queryEmbedding && ! empty($chunk['embedding'])
                ? $this->cosine($queryEmbedding, $chunk['embedding'])
                : 0.0;

            return $chunk + [
                'score' => round(($semantic * 100) + min($contentLexical * 8, 40) + min($titleLexical * 16, 64), 4),
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
        $key = trim((string) config('services.gemini.api_key'));
        if ($key === '' || $texts === []) {
            return [];
        }

        $model = trim((string) config('services.gemini.embedding_model', 'text-embedding-004'));
        $embeddings = [];

        foreach (array_values($texts) as $index => $text) {
            try {
                $response = Http::acceptJson()
                    ->withHeaders(['X-goog-api-key' => $key])
                    ->timeout(15)
                    ->retry(1, 200)
                    ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$key}", [
                        'model' => "models/{$model}",
                        'content' => [
                            'parts' => [['text' => $text]],
                        ],
                    ]);

                if ($response->successful()) {
                    $values = $response->json('embedding.values');
                    if (is_array($values) && $values !== []) {
                        $embeddings[$index] = $values;
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $embeddings;
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
                if ($buffer !== '') {
                    $chunks[] = $buffer;
                }

                return collect($chunks)->map(fn (string $content) => [
                    'title' => $title,
                    'source' => 'Rosaliza Hotel · '.$title,
                    'content' => $content,
                ]);
            });
        })->values()->all();
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
        if (count($left) !== count($right) || $left === []) {
            return 0.0;
        }
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

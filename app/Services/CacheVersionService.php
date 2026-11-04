<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheVersionService
{
    private const KEY_CATALOG_VERSION = 'royal:version:catalog';
    private const KEY_KNOWLEDGE_VERSION = 'royal:version:knowledge';
    private const KEY_AVAILABILITY_VERSION = 'royal:version:availability';

    public function catalogVersion(): int
    {
        return (int) Cache::get(self::KEY_CATALOG_VERSION, 1);
    }

    public function bumpCatalogVersion(): int
    {
        $next = $this->catalogVersion() + 1;
        Cache::forever(self::KEY_CATALOG_VERSION, $next);

        return $next;
    }

    public function knowledgeVersion(): int
    {
        return (int) Cache::get(self::KEY_KNOWLEDGE_VERSION, 1);
    }

    public function bumpKnowledgeVersion(): int
    {
        $next = $this->knowledgeVersion() + 1;
        Cache::forever(self::KEY_KNOWLEDGE_VERSION, $next);
        Cache::forget('royal-knowledge-v2');

        return $next;
    }

    public function availabilityVersion(): int
    {
        return (int) Cache::get(self::KEY_AVAILABILITY_VERSION, 1);
    }

    public function bumpAvailabilityVersion(): int
    {
        $next = $this->availabilityVersion() + 1;
        Cache::forever(self::KEY_AVAILABILITY_VERSION, $next);

        return $next;
    }
}

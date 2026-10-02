<?php

namespace App\Console\Commands;

use App\Services\FaceId\FaceSyncService;
use Illuminate\Console\Command;
use Throwable;

class FaceSyncCommand extends Command
{
    protected $signature = 'face:sync {--full : Replace the Pi cache with all active profiles} {--limit=50}';
    protected $description = 'Synchronize pending Face ID changes to the Raspberry Pi';

    public function handle(FaceSyncService $sync): int
    {
        try {
            if ($this->option('full')) {
                $result = $sync->fullSync();
                $this->info('Full sync complete: '.json_encode($result, JSON_UNESCAPED_UNICODE));
                return self::SUCCESS;
            }

            $result = $sync->syncPending((int) $this->option('limit'));
            $this->info(sprintf(
                'Face sync: processed=%d synced=%d failed=%d deactivated=%d',
                $result['processed'], $result['synced'], $result['failed'], $result['deactivated']
            ));
            return $result['failed'] > 0 ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $error) {
            $this->error('Face sync failed: '.$error->getMessage());
            return self::FAILURE;
        }
    }
}


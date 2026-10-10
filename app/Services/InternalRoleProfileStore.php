<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class InternalRoleProfileStore
{
    private const FILE = 'internal-role-profiles.json';

    public function all(): array
    {
        $path = Storage::disk('local')->path(self::FILE);
        if (! is_file($path)) {
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Không thể đọc danh sách vai trò nội bộ.');
        }

        try {
            flock($handle, LOCK_SH);
            $profiles = json_decode(stream_get_contents($handle), true, 512, JSON_THROW_ON_ERROR);
            return is_array($profiles) ? $profiles : [];
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function create(string $name, array $permissions): ?array
    {
        $path = Storage::disk('local')->path(self::FILE);
        File::ensureDirectoryExists(dirname($path));
        $handle = fopen($path, 'c+');
        if ($handle === false) {
            throw new RuntimeException('Không thể lưu vai trò nội bộ.');
        }

        try {
            flock($handle, LOCK_EX);
            rewind($handle);
            $contents = stream_get_contents($handle);
            $profiles = $contents === '' ? [] : json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            $profiles = is_array($profiles) ? $profiles : [];
            $slug = Str::slug($name);

            if ($slug === '' || collect($profiles)->contains('slug', $slug)) {
                return null;
            }

            $profile = [
                'slug' => $slug,
                'name' => trim($name),
                'permissions' => array_values(array_unique($permissions)),
            ];
            $profiles[] = $profile;
            $json = json_encode($profiles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            rewind($handle);
            if (! ftruncate($handle, 0) || fwrite($handle, $json) !== strlen($json) || ! fflush($handle)) {
                throw new RuntimeException('Không thể lưu vai trò nội bộ.');
            }

            return $profile;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}

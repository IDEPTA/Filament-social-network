<?php

namespace App\Support;

use App\Models\File;

class FileUrl
{
    public static function download(File|int|string $file, ?bool $inline = null): string
    {
        $fileId = $file instanceof File ? $file->getKey() : $file;
        $params = ['file' => $fileId];

        if ($inline !== null) {
            $params['inline'] = $inline ? 1 : 0;
        }

        return route('admin.files.download', $params);
    }

    public static function fromState(mixed $state): ?string
    {
        if ($state instanceof File) {
            return self::download($state);
        }

        if (is_array($state)) {
            $id = $state['id'] ?? $state['file_id'] ?? null;

            if ($id) {
                return self::download($id);
            }

            $path = $state['path'] ?? null;
            $disk = $state['disk'] ?? null;

            if (is_string($path) && $path !== '') {
                $query = File::query()->where('path', $path);

                if (is_string($disk) && $disk !== '') {
                    $query->where('disk', $disk);
                }

                $file = $query->latest()->first();

                return $file ? self::download($file) : null;
            }

            return null;
        }

        if (is_string($state) && $state !== '') {
            if (is_numeric($state)) {
                return self::download($state);
            }

            $file = File::query()
                ->where('path', $state)
                ->latest()
                ->first();

            return $file ? self::download($file) : null;
        }

        if (is_int($state)) {
            return self::download($state);
        }

        return null;
    }
}

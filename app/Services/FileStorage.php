<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use League\Flysystem\FilesystemException;
use RuntimeException;

class FileStorage
{
    /**
     * Сохраняет содержимое в файл по указанному пути.
     *
     * @param  string      $path     Относительный путь к файлу.
     * @param  string|null $contents Содержимое файла.
     * @param  array       $meta     Метаданные (например, 'visibility', 'mime_type').
     * @param  string|null $disk     Имя диска (если null, используется диск по умолчанию).
     * @return string                Абсолютный или относительный путь к сохранённому файлу (зависит от драйвера).
     * @throws \RuntimeException      При ошибке записи.
     */
    public function put(
        string $path,
        ?string $contents = '',
        array $meta = [],
        ?string $disk = null
    ): string {
        $disk ??= config('files.upload_disk', config('filesystems.default', 'local'));

        $uploaded = Storage::disk($disk)->put(
            $path,
            $contents,
            ['visibility' => 'public', 'Metadata' => $meta]
        );

        if ($uploaded === false) {
            throw new RuntimeException("Не удалось загрузить файл {$path} на диск {$disk}.");
        }

        return $path;
    }


    /**
     * Генерирует временную ссылку для доступа к файлу.
     *
     * @param  string   $path       Относительный путь к файлу.
     * @param  int      $ttlSeconds Время жизни ссылки в секундах (по умолчанию 3600).
     * @param  string|null $disk    Имя диска.
     * @return string               Временный URL.
     * @throws \InvalidArgumentException Если диск не поддерживает временные ссылки.
     */
    public function temporaryUrl(
        string $path,
        int $ttlSeconds = 3600,
        ?string $disk = null
    ): string {
        $disk ??= config('files.upload_disk', config('filesystems.default', 'local'));
        $storage = Storage::disk($disk);

        try {
            $url = $storage->temporaryUrl(
                $path,
                now()->addSeconds($ttlSeconds)
            );
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'does not support creating temporary URLs')) {
                $url = $storage->url($path);
            } else {
                throw $exception;
            }
        }

        $externalUrl = config("filesystems.disks.{$disk}.url");

        if (is_string($externalUrl) && $externalUrl !== '') {
            return $this->applyExternalUrl($url, $disk);
        }

        if ($this->shouldProxyTemporaryUrl($url, $disk)) {
            return $this->buildProxyUrl($path, $disk, $ttlSeconds);
        }

        return $url;
    }

    /**
     * Удаляет файл по указанному пути.
     *
     * @param  string      $path Относительный путь к файлу.
     * @param  string|null $disk Имя диска.
     * @return void
     */
    public function delete(
        string $path,
        ?string $disk = null
    ): void {
        $disk ??= config('files.upload_disk', config('filesystems.default', 'local'));

        $storage = Storage::disk($disk);
        $deleted = $storage->delete($path);

        if ($deleted === false) {
            try {
                if ($storage->exists($path)) {
                    throw new RuntimeException("Не удалось удалить файл {$path} с диска {$disk}.");
                }
            } catch (FilesystemException) {
                return;
            }
        }
    }

    /**
     * Применяет внешний URL для прокси (например, для облачных дисков).
     *
     * @param  string $url  Исходный URL.
     * @param  string $disk Имя диска.
     * @return string       Модифицированный URL.
     */
    private function applyExternalUrl(string $url, string $disk): string
    {
        $externalUrl = config("filesystems.disks.{$disk}.url");

        if (! is_string($externalUrl) || $externalUrl === '') {
            return $url;
        }

        $urlParts = parse_url($url);
        $externalParts = parse_url($externalUrl);

        if (! is_array($urlParts) || ! is_array($externalParts) || ! isset($externalParts['host'])) {
            return $url;
        }

        $scheme = $externalParts['scheme'] ?? $urlParts['scheme'] ?? 'https';
        $host = $externalParts['host'];
        $port = isset($externalParts['port']) ? ':' . $externalParts['port'] : '';
        $basePath = $externalParts['path'] ?? '';
        $path = $urlParts['path'] ?? '';

        $basePath = rtrim($basePath, '/');
        $path = $path !== '' ? '/' . ltrim($path, '/') : '';

        if ($basePath !== '' && ! str_starts_with($path, $basePath . '/')) {
            $path = $basePath . $path;
        }

        $query = isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
        $fragment = isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '';

        return "{$scheme}://{$host}{$port}{$path}{$query}{$fragment}";
    }

    /**
     * Определяет, нужно ли проксировать временную ссылку через приложение.
     *
     * @param  string $url  URL.
     * @param  string $disk Имя диска.
     * @return bool         true, если требуется проксирование.
     */
    private function isTemporarySignedUrl(string $url): bool
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if (! is_string($query) || $query === '') {
            return false;
        }

        parse_str($query, $params);

        if (! is_array($params)) {
            return false;
        }

        return isset($params['X-Amz-Signature']) || isset($params['X-Amz-Algorithm']);
    }

    /**
     * Определяет, нужно ли проксировать временную ссылку через приложение.
     *
     * @param  string $url  URL.
     * @param  string $disk Имя диска.
     * @return bool         true, если требуется проксирование.
     */
    private function shouldProxyTemporaryUrl(string $url, string $disk): bool
    {
        $driver = config("filesystems.disks.{$disk}.driver");

        if ($driver !== 's3') {
            return false;
        }

        $endpoint = config("filesystems.disks.{$disk}.endpoint");
        $endpointHost = is_string($endpoint) ? parse_url($endpoint, PHP_URL_HOST) : null;
        $urlHost = parse_url($url, PHP_URL_HOST);

        if (! is_string($endpointHost) || ! is_string($urlHost)) {
            return false;
        }

        return $endpointHost === $urlHost && $this->isTemporarySignedUrl($url);
    }

    /**
     * Формирует внутренний URL для прокси-доступа к файлу.
     *
     * @param  string $path       Относительный путь.
     * @param  string $disk       Имя диска.
     * @param  int    $ttlSeconds Время жизни ссылки.
     * @return string             Прокси-URL.
     */
    private function buildProxyUrl(string $path, string $disk, int $ttlSeconds): string
    {
        return URL::temporarySignedRoute(
            'storage.minio',
            now()->addSeconds($ttlSeconds),
            [
                'disk' => $disk,
                'path' => $path,
            ]
        );
    }

    /**
     * Возвращает содержимое файла или ресурс для чтения.
     *
     * @param  string      $path Относительный путь.
     * @param  string|null $disk Имя диска.
     * @return mixed             Содержимое файла (строка) или потоковый ресурс.
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException Если файл не найден.
     */
    public function getFile(
        string $path,
        ?string $disk = null
    ) {
        $disk ??= config('files.upload_disk', config('filesystems.default', 'local'));
        if (! Storage::disk($disk)->exists($path)) {
            abort(404, 'Файл не найден');
        }

        return Storage::disk($disk)->get($path);
    }
}

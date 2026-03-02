<?php

namespace App\Services;

use App\Enums\FileKind;
use App\Models\File;
use App\Services\FileStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FileService
{
    public function __construct(
        private readonly FileStorage $storage
    ) {}

    /**
     * Создаёт запись File для уже существующего на диске файла.
     *
     * @param  string|null           $path            Относительный путь к файлу на диске.
     * @param  FileKind|string       $kind            Тип файла (значение enum или строка).
     * @param  Model|null            $model           Связанная модель (полиморфная связь).
     * @param  int|null              $createdBy       ID пользователя, создавшего запись.
     * @param  bool                  $isPublic        Флаг публичного доступа.
     * @param  string|null           $disk            Имя диска (если null, используется значение по умолчанию).
     * @param  array|null            $providedMetadata Дополнительные метаданные (original_name, mime, size и т.д.).
     * @return File                                   Созданная модель File.
     * @throws \InvalidArgumentException               Если путь пуст или диск не найден.
     */
    public function createFromStoredPath(
        ?string $path,
        FileKind|string $kind,
        ?Model $model = null,
        ?int $createdBy = null,
        bool $isPublic = false,
        ?string $disk = null,
        ?array $providedMetadata = null,
    ): File {
        if ($path === '' || $path === null) {
            throw ValidationException::withMessages([
                'path' => 'Файл не был загружен или путь не указан.',
            ]);
        }

        $disk ??= config('files.upload_disk', config('filesystems.default', 'local'));
        $fileKind = $this->resolveKind($kind);
        Log::info('FileService createFromStoredPath started', [
            'path' => $path,
            'disk' => $disk,
            'kind' => $fileKind->value,
            'is_public' => $isPublic,
            'model' => $model?->getMorphClass(),
            'model_id' => $model?->getKey(),
        ]);

        $metadata = $this->buildMetadata(
            $disk,
            $path,
            $isPublic,
            $providedMetadata,
        );

        $file = new File([
            'disk' => $disk,
            'path' => $path,
            'original_name' => $metadata['original_name'],
            'mime' => $metadata['mime'],
            'size' => $metadata['size'],
            'hash' => $metadata['hash'],
            'kind' => $fileKind,
            'model_type' => $model?->getMorphClass(),
            'model_id' => $model?->getKey(),
            'created_by' => $createdBy ?? Auth::id(),
        ]);

        $file->save();

        Log::info('FileService createFromStoredPath completed', [
            'file_id' => $file->getKey(),
            'path' => $file->path,
            'disk' => $file->disk,
            'mime' => $file->mime,
            'size' => $file->size,
        ]);

        return $file;
    }

    /**
     * Обновляет существующую запись File новым путём и параметрами.
     *
     * @param  File                  $record          Существующая модель File.
     * @param  string                $path            Новый относительный путь к файлу.
     * @param  FileKind|string       $kind            Тип файла (значение enum или строка).
     * @param  bool                  $isPublic        Флаг публичного доступа.
     * @param  string|null           $disk            Имя диска (если null, оставляется текущий диск).
     * @param  array|null            $providedMetadata Дополнительные метаданные для обновления.
     * @param  array                 $additionalData  Дополнительные поля для обновления (например, model_type, model_id).
     * @return File                                   Обновлённая модель File.
     */
    public function updateFromStoredPath(
        File $record,
        string $path,
        FileKind|string $kind,
        bool $isPublic = false,
        ?string $disk = null,
        ?array $providedMetadata = null,
        ?array $additionalData = []
    ): File {
        $disk ??= config('files.upload_disk', config('filesystems.default', 'local'));
        $fileKind = $this->resolveKind($kind);
        Log::info('FileService updateFromStoredPath started', [
            'record_id' => $record->getKey(),
            'old_path' => $record->path,
            'new_path' => $path,
            'disk' => $disk,
            'old_disk' => $record->disk,
            'kind' => $fileKind->value,
            'is_public' => $isPublic,
            'additional_data' => $additionalData,
        ]);
        $metadata = [
            'mime' => $record->mime ?? 'application/octet-stream',
            'size' => $record->size,
            'original_name' => $record->original_name,
            'hash' => $record->hash,
        ];

        if ($path === $record->path && $disk === $record->disk) {
            Storage::disk($disk)->setVisibility($path, $isPublic ? 'public' : 'private');
        }

        if ($path !== $record->path || $disk !== $record->disk) {
            $metadata = $this->buildMetadata($disk, $path, $isPublic, $providedMetadata);

            $this->storage->delete($record->path, $record->disk);

            $record->fill([
                'disk' => $disk,
                'path' => $path,
                'original_name' => $metadata['original_name'],
                'mime' => $metadata['mime'],
                'size' => $metadata['size'],
                'hash' => $metadata['hash'],
            ]);
        }

        $record->kind = $fileKind;

        if (! empty($additionalData)) {
            foreach ($additionalData as $field => $value) {
                $record->$field = $value;
            }
        }

        $record->save();

        Log::info('FileService updateFromStoredPath completed', [
            'record_id' => $record->getKey(),
            'path' => $record->path,
            'disk' => $record->disk,
            'mime' => $record->mime,
            'size' => $record->size,
            'file_category_id' => $record->file_category_id,
            'file_format_id' => $record->file_format_id,
        ]);

        return $record;
    }

    /**
     * Генерирует временную ссылку для скачивания файла.
     *
     * @param  File      $file       Модель файла.
     * @param  int|null  $ttlSeconds Время жизни ссылки в секундах (null = значение по умолчанию).
     * @return string                Временный URL для скачивания.
     * @throws \RuntimeException      Если диск не поддерживает временные ссылки.
     */
    public function temporaryUrl(File $file, ?int $ttlSeconds = null): string
    {
        return $this->storage->temporaryUrl(
            $file->path,
            $ttlSeconds ?? (int) config('files.presigned_ttl', 3600),
            $file->disk
        );
    }

    /**
     * Удаляет файл с диска и соответствующую запись из БД.
     *
     * @param  File $file Модель файла.
     * @return void
     */
    public function deleteFile(File $file): void
    {
        $this->storage->delete($file->path, $file->disk);
        $file->delete();
    }

    /**
     * Преобразует входной параметр kind в объект FileKind.
     *
     * @param  FileKind|string $kind Тип файла (строка или объект FileKind).
     * @return FileKind              Объект FileKind.
     * @throws \InvalidArgumentException Если строка не соответствует ни одному из значений enum.
     */
    protected function resolveKind(FileKind|string $kind): FileKind
    {
        if ($kind instanceof FileKind) {
            return $kind;
        }

        $resolved = FileKind::tryFrom($kind);

        if ($resolved === null) {
            throw ValidationException::withMessages([
                'kind' => 'Недопустимое значение типа файла.',
            ]);
        }

        return $resolved;
    }

    /**
     * Формирует массив метаданных для сохранения в БД.
     *
     * @param  string      $disk              Имя диска.
     * @param  string      $path              Путь к файлу.
     * @param  bool        $isPublic          Флаг публичности.
     * @param  array|null  $providedMetadata  Пользовательские метаданные (original_name, mime, size).
     * @return array                           Массив метаданных (например, size, mime, original_name).
     */
    protected function buildMetadata(
        string $disk,
        string $path,
        bool $isPublic,
        ?array $providedMetadata = null,
    ): array {
        $storage = Storage::disk($disk);

        if (! $storage->exists($path)) {
            throw new RuntimeException("Файл {$path} не найден на диске {$disk}.");
        }

        $fullPath = $storage->path($path);

        $originalName = $providedMetadata['original_name']
            ?? basename($path);

        $mime = $providedMetadata['mime']
            ?? $storage->mimeType($path)
            ?? 'application/octet-stream';

        $size = $providedMetadata['size']
            ?? $storage->size($path);

        // hash считаем по реальному файлу
        $hash = $providedMetadata['hash']
            ?? hash_file('sha256', $fullPath);

        return [
            'original_name' => $originalName,
            'mime' => $mime,
            'size' => $size,
            'hash' => $hash,
        ];
    }
}

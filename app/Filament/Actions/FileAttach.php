<?php

namespace App\Filament\Actions;

use App\Enums\FileKind;
use App\Filament\Traits\HasFileService;
use App\Filament\Traits\HasFileStorage;
use App\Models\File;
use App\Support\FileUrl;
use Closure;
use Filament\Forms\Components\BaseFileUpload;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class FileAttach extends FileUpload
{
    use HasFileService;
    use HasFileStorage;

    protected FileKind|Arrayable|Closure $fileKind = FileKind::Document;

    public function fileKind(FileKind|Arrayable|Closure $kind): static
    {
        $this->fileKind = $kind;

        return $this;
    }

    /**
     * Возвращает тип файла
     *
     * @return FileKind
     */
    public function getFileKind(): FileKind|string|null
    {
        return $this->evaluate($this->fileKind);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $uploadDisk = config('files.upload_disk', config('filesystems.default', 'local'));
        $this->disk(function (Get $get) use ($uploadDisk) {
            return $get('disk') ?? $uploadDisk;
        });
        $this->preserveFilenames();
        $this->label('Файл');
        $this->multiple(false);
        $this->openable();
        $this->downloadable();
        $this->afterStateHydrated(function () {});
        $this->dehydrated(true);
        $this->saveUploadedFileUsing(function (UploadedFile $file, FileUpload $component) {
            try {
                $currentFile = $this->resolveCurrentFile($component);
                if ($currentFile) {
                    return $this->updateUploadedFile($currentFile, $file, $component);
                }
                return $this->saveUploadedFile($file, $component);
            } catch (Throwable $e) {
                throw ValidationException::withMessages([
                    $component->getStatePath() => $e->getMessage(),
                ]);
            }
        });
        $this->getUploadedFileUsing(function (
            BaseFileUpload $component,
            string|array $file,
            string|array|null $storedFileNames
        ): ?array {
            if (is_array($file)) {
                $id = $file['path'] ?? $file['id'] ?? null;

                $fileModel = $id ? File::find($id) : null;

                if (! $fileModel && isset($file['path']) && is_string($file['path'])) {
                    $fileModel = File::query()->where('path', $file['path'])->latest()->first();
                }

                if (! $fileModel) {
                    return null;
                }

                return [
                    'name' => $fileModel->original_name ?: basename((string) $fileModel->path),
                    'size' => (int) ($fileModel->size ?? 0),
                    'type' => $fileModel->mime ?? null,
                    'url' => FileUrl::download($fileModel),
                ];
            }

            if (is_numeric($file) || Str::isUuid($file)) {
                $fileModel = File::find($file);
            } else {
                $record = $component->getRecord();

                if ($record instanceof File && $record->path === $file) {
                    $fileModel = $record;
                } else {
                    $query = File::query()->where('path', $file);

                    if ($record instanceof Model) {
                        $query
                            ->where('model_type', $record::class)
                            ->where('model_id', $record->getKey());
                    }

                    $fileModel = $query->latest()->first();
                }
            }

            if (! $fileModel) {
                return null;
            }

            $name = $fileModel->original_name
                ?: ($component->isMultiple()
                    ? ($storedFileNames[$file] ?? null)
                    : $storedFileNames)
                ?: basename((string) $file);

            return [
                'name' => $name,
                'size' => $fileModel->size ?? 0,
                'type' => $fileModel->mime ?? null,
                'url' => FileUrl::download($fileModel),
            ];
        });
    }


    /**
     * Определяет тип записи
     *
     * @param FileUpload $component
     *
     * @return Model|null
     */
    protected function resolveAttachableRecord(FileUpload $component): ?Model
    {
        $livewire = $component->getLivewire();

        // RelationManager
        if (method_exists($livewire, 'getOwnerRecord')) {
            return $livewire->getOwnerRecord();
        }

        // Edit/create page
        if (method_exists($livewire, 'getRecord')) {
            return $livewire->getRecord();
        }

        return null;
    }

    /**
     * Извлекает существующую модель File из состояния компонента.
     * Поддерживаются: объект File, числовой/строковый ID, массив с ключом 'id'.
     *
     * @param FileUpload $component
     *
     * @return File|null
     */
    protected function resolveCurrentFile(FileUpload $component): ?File
    {
        $state = $component->getState();

        return match (true) {
            $state instanceof File => $state,
            is_numeric($state), is_string($state) => File::find($state),
            is_array($state) && isset($state['id']) => File::find($state['id']),
            default => null,
        };
    }

    /**
     * Обновляет существующий файл: удаляет старый, сохраняет новый, обновляет запись в БД.
     *
     * @param File $currentFile
     * @param UploadedFile $file
     * @param FileUpload $component
     *
     * @return string
     */
    protected function updateUploadedFile(
        File $currentFile,
        UploadedFile $file,
        FileUpload $component
    ): string {
        $disk = $currentFile->disk;
        $directory = $component->evaluate($component->getDirectory()) ?? 'files';
        $newPath = $directory . '/' . $file->getClientOriginalName();

        // Удаляем старый файл
        $this->storage()->delete($currentFile->path, $disk);

        // Сохраняем новый файл
        $this->storage()->put($newPath, $file->getContent());

        // Обновляем запись через сервис
        $kind = $this->getFileKind();
        $record = $this->resolveAttachableRecord($component);

        $updatedFile = $this->fileService()->updateFromStoredPath(
            record: $currentFile,
            path: $newPath,
            kind: $kind,
            isPublic: false,
            disk: $disk,
            providedMetadata: [
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
            additionalData: [] // если нужно что-то ещё
        );

        // Привязка к родительской модели (если есть)
        if ($record) {
            $updatedFile->model_type = $record->getMorphClass();
            $updatedFile->model_id = $record->getKey();
            $updatedFile->save();
        }

        return (string) $updatedFile->getKey();
    }

    /**
     * Сохраняет новый файл: записывает на диск, создаёт запись в БД.
     *
     * @param UploadedFile $file
     * @param FileUpload $component
     *
     * @return string
     */
    protected function saveUploadedFile(
        UploadedFile $file,
        FileUpload $component
    ): string {
        $disk = $component->getDiskName() ?? config('filesystems.default');
        $directory = $component->evaluate($component->getDirectory()) ?? 'files';
        $path = $directory . '/' . $file->getClientOriginalName();

        // Сохраняем файл физически
        $this->storage()->put($path, $file->getContent());

        // Создаём запись в БД через сервис
        $record = $this->resolveAttachableRecord($component);
        $kind = $this->getFileKind();

        $fileModel = $this->fileService()->createFromStoredPath(
            path: $path,
            kind: $kind,
            model: $record,
            createdBy: auth()->id(),
            isPublic: false,
            disk: $disk,
            providedMetadata: [
                'original_name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]
        );

        return (string) $fileModel->getKey();
    }

    /**
     * Если компонент привязан к модели записи, заполняет поля model_type / model_id.
     *
     * @param File $file
     * @param FileUpload $component
     *
     * @return void
     */
    protected function attachToModel(
        File $file,
        FileUpload $component
    ): void {
        $record = $component->getRecord();
        if ($record) {
            $file->model_type = $record->getMorphClass();
            $file->model_id = $record->getKey();
            $file->save();
        }
    }
}

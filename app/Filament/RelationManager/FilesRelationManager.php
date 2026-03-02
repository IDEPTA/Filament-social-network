<?php

namespace App\Filament\RelationManager;

use App\Enums\FileKind;
use App\Filament\Actions\FileAttach;
use App\Models\Content\Post;
use App\Models\File;
use App\Models\User;
use App\Services\FileService;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FilesRelationManager extends RelationManager
{
    protected static string $relationship = 'files';
    protected static ?string $title = 'Файлы';

    protected ?User $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function getDescription(): ?string
    {
        $owner = $this->getOwnerRecord();

        if ($owner instanceof Post) {
            return 'Поля для прикрепления файлов к посту';
        }

        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('original_name')
            ->columns([
                TextColumn::make('original_name')
                    ->label('Название')
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('kind')
                    ->label('Тип')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->toggleable()
                    ->formatStateUsing(fn(?FileKind $state) => $state?->label() ?? $state),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Загрузить файл')
                    ->modalHeading('Загрузка файла')
                    ->schema(fn() => $this->getUploadFormSchema())
                    ->action(
                        function (RelationManager $livewire) {
                            $livewire->resetTable();

                            $livewire->dispatch('$refresh');

                            Notification::make()
                                ->title('Добавлено')
                                ->success()
                                ->send();
                        }
                    ),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Редактировать')
                    ->form(fn() => $this->getUploadFormSchema())
                    ->using(function (Model $record, array $data): Model {
                        /** @var File $record */

                        return $this->handleRecordUpdate($record, $data);
                    })
                    ->modalHeading('Редактировать файл'),
                DeleteAction::make()
                    ->label('Удалить')
                    ->requiresConfirmation()
                    ->modalHeading('Вы уверены, что хотите удалить этот файл?')
                    ->modalSubheading('Эта операция необратима.')
                    ->modalButton('Да, удалить'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public function getUploadFormSchema(): array
    {
        $owner = $this->getOwnerRecord();

        return [
            Hidden::make('file_id')
                ->default(fn() => $owner->getKey()),
            FileAttach::make('path')
                ->label('Файл')
                ->reactive()
                ->fileKind(fn(Get $get) => $get('kind'))
                ->directory("files/{$owner->getKey()}")
                ->required()
                ->live(),
            Select::make('kind')
                ->label('Тип файла')
                ->reactive()
                ->options(
                    collect(FileKind::cases())
                        ->mapWithKeys(fn(FileKind $case) => [
                            $case->value => $case->label(),
                        ])
                        ->toArray()
                )
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var File $record */
        $disk = data_get($data, 'disk', $record->disk);
        $isPublic = (bool) data_get($data, 'is_public', false);

        $data['path'] = $this->resolveUploadedPath($data['path'] ?? $record->path, $disk, $isPublic);

        try {
            $additionalData = [];

            $additionalData = $this->addAdditionalData($data, $additionalData);

            $updated = app(FileService::class)->updateFromStoredPath(
                $record,
                $data['path'] ?? $record->path,
                $data['kind'],
                isPublic: $isPublic,
                disk: $disk,
                additionalData: $additionalData,
            );
        } catch (\Throwable $exception) {
            throw $exception;
        }

        return $updated;
    }

    /**
     * @param mixed $value
     * @param string $disk
     * @param bool $isPublic
     *
     * @return string|null
     */
    protected function resolveUploadedPath(mixed $value, string $disk, bool $isPublic): ?string
    {
        if ($value instanceof TemporaryUploadedFile) {
            return $this->storeUploadedFile($value, $disk, $isPublic);
        }

        if (is_array($value)) {
            if (isset($value['path']) && is_string($value['path'])) {
                return $value['path'];
            }

            if (isset($value['file']) && $value['file'] instanceof TemporaryUploadedFile) {
                return $this->storeUploadedFile($value['file'], $disk, $isPublic);
            }

            return Arr::first(array_filter(array_map(
                fn($item) => $this->resolveUploadedPath($item, $disk, $isPublic),
                $value,
            )));
        }

        return $value;
    }

    /**
     * Дополнительные данные к файлу
     */
    protected function addAdditionalData(array $data, array $additionalData = []): array
    {
        if (isset($data['file_category_id'])) {
            $additionalData['file_category_id'] = (int) $data['file_category_id'];
        }

        if (isset($data['file_format_id'])) {
            $additionalData['file_format_id'] = (int) $data['file_format_id'];
        }

        return $additionalData;
    }
}

<?php

namespace App\Filament\Resources\Files\Tables;

use App\Enums\FileKind;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                TextColumn::make('path')
                    ->label('Путь')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('mime')
                    ->label('Тип')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('size')
                    ->label('Сайз')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Дата загрузки')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

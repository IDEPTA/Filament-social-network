<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Filament\Resources\Posts\PostResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Автор')
                    ->toggleable()
                    ->url(fn($record): ?string => $record->user ? UserResource::getUrl(
                        'view',
                        ['record' => $record->user]
                    ) : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('post.title')
                    ->label('Пост')
                    ->toggleable()
                    ->searchable()
                    ->url(fn($record): ?string => $record->post ? PostResource::getUrl(
                        'view',
                        ['record' => $record->post]
                    ) : null)
                    ->sortable(),
                TextColumn::make('text')
                    ->label('Комментарий')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->date('M j, Y')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Автор')
                    ->relationship('user', 'name')
                    ->multiple()
                    ->searchable(),
                Filter::make('created_at')
                    ->label('Дата создания')
                    ->schema([
                        DatePicker::make('created_from')
                            ->label('От'),
                        DatePicker::make('created_until')
                            ->label('До'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn($q, $date) => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['created_until'],
                                fn($q, $date) => $q->whereDate('created_at', '<=', $date)
                            );
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

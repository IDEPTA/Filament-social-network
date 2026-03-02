<?php

namespace App\Filament\Resources\Posts\RelationManagers;

use Dom\Text;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    protected static ?string $title = 'Комментарии';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('text')
                    ->label('Текст')
                    ->placeholder('Введите комментарий')
                    ->required(),
                Select::make('user_id')
                    ->label('Автор')
                    ->relationship('user', 'name')
                    ->multiple()
                    ->optionsLimit(10)
                    ->preload()
                    ->placeholder('Введите имя автора')

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('text')
            ->columns([
                TextColumn::make('text')
                    ->label('Комментарий')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Автор')
                    ->toggleable()
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
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';
    protected static ?string $title = 'Посты';
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->description('Заголовок и автор поста')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextInput::make('title')
                            ->label('Заголовок')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),
                        Select::make('user_id')
                            ->label('Автор')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->columnSpan(1),
                    ])
                    ->collapsible(),


                Section::make('Содержание')
                    ->description('Текст поста с поддержкой Markdown')
                    ->icon('heroicon-o-pencil')
                    ->schema([
                        MarkdownEditor::make('text')
                            ->label('Текст')
                            ->required()
                            ->toolbarButtons([
                                ['bold', 'italic', 'strike', 'link'],
                                ['heading'],
                                ['blockquote', 'codeBlock', 'bulletList', 'orderedList'],
                                ['table', 'attachFiles'],
                                ['undo', 'redo'],
                            ])
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label('Заголовок'),
                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->label('Автор'),
                TextColumn::make('created_at')
                    ->label('Дата регистрации')
                    ->toggleable()
                    ->date('M j, Y'),
                TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('M j, Y')
            ])
            ->filters([
                Filter::make('created_at')
                    ->label('Дата регистрации')
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
                AssociateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DissociateAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

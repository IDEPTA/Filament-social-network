<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Posts\PostResource;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
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
                Grid::make(3)
                    ->schema([
                        Section::make('Содержание')
                            ->description('Текст поста с поддержкой Markdown')
                            ->icon('heroicon-o-pencil')
                            ->schema([
                                MarkdownEditor::make('text')
                                    ->label('Текст комментария')
                                    ->required()
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'link',
                                        'strike',
                                        'codeBlock',
                                        'bulletList',
                                        'orderedList',
                                    ]),
                            ])->columnSpan(2),
                        Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Section::make('Основная информация')
                                    ->description('Заголовок и автор поста')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Select::make('user_id')
                                            ->label('Автор')
                                            ->searchable()
                                            ->relationship('user', 'name')
                                            ->optionsLimit(10)
                                            ->preload()
                                            ->placeholder('Выберите автора'),
                                        Select::make('post_id')
                                            ->label('Пост')
                                            ->searchable()
                                            ->relationship('post', 'title')
                                            ->optionsLimit(10)
                                            ->preload()
                                            ->placeholder('Выберите пост'),
                                    ])->columnSpanFull()
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('комментарий')
            ->columns([
                TextColumn::make('post.title')
                    ->label('Пост')
                    ->toggleable()
                    ->searchable()
                    ->url(fn($record): ?string => $record->post ? PostResource::getUrl(
                        'view',
                        ['record' => $record->post]
                    ) : null)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->date('M j, Y')
                    ->toggleable()
                    ->sortable(),
                TextColumn::make('text')
                    ->label('Комментарий')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Дата обновления')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->date('M j, Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('post_id')
                    ->label('Пост')
                    ->relationship('post', 'title')
                    ->multiple()
                    ->searchable()
                    ->optionsLimit(10)
                    ->preload(),
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

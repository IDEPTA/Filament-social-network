<?php

namespace App\Filament\Resources\Comments\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommentForm
{
    public static function configure(Schema $schema): Schema
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
}

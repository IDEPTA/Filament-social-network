<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
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
}

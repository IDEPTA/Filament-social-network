<?php

namespace App\Filament\Resources\Comments\Pages;

use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Widgets\ActiveCommentCreaterChart;
use App\Filament\Widgets\NewCommentChart;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComments extends ListRecords
{
    protected static string $resource = CommentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ActiveCommentCreaterChart::class,
            NewCommentChart::class,
        ];
    }
}

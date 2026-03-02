<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Filament\Widgets\CommentByUserChart;
use App\Filament\Widgets\PostByUserChart;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CommentByUserChart::class,
            PostByUserChart::class
        ];
    }
}

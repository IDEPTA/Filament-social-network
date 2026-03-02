<?php

namespace App\Filament\Traits;

use App\Services\FileService;

trait HasFileService
{
    protected ?FileService $service = null;

    protected function fileService(): FileService
    {
        return $this->service ??= app(FileService::class);
    }
}

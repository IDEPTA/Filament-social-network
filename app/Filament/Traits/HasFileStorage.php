<?php

namespace App\Filament\Traits;

use App\Services\FileStorage;

trait HasFileStorage
{
    protected ?FileStorage $storage = null;

    protected function storage(): FileStorage
    {
        return $this->storage ??= app(FileStorage::class);
    }
}

<?php

use App\Http\Controllers\FileDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('files/{file}/download', FileDownloadController::class)
            ->name('files.download');
    });

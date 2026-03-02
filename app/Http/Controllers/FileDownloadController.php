<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileDownloadController extends Controller
{
    /**
     * Отдает steam файла
     * @param File $file
     *
     * @return StreamedResponse
     */
    public function __invoke(File $file): StreamedResponse
    {
        $disk = $file->disk ?? 'public';

        if (! Storage::disk($disk)->exists($file->path)) {
            abort(404);
        }

        $stream = Storage::disk($disk)->readStream($file->path);

        if ($stream === false) {
            abort(500, 'Cannot open file stream');
        }

        $headers = [
            'Content-Type' => Storage::disk($disk)->mimeType($file->path) ?? 'application/octet-stream',
            'Content-Length' => Storage::disk($disk)->size($file->path),
            'Content-Disposition' => 'attachment; filename="' . $file->original_name . '"',
        ];

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            fclose($stream);
        }, 200, $headers);
    }
}

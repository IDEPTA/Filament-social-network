<?php

namespace App\Repositories;

use App\Models\File;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FileRepository
{
    /**
     * Получить количество загруженных файлов по дням за указанный период.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection
     */
    public function getDailyUploadedFilesCountForPeriod(Carbon $start, Carbon $end): Collection
    {
        return File::whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->date = Carbon::parse($item->date);
                return $item;
            });
    }
}

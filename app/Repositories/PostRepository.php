<?php

namespace App\Repositories;

use App\Models\Content\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class PostRepository
{
    /**
     * Получить количество постов по дням за указанный период.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection
     */
    public function getDailyPostCountForPeriod(Carbon $start, Carbon $end)
    {
        return Post::whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->date = Carbon::parse($item->date);
                return $item;
            });
    }

    /**
     * Возвращает топ активных юзеров по постам
     *
     * @param int $limit
     *
     * @return Collection
     */
    public function getTopActiveUsers(int $limit = 5): Collection
    {
        return User::withCount('posts')
            ->orderBy('posts_count', 'desc')
            ->limit($limit)
            ->get();
    }
}

<?php

namespace App\Repositories;

use App\Models\Content\Comment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommentRepository
{
    /**
     * Получить количество комментариев по дням за указанный период.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection
     */
    public function getDailyCommentCountForPeriod(
        Carbon $start,
        Carbon $end
    ): Collection {
        return Comment::whereBetween('created_at', [$start, $end])
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
     * Возвращает топ активных юзеров по комментариев
     *
     * @param int $limit
     *
     * @return Collection
     */
    public function getTopActiveUsers(int $limit = 5): Collection
    {
        return User::withCount('comments')
            ->orderBy('comments_count', 'desc')
            ->limit($limit)
            ->get();
    }
}

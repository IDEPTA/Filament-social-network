<?php

namespace App\Repositories;

use App\Models\Content\Comment;
use App\Models\Content\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    public function getDailyCommentCountForUser(
        int $userId,
        Carbon $start,
        Carbon $end
    ): Collection {
        return Comment::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item->date = Carbon::parse($item->date);
                return $item;
            });
    }

    public function getDailyPostCountForUser(
        int $userId,
        Carbon $start,
        Carbon $end
    ): Collection {
        return Post::where('user_id', $userId)
            ->whereBetween('created_at', [$start, $end])
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
     * Получить количество новых пользователей по дням за указанный период.
     *
     * @param Carbon $start
     * @param Carbon $end
     * @return Collection
     */
    public function getDailyNewUsersCountForPeriod(Carbon $start, Carbon $end): Collection
    {
        return User::whereBetween('created_at', [$start, $end])
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

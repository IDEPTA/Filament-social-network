<?php

namespace App\Filament\Widgets;

use App\Repositories\PostRepository;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;

class NewPostsChart extends ChartWidget
{
    protected ?string $heading = 'График новыйх постов';

    protected function getData(): array
    {
        $postRepository = app(PostRepository::class);

        $days = 31;
        $end = Carbon::now()->endOfDay();
        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        $countsByDate = $postRepository->getDailyPostCountForPeriod($start, $end)
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        $labels = [];
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $start->copy()->addDays($i);
            $dateKey = $currentDate->format('Y-m-d');

            $labels[] = $currentDate->format('d.m');
            $data[] = $countsByDate[$dateKey]->count ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Новые посты',
                    'data' => $data,
                    'borderColor' => '#4f46e5',
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

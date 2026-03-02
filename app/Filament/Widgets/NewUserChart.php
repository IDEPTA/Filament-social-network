<?php

namespace App\Filament\Widgets;

use App\Repositories\UserRepository;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class NewUserChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'График новых пользователей';

    // Занимает всю ширину
    protected int|string|array $columnSpan = 'full';

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('startDate')
                ->label('Начало периода')
                ->default(now()->subDays(30)),
            DatePicker::make('endDate')
                ->label('Конец периода')
                ->default(now()),
        ]);
    }

    protected function getData(): array
    {
        /** @var UserRepository $userRepository */
        $userRepository = app(UserRepository::class);

        $start = isset($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $end = isset($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        $dailyCounts = $userRepository->getDailyNewUsersCountForPeriod($start, $end);

        $labels = [];
        $data = [];

        foreach ($dailyCounts as $item) {
            $labels[] = $item->date->format('d.m');
            $data[] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Новые пользователи',
                    'data' => $data,
                    'borderColor' => '#3b82f6',
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

<?php

namespace App\Filament\Widgets;

use App\Repositories\UserRepository;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class PostByUserChart extends ChartWidget
{
    use HasFiltersSchema;

    public $record;
    protected static bool $isDiscovered = false;
    protected ?string $heading = 'График постов пользователя';

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
        if (!$this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        $userId = $this->record->id ?? $this->record['id'] ?? null;

        /** @var UserRepository $userRepository */
        $userRepository = app(UserRepository::class);

        if (!$userId) {
            return [
                'datasets' => [],
                'labels' => [],
            ];
        }

        $start = isset($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $end = isset($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        $dailyCounts = $userRepository->getDailyPostCountForUser($userId, $start, $end);

        $labels = [];
        $data = [];

        foreach ($dailyCounts as $item) {
            $labels[] = $item->date->format('d.m');
            $data[] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Посты',
                    'data' => $data,
                    'borderColor' => '#0b70f5',
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

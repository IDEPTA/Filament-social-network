<?php

namespace App\Filament\Widgets;

use App\Repositories\FileRepository;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class UploadedFilesChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'График загруженных файлов';

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
        /** @var FileRepository $fileRepository */
        $fileRepository = app(FileRepository::class);

        $start = isset($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->startOfDay()
            : now()->subDays(30)->startOfDay();

        $end = isset($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->endOfDay()
            : now()->endOfDay();

        $dailyCounts = $fileRepository->getDailyUploadedFilesCountForPeriod($start, $end);

        $labels = [];
        $data = [];

        foreach ($dailyCounts as $item) {
            $labels[] = $item->date->format('d.m');
            $data[] = $item->count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Загруженные файлы',
                    'data' => $data,
                    'borderColor' => '#8b5cf6', // фиолетовый
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

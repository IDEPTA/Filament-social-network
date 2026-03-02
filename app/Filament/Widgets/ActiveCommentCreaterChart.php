<?php

namespace App\Filament\Widgets;

use App\Repositories\CommentRepository;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class ActiveCommentCreaterChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Активные щитпостеры комментариями';

    public function filtersSchema(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('limit')
                ->label('Выберите кол-во активных щитпостеров')
                ->required()
                ->numeric()
        ]);
    }

    protected function getData(): array
    {
        /** @var CommentRepository $commentRepository */
        $commentRepository = app(CommentRepository::class);

        $limit = $this->filters['limit'] ?? 5;;
        $topUsers = $commentRepository->getTopActiveUsers($limit);

        $labels = [];
        $data = [];

        foreach ($topUsers as $user) {
            $labels[] = $user->email;
            $data[] = $user->comments_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Количество комментов',
                    'data' => $data,
                    'backgroundColor' => '#e59b46',
                    'borderColor' => '#4b4b4b',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}

<?php

namespace App\Filament\Widgets;

use App\Repositories\PostRepository;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\ChartWidget\Concerns\HasFiltersSchema;

class ActivePostCreaterChart extends ChartWidget
{
    use HasFiltersSchema;

    protected ?string $heading = 'Активные щитпостеры';

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
        /** @var PostRepository $postRepository */
        $postRepository = app(PostRepository::class);

        $limit = $this->filters['limit'] ?? 5;;
        $topUsers = $postRepository->getTopActiveUsers($limit);

        $labels = [];
        $data = [];

        foreach ($topUsers as $user) {
            $labels[] = $user->email;
            $data[] = $user->posts_count;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Количество постов',
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

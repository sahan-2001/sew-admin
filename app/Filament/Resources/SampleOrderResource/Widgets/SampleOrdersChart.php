<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\SampleOrder;

class SampleOrdersChart extends ChartWidget
{
    protected static ?string $heading = 'Sample Orders by Status';
    protected static ?int $sort = 1;
    protected static string $color = 'warning';

    protected function getData(): array
    {
        $statuses = [
            'planned', 'released', 'started', 'cut',
            'completed', 'rejected', 'accepted', 'converted',
        ];

        $data = SampleOrder::selectRaw('status, COUNT(*) as total')
            ->whereIn('status', $statuses)
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $labels = [];
        $values = [];

        foreach ($statuses as $status) {
            $labels[] = ucfirst($status);
            $values[] = $data[$status] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Sample Orders',
                    'data' => $values,
                    'backgroundColor' => '#f59e0b', 
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

<?php

namespace App\Filament\Widgets;

use App\Models\Quote;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class QuotesSummary extends ChartWidget
{
    protected static ?string $heading = 'Quotes Overview';

    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getData(): array
    {
        $dateRange = $this->getDateRange();
        [$start, $end] = $dateRange;

        $quotes = Quote::whereBetween('created_at', [$start, $end])
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        return [
            'datasets' => [
                [
                    'label' => 'Quotes by Status',
                    'data' => $quotes->values()->toArray(),
                    'backgroundColor' => ['#3b82f6', '#10b981', '#ef4444', '#f59e0b'],
                ],
            ],
            'labels' => $quotes->keys()->map(fn($status) => ucfirst($status))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getDateRange(): array
    {
        $start = $this->startDate ? Carbon::parse($this->startDate) : now()->subMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate) : now();

        return [$start, $end];
    }
}

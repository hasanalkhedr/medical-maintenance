<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Trend';
    protected int | string | array $columnSpan = 'full';

    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getData(): array
    {
        $dateRange = $this->getDateRange();
        [$start, $end] = $dateRange;

        // For demo purposes - in real app, you'd query your actual data
        $revenueData = Payment::whereBetween('payment_date', [$start, $end])
            ->select(
                DB::raw('DATE(payment_date) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // If no data, return some demo data
        if ($revenueData->isEmpty()) {
            return $this->getDemoData();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Daily Revenue',
                    'data' => $revenueData->pluck('total')->toArray(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $revenueData->pluck('date')->map(fn($date) => Carbon::parse($date)->format('M d'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getDateRange(): array
    {
        $start = $this->startDate ? Carbon::parse($this->startDate) : now()->subMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate) : now();

        return [$start, $end];
    }

    protected function getDemoData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Daily Revenue',
                    'data' => [1200, 1800, 1500, 2200, 1900, 2500, 2800],
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => ['Jan 01', 'Jan 02', 'Jan 03', 'Jan 04', 'Jan 05', 'Jan 06', 'Jan 07'],
        ];
    }
}

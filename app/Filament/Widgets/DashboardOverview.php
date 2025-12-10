<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Contract;
use App\Models\Quote;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DashboardOverview extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = null;
    protected static bool $isLazy = true;

    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $dateRange = $this->getDateRange();

        return [
            Stat::make('Total Invoices', $this->getTotalInvoices($dateRange))
                ->description($this->getInvoiceGrowth($dateRange))
                ->descriptionIcon($this->getInvoiceGrowthIcon($dateRange))
                ->color('primary')
                ->chart($this->getInvoiceChartData($dateRange)),

            Stat::make('Total Revenue', '$' . number_format($this->getTotalRevenue($dateRange), 2))
                ->description($this->getRevenueGrowth($dateRange))
                ->descriptionIcon($this->getRevenueGrowthIcon($dateRange))
                ->color('success'),

            Stat::make('Active Contracts', $this->getActiveContracts($dateRange))
                ->description($this->getContractGrowth($dateRange))
                ->descriptionIcon($this->getContractGrowthIcon($dateRange))
                ->color('warning'),

            Stat::make('Pending Quotes', $this->getPendingQuotes($dateRange))
                ->description('Conversion Rate: ' . $this->getQuoteConversionRate($dateRange))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];
    }

    protected function getDateRange(): array
    {
        $start = $this->startDate ? Carbon::parse($this->startDate) : now()->subYear();
        $end = $this->endDate ? Carbon::parse($this->endDate) : now();

        return [$start, $end];
    }

    // Statistical methods
    protected function getTotalInvoices(array $dateRange): int
    {
        [$start, $end] = $dateRange;

        return Invoice::whereBetween('created_at', [$start, $end])->count();
    }

    protected function getTotalRevenue(array $dateRange): float
    {
        [$start, $end] = $dateRange;

        return Payment::whereBetween('payment_date', [$start, $end])
            ->sum('amount');
    }

    protected function getActiveContracts(array $dateRange): int
    {
        [$start, $end] = $dateRange;

        return Contract::whereBetween('start_date', [$start, $end])
            //->where('status', 'active')
            ->count();
    }

    protected function getPendingQuotes(array $dateRange): int
    {
        [$start, $end] = $dateRange;

        return Quote::whereBetween('created_at', [$start, $end])
            //->where('status', 'pending')
            ->count();
    }

    protected function getQuoteConversionRate(array $dateRange): string
    {
        [$start, $end] = $dateRange;

        $totalQuotes = Quote::whereBetween('created_at', [$start, $end])->count();
        $convertedQuotes = Quote::whereBetween('created_at', [$start, $end])
            ->where('status', 'converted')
            ->count();

        if ($totalQuotes === 0) return '0%';

        return round(($convertedQuotes / $totalQuotes) * 100, 1) . '%';
    }

    // Growth calculation methods (simplified for example)
    protected function getInvoiceGrowth(array $dateRange): string
    {
        return '12.5% growth';
    }

    protected function getInvoiceGrowthIcon(array $dateRange): string
    {
        return 'heroicon-m-arrow-trending-up';
    }

    protected function getRevenueGrowth(array $dateRange): string
    {
        return '8.2% growth';
    }

    protected function getRevenueGrowthIcon(array $dateRange): string
    {
        return 'heroicon-m-arrow-trending-up';
    }

    protected function getContractGrowth(array $dateRange): string
    {
        return '5.7% growth';
    }

    protected function getContractGrowthIcon(array $dateRange): string
    {
        return 'heroicon-m-arrow-trending-up';
    }

    protected function getInvoiceChartData(array $dateRange): array
    {
        return [10, 15, 12, 18, 20, 25, 30];
    }
}

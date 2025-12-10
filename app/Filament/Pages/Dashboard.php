<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DashboardOverview;
use App\Filament\Widgets\RecentInvoicesTable;
use App\Filament\Widgets\RecentPaymentsTable;
use App\Filament\Widgets\QuotesSummary;
use App\Filament\Widgets\RevenueChart;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;

class Dashboard extends BaseDashboard
{
    use BaseDashboard\Concerns\HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Date Range Filter')
                    ->description('Filter dashboard data by date range')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->default(now()->subMonth()),
                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            DashboardOverview::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            RevenueChart::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            //QuotesSummary::class,
            //RecentInvoicesTable::class,
            //RecentPaymentsTable::class,
        ];
    }

    public function getColumns(): int | string | array
    {
        return [
            'lg' => 2,
            'xl' => 2,
        ];
    }
}

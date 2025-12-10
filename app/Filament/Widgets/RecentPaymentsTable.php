<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentPaymentsTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $dateRange = $this->getDateRange();
        [$start, $end] = $dateRange;

        return Payment::with(['invoice.client'])
            ->whereBetween('payment_date', [$start, $end])
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('reference')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('invoice.invoice_number')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('invoice.client.name')
                ->searchable(),

            Tables\Columns\TextColumn::make('amount')
                ->money('USD')
                ->sortable(),

            Tables\Columns\TextColumn::make('payment_date')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('payment_method')
                ->badge(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'completed' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                    default => 'gray',
                }),
        ];
    }

    protected function getDateRange(): array
    {
        $start = $this->startDate ? Carbon::parse($this->startDate) : now()->subMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate) : now();

        return [$start, $end];
    }
}

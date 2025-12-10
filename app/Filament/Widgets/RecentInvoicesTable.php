<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentInvoicesTable extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    public ?string $startDate = null;
    public ?string $endDate = null;

    protected function getTableQuery(): Builder
    {
        $dateRange = $this->getDateRange();
        [$start, $end] = $dateRange;

        return Invoice::with(['client'])
            ->whereBetween('invoice_date', [$start, $end])
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('invoice_number')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('client.name')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('total_amount')
                ->money('USD')
                ->sortable(),

            Tables\Columns\TextColumn::make('invoice_date')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('due_date')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'paid' => 'success',
                    'sent' => 'warning',
                    'overdue' => 'danger',
                    'draft' => 'secondary',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->url(fn (Invoice $record): string => route('filament.admin.resources.invoices.view', $record))
                ->icon('heroicon-o-eye'),
        ];
    }

    protected function getDateRange(): array
    {
        $start = $this->startDate ? Carbon::parse($this->startDate) : now()->subMonth();
        $end = $this->endDate ? Carbon::parse($this->endDate) : now();

        return [$start, $end];
    }
}

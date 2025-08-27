<?php
namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Payment Information')
                    ->schema([
                        Components\TextEntry::make('invoice.invoice_number'),
                        Components\TextEntry::make('payment_date')
                            ->date(),
                        Components\TextEntry::make('amount')
                            ->money('AED'),
                        Components\TextEntry::make('payment_method')
                            ->badge(),
                        Components\TextEntry::make('reference'),
                    ])->columns(2),

                Components\Section::make('Check Details')
                    ->visible(fn ($record) => $record->payment_method === 'check')
                    ->schema([
                        Components\TextEntry::make('check.check_number'),
                        Components\TextEntry::make('check.bank_name'),
                        Components\TextEntry::make('check.branch_name'),
                        Components\TextEntry::make('check.issue_date')
                            ->date(),
                        Components\TextEntry::make('check.due_date')
                            ->date(),
                        Components\TextEntry::make('check.status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cleared' => 'success',
                                'bounced' => 'danger',
                                'pending' => 'warning',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('check.notes')
                            ->columnSpanFull(),
                    ])->columns(2),

                Components\Section::make('Additional Information')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->markdown(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}

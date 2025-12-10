<?php
namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\BankTransferResource;
use App\Filament\Resources\CheckResource;
use App\Filament\Resources\PaymentResource;
use App\Models\BankTransfer;
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
                    ->visible(fn($record) => $record->payment_method === 'check')
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
                            ->color(fn(string $state): string => match ($state) {
                                'cleared' => 'success',
                                'bounced' => 'danger',
                                'pending' => 'warning',
                                default => 'gray',
                            }),
                        Components\TextEntry::make('check.notes')
                            ->columnSpanFull(),
                    ])->columns(2),

                Components\Section::make('Bank Transfer Details')
                    ->visible(fn($record) => $record->payment_method === 'bank_transfer')
                    ->schema([
                        Components\TextEntry::make('bank_transfer.doc_number'),
                        Components\TextEntry::make('bank_transfer.bank_name'),
                        Components\TextEntry::make('bank_transfer.doc_date')
                            ->date(),
                        Components\TextEntry::make('bank_transfer.notes')
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
            Actions\Action::make('downloadCheckDocument')
                ->label('Download Check Document')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(function () {
                    if (!$this->record->check?->document_path) {
                        return '#';
                    }

                    return \Illuminate\Support\Facades\Storage::disk('public')->url($this->record->check->document_path);
                })
                ->openUrlInNewTab()
                ->hidden(fn(): bool => blank($this->record->check?->document_path)),

            Actions\Action::make('downloadBankDocument')
                ->label('Download Bank Document')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(function () {
                    if (!$this->record->bank_transfer?->document_path) {
                        return '#';
                    }

                    return \Illuminate\Support\Facades\Storage::disk('public')->url($this->record->bank_transfer->document_path);
                })
                ->openUrlInNewTab()
                ->hidden(fn(): bool => blank($this->record->bank_transfer?->document_path)),
        ];
    }

    protected function getFooterWidgets(): array
    {
        if ($this->record->payment_method === 'check') {
            return [
                CheckResource\Widgets\CheckDocumentWidget::class,
            ];
        }
        if ($this->record->payment_method === 'bank_transfer') {
            return [
                BankTransferResource\Widgets\BankTransferDocumentWidget::class,
            ];
        }
        return [];
    }
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load check data if payment method is check
        if ($data['payment_method'] === 'check') {
            $check = \App\Models\Check::where('payment_id', $this->record->id)->first();
            if ($check) {
                $data['check'] = [
                    'check_number' => $check->check_number,
                    'bank_name' => $check->bank_name,
                    'branch_name' => $check->branch_name,
                    'issue_date' => $check->issue_date,
                    'due_date' => $check->due_date,
                    'status' => $check->status,
                    'notes' => $check->notes,
                    'document_path' => $check->document_path,
                ];
            }
        }
        if ($data['payment_method'] === 'bank_transfer') {
            $bank_transfer = BankTransfer::where('payment_id', $this->record->id)->first();
            if ($bank_transfer) {
                $data['bank_transfer'] = [
                    'doc_number' => $bank_transfer->doc_number,
                    'bank_name' => $bank_transfer->bank_name,
                    'doc_date' => $bank_transfer->doc_date,
                    'notes' => $bank_transfer->notes,
                    'document_path' => $bank_transfer->document_path,
                ];
            }
        }

        return $data;
    }
}

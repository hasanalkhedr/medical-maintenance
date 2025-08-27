<?php
namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\InvoicePdfService;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Mail;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Invoice Information')
                    ->schema([
                        Grid::make(3)
                            ->schema(components: [
                                TextEntry::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->weight('bold'),
                                TextEntry::make('issue_date')
                                    ->date()
                                    ->label('Issue Date'),
                                TextEntry::make('due_date')
                                    ->date()
                                    ->label('Due Date'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextEntry::make('company.trade_name')
                                    ->label('Company')
                                    ->weight('medium'),
                                TextEntry::make('medicalInstitution.name')
                                    ->label('Medical Institution')
                                    ->weight('medium'),
                                TextEntry::make('contract.contract_number')
                                    ->label('Contract No.')
                                    ->weight('medium'),
                            ]),

                        TextEntry::make('title')
                            ->label('Invoice For')
                            ->columnSpanFull()
                            ->weight('medium')
                            ->extraAttributes(['class' => 'mt-4']),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'paid' => 'success',
                                'partial' => 'warning',
                                'pending' => 'danger',
                                default => 'gray',
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'mt-4']),
                    ]),

                // Payment Information Section
                Section::make('Payment Information')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_amount')
                                    ->money('AED')
                                    ->label('Total Amount')
                                    ->weight('bold')
                                    ->size('lg')
                                    ->extraAttributes(['class' => 'text-green-600 font-bold']),

                                TextEntry::make('paid_amount')
                                    ->money('AED')
                                    ->label('Paid Amount')
                                    ->weight('bold')
                                    ->color('success')
                                    ->size('lg'),

                                TextEntry::make('remaining_amount')
                                    ->money('AED')
                                    ->label('Remaining Amount')
                                    ->weight('bold')
                                    ->color(fn($record) => $record->remaining_amount > 0 ? 'danger' : 'success')
                                    ->size('lg'),

                                TextEntry::make('paid_percentage')
                                    ->label('Paid Percentage')
                                    ->formatStateUsing(fn($state) => number_format($state, 1) . '%')
                                    ->weight('bold')
                                    ->color(fn($record) => match (true) {
                                        $record->paid_percentage >= 100 => 'success',
                                        $record->paid_percentage > 0 => 'warning',
                                        default => 'danger'
                                    })
                                    ->size('lg'),
                            ]),

                        // Components\Progress::make('paid_percentage')
                        //     ->label('Payment Progress')
                        //     ->color(fn($record) => match (true) {
                        //         $record->paid_percentage >= 100 => 'success',
                        //         $record->paid_percentage > 0 => 'warning',
                        //         default => 'danger'
                        //     })
                        //     ->extraAttributes(['class' => 'mt-4']),
                    ])
                    ->hidden(fn($record) => !$record->total_amount),

                Components\Section::make('Invoice Items')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->schema([
                                Grid::make(6)
                                    ->schema([
                                        TextEntry::make('description')
                                            ->columnSpan(2)
                                            ->weight('medium'),
                                        TextEntry::make('quantity')
                                            ->weight('medium'),
                                        TextEntry::make('unit_price')
                                            ->money('AED')
                                            ->weight('medium'),
                                        TextEntry::make('amount')
                                            ->money('AED')
                                            ->weight('bold')
                                            ->label('Total'),
                                    ]),
                            ])
                            ->grid(1)
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Summary')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    TextEntry::make('subtotal')
                                        ->money('AED')
                                        ->label('Subtotal')
                                        ->weight('medium')
                                        ->extraAttributes(['class' => 'text-gray-700']),

                                    TextEntry::make('discount_amount')
                                        ->money('AED')
                                        ->label('Discount Amount')
                                        ->weight('medium')
                                        ->extraAttributes(['class' => 'text-red-600'])
                                        ->visible(fn($record) => $record->discount_amount > 0),

                                    TextEntry::make('tax_amount')
                                        ->money('AED')
                                        ->label('Tax Amount')
                                        ->weight('medium')
                                        ->extraAttributes(['class' => 'text-blue-600'])
                                        ->visible(fn($record) => $record->tax_amount > 0),
                                ]),

                                Group::make([
                                    TextEntry::make('total_amount')
                                        ->money('AED')
                                        ->label('Total Amount')
                                        ->weight('bold')
                                        ->size('lg')
                                        ->extraAttributes(['class' => 'text-green-600 font-bold']),
                                ]),
                            ]),
                    ]),

                // Payments History Section
                Section::make('Payment History')
                    ->schema([
                        RepeatableEntry::make('payments')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        TextEntry::make('payment_date')
                                            ->date()
                                            ->label('Date'),
                                        TextEntry::make('amount')
                                            ->money('AED')
                                            ->label('Amount'),
                                        TextEntry::make('payment_method')
                                            ->label('Method')
                                            ->formatStateUsing(fn($state) => \App\Models\Payment::getPaymentMethods()[$state] ?? $state)
                                            ->badge()
                                            ->color(fn(string $state): string => match ($state) {
                                                'cash' => 'gray',
                                                'bank_transfer' => 'info',
                                                'check' => 'warning',
                                                'credit_card' => 'success',
                                                'debit_card' => 'primary',
                                                'online' => 'purple',
                                                default => 'gray',
                                            }),
                                        TextEntry::make('reference')
                                            ->label('Reference'),
                                        TextEntry::make('created_at')
                                            ->dateTime()
                                            ->label('Recorded At'),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->hidden(fn($record) => $record->payments->isEmpty()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Add Payment Action
            Actions\Action::make('add_payment')
                ->label('Add Payment')
                ->icon('heroicon-o-currency-dollar')
                ->color('success')
                ->form([
                    TextInput::make('amount')
                        ->numeric()
                        ->required()
                        ->minValue(0.01)
                        ->maxValue(fn(Invoice $record) => $record->remaining_amount)
                        ->default(fn(Invoice $record) => $record->remaining_amount)
                        ->suffix('AED')
                        ->label('Payment Amount'),
                    DatePicker::make('payment_date')
                        ->required()
                        ->default(now())
                        ->label('Payment Date'),
                    Select::make('payment_method')
                        ->options(Payment::getPaymentMethods())
                        ->required()
                        ->default('bank_transfer')
                        ->label('Payment Method'),
                    TextInput::make('reference')
                        ->maxLength(255)
                        ->placeholder('Transaction reference number')
                        ->label('Reference Number'),
                    Textarea::make('notes')
                        ->label('Payment Notes')
                        ->placeholder('Add any notes about this payment...'),
                ])
                ->action(function (array $data): void {
                    try {
                        $payment = Payment::create(array_merge($data, ['invoice_id'=>$this->record->id]));
                        $this->record->addPayment($payment);

                        Notification::make()
                            ->title('Payment Added Successfully')
                            ->body('Payment of AED ' . number_format($data['amount'], 2) . ' has been added to invoice ' . $this->record->invoice_number)
                            ->success()
                            ->send();

                        // Refresh the page to show updated payment information
                        $this->refreshFormData(['paid_amount', 'remaining_amount', 'status']);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Add Payment')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                })
                ->hidden(fn() => $this->record->remaining_amount <= 0)
                ->modalHeading('Add Payment to Invoice')
                ->modalSubmitActionLabel('Add Payment'),

            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('pdf')
                ->label('PDF')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (InvoicePdfService $pdfService) {
                    $pdf = $pdfService->generatePdf($this->record);
                    return response()->download($pdf['path'], $pdf['filename']);
                }),
            Actions\Action::make('email')
                ->label('Email')
                ->color('success')
                ->icon('heroicon-o-envelope')
                ->form([
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->default(fn() => $this->record->medicalInstitution->email),
                    Textarea::make('message')
                        ->label('Additional Message')
                        ->placeholder('Add a custom message to the email...'),
                ])
                ->action(function (array $data, InvoicePdfService $pdfService) {
                    try {
                        $pdf = $pdfService->generatePdf($this->record);

                        Mail::to($data['email'])
                            ->send(new InvoiceMail(
                                invoice: $this->record,
                                pdfPath: $pdf['path'],
                                message: $data['message'] ?? null
                            ));

                        Notification::make()
                            ->title('Invoice sent successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to send invoice')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                })
                ->modalSubmitActionLabel('Send Invoice')
        ];
    }
}

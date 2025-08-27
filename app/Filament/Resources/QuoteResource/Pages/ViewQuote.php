<?php
namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\QuoteResource;
use App\Mail\QuoteMail;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\QuotePdfService;
use Filament\Actions;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\View;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Mail;

use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\RepeatableEntry;

class ViewQuote extends ViewRecord
{
    protected static string $resource = QuoteResource::class;
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Quote Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('quote_number')
                                    ->label('Quote Number')
                                    ->weight('bold'),
                                TextEntry::make('issue_date')
                                    ->date()
                                    ->label('Issue Date'),
                                TextEntry::make('expiry_date')
                                    ->date()
                                    ->label('Expiry Date'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextEntry::make('company.trade_name')
                                    ->label('Company')
                                    ->weight('medium'),
                                TextEntry::make('medicalInstitution.name')
                                    ->label('Medical Institution')
                                    ->weight('medium'),
                            ]),

                        TextEntry::make('title')
                            ->label('Quote For')
                            ->columnSpanFull()
                            ->weight('medium')
                            ->extraAttributes(['class' => 'mt-4']),

                        TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'approved' => 'success',
                                'converted' => 'success',
                                'sent' => 'info',
                                'rejected' => 'danger',
                                'draft' => 'gray',
                                default => 'gray',
                            })
                            ->columnSpanFull()
                            ->extraAttributes(['class' => 'mt-4']),
                    ]),

                Section::make('Quote Items')
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

                Section::make('Summary')
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

                Section::make('Terms & Conditions')
                    ->schema([
                        TextEntry::make('technical_terms')
                            ->label('Technical Terms')
                            ->markdown()
                            ->columnSpanFull()
                            ->visible(fn($record) => !empty($record->technical_terms)),

                        TextEntry::make('payment_terms')
                            ->label('Payment Terms')
                            ->markdown()
                            ->columnSpanFull()
                            ->visible(fn($record) => !empty($record->payment_terms)),
                    ])
                    ->collapsible()
                    ->collapsed(fn($record) => empty($record->technical_terms) && empty($record->payment_terms)),
            ]);
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('convert_to_invoice')
                ->label('Convert to Invoice')
                ->icon('heroicon-o-document-plus')
                ->visible(fn($record) => $record->status === 'approved')
                ->action(function (Quote $record) {
                    $invoice = Invoice::create([
                        'invoice_number' => 'INV-' . now()->format('Ymd-His'),
                        'company_id' => $record->company_id,
                        'medical_institution_id' => $record->medical_institution_id,
                        'title' => $record->title,
                        'issue_date' => now(),
                        'due_date' => now()->addDays(30),
                        'subtotal' => $record->subtotal,
                        'tax_rate' => $record->tax_rate,
                        'tax_amount' => $record->tax_amount,
                        'discount_rate' => $record->discount_rate,
                        'discount_amount' => $record->discount_amount,
                        'total_amount' => $record->total_amount,
                        'status' => 'pending',
                    ]);
                    foreach ($record->items as $item) {
                        $invoice->items()->create([
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'amount' => $item->amount,
                        ]);
                    }
                    $record->update(['status' => 'converted']);
                    // Redirect to the created invoice
                    return redirect()->route('filament.admin.resources.invoices.edit', $invoice);
                })
                ->requiresConfirmation()
                ->modalHeading('Convert Quote to Invoice')
                ->modalDescription('Are you sure you want to convert this quote to an invoice? This action cannot be undone.')
                ->modalSubmitActionLabel('Convert')
                ->successNotificationTitle('Quote converted to invoice successfully'),
            Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('danger')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->visible(fn(Quote $record): bool => $record->status === 'sent')
                    ->action(function (Quote $record) {
                        $record->update(['status' => 'approved']);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Approve Quote')
                    ->modalDescription('Are you sure you want to approve this quote?')
                    ->modalSubmitActionLabel('Approve')
                    ->successNotificationTitle('Quote approved successfully'),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('pdf')
                ->label('PDF')
                ->color('success')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Quote $quote, QuotePdfService $pdfService) {
                    $pdf = $pdfService->generatePdf($quote);
                    return response()->download($pdf['path'], $pdf['filename']);
                }),
            Actions\Action::make('email')
                ->label('Email')
                ->color('success')
                ->icon('heroicon-o-envelope')
                ->hidden(fn(Quote $record): bool => $record->status === 'sent')
                ->form([
                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->default(fn(Quote $quote) => $quote->medicalInstitution->email),
                    Textarea::make('message')
                        ->label('Additional Message')
                        ->placeholder('Add a custom message to the email...'),
                ])
                ->action(function (array $data, Quote $quote, QuotePdfService $pdfService) {
                    try {
                        $pdf = $pdfService->generatePdf($quote);

                        Mail::to($data['email'])
                            ->send(new QuoteMail(
                                quote: $quote,
                                pdfPath: $pdf['path'],
                                message: $data['message'] ?? null
                            ));
                        $quote->update(['status' => 'sent']);
                        Notification::make()
                            ->title('Quote sent successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to send quote')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                })
                ->modalSubmitActionLabel('Send Quote'),
        ];
    }
}

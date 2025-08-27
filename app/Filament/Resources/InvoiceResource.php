<?php
namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\MedicalInstitution;
use App\Models\Payment;
use App\Services\InvoicePdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Route;
use Mail;
use Number;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Invoice';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invoice Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Invoice For:')
                            ->columnSpan(2),
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'trade_name')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('medical_institution_id')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                return $companyId ? MedicalInstitution::where('company_id', $companyId)
                                    ->pluck('name', 'id') : [];
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('contract_id')
                            ->relationship('contract', 'contract_number')
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('invoice_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default('INV-' . now()->format('Ymd-His')),
                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('due_date')
                            ->required()
                            ->default(now()->addDays(14)),
                        Forms\Components\Select::make('status')
                            ->options([
                                'paid' => 'Paid',
                                'partial' => 'Partial Paid',
                                'pending' => 'Pending',
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2)->collapsible(),

                Forms\Components\Section::make('Invoice Items')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->live()
                            ->afterStateUpdated(function ($set, $get) {
                                self::updateCalculations($set, $get);
                            })
                            ->schema([
                                Forms\Components\TextInput::make('description')
                                    ->required()
                                    ->columnSpan(2),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->minValue(1)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, $get) {
                                        $unitPrice = (float) $get('unit_price');
                                        $quantity = (int) $get('quantity');
                                        if ($unitPrice && $quantity) {
                                            $total = $unitPrice * $quantity;
                                            $set('total_price', Number::format($total, 2));
                                            $set('amount', Number::format($total, 2));
                                        }
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->prefix('AED')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, $get) {
                                        $unitPrice = (float) $get('unit_price');
                                        $quantity = (int) $get('quantity');
                                        if ($unitPrice && $quantity) {
                                            $total = $unitPrice * $quantity;
                                            $set('total_price', Number::format($total, 2));
                                            $set('amount', Number::format($total, 2));
                                        }
                                    }),
                                Forms\Components\TextInput::make('amount')
                                    ->readOnly()
                                    ->default(0)
                                    ->suffix('AED')
                            ])
                            ->columns(5)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Summary')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Placeholder::make('subtotal_placeholder')
                            ->content(function ($get) {
                                return 'AED ' . number_format($get('subtotal') ?? 0, 2);
                            })
                            ->label('Subtotal')
                            ->extraAttributes([
                                'class' => 'bg-gray-50 border border-gray-300 rounded-md p-3 font-medium text-gray-700'
                            ])
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('discount_rate')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->suffix('%')
                            ->live(true)
                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                self::updateCalculations($set, $get);
                            }),

                        Forms\Components\Placeholder::make('discount_placeholder')
                            ->content(function ($get) {
                                $discountAmount = (float) $get('discount_amount') ?? 0;
                                return 'AED ' . number_format($discountAmount, 2);
                            })
                            ->label('Discount Amount')
                            ->extraAttributes([
                                'class' => 'bg-red-50 border border-red-200 rounded-md p-3 font-medium text-red-700'
                            ]),

                        Forms\Components\TextInput::make('tax_rate')
                            ->numeric()
                            ->required()
                            ->default(0)
                            ->suffix('%')
                            ->live(true)
                            ->afterStateUpdated(function ($state, Set $set, $get) {
                                self::updateCalculations($set, $get);
                            }),

                        Forms\Components\Placeholder::make('tax_placeholder')
                            ->content(function ($get) {
                                return 'AED ' . number_format((float) $get('tax_amount') ?? 0, 2);
                            })
                            ->label('Tax Amount')
                            ->extraAttributes([
                                'class' => 'bg-blue-50 border border-blue-200 rounded-md p-3 font-medium text-blue-700'
                            ]),

                        Forms\Components\Placeholder::make('total_placeholder')
                            ->content(function ($get) {
                                return 'AED ' . number_format($get('total_amount') ?? 0, 2);
                            })
                            ->label('Total Amount')
                            ->extraAttributes([
                                'class' => 'bg-gray-50 border-2 border-green-300 rounded-md p-4 font-bold text-xl text-green-800'
                            ])
                            ->columnSpan(2),
                    ])
                    ->columns(2),
                // Hidden fields for storing calculated values
                Forms\Components\Hidden::make('subtotal'),
                Forms\Components\Hidden::make('tax_amount'),
                Forms\Components\Hidden::make('discount_amount'),
                Forms\Components\Hidden::make('total_amount'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.trade_name')
                    ->sortable()->toggleable(true),
                Tables\Columns\TextColumn::make('medicalInstitution.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contract.contract_number')
                    ->sortable()->toggleable(true),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid_amount')
                    ->money('AED')
                    ->sortable()
                    ->toggleable(true)
                    ->color('success'),
                Tables\Columns\TextColumn::make('remaining_amount')
                    ->money('AED')
                    ->sortable()
                    ->color(fn($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('paid_percentage')
                    ->label('Paid %')
                    ->numeric(1)
                    ->suffix('%')
                    ->color(fn($record) => match (true) {
                        $record->paid_percentage >= 100 => 'success',
                        $record->paid_percentage > 0 => 'warning',
                        default => 'danger'
                    })
                    ->sortable()
                    ->toggleable(true),
                // Tables\Columns\TextColumn::make('paid_percentage')
                //     ->label('Progress')
                //     ->color(fn($record) => match (true) {
                //         $record->paid_percentage >= 100 => 'success',
                //         $record->paid_percentage > 0 => 'warning',
                //         default => 'danger'
                //     })
                //     ->maxValue(100),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial' => 'warning',
                        'pending' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'trade_name'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'paid' => 'Paid',
                        'partial' => 'Partial Paid',
                        'pending' => 'Pending',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Invoices')
                    ->query(fn(Builder $query): Builder => $query
                        ->where('status', '!=', 'paid')
                        ->where('due_date', '<', now())),
                Tables\Filters\Filter::make('unpaid')
                    ->label('Unpaid Invoices')
                    ->query(fn(Builder $query): Builder => $query
                        ->where('status', '!=', 'paid')
                        ->where('remaining_amount', '>', 0)),
            ])
            ->actions([
                // Add Payment Action
                Tables\Actions\Action::make('add_payment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(fn(Invoice $record) => $record->remaining_amount)
                            ->default(fn(Invoice $record) => $record->remaining_amount)
                            ->suffix('AED'),
                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('payment_method')
                            ->options(\App\Models\Payment::getPaymentMethods())
                            ->required()
                            ->default('bank_transfer'),
                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255)
                            ->placeholder('Transaction reference number'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Payment Notes')
                            ->placeholder('Add any notes about this payment...'),
                    ])
                    ->action(function (Invoice $record, array $data): void {
                        try {
                            $payment = Payment::create(array_merge($data, ['invoice_id'=>$record->id]));
                            $record->addPayment($payment);

                            Notification::make()
                                ->title('Payment Added Successfully')
                                ->body('Payment of AED ' . number_format($data['amount'], 2) . ' has been added to invoice ' . $record->invoice_number)
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Add Payment')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw $e;
                        }
                    })
                    ->hidden(fn(Invoice $record) => $record->remaining_amount <= 0),

                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
                Tables\Actions\RestoreAction::make()->label(''),
                Tables\Actions\ForceDeleteAction::make()->label(''),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Invoice $invoice, InvoicePdfService $pdfService) {
                        $pdf = $pdfService->generatePdf($invoice);
                        return response()->download($pdf['path'], $pdf['filename']);
                    }),
                Tables\Actions\Action::make('email')
                    ->label('Email')
                    ->color('success')
                    ->icon('heroicon-o-envelope')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->default(fn(Invoice $invoice) => $invoice->medicalInstitution->email),
                        Forms\Components\Textarea::make('message')
                            ->label('Additional Message')
                            ->placeholder('Add a custom message to the email...'),
                    ])
                    ->action(function (array $data, Invoice $invoice, InvoicePdfService $pdfService) {
                        try {
                            $pdf = $pdfService->generatePdf($invoice);

                            Mail::to($data['email'])
                                ->send(new InvoiceMail(
                                    invoice: $invoice,
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
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function updateCalculations(Set $set, $get): void
    {
        // Calculate subtotal from items
        $items = collect($get('items'))->filter(fn($item) => !empty($item['unit_price']));

        $subtotal = $items->sum(function ($item) {
            $quantity = (int) ($item['quantity'] ?? 1);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            return $quantity * $unitPrice;
        });

        $set('subtotal', $subtotal);

        // Calculate discount amount
        $discountRate = (float) $get('discount_rate') ?? 0;
        $discountAmount = $discountRate * $subtotal / 100;
        $set('discount_amount', $discountAmount);

        // Calculate amount after discount
        $amountAfterDiscount = $subtotal - $discountAmount;

        // Calculate tax amount
        $taxRate = (float) $get('tax_rate') ?? 0;
        $taxAmount = $taxRate * $amountAfterDiscount / 100;
        $set('tax_amount', $taxAmount);

        // Calculate final total
        $totalAmount = $amountAfterDiscount + $taxAmount;
        $set('total_amount', $totalAmount);
    }
}

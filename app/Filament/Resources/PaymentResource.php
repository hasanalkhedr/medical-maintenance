<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Financial Management';

    protected static ?string $modelLabel = 'Payment';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Information')
                    ->schema([
                        Forms\Components\Select::make('invoice_id')
                            ->relationship('invoice', 'invoice_number')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $invoice = \App\Models\Invoice::find($state);
                                    if ($invoice) {
                                        $set('amount', $invoice->remaining_amount);
                                    }
                                }
                            }),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->minValue(0.01)
                            ->maxValue(function (callable $get) {
                                $invoiceId = $get('invoice_id');
                                if ($invoiceId) {
                                    $invoice = \App\Models\Invoice::find($invoiceId);
                                    return $invoice ? $invoice->remaining_amount : 999999;
                                }
                                return 999999;
                            })
                            ->suffix('AED'),

                        Forms\Components\DatePicker::make('payment_date')
                            ->required()
                            ->default(now()),

                        Forms\Components\Select::make('payment_method')
                            ->options(Payment::getPaymentMethods())
                            ->required()
                            ->live()
                            ->default('bank_transfer'),

                        Forms\Components\TextInput::make('reference')
                            ->maxLength(255)
                            ->placeholder('Transaction reference number'),

                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull(),
                    ])->columns(2),

                // Check details section (only show if payment method is check)
                Forms\Components\Section::make('Check Details')
                    ->schema([
                        Forms\Components\TextInput::make('check.check_number')
                            ->label('Check Number')
                            ->required(fn(callable $get) => $get('payment_method') === 'check')
                            ->maxLength(255)
                            ->rule('required_if:payment_method,check'),

                        Forms\Components\TextInput::make('check.bank_name')
                            ->label('Bank Name')
                            ->required(fn(callable $get) => $get('payment_method') === 'check')
                            ->maxLength(255)
                            ->rule('required_if:payment_method,check'),

                        Forms\Components\TextInput::make('check.branch_name')
                            ->label('Branch Name')
                            ->maxLength(255)
                            ->nullable(),

                        Forms\Components\DatePicker::make('check.issue_date')
                            ->label('Issue Date')
                            ->required(fn(callable $get) => $get('payment_method') === 'check')
                            ->default(now())
                            ->rule('required_if:payment_method,check')
                            ->rule('date'),

                        Forms\Components\DatePicker::make('check.due_date')
                            ->label('Due Date')
                            ->required(fn(callable $get) => $get('payment_method') === 'check')
                            ->minDate(fn(callable $get) => $get('check.issue_date') ?: now())
                            ->rule('required_if:payment_method,check')
                            ->rule('date')
                            ->rule('after_or_equal:check.issue_date'),

                        Forms\Components\Select::make('check.status')
                            ->label('Check Status')
                            ->options([
                                'pending' => 'Pending',
                                'cleared' => 'Cleared',
                                'bounced' => 'Bounced',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('pending')
                            ->required(fn(callable $get) => $get('payment_method') === 'check'),

                        // Check Document Upload
                        Forms\Components\FileUpload::make('check.document_path')
                            ->label('Check Document')
                            ->helperText('Upload check image or PDF (Max: 5MB)')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'application/pdf',
                            ])
                            ->maxSize(5120)
                            ->directory('checks/documents')
                            ->disk('public')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->preserveFilenames()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('check.notes')
                            ->label('Check Notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->hidden(fn(callable $get) => $get('payment_method') !== 'check'),

                // Bank Transfer details section (only show if payment method is Bank Transfer)
                Forms\Components\Section::make('Bank Transfer Details')
                    ->schema([
                        Forms\Components\TextInput::make('bank_transfer.doc_number')
                            ->label('Number')
                            ->required(fn(callable $get) => $get('payment_method') === 'bank_transfer')
                            ->maxLength(255)
                            ->rule('required_if:payment_method,bank_transfer'),

                        Forms\Components\TextInput::make('bank_transfer.bank_name')
                            ->label('Bank Name')
                            ->required(fn(callable $get) => $get('payment_method') === 'bank_transfer')
                            ->maxLength(255)
                            ->rule('required_if:payment_method,bank_transfer'),

                        Forms\Components\DatePicker::make('bank_transfer.doc_date')
                            ->label('Date')
                            ->required(fn(callable $get) => $get('payment_method') === 'bank_transfer')
                            ->default(now())
                            ->rule('required_if:payment_method,bank_transfer')
                            ->rule('date'),

                        // bank_transfer Document Upload
                        Forms\Components\FileUpload::make('bank_transfer.document_path')
                            ->label('Document')
                            ->helperText('Upload Bank Transfer image or PDF (Max: 5MB)')
                            ->acceptedFileTypes([
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'image/webp',
                                'application/pdf',
                            ])
                            ->maxSize(5120)
                            ->directory('bank_transfers/documents')
                            ->disk('public')
                            ->visibility('public')
                            ->downloadable()
                            ->openable()
                            ->previewable()
                            ->preserveFilenames()
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('bank_transfer.notes')
                            ->label('Bank Transfer Notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ])
                    ->columns(2)
                    ->hidden(fn(callable $get) => $get('payment_method') !== 'bank_transfer'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->sortable()
                    ->searchable()
                    ->url(fn(Payment $record) => \App\Filament\Resources\InvoiceResource::getUrl('view', ['record' => $record->invoice_id])),

                Tables\Columns\TextColumn::make('invoice.company.trade_name')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('invoice.medical_institution.name')
                    ->label('Medical Institution')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('amount')
                    ->money('AED')
                    ->sortable()
                    ->color(fn(Payment $record) => $record->amount > 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn($state) => Payment::getPaymentMethods()[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'gray',
                        'bank_transfer' => 'info',
                        'check' => 'warning',
                        'credit_card' => 'success',
                        'debit_card' => 'primary',
                        'online' => 'purple',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('check.status')
                    ->label('Check Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'cleared' => 'success',
                        'bounced' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($state) => $state ? ucfirst($state) : 'N/A')
                    ->tooltip(fn($record) => $record?->check ? 'Check Status' : null),

                // // Check Document Column
                // Tables\Columns\IconColumn::make('check.document_path')
                //     ->label('Check Document')
                //     ->icon(fn ($record): string => $record?->check?->document_path
                //         ? 'heroicon-o-document'
                //         : 'heroicon-o-minus')
                //     ->color(fn ($record): string => $record?->check?->document_path
                //         ? 'success'
                //         : 'gray')
                //     ->tooltip(fn ($record): string => $record?->check?->document_path
                //         ? 'Document attached'
                //         : 'No document')
                //     ->toggleable(isToggledHiddenByDefault: fn ($record):bool => $record?->payment_method === 'check'),

                // // bank_transfer Document Column
                // Tables\Columns\IconColumn::make('bank_transfer.document_path')
                //     ->label('Bank Document')
                //     ->icon(fn ($record): string => $record?->bank_transfer?->document_path
                //         ? 'heroicon-o-document'
                //         : 'heroicon-o-minus')
                //     ->color(fn ($record): string => $record?->bank_transfer?->document_path
                //         ? 'success'
                //         : 'gray')
                //     ->tooltip(fn ($record): string => $record?->bank_transfer?->document_path
                //         ? 'Document attached'
                //         : 'No document')
                //     ->toggleable(isToggledHiddenByDefault: fn ($record):bool => $record?->payment_method === 'bank_transfer'),

                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('invoice_id')
                    ->label('Invoice')
                    ->relationship('invoice', 'invoice_number')
                    ->searchable()
                    ->preload()
                    ->indicator('Invoice'),

                Tables\Filters\SelectFilter::make('company')
                    ->label('Company')
                    ->relationship('invoice.company', 'trade_name')
                    ->searchable()
                    ->preload()
                    ->indicator('Company'),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->options(Payment::getPaymentMethods())
                    ->indicator('Payment Method'),

                // Filter for checks with documents
                Tables\Filters\Filter::make('has_check_document')
                    ->label('Has Check Document')
                    ->query(fn (Builder $query): Builder => $query->whereHas('check', function ($q) {
                        $q->whereNotNull('document_path');
                    }))
                    ->indicator('Has Check Document'),

                // Filter for bank_transfer with documents
                Tables\Filters\Filter::make('has_bank_document')
                    ->label('Has Bank Document')
                    ->query(fn (Builder $query): Builder => $query->whereHas('bank_transfer', function ($q) {
                        $q->whereNotNull('document_path');
                    }))
                    ->indicator('Has Bank Document'),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Payment date from ' . Carbon::parse($data['from'])->toFormattedDateString();
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Payment date until ' . Carbon::parse($data['until'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),

                Tables\Filters\Filter::make('amount')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Minimum Amount')
                            ->numeric()
                            ->placeholder('Min amount'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Maximum Amount')
                            ->numeric()
                            ->placeholder('Max amount'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['min_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['max_amount'],
                                fn(Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['min_amount'] ?? null) {
                            $indicators['min_amount'] = 'Min amount: AED ' . number_format($data['min_amount'], 2);
                        }
                        if ($data['max_amount'] ?? null) {
                            $indicators['max_amount'] = 'Max amount: AED ' . number_format($data['max_amount'], 2);
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Download Check Document Action
                Tables\Actions\Action::make('downloadCheckDocument')
                    ->label('Download Check Document')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Payment $record) {
                        if (!$record->check?->document_path) {
                            return;
                        }

                        $disk = 'public';

                        if (!Storage::disk($disk)->exists($record->check->document_path)) {
                            throw new \Exception("File not found: " . $record->check->document_path);
                        }

                        return Storage::disk($disk)->download($record->check->document_path);
                    })
                    ->hidden(fn (Payment $record): bool => blank($record->check?->document_path)),

                // Download Check Document Action
                Tables\Actions\Action::make('downloadBankDocument')
                    ->label('Download Bank Document')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Payment $record) {
                        if (!$record->bank_transfer?->document_path) {
                            return;
                        }

                        $disk = 'public';

                        if (!Storage::disk($disk)->exists($record->bank_transfer->document_path)) {
                            throw new \Exception("File not found: " . $record->bank_transfer->document_path);
                        }

                        return Storage::disk($disk)->download($record->bank_transfer->document_path);
                    })
                    ->hidden(fn (Payment $record): bool => blank($record->bank_transfer?->document_path)),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('payment_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}

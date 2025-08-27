<?php
namespace App\Filament\Resources;

use App\Filament\Resources\QuoteResource\Pages;
use App\Filament\Resources\QuoteResource\RelationManagers;
use App\Mail\QuoteMail;
use App\Models\Invoice;
use App\Models\Quote;
use App\Services\QuotePdfService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;
use Number;

class QuoteResource extends Resource
{
    protected static ?string $model = Quote::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = 'Quote';
    protected static ?string $navigationGroup = 'Sales Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quote Information')
                    ->collapsible()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Quote For:')
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
                                if (!$companyId) {
                                    return [];
                                }
                                return \App\Models\MedicalInstitution::where('company_id', $companyId)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('quote_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->default('Q-' . now()->format('Ymd-His')),
                        Forms\Components\DatePicker::make('issue_date')
                            ->required()
                            ->default(now()),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->required()
                            ->default(now()->addMonth()),
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'sent' => 'Sent',
                                'approved' => 'Approved',
                                'converted' => 'Converted',
                                'rejected' => 'Rejected',
                            ])
                            ->default('draft')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Quote Items')
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

                Forms\Components\Section::make('Additional Information')
                    ->collapsible()
                    ->schema([
                        Forms\Components\Textarea::make('technical_terms')
                            ->columnSpanFull()
                            ->rows(3),
                        Forms\Components\Textarea::make('payment_terms')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),

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
                Tables\Columns\TextColumn::make('quote_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.trade_name')
                    ->sortable()
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('medicalInstitution.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'converted' => 'success',
                        'sent' => 'info',
                        'rejected' => 'danger',
                        'draft' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Sent',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ]),
                Tables\Filters\Filter::make('expired')
                    ->label('Expired Quotes')
                    ->query(fn(Builder $query): Builder => $query
                        ->where('expiry_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\Action::make('convert_to_invoice')
                    ->label('Invoice')
                    ->color('danger')
                    ->icon('heroicon-o-document-plus')
                    ->visible(fn(Quote $record): bool => $record->status === 'approved')
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
                Tables\Actions\Action::make('approve')
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
                Tables\Actions\ViewAction::make()->label(''),
                Tables\Actions\EditAction::make()->label(''),
                Tables\Actions\DeleteAction::make()->label(''),
                Tables\Actions\RestoreAction::make()->label(''),
                Tables\Actions\ForceDeleteAction::make()->label(''),
                Tables\Actions\Action::make('pdf')
                    ->label('PDF')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (Quote $quote, QuotePdfService $pdfService) {
                        $pdf = $pdfService->generatePdf($quote);
                        return response()->download($pdf['path'], $pdf['filename']);
                    }),

                Tables\Actions\Action::make('email')
                    ->label('Email')
                    ->color('success')
                    ->icon('heroicon-o-envelope')
                    ->hidden(fn(Quote $record): bool => $record->status === 'sent')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->default(fn(Quote $quote) => $quote->medicalInstitution->email),
                        Forms\Components\Textarea::make('message')
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
                    ->modalSubmitActionLabel('Send Quote')
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
            //
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
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

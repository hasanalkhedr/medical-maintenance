<?php

namespace App\Filament\Pages;

use App\Models\MedicalInstitution;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\ReportPdfService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Str;

class ClientReports extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.client-reports';
    protected static ?string $navigationLabel = 'Client Reports';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 1;

    public ?array $data = [];
    public $medicalInstitutionId;
    public $startDate;
    public $endDate;

    public function mount(): void
    {
        $this->form->fill();
    }
// protected function getHeaderActions(): array
//     {
//         return [
//             Action::make('export_pdf')
//                 ->label('Export PDF')
//                 ->icon('heroicon-o-document-arrow-down')
//                 ->color('success')
//                 ->action(function () {
//                     if (!$this->medicalInstitutionId) {
//                         Notification::make()
//                             ->title('Error')
//                             ->body('Please select a client first.')
//                             ->danger()
//                             ->send();
//                         return;
//                     }

//                     try {
//                         $pdfService = new ReportPdfService();
//                         $pdf = $pdfService->generateClientReport(
//                             $this->medicalInstitutionId,
//                             $this->startDate,
//                             $this->endDate
//                         );

//                         $client = MedicalInstitution::find($this->medicalInstitutionId);
//                         $fileName = 'client-report-' . Str::slug($client->name) . '-' . now()->format('Y-m-d') . '.pdf';

//                         return response()->streamDownload(function () use ($pdf) {
//                             echo $pdf->output();
//                         }, $fileName);

//                     } catch (\Exception $e) {
//                         Notification::make()
//                             ->title('Export Failed')
//                             ->body('Error generating PDF: ' . $e->getMessage())
//                             ->danger()
//                             ->send();
//                     }
//                 })
//                 ->hidden(fn() => !$this->medicalInstitutionId),
//         ];
//     }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('medical_institution_id')
                    ->label('Client (Medical Institution)')
                    ->options(MedicalInstitution::all()->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->medicalInstitutionId = $state;
                    }),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->startDate = $state;
                    }),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->reactive()
                    ->afterStateUpdated(function ($state) {
                        $this->endDate = $state;
                    }),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
{
    return $table
        ->query(function () {
            if (!$this->medicalInstitutionId) {
                return Invoice::query()->whereNull('id');
            }

            $query = Invoice::with(['payments', 'company', 'contract'])
                ->where('medical_institution_id', $this->medicalInstitutionId);

            if ($this->startDate) {
                $query->whereDate('issue_date', '>=', $this->startDate);
            }

            if ($this->endDate) {
                $query->whereDate('issue_date', '<=', $this->endDate);
            }

            return $query;
        })
        ->columns([
            // Invoice Header
            Stack::make([
                Split::make([
                    TextColumn::make('invoice_number')
                        ->label('Invoice #')
                        ->weight('bold')
                        ->size('lg'),

                    TextColumn::make('issue_date')
                        ->date()
                        ->label('Issue Date'),

                    TextColumn::make('due_date')
                        ->date()
                        ->label('Due Date'),

                    TextColumn::make('total_amount')
                        ->money('AED')
                        ->weight('bold'),

                    TextColumn::make('status')
                        ->badge()
                        ->color(fn(string $state): string => match ($state) {
                            'paid' => 'success',
                            'partial' => 'warning',
                            'pending' => 'danger',
                            default => 'gray',
                        }),
                ]),
            ]),

            // Invoice Details
            Stack::make([
                TextColumn::make('title')
                    ->label('Description')
                    ->weight('medium')
                    ->extraAttributes(['class' => 'text-gray-600']),

                Split::make([
                    TextColumn::make('subtotal')
                        ->money('AED')
                        ->label('Subtotal')
                        ->size('sm'),

                    TextColumn::make('tax_amount')
                        ->money('AED')
                        ->label('Tax')
                        ->size('sm')
                        ->visible(fn($record) => $record && $record->tax_amount > 0),

                    TextColumn::make('discount_amount')
                        ->money('AED')
                        ->label('Discount')
                        ->size('sm')
                        ->visible(fn($record) => $record && $record->discount_amount > 0),
                ])->extraAttributes(['class' => 'text-sm text-gray-500']),
            ]),

            // Payment Details
            Stack::make([
                TextColumn::make('payments_count')
                    ->label('Payments')
                    ->formatStateUsing(fn($state, $record) => $record ? ($record->payments->count() . ' payment(s) - Total: AED ' . number_format($record->paid_amount, 2)) : '')
                    ->extraAttributes(['class' => 'font-medium text-blue-600 mt-2']),

                // Payments list
                TextColumn::make('payments')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$record || $record->payments->isEmpty()) {
                            return '<div class="text-gray-400 text-sm">No payments recorded</div>';
                        }

                        $paymentsHtml = '';
                        foreach ($record->payments as $payment) {
                            $paymentsHtml .= '
                            <div class="border-l-4 border-green-200 pl-2 ml-2 my-1">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">' . $payment->payment_date->format('M d, Y') . '</span>
                                    <span class="font-medium">AED ' . number_format($payment->amount, 2) . '</span>
                                    <span class="text-gray-500">' . (\App\Models\Payment::getPaymentMethods()[$payment->payment_method] ?? $payment->payment_method) . '</span>
                                    <span class="text-gray-400">' . ($payment->reference ?: 'No reference') . '</span>
                                </div>
                            </div>';
                        }
                        return $paymentsHtml;
                    })
                    ->html()
                    ->extraAttributes(['class' => 'mt-1']),
            ]),

            // Summary row for each invoice
            Stack::make([
                Split::make([
                    TextColumn::make('paid_amount')
                        ->money('AED')
                        ->label('Paid')
                        ->color('success')
                        ->weight('medium'),

                    TextColumn::make('remaining_amount')
                        ->money('AED')
                        ->label('Balance')
                        ->color(fn($record) => $record && $record->remaining_amount > 0 ? 'danger' : 'success')
                        ->weight('medium'),

                    TextColumn::make('paid_percentage')
                        ->label('Progress')
                        ->formatStateUsing(fn($state, $record) => $record ? number_format($record->paid_percentage, 1) . '%' : '')
                        ->color(fn($record) => $record ? match (true) {
                            $record->paid_percentage >= 100 => 'success',
                            $record->paid_percentage > 0 => 'warning',
                            default => 'danger'
                        } : 'gray'),
                ])->extraAttributes(['class' => 'border-t pt-2 mt-2']),
            ]),
        ])
        ->contentGrid(['md' => 1])
        ->paginated(false);
}

    public function getInvoices(): Collection
    {
        if (!$this->medicalInstitutionId) {
            return collect();
        }

        $query = Invoice::with(['payments', 'company', 'contract'])
            ->where('medical_institution_id', $this->medicalInstitutionId);

        if ($this->startDate) {
            $query->whereDate('issue_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('issue_date', '<=', $this->endDate);
        }

        return $query->get();
    }

    public function getSummaryStats()
    {
        if (!$this->medicalInstitutionId) {
            return null;
        }

        $invoices = $this->getInvoices();

        return [
            'total_invoices' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('paid_amount'),
            'total_remaining' => $invoices->sum('remaining_amount'),
            'paid_percentage' => $invoices->sum('total_amount') > 0
                ? ($invoices->sum('paid_amount') / $invoices->sum('total_amount')) * 100
                : 0,
        ];
    }

    public function getSubHeading(): string|Htmlable|null
    {
        if (!$this->medicalInstitutionId) {
            return 'Select a client to view reports';
        }

        $client = MedicalInstitution::find($this->medicalInstitutionId);
        $stats = $this->getSummaryStats();

        return "Reports for: {$client->name} | " .
               "Total Invoices: {$stats['total_invoices']} | " .
               "Total Amount: AED " . number_format($stats['total_amount'], 2) . " | " .
               "Paid: AED " . number_format($stats['total_paid'], 2) . " | " .
               "Remaining: AED " . number_format($stats['total_remaining'], 2);
    }

    public function exportPdf()
{
    if (!$this->medicalInstitutionId) {
        Notification::make()
            ->title('Error')
            ->body('Please select a client first.')
            ->danger()
            ->send();
        return;
    }

    try {
        $pdfService = new ReportPdfService();
        $pdf = $pdfService->generateClientReport(
            $this->medicalInstitutionId,
            $this->startDate,
            $this->endDate
        );

        $client = MedicalInstitution::find($this->medicalInstitutionId);
        $fileName = 'client-report-' . Str::slug($client->name) . '-' . now()->format('Y-m-d') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName);

    } catch (\Exception $e) {
        Notification::make()
            ->title('Export Failed')
            ->body('Error generating PDF: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}

}

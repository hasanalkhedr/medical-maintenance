<?php

namespace App\Filament\Resources\ContractResource\Pages;

use App\Filament\Resources\ContractResource;
use App\Models\Contract;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view_contract_file')
                ->label('View Contract File')
                ->icon('heroicon-o-eye')
                ->color('primary')
                ->url(fn(): string => $this->record->contract_file
                    ? \Illuminate\Support\Facades\Storage::url($this->record->contract_file)
                    : '#')
                ->openUrlInNewTab()
                ->hidden(fn(): bool => empty($this->record->contract_file)),

            Actions\Action::make('download_contract_file')
                ->label('Download Contract')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(fn() => response()->download(
                    public_path('storage/' . $this->record->contract_file)
                ))
                ->hidden(fn(): bool => empty($this->record->contract_file)),

            // Add the generate invoice action from the table
            Actions\Action::make('generate_invoice')
                ->label('Generate Invoices')
                ->icon('heroicon-o-document-plus')
                ->action(function () {
                    try {
                        // Check if invoices already exist
                        if ($this->record->hasInvoices()) {
                            throw new \Exception('Invoices already generated for this contract.');
                        }

                        // Generate invoices
                        $invoices = $this->record->generateInvoices();

                        // Show success notification
                        Notification::make()
                            ->title('Invoices Generated Successfully')
                            ->body(count($invoices) . ' invoices have been created based on the ' . $this->record->payment_schedule . ' payment schedule.')
                            ->success()
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Generate Invoices')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        throw $e;
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Generate Invoices')
                ->modalDescription('This will create invoices based on the selected payment schedule. Are you sure?')
                ->modalSubmitActionLabel('Yes, Generate Invoices')
                ->hidden(fn(): bool => $this->record->hasInvoices())
                ->after(function () {
                    // Dispatch event to refresh relation manager
                    $this->dispatch('refreshRelationManager', manager: 'invoices');
                }),
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make()->hidden(fn(): bool => !$this->record->trashed()),
            Actions\ForceDeleteAction::make()->hidden(fn(): bool => !$this->record->trashed()),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Contract Information')
                    ->schema([
                        Components\TextEntry::make('contract_number'),
                        Components\TextEntry::make('company.trade_name'),
                        Components\TextEntry::make('medical_institution.name'),
                        Components\TextEntry::make('start_date')
                            ->date(),
                        Components\TextEntry::make('end_date')
                            ->date(),
                        Components\TextEntry::make('payment_schedule'),
                        Components\TextEntry::make('total_amount')
                            ->money('AED'),
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'active' => 'success',
                                'expired' => 'danger',
                                default => 'gray',
                            }),
                    ])->columns(2),

                Components\Section::make('Contract Details')
                    ->schema([
                        Components\TextEntry::make('terms')
                            ->columnSpanFull()
                            ->markdown()
                            ->hidden(fn($state) => empty($state)),

                        // Contract file preview section
                        Components\Group::make()
                            ->schema([
                                Components\TextEntry::make('contract_file')
                                    ->label('Contract File')
                                    ->formatStateUsing(fn($state) => $state ? 'File Attached' : 'No File Attached')
                                    ->badge()
                                    ->color(fn($state) => $state ? 'success' : 'gray'),

                                Components\Actions::make([
                                    Components\Actions\Action::make('view_file')
                                        ->label('View Contract File')
                                        ->icon('heroicon-o-eye')
                                        ->color('primary')
                                        ->url(fn(): string => $this->record->contract_file
                                            ? \Illuminate\Support\Facades\Storage::url($this->record->contract_file)
                                            : '#')
                                        ->openUrlInNewTab()
                                        ->hidden(fn(): bool => empty($this->record->contract_file)),

                                    Components\Actions\Action::make('download_file')
                                        ->label('Download Contract')
                                        ->icon('heroicon-o-arrow-down-tray')
                                        ->color('gray')
                                        ->action(fn() => response()->download(
                                            public_path('storage/' . $this->record->contract_file)
                                        ))
                                        ->hidden(fn(): bool => empty($this->record->contract_file)),
                                ]),
                            ])
                            ->columnSpanFull()
                            ->columns(2),
                    ]),
            ]);
    }
}

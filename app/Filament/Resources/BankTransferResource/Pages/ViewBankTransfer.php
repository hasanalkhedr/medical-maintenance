<?php
namespace App\Filament\Resources\BankTransferResource\Pages;

use App\Filament\Resources\BankTransferResource;
use App\Models\BankTransfer;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Illuminate\Support\Facades\Storage;

class ViewBankTransfer extends ViewRecord
{
    protected static string $resource = BankTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('download')
                ->label('Download Document')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(function () {
                    if (!$this->record->document_path) {
                        return '#';
                    }

                    return Storage::disk('public')->url($this->record->document_path);
                })
                ->openUrlInNewTab()
                ->hidden(fn (): bool => blank($this->record->document_path)),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            BankTransferResource\Widgets\BankTransferDocumentWidget::class,
        ];
    }


    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('BankTransfer Information')
                    ->schema([
                        Components\TextEntry::make('doc_number'),
                        Components\TextEntry::make('payment.reference'),
                        Components\TextEntry::make('bank_name'),
                        Components\TextEntry::make('doc_date')
                            ->date(),
                    ])->columns(2),

                Components\Section::make('Additional Information')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->markdown(),
                    ]),
            ]);
    }
}

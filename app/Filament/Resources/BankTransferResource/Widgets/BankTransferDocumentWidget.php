<?php

namespace App\Filament\Resources\BankTransferResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class BankTransferDocumentWidget extends Widget
{
    protected static string $view = 'filament.resources.bank-transfer-resource.widgets.bank-transfer-document-widget';

    public $record;

    protected function getViewData(): array
    {
        if ($this->record->document_path) {
            return [
                'documentUrl' => $this->record->document_path
                    ? Storage::url($this->record->document_path)
                    : null,
                'documentType' => $this->record->document_path
                    ? pathinfo($this->record->document_path, PATHINFO_EXTENSION)
                    : null,
            ];
        } else if ($this->record->payment_method === 'bank_transfer') {
            return [
                'documentUrl' => $this->record->bank_transfer->document_path
                    ? Storage::url($this->record->bank_transfer->document_path)
                    : null,
                'documentType' => $this->record->bank_transfer->document_path
                    ? pathinfo($this->record->bank_transfer->document_path, PATHINFO_EXTENSION)
                    : null,
            ];
        } else {
            return [
                'documentUrl' => null,
                'documentType' => null,
            ];
        }
    }
}

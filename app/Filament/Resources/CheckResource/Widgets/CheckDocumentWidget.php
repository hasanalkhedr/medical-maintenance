<?php

namespace App\Filament\Resources\CheckResource\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;

class CheckDocumentWidget extends Widget
{
    protected static string $view = 'filament.resources.check-resource.widgets.check-document-widget';

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
        } else if ($this->record->payment_method === 'check') {
            return [
                'documentUrl' => $this->record->check->document_path
                    ? Storage::url($this->record->check->document_path)
                    : null,
                'documentType' => $this->record->check->document_path
                    ? pathinfo($this->record->check->document_path, PATHINFO_EXTENSION)
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

<?php
namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function generatePdf(Invoice $invoice)
    {
        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice]);
        $filename = 'invoices/invoice-'.$invoice->invoice_number.'.pdf';

        Storage::disk('public')->put($filename, $pdf->output());

        return [
            'path' => storage_path('app/public/'.$filename),
            'url' => asset('storage/'.$filename),
            'filename' => 'Invoice-'.$invoice->invoice_number.'.pdf'
        ];
    }
}

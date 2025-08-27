<?php
namespace App\Services;

use App\Models\Quote;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class QuotePdfService
{
    public function generatePdf(Quote $quote)
    {
        $pdf = Pdf::loadView('quotes.pdf', ['quote' => $quote]);
        $filename = 'quotes/quote-'.$quote->quote_number.'.pdf';

        Storage::disk('public')->put($filename, $pdf->output());

        return [
            'path' => storage_path('app/public/'.$filename),
            'url' => asset('storage/'.$filename),
            'filename' => 'Quote-'.$quote->quote_number.'.pdf'
        ];
    }
}

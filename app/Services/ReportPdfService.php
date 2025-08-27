<?php

namespace App\Services;

use App\Models\MedicalInstitution;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Date;

class ReportPdfService
{
    public function generateClientReport($medicalInstitutionId, $startDate = null, $endDate = null)
    {
        $client = MedicalInstitution::with(['company'])->findOrFail($medicalInstitutionId);

        $query = Invoice::with(['payments', 'contract', 'items', 'medicalInstitution'])
            ->where('medical_institution_id', $medicalInstitutionId);

        if ($startDate) {
            $query->whereDate('issue_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('issue_date', '<=', $endDate);
        }

        $invoices = $query->get();

        $stats = [
            'total_invoices' => $invoices->count(),
            'total_amount' => $invoices->sum('total_amount'),
            'total_paid' => $invoices->sum('paid_amount'),
            'total_remaining' => $invoices->sum('remaining_amount'),
            'paid_percentage' => $invoices->sum('total_amount') > 0
                ? ($invoices->sum('paid_amount') / $invoices->sum('total_amount')) * 100
                : 0,
        ];

        $pdf = PDF::loadView('pdf.client-report', [
            'client' => $client,
            'invoices' => $invoices,
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'generatedAt' => now(),
        ]);

        // إعداد خيارات PDF
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('defaultFont', 'DejaVu Sans');

        return $pdf;
    }
}

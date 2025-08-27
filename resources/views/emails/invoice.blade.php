<x-mail::message>
    # Invoice #{{ $invoice->invoice_number }}

    Dear {{ $invoice->medicalInstitution->contact_person ?? 'Valued Customer' }},

    Please find attached your invoice from MASARNA MEDICAL & LABORATORY EQUIPMENT REPAIRING L.L.C.

    **Invoice Summary:**
    - Invoice Number: {{ $invoice->invoice_number }}
    - Date: {{ $invoice->issue_date->format('d/m/Y') }}
    - Valid Until: {{ $invoice->expiry_date->format('d/m/Y') }}
    - Total Amount: AED {{ number_format($invoice->total_amount, 2) }}

    @isset($message)
        **Additional Message:**
        {{ $message }}
    @endisset

    <x-mail::button :url="route('filament.admin.resources.invoices.view', $invoice->id)">
        View Invoice Online
    </x-mail::button>

    Thanks,<br>
    MASARNA MEDICAL & LABORATORY EQUIPMENT REPAIRING L.L.C
</x-mail::message>

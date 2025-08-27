<x-mail::message>
    # Quote #{{ $quote->quote_number }}

    Dear {{ $quote->medicalInstitution->contact_person ?? 'Valued Customer' }},

    Please find attached your quote from MASARNA MEDICAL & LABORATORY EQUIPMENT REPAIRING L.L.C.

    **Quote Summary:**
    - Quote Number: {{ $quote->quote_number }}
    - Date: {{ $quote->issue_date->format('d/m/Y') }}
    - Valid Until: {{ $quote->expiry_date->format('d/m/Y') }}
    - Total Amount: AED {{ number_format($quote->total_amount, 2) }}

    @isset($message)
        **Additional Message:**
        {{ $message }}
    @endisset

    <x-mail::button :url="route('filament.admin.resources.quotes.view', $quote->id)">
        View Quote Online
    </x-mail::button>

    Thanks,<br>
    MASARNA MEDICAL & LABORATORY EQUIPMENT REPAIRING L.L.C
</x-mail::message>

<x-filament-panels::page>
    <x-filament-panels::form wire:submit="filter">
        {{ $this->form }}

        <x-filament::button type="submit" class="mt-4">
            Generate Report
        </x-filament::button>
        @if($this->medicalInstitutionId)
                <x-filament::button
                    wire:click="exportPdf"
                    icon="heroicon-o-document-arrow-down"
                    color="success">
                    Export PDF
                </x-filament::button>
            @endif
    </x-filament-panels::form>

    @if ($this->medicalInstitutionId)
        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Summary Statistics
            </x-slot>

            @php
                $stats = $this->getSummaryStats();
                $client = \App\Models\MedicalInstitution::find($this->medicalInstitutionId);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-gray-500">Total Invoices</div>
                    <div class="text-2xl font-bold">{{ $stats['total_invoices'] }}</div>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-blue-500">Total Amount</div>
                    <div class="text-2xl font-bold">AED {{ number_format($stats['total_amount'], 2) }}</div>
                </div>

                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-green-500">Total Paid</div>
                    <div class="text-2xl font-bold">AED {{ number_format($stats['total_paid'], 2) }}</div>
                </div>

                <div class="bg-orange-50 p-4 rounded-lg">
                    <div class="text-sm font-medium text-orange-500">Total Remaining</div>
                    <div class="text-2xl font-bold">AED {{ number_format($stats['total_remaining'], 2) }}</div>
                </div>
            </div>

            <div class="mt-4">
                <div class="text-sm font-medium text-gray-500">Payment Progress</div>
                <div class="w-full bg-gray-200 rounded-full h-4 mt-1">
                    <div class="bg-green-500 h-4 rounded-full"
                         style="width: {{ min($stats['paid_percentage'], 100) }}%"></div>
                </div>
                <div class="text-sm text-gray-600 mt-1">
                    {{ number_format($stats['paid_percentage'], 1) }}% Paid
                </div>
            </div>
        </x-filament::section>

        <x-filament::section class="mt-6">
            <x-slot name="heading">
                Invoices with Payments
            </x-slot>

            <div class="space-y-4">
                {{ $this->table }}
            </div>
        </x-filament::section>
    @else
        <x-filament::section class="mt-6">
            <div class="text-center py-8 text-gray-500">
                Please select a client to generate reports.
            </div>
        </x-filament::section>
    @endif

    <style>
        .fi-ta-content grid grid-cols-1 md\:grid-cols-1 {
            grid-template-columns: 1fr !important;
        }

        .fi-ta-col {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .fi-ta-col:last-child {
            border-bottom: 2px solid #9ca3af;
        }
    </style>
</x-filament-panels::page>

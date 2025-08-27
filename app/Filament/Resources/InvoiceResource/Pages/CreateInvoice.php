<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Models\Contract;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        // If coming from contract with pre-filled data
        if (request()->has('contract_id')) {
            $contract = Contract::find(request('contract_id'));

            if ($contract) {
                // Set default values from contract
                $data['company_id'] = $contract->company_id;
                $data['medical_institution_id'] = $contract->medical_institution_id;
                $data['contract_id'] = $contract->id;
                $data['title'] = 'Invoice for Contract ' . $contract->contract_number;
            }
        }

        return parent::handleRecordCreation($data);
    }
}

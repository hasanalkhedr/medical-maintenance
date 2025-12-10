<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\BankTransfer;
use App\Models\Check;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPayment extends EditRecord
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load check data if payment method is check
        if ($data['payment_method'] === 'check') {
            $check = Check::where('payment_id', $this->record->id)->first();
            if ($check) {
                $data['check'] = [
                    'check_number' => $check->check_number,
                    'bank_name' => $check->bank_name,
                    'branch_name' => $check->branch_name,
                    'issue_date' => $check->issue_date,
                    'due_date' => $check->due_date,
                    'status' => $check->status,
                    'notes' => $check->notes,
                    'document_path' => $check->document_path,
                ];
            }
        }
        if ($data['payment_method'] === 'bank_transfer') {
            $bank_transfer = BankTransfer::where('payment_id', $this->record->id)->first();
            if ($bank_transfer) {
                $data['bank_transfer'] = [
                    'doc_number' => $bank_transfer->doc_number,
                    'bank_name' => $bank_transfer->bank_name,
                    'doc_date' => $bank_transfer->doc_date,
                    'notes' => $bank_transfer->notes,
                    'document_path' => $bank_transfer->document_path,
                ];
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Extract check data
        $checkData = [];
        if (isset($data['check'])) {
            $checkData = $data['check'];
            unset($data['check']);
        }
        // Extract bank_transfer data
        $bank_transferData = [];
        if (isset($data['bank_transfer'])) {
            $bank_transferData = $data['bank_transfer'];
            unset($data['bank_transfer']);
        }

        // Update the payment
        $record->update($data);

        // Handle check data
        if ($data['payment_method'] === 'check') {
            $check = Check::where('payment_id', $record->id)->first();

            if ($check && !empty($checkData)) {
                // Update existing check
                $check->update($checkData);
            } elseif (!$check && !empty($checkData)) {
                // Create new check
                $checkData['payment_id'] = $record->id;
                Check::create($checkData);
            }
        } else {
            // If payment method is not check, delete any existing check record
            Check::where('payment_id', $record->id)->delete();
        }

        // Handle bank_transfer data
        if ($data['payment_method'] === 'bank_transfer') {
            $bank_transfer = BankTransfer::where('payment_id', operator: $record->id)->first();

            if ($bank_transfer && !empty($bank_transferData)) {
                // Update existing bank_transfer
                $bank_transfer->update($bank_transferData);
            } elseif (!$bank_transfer && !empty($bank_transferData)) {
                // Create new bank_transfer
                $bank_transferData['payment_id'] = $record->id;
                BankTransfer::create($bank_transferData);
            }
        } else {
            // If payment method is not bank_transfer, delete any existing bank_transfer record
            BankTransfer::where('payment_id', $record->id)->delete();
        }

        return $record;
    }
}

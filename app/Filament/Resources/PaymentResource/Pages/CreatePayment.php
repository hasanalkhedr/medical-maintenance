<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\BankTransfer;
use App\Models\Check;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Extract check data if payment method is check
        $checkData = [];
        if ($data['payment_method'] === 'check' && isset($data['check'])) {
            $checkData = $data['check'];
            unset($data['check']);
        }

        // Extract bank_transfer data if payment method is bank_transfer
        $bankData = [];
        if ($data['payment_method'] === 'bank_transfer' && isset($data['bank_transfer'])) {
            $bankData = $data['bank_transfer'];
            unset($data['bank_transfer']);
        }

        // Create the payment
        $payment = static::getModel()::create($data);

        // Create check record if payment method is check
        if ($data['payment_method'] === 'check' && !empty($checkData)) {
            $checkData['payment_id'] = $payment->id;
            Check::create($checkData);
        }

        // Create bank_transfer record if payment method is bank_transfer
        if ($data['payment_method'] === 'bank_transfer' && !empty($bankData)) {
            $bankData['payment_id'] = $payment->id;
            BankTransfer::create($bankData);
        }

        return $payment;
    }
}

<?php

namespace App\Filament\Resources\BankTransferResource\Pages;

use App\Filament\Resources\BankTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBankTransfer extends EditRecord
{
    protected static string $resource = BankTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

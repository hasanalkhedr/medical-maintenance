<?php

namespace App\Filament\Resources\BankTransferResource\Pages;

use App\Filament\Resources\BankTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBankTransfers extends ListRecords
{
    protected static string $resource = BankTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

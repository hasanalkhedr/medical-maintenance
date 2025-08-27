<?php

namespace App\Filament\Resources\MedicalInstitutionResource\Pages;

use App\Filament\Resources\MedicalInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMedicalInstitution extends EditRecord
{
    protected static string $resource = MedicalInstitutionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}

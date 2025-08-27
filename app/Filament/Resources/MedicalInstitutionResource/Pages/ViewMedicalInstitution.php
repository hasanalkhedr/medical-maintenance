<?php
namespace App\Filament\Resources\MedicalInstitutionResource\Pages;

use App\Filament\Resources\MedicalInstitutionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewMedicalInstitution extends ViewRecord
{
    protected static string $resource = MedicalInstitutionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Institution Information')
                    ->schema([
                        Components\TextEntry::make('company.trade_name'),
                        Components\TextEntry::make('name'),
                        Components\TextEntry::make('address')
                            ->columnSpanFull(),
                    ]),

                Components\Section::make('Contact Information')
                    ->schema([
                        Components\TextEntry::make('phone'),
                        Components\TextEntry::make('email'),
                        Components\TextEntry::make('contact_person'),
                        Components\TextEntry::make('contact_person_phone'),
                    ])->columns(2),
            ]);
    }
}

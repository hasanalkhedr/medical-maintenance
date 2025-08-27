<?php
namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Company Information')
                    ->schema([
                        Components\TextEntry::make('trade_name'),
                        Components\TextEntry::make('license_number'),
                        Components\TextEntry::make('address')
                            ->columnSpanFull(),
                    ])->columns(2),

                Components\Section::make('Contact Information')
                    ->schema([
                        Components\TextEntry::make('phone'),
                        Components\TextEntry::make('email'),
                    ])->columns(2),

                Components\Section::make('Additional Information')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->columnSpanFull(),

                    ]),
            ]);
    }
}

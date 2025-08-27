<?php
namespace App\Filament\Resources\CheckResource\Pages;

use App\Filament\Resources\CheckResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;

class ViewCheck extends ViewRecord
{
    protected static string $resource = CheckResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Check Information')
                    ->schema([
                        Components\TextEntry::make('check_number'),
                        Components\TextEntry::make('payment.reference'),
                        Components\TextEntry::make('bank_name'),
                        Components\TextEntry::make('branch_name'),
                        Components\TextEntry::make('issue_date')
                            ->date(),
                        Components\TextEntry::make('due_date')
                            ->date(),
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'cleared' => 'success',
                                'bounced' => 'danger',
                                'pending' => 'warning',
                                default => 'gray',
                            }),
                    ])->columns(2),

                Components\Section::make('Additional Information')
                    ->schema([
                        Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->markdown(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('mark_as_cleared')
                ->label('Mark as Cleared')
                ->icon('heroicon-o-check-badge')
                ->visible(fn ($record) => $record->status === 'pending')
                ->action(function ($record) {
                    $record->update(['status' => 'cleared']);
                })
                ->requiresConfirmation(),
            Actions\EditAction::make(),
        ];
    }
}

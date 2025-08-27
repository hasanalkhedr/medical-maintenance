<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->required()
                    ->minValue(0.01)
                    ->maxValue(fn($record) => $record?->invoice?->remaining_amount ?? 999999)
                    ->suffix('AED'),

                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->default(now()),

                Forms\Components\Select::make('payment_method')
                    ->options(\App\Models\Payment::getPaymentMethods())
                    ->required()
                    ->default('bank_transfer'),

                Forms\Components\TextInput::make('reference')
                    ->maxLength(255),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('amount')
            ->columns([
                Tables\Columns\TextColumn::make('amount')
                    ->money('AED')
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->badge()
                    ->formatStateUsing(fn($state) => \App\Models\Payment::getPaymentMethods()[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'cash' => 'gray',
                        'bank_transfer' => 'info',
                        'check' => 'warning',
                        'credit_card' => 'success',
                        'debit_card' => 'primary',
                        'online' => 'purple',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('reference'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Payment')
                    ->modalHeading('Add Payment to Invoice')
                    ->after(function ($record) {
                        // Refresh the invoice to update paid amount
                        $this->ownerRecord->refresh();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->after(function ($record) {
                        // Refresh the invoice to update paid amount
                        $this->ownerRecord->refresh();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            // Refresh the invoice to update paid amount
                            $this->ownerRecord->refresh();
                        }),
                ]),
            ]);
    }
}

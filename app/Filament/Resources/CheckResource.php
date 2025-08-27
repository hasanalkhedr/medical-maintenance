<?php
namespace App\Filament\Resources;

use App\Filament\Resources\CheckResource\Pages;
use App\Filament\Resources\CheckResource\RelationManagers;
use App\Models\Check;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CheckResource extends Resource
{
    protected static ?string $model = Check::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $modelLabel = 'Check';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payment_id')
                    ->relationship('payment', 'reference')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('check_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('branch_name')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('issue_date')
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'cleared' => 'Cleared',
                        'bounced' => 'Bounced',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('check_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment.reference')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'cleared' => 'success',
                        'bounced' => 'danger',
                        'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'cleared' => 'Cleared',
                        'bounced' => 'Bounced',
                    ]),
                Tables\Filters\Filter::make('due_soon')
                    ->label('Due Soon (within 7 days)')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->whereBetween('due_date', [now(), now()->addDays(7)])),
                Tables\Filters\Filter::make('overdue')
                    ->label('Overdue Checks')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pending')
                        ->where('due_date', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChecks::route('/'),
            'create' => Pages\CreateCheck::route('/create'),
            //'view' => Pages\ViewCheck::route('/{record}'),
            'edit' => Pages\EditCheck::route('/{record}/edit'),
        ];
    }
}

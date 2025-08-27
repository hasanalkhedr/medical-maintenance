<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ContractResource\Pages;
use App\Filament\Resources\ContractResource\RelationManagers;
use App\Models\Contract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Contract';
    protected static ?string $navigationGroup = 'Sales Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contract Information')
                    ->schema([
                        Forms\Components\TextInput::make('contract_number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'trade_name')
                            ->required()
                            ->live()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('medical_institution_id')
                            ->options(function (callable $get) {
                                $companyId = $get('company_id');
                                if (!$companyId) {
                                    return [];
                                }
                                return \App\Models\MedicalInstitution::where('company_id', $companyId)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required(),
                        Forms\Components\Select::make('payment_schedule')
                            ->options([
                                'monthly' => 'Monthly',
                                'quarterly' => 'Quarterly',
                                'semi-annually' => 'Semi-Annually',
                                'annually' => 'Annually',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('total_amount')
                            ->numeric()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Contract Details')
                    ->schema([
                        Forms\Components\FileUpload::make('contract_file')
                            ->label('Contract Document')
                            ->helperText('Upload the signed contract document (PDF only, max: 5MB)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(5120) // 5MB in KB
                            ->directory('contracts')
                            ->preserveFilenames()
                            ->downloadable()
                            ->openable()
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('terms')
                            ->label('Contract Terms & Conditions')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('contract_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.trade_name')
                    ->sortable()
                    ->toggleable(true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('medicalInstitution.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_schedule')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'monthly' => 'info',
                        'quarterly' => 'primary',
                        'semi-annually' => 'warning',
                        'annually' => 'success',
                    }),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('AED')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                // Add a column to show if contract file exists
                Tables\Columns\IconColumn::make('contract_file')
                    ->label('Contract File')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-text')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('company')
                    ->relationship('company', 'trade_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('payment_schedule')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'semi-annually' => 'Semi-Annually',
                        'annually' => 'Annually',
                    ]),
                Tables\Filters\Filter::make('active')
                    ->query(fn(Builder $query): Builder => $query->where('status', 'active')),
                Tables\Filters\Filter::make('expired')
                    ->query(fn(Builder $query): Builder => $query->where('status', 'expired')),
            ])
            ->actions([
                Tables\Actions\Action::make('view_contract_file')
                    ->label('View Contract File')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn(Contract $record): string => $record->contract_file
                        ? \Illuminate\Support\Facades\Storage::url($record->contract_file)
                        : '#')
                    ->openUrlInNewTab()
                    ->hidden(fn(Contract $record): bool => empty($record->contract_file)),

                Tables\Actions\Action::make('download_contract_file')
                    ->label('Download Contract')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(fn(Contract $record) => response()->download(
                        public_path('storage/' . $record->contract_file)
                    ))
                    ->hidden(fn(Contract $record): bool => empty($record->contract_file)),

                // In your ContractResource table() method
                Tables\Actions\Action::make('generate_invoice')
                    ->label('Generate Invoices')
                    ->icon('heroicon-o-document-plus')
                    ->action(function (Contract $record) {
                        try {
                            // Check if invoices already exist
                            if ($record->hasInvoices()) {
                                throw new \Exception('Invoices already generated for this contract.');
                            }

                            // Generate invoices
                            $invoices = $record->generateInvoices();

                            // Show success notification
                            Notification::make()
                                ->title('Invoices Generated Successfully')
                                ->body(count($invoices) . ' invoices have been created based on the ' . $record->payment_schedule . ' payment schedule.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Failed to Generate Invoices')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw $e;
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generate Invoices')
                    ->modalDescription('This will create invoices based on the selected payment schedule. Are you sure?')
                    ->modalSubmitActionLabel('Yes, Generate Invoices')
                    ->hidden(fn(Contract $record): bool => $record->hasInvoices()),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContracts::route('/'),
            'create' => Pages\CreateContract::route('/create'),
            'view' => Pages\ViewContract::route('/{record}'),
            'edit' => Pages\EditContract::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}

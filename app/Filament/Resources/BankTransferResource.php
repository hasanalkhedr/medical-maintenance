<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BankTransferResource\Pages;
use App\Filament\Resources\BankTransferResource\RelationManagers;
use App\Models\BankTransfer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Storage;

class BankTransferResource extends Resource
{
    protected static ?string $model = BankTransfer::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $modelLabel = 'Bank Transfer';
    protected static ?string $navigationGroup = 'Financial Management';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('payment_id')
                    ->relationship('payment', 'payment_method')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('doc_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('doc_date')
                    ->required(),

                Forms\Components\FileUpload::make('document_path')
                    ->label('Bank Document')
                    ->helperText('Upload Bank image or PDF (Max: 5MB)')
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'application/pdf',
                    ])
                    ->maxSize(5120)
                    ->directory('bank_transfers/documents')
                    ->disk('public') // or 'local' - make sure it matches your storage
                    ->visibility('public') // if using public disk
                    ->downloadable()
                    ->openable()
                    ->previewable()
                    ->preserveFilenames(),

                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('doc_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment.reference')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name'),
                Tables\Columns\TextColumn::make('doc_date')
                    ->date()
                    ->sortable(),

                // Document column
                Tables\Columns\IconColumn::make('document_path')
                    ->label('Document')
                    ->icon(fn($record): string => $record->document_path
                        ? 'heroicon-o-document'
                        : 'heroicon-o-minus')
                    ->color(fn($record): string => $record->document_path
                        ? 'success'
                        : 'gray')
                    ->tooltip(fn($record): string => $record->document_path
                        ? 'Document attached'
                        : 'No document'),
            ])
            ->filters([

                // Filter for checks with/without documents
                Tables\Filters\Filter::make('has_document')
                    ->label('Has Document')
                    ->query(fn(Builder $query): Builder => $query->whereNotNull('document_path')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                /// Fixed download action
                Tables\Actions\Action::make('download')
                    ->label('Download Document')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (BankTransfer $record) {
                        if (!$record->document_path) {
                            return;
                        }

                        // Get the storage disk
                        $disk = 'public'; // Use the same disk as in FileUpload

                        // BankTransfer if file exists
                        if (!Storage::disk($disk)->exists($record->document_path)) {
                            throw new \Exception("File not found: " . $record->document_path);
                        }

                        return Storage::disk($disk)->download($record->document_path);
                    })
                    ->hidden(fn(BankTransfer $record): bool => blank($record->document_path)),

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
            'index' => Pages\ListBankTransfers::route('/'),
            'create' => Pages\CreateBankTransfer::route('/create'),
            'view' => Pages\ViewBankTransfer::route('/{record}'), // Uncomment this
            'edit' => Pages\EditBankTransfer::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Filament\Resources\PurchaseOrderResource\RelationManagers\ItemsRelationManager;
use App\Models\PurchaseOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Purchase';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Purchase Order')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('po_number')
                        ->label('PO Number')
                        ->required()
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('supplier_id')
                        ->label('Supplier')
                        ->relationship('supplier', 'name') // <- pastikan relasi supplier() ada di model
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'ordered' => 'Ordered',
                            'received' => 'Received',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->default('draft'),

                    Forms\Components\DateTimePicker::make('ordered_at')
                        ->label('Ordered At')
                        ->seconds(false),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Summary')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('total')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false) // total dihitung dari items (bukan input user)
                        ->helperText('Total otomatis dari item.')
                        ->default(0),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('PO Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'ordered',
                        'success' => 'received',
                        'danger' => 'cancelled',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('ordered_at')
                    ->label('Ordered At')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->money('IDR', true)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'ordered' => 'Ordered',
                        'received' => 'Received',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // ItemsRelationManager::class, // Items PO ditaruh di relation manager
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }

    // opsional: kalau lu mau list PO cuma milik user tertentu / tenant
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}

<?php

namespace App\Filament\Resources;

use App\Enums\InventoryMovementType;
use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 30;

    protected static ?string $navigationLabel = 'Inventory Movements';
    protected static ?string $modelLabel = 'Inventory Movement';
    protected static ?string $pluralModelLabel = 'Inventory Movements';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Movement Information')
                ->schema([
                    Select::make('product_id')
                        ->label('Product')
                        ->relationship('product', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('type')
                        ->label('Type')
                        ->required()
                        ->options([
                            'IN' => 'IN (Stock In)',
                            'OUT' => 'OUT (Stock Out)',
                            'ADJUST' => 'ADJUST (Adjustment)',
                        ]),

                    TextInput::make('quantity')
                        ->label('Quantity')
                        ->numeric()
                        ->minValue(1)
                        ->required(),

                    TextInput::make('reference_type')
                        ->label('Reference Type')
                        ->helperText('Contoh: purchase_order, sale, manual')
                        ->maxLength(255)
                        ->required(),

                    TextInput::make('reference_id')
                        ->label('Reference ID')
                        ->numeric()
                        ->helperText('Optional. Isi kalau movement ini terkait dokumen tertentu.')
                        ->nullable(),

                    Textarea::make('note')
                        ->label('Note')
                        ->rows(4)
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => fn($state) => ($state instanceof InventoryMovementType ? $state->value : $state) === 'IN',
                        'danger' => fn($state) => ($state instanceof InventoryMovementType ? $state->value : $state) === 'OUT',
                        'warning' => fn($state) => ($state instanceof InventoryMovementType ? $state->value : $state) === 'ADJUST',
                    ])
                    ->formatStateUsing(fn($state) => $state instanceof InventoryMovementType ? $state->value : ($state ?: '-'))
                    ->sortable(),


                TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable(),

                TextColumn::make('reference_type')
                    ->label('Ref Type')
                    ->searchable()
                    ->limit(25)
                    ->sortable(),

                TextColumn::make('reference_id')
                    ->label('Ref ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('is_void')
                    ->label('Status')
                    ->colors([
                        'success' => 0,
                        'danger' => 1,
                    ])
                    ->formatStateUsing(fn($state) => (int) $state === 1 ? 'VOID' : 'ACTIVE')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'IN' => 'IN',
                        'OUT' => 'OUT',
                        'ADJUST' => 'ADJUST',
                    ]),

                SelectFilter::make('is_void')
                    ->label('Status')
                    ->options([
                        0 => 'Active',
                        1 => 'Void',
                    ]),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['from'] ?? null, fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'] ?? null, fn($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->disabled(fn($record) => (int) $record->is_void === 1),

                // ✅ VOID ACTION (update is_void = 1)
                Tables\Actions\Action::make('void')
                    ->label('Void')
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->visible(fn($record) => (int) $record->is_void === 0)
                    ->form([
                        Textarea::make('note')
                            ->label('Void Note')
                            ->helperText('Catatan pembatalan movement (opsional).')
                            ->maxLength(255),
                    ])
                    ->action(function (InventoryMovement $record, array $data) {
                        $record->is_void = 1;

                        // kalau user isi note void, kita append biar gak hilang note lama
                        if (!empty($data['note'])) {
                            $existing = (string) ($record->note ?? '');
                            $record->note = trim($existing . "\n[VOID] " . $data['note']);
                        }

                        $record->save();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No inventory movements yet')
            ->emptyStateDescription('Inventory movement akan muncul ketika ada transaksi stock IN/OUT/ADJUST.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
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
            'index' => Pages\ListInventoryMovements::route('/'),
            'create' => Pages\CreateInventoryMovement::route('/create'),
            'edit' => Pages\EditInventoryMovement::route('/{record}/edit'),
        ];
    }
}

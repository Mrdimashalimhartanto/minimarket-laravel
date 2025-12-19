<?php

namespace App\Filament\Resources;

use App\Enums\ProductStatus;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Products';
    protected static ?string $modelLabel = 'Product';
    protected static ?string $pluralModelLabel = 'Products';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Product Information')
                ->schema([
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('sku')
                        ->label('SKU')
                        ->required()
                        ->maxLength(100)
                        ->unique(ignoreRecord: true),

                    TextInput::make('name')
                        ->label('Product Name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true),

                    Select::make('status')
                        ->label('Status')
                        ->required()
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                        ]),

                    FileUpload::make('image_path')
                        ->label('Image')
                        ->disk('minio')
                        ->directory('products')
                        ->visibility('public')
                        ->image()
                        ->openable()
                        ->downloadable(),


                    Textarea::make('description')
                        ->label('Description')
                        ->rows(4)
                        ->maxLength(500)
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Pricing & Stock')
                ->schema([
                    TextInput::make('cost_price')
                        ->label('Cost Price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required(),

                    TextInput::make('selling_price')
                        ->label('Selling Price')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('Rp')
                        ->required(),

                    TextInput::make('stock')
                        ->label('Stock')
                        ->numeric()
                        ->minValue(0)
                        ->required(),

                    TextInput::make('min_stock')
                        ->label('Min Stock')
                        ->numeric()
                        ->minValue(0)
                        ->required(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // ✅ Stock based on inventory_movements (audit)
                $stockSubquery = "
                    (
                        SELECT COALESCE(SUM(
                            CASE
                                WHEN im.is_void = 1 THEN 0
                                WHEN im.type = 'IN' THEN im.quantity
                                WHEN im.type = 'OUT' THEN -im.quantity
                                WHEN im.type = 'ADJUST' THEN im.quantity
                                ELSE 0
                            END
                        ), 0)
                        FROM inventory_movements im
                        WHERE im.product_id = products.id
                    )
                ";

                return $query->select('products.*')
                    ->selectRaw("$stockSubquery as stock_on_hand");
            })
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->circular()
                    ->size(45)
                    ->disk('minio')
                    ->visibility('public')
                    ->checkFileExistence(false)
                    ->getStateUsing(fn($record) => $record->image_path), // ✅ PATH RELATIVE: products/2025/12/xxx.png
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => fn($state) => ($state instanceof ProductStatus ? $state->value : $state) === 'active',
                        'danger' => fn($state) => ($state instanceof ProductStatus ? $state->value : $state) === 'inactive',
                    ])

                    ->formatStateUsing(function ($state) {
                        if ($state instanceof ProductStatus) {
                            return strtoupper($state->value); // active / inactive -> ACTIVE / INACTIVE
                            // kalau enum lu punya label(): return $state->label();
                        }

                        return $state ? strtoupper((string) $state) : '-';
                    })

                    ->sortable(),

                TextColumn::make('cost_price')
                    ->label('Cost')
                    ->money('IDR', locale: 'id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('selling_price')
                    ->label('Selling')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                // ✅ Stock yang dipakai sistem (stored)
                BadgeColumn::make('stock')
                    ->label('Stock Barang')
                    ->alignCenter()
                    ->colors([
                        'danger' => fn($record) => (int) $record->stock <= 0,
                        'warning' => fn($record) => (int) $record->stock > 0 && (int) $record->stock <= (int) $record->min_stock,
                        'success' => fn($record) => (int) $record->stock > (int) $record->min_stock,
                    ])
                    ->formatStateUsing(fn($state) => (string) $state)
                    ->sortable(),

                TextColumn::make('min_stock')
                    ->label('Min')
                    ->alignCenter()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // ✅ Optional: Stock hasil audit dari movements
                BadgeColumn::make('stock_on_hand')
                    ->label('Stock (Movement)')
                    ->alignCenter()
                    ->colors([
                        'danger' => fn($state) => (int) $state <= 0,
                        'warning' => fn($state) => (int) $state > 0 && (int) $state <= 5,
                        'success' => fn($state) => (int) $state > 5,
                    ])
                    ->sortable(query: function (Builder $query, string $direction) {
                        $stockSubquery = "
                            (
                                SELECT COALESCE(SUM(
                                    CASE
                                        WHEN im.is_void = 1 THEN 0
                                        WHEN im.type = 'IN' THEN im.quantity
                                        WHEN im.type = 'OUT' THEN -im.quantity
                                        WHEN im.type = 'ADJUST' THEN im.quantity
                                        ELSE 0
                                    END
                                ), 0)
                                FROM inventory_movements im
                                WHERE im.product_id = products.id
                            )
                        ";

                        $query->orderByRaw("$stockSubquery {$direction}");
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                    ]),

                // ✅ Filter Low Stock
                Filter::make('low_stock')
                    ->label('Low Stock')
                    ->query(fn(Builder $query) => $query->whereColumn('stock', '<=', 'min_stock')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // ✅ Shortcut Adjust Stock (buat audit movement + update stock product)
                Tables\Actions\Action::make('adjust_stock')
                    ->label('Adjust Stock')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->form([
                        Select::make('type')
                            ->label('Type')
                            ->options([
                                'IN' => 'IN (Stock In)',
                                'OUT' => 'OUT (Stock Out)',
                                'ADJUST' => 'ADJUST',
                            ])
                            ->required(),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                        Textarea::make('note')
                            ->label('Note')
                            ->maxLength(255),
                    ])
                    ->action(function (Product $record, array $data) {
                        $qty = (int) $data['quantity'];
                        $type = $data['type'];

                        // 1) Insert movement audit
                        DB::table('inventory_movements')->insert([
                            'product_id' => $record->id,
                            'type' => $type,
                            'reference_type' => 'FILAMENT',
                            'reference_id' => null,
                            'quantity' => $qty,
                            'note' => $data['note'] ?? null,
                            'is_void' => 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        // 2) Update stock stored di products (biar sesuai sistem lu)
                        // IN => +qty, OUT => -qty, ADJUST => +qty
                        if ($type === 'IN' || $type === 'ADJUST') {
                            $record->increment('stock', $qty);
                        } elseif ($type === 'OUT') {
                            $record->decrement('stock', $qty);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No products yet')
            ->emptyStateDescription('Create your first product to start managing inventory.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // next: RelationManager movements per product kalau lu mau
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

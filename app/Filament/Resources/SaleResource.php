<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers\ItemsRelationManager;
use App\Models\Sale;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Sale';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Sale')
                ->columns(2)
                ->schema([
                    TextInput::make('code')
                        ->label('Code')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    // NOTE:
                    // kalau cashier lu sebenernya relasi ke users, nanti kita ganti jadi Select->relationship(...)
                    TextInput::make('cashier_id')
                        ->label('Cashier ID')
                        ->numeric()
                        ->required(),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->required()
                        ->options([
                            'cash' => 'Cash',
                            'transfer' => 'Transfer',
                            'e_wallet' => 'E-Wallet',
                        ])
                        ->default('cash'),

                    TextInput::make('paid_amount')
                        ->label('Paid Amount')
                        ->numeric()
                        ->required()
                        ->default(0)
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $total = (float) ($get('total_amount') ?? 0);
                            $discount = (float) ($get('total_discount') ?? 0);
                            $grandTotal = max(0, $total - $discount);

                            $paid = (float) ($state ?? 0);
                            $change = $paid - $grandTotal;

                            $set('change_amount', $change > 0 ? $change : 0);
                        }),

                    // total & discount biasanya dari items => read-only di form
                    TextInput::make('total_amount')
                        ->label('Total Amount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true)
                        ->default(0)
                        ->helperText('Total otomatis dari item.'),

                    TextInput::make('total_discount')
                        ->label('Total Discount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true)
                        ->default(0)
                        ->helperText('Discount total (kalau ada).'),

                    TextInput::make('change_amount')
                        ->label('Change Amount')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(true)
                        ->default(0)
                        ->helperText('Kembalian otomatis dari paid - (total - discount).'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cashier_id')
                    ->label('Cashier ID')
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->money('IDR', true)
                    ->sortable(),

                TextColumn::make('total_discount')
                    ->label('Discount')
                    ->money('IDR', true)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('payment_method')
                    ->label('Payment Method')
                    ->color(fn($state) => $state?->color())
                    ->formatStateUsing(fn($state) => $state?->label())
                    ->sortable(),

                TextColumn::make('paid_amount')
                    ->label('Paid Amount')
                    ->money('IDR', true)
                    ->sortable(),

                TextColumn::make('change_amount')
                    ->label('Change')
                    ->money('IDR', true)
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash' => 'Cash',
                        'transfer' => 'Transfer',
                        'e_wallet' => 'E-Wallet',
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
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}

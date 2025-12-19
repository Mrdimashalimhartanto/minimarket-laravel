<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Items';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('product_id')
                ->relationship('product', 'name')
                ->searchable()
                ->preload()
                ->required(),

            Forms\Components\TextInput::make('qty')
                ->numeric()
                ->required()
                ->minValue(1),

            Forms\Components\TextInput::make('price')
                ->numeric()
                ->required()
                ->minValue(0),

            Forms\Components\TextInput::make('subtotal')
                ->numeric()
                ->disabled()
                ->dehydrated(false)
                ->default(0)
                ->helperText('Subtotal = qty * price (auto).'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty')->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('subtotal')
                    ->money('IDR', true)
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}

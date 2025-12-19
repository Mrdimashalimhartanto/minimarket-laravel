<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PurchaseOrderRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders'; // <- harus sama dengan relasi di Supplier model
    protected static ?string $title = 'Purchase Orders';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Purchase Order')
                ->columns(2)
                ->schema([
                    // kalau kolom lu bukan po_number, ganti jadi `code`
                    Forms\Components\TextInput::make('po_number')
                        ->label('PO Number')
                        ->required()
                        ->maxLength(50),

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
                        ->seconds(false)
                        ->default(now()),

                    Forms\Components\Textarea::make('notes')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('po_number') // ganti jika field lu beda
            ->columns([
                // kalau field lu `code`, ganti `po_number` -> `code`
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // optional: kalau tabel purchase_orders punya kolom total
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR', true)
                    ->sortable()
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    // supplier_id otomatis keisi karena ini relation manager
                    ->mutateFormDataUsing(function (array $data): array {
                        // safety: pastikan total default kalau kolom ada
                        $data['total'] = $data['total'] ?? 0;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {

        static::saving(function (SaleItem $item) {
            $qty = (int) ($item->quantity ?? 0);
            $price = (float) ($item->unit_price ?? 0);
            $discount = (float) ($item->discount ?? 0);

            $sub = ($qty * $price) - $discount;


            $item->subtotal = max(0, $sub);
        });


        $recalculateSale = function (SaleItem $item) {
            $sale = $item->sale;
            if (!$sale)
                return;


            $itemsQuery = method_exists($sale, 'items') ? $sale->items() : $sale->saleItems();

            $subTotal = (float) $itemsQuery->sum('subtotal');
            $discount = (float) ($sale->total_discount ?? 0);
            $paid = (float) ($sale->paid_amount ?? 0);


            $totalAmount = max(0, $subTotal - $discount);


            $change = max(0, $paid - $totalAmount);

            $sale->forceFill([
                'total_amount' => $totalAmount,
                'change_amount' => $change,
            ])->saveQuietly();
        };

        static::saved($recalculateSale);
        static::deleted($recalculateSale);
    }
}

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
        static::saving(function ($item) {
            $item->subtotal = ((float) $item->qty) * ((float) $item->price);
        });

        static::saved(function ($item) {
            $sale = $item->sale;
            if ($sale) {
                $sale->total = (float) $sale->items()->sum('subtotal');
                $sale->change_amount = max(0, (float) $sale->paid_amount - (float) $sale->total);
                $sale->saveQuietly();
            }
        });

        static::deleted(function ($item) {
            $sale = $item->sale;
            if ($sale) {
                $sale->total = (float) $sale->items()->sum('subtotal');
                $sale->change_amount = max(0, (float) $sale->paid_amount - (float) $sale->total);
                $sale->saveQuietly();
            }
        });
    }

}

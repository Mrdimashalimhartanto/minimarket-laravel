<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryAdjustment extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'previous_stock',
        'adjusted_stock',
        'difference',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'previous_stock' => 'integer',
        'adjusted_stock' => 'integer',
        'difference' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

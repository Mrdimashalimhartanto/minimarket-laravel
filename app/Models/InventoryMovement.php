<?php

namespace App\Models;

use App\Enums\InventoryMovementType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'type',
        'reference_type',
        'reference_id',
        'quantity',
        'note',
        'is_void'
    ];

    protected $casts = [
        'type' => InventoryMovementType::class,
        'is_void' => 'boolean',
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

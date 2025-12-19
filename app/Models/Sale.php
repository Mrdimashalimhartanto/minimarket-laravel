<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'cashier_id',
        'total_amount',
        'total_discount',
        'payment_method',
        'paid_amount',
        'change_amount',
    ];

    protected $casts = [
        'payment_method' => PaymentMethod::class,
        'is_void' => 'boolean',
    ];

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}

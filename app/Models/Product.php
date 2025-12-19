<?php

namespace App\Models;

use App\Enums\ProductStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'sku',
        'name',
        'description',
        'cost_price',
        'selling_price',
        'stock',
        'image_path',
        'min_stock',
        'status',
    ];

    protected $casts = [
        'status' => ProductStatus::class,
    ];

    protected $appends = [
        'image_url',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function inventoryAdjustments()
    {
        return $this->hasMany(InventoryAdjustment::class);
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image_path)
            return null;

        $base = rtrim(env('MINIO_PUBLIC_URL'), '/');
        return $base . '/' . ltrim($this->image_path, '/');
    }

}

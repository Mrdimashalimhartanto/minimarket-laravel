<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductStockRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ProductStockEloquentRepository implements ProductStockRepository
{
  public function getStock(int $productId): int
  {
    $product = Product::query()->find($productId);

    if (!$product) {
      throw new ModelNotFoundException("Product not found: {$productId}");
    }

    return (int) $product->stock;
  }

  public function setStock(int $productId, int $newStock): void
  {
    $product = Product::query()->find($productId);

    if (!$product) {
      throw new ModelNotFoundException("Product not found: {$productId}");
    }

    $product->stock = $newStock;
    $product->save();
  }
}

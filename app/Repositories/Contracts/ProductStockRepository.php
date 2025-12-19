<?php

namespace App\Repositories\Contracts;

interface ProductStockRepository
{
  public function getStock(int $productId): int;

  public function setStock(int $productId, int $newStock): void;
}

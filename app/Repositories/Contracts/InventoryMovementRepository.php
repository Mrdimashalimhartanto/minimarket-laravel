<?php

namespace App\Repositories\Contracts;

interface InventoryMovementRepository
{
  public function create(array $data): void;
}

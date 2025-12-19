<?php

namespace App\Repositories\Eloquent;

use App\Models\InventoryMovement;
use App\Repositories\Contracts\InventoryMovementRepository;

class InventoryMovementEloquentRepository implements InventoryMovementRepository
{
  public function create(array $data): void
  {
    InventoryMovement::query()->create($data);
  }
}

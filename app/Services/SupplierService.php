<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplierService
{
  public function list(array $filters = []): LengthAwarePaginator
  {
    $query = Supplier::query();

    if (!empty($filters['search'])) {
      $search = $filters['search'];
      $query->where('name', 'like', "%{$search}%");
    }

    if (isset($filters['is_active'])) {
      $query->where('is_active', (bool) $filters['is_active']);
    }

    return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
  }

  public function create(array $data): Supplier
  {
    return Supplier::create($data);
  }

  public function update(Supplier $supplier, array $data): Supplier
  {
    $supplier->fill($data);
    $supplier->save();

    return $supplier;
  }

  public function delete(Supplier $supplier): void
  {
    $supplier->delete();
  }
}

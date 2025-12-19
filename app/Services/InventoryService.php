<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Repositories\Contracts\InventoryMovementRepository;
use App\Repositories\Contracts\ProductStockRepository;
use App\Support\EnumHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
  public function __construct(
    private readonly ProductStockRepository $products,
    private readonly InventoryMovementRepository $movements
  ) {
  }
  public function stockList(array $filters = []): LengthAwarePaginator
  {
    $query = Product::query()->with('category');

    if (!empty($filters['search'])) {
      $search = $filters['search'];
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('sku', 'like', "%{$search}%");
      });
    }

    return $query->orderBy('name')->paginate($filters['per_page'] ?? 15);
  }

  /**
   * Detail stok 1 produk.
   */
  public function stockDetail(int $id): Product
  {
    return Product::with('category')->findOrFail($id);
  }

  /**
   * List pergerakan stok.
   */
  public function movementsList(array $filters = []): LengthAwarePaginator
  {
    $query = InventoryMovement::query()->with('product');

    if (!empty($filters['product_id'])) {
      $query->where('product_id', $filters['product_id']);
    }

    if (!empty($filters['type'])) {
      // pastikan nilainya valid enum
      if (in_array($filters['type'], EnumHelper::values(InventoryMovementType::class), true)) {
        $query->where('type', $filters['type']);
      }
    }

    return $query->orderByDesc('id')->paginate($filters['per_page'] ?? 20);
  }

  /**
   * Detail 1 movement.
   */
  public function movementDetail(InventoryMovement $movement): InventoryMovement
  {
    return $movement->load('product');
  }

  /**
   * Update catatan movement (tidak mengubah stok).
   */
  public function updateMovement(InventoryMovement $movement, array $data): InventoryMovement
  {
    $movement->update([
      'note' => $data['note'],
    ]);

    return $movement->fresh('product');
  }

  /**
   * Void movement → stok dikembalikan seperti sebelum movement ini terjadi.
   */
  public function voidMovement(InventoryMovement $movement, ?string $reason = null): InventoryMovement
  {
    if ($movement->is_void) {
      throw new \DomainException('Movement sudah di-void.');
    }

    return DB::transaction(function () use ($movement, $reason) {
      // lock product dulu
      $product = Product::lockForUpdate()->findOrFail($movement->product_id);

      // Revert stok:
      // kalau movement.quantity = +10 (IN)  → stok sekarang dikurangi 10
      // kalau movement.quantity = -5  (OUT) → stok sekarang dikurangi -5 (alias +5)
      $product->update([
        'stock' => $product->stock - $movement->quantity,
      ]);

      $note = $movement->note ?? '';
      if ($reason) {
        $note = trim($note . ' (void: ' . $reason . ')');
      }

      $movement->update([
        'is_void' => true,
        'note' => $note,
      ]);

      return $movement->fresh('product');
    });
  }

  /**
   * Manual adjust stok (stock opname).
   */
  public function adjustStock(int $productId, int $qty, string $type, string $reason, ?string $note = null)
  {
    if ($qty <= 0) {
      throw new InvalidArgumentException("qty must be greater than 0");
    }

    $type = strtolower(trim($type));

    $movementType = match ($type) {
      'increase', 'in' => 'IN',
      'decrease', 'out' => 'OUT',
      'adjust' => 'ADJUST',
      default => throw new InvalidArgumentException("invalid type"),
    };

    $signedQty = $movementType === 'OUT' ? -abs($qty) : abs($qty);

    return DB::transaction(function () use ($productId, $movementType, $signedQty, $reason, $note) {
      $movement = InventoryMovement::create([
        'product_id' => $productId,
        'type' => $movementType,
        'reference_type' => 'adjustment',
        'reference_id' => null,
        'quantity' => $signedQty,
        'note' => $note ?: $reason,
      ]);

      $product = Product::query()
        ->whereKey($productId)
        ->lockForUpdate()
        ->firstOrFail();

      $newStock = $product->stock + $signedQty;

      if ($newStock < 0) {
        throw new InvalidArgumentException("stock cannot be negative");
      }

      $product->update(['stock' => $newStock]);

      return $movement;
    });
  }

}

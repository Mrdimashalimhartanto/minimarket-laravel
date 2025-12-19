<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class InventoryRepository
{
  /**
   * List stock (frequently accessed) - cocok untuk cache
   * Support filter: search, low_stock, sort, per_page
   */
  public function stockIndex(array $filters = [])
  {
    $search = $filters['search'] ?? null;
    $lowStock = filter_var($filters['low_stock'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $sort = $filters['sort'] ?? 'desc'; // asc/desc
    $perPage = (int) ($filters['per_page'] ?? 15);

    // ✅ Sesuaikan table/kolom jika berbeda
    $q = DB::table('products as p')
      ->leftJoin('inventories as inv', 'inv.product_id', '=', 'p.id')
      ->select([
        'p.id',
        'p.sku',
        'p.name',
        'p.price',
        DB::raw('COALESCE(inv.stock, 0) as stock'),
        'p.updated_at',
      ]);

    if ($search) {
      $q->where(function ($w) use ($search) {
        $w->where('p.name', 'like', "%{$search}%")
          ->orWhere('p.sku', 'like', "%{$search}%");
      });
    }

    if ($lowStock) {
      // contoh threshold default 5
      $threshold = (int) ($filters['threshold'] ?? 5);
      $q->whereRaw('COALESCE(inv.stock,0) <= ?', [$threshold]);
    }

    $q->orderBy('stock', $sort);

    return $q->paginate($perPage);
  }

  public function stockShow(int $id)
  {
    return DB::table('products as p')
      ->leftJoin('inventories as inv', 'inv.product_id', '=', 'p.id')
      ->select([
        'p.id',
        'p.sku',
        'p.name',
        'p.price',
        DB::raw('COALESCE(inv.stock, 0) as stock'),
        'p.updated_at',
      ])
      ->where('p.id', $id)
      ->first();
  }

  /**
   * Movements list - cocok untuk cache
   */
  public function movementsIndex(array $filters = [])
  {
    $perPage = (int) ($filters['per_page'] ?? 15);

    // ✅ Sesuaikan table/kolom jika berbeda
    return DB::table('inventory_movements as m')
      ->leftJoin('products as p', 'p.id', '=', 'm.product_id')
      ->select([
        'm.id',
        'm.product_id',
        'p.name as product_name',
        'm.type',       // in/out/adjust/void
        'm.qty',
        'm.note',
        'm.created_at',
      ])
      ->orderByDesc('m.id')
      ->paginate($perPage);
  }

  public function movementShow(int $movementId)
  {
    return DB::table('inventory_movements as m')
      ->leftJoin('products as p', 'p.id', '=', 'm.product_id')
      ->select([
        'm.id',
        'm.product_id',
        'p.name as product_name',
        'm.type',
        'm.qty',
        'm.note',
        'm.created_at',
      ])
      ->where('m.id', $movementId)
      ->first();
  }

  /**
   * Adjust stock - write endpoint (nanti invalidate cache di service)
   */
  public function adjustStock(array $payload)
  {
    // payload minimal: product_id, qty, type(in/out/adjust), note(optional)
    $productId = (int) ($payload['product_id'] ?? 0);
    $qty = (int) ($payload['qty'] ?? 0);
    $type = $payload['type'] ?? 'adjust';
    $note = $payload['note'] ?? null;

    // ✅ Pastikan inventory row ada
    DB::table('inventories')->updateOrInsert(
      ['product_id' => $productId],
      ['stock' => DB::raw('stock'), 'updated_at' => now()]
    );

    // ✅ Update stock
    if ($type === 'in') {
      DB::table('inventories')->where('product_id', $productId)->increment('stock', $qty);
    } elseif ($type === 'out') {
      DB::table('inventories')->where('product_id', $productId)->decrement('stock', $qty);
    } else {
      // adjust set absolute (kalau lo mau set value)
      // kalau adjust ingin +/-, ganti sesuai kebutuhan
      DB::table('inventories')->where('product_id', $productId)->update([
        'stock' => $qty,
        'updated_at' => now(),
      ]);
    }

    // ✅ Insert movement
    $movementId = DB::table('inventory_movements')->insertGetId([
      'product_id' => $productId,
      'type' => $type,
      'qty' => $qty,
      'note' => $note,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return $this->movementShow($movementId);
  }

  public function movementVoid(int $movementId)
  {
    // contoh: set status void (kalau ada kolom status)
    return DB::table('inventory_movements')
      ->where('id', $movementId)
      ->update([
        'type' => 'void',
        'updated_at' => now(),
      ]);
  }
}

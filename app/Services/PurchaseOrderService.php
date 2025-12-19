<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
  public function list(array $filters = []): LengthAwarePaginator
  {
    $query = PurchaseOrder::query()
      ->with(['supplier', 'items.product']);

    if (!empty($filters['status'])) {
      $query->where('status', $filters['status']);
    }

    if (!empty($filters['supplier_id'])) {
      $query->where('supplier_id', $filters['supplier_id']);
    }

    return $query->orderByDesc('id')->paginate($filters['per_page'] ?? 15);
  }

  public function create(array $data): PurchaseOrder
  {
    return DB::transaction(function () use ($data) {
      $user = Auth::user();

      $po = PurchaseOrder::create([
        'supplier_id' => $data['supplier_id'],
        'code' => $this->generateCode(),
        'status' => 'draft',
        'total_amount' => 0,
        'created_by' => $user->id,
      ]);

      $total = 0;

      foreach ($data['items'] as $item) {
        $subtotal = $item['unit_cost'] * $item['quantity_ordered'];

        $poItem = PurchaseOrderItem::create([
          'purchase_order_id' => $po->id,
          'product_id' => $item['product_id'],
          'quantity_ordered' => $item['quantity_ordered'],
          'quantity_received' => 0,
          'unit_cost' => $item['unit_cost'],
          'subtotal' => $subtotal,
        ]);

        $total += $subtotal;
      }

      $po->update(['total_amount' => $total]);

      return $po->load(['supplier', 'items.product']);
    });
  }

  public function markOrdered(PurchaseOrder $po): PurchaseOrder
  {
    if ($po->status !== 'draft') {
      throw ValidationException::withMessages([
        'status' => ['Only draft PO can be marked as ordered.'],
      ]);
    }

    $po->update([
      'status' => 'ordered',
      'ordered_at' => now(),
    ]);

    return $po;
  }

  public function receive(PurchaseOrder $po, array $data): PurchaseOrder
  {
    if (!in_array($po->status, ['draft', 'ordered'])) {
      throw ValidationException::withMessages([
        'status' => ['Only draft or ordered PO can be received.'],
      ]);
    }

    return DB::transaction(function () use ($po, $data) {
      foreach ($data['items'] as $itemData) {
        /** @var PurchaseOrderItem $item */
        $item = $po->items()->where('id', $itemData['id'])->firstOrFail();

        $qtyReceived = (int) $itemData['quantity_received'];
        $delta = $qtyReceived - $item->quantity_received;

        $item->update([
          'quantity_received' => $qtyReceived,
        ]);

        if ($delta > 0) {
          // update stock product
          /** @var Product $product */
          $product = $item->product;
          $product->increment('stock', $delta);

          // log inventory movement
          InventoryMovement::create([
            'product_id' => $product->id,
            'type' => InventoryMovementType::IN,
            'reference_type' => 'purchase',
            'reference_id' => $po->id,
            'quantity' => $delta,
            'note' => 'Receive PO ' . $po->code,
          ]);
        }
      }

      $po->update([
        'status' => 'received',
        'received_at' => now(),
      ]);

      return $po->load(['supplier', 'items.product']);
    });
  }

  public function cancel(PurchaseOrder $po): PurchaseOrder
  {
    if ($po->status === 'received') {
      throw ValidationException::withMessages([
        'status' => ['Received PO cannot be cancelled.'],
      ]);
    }

    $po->update(['status' => 'cancelled']);

    return $po;
  }

  protected function generateCode(): string
  {
    return 'PO-' . now()->format('YmdHis');
  }

  public function delete(int $id): bool
  {
    /** @var PurchaseOrder $po */
    $po = PurchaseOrder::with('items')->findOrFail($id);

    if ($po->status !== 'draft') {
      throw new DomainException('Hanya PO dengan status draft yang boleh dihapus.');
    }

    // Hapus semua item
    $po->items()->delete();

    // Hapus purchase order
    return $po->delete();
  }

  public function update(int $id, array $data): PurchaseOrder
  {
    return DB::transaction(function () use ($id, $data) {
      // Lock row supaya aman kalau ada proses paralel
      $po = PurchaseOrder::lockForUpdate()->findOrFail($id);

      // Boleh diubah hanya kalau belum diterima / belum cancel
      if (in_array($po->status, ['received', 'cancelled'])) {
        throw new DomainException(
          'Purchase order tidak bisa diubah karena statusnya sudah ' . $po->status . '.'
        );
      }

      // Update header PO
      $po->supplier_id = $data['supplier_id'];
      $po->note = $data['note'] ?? $po->note;

      // --- Update items ---
      // Cara paling simple: hapus item lama, insert ulang item baru
      // (kalau mau lebih advanced bisa pakai sync per-item_id)
      $po->items()->delete();

      $totalAmount = 0;

      foreach ($data['items'] as $itemData) {
        $qty = (int) $itemData['quantity_ordered'];
        $unitCost = (int) $itemData['unit_cost'];
        $subtotal = $qty * $unitCost;

        $item = new PurchaseOrderItem([
          'product_id' => $itemData['product_id'],
          'quantity_ordered' => $qty,
          // kalau tabelmu punya kolom quantity_received, bisa default 0
          // 'quantity_received' => 0,
          'unit_cost' => $unitCost,
          'subtotal' => $subtotal,
        ]);

        $po->items()->save($item);
        $totalAmount += $subtotal;
      }

      // Update total_amount
      $po->total_amount = $totalAmount;
      $po->save();

      // Load relasi buat response yang rapi
      $po->load(['supplier', 'items.product']);

      return $po;
    });
  }


}

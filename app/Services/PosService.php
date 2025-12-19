<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Enums\PaymentMethod;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
  /**
   * List sales dengan filter optional.
   * Dipakai untuk GET /pos/sales
   */
  public function list(array $filters = []): LengthAwarePaginator
  {
    $query = Sale::query()->with(['items.product', 'cashier']);

    if (!empty($filters['cashier_id'])) {
      $query->where('cashier_id', $filters['cashier_id']);
    }

    if (!empty($filters['date_from'])) {
      $query->whereDate('created_at', '>=', $filters['date_from']);
    }

    if (!empty($filters['date_to'])) {
      $query->whereDate('created_at', '<=', $filters['date_to']);
    }

    return $query->orderByDesc('id')->paginate($filters['per_page'] ?? 15);
  }

  /**
   * Alias kalau di controller kamu pakai listSales()
   */
  public function listSales(array $filters = []): LengthAwarePaginator
  {
    return $this->list($filters);
  }

  /**
   * CREATE SALE
   * Dipakai untuk POST /pos/sales
   */
  public function createSale(array $data): Sale
  {
    return DB::transaction(function () use ($data) {
      $user = Auth::user();

      $totalAmount = 0;
      $totalDiscount = 0;

      $sale = Sale::create([
        'code' => $this->generateCode(),
        'cashier_id' => $user->id,
        'total_amount' => 0,
        'total_discount' => 0,
        'payment_method' => PaymentMethod::from($data['payment_method']),
        'paid_amount' => 0,
        'change_amount' => 0,
        'note' => $data['note'] ?? null,
      ]);

      foreach ($data['items'] as $itemData) {
        /** @var Product $product */
        $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

        $qty = (int) $itemData['quantity'];

        if ($product->stock < $qty) {
          throw ValidationException::withMessages([
            'items' => ["Insufficient stock for product {$product->name}"],
          ]);
        }

        $unitPrice = $itemData['unit_price'] ?? $product->selling_price;
        $discount = 0; // kalau nanti mau ada diskon per item, tinggal ubah di sini
        $subtotal = ($unitPrice - $discount) * $qty;

        SaleItem::create([
          'sale_id' => $sale->id,
          'product_id' => $product->id,
          'quantity' => $qty,
          'unit_price' => $unitPrice,
          'discount' => $discount,
          'subtotal' => $subtotal,
        ]);

        // kurangi stok product
        $product->decrement('stock', $qty);

        // log inventory movement (OUT)
        InventoryMovement::create([
          'product_id' => $product->id,
          'type' => InventoryMovementType::OUT,
          'reference_type' => 'sale',
          'reference_id' => $sale->id,
          'quantity' => -$qty, // pakai minus sesuai migration kamu
          'note' => 'POS sale ' . $sale->code,
        ]);

        $totalAmount += $subtotal;
        $totalDiscount += $discount * $qty;
      }

      $paidAmount = $data['paid_amount'];
      $changeAmount = max(0, $paidAmount - $totalAmount);

      $sale->update([
        'total_amount' => $totalAmount,
        'total_discount' => $totalDiscount,
        'paid_amount' => $paidAmount,
        'change_amount' => $changeAmount,
      ]);

      return $sale->load(['items.product', 'cashier']);
    });
  }

  /**
   * DETAIL SALE
   * Dipakai untuk GET /pos/sales/{sale}
   */
  public function getSaleDetail(Sale $sale): Sale
  {
    return $sale->load(['items.product', 'cashier']);
  }

  /**
   * UPDATE SALE
   * Dipakai untuk PUT /pos/sales/{sale}
   *
   * Flow:
   * 1. Revert stok dari item lama (stok produk + movement IN).
   * 2. Hapus item lama.
   * 3. Insert item baru, kurangi stok lagi (movement OUT).
   * 4. Rehitung total_amount, total_discount, change_amount.
   */
  public function updateSale(Sale $sale, array $data): Sale
  {
    return DB::transaction(function () use ($sale, $data) {

      // 1. Revert stok dari item lama
      $oldItems = $sale->items()->with('product')->get();

      foreach ($oldItems as $item) {
        // balikin stok lama
        $item->product->increment('stock', $item->quantity);

        // movement IN untuk revert
        InventoryMovement::create([
          'product_id' => $item->product_id,
          'type' => 'IN',
          'reference_type' => 'sale_update_revert',
          'reference_id' => $sale->id,
          'quantity' => $item->quantity, // boleh positif untuk IN
          'note' => 'Revert old sale items #' . $sale->code,
        ]);
      }

      // hapus item lama
      $sale->items()->delete();

      // 2. Insert item baru
      $totalAmount = 0;
      $totalDiscount = 0;

      foreach ($data['items'] as $itemData) {
        /** @var Product $product */
        $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

        $qty = (int) $itemData['quantity'];

        if ($product->stock < $qty) {
          throw ValidationException::withMessages([
            'items' => ["Insufficient stock for product {$product->name} (update)."],
          ]);
        }

        $unitPrice = $itemData['unit_price'] ?? $product->selling_price;
        $discount = 0;
        $subtotal = ($unitPrice - $discount) * $qty;

        SaleItem::create([
          'sale_id' => $sale->id,
          'product_id' => $product->id,
          'quantity' => $qty,
          'unit_price' => $unitPrice,
          'discount' => $discount,
          'subtotal' => $subtotal,
        ]);

        // kurangi stok untuk item baru
        $product->decrement('stock', $qty);

        // movement OUT untuk update
        InventoryMovement::create([
          'product_id' => $product->id,
          'type' => 'OUT',
          'reference_type' => 'sale_update',
          'reference_id' => $sale->id,
          'quantity' => -$qty,
          'note' => 'Update sale #' . $sale->code,
        ]);

        $totalAmount += $subtotal;
        $totalDiscount += $discount * $qty;
      }

      // 3. Hitung ulang change_amount pakai paid_amount lama
      $paidAmount = $sale->paid_amount;
      $changeAmount = max(0, $paidAmount - $totalAmount);

      // 4. Update header sale
      $sale->update([
        'note' => $data['note'] ?? $sale->note,
        'total_amount' => $totalAmount,
        'total_discount' => $totalDiscount,
        'change_amount' => $changeAmount,
      ]);

      return $sale->load(['items.product', 'cashier']);
    });
  }


  public function deleteSale(Sale $sale): void
  {
    DB::transaction(function () use ($sale) {
      $items = $sale->items()->with('product')->get();

      foreach ($items as $item) {
        // balikin stok
        $item->product->increment('stock', $item->quantity);

        // movement IN karena void sale
        InventoryMovement::create([
          'product_id' => $item->product_id,
          'type' => 'IN',
          'reference_type' => 'sale_void',
          'reference_id' => $sale->id,
          'quantity' => $item->quantity,
          'note' => 'Void sale #' . $sale->code,
        ]);
      }
      $sale->delete();
    });
  }

  protected function generateCode(): string
  {
    return 'POS-' . now()->format('YmdHis');
  }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AdjustStockRequest;
use App\Http\Requests\Inventory\MovementFilterRequest;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\InventoryService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $service
    ) {
    }

    /**
     * GET /api/v1/inventory/stock
     * List stok semua produk (bisa pakai search & per_page).
     */
    public function stockIndex(Request $request)
    {
        $filters = $request->only([
            'search',
            'product_id',
            'per_page',
        ]);

        $stock = $this->service->stockList(filters: $filters);

        return ApiResponse::success(
            message: 'Stock list',
            data: $stock,
        );
    }

    /**
     * GET /api/v1/inventory/stock/{id}
     * Detail stok 1 produk berdasarkan ID.
     */
    public function stockShow(int $id)
    {
        $product = $this->service->stockDetail($id);

        return ApiResponse::success(
            message: 'Stock detail',
            data: $product,
        );
    }

    /**
     * GET /api/v1/inventory/movements
     * List pergerakan stok (paging + filter).
     */
    public function movementsIndex(MovementFilterRequest $request)
    {
        $movements = $this->service->movementsList($request->validated());

        return ApiResponse::success(
            message: 'Inventory movements',
            data: $movements,
        );
    }


    /**
     * GET /api/v1/inventory/movements/{movement}
     * Detail 1 movement (pakai route model binding).
     */
    public function movementShow(InventoryMovement $movement)
    {
        $movement = $this->service->movementDetail($movement);

        return ApiResponse::success(
            message: 'Inventory movement detail',
            data: $movement,
        );
    }

    /**
     * PUT /api/v1/inventory/movements/{movement}
     * Update catatan (note) movement.
     * Ini tidak mengubah stok, hanya catatan.
     */
    public function movementUpdate(Request $request, InventoryMovement $movement)
    {
        $data = $request->validate([
            'note' => ['required', 'string', 'max:255'],
        ]);

        $movement = $this->service->updateMovement($movement, $data);

        return ApiResponse::success(
            message: 'Inventory movement updated',
            data: $movement,
        );
    }

    /**
     * POST /api/v1/inventory/movements/{movement}/void
     * Void / batalkan movement → stok dikembalikan seperti sebelum movement itu terjadi.
     */
    public function movementVoid(Request $request, InventoryMovement $movement)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $movement = $this->service->voidMovement(
                movement: $movement,
                reason: $data['reason'] ?? null,
            );

            return ApiResponse::success(
                message: 'Inventory movement voided',
                data: $movement,
            );
        } catch (\DomainException $e) {
            // misal sudah pernah di-void
            return ApiResponse::error(
                message: $e->getMessage(),
                errors: 422
            );
        }
    }

    /**
     * POST /api/v1/inventory/adjustments
     * Manual adjustment stok (stock opname / koreksi).
     */


    public function adjustStock(AdjustStockRequest $request)
    {
        $data = $request->validated();

        $productId = (int) $data['product_id'];
        $reason = (string) ($data['reason'] ?? 'Stock adjustment');
        $note = $data['note'] ?? null;

        // Payload format Postman:
        // adjustment_type: decrease/increase/adjust
        // adjusted_stock: final stock value
        if (isset($data['adjustment_type']) && array_key_exists('adjusted_stock', $data)) {

            $product = Product::query()->findOrFail($productId);

            $currentStock = (int) $product->stock;
            $adjustedStock = (int) $data['adjusted_stock'];

            // hitung selisih
            $diff = $adjustedStock - $currentStock;

            // jika tidak berubah, bisa dianggap error atau tetap catat movement ADJUST (qty=0)
            if ($diff === 0) {
                return ApiResponse::error(
                    message: 'Stock is unchanged',
                    errors: ['adjusted_stock' => ['Adjusted stock must be different from current stock.']],
                    status: 422
                );
            }

            $qty = abs($diff);
            $type = $diff > 0
                ? InventoryMovementType::IN->value
                : InventoryMovementType::OUT->value;

            $this->service->adjustStock(
                productId: $productId,
                qty: $qty,
                type: $type,
                reason: $reason,
                note: $note
            );

            return ApiResponse::created(
                message: 'Stock adjusted',
                data: [
                    'product_id' => $productId,
                    'from_stock' => $currentStock,
                    'to_stock' => $adjustedStock,
                    'qty' => $qty,
                    'type' => $type,
                    'reason' => $reason,
                    'note' => $note,
                ]
            );
        }

        // Fallback kalau suatu saat lu kirim format qty/type
        $qty = (int) ($data['qty'] ?? 0);
        $type = (string) ($data['type'] ?? '');

        if ($qty <= 0 || $type === '') {
            return ApiResponse::error(
                message: 'Invalid payload',
                errors: ['payload' => ['Send adjustment_type+adjusted_stock OR qty+type']],
                status: 422
            );
        }

        $this->service->adjustStock(
            productId: $productId,
            qty: $qty,
            type: $type,
            reason: $reason,
            note: $note
        );

        return ApiResponse::created(message: 'Stock adjusted', data: null);
    }


}

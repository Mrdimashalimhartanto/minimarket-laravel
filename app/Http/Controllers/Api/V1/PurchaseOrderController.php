<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\ReceivePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    public function __construct(
        protected PurchaseOrderService $service
    ) {
    }

    public function index(Request $request)
    {
        $pos = $this->service->list($request->only('status', 'supplier_id', 'per_page'));

        return ApiResponse::success('Purchase order list', $pos);
    }

    public function store(StorePurchaseOrderRequest $request)
    {
        $po = $this->service->create($request->validated());

        return ApiResponse::created('Purchase order created', $po);
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['supplier', 'items.product']);

        return ApiResponse::success('Purchase order detail', $purchaseOrder);
    }

    public function markOrdered(PurchaseOrder $purchaseOrder)
    {
        $po = $this->service->markOrdered($purchaseOrder);

        return ApiResponse::success('Purchase order marked as ordered', $po);
    }

    public function receive(ReceivePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder)
    {
        $po = $this->service->receive($purchaseOrder, $request->validated());

        return ApiResponse::success('Purchase order received', $po);
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $po = $this->service->cancel($purchaseOrder);

        return ApiResponse::success('Purchase order cancelled', $po);
    }

    public function destroy(int $purchaseOrder)
    {
        try {
            $this->service->delete($purchaseOrder);

            return ApiResponse::success('Purchase order deleted successfully');
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function update(UpdatePurchaseOrderRequest $request, int $purchaseOrder)
    {
        try {
            $po = $this->service->update($purchaseOrder, $request->validated());

            return ApiResponse::success(
                message: 'Purchase order updated',
                data: $po
            );
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }


}

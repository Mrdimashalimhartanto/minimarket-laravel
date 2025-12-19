<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pos\StoreSaleRequest;
use App\Http\Requests\Pos\UpdateSaleRequest;
use App\Models\Sale;
use App\Services\PosService;
use App\Support\ApiResponse;
use DomainException;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function __construct(
        protected PosService $service
    ) {
    }

    public function index(Request $request)
    {
        $sales = $this->service->list($request->only('cashier_id', 'date_from', 'date_to', 'per_page'));

        return ApiResponse::success('Sales list', $sales);
    }

    public function store(StoreSaleRequest $request)
    {
        $sale = $this->service->createSale($request->validated());

        return ApiResponse::created('Sale created', $sale);
    }

    public function show(Sale $sale)
    {
        $detail = $this->service->getSaleDetail($sale);

        return ApiResponse::success('Sale detail', $detail);
    }

    public function update(UpdateSaleRequest $request, Sale $sale)
    {
        try {
            $sale = $this->service->updateSale($sale, $request->validated());

            return ApiResponse::success('Sale updated', $sale);
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    public function destroy(Sale $sale)
    {
        try {
            $this->service->deleteSale($sale);

            return ApiResponse::success('Sale deleted/voided', null);
        } catch (DomainException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }
}

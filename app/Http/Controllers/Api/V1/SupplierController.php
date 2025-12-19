<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function __construct(
        protected SupplierService $service
    ) {
    }

    public function index(Request $request)
    {
        $suppliers = $this->service->list($request->only('search', 'is_active', 'per_page'));

        return ApiResponse::success('Supplier list', $suppliers);
    }

    public function store(StoreSupplierRequest $request)
    {
        $supplier = $this->service->create($request->validated());

        return ApiResponse::created('Supplier created', $supplier);
    }

    public function show(Supplier $supplier)
    {
        return ApiResponse::success('Supplier detail', $supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $supplier = $this->service->update($supplier, $request->validated());

        return ApiResponse::success('Supplier updated', $supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $this->service->delete($supplier);

        return ApiResponse::success('Supplier deleted');
    }
}

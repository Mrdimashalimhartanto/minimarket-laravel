<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductService $service
    ) {
    }

    public function index(Request $request)
    {
        $products = $this->service->list(
            $request->only('search', 'status', 'category_id', 'per_page')
        );

        return ApiResponse::success('Product list', $products);
    }

    public function store(StoreProductRequest $request)
    {
        $product = $this->service->create(
            $request->validated(),
            $request->file('image'),
        );

        return ApiResponse::created('Product created', $product);
    }


    public function show(Product $product)
    {
        $product->load('category');

        return ApiResponse::success('Product detail', $product);
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        $product = $this->service->update(
            $product,
            $request->validated(),
            $request->file('image'),
        );

        return ApiResponse::success('Product updated', $product);
    }

    public function destroy(Product $product)
    {
        $this->service->delete($product);

        return ApiResponse::success('Product deleted');
    }
}

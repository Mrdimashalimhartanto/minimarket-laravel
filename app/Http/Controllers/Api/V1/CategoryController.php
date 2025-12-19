<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
     public function __construct(
        protected CategoryService $categoryService
    ) {
    }

    // GET /api/v1/categories
    public function index()
    {
        $categories = $this->categoryService->getAll();

        return ApiResponse::success(
            message: 'Category list',
            data: $categories
        );
    }

    // POST /api/v1/categories
    public function store(StoreCategoryRequest $request)
    {
        $data = $request->validated();

        $category = $this->categoryService->create($data);

        return ApiResponse::success(
            message: 'Category created',
            data: $category,
            // statusCode: 201
        );
    }

    // GET /api/v1/categories/{category}
    public function show(Category $category)
    {
        return ApiResponse::success(
            message: 'Category detail',
            data: $category
        );
    }

    // PUT/PATCH /api/v1/categories/{category}
    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $data = $request->validated();

        $category = $this->categoryService->update($category, $data);

        return ApiResponse::success(
            message: 'Category updated',
            data: $category
        );
    }

    // DELETE /api/v1/categories/{category}
    public function destroy(Category $category)
    {
        $this->categoryService->delete($category);

        return ApiResponse::success(
            message: 'Category deleted'
        );
    }
}

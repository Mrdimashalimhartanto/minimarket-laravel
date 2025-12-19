<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

class ProductService
{
  public function __construct(
    protected ImageStorageService $imageStorage,
  ) {
  }

  public function list(array $filters = []): LengthAwarePaginator
  {
    $query = Product::query()->with('category');

    if (!empty($filters['search'])) {
      $search = $filters['search'];
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('sku', 'like', "%{$search}%");
      });
    }

    if (!empty($filters['status'])) {
      $query->where('status', $filters['status']);
    }

    if (!empty($filters['category_id'])) {
      $query->where('category_id', $filters['category_id']);
    }

    return $query->orderByDesc('id')->paginate($filters['per_page'] ?? 15);
  }

  public function create(array $data, ?UploadedFile $imageFile = null): Product
  {
    if ($imageFile) {
      // upload image ke MinIO & simpan path
      $data['image_path'] = $this->imageStorage->uploadProductImage($imageFile);
    }

    $product = Product::create($data);

    return $product->load('category');
  }

  public function update(Product $product, array $data, ?UploadedFile $imageFile = null): Product
  {
    if ($imageFile) {
      // upload baru + hapus lama
      $data['image_path'] = $this->imageStorage->uploadProductImage(
        $imageFile,
        $product->image_path,   // oldPath
      );
    }

    $product->update($data);

    return $product->refresh()->load('category');
  }


  public function delete(Product $product): void
  {
    $this->imageStorage->delete($product->image_path);
    $product->delete();
  }
}

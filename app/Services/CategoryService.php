<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAll(): Collection
    {
        return Category::query()
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Category
    {
        return Category::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active'   => $data['is_active'],
        ]);
    }

    public function update(Category $category, array $data): Category
    {
        $category->fill([
            'name'        => $data['name']        ?? $category->name,
            'description' => $data['description'] ?? $category->description,
            'is_active'   => $data['is_active']   ?? $category->is_active,
        ]);

        $category->save();

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}

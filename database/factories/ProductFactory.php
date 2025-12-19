<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(), // ✅ wajib
            'sku' => $this->faker->unique()->bothify('SKU-#####'), // ✅ wajib & unique
            'name' => $this->faker->words(2, true), // ✅ wajib

            'image_path' => null,
            'description' => $this->faker->optional()->sentence(),

            'cost_price' => 10000.00,
            'selling_price' => 15000.00,

            'stock' => 10,
            'min_stock' => 0,

            'status' => 'active', // enum: active/inactive? sesuaikan kalau ada value lain
        ];
    }
}

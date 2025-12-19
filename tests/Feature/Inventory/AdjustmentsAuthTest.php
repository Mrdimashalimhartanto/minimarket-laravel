<?php

namespace Tests\Feature\Inventory;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;

class AdjustmentsAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function request_without_bearer_token_should_return_401(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $res = $this->postJson('/api/v1/inventory/adjustments', [
            'product_id' => $product->id,
            'adjustment_type' => 'decrease',
            'adjusted_stock' => 9,
            'reason' => 'Barang rusak',
        ]);

        $res->assertStatus(401);
    }

    #[Test]
    public function request_with_valid_sanctum_user_should_success(): void
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Test Category',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product = Product::factory()->create([
            'category_id' => $categoryId,
            'stock' => 10,
        ]);

        $res = $this->postJson('/api/v1/inventory/adjustments', [
            'product_id' => $product->id,
            'adjustment_type' => 'decrease',
            'adjusted_stock' => 9,
            'reason' => 'Barang rusak',
        ]);

        $res->assertStatus(201)->assertJson(['success' => true]);
    }

    #[Test]
    public function request_with_invalid_token_should_return_401(): void
    {
        $product = Product::factory()->create(['stock' => 10]);

        $res = $this->withHeaders([
            'Authorization' => 'Bearer invalid_token',
            'Accept' => 'application/json',
        ])->postJson('/api/v1/inventory/adjustments', [
                    'product_id' => $product->id,
                    'adjustment_type' => 'decrease',
                    'adjusted_stock' => 9,
                    'reason' => 'Barang rusak',
                ]);

        $res->assertStatus(401);
    }
}

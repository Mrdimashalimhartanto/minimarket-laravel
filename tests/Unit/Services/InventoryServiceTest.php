<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use InvalidArgumentException;
use App\Services\InventoryService;
use App\Repositories\Contracts\ProductStockRepository;
use App\Repositories\Contracts\InventoryMovementRepository;

class InventoryServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_increases_stock_and_creates_movement_when_type_is_in()
    {
        $products = Mockery::mock(ProductStockRepository::class);
        $movements = Mockery::mock(InventoryMovementRepository::class);

        $service = new InventoryService($products, $movements);

        $productId = 10;

        $products->shouldReceive('getStock')
            ->once()
            ->with($productId)
            ->andReturn(10);

        $products->shouldReceive('setStock')
            ->once()
            ->with($productId, 15);

        $movements->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function ($payload) use ($productId) {
                return $payload['product_id'] === $productId
                    && $payload['type'] === 'in'
                    && $payload['qty'] === 5
                    && $payload['reason'] === 'restock'
                    && $payload['note'] === 'unit test';
            }));

        $service->adjustStock($productId, 5, 'in', 'restock', 'unit test');

        $this->assertTrue(true); // jika semua expectation terpenuhi = lulus
    }

    /** @test */
    public function it_throws_exception_when_decrease_would_make_stock_negative()
    {
        $products = Mockery::mock(ProductStockRepository::class);
        $movements = Mockery::mock(InventoryMovementRepository::class);

        $service = new InventoryService($products, $movements);

        $productId = 10;

        $products->shouldReceive('getStock')
            ->once()
            ->with($productId)
            ->andReturn(2);

        // setStock & create tidak boleh dipanggil
        $products->shouldNotReceive('setStock');
        $movements->shouldNotReceive('create');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('stock cannot be negative');

        $service->adjustStock($productId, 5, 'out', 'sale', 'unit test');
    }

    /** @test */
    public function it_throws_exception_when_qty_is_zero_or_less()
    {
        $products = Mockery::mock(ProductStockRepository::class);
        $movements = Mockery::mock(InventoryMovementRepository::class);

        $service = new InventoryService($products, $movements);

        $products->shouldNotReceive('getStock');
        $products->shouldNotReceive('setStock');
        $movements->shouldNotReceive('create');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('qty must be greater than 0');

        $service->adjustStock(1, 0, 'in', 'restock', null);
    }
}

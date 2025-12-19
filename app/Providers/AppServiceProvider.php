<?php

namespace App\Providers;

use App\Repositories\Contracts\InventoryMovementRepository;
use App\Repositories\Contracts\ProductStockRepository;
use App\Repositories\Eloquent\InventoryMovementEloquentRepository;
use App\Repositories\Eloquent\ProductStockEloquentRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ProductStockRepository::class, ProductStockEloquentRepository::class);
        $this->app->bind(InventoryMovementRepository::class, InventoryMovementEloquentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleAuthController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\PosController;
use App\Http\Controllers\Api\V1\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // ========== AUTH ==========
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('2fa/verify', [TwoFactorController::class, 'verify']);

        // Google Login 
        Route::post('google', [GoogleAuthController::class, 'login']);

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', [AuthController::class, 'logout']);
        });
    });

    // Semua fitur di bawah wajib login
    Route::middleware('auth:sanctum')->group(function (): void {

        // CATEGORY MANAGEMENT
        Route::apiResource('categories', CategoryController::class);

        // PRODUCT MANAGEMENT  (MinIO dipakai di ProductService)
        Route::apiResource('products', ProductController::class);

        // SUPPLIER MANAGEMENT
        Route::apiResource('suppliers', SupplierController::class);

        // PURCHASE ORDER MANAGEMENT
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);   
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::get('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show']);
        Route::post('purchase-orders/{purchaseOrder}/mark-ordered', [PurchaseOrderController::class, 'markOrdered']);
        Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
        Route::post('purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
        Route::delete('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy']);
        Route::put('purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update']);

        // POS SALES
        Route::get('pos/sales', [PosController::class, 'index']);
        Route::post('pos/sales', [PosController::class, 'store']);
        Route::get('pos/sales/{sale}', [PosController::class, 'show']);
        Route::delete('pos/sales/{sale}', [PosController::class, 'destroy']);

        // INVENTORY
        Route::get('inventory/stock', [InventoryController::class, 'stockIndex']);
        Route::get('inventory/movements', [InventoryController::class, 'movementsIndex']);
        Route::get('inventory/stock/{id}', [InventoryController::class, 'stockShow']);
        Route::post('inventory/adjustments', [InventoryController::class, 'adjustStock']);
        Route::get('inventory/movements/{movement}', [InventoryController::class, 'movementShow']);
        Route::put('inventory/movements/{movement}', [InventoryController::class, 'movementUpdate']);
        Route::post('inventory/movements/{movement}/void', [InventoryController::class, 'movementVoid']);
    });
});

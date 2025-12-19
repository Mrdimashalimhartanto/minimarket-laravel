# 🏪 Minimarket POS System (Laravel Backend)

**Minimarket POS System** merupakan aplikasi backend berbasis **Laravel** yang dirancang untuk mendukung pengelolaan operasional minimarket secara **terstruktur, aman, dan efisien**.

Sistem ini mencakup berbagai fitur utama, antara lain:
- Manajemen **produk** dan **kategori**
- Pengelolaan **stok / inventory**
- Manajemen **supplier**
- Proses **purchase order**
- Pencatatan **penjualan (sales)**
- Integrasi **object storage (MinIO)** untuk penyimpanan gambar produk

Project ini dikembangkan dengan pendekatan **Clean Architecture** dan **RESTful API**, serta dirancang agar mudah diintegrasikan dengan **frontend web** maupun **aplikasi mobile (Flutter)**.

---

## 🚀 Tech Stack

- **Framework**: Laravel 12  
- **Language**: PHP 8.2+  
- **Database**: MySQL  
- **Admin Panel**: Filament v3  
- **Storage**: MinIO (S3 Compatible)  
- **Authentication**: Laravel Sanctum  
- **Testing**: PHPUnit  
- **Containerization**: Docker  

---

## 📂 Project Structure (Simplified)

minimarket-laravel/
📦app
 ┣ 📂Actions
 ┃ ┣ 📂Auth
 ┃ ┗ 📂Inventory
 ┣ 📂Enums
 ┃ ┣ 📜InventoryMovementType.php
 ┃ ┣ 📜PaymentMethod.php
 ┃ ┗ 📜ProductStatus.php
 ┣ 📂Filament
 ┃ ┗ 📂Resources
 ┃ ┃ ┣ 📂CategoryResource
 ┃ ┃ ┃ ┗ 📂Pages
 ┃ ┃ ┃ ┃ ┣ 📜CreateCategory.php
 ┃ ┃ ┃ ┃ ┣ 📜EditCategory.php
 ┃ ┃ ┃ ┃ ┗ 📜ListCategories.php
 ┃ ┃ ┣ 📂InventoryMovementResource
 ┃ ┃ ┃ ┗ 📂Pages
 ┃ ┃ ┃ ┃ ┣ 📜CreateInventoryMovement.php
 ┃ ┃ ┃ ┃ ┣ 📜EditInventoryMovement.php
 ┃ ┃ ┃ ┃ ┗ 📜ListInventoryMovements.php
 ┃ ┃ ┣ 📂ProductResource
 ┃ ┃ ┃ ┗ 📂Pages
 ┃ ┃ ┃ ┃ ┣ 📜CreateProduct.php
 ┃ ┃ ┃ ┃ ┣ 📜EditProduct.php
 ┃ ┃ ┃ ┃ ┗ 📜ListProducts.php
 ┃ ┃ ┣ 📂PurchaseOrderResource
 ┃ ┃ ┃ ┗ 📂Pages
 ┃ ┃ ┃ ┃ ┣ 📜CreatePurchaseOrder.php
 ┃ ┃ ┃ ┃ ┣ 📜EditPurchaseOrder.php
 ┃ ┃ ┃ ┃ ┗ 📜ListPurchaseOrders.php
 ┃ ┃ ┣ 📂SaleResource
 ┃ ┃ ┃ ┣ 📂Pages
 ┃ ┃ ┃ ┃ ┣ 📜CreateSale.php
 ┃ ┃ ┃ ┃ ┣ 📜EditSale.php
 ┃ ┃ ┃ ┃ ┗ 📜ListSales.php
 ┃ ┃ ┃ ┗ 📂RelationManagers
 ┃ ┃ ┃ ┃ ┗ 📜ItemsRelationManager.php
 ┃ ┃ ┣ 📂SupplierResource
 ┃ ┃ ┃ ┣ 📂Pages
 ┃ ┃ ┃ ┃ ┣ 📜CreateSupplier.php
 ┃ ┃ ┃ ┃ ┣ 📜EditSupplier.php
 ┃ ┃ ┃ ┃ ┗ 📜ListSuppliers.php
 ┃ ┃ ┃ ┗ 📂RelationManagers
 ┃ ┃ ┃ ┃ ┗ 📜PurchaseOrderRelationManager.php
 ┃ ┃ ┣ 📜CategoryResource.php
 ┃ ┃ ┣ 📜InventoryMovementResource.php
 ┃ ┃ ┣ 📜ProductResource.php
 ┃ ┃ ┣ 📜PurchaseOrderResource.php
 ┃ ┃ ┣ 📜SaleResource.php
 ┃ ┃ ┗ 📜SupplierResource.php
 ┣ 📂Http
 ┃ ┣ 📂Controllers
 ┃ ┃ ┣ 📂Api
 ┃ ┃ ┃ ┗ 📂V1
 ┃ ┃ ┃ ┃ ┣ 📂Auth
 ┃ ┃ ┃ ┃ ┃ ┣ 📜AuthController.php
 ┃ ┃ ┃ ┃ ┃ ┣ 📜GoogleAuthController.php
 ┃ ┃ ┃ ┃ ┃ ┗ 📜TwoFactorController.php
 ┃ ┃ ┃ ┃ ┣ 📜CategoryController.php
 ┃ ┃ ┃ ┃ ┣ 📜InventoryController.php
 ┃ ┃ ┃ ┃ ┣ 📜PosController.php
 ┃ ┃ ┃ ┃ ┣ 📜ProductController.php
 ┃ ┃ ┃ ┃ ┣ 📜PurchaseOrderController.php
 ┃ ┃ ┃ ┃ ┗ 📜SupplierController.php
 ┃ ┃ ┗ 📜Controller.php
 ┃ ┣ 📂Middleware
 ┃ ┃ ┗ 📜EnsureTwoFactorEnabled.php
 ┃ ┗ 📂Requests
 ┃ ┃ ┣ 📂Auth
 ┃ ┃ ┃ ┣ 📜GoogleLoginRequest.php
 ┃ ┃ ┃ ┣ 📜LoginRequest.php
 ┃ ┃ ┃ ┣ 📜RegisterRequest.php
 ┃ ┃ ┃ ┗ 📜VerifyTwoFactorRequest.php
 ┃ ┃ ┣ 📂Category
 ┃ ┃ ┃ ┣ 📜StoreCategoryRequest.php
 ┃ ┃ ┃ ┗ 📜UpdateCategoryRequest.php
 ┃ ┃ ┣ 📂Inventory
 ┃ ┃ ┃ ┣ 📜AdjustStockRequest.php
 ┃ ┃ ┃ ┗ 📜MovementFilterRequest.php
 ┃ ┃ ┣ 📂Pos
 ┃ ┃ ┃ ┣ 📜StoreSaleRequest.php
 ┃ ┃ ┃ ┗ 📜UpdateSaleRequest.php
 ┃ ┃ ┣ 📂Product
 ┃ ┃ ┃ ┣ 📜StoreProductRequest.php
 ┃ ┃ ┃ ┗ 📜UpdateProductRequest.php
 ┃ ┃ ┣ 📂PurchaseOrder
 ┃ ┃ ┃ ┣ 📜ReceivePurchaseOrderRequest.php
 ┃ ┃ ┃ ┣ 📜StorePurchaseOrderRequest.php
 ┃ ┃ ┃ ┗ 📜UpdatePurchaseOrderRequest.php
 ┃ ┃ ┗ 📂Supplier
 ┃ ┃ ┃ ┣ 📜StoreSupplierRequest.php
 ┃ ┃ ┃ ┗ 📜UpdateSupplierRequest.php
 ┣ 📂Models
 ┃ ┣ 📜Category.php
 ┃ ┣ 📜InventoryAdjustment.php
 ┃ ┣ 📜InventoryMovement.php
 ┃ ┣ 📜Product.php
 ┃ ┣ 📜ProductImage.php
 ┃ ┣ 📜PurchaseOrder.php
 ┃ ┣ 📜PurchaseOrderItem.php
 ┃ ┣ 📜Sale.php
 ┃ ┣ 📜SaleItem.php
 ┃ ┣ 📜Supplier.php
 ┃ ┣ 📜TwoFactorCode.php
 ┃ ┗ 📜User.php
 ┣ 📂Providers
 ┃ ┣ 📂Filament
 ┃ ┃ ┗ 📜AdminPanelProvider.php
 ┃ ┗ 📜AppServiceProvider.php
 ┣ 📂Repositories
 ┃ ┣ 📂Contracts
 ┃ ┃ ┣ 📜InventoryMovementRepository.php
 ┃ ┃ ┗ 📜ProductStockRepository.php
 ┃ ┣ 📂Eloquent
 ┃ ┃ ┣ 📜InventoryMovementEloquentRepository.php
 ┃ ┃ ┗ 📜ProductStockEloquentRepository.php
 ┃ ┣ 📜InventoryRepository.php
 ┃ ┣ 📜ProductRepository.php
 ┃ ┣ 📜PurchaseOrderRepository.php
 ┃ ┣ 📜SaleRepository.php
 ┃ ┗ 📜SupplierRepository.php
 ┣ 📂Services
 ┃ ┣ 📜AuthService.php
 ┃ ┣ 📜CategoryService.php
 ┃ ┣ 📜ImageStorageService.php
 ┃ ┣ 📜InventoryService.php
 ┃ ┣ 📜PosService.php
 ┃ ┣ 📜ProductService.php
 ┃ ┣ 📜PurchaseOrderService.php
 ┃ ┗ 📜SupplierService.php
 ┗ 📂Support
 ┃ ┣ 📜ApiResponse.php
 ┃ ┣ 📜CacheKey.php
 ┃ ┣ 📜CacheVersion.php
 ┃ ┣ 📜EnumHelper.php
 ┃ ┣ 📜ImagePath.php
 ┃ ┗ 📜TwoFactor.php



---

## 🗄️ Database Setup

Buat database MySQL terlebih dahulu:

sql
CREATE DATABASE minimarket_pos_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

---

## ⚙️ Environment Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=minimarket_pos_system
DB_USERNAME=minimarket_user
DB_PASSWORD=passwordku123

---

📦 Install Dependency & Generate Key
composer install
php artisan key:generate
php artisan migrate
---

🐳 Docker Compose Setup (MinIO)
version: "3.9"

services:
  minio:
    image: minio/minio:latest
    container_name: minimarket_minio
    restart: unless-stopped
    environment:
      MINIO_ROOT_USER: minioadmin
      MINIO_ROOT_PASSWORD: minioadmin
    command: server /data --address ":9000" --console-address ":9001"
    ports:
      - "9000:9000"
      - "9001:9001"
    volumes:
      - ./storage/minio-data:/data

volumes:
  db_data:

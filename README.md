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
```bash
minimarket-laravel/
├── app/
│ ├── Http/
│ │ ├── Controllers/
│ │ │ └── Api/V1
│ │ ├── Requests
│ │ └── Resources
│ ├── Models
│ ├── Services
│ └── Support
├── database/
│ ├── migrations
│ └── seeders
├── routes/
│ ├── api.php
│ └── web.php
├── tests/
│ ├── Feature
│ └── Unit
├── .env.example
└── README.md
```
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

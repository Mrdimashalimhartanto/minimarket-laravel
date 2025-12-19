# 🏪 Minimarket POS System (Laravel Backend)

Minimarket POS System adalah aplikasi backend berbasis **Laravel** yang dirancang untuk mengelola operasional minimarket secara terstruktur dan efisien.  
Sistem ini mencakup pengelolaan **produk, kategori, stok/inventory, supplier, purchase order**, serta integrasi **object storage (MinIO)** untuk manajemen gambar produk.

Project ini dikembangkan dengan pendekatan **clean architecture**, RESTful API, dan siap diintegrasikan dengan **frontend web maupun mobile (Flutter)**.

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

## 📂 Project Structure (Simplified)
minimarket-laravel/
├── app/
│ ├── Http/
│ │ ├── Controllers/Api/V1
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


## DATABASE SETUP 
- CREATE DATABASE minimarket_pos_system

## KONFIGURASI ENV
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3307
DB_DATABASE=minimarket_pos_system
DB_USERNAME=minimarket_user
DB_PASSWORD=passwordku123

## Install Dependency & Generate Key
- composer install
- php artisan key:generate
- php artisan migrate

## Docker compose setup
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

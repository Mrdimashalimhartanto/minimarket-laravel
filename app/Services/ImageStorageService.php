<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageStorageService
{
  protected string $disk = 'minio';

  /**
   * Upload image produk ke MinIO.
   *
   * @param UploadedFile $file
   * @param string|null  $oldPath  path lama (kalau update dan mau hapus file lama)
   * @return string path baru yang disimpan di DB
   */
  public function uploadProductImage(UploadedFile $file, ?string $oldPath = null): string
  {
    // hapus file lama kalau ada
    if ($oldPath) {
      $this->delete($oldPath);
    }

    $extension = $file->getClientOriginalExtension();
    $filename = Str::uuid()->toString() . '.' . $extension;
    $folder = 'products/' . now()->format('Y/m'); // products/2025/12

    // simpan ke MinIO → hasil path, mis: "products/2025/12/uuid.png"
    $path = Storage::disk($this->disk)->putFileAs(
      $folder,
      $file,
      $filename
    );

    return $path;
  }



  /**
   * Hapus file di MinIO.
   */
  public function delete(?string $path): void
  {
    if (!$path) {
      return;
    }

    Storage::disk($this->disk)->delete($path);
  }
}

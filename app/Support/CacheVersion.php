<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class CacheVersion
{
  public static function get(string $name): int
  {
    return (int) Cache::get("v:{$name}", 1);
  }

  public static function bump(string $name): void
  {
    Cache::increment("v:{$name}");
  }
}

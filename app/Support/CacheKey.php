<?php

namespace App\Support;

use Illuminate\Http\Request;

class CacheKey
{
  public static function fromRequest(string $prefix, Request $request, ?int $userId = null): string
  {
    $path = trim($request->path(), '/');
    $query = $request->query();
    ksort($query);

    $queryHash = md5(http_build_query($query));
    $scope = $userId ? "u{$userId}" : "public";
    $version = CacheVersion::get($prefix);

    return "{$prefix}:v{$version}:{$scope}:{$path}:{$queryHash}";
  }
}

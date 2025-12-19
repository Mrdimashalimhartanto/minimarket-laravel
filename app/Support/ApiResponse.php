<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
  public static function success(string $message = 'OK', mixed $data = null, int $status = 200): JsonResponse
  {
    return response()->json([
      'success' => true,
      'message' => $message,
      'data' => $data,
    ], $status);
  }

  public static function created(string $message = 'Created', mixed $data = null): JsonResponse
  {
    return self::success($message, $data, 201);
  }

  public static function error(string $message = 'Error', mixed $errors = null, int $status = 422): JsonResponse
  {
    return response()->json([
      'success' => false,
      'message' => $message,
      'errors' => $errors,
    ], $status);
  }

  public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
  {
    return self::error($message, null, 401);
  }
}

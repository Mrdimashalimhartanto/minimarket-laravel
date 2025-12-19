<?php

namespace Tests\Unit\Support;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Http\JsonResponse;
use App\Support\ApiResponse;

class ApiResponseTest extends TestCase
{
    #[Test]
    public function success_returns_expected_structure(): void
    {
        $res = ApiResponse::success('OK', ['x' => 1], 200);

        $this->assertInstanceOf(JsonResponse::class, $res);
        $this->assertSame(200, $res->getStatusCode()); // ✅ status HTTP

        $data = $res->getData(true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('data', $data);

        $this->assertTrue($data['success']);
        $this->assertSame('OK', $data['message']);
        $this->assertSame(['x' => 1], $data['data']);
    }

    #[Test]
    public function error_returns_expected_structure(): void
    {
        $res = ApiResponse::error('Bad Request', ['field' => ['required']], 400);

        $this->assertInstanceOf(JsonResponse::class, $res);
        $this->assertSame(400, $res->getStatusCode()); // ✅ status HTTP

        $data = $res->getData(true);

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('errors', $data);

        $this->assertFalse($data['success']);
        $this->assertSame('Bad Request', $data['message']);
        $this->assertSame(['field' => ['required']], $data['errors']);
    }
}

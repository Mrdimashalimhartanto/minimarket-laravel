<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {
    }

    public function register(RegisterRequest $request)
    {
        $user = $this->authService->register($request->validated());

        return ApiResponse::created('User registered', [
            'user' => $user,
        ]);
    }

    public function login(LoginRequest $request)
    {
        $data = $this->authService->login($request->validated());

        return ApiResponse::success('Login success', $data);
    }

    public function logout(Request $request)
    {
        $this->authService->logout($request->user());

        return ApiResponse::success('Logged out');
    }
}

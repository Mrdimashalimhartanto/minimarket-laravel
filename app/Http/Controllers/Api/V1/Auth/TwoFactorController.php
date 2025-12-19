<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyTwoFactorRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class TwoFactorController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function verify(VerifyTwoFactorRequest $request)
    {
        $data = $request->validated();

        // cari user
        $user = User::where('email', $data['email'])->firstOrFail();

        //  Service untuk verify + Generate Token
        $result = $this->authService->verifyTwoFactor($user, $data['code']);

        // LANGSUNG JSON, tanpa helper lain
        return response()->json([
            'success' => true,
            'message' => 'Two factor verified',
            'data'    => $result,
        ]);
    }
}

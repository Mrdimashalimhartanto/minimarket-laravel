<?php

namespace App\Services;

use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
  public function __construct(
    protected TwoFactor $twoFactor,
  ) {
  }

  public function register(array $payload): User
  {
    return DB::transaction(function () use ($payload) {
      $user = User::create([
        'name' => $payload['name'],
        'email' => $payload['email'],
        'password' => bcrypt($payload['password']),
        'two_factor_type' => 'email', // default: aktifkan 2FA email
      ]);

      return $user;
    });
  }

  public function login(array $payload): array
  {
    if (!Auth::attempt(['email' => $payload['email'], 'password' => $payload['password']])) {
      throw ValidationException::withMessages([
        'email' => ['Invalid credentials.'],
      ]);
    }

    /** @var User $user */
    $user = Auth::user();

    // Kalau 2FA aktif, jangan dulu kirim token, kirim flag dulu
    if ($user->two_factor_type !== 'none') {
      $this->twoFactor->createLoginCode($user);

      return [
        'requires_two_factor' => true,
      ];
    }

    $token = $user->createToken('api')->plainTextToken;

    return [
      'requires_two_factor' => false,
      'token' => $token,
      'user' => $user,
    ];
  }

  public function verifyTwoFactor(User $user, string $code): array
  {
    if (!$this->twoFactor->verifyCode($user, $code)) {
      throw ValidationException::withMessages([
        'code' => ['Invalid or expired code.'],
      ]);
    }

    $token = $user->createToken('api')->plainTextToken;

    return [
      'token' => $token,
      'user' => $user,
    ];
  }

  public function logout(User $user): void
  {
    $user->currentAccessToken()?->delete();
  }
}

<?php

namespace App\Support;

use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TwoFactor
{
  public function createLoginCode(User $user): TwoFactorCode
  {
    // hapus code lama
    $user->twoFactorCodes()->delete();

    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

    $twoFactor = $user->twoFactorCodes()->create([
      'code' => $code,
      'expired_at' => now()->addMinutes(5),
    ]);

    // kirim email (simple dulu)
    if ($user->two_factor_type === 'email') {
      // optional: bikin view / email proper
      Mail::raw("Your login code is: {$code}", function ($message) use ($user) {
        $message->to($user->email)
          ->subject('Your Login Verification Code');
      });
    }

    return $twoFactor;
  }

  public function verifyCode(User $user, string $code): bool
  {
    $record = $user->twoFactorCodes()
      ->where('code', $code)
      ->where('expired_at', '>=', now())
      ->latest()
      ->first();

    if (!$record) {
      return false;
    }

    // sekali pakai: hapus setelah dipakai
    $record->delete();

    return true;
  }
}

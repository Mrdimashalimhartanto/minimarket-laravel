<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GoogleLoginRequest;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use GuzzleHttp\Client as GuzzleClient;

class GoogleAuthController extends Controller
{
    public function login(GoogleLoginRequest $request): JsonResponse
    {
        $idToken = $request->validated()['id_token'];

        $clientId = config('services.google.client_id');
        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi GOOGLE_CLIENT_ID belum di-set di server.',
            ], 500);
        }

        try {
            // ✅ Verify id_token with google/apiclient
            $client = new GoogleClient(['client_id' => $clientId]);

            $client->setHttpClient(new GuzzleClient([
                'verify' => 'C:\laragon\etc\ssl\cacert.pem',
            ]));

            /**
             * OPTIONAL (kalau lu masih sering kena SSL error):
             * pakai guzzle client custom yg verify CA bundle.
             * PASTIKAN path ini bener di Windows/Laragon.
             */
            $caPath = ini_get('curl.cainfo') ?: ini_get('openssl.cafile');
            if (!empty($caPath) && file_exists($caPath)) {
                $client->setHttpClient(new \GuzzleHttp\Client([
                    'verify' => $caPath,
                    'timeout' => 10,
                ]));
            }

            $payload = $client->verifyIdToken($idToken);
        } catch (\Throwable $e) {
            // ✅ biar kalau ada error curl 77 / ssl, lu langsung tau
            return response()->json([
                'success' => false,
                'message' => 'Gagal verifikasi token Google.',
                'error' => $e->getMessage(),
            ], 500);
        }

        if (!$payload) {
            return response()->json([
                'success' => false,
                'message' => 'Google token tidak valid / expired.',
            ], 401);
        }

        $email = $payload['email'] ?? null;
        $name = $payload['name'] ?? null;

        if (!$email) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan dari Google token.',
            ], 422);
        }

        $user = User::query()->where('email', $email)->first();

        if (!$user) {
            $user = User::query()->create([
                'name' => $name ?: Str::before($email, '@'),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
            ]);
        } else {
            if ($name && (!$user->name || trim($user->name) === '')) {
                $user->update(['name' => $name]);
            }
        }

        // Optional: kalau mau 1 user 1 token aja
        // $user->tokens()->delete();

        $token = $user->createToken('google-auth')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login Google berhasil.',
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = auth()->user();
        $user?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }
}

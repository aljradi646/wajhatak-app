<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RealtimeTicketController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $secret = (string) config('services.realtime.shared_secret');
        if (strlen($secret) < 32) {
            return response()->json(['message' => 'خدمة التحديث الفوري غير مهيأة.'], 503);
        }

        $expiresAt = now()->addSeconds(60)->timestamp;
        $payload = [
            'sub' => (int) $request->user()->id,
            'exp' => $expiresAt,
            'nonce' => Str::random(32),
        ];
        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encoded, $secret, true));

        return response()->json([
            'data' => [
                'ticket' => $encoded.'.'.$signature,
                'expires_at' => $expiresAt,
            ],
        ])->header('Cache-Control', 'no-store');
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

<?php

namespace App\Services\Notifications;

use App\Models\UserDevice;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmPushService
{
    public function send(UserDevice $device, array $payload): bool
    {
        $projectId = (string) config('services.firebase.project_id');
        if ($projectId === '') {
            return false;
        }

        $accessToken = $this->accessToken();
        if (! $accessToken) {
            return false;
        }

        $data = [];
        foreach ($payload as $key => $value) {
            if (is_scalar($value) && $value !== '') {
                $stringValue = (string) $value;
                if (in_array((string) $key, ['message', 'reason'], true)) {
                    $stringValue = mb_substr($stringValue, 0, 500);
                }
                $data[(string) $key] = $stringValue;
            }
        }

        $title = (string) ($payload['title'] ?? 'وجهتك');
        $body = mb_substr((string) ($payload['message'] ?? 'لديك إشعار جديد في وجهتك.'), 0, 500);
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->timeout(8)
            ->post('https://fcm.googleapis.com/v1/projects/'.rawurlencode($projectId).'/messages:send', [
                'message' => [
                    'token' => $device->push_token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                    'android' => ['priority' => 'HIGH'],
                    'apns' => ['headers' => ['apns-priority' => '10']],
                ],
            ]);

        if (! $response->successful()) {
            Log::warning('FCM push delivery failed', [
                'device_id' => $device->id,
                'status' => $response->status(),
            ]);
            return false;
        }

        return true;
    }

    private function accessToken(): ?string
    {
        $projectId = (string) config('services.firebase.project_id');
        $email = (string) config('services.firebase.client_email');
        $privateKey = (string) config('services.firebase.private_key');

        if ($projectId === '' || $email === '' || $privateKey === '') {
            return null;
        }

        return Cache::remember('fcm:oauth:'.hash('sha256', $email), 3500, function () use ($email, $privateKey): ?string {
            $privateKey = str_replace('\\n', "\n", $privateKey);
            $now = time();
            $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64UrlEncode(json_encode([
                'iss' => $email,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));
            $unsigned = $header.'.'.$claims;

            $key = openssl_pkey_get_private($privateKey);
            if ($key === false || ! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
                Log::error('FCM service account key could not sign an access assertion.');
                return null;
            }

            $assertion = $unsigned.'.'.$this->base64UrlEncode($signature);
            $response = Http::asForm()->timeout(8)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

            if (! $response->successful()) {
                Log::warning('FCM OAuth token exchange failed', ['status' => $response->status()]);
                return null;
            }

            return $response->json('access_token');
        });
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

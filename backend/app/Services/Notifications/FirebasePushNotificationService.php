<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebasePushNotificationService
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const MESSAGING_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function sendDatabaseNotification(User $user, Model $notification): void
    {
        $credentials = $this->credentials();
        if ($credentials === null) {
            return;
        }

        $projectId = (string) (config('services.firebase.project_id') ?: ($credentials['project_id'] ?? ''));
        if ($projectId === '') {
            Log::warning('Firebase push is not configured: project id is missing.');
            return;
        }

        $devices = $user->devices()
            ->whereIn('platform', ['android', 'ios'])
            ->whereNotNull('push_token')
            ->get(['id', 'push_token']);

        if ($devices->isEmpty()) {
            return;
        }

        try {
            $accessToken = $this->accessToken($credentials);
            if ($accessToken === '') {
                return;
            }

            $data = is_array($notification->data ?? null) ? $notification->data : [];
            $payload = [
                'notification_id' => (string) $notification->getKey(),
                'kind' => (string) ($data['kind'] ?? $notification->type ?? 'update'),
            ];

            foreach ([
                'support_ticket_id', 'ticket_reference', 'ticket_subject',
                'conversation_id', 'viewing_request_id', 'property_id',
            ] as $key) {
                if (isset($data[$key]) && is_scalar($data[$key])) {
                    $payload[$key] = (string) $data[$key];
                }
            }

            $endpoint = 'https://fcm.googleapis.com/v1/projects/'
                .rawurlencode($projectId).'/messages:send';

            foreach ($devices as $device) {
                try {
                    $response = Http::withToken($accessToken)
                        ->acceptJson()
                        ->asJson()
                        ->timeout(5)
                        ->post($endpoint, [
                            'message' => [
                                'token' => $device->push_token,
                                'notification' => [
                                    'title' => trim((string) ($data['title'] ?? 'تحديث من وجهتك')),
                                    'body' => trim((string) ($data['message'] ?? 'لديك إشعار جديد في وجهتك.')),
                                ],
                                'data' => $payload,
                                'android' => [
                                    'priority' => 'HIGH',
                                    'notification' => [
                                        'channel_id' => 'lux_updates',
                                        'sound' => 'default',
                                    ],
                                ],
                                'apns' => [
                                    'payload' => ['aps' => ['sound' => 'default']],
                                ],
                            ],
                        ]);

                    if ($response->successful()) {
                        continue;
                    }

                    $message = (string) $response->json('error.message', '');
                    if ($response->status() === 404 || str_contains($message, 'UNREGISTERED')) {
                        $user->devices()->whereKey($device->id)->delete();
                        continue;
                    }

                    Log::warning('Firebase push delivery failed.', [
                        'user_id' => $user->id,
                        'device_id' => $device->id,
                        'status' => $response->status(),
                        'reason' => mb_substr($message, 0, 300),
                    ]);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function credentials(): ?array
    {
        $json = (string) config('services.firebase.service_account_json', '');
        $path = (string) config('services.firebase.credentials_path', '');

        if ($json === '' && $path !== '' && is_file($path) && is_readable($path)) {
            $json = (string) file_get_contents($path);
        }
        if ($json === '') {
            return null;
        }

        try {
            $credentials = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            Log::error('Firebase service account JSON could not be parsed.');
            return null;
        }

        if (! is_array($credentials)
            || empty($credentials['client_email'])
            || empty($credentials['private_key'])
            || empty($credentials['project_id'])) {
            Log::error('Firebase service account is missing required fields.');
            return null;
        }

        return $credentials;
    }

    private function accessToken(array $credentials): string
    {
        $cacheKey = 'firebase_fcm_access_token:'.sha1((string) $credentials['client_email']);

        $token = (string) Cache::remember($cacheKey, now()->addMinutes(50), function () use ($credentials): string {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => self::MESSAGING_SCOPE,
                'aud' => self::TOKEN_URI,
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));

            $unsignedToken = $header.'.'.$claims;
            $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);
            if ($privateKey === false) {
                Log::error('Firebase service account private key is invalid.');
                return '';
            }

            $signature = '';
            if (! openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
                Log::error('Firebase service account JWT signing failed.');
                return '';
            }

            $response = Http::asForm()->acceptJson()->timeout(5)->post(self::TOKEN_URI, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsignedToken.'.'.$this->base64Url($signature),
            ]);

            if (! $response->successful()) {
                Log::warning('Firebase OAuth token exchange failed.', ['status' => $response->status()]);
                return '';
            }

            return (string) $response->json('access_token', '');
        });

        // Do not cache failed exchanges as an empty token for 50 minutes.
        if ($token === '') {
            Cache::forget($cacheKey);
        }

        return $token;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

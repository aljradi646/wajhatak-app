<?php

namespace App\Services\Realtime;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RealtimePublisher
{
    public function publishToUser(int $userId, string $event, array $data = []): void
    {
        $url = trim((string) config('services.realtime.publish_url'));
        $secret = (string) config('services.realtime.shared_secret');
        if ($url === '' || strlen($secret) < 32) {
            return;
        }

        try {
            $timestamp = (string) now()->timestamp;
            $body = json_encode([
                'user_id' => $userId,
                'event' => $event,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $signature = hash_hmac('sha256', $timestamp.'.'.$body, $secret);

            $response = Http::timeout(3)
                ->withHeaders([
                    'X-Realtime-Timestamp' => $timestamp,
                    'X-Realtime-Signature' => $signature,
                ])
                ->withBody($body, 'application/json')
                ->post($url);

            if (! $response->successful()) {
                Log::warning('Realtime publish request was rejected', [
                    'event' => $event,
                    'status' => $response->status(),
                ]);
            }
        } catch (Throwable $exception) {
            Log::warning('Realtime publish unavailable', [
                'event' => $event,
                'exception' => $exception::class,
            ]);
        }
    }
}

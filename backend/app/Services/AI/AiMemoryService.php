<?php

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiMemoryService
{
    public function getMemories(?User $user, ?string $query = null): array
    {
        if (! $user || ! Schema::hasTable('ai_user_memories')) {
            return [];
        }

        try {
            $builder = DB::table('ai_user_memories')
                ->where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderByDesc('updated_at');

            if ($query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('memory_key', 'like', '%'.mb_substr($query, 0, 80).'%')
                        ->orWhere('memory_value', 'like', '%'.mb_substr($query, 0, 120).'%');
                });
            }

            return $builder->limit(12)->pluck('memory_value', 'memory_key')->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    public function remember(
        ?User $user,
        string $key,
        string $value,
        float $confidence = 1.0,
        string $source = 'conversation',
        ?\DateTimeInterface $expiresAt = null,
    ): bool {
        if (! $user || ! Schema::hasTable('ai_user_memories')) {
            return false;
        }

        try {
            $key = mb_substr(trim($key), 0, 80);
            $value = mb_substr(trim($value), 0, 500);
            if ($key === '' || $value === '') {
                return false;
            }

            $existing = DB::table('ai_user_memories')
                ->where('user_id', $user->id)
                ->where('memory_key', $key)
                ->first();

            $now = now();
            $payload = [
                'memory_value' => $value,
                'confidence' => max(0, min(1, $confidence)),
                'source' => mb_substr($source, 0, 30),
                'last_used_at' => $now,
                'updated_at' => $now,
                'expires_at' => $expiresAt,
            ];

            if ($existing) {
                return (bool) DB::table('ai_user_memories')
                    ->where('user_id', $user->id)
                    ->where('memory_key', $key)
                    ->update($payload);
            }

            $payload['user_id'] = $user->id;
            $payload['memory_key'] = $key;
            $payload['created_at'] = $now;

            return (bool) DB::table('ai_user_memories')->insert($payload);
        } catch (Throwable) {
            return false;
        }
    }
}

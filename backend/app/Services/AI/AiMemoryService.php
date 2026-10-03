<?php

namespace App\Services\AI;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Schema;
use Throwable;

/**
 * خدمة إدارة ذاكرة المستخدم طويلة المدى User Memory Service
 */
class AiMemoryService
{
    /**
     * استرجاع ذكريات وتفضيلات المستخدم المنهجية.
     */
    public function getMemories(?User $user, ?string $query = null): array
    {
        if (!$user) {
            return [];
        }

        try {
            if (!Schema::hasTable('ai_user_memories')) {
                return [];
            }

            $queryBuilder = DB::table('ai_user_memories')->where('user_id', $user->id);

            if ($query) {
                $queryBuilder->where('memory_value', 'like', '%' . $query . '%');
            }

            return $queryBuilder->limit(10)->pluck('memory_value', 'memory_key')->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * حفظ/تحديث ذاكرة مفيدة للمستخدم (مثل المدينة المفضلة أو الميزانية).
     */
    public function remember(?User $user, string $key, string $value): bool
    {
        if (!$user) {
            return false;
        }

        try {
            if (!Schema::hasTable('ai_user_memories')) {
                return false;
            }

            DB::table('ai_user_memories')->updateOrInsert(
                ['user_id' => $user->id, 'memory_key' => $key],
                ['memory_value' => $value, 'updated_at' => now(), 'created_at' => now()]
            );

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}

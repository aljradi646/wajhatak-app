<?php

namespace App\Services\AI;

use App\Models\AiConversation;
use App\Models\AiRequestLog;
use Illuminate\Support\Str;

/**
 * تسجيل آمن لطلبات المساعد — للمراقبة والتدقيق فقط.
 * لا يُسجل أبدًا: محتوى الرسائل الحرة، أسرار، بيانات حساسة، أو نصوص النموذج.
 */
class AiLoggingService
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  list<array{tool: string, ok: bool}>|null  $toolCalls
     */
    public function record(
        ?AiConversation $conversation,
        ?int $userId,
        string $intent,
        array $filters,
        ?array $toolCalls,
        int $resultsCount,
        string $status,
        int $latencyMs,
        int $searchMs = 0,
        int $tokensUsed = 0,
        ?string $errorCode = null,
    ): AiRequestLog {
        return AiRequestLog::query()->create([
            'ai_conversation_id' => $conversation?->exists ? $conversation->id : null,
            'user_id' => $userId,
            'request_id' => (string) Str::uuid(),
            'intent' => mb_substr($intent, 0, 40),
            'structured_filters' => $this->sanitizeFilters($filters) ?: null,
            'tool_calls' => $toolCalls,
            'results_count' => $resultsCount,
            'status' => $status,
            'error_code' => $errorCode !== null ? mb_substr($errorCode, 0, 60) : null,
            'latency_ms' => $latencyMs,
            'search_ms' => $searchMs,
            'tokens_used' => $tokensUsed,
        ]);
    }

    /** إحصاءات لوحة المراقبة. */
    public function stats(): array
    {
        $base = AiRequestLog::query();
        $now = now();

        $conversations = \App\Models\AiConversation::query()->count();
        $messages = \App\Models\AiMessage::query()->count();

        return [
            'total_conversations' => $conversations,
            'total_messages' => $messages,
            'successful_requests' => (clone $base)->where('status', 'ok')->count(),
            'blocked_requests' => (clone $base)->where('status', 'blocked')->count(),
            'errors' => (clone $base)->where('status', 'error')->count(),
            'fallback_requests' => (clone $base)->where('status', 'fallback')->count(),
            'avg_response_ms' => (int) (clone $base)->where('status', 'ok')->avg('latency_ms'),
            'avg_search_ms' => (int) (clone $base)->where('status', 'ok')->avg('search_ms'),
            'tool_calls' => (clone $base)->whereNotNull('tool_calls')->count(),
            'no_match_searches' => (clone $base)->where('results_count', 0)->where('intent', 'search')->count(),
            'requests_today' => (clone $base)->whereDate('created_at', $now)->count(),
        ];
    }

    private function sanitizeFilters(array $filters): array
    {
        // المعايير منظمة ومتظبطة أصلاً؛ نحدّ الحجم فقط ويُحذف أي نص حر طويل.
        $clean = $filters;
        if (isset($clean['q'])) {
            $clean['q'] = mb_substr((string) $clean['q'], 0, 60);
        }

        return array_slice($clean, 0, 20);
    }
}


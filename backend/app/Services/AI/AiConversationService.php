<?php

namespace App\Services\AI;

use App\Enums\AiMessageRole;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * دورة حياة محادثات المساعد: فتح، إضافة رسائل، حفظ السياق (المعايير
 * المتراكمة)، حد أسئلة المتابعة، وسياسات الإبقاء والمسح.
 */
class AiConversationService
{
    public function __construct(private readonly AiSettingsService $settings) {}

    /** المحادثة النشطة للمستخدم أو للزائر (عبر مفتاح جلسة) أو إنشاء واحدة. */
    public function currentFor(?User $user, string $locale = 'ar', ?string $sessionToken = null): AiConversation
    {
        $maxConversations = (int) config('ai.limits.max_conversations', 50);

        $query = AiConversation::query()->where('status', 'active');
        if ($user) {
            $query->where('user_id', $user->id);
        } else {
            $query->whereNull('user_id');
            if ($sessionToken) {
                $query->where('session_token', $sessionToken);
            }
        }

        $conversation = $query->latest('last_message_at')->first();

        if (! $conversation) {
            if ($user) {
                // تقليم المحادثات القديمة عند تجاوز الحد.
                $count = AiConversation::query()->where('user_id', $user->id)->count();
                if ($count >= $maxConversations) {
                    $old = AiConversation::query()->where('user_id', $user->id)
                        ->orderByDesc('last_message_at')
                        ->skip($maxConversations - 1)->limit(50)->pluck('id');
                    AiConversation::query()->whereIn('id', $old)->update(['status' => 'archived']);
                }

                $conversation = AiConversation::query()->create([
                    'user_id' => $user->id,
                    'locale' => $locale,
                ]);
            } else {
                // زائر: محادثة مرتبطة بمفتاح جلسة عشوائي غير قابل للتخمين.
                $conversation = AiConversation::query()->create([
                    'user_id' => null,
                    'session_token' => $sessionToken ?: bin2hex(random_bytes(32)),
                    'locale' => $locale,
                ]);
            }
        }

        return $conversation;
    }

    /** آخر رسائل المحادثة بصيغة مزود الاستدلال. */
    public function historyFor(AiConversation $conversation): array
    {
        if (! $this->settings->get('ai_history_enabled', true)) {
            return [];
        }

        $limit = (int) config('ai.limits.history_messages', 8);

        return AiMessage::query()
            ->where('ai_conversation_id', $conversation->id)
            ->where('status', 'ok')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy('id')
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => mb_substr($m->content, 0, 800)])
            ->values()
            ->all();
    }

    /** المعايير المتراكمة: من آخر رسالة مستخدم تحمل معايير (سياق متسلسل). */
    public function accumulatedFilters(AiConversation $conversation): array
    {
        $last = AiMessage::query()
            ->where('ai_conversation_id', $conversation->id)
            ->where('role', AiMessageRole::User->value)
            ->whereNotNull('structured_filters')
            ->orderByDesc('id')
            ->first();

        return $last?->structured_filters ?? [];
    }

    public function addUserMessage(AiConversation $conversation, string $content, array $filters = []): AiMessage
    {
        return AiMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessageRole::User->value,
            'content' => mb_substr($content, 0, (int) config('ai.limits.max_message_length', 600)),
            'structured_filters' => $filters ?: null,
            'status' => 'ok',
        ]);
    }

    public function addAssistantMessage(
        AiConversation $conversation,
        string $content,
        array $propertyIds = [],
        string $status = 'ok',
    ): AiMessage {
        $message = AiMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessageRole::Assistant->value,
            'content' => $content,
            'property_ids' => $propertyIds ?: null,
            'status' => $status,
        ]);
        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    /** عدد أسئلة المتابعة المتتالية الأخيرة (لحد أسئلة المساعد). */
    public function consecutiveFollowUps(AiConversation $conversation): int
    {
        return (int) AiMessage::query()
            ->where('ai_conversation_id', $conversation->id)
            ->where('role', AiMessageRole::Assistant->value)
            ->where('content', 'like', '%?%') // علامة تقريبية لرسائل السؤال
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();
    }

    /** مسح محادثة (soft وفق الإعداد) أو أرشفة كل محادثات المستخدم. */
    public function clear(AiConversation $conversation, string $policy): void
    {
        if ($policy === 'hard') {
            $conversation->messages()->delete();
            $conversation->delete();

            return;
        }

        $conversation->messages()->delete();
        $conversation->update(['status' => 'archived', 'last_message_at' => now()]);
    }

    /** حذف المحادثات القديمة وفق مدة الإبقاء (يُستدعى مجدولًا). */
    public function pruneExpired(): int
    {
        $days = (int) $this->settings->get('ai_history_retention_days', 30);
        $cutoff = now()->subDays($days);

        return (int) AiConversation::query()
            ->where('last_message_at', '<', $cutoff)
            ->each(fn (AiConversation $c) => $c->messages()->delete() || $c->update(['status' => 'archived']))
            ->count();
    }
}

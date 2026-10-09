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
            // لا نختار أي محادثة زائر عند غياب الرمز. الرموز المقبولة 64
            // رقمًا سداسيًا عشوائيًا يصدره الخادم، وليست قيمًا يختارها العميل.
            $query->whereNull('user_id');
            $validSessionToken = is_string($sessionToken)
                && preg_match('/^[a-f0-9]{64}$/iD', $sessionToken) === 1
                    ? $sessionToken
                    : null;

            if ($validSessionToken !== null) {
                $query->where('session_token', $validSessionToken);
            } else {
                // لا يمكن لأي سجل قديم بلا رمز أو برمز ضعيف أن يطابق.
                $query->whereRaw('1 = 0');
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
                    // لا يُستخدم أي رمز أرسله العميل كرمز ملكية جديد.
                    'session_token' => bin2hex(random_bytes(32)),
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
            ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => mb_substr((string) $m->content, 0, 800)])
            ->values()
            ->all();
    }

    /** المعايير المتراكمة: من آخر رسالة مستخدم تحمل معايير (سياق متسلسل). */
    public function accumulatedFilters(AiConversation $conversation): array
    {
        $state = is_array($conversation->context_state) ? $conversation->context_state : [];

        return is_array($state['active_search'] ?? null) ? $state['active_search'] : [];
    }

    /** آخر مجموعة عقارات مرتبطة برسالة مساعد بعينها، لا حالة عامة. */
    public function lastRetrievedPropertyIds(AiConversation $conversation): array
    {
        $message = AiMessage::query()
            ->where('ai_conversation_id', $conversation->id)
            ->where('role', AiMessageRole::Assistant->value)
            ->whereNotNull('property_ids')
            ->orderByDesc('id')
            ->first();

        return array_values(array_unique(array_filter(
            array_map('intval', (array) ($message?->property_ids ?? [])),
            fn ($id) => $id > 0,
        )));
    }

    public function updateUserMessageFilters(AiMessage $message, array $filters): void
    {
        $message->structured_filters = $filters !== [] ? $filters : null;
        $message->save();
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
        string $responseType = 'text',
        array $metadata = [],
    ): AiMessage {
        $message = AiMessage::query()->create([
            'ai_conversation_id' => $conversation->id,
            'role' => AiMessageRole::Assistant->value,
            'content' => $content,
            'property_ids' => $propertyIds ?: null,
            'status' => $status,
            'response_type' => $responseType,
            'metadata' => $metadata ?: null,
        ]);
        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    /**
     * عدد أسئلة المتابعة المتتالية الأخيرة للمساعد (لحد أسئلة المتابعة — سؤالان).
     *
     * نعدّ ردود المساعد المتتالية التي تحمل سؤالًا (؟) من الأحدث إلى الأقدم،
     * ونتخطّى رسائل المستخدم المفردة التي تسبق كل سؤال. أول رد مساعد غير
     * سؤالي ينهي السلسلة — هكذا يُحسب السقف عبر الأدوار لا داخل الدور الواحد.
     */
    public function consecutiveFollowUps(AiConversation $conversation): int
    {
        $recent = AiMessage::query()
            ->where('ai_conversation_id', $conversation->id)
            ->whereIn('role', [AiMessageRole::Assistant->value, AiMessageRole::User->value])
            ->orderByDesc('id')
            ->limit(10)
            ->get(['role', 'content']);

        $streak = 0;
        foreach ($recent as $message) {
            if ($message->role !== AiMessageRole::Assistant->value) {
                continue; // تخطَّ رسائل المستخدم — السلسلة تُقاس بين الأدوار.
            }
            if (! self::isQuestion($message->content)) {
                break; // رد مساعد غير سؤالي ينهي سلسلة أسئلة المتابعة.
            }
            $streak++;
        }

        return $streak;
    }

    /** هل نص رد المساعد يحتوي سؤالًا؟ */
    private static function isQuestion(string $content): bool
    {
        return mb_strpos($content, '؟') !== false || mb_strpos($content, '?') !== false;
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

    /** حذف رسائل المحادثات المنتهية وأرشفتها على دفعات؛ يُستدعى مجدولًا. */
    public function pruneExpired(): int
    {
        $days = max(1, (int) $this->settings->get('ai_history_retention_days', 30));
        $cutoff = now()->subDays($days);
        $pruned = 0;

        AiConversation::query()
            ->where(function ($query) use ($cutoff): void {
                $query->where('last_message_at', '<', $cutoff)
                    ->orWhere(function ($query) use ($cutoff): void {
                        $query->whereNull('last_message_at')
                            ->where('created_at', '<', $cutoff);
                    });
            })
            ->orderBy('id')
            ->chunkById(100, function ($conversations) use (&$pruned): void {
                $ids = $conversations->modelKeys();
                if ($ids === []) {
                    return;
                }

                // افصل الحذف عن الأرشفة: حذف الرسائل لا ينبغي أن يمنع تحديث
                // حالة المحادثة بسبب short-circuit، كما لا نستدعي count على each().
                AiMessage::query()->whereIn('ai_conversation_id', $ids)->delete();

                AiConversation::query()->whereIn('id', $ids)->update([
                    'status' => 'archived',
                    'context_state' => null,
                ]);

                $pruned += count($ids);
            });

        return $pruned;
    }
}

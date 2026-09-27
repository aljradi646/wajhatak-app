<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiChatRequest;
use App\Http\Requests\AiSearchRequest;
use App\Models\AiConversation;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiConversationService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\AiSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $assistant,
        private readonly AiConversationService $conversations,
        private readonly AiSettingsService $settings,
        private readonly AiProviderManager $providers,
    ) {}

    /** POST /api/v1/ai/chat — رسالة كاملة مع توليد رد ونتائج حقيقية. */
    public function chat(AiChatRequest $request): JsonResponse
    {
        if (! $this->available()) {
            return $this->unavailable();
        }

        $user = $request->user('sanctum') ?? $request->user();

        // Rate limiting خاص بالمساعد (أضيق من المعدل العام).
        $key = 'ai-chat:'.($user?->id ?? $request->ip());
        if (! RateLimiter::attempt($key, $perMinute = (int) config('ai.limits.rate_limit_per_min', 10), fn () => true, 60)) {
            return response()->json([
                'message' => 'طلبات كثيرة على المساعد. انتظر قليلًا ثم أعد المحاولة.',
                'status' => 'rate_limited',
            ], 429);
        }

        $conversation = null;
        if ($id = $request->integer('conversation_id')) {
            $conversation = AiConversation::query()->find($id);
            // حماية الملكية: لا يمكن لمستخدم متابعة محادثة غيره.
            if ($conversation && $user && $conversation->user_id !== null && $conversation->user_id !== $user->id) {
                return response()->json(['message' => 'غير مصرح للوصول لهذه المحادثة.'], 403);
            }
        }

        $result = $this->assistant->handleChat(
            $user,
            (string) $request->string('message'),
            $conversation,
            (string) $request->input('locale', 'ar'),
        );

        return response()->json(['data' => $result]);
    }

    /** POST /api/v1/ai/search — بحث منظّم مباشر بلا رد نصي. */
    public function search(AiSearchRequest $request): JsonResponse
    {
        $key = 'ai-search:'.($request->user('sanctum')?->id ?? $request->ip());
        if (! RateLimiter::attempt($key, (int) config('ai.limits.rate_limit_search', 30), fn () => true, 60)) {
            return response()->json(['message' => 'طلبات كثيرة. حاول لاحقًا.', 'status' => 'rate_limited'], 429);
        }

        $user = $request->user('sanctum') ?? $request->user();

        return response()->json(['data' => $this->assistant->handleSearch($request->validated(), $user)]);
    }

    /** GET /api/v1/ai/conversations — محادثات المستخدم. */
    public function conversations(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'يلزم تسجيل الدخول.');

        $items = AiConversation::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->withCount('messages as messages_count')
            ->orderByDesc('last_message_at')
            ->limit((int) config('ai.limits.max_conversations', 50))
            ->get(['id', 'locale', 'status', 'last_message_at', 'created_at']);

        return response()->json(['data' => $items->map(fn (AiConversation $c) => [
            'id' => $c->id,
            'messages_count' => $c->messages_count,
            'last_message_at' => optional($c->last_message_at)->toISOString(),
            'created_at' => optional($c->created_at)->toISOString(),
        ])]);
    }

    /** GET /api/v1/ai/conversations/{id} — رسائل محادثة (ملكيتها فقط). */
    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'يلزم تسجيل الدخول.');
        abort_if($conversation->user_id !== $user->id, 403, 'غير مصرح للوصول لهذه المحادثة.');

        $messages = $conversation->messages()
            ->where('status', '!=', 'blocked')
            ->limit((int) $this->settings->get('ai_max_messages', 50))
            ->get(['id', 'role', 'content', 'property_ids', 'created_at']);

        return response()->json(['data' => [
            'id' => $conversation->id,
            'messages' => $messages->map(fn ($m) => [
                'id' => $m->id,
                'role' => $m->role,
                'content' => $m->content,
                'property_ids' => $m->property_ids,
                'created_at' => optional($m->created_at)->toISOString(),
            ]),
        ]]);
    }

    /** DELETE /api/v1/ai/conversations/{id} — مسح محادثة. */
    public function destroy(Request $request, AiConversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'يلزم تسجيل الدخول.');
        abort_if($conversation->user_id !== $user->id, 403, 'غير مصرح للوصول لهذه المحادثة.');

        $this->conversations->clear($conversation, (string) $this->settings->get('ai_clear_policy', 'soft'));

        return response()->json(status: 204);
    }

    /** GET /api/v1/ai/health — صحة النموذج (عامة ومبسطة، بلا أسرار). */
    public function health(): JsonResponse
    {
        $status = $this->providers->provider()->health();

        return response()->json(['data' => [
            'assistant_enabled' => $this->settings->enabled(),
            'healthy' => $status->healthy,
            'latency_ms' => $status->latencyMs,
            // لا نكشف أسماء النماذج أو عناوين الشبكة الداخلية للعميل.
        ]]);
    }

    /** GET /api/v1/ai/bootstrap — إعدادات واجهة المساعد (اسم/رسالة ترحيب/اقتراحات). */
    public function bootstrap(): JsonResponse
    {
        return response()->json(['data' => [
            'enabled' => $this->settings->enabled(),
            'assistant_name' => $this->settings->assistantName(),
            'welcome_message' => $this->settings->welcomeMessage(),
            'suggestions' => [
                'ابحث لي عن شقة',
                'أرخص العقارات',
                'شقق مفروشة',
                'عقارات قريبة مني',
            ],
        ]]);
    }

    // ------------------------------------------------------------------

    private function available(): bool
    {
        return $this->settings->enabled();
    }

    private function unavailable(): JsonResponse
    {
        return response()->json([
            'message' => 'المساعد غير متاح حاليًا، لكن يمكنك استخدام البحث العقاري التقليدي.',
            'status' => 'assistant_disabled',
        ], 503);
    }
}

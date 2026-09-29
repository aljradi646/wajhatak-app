<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AiChatRequest;
use App\Http\Requests\AiSearchRequest;
use App\Models\AiConversation;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiConversationService;
use App\Services\AI\AiSchemaService;
use App\Services\AI\AiSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiAssistantService $assistant,
        private readonly AiConversationService $conversations,
        private readonly AiSettingsService $settings,
        private readonly AiSchemaService $schema,
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
        if (! RateLimiter::attempt($key, (int) config('ai.limits.rate_limit_per_min', 10), fn () => true, 60)) {
            return response()->json([
                'message' => 'طلبات كثيرة على المساعد. انتظر قليلًا ثم أعد المحاولة.',
                'status' => 'rate_limited',
            ], 429);
        }

        $sessionToken = (string) $request->input('session_token', '');
        $conversation = null;
        if ($id = $request->integer('conversation_id')) {
            $conversation = AiConversation::query()->find($id);
            if ($conversation) {
                // حماية الملكية: محادثة المستخدم أو محادثة زائر بمفتاحه الصحيح فقط.
                if ($user && $conversation->user_id !== null && $conversation->user_id !== $user->id) {
                    return response()->json(['message' => 'غير مصرح للوصول لهذه المحادثة.'], 403);
                }
                if ($conversation->user_id === null
                    && ($user !== null || $sessionToken === '' || ! hash_equals((string) $conversation->session_token, $sessionToken))) {
                    $conversation = null; // تُنشأ محادثة جديدة بدل كشف محادثات الغير.
                }
            }
        }

        if ($conversation === null) {
            // فشل فتح المحادثة (جدول ناقص/قاعدة مشغولة) لا يجب أن يمنع الرد:
            // نُكمل بلا محادثة محفوظة ويجيب المساعد من العقارات الحقيقية.
            try {
                $conversation = $this->conversations->currentFor($user, (string) $request->input('locale', 'ar'), $sessionToken ?: null);
            } catch (Throwable $e) {
                report($e);
                $conversation = null;
            }
        }

        $result = $this->assistant->handleChat(
            $user,
            (string) $request->string('message'),
            $conversation,
            (string) $request->input('locale', 'ar'),
            [
                'latitude' => $request->filled('latitude') ? (float) $request->input('latitude') : null,
                'longitude' => $request->filled('longitude') ? (float) $request->input('longitude') : null,
                'radius_km' => $request->filled('radius_km') ? (float) $request->input('radius_km') : null,
            ],
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

    /** GET /api/v1/ai/health — صحة المحرك الحتمي (فحص حقيقي، بلا أسرار). */
    public function health(): JsonResponse
    {
        // فحص فعلي للمخطط بدل إعلان «سليم» دائمًا: إن كان جدول/عمود ناقصًا
        // فالرد يقول ذلك صراحةً بدل أن يكتشفه المستخدم برسالة خطأ غامضة.
        $diagnostics = $this->schema->diagnose();
        $missing = collect($diagnostics)
            ->filter(fn (array $info) => empty($info['exists']) || ! empty($info['missing_columns']) || ! empty($info['error']))
            ->keys()
            ->all();

        $indexed = 0;
        try {
            $indexed = \App\Models\AiSearchIndex::query()->count();
        } catch (Throwable) {
            $missing[] = 'ai_search_index(غير قابل للقراءة)';
        }

        return response()->json(['data' => [
            'assistant_enabled' => $this->settings->enabled(),
            'healthy' => $missing === [] && $this->settings->enabled(),
            'tables_ready' => $missing === [],
            'missing' => $missing,
            'indexed_properties' => $indexed,
            'latency_ms' => null,
            'engine' => 'deterministic',
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

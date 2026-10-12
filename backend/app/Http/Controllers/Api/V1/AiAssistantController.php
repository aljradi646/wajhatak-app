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
use App\Services\AI\AiLlmClient;
use App\Services\AI\AiPropertySearchService;
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
        private readonly AiLlmClient $llm,
        private readonly AiPropertySearchService $propertySearch,
    ) {}

    /** POST /api/v1/ai/chat — رسالة كاملة مع توليد رد ونتائج حقيقية. */
    public function chat(AiChatRequest $request): JsonResponse
    {
        return $this->processChat($request);
    }

    /**
     * تنفيذ دورة المحادثة المشتركة بين JSON وSSE.
     *
     * @param callable(string): void|null $onDelta يُستدعى فقط مع نص الرد النهائي الآمن.
     */
    private function processChat(AiChatRequest $request, ?callable $onDelta = null): JsonResponse
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

        $sessionToken = trim((string) $request->input('session_token', ''));
        $conversation = null;

        if ($id = $request->integer('conversation_id')) {
            $conversation = AiConversation::query()->find($id);

            // نفس الرد للمحادثة غير الموجودة أو التي لا يملكها صاحب الطلب؛ لا
            // نعيد محاولة فتح محادثة أخرى عند تقديم معرّف صريح غير مخوّل.
            if (! $conversation) {
                return response()->json(['message' => 'المحادثة غير متاحة.'], 404);
            }

            if ($user !== null) {
                if ((int) $conversation->user_id !== (int) $user->id) {
                    return response()->json(['message' => 'المحادثة غير متاحة.'], 404);
                }
            } else {
                $storedToken = (string) ($conversation->session_token ?? '');

                // مفاتيح جلسات الزوار يصدرها الخادم فقط (32 بايت عشوائية بصيغة hex).
                // لا نقبل رموزًا قديمة قصيرة أو رموزًا يختارها العميل.
                if ($conversation->user_id !== null
                    || ! $this->isValidGuestSessionToken($sessionToken)
                    || $storedToken === ''
                    || ! hash_equals($storedToken, $sessionToken)) {
                    return response()->json(['message' => 'المحادثة غير متاحة.'], 404);
                }
            }
        }

        if ($conversation === null) {
            // رمز الجلسة مُعرّف بحث فقط؛ لا يصبح سرّ ملكية جديدًا يختاره العميل.
            // عند غيابه/عدم صلاحيته يصدر AiConversationService رمزًا قويًا من الخادم.
            try {
                $guestSessionToken = $user === null && $this->isValidGuestSessionToken($sessionToken)
                    ? $sessionToken
                    : null;
                $conversation = $this->conversations->currentFor(
                    $user,
                    (string) $request->input('locale', 'ar'),
                    $guestSessionToken,
                );
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
            $onDelta,
        );

        return response()->json(['data' => $result]);
    }


    /** POST /api/v1/ai/chat/stream — يبث النص النهائي فور وصوله من مزود النموذج. */
    public function streamChat(AiChatRequest $request)
    {
        return response()->stream(function () use ($request): void {
            $send = static function (string $event, array $payload): void {
                echo 'event: '.$event."\n";
                echo 'data: '.json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n\n";
                if (ob_get_level() > 0) @ob_flush();
                @flush();
            };

            $send('start', ['event' => 'start']);
            $streamedCharacters = 0;
            $nextId = 1;

            try {
                $json = $this->processChat($request, function (string $delta) use ($send, &$streamedCharacters, &$nextId): void {
                    if (connection_aborted() || $delta === '') {
                        return;
                    }

                    $streamedCharacters += mb_strlen($delta);
                    $send('delta', ['event' => 'delta', 'id' => $nextId++, 'delta' => $delta]);
                });

                if ($json->getStatusCode() >= 400) {
                    $error = $json->getData(true);
                    $send('error', [
                        'event' => 'error',
                        'message' => (string) ($error['message'] ?? 'تعذر تنفيذ طلب المساعد.'),
                        'status_code' => $json->getStatusCode(),
                    ]);
                    return;
                }

                $payload = $json->getData(true);
                $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
                $reply = trim((string) ($data['reply'] ?? ''));

                // الردود الحتمية أو الرجوع الآمن لا تحتاج إلى اتصال نموذج متدفق.
                // في هذه الحالة نرسل الرد هنا بدل ترك العميل ينتظر حدث done فارغًا.
                if ($streamedCharacters === 0 && $reply !== '') {
                    $chunks = preg_split('/(?<=\\s|[،.!؟\\n])/u', $reply, -1, PREG_SPLIT_NO_EMPTY) ?: [$reply];
                    foreach ($chunks as $chunk) {
                        if (connection_aborted()) {
                            return;
                        }
                        $send('delta', ['event' => 'delta', 'id' => $nextId++, 'delta' => $chunk]);
                    }
                }

                if (! connection_aborted()) {
                    $send('done', ['event' => 'done', 'id' => $nextId, 'data' => $data]);
                }
            } catch (Throwable $e) {
                report($e);
                if (! connection_aborted()) {
                    $send('error', [
                        'event' => 'error',
                        'message' => 'تعذر إكمال رد المساعد. حاول مرة أخرى.',
                        'status_code' => 500,
                    ]);
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=utf-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
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
            ->get(['id', 'locale', 'status', 'title', 'is_pinned', 'last_message_at', 'created_at']);

        return response()->json(['data' => $items->map(fn (AiConversation $c) => [
            'id' => $c->id,
            'title' => $c->title ?: 'محادثة جديدة',
            'is_pinned' => (bool) $c->is_pinned,
            'messages_count' => $c->messages_count,
            'last_message_at' => optional($c->last_message_at)->toISOString(),
            'created_at' => optional($c->created_at)->toISOString(),
        ])]);
    }

    /** POST /api/v1/ai/conversations — إنشاء محادثة جديدة صريحة. */
    public function createConversation(Request $request): JsonResponse
    {
        $user=$request->user();
        abort_unless($user,401,'يلزم تسجيل الدخول.');
        $conversation=$this->conversations->currentFor($user,(string)$request->input('locale','ar'));
        $conversation->update(['status'=>'archived','last_message_at'=>now()]);
        $fresh=$this->conversations->currentFor($user,(string)$request->input('locale','ar'));
        return response()->json(['data'=>['id'=>$fresh->id,'title'=>$fresh->title,'is_pinned'=>(bool)$fresh->is_pinned]],201);
    }

    /** PATCH /api/v1/ai/conversations/{id}/pin — تثبيت/إلغاء تثبيت. */
    public function pin(Request $request,AiConversation $conversation): JsonResponse
    {
        $user=$request->user();
        abort_unless($user,401,'يلزم تسجيل الدخول.');
        abort_if($conversation->user_id!==$user->id,403,'غير مصرح للوصول لهذه المحادثة.');
        $conversation->update(['is_pinned'=>$request->boolean('pinned')]);
        return response()->json(['data'=>['id'=>$conversation->id,'is_pinned'=>(bool)$conversation->is_pinned]]);
    }

    /** GET /api/v1/ai/conversations/{id} — رسائل محادثة (ملكيتها فقط). */
    public function show(Request $request, AiConversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'يلزم تسجيل الدخول.');
        abort_if($conversation->user_id !== $user->id, 403, 'غير مصرح للوصول لهذه المحادثة.');

        $messages = $conversation->messages()
            ->where('status', '!=', 'blocked')
            ->orderBy('id')
            ->limit((int) $this->settings->get('ai_max_messages', 50))
            ->get(['id', 'role', 'content', 'property_ids', 'response_type', 'metadata', 'created_at']);

        $ids = $messages->flatMap(fn ($m) => (array) ($m->property_ids ?? []))->map(fn ($id) => (int) $id)->unique()->values()->all();
        $propertyMap = collect($ids ? $this->propertySearch->detailsMany($ids) : [])->keyBy('property_id');

        return response()->json(['data' => [
            'id' => $conversation->id,
            'messages' => $messages->map(function ($m) use ($propertyMap) {
                $type = $m->response_type ?: 'text';
                $allowed = in_array($type, ['property_results', 'property_detail'], true);
                $properties = $allowed
                    ? collect((array) ($m->property_ids ?? []))
                        ->map(fn ($id) => $propertyMap->get((int) $id))
                        ->filter()
                        ->values()
                        ->all()
                    : [];

                return [
                    'id' => $m->id,
                    'role' => $m->role,
                    'content' => $m->content,
                    'response_type' => $type,
                    'properties' => $properties,
                    'actions' => data_get($m->metadata, 'actions', []),
                    'property_ids' => $m->property_ids,
                    'created_at' => optional($m->created_at)->toISOString(),
                ];
            }),
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

    /** POST /api/v1/ai/messages/{message}/feedback — تقييم رسالة المساعد. */
    public function feedback(Request $request, int $message): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        abort_unless($user, 401, 'يلزم تسجيل الدخول.');

        $value = (string) $request->input('feedback', '');
        abort_unless(in_array($value, ['helpful', 'not_helpful'], true), 422, 'قيمة التقييم غير صالحة.');

        $aiMessage = \App\Models\AiMessage::query()
            ->whereKey($message)
            ->where('role', 'assistant')
            ->whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))
            ->firstOrFail();

        $feedback = \App\Models\AiMessageFeedback::query()->updateOrCreate(
            ['ai_message_id' => $aiMessage->id, 'user_id' => $user->id],
            ['feedback' => $value, 'note' => null],
        );

        return response()->json([
            'data' => [
                'message_id' => $aiMessage->id,
                'feedback' => $feedback->feedback,
            ],
        ]);
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
            'engine' => $this->llm->configured() ? 'llm_agent' : 'rule_fallback',
            'llm' => $this->llm->health(),
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

    private function isValidGuestSessionToken(string $token): bool
    {
        return preg_match('/^[a-f0-9]{64}$/iD', $token) === 1;
    }

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

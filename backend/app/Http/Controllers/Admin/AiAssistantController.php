<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiRequestLog;
use App\Models\Setting;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiConversationService;
use App\Services\AI\AiLoggingService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\AiSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * قسم المساعد الذكي في لوحة التحكم: إعدادات + مراقبة + سجل + محادثة اختبار حقيقية
 * تستدعي نفس AiAssistantService الذي يستخدمه التطبيق — نفس الحواجز والبحث والتوليد.
 */
class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderManager $providers,
        private readonly AiLoggingService $logging,
        private readonly AiAssistantService $assistant,
        private readonly AiConversationService $conversations,
    ) {}

    public function index(Request $request)
    {
        $values = $this->settings->all();
        $stats = $this->logging->stats();
        $health = $this->providers->provider()->health();

        $logs = AiRequestLog::query()
            ->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->paginate(25);

        return view('admin.ai.index', [
            'values' => $values,
            'stats' => $stats,
            'health' => $health,
            'logs' => $logs,
            'providers' => $this->providers->available(),
        ]);
    }

    public function update(Request $request)
    {
        // مفاتيح مسموحة فقط — أي مفتاح آخر يُتجاهل (منع العبث من النموذج).
        $allowed = [
            'ai_enabled', 'ai_assistant_name', 'ai_welcome_message', 'ai_default_language',
            'ai_provider', 'ai_model', 'ai_inference_endpoint', 'ai_temperature', 'ai_max_tokens',
            'ai_context_window', 'ai_timeout',
            'ai_system_prompt', 'ai_personality', 'ai_response_style', 'ai_max_results',
            'ai_min_match_score', 'ai_allow_comparison', 'ai_allow_recommendations', 'ai_allow_followups',
            'ai_scope_properties', 'ai_scope_locations', 'ai_scope_features', 'ai_scope_availability', 'ai_scope_faq',
            'ai_guard_domain_restriction', 'ai_guard_hallucination', 'ai_guard_prompt_injection',
            'ai_guard_sensitive_data', 'ai_guard_system_prompt', 'ai_out_of_scope_response',
            'ai_semantic_ranking', 'ai_similarity_threshold', 'ai_max_candidates', 'ai_sort_strategy',
            'ai_default_search_radius_km',
            'ai_history_enabled', 'ai_history_retention_days', 'ai_max_messages', 'ai_clear_policy',
        ];

        $this->settings->putMany($request->only($allowed));

        // مسح كاش الإعدادات العامة لضمان سريان التغييرات فورًا.
        Setting::forget('ai_provider');
        Setting::forget('ai_model');
        Setting::forget('ai_inference_endpoint');

        \App\Models\ActivityLog::record('ai', 'تم تحديث إعدادات المساعد الذكي');

        return back()->with('status', 'تم حفظ إعدادات المساعد الذكي بنجاح.');
    }

    /** POST /admin/ai/reindex — إعادة بناء فهرس البحث يدويًا. */
    public function reindex()
    {
        $count = app(\App\Services\AI\AiIndexSyncService::class)->reindexAll();
        \App\Models\ActivityLog::record('ai', "إعادة فهرسة بحث المساعد: {$count} عقار");

        return back()->with('status', "تمت مزامنة {$count} عقار مع فهرس المساعد الذكي.");
    }

    // =====================================================================
    // محادثة اختبار حقيقية (Playground) — نفس مسار التطبيق تمامًا.
    // =====================================================================

    /** GET /admin/ai/playground — صفحة اختبار المساعد بالحوار الحقيقي. */
    public function playground(Request $request)
    {
        $conversationId = $request->session()->get('ai_playground_conversation_id');
        $messages = collect();

        if ($conversationId) {
            $conversation = AiConversation::query()->find($conversationId);
            if ($conversation) {
                $messages = $conversation->messages()
                    ->where('status', '!=', 'blocked')
                    ->get(['id', 'role', 'content', 'property_ids', 'created_at'])
                    ->map(fn ($m) => [
                        'id' => $m->id,
                        'role' => $m->role,
                        'content' => $m->content,
                        'property_ids' => $m->property_ids ?? [],
                        'time' => $m->created_at?->format('H:i'),
                    ]);
            }
        }

        return view('admin.ai.playground', [
            'messages' => $messages,
            'health' => $this->providers->provider()->health(),
            'enabled' => $this->settings->enabled(),
            'assistantName' => $this->settings->assistantName(),
        ]);
    }

    /** POST /admin/ai/playground/send — إرسال رسالة عبر المحرك الحقيقي نفسه. */
    public function playgroundSend(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:600'],
        ]);

        if (! $this->settings->enabled()) {
            return response()->json([
                'reply' => 'المساعد غير متاح حاليًا (معطل من الإعدادات). فعّله من تبويب الإعدادات أولًا.',
                'status' => 'disabled',
                'properties' => [],
            ], 503);
        }

        $admin = $request->user();

        // نفس معدل التطبيق — اللوحة ليست استثناءً.
        if (! RateLimiter::attempt('ai-admin:'.$admin->id, (int) config('ai.limits.rate_limit_per_min', 10), fn () => true, 60)) {
            return response()->json(['reply' => 'طلبات كثيرة. انتظر دقيقة ثم أعد المحاولة.', 'status' => 'rate_limited', 'properties' => []], 429);
        }

        // محادثة اختبار مرتبطة بحساب المدير نفسه (نفس التخزين والسياق).
        $conversationId = $request->session()->get('ai_playground_conversation_id');
        $conversation = $conversationId
            ? AiConversation::query()->where('id', $conversationId)->where('user_id', $admin->id)->first()
            : null;
        $conversation ??= $this->conversations->currentFor($admin, 'ar');
        $request->session()->put('ai_playground_conversation_id', $conversation->id);

        // ✅ نفس الاستدعاء الحرفي الذي يستخدمه التطبيق: حواجز + نية + بحث حقيقي + نموذج + grounding.
        $result = $this->assistant->handleChat($admin, $data['message'], $conversation, 'ar');

        return response()->json(['data' => [
            'reply' => $result['reply'],
            'status' => $result['status'],
            'properties' => array_map(fn ($p) => [
                'property_id' => $p['property_id'],
                'title' => $p['title'],
                'price' => $p['price'],
                'currency' => $p['currency'],
                'city' => $p['city'],
                'district' => $p['district'] ?? null,
                'bedrooms' => $p['bedrooms'] ?? null,
                'available' => $p['available'] ?? true,
            ], $result['properties'] ?? []),
            'filters' => $result['filters'] ?? [],
        ]]);
    }

    /** POST /admin/ai/playground/clear — مسح محادثة الاختبار. */
    public function playgroundClear(Request $request)
    {
        $conversationId = $request->session()->get('ai_playground_conversation_id');
        if ($conversationId) {
            $conversation = AiConversation::query()->find($conversationId);
            if ($conversation && $conversation->user_id === $request->user()->id) {
                $this->conversations->clear($conversation, (string) $this->settings->get('ai_clear_policy', 'soft'));
            }
        }
        $request->session()->forget('ai_playground_conversation_id');

        return redirect()->route('admin.ai.playground')->with('status', 'تم مسح محادثة الاختبار.');
    }
}

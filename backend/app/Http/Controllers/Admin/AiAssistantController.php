<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiRequestLog;
use App\Models\AiSearchIndex;
use App\Models\Property;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiConversationService;
use App\Services\AI\AiHealthStatus;
use App\Services\AI\AiLoggingService;
use App\Services\AI\AiSchemaService;
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
        private readonly AiLoggingService $logging,
        private readonly AiAssistantService $assistant,
        private readonly AiConversationService $conversations,
        private readonly AiSchemaService $schema,
    ) {}

    public function index(Request $request)
    {
        $values = $this->settings->all();
        $stats = $this->logging->stats();

        // تشخيص حقيقي (لا شعارات): هل جداول المساعد وأعمدته موجودة؟ وهل الفهرس
        // يطابق العقارات الفعلية؟ هذا ما يجعل سبب أي خلل ظاهرًا في اللوحة.
        $diagnostics = $this->schema->diagnose();
        $schemaProblems = collect($diagnostics)
            ->filter(fn (array $info) => empty($info['exists']) || ! empty($info['missing_columns']) || ! empty($info['error']))
            ->keys()
            ->all();

        $indexed = $this->safeCount(AiSearchIndex::class);
        $published = $this->safeCount(Property::class, fn ($q) => $q->where('status', 'published'));
        $needsReindex = $schemaProblems === [] && $indexed === 0 && $published > 0;

        $health = new AiHealthStatus(
            healthy: $schemaProblems === [] && $this->settings->enabled(),
            provider: 'deterministic',
            message: $schemaProblems !== []
                ? 'جداول/أعمدة ناقصة: '.implode('، ', $schemaProblems).' — اضغط «إصلاح المخطط» أو نفّذ php artisan ai:doctor --fix'
                : ($needsReindex
                    ? 'الفهرس فارغ رغم وجود '.$published.' عقارًا منشورًا — اضغط «إعادة بناء الفهرس الآن»'
                    : 'المحرك الحتمي يعمل على الخادم مباشرة — جاهز.'),
            details: [
                'tables' => $diagnostics,
                'indexed_properties' => $indexed,
                'published_properties' => $published,
                'schema_problems' => $schemaProblems,
            ],
        );

        $logs = AiRequestLog::query()
            ->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->paginate(25);

        return view('admin.ai.index', [
            'values' => $values,
            'stats' => $stats,
            'health' => $health,
            'diagnostics' => $diagnostics,
            'rules' => $this->rulesReport(),
            'logs' => $logs,
            'providers' => ['deterministic'],
        ]);
    }

    /** إصلاح ذاتي لمخطط المساعد من اللوحة (نفس ما ينفذه ai:doctor --fix). */
    public function repair()
    {
        $result = app(\App\Services\AI\AiSchemaService::class)->ensure(force: true);

        \App\Models\ActivityLog::record('ai', 'تشخيص وإصلاح مخطط المساعد');

        $message = 'تم الفحص. جداول أُنشئت: '.count($result['created'])
            .'، أعمدة أُضيفت: '.count($result['columns'])
            .'، عقارات فُهرست: '.$result['indexed'].'.';

        return back()->with($result['errors'] === [] ? 'status' : 'error', $message.implode(' ', $result['errors']));
    }

    /** تشغيل سريع لقواعد الفهم على عبارات عربية للتأكد من سلامة المحرك. */
    private function rulesReport(): array
    {
        $probes = [
            'ابحث لي عن شقة للايجار في صنعاء',
            'شقة ثلاث غرف في حدة أقل من 80 ألف',
            'فيلا للبيع في عدن بميزانية 50 مليون',
            'شقة غير مفروش',
        ];

        return collect($probes)->mapWithKeys(function (string $probe) {
            try {
                $parsed = app(\App\Services\AI\AiIntentService::class)->parse($probe);

                return [$probe => $parsed['filters']];
            } catch (\Throwable $e) {
                return [$probe => ['error' => $e->getMessage()]];
            }
        })->all();
    }

    private function safeCount(string $model, ?callable $constraint = null): int
    {
        try {
            $query = $model::query();
            if ($constraint) {
                $constraint($query);
            }

            return $query->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function update(Request $request)
    {
        // مفاتيح مسموحة فقط — أي مفتاح آخر يُتجاهل (منع العبث من النموذج).
        $allowed = [
            'ai_enabled', 'ai_assistant_name', 'ai_welcome_message', 'ai_default_language',
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
            'health' => new AiHealthStatus(true, 'deterministic', null, null, 'المحرك الحتمي جاهز.'),
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
            // المرحلة التي فشلت (وضع التصحيح) — تشخيص فوري داخل صفحة الاختبار.
            'failed_stage' => $result['failed_stage'] ?? null,
            'error' => $result['error'] ?? null,
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

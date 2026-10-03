<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AiConversation;
use App\Models\AiRequestLog;
use App\Models\AiSearchIndex;
use App\Models\Property;
use App\Services\AI\AiAssistantService;
use App\Services\AI\AiConversationService;
use App\Services\AI\AiHealthStatus;
use App\Services\AI\AiIndexSyncService;
use App\Services\AI\AiIntentService;
use App\Services\AI\AiLoggingService;
use App\Services\AI\AiSchemaService;
use App\Services\AI\AiSettingsService;
use App\Services\AI\AiLlmClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * قسم المساعد الذكي في لوحة التحكم: إعدادات + مراقبة + سجل + محادثة اختبار حقيقية
 * تستدعي نفس AiAssistantService الذي يستخدمه التطبيق — نفس الحواجز والبحث والتوليد.
 */
class AiAssistantController extends Controller
{
    /**
     * أقسام إعدادات المساعد — كل قسم صفحة مستقلة بمفاتيحه الخاصة.
     * هذا ما يمنع «النموذج الضخم» الذي يجمع كل شيء في صفحة واحدة.
     *
     * @var array<string, array{label: string, description: string, fields: array<int, array<string, mixed>>}>
     */
    public const SETTINGS_SECTIONS = [
        'general' => [
            'label' => 'إعدادات المساعد',
            'description' => 'تفعيل المساعد وهويته ولغته الافتراضية في التطبيق.',
            'fields' => [
                ['key' => 'ai_enabled', 'label' => 'تفعيل المساعد الذكي', 'type' => 'boolean'],
                ['key' => 'ai_assistant_name', 'label' => 'اسم المساعد', 'type' => 'text', 'rules' => ['required', 'string', 'max:60']],
                ['key' => 'ai_welcome_message', 'label' => 'رسالة الترحيب', 'type' => 'textarea', 'rows' => 3, 'rules' => ['required', 'string', 'max:1000']],
                ['key' => 'ai_default_language', 'label' => 'اللغة الافتراضية', 'type' => 'select', 'options' => ['ar' => 'العربية', 'en' => 'English'], 'rules' => ['required', 'in:ar,en']],
            ],
        ],
        'behavior' => [
            'label' => 'التعليمات والسلوك',
            'description' => 'شخصية المساعد وأسلوب الرد وحدود البحث. قواعد السلامة الإلزامية محمية في الكود ولا يمكن تعطيلها.',
            'fields' => [
                ['key' => 'ai_system_prompt', 'label' => 'توجيهات إضافية للنظام (اختياري)', 'type' => 'textarea', 'rows' => 3, 'rules' => ['nullable', 'string', 'max:4000']],
                ['key' => 'ai_personality', 'label' => 'شخصية المساعد', 'type' => 'textarea', 'rows' => 2, 'rules' => ['nullable', 'string', 'max:1000']],
                ['key' => 'ai_response_style', 'label' => 'أسلوب الرد', 'type' => 'select', 'options' => ['concise' => 'مختصر', 'detailed' => 'مفصّل'], 'rules' => ['required', 'in:concise,detailed']],
                ['key' => 'ai_max_results', 'label' => 'أقصى عدد نتائج بحث', 'type' => 'number', 'min' => 1, 'max' => 6, 'rules' => ['required', 'integer', 'min:1', 'max:6']],
                ['key' => 'ai_min_match_score', 'label' => 'أدنى درجة مطابقة (0 - 1)', 'type' => 'number', 'step' => 0.01, 'min' => 0, 'max' => 1, 'rules' => ['required', 'numeric', 'min:0', 'max:1']],
                ['key' => 'ai_allow_comparison', 'label' => 'السماح بالمقارنة', 'type' => 'boolean'],
                ['key' => 'ai_allow_recommendations', 'label' => 'السماح بالتوصيات', 'type' => 'boolean'],
                ['key' => 'ai_allow_followups', 'label' => 'أسئلة المتابعة', 'type' => 'boolean'],
            ],
        ],
        'knowledge' => [
            'label' => 'المعرفة',
            'description' => 'مصادر البيانات المسموح للمساعد بالاعتماد عليها. أي مصدر غير مُفعّل يمتنع المساعد عن الإجابة منه.',
            'fields' => [
                ['key' => 'ai_scope_properties', 'label' => 'العقارات', 'type' => 'boolean'],
                ['key' => 'ai_scope_locations', 'label' => 'المواقع', 'type' => 'boolean'],
                ['key' => 'ai_scope_features', 'label' => 'المزايا والخصائص', 'type' => 'boolean'],
                ['key' => 'ai_scope_availability', 'label' => 'التوفر', 'type' => 'boolean'],
                ['key' => 'ai_scope_faq', 'label' => 'أسئلة المنصة الشائعة', 'type' => 'boolean'],
            ],
        ],
        'search' => [
            'label' => 'البحث والترتيب',
            'description' => 'استراتيجية ترتيب النتائج وحدود المترشحين ونصف قطر البحث الجغرافي.',
            'fields' => [
                ['key' => 'ai_semantic_ranking', 'label' => 'تمكين الترتيب الدلالي', 'type' => 'boolean'],
                ['key' => 'ai_similarity_threshold', 'label' => 'عتبة التشابه (0 - 1)', 'type' => 'number', 'step' => 0.01, 'min' => 0, 'max' => 1, 'rules' => ['required', 'numeric', 'min:0', 'max:1']],
                ['key' => 'ai_max_candidates', 'label' => 'أقصى عدد مترشحين', 'type' => 'number', 'min' => 10, 'max' => 60, 'rules' => ['required', 'integer', 'min:10', 'max:60']],
                ['key' => 'ai_sort_strategy', 'label' => 'استراتيجية الترتيب', 'type' => 'select', 'options' => ['relevance' => 'الأكثر ملاءمة', 'price_asc' => 'الأرخص أولًا', 'price_desc' => 'الأغلى أولًا'], 'rules' => ['required', 'in:relevance,price_asc,price_desc']],
                ['key' => 'ai_default_search_radius_km', 'label' => 'نصف قطر البحث الافتراضي (كم)', 'type' => 'number', 'min' => 1, 'max' => 100, 'rules' => ['required', 'integer', 'min:1', 'max:100']],
            ],
        ],
        'security' => [
            'label' => 'الأمان والحواجز',
            'description' => 'حماية ضد الهلوسة وحقن التعليمات وتسرب البيانات. الحواجز الأساسية مفروضة في الكود ولا يمكن تعطيلها.',
            'fields' => [
                ['key' => 'ai_guard_domain_restriction', 'label' => 'قصر النطاق على العقارات', 'type' => 'boolean'],
                ['key' => 'ai_guard_hallucination', 'label' => 'حماية من الهلوسة (إلزامي)', 'type' => 'forced'],
                ['key' => 'ai_guard_prompt_injection', 'label' => 'حماية من حقن التعليمات (إلزامي)', 'type' => 'forced'],
                ['key' => 'ai_guard_sensitive_data', 'label' => 'حماية البيانات الحساسة (إلزامي)', 'type' => 'forced'],
                ['key' => 'ai_out_of_scope_response', 'label' => 'رد الطلبات خارج النطاق', 'type' => 'textarea', 'rows' => 2, 'rules' => ['required', 'string', 'max:1000']],
            ],
        ],
        'conversations' => [
            'label' => 'المحادثات والذاكرة',
            'description' => 'سياق الحوار ومدة الإبقاء وسياسة المسح. تُطبّق هذه القيم على محادثات التطبيق كلها.',
            'fields' => [
                ['key' => 'ai_history_enabled', 'label' => 'تمكين سجل المحادثة', 'type' => 'boolean'],
                ['key' => 'ai_history_retention_days', 'label' => 'مدة الإبقاء (أيام)', 'type' => 'number', 'min' => 1, 'max' => 365, 'rules' => ['required', 'integer', 'min:1', 'max:365']],
                ['key' => 'ai_max_messages', 'label' => 'أقصى رسائل محفوظة لكل محادثة', 'type' => 'number', 'min' => 10, 'max' => 200, 'rules' => ['required', 'integer', 'min:10', 'max:200']],
                ['key' => 'ai_clear_policy', 'label' => 'سياسة المسح', 'type' => 'select', 'options' => ['soft' => 'أرشفة (soft)', 'hard' => 'حذف نهائي (hard)'], 'rules' => ['required', 'in:soft,hard']],
            ],
        ],
    ];

    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiLoggingService $logging,
        private readonly AiAssistantService $assistant,
        private readonly AiConversationService $conversations,
        private readonly AiSchemaService $schema,
        private readonly AiLlmClient $llm,
    ) {}

    /** GET /admin/ai — نظرة عامة: حالة المحرك والإحصاءات وروابط الأقسام. */
    public function index()
    {
        return view('admin.ai.index', [
            'stats' => $this->logging->stats(),
            'health' => $this->healthStatus(),
            'sections' => self::SETTINGS_SECTIONS,
            'enabled' => $this->settings->enabled(),
            'assistantName' => $this->settings->assistantName(),
        ]);
    }

    /** GET /admin/ai/settings/{section} — صفحة مستقلة لكل قسم إعدادات. */
    public function settings(string $section = 'general')
    {
        abort_unless(array_key_exists($section, self::SETTINGS_SECTIONS), 404);

        return view('admin.ai.settings', [
            'section' => $section,
            'definition' => self::SETTINGS_SECTIONS[$section],
            'sections' => self::SETTINGS_SECTIONS,
            'values' => $this->settings->all(),
            'enabled' => $this->settings->enabled(),
        ]);
    }

    /** POST /admin/ai/settings/{section} — حفظ قسم واحد فقط بعد التحقق من مدخلاته. */
    public function updateSection(Request $request, string $section)
    {
        abort_unless(array_key_exists($section, self::SETTINGS_SECTIONS), 404);

        $fields = self::SETTINGS_SECTIONS[$section]['fields'];

        $rules = [];
        foreach ($fields as $field) {
            $type = $field['type'] ?? 'text';
            if ($type === 'forced') {
                continue;
            }
            $rules[$field['key']] = $type === 'boolean'
                ? ['sometimes', 'boolean']
                : ($field['rules'] ?? ['nullable', 'string']);
        }

        $data = $request->validate($rules, [], array_combine(
            array_map(fn ($f) => $f['key'], $fields),
            array_map(fn ($f) => $f['label'], $fields),
        ));

        $booleans = array_values(array_map(
            fn ($f) => $f['key'],
            array_filter($fields, fn ($f) => ($f['type'] ?? '') === 'boolean'),
        ));

        $this->settings->putMany($data, $booleans);

        $label = self::SETTINGS_SECTIONS[$section]['label'];
        ActivityLog::record('ai', "تم تحديث قسم «{$label}» في إعدادات المساعد الذكي");

        return redirect()
            ->route('admin.ai.settings', ['section' => $section])
            ->with('status', "تم حفظ إعدادات «{$label}».");
    }

    /** GET /admin/ai/monitoring — حالة المخطط والفهم وإعادة بناء الفهرس. */
    public function monitoring()
    {
        return view('admin.ai.monitoring', [
            'health' => $this->healthStatus(),
            'rules' => $this->rulesReport(),
            'sections' => self::SETTINGS_SECTIONS,
        ]);
    }

    /** GET /admin/ai/stats — الإحصاءات التشغيلية. */
    public function stats()
    {
        return view('admin.ai.stats', [
            'stats' => $this->logging->stats(),
            'sections' => self::SETTINGS_SECTIONS,
        ]);
    }

    /** GET /admin/ai/logs — سجل طلبات المساعد. */
    public function logs(Request $request)
    {
        $logs = AiRequestLog::query()
            ->latest()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->paginate(25)
            ->withQueryString();

        return view('admin.ai.logs', [
            'logs' => $logs,
            'status' => $request->input('status'),
            'sections' => self::SETTINGS_SECTIONS,
        ]);
    }

    /** حالة صحة محرك المساعد — تشخيص حقيقي من المخطط والفهرس. */
    private function healthStatus(): AiHealthStatus
    {
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

        $llmHealth=$this->llm->health();
        $healthy=$schemaProblems===[] && $this->settings->enabled() && (!$this->llm->configured() || $llmHealth['reachable']);
        return new AiHealthStatus(
            healthy: $healthy,
            provider: $this->llm->configured() ? 'openai-compatible-llm' : 'rule-fallback',
            model: $llmHealth['model'] ?? null,
            latencyMs: $llmHealth['latency_ms'] ?? null,
            message: $schemaProblems !== []
                ? 'جداول/أعمدة ناقصة: '.implode('، ', $schemaProblems)
                : (!$this->llm->configured()
                    ? ($needsReindex ? 'الفهرس يحتاج إعادة بناء.' : 'LLM غير مُعد؛ يعمل Rule Fallback.')
                    : (($llmHealth['reachable']??false) ? 'خادم LLM متاح والوكيل يعمل مع Tool Calling.' : 'خادم LLM غير متاح؛ سيُستخدم fallback إذا كان مفعّلًا.')),
            details: [
                'tables'=>$diagnostics,
                'indexed_properties'=>$indexed,
                'published_properties'=>$published,
                'schema_problems'=>$schemaProblems,
                'llm'=>$llmHealth,
            ],
        );
    }

    /** إصلاح ذاتي لمخطط المساعد من اللوحة (نفس ما ينفذه ai:doctor --fix). */
    public function repair()
    {
        $result = app(AiSchemaService::class)->ensure(force: true);

        ActivityLog::record('ai', 'تشخيص وإصلاح مخطط المساعد');

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
                $parsed = app(AiIntentService::class)->parse($probe);

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

    /** POST /admin/ai/reindex — إعادة بناء فهرس البحث يدويًا. */
    public function reindex()
    {
        $count = app(AiIndexSyncService::class)->reindexAll();
        ActivityLog::record('ai', "إعادة فهرسة بحث المساعد: {$count} عقار");

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

        // صفحة المحادثة لا تحتاج إلى تشخيص شامل أو طلب شبكة إلى خادم LLM عند
        // فتحها. التشخيص موجود في صفحة «المراقبة» حتى لا يجعل تعطل inference
        // أو cache قاعدة البيانات صفحة الاختبار نفسها تُرجع 500.
        return view('admin.ai.playground', [
            'messages' => $messages,
            'enabled' => $this->settings->enabled(),
            'assistantName' => $this->settings->assistantName(),
            'sections' => self::SETTINGS_SECTIONS,
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

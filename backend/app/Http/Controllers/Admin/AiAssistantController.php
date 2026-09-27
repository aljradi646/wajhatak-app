<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiRequestLog;
use App\Models\Setting;
use App\Services\AI\AiLoggingService;
use App\Services\AI\AiProviderManager;
use App\Services\AI\AiSettingsService;
use Illuminate\Http\Request;

/**
 * قسم المساعد الذكي في لوحة التحكم: إعدادات كاملة + مراقبة + سجل طلبات.
 */
class AiAssistantController extends Controller
{
    public function __construct(
        private readonly AiSettingsService $settings,
        private readonly AiProviderManager $providers,
        private readonly AiLoggingService $logging,
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
}

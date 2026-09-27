<?php

// =============================================================================
// إعدادات المساعد العقاري الذكي — وجهتك
//
// القيم هنا هي *قيم افتراضية للإنتاج* فقط. الإدارة تتحكم بمعظمها من لوحة
// التحكم (تُخزن في جدول settings بمفاتيح ai_* عبر AiSettingsService)،
// والمتغيرات البيئية تُستخدم كقيم أولية لبيئة التشغيل.
//
// لا مفاتيح سرية سحابية: النموذج يُشغَّل ذاتيًا (self-hosted) ويتصل به
// Laravel عبر شبكة داخلية فقط. Flutter لا يتصل بخادم النموذج مطلقًا.
// =============================================================================

return [

    // المحرك الافتراضي — يجب أن يكون مفتاحًا مسجلًا في AiProviderManager.
    'default_provider' => env('AI_PROVIDER', 'local_glm'),

    // نقطة النهاية للنموذج المحلي (OpenAI-compatible API).
    // 1) داخل الحاوية (الافتراضي الإنتاجي): llama-server الذي يشغّله
    //    scripts/ai_inference_service.sh على 127.0.0.1:8018 — نفس نمط عامل الطابور.
    // 2) خادم خارجي: vLLM http://<ai-server>:8000/v1 · Ollama :11434/v1
    'inference' => [
        'base_url'         => env('AI_INFERENCE_BASE_URL', 'http://127.0.0.1:8018/v1'),
        'model'            => env('AI_MODEL', 'glm-4.6'),
        // مفتاح اختياري لخادم الاستدلال الخاص بك (يبقى داخل الشبكة ولا يُرسل للعميل أبدًا).
        'api_key'          => env('AI_INFERENCE_API_KEY'),
        'timeout'          => (int) env('AI_TIMEOUT', 30),
        'connect_timeout'  => (int) env('AI_CONNECT_TIMEOUT', 5),
        'temperature'      => (float) env('AI_TEMPERATURE', 0.3),
        'max_tokens'       => (int) env('AI_MAX_TOKENS', 700),
        'context_window'   => (int) env('AI_CONTEXT_WINDOW', 8192),
    ],

    // حدود الحوار والبحث (حد عليا لا يمكن للإدارة تجاوزه).
    'limits' => [
        'max_message_length'   => (int) env('AI_MAX_MESSAGE_LENGTH', 600),
        'max_results'          => (int) env('AI_MAX_RESULTS', 6),
        'max_candidates'       => (int) env('AI_MAX_CANDIDATES', 60),
        'max_followups'        => (int) env('AI_MAX_FOLLOWUPS', 2),
        'history_messages'     => (int) env('AI_HISTORY_MESSAGES', 8),
        'rate_limit_per_min'   => (int) env('AI_RATE_LIMIT_PER_MINUTE', 10),
        'rate_limit_search'    => (int) env('AI_RATE_LIMIT_SEARCH_PER_MINUTE', 30),
        'max_conversations'    => (int) env('AI_MAX_CONVERSATIONS', 50),
    ],

    // حد أدنى لدرجة مطابقة نتائج البحث قبل عرضها (0 - 1).
    'search' => [
        'min_score' => (float) env('AI_MIN_SCORE', 0.05),
    ],

];

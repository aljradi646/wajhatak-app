<?php

// =============================================================================
// إعدادات المساعد العقاري الذكي — وجهتك
//
// المحرك حتمي 100% داخل Laravel (AiReplyEngine + قواعد النية + بحث حقيقي):
// لا نموذج لغوي ولا مزود خارجي ولا أي تنزيلات — يعمل فورًا على أي استضافة.
// الإدارة تتحكم بالسلوك من لوحة التحكم (جدول settings بمفاتيح ai_*).
// =============================================================================

return [

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

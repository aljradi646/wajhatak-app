<?php

return [
    'enabled' => (bool) env('AI_ENABLED', true),
    'allow_rule_fallback' => (bool) env('AI_ALLOW_RULE_FALLBACK', true),

    'limits' => [
        'max_message_length' => (int) env('AI_MAX_MESSAGE_LENGTH', 2000),
        'max_results' => (int) env('AI_MAX_RESULTS', 6),
        'max_candidates' => (int) env('AI_MAX_CANDIDATES', 60),
        'max_followups' => (int) env('AI_MAX_FOLLOWUPS', 2),
        'history_messages' => (int) env('AI_HISTORY_MESSAGES', 12),
        'rate_limit_per_min' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 10),
        'rate_limit_search' => (int) env('AI_RATE_LIMIT_SEARCH_PER_MINUTE', 30),
        'max_conversations' => (int) env('AI_MAX_CONVERSATIONS', 50),
    ],

    'search' => [
        'min_score' => (float) env('AI_MIN_SCORE', 0.05),
    ],

    'llm' => [
        'enabled' => (bool) env('AI_LLM_ENABLED', false),
        'base_url' => env('AI_LLM_BASE_URL', 'http://127.0.0.1:11434/v1'),
        'api_key' => env('AI_LLM_API_KEY', ''),
        'model' => env('AI_LLM_MODEL', ''),
        'temperature' => (float) env('AI_LLM_TEMPERATURE', 0.2),
        'max_output_tokens' => (int) env('AI_LLM_MAX_OUTPUT_TOKENS', 1200),
        'max_tool_rounds' => (int) env('AI_LLM_MAX_TOOL_ROUNDS', 5),
        'timeout' => (int) env('AI_LLM_TIMEOUT', 45),
        'connect_timeout' => (int) env('AI_LLM_CONNECT_TIMEOUT', 5),
        'retries' => (int) env('AI_LLM_RETRIES', 1),
        'retry_delay_ms' => (int) env('AI_LLM_RETRY_DELAY_MS', 250),
    ],
];
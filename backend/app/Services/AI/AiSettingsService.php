<?php

namespace App\Services\AI;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * إعدادات المساعد القابلة للإدارة من لوحة التحكم — تُخزن في جدول settings
 * بمفاتيح ai_* وتُقرأ عبر هذه الخدمة فقط. قواعد السلامة الأساسية (قواعد
 * النظام غير القابلة للتغيير) لا يمكن تعطيلها من هنا أبدًا — انظر AiGuardrailService.
 */
class AiSettingsService
{
    private const CACHE_KEY = 'ai_settings_bundle_v1';

    private const CACHE_TTL = 300; // ثواني — يجعل تعديلات الإدارة تصل بسرعة مع حماية القاعدة.

    // المفاتيح المدعومة وقيمها الافتراضية. أي مفتاح آخر يُتجاهل.
    private const DEFAULTS = [
        // General
        'ai_enabled' => ['1', 'boolean'],
        'ai_assistant_name' => ['مساعد وجهتك', 'string'],
        'ai_welcome_message' => ['أهلًا بك! أنا مساعد وجهتك العقاري 🏡\nساعدك في إيجاد الشقة أو البيت أو الأرض المناسبة لك بالسعر والموقع الذي تريده.\nكيف أخدمك اليوم؟', 'text'],
        'ai_default_language' => ['ar', 'string'],

        // المحرك حتمي داخل الخادم — لا مفاتيح نموذج/مزود.

        // Behavior
        'ai_system_prompt' => ['', 'text'],
        'ai_personality' => ['ودود ومهني، جمل قصيرة، عربي واضح.', 'text'],
        'ai_response_style' => ['concise', 'string'],
        'ai_max_results' => ['6', 'string'],
        'ai_min_match_score' => ['0.05', 'string'],
        'ai_allow_comparison' => ['1', 'boolean'],
        'ai_allow_recommendations' => ['1', 'boolean'],
        'ai_allow_followups' => ['1', 'boolean'],

        // Scope (مصادر المعرفة المسموحة)
        'ai_scope_properties' => ['1', 'boolean'],
        'ai_scope_locations' => ['1', 'boolean'],
        'ai_scope_features' => ['1', 'boolean'],
        'ai_scope_availability' => ['1', 'boolean'],
        'ai_scope_faq' => ['1', 'boolean'],

        // Guardrails
        'ai_guard_domain_restriction' => ['1', 'boolean'],
        'ai_guard_hallucination' => ['1', 'boolean'],
        'ai_guard_prompt_injection' => ['1', 'boolean'],
        'ai_guard_sensitive_data' => ['1', 'boolean'],
        'ai_out_of_scope_response' => ['أنا مساعد وجهتك، ومتخصص في مساعدتك في البحث عن العقارات واستخدام منصة وجهتك.', 'text'],

        // Search
        'ai_semantic_ranking' => ['1', 'boolean'],
        'ai_similarity_threshold' => ['0.15', 'string'],
        'ai_max_candidates' => ['60', 'string'],
        'ai_sort_strategy' => ['relevance', 'string'],
        'ai_default_search_radius_km' => ['10', 'string'],

        // Conversation
        'ai_history_enabled' => ['1', 'boolean'],
        'ai_history_retention_days' => ['30', 'string'],
        'ai_max_messages' => ['50', 'string'],
        'ai_clear_policy' => ['soft', 'string'],
    ];

    /** قيم منطقية لا يمكن للإدارة تعطيلها (طبقة الحماية الإلزامية). */
    private const FORCED_TRUE = [
        'ai_guard_sensitive_data',   // لا تُكشف بيانات حساسة أبدًا.
        'ai_guard_hallucination',    // التحقق الأرضي إلزامي دائمًا.
        'ai_guard_prompt_injection', // فحص الحقن إلزامي دائمًا.
    ];

    /** @return array<string, mixed> كل الإعدادات بصيغة محوّلة الأنواع. */
    public function all(): array
    {
        try {
            $values = Cache::remember(
                self::CACHE_KEY,
                self::CACHE_TTL,
                fn () => $this->readRaw(),
            );
        } catch (Throwable $e) {
            // إعدادات لوحة الإدارة يجب ألا تُسقط صفحة كاملة إذا كان cache store
            // غير مُرحّل بعد أو غير متاح أثناء الإقلاع. نعود مباشرة إلى DB/defaults.
            Log::warning('ai.settings_cache_unavailable', [
                'message' => $e->getMessage(),
            ]);
            $values = $this->readRaw();
        }

        foreach (self::DEFAULTS as $key => [$default, $type]) {
            if (! array_key_exists($key, $values)) {
                $values[$key] = $default;
            }
            if ($type === 'boolean') {
                $values[$key] = in_array($key, self::FORCED_TRUE, true)
                    ? true
                    : filter_var($values[$key], FILTER_VALIDATE_BOOLEAN);
            } elseif (is_numeric($default)) {
                $values[$key] = $values[$key] === '' ? (float) $default : (float) $values[$key];
            }
        }

        // سقفيات أمنية لا يمكن للإدارة تجاوزها:
        $values['ai_max_results'] = max(1, min((int) $values['ai_max_results'], (int) config('ai.limits.max_results', 6)));
        $values['ai_max_candidates'] = max(1, min((int) $values['ai_max_candidates'], (int) config('ai.limits.max_candidates', 60)));

        return $values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function enabled(): bool
    {
        return (bool) $this->get('ai_enabled', true);
    }

    public function assistantName(): string
    {
        return (string) ($this->get('ai_assistant_name') ?: 'مساعد وجهتك');
    }

    public function welcomeMessage(): string
    {
        return (string) $this->get('ai_welcome_message');
    }

    public function isScoped(string $scope): bool
    {
        return (bool) $this->get('ai_scope_'.$scope, true);
    }

    public function guardActive(string $guard): bool
    {
        if (in_array('ai_guard_'.$guard, self::FORCED_TRUE, true)) {
            return true;
        }

        return (bool) $this->get('ai_guard_'.$guard, true);
    }

    /**
     * حفظ دفعي من نموذج الإدارة (مفاتيح معروفة فقط).
     *
     * `$booleanKeys` = المفاتيح المنطقية التي يحويها النموذج المرسل. أي مفتاح
     * منها غائب عن الطلب يُعامل على أنه "غير محدد" ويُحفظ بالقيمة 0 — بدون ذلك
     * لا يمكن للإدارة إلغاء تفعيل خيار (مثل تعطيل المساعد) لأن مربعات الاختيار
     * غير المحددة لا تُرسل أصلًا.
     */
    public function putMany(array $input, array $booleanKeys = []): void
    {
        foreach ($booleanKeys as $key) {
            if (! array_key_exists($key, $input)) {
                $input[$key] = '0';
            }
        }

        foreach (self::DEFAULTS as $key => [$default, $type]) {
            if (! array_key_exists($key, $input)) {
                continue;
            }
            $value = $input[$key];
            if ($type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }
            Setting::put($key, (string) $value, $type);
        }
        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, string> قيم خام للكاش (نصوص كما في القاعدة).
     *
     * متانة إنتاجية: إن كان جدول `settings` غير موجود (هجرة لم تُنفَّذ بعد)
     * أو تعذّرت قراءته، نرجع إلى القيم الافتراضية الآمنة بدل إسقاط الطلب
     * كله. يبقى الحاجز الإلزامي (FORCED_TRUE) مفعّلًا في الحالتين.
     */
    private function readRaw(): array
    {
        try {
            // ملاحظة مهمة: لا نستخدم `like 'ai\_%'` — الشرطة السفلية في LIKE حرف
            // بديل (wildcard) يحتاج جملة ESCAPE، وبلاها يتصرف السائق بشكل مختلف
            // بين MySQL وSQLite فتُقرأ صفر صفوف ويُعاد دائمًا إلى القيم الافتراضية.
            // المفاتيح معروفة ومحدودة في DEFAULTS، فنسأل عنها صراحةً — أدق وأسرع.
            $rows = Setting::query()->whereIn('key', array_keys(self::DEFAULTS))->get();

            return $rows->mapWithKeys(fn (Setting $s) => [$s->key => (string) $s->value])->all();
        } catch (Throwable $e) {
            Log::warning('ai.settings_unavailable', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}

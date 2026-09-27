# دليل المزودين (Provider Abstraction)

## العقد الموحد

`app/Services/AI/Contracts/AiProviderInterface.php`:

```php
interface AiProviderInterface
{
    /** حوار حر — توليد الرد النهائي. */
    public function chat(array $messages, array $options = []): AiResponse;

    /** استدعاء مهيكل مع مخطط JSON — تحليل النية. */
    public function structured(array $messages, array $schema, array $options = []): array;

    /** فحص صحة خادم الاستدلال. */
    public function health(): AiHealthStatus;
}
```

## إضافة مزود جديد (مثال: خادم llama.cpp أو محرك داخلي آخر)

1. أنشئ الكلاس وطبّق العقد:

```php
// app/Services/AI/Providers/LlamaCppProvider.php
class LlamaCppProvider implements AiProviderInterface
{
    public function chat(array $messages, array $options = []): AiResponse { /* ... */ }
    public function structured(array $messages, array $schema, array $options = []): array { /* ... */ }
    public function health(): AiHealthStatus { /* ... */ }
}
```

2. سجّله في `AiProviderManager::__construct()`:

```php
$this->register('llama_cpp', fn () => new LlamaCppProvider(...));
```

3. اختره من `/admin/ai` → النموذج → المزود (أو `AI_PROVIDER=llama_cpp`).

**لا تعدّل أي ملف آخر** — الخدمات العشرة الأخرى والمتحكمات والاختبارات كلها تعتمد العقد والسجل فقط.

## الالتزامات الواجب احترامها في أي تطبيق جديد

1. **بلا بيانات DB**: المزود لا يستلم سوى الرسائل المعدة — الاستعلام والتصفية تبقى في `AiPropertySearchService`.
2. **بلا أسرار في المخرجات**: أي رد يمر لاحقًا بـ Grounding الذي يستبدل الرد عند اكتشاف تسرب — لكن لا تعرض مفتاحك في الرسائل أصلًا.
3. `structured()` يجب أن يرمي `AiProviderException` عند JSON غير صالح (المحلل يعتمد على ذلك للتراجع للقواعد بهدوء).
4. `health()` يجب ألا يرمي استثناءً أبدًا — يعيد `AiHealthStatus(healthy: false, ...)`.

## التوجيه في زمن التشغيل

`AiProviderManager::provider()` يقرأ `ai_provider` من إعدادات اللوحة (سريعة عبر كاش 5 دقائق) مع الرجوع إلى `config('ai.default_provider')`. تغيير المحرك يظهر للمستخدمين خلال ≤ 5 دقائق بلا إعادة نشر.

## الاختبار مع مزود بديل

الاختبارات لا تلمس الشبكة: سلوك «تعذر النموذج» يُحاكى عبر `Http::fake(['*' => Http::response(null, 500)])` (انظر `tests/Feature/Ai/AiAssistantTest.php::test_model_unavailable_returns_graceful_fallback`). أي مزود جديد يمر بنفس الاختبارات تلقائيًا لأنها تختبر العقد والمنسق لا التطبيق.

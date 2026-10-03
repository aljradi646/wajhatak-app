# معمارية المساعد العقاري الذكي — وجهتك

> مساعد ذكاء اصطناعي **حقيقي** للبحث العقاري باللغة الطبيعية، بنموذج محلي (Self-Hosted)، بدون أي API سحابي، وبدون وصول مباشر للقاعدة من النموذج.

## 1. نظرة عامة

```
Flutter (زر عائم + شاشة محادثة)
   ↓  POST /api/v1/ai/chat  (Bearer token أو مفتاح جلسة زائر)
Laravel API  ← كل الأمان والمنطق هنا
   ├─ AiGuardrailService      فحص مسبق: نطاق/حقن/حساسية (في الكود لا في الـ prompt)
   ├─ AiIntentService         لغة طبيعية → معايير بحث منظمة (قواعد + نموذج)
   ├─ AiConversationService   سياق المحادثة + دمج المعايير المتراكمة
   ├─ AiPropertySearchService استعلام حقيقي على ai_search_index (المتزامنة مع properties)
   ├─ AiPromptService         قطع DATA موثوقة + قواعد سلامة غير قابلة للتعديل
   ├─ LocalGlmProvider        اتصال OpenAI-compatible بخادم vLLM/Ollama الداخلي
   └─ AiResponseGroundingService  منع الهلوسة: تحقق من كل property_id وأرقام
   ↓
رد + بطاقات عقارات حقيقية (property_id + بيانات من القاعدة فقط)
```

## 2. الطبقات والمسؤوليات

| الخدمة | الملف | المسؤولية |
|---|---|---|
| `AiAssistantService` | `app/Services/AI/AiAssistantService.php` | المنسق: يربط كل الخطوات ويعالج الفشل برد بديل لطيف |
| `AiIntentService` | `app/Services/AI/AiIntentService.php` | تحليل اللغة الطبيعية إلى معايير منظمة؛ قواعد regex عربية أولًا ثم النموذج للباقي |
| `AiGuardrailService` | `app/Services/AI/AiGuardrailService.php` | رفض خارج النطاق + حقن التعليمات + طلبات البيانات الحساسة — قرارات في الكود |
| `AiPropertySearchService` | `app/Services/AI/AiPropertySearchService.php` | البناء الآمن للاستعلام + التصفية + النص الحر + التسجيل والترتيب |
| `AiToolService` | `app/Services/AI/AiToolService.php` | الأدوات الست المسموحة (search/details/compare/locations/features/availability) — القائمة البيضاء الوحيدة |
| `AiResponseGroundingService` | `app/Services/AI/AiResponseGroundingService.php` | التحقق الأرضي بعد التوليد: كل معرف في الرد يجب أن يكون في نتائج البحث |
| `AiConversationService` | `app/Services/AI/AiConversationService.php` | دورة حياة المحادثات + سياق المعايير + حد أسئلة المتابعة + التقليم |
| `AiPromptService` | `app/Services/AI/AiPromptService.php` | System prompt مركزي + قواعد سلامة **غير قابلة للتغيير من الإدارة** |
| `AiSettingsService` | `app/Services/AI/AiSettingsService.php` | إعدادات لوحة التحكم مع سقفيات أمنية مفروضة (3 حواجز لا تُعطّل) |
| `AiIndexSyncService` | `app/Services/AI/AiIndexSyncService.php` | مزامنة `ai_search_index` مع كل تغيير في العقارات |
| `AiLoggingService` | `app/Services/AI/AiLoggingService.php` | سجل طلبات آمن (بلا أسرار أو محتوى حر) + إحصاءات المراقبة |
| `AiProviderManager` | `app/Services/AI/AiProviderManager.php` | سجل المزودين — نقطة تبديل المحرك الوحيدة |

## 3. تجريد المزود (Provider Abstraction)

```
AiProviderInterface (Contracts)
   ├─ chat(messages, options): AiResponse
   ├─ structured(messages, schema): array
   └─ health(): AiHealthStatus

LocalGlmProvider  ← التطبيق الحالي (OpenAI-compatible: vLLM / SGLang / llama.cpp / Ollama)
[أي مزود مستقبلي — سجّله في AiProviderManager]
```

لا يعتمد أي كود آخر على `LocalGlmProvider` مباشرة؛ التوجيه عبر `config('ai.default_provider')` وإعداد `ai_provider` من لوحة التحكم.

## 4. منع الهلوسة — 4 طبقات

1. **قبل النموذج:** الحواجز ترفض الطلبات الخطرة قبل أي استدلال.
2. **أثناء التوليد:** النموذج لا يرى إلا قطع DATA مستخرجة من القاعدة + تعليمات صارمة (الرد المختلق لا يتجاوز هذه الطبقة).
3. **بعد التوليد (إلزامي):** `AiResponseGroundingService` يستخرج كل معرف مذكور ويتحقق أنه ضمن نتائج البحث؛ يحذف أسطر المعرفات المخترعة ويستبدل الرد كاملًا عند تسرب أسرار.
4. **هيكليًا:** الواجهة لا تعرض بطاقة إلا لمعرف قادم من مصفوفة `properties` في الاستجابة — لا تحليل نصي للبطاقات.

## 5. مزامنة البيانات (Source of Truth)

`PropertyObserver` (مسجل في `AppServiceProvider`) يستدعي `AiIndexSyncService` عند `created/updated/deleted/restored/forceDeleted`. تعديل السعر أو التوفر أو الموقع ينعكس على المساعد **فورًا** بلا أي إعادة بناء يدوية — والتحديث رخيص بفضل `content_hash` (تخطي الكتابة عند تطابق التجزئة).

أمر يدوي للاستثناءات: `php artisan ai:reindex {--fresh}`.

## 6. بنية الجداول

- `ai_conversations` — محادثات المساعد (`user_id` للمسجلين + `session_token` للزوار).
- `ai_messages` — الرسائل + المعايير المنظمة + معرفات العقارات المعروضة + حالة كل رسالة.
- `ai_request_logs` — سجل تشغيلي (نية، معايير، أدوات، نتائج، latency، خطأ) — بلا أسرار أو نصوص حرة.
- `ai_search_index` — نسخة بحث مُعدة من `properties` (FULLTEXT على MySQL، LIKE على SQLite).

## 7. التدهور الآمن (Fallback)

| الحالة | سلوك النظام |
|---|---|
| خادم الاستدلال offline/timeout | رد ملخص محلي مبني على نتائج البحث الحقيقية + البطاقات تظهر طبيعيًا |
| النموذج أعاد JSON غير صالح | محلل القواعد يكفي للمعايير الصريحة، والبحث يستمر |
| المساعد معطل من الإدارة | `503` برسالة: «المساعد غير متاح حاليًا، لكن يمكنك استخدام البحث العقاري التقليدي.» |
| فشل شبكة في Flutter | رسالة بديلة في المحادثة + زر إعادة إرسال؛ باقي التطبيق يعمل كاملًا |

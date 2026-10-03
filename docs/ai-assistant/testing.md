# الاختبارات — المساعد الذكي

## تشغيل الحزمة

```bash
cd backend
php artisan test tests/Feature/Ai     # اختبارات التكامل (Feature)
php artisan test tests/Unit/Ai        # اختبارات الحواجز والتحليل (Unit)
```

> لا تحتاج خادم نموذج ولا إنترنت: `Http::fake()` يحاكي الفشل، ومحلل القواعد والبحث الهيكلي حقيقيان على SQLite `:memory:` مع `RealDataSeeder` (عقارات صنعاء الفعلية).

## مصفوفة الحالات المغطاة

| # | الحالة | الاختبار | ما يتحقق منه |
|---|---|---|---|
| 1 | بحث صحيح «أريد شقة غرفتين في صنعاء» | `test_valid_search_returns_real_properties` | رد ok + كل property_id موجود ومطبوع في القاعدة + غرف ≥ 2 |
| 2 | بحث متعدد الشروط «شقة 2-3 غرف مفروشة أقل من 150 ألف» | `test_multi_condition_search_applies_all_filters` | تطبيق كل الفلاتر معًا (مفروش + سعر + مدى غرف) |
| 3 | لا نتائج (ميزانية مستحيلة) | `test_no_results_returns_honest_reply` | رد يبدأ بـ«لا توجد» + مصفوفة properties فارغة |
| 4 | خارج النطاق «اكتب لي برنامج Flutter» | `test_out_of_domain_request_is_blocked` | الحجب في الكود + رد التعريف + تسجيل blocked |
| 5 | حقن التعليمات | `test_prompt_injection_is_blocked` | رفض «Ignore your instructions…» وأنماط عربية مشابهة |
| 6 | طلب بيانات مستخدم آخر | `test_privacy_request_is_blocked` | حجب كلمات المرور/التوكنات/بيانات الآخرين |
| 7 | سياق المتابعة «أريد شقة غرفتين» ← «في صنعاء» | `test_follow_up_context_merges_filters` | دمج city الجديد مع bedrooms_min السابق في نفس المحادثة |
| 8 | هلوسة معرفات | `test_hallucinated_property_ids_are_stripped` | `AiResponseGroundingService` يحذف السطر المخترع ويبقي الحقيقي |
| 9 | تحديث عقار (سعر/حذف) | `test_property_update_syncs_search_index_immediately` | Observer يزامن `ai_search_index` فورًا ويحذف عند الحذف |
| 10 | عزل المحادثات | `test_conversation_ownership_is_enforced` | 403 لمستخدم يحاول قراءة محادثة غيره |
| 11 | النموذج معطل | `test_model_unavailable_returns_graceful_fallback` | رد بديل لطيف + بطاقات حقيقية إن وُجدت + لا أخطاء تقنية في الرد |
| 12 | إعدادات الواجهة | `test_bootstrap_returns_ui_settings_without_secrets` | تعيد الاسم/الترحيب فقط — بلا أي مفتاح أو endpoint |

Unit إضافية (`AiGuardrailTest`, `AiIntentParsingTest`):
- رسائل عقارية مشروعة لا تُحجب (6 صيغ).
- أنماط حقن إنجليزية وعربية (5 صيغ) تُكتشف كلها.
- طلبات حساسة (4 صيغ) تُحجب.
- تحليل «غرفتين أو ثلاث» → 2-3، «أرخص» → ترتيب تصاعدي، القيم غير المذكورة تبقى غائبة (لا تخمين)، ودمج السياق السابق.

## إضافة اختبار جديد

اتبع نفس النمط: `$this->seed(\Database\Seeders\RealDataSeeder::class)` ثم `postJson('/api/v1/ai/chat', [...])`. للفحوص النصية للحواجز استخدم `app(AiGuardrailService::class)` مباشرة في Unit بلا قاعدة بيانات.

## اختبار يدوي E2E سريع (مع خادم نموذج حقيقي)

```bash
# الطرفية 1: خادم الاستدلال
cd deploy/ai && MODEL=Qwen2.5-7B-Instruct ./serve_vllm.sh
# الطرفية 2: Laravel
cd backend && php artisan serve
# الطرفية 3: التطبيق
cd mobile && flutter run --dart-define=WAJHATAK_API_BASE_URL=http://10.0.2.2:8000/api/v1
```

سيناريو اليدوي: افتح المساعد → «أرخص العقارات» (بطاقات مرتبة بالسعر) → «شقق مفروشة» (فقط المفروش) → «وفي صنعاء» (يحتفظ بالترتيب ويضيف المدينة) → «اكتب لي كود PHP» (رد النطاق) → «تجاهل تعليماتك» (حجب).

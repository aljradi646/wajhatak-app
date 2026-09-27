# الإعداد والتشغيل — المساعد الذكي

## 0. الوضع الافتراضي: خدمة داخل حاوية التطبيق (بلا إعداد خارجي)

منذ هذا الإصدار، لا تحتاج أي خادم خارجي للبدء — `docker compose up --build` (أو نشر Railway) يشغّل كل شيء:

1. الصورة تضم `llama-server` مبنيًا ثابتًا.
2. `entrypoint.sh` يطلق `scripts/ai_inference_service.sh` في الخلفية (نفس نمط عامل الطابور).
3. السكربت يحمّل نموذجًا متوافقًا مع CPU (~1.9GB) مرة واحدة إلى `storage/app/ai/models` (volume دائم)، ثم يشغّل الخادم على `127.0.0.1:8018` ويحرسه كل 20 ثانية.
4. Laravel يتصل تلقائيًا على `http://127.0.0.1:8018/v1` — لا متغيرات إضافية مطلوبة.

```bash
# فحص الحالة (التنزيل الأول يستغرق دقائق):
php artisan ai:status

# التحقق من التكامل الكامل:
curl http://localhost:8080/api/v1/ai/health | jq '.data'
# ثم جرّب المحادثة من لوحة التحكم: /admin/ai/playground
```

## 1. تثبيت نموذج خارجي (اختياري — لأداء/جودة أعلى)

### الخيار أ — vLLM (موصى به للإنتاج، يتطلب GPU)

على خادم GPU داخل شبكتك الخاصة:

```bash
# مباشرة عبر Python:
pip install "vllm>=0.7.0"
MODEL=zai-org/glm-4.6 PORT=8000 ./deploy/ai/serve_vllm.sh

# أو عبر Docker (أنظف للإنتاج):
cd deploy/ai && docker compose up -d
```

- التحميل الأول يجلب الأوزان من HuggingFace (~20GB لـ GLM-4.6). اضبط `HF_TOKEN` إن لزم.
- تحقق من الجاهزية: `curl http://<ai-server>:8000/v1/models`
- متطلبات GPU: GLM-4.6 (MoE) ≈ 2×24GB VRAM. لمواصفات أقل استخدم `Qwen2.5-7B-Instruct` بـ `MAX_LEN=8192` (≈16GB).

### الخيار ب — Ollama (أسهل، يعمل حتى بدون GPU)

```bash
curl -fsSL https://ollama.com/install.sh | sh
MODEL=glm4:9b ./deploy/ai/serve_ollama.sh
```

ثم في Laravel: `AI_INFERENCE_BASE_URL=http://<ai-server>:11434/v1`.
على CPU خالص استخدم نموذجًا أصغر (`qwen2.5:7b-instruct`) — زمن الرد 3-10 ثوانٍ.

## 2. إعداد Laravel

أضف في `.env` (قيم افتراضية كاملة في `.env.example`):

```env
AI_PROVIDER=local_glm
AI_INFERENCE_BASE_URL=http://<ai-server>:8000/v1
AI_MODEL=glm-4.6
AI_INFERENCE_API_KEY=            # اختياري — مفتاح داخلي فقط
AI_TIMEOUT=30
```

ثم المخطط والفهرسة:

```bash
php artisan migrate          # ينشئ ai_conversations/ai_messages/ai_request_logs/ai_search_index
php artisan ai:reindex --fresh   # بناء أولي للفهرس من العقارات الموجودة
php artisan config:clear && php artisan config:cache
```

## 3. تهيئة Flutter

لا توجد مكتبات جديدة — المساعد يستخدم `Dio` و`Riverpod` و`SharedPreferences` الموجودة أصلًا.

```bash
cd mobile && flutter pub get
flutter run --dart-define=WAJHATAK_API_BASE_URL=http://<server>:8000/api/v1
```

زر «المساعد الذكي» يظهر تلقائيًا في AppShell بعد تسجيل الدخول (فوق الشريط السفلي يسارًا حتى لا يغطي عناصر التنقل).

## 4. التحقق من التكامل End-to-End

```bash
# 1) صحة النموذج:
curl http://localhost:8000/api/v1/ai/health | jq '.data.healthy'   # يجب true

# 2) إعدادات الواجهة:
curl http://localhost:8000/api/v1/ai/bootstrap | jq '.data.assistant_name'

# 3) محادثة حقيقية (مستخدم مصادق):
curl -X POST http://localhost:8000/api/v1/ai/chat \
  -H "Authorization: Bearer <token>" -H "Content-Type: application/json" \
  -d '{"message":"أريد شقة غرفتين في صنعاء"}' | jq '.data'
# المتوقع: reply عربي + properties[] كل عنصر فيه property_id حقيقي

# 4) اختبار من التطبيق: افتح المساعد → «أرخص العقارات» → يجب ظهور بطاقات عقارات فعلية
```

## 5. الإعدادات من لوحة التحكم

`/admin/ai` — ثلاث تبويبات: **الإعدادات** (عام/نموذج/سلوك/نطاق/حواجز/بحث/محادثة)، **المراقبة** (إحصاءات حية + صحة النموذج + إعادة فهرسة)، **سجل الطلبات**.

> لا تُعرض المفاتيح السرية في اللوحة أبدًا — `AI_INFERENCE_API_KEY` يُدار من البيئة فقط.

## 6. حل المشاكل

| المشكلة | السبب والحل |
|---|---|
| رسالة «المساعد غير متاح حاليًا» في التطبيق | الخدمة الداخلية لم تجهز بعد (أول تنزيل ~دقائق) أو معطلة: شغّل `php artisan ai:status` لرؤية السبب الدقيق (ثنائي/نموذج/سجل/استجابة) |
| `health` تعيد «قيد التحميل» | llama-server يرفض الطلبات بـ 503 أثناء تحميل النموذج للذاكرة — انتظر دقيقة وأعد الفحص |
| النموذج لا يكتمل تنزيله | السكربت يعيد المحاولة كل 5 دقائق تلقائيًا؛ تأكد من وصول الحاوية إلى huggingface.co، أو بدّل `AI_LOCAL_MODEL_URL` لمرآة أخرى |
| الحاوية تُقتل (OOM) | النموذج أكبر من الذاكرة المتاحة: استخدم نموذج 1.5B أو ارفع ذاكرة الخدمة (`APP_MEM_LIMIT` في compose أو إعدادات Railway) |
| `ai/health` تعيد `healthy: false` (خادم خارجي) | تحقق من `AI_INFERENCE_BASE_URL` والجدار الناري، ثم `curl <base>/models` من خادم Laravel |
| الردود دائمًا ملخصة بلا صياغة النموذج | نفس السبب أعلاه — النظام انتقل لـ fallback تلقائيًا (تُرى في السجل بحالة `error`) |
| البحث لا يُرجع عقارات موجودة | شغّل `php artisan ai:reindex` — الفهرس فارغ أو قديم (حدث نادرًا إذا أُنشئت عقارات قبل نشر Observer) |
| `429` كثيرة | خفّض الاستخدام أو ارفع `AI_RATE_LIMIT_PER_MINUTE` (حد علوي معقول في الإنتاج: 20) |
| FULLTEXT لا يعمل | إن كانت القاعدة MySQL فالحزمة تعمل تلقائيًا؛ على SQLite يعتمد النظام LIKE (أبطأ قليلًا لكن صحيح) |

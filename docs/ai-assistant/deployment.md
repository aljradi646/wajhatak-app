# النشر الإنتاجي — المساعد الذكي

## 1. فصل الخدمات (إلزامي)

الاستضافة الحالية (Railway — Docker PHP لا يوفر GPU) **لا تشغّل نموذجًا محليًا**، والنظام مصمم على فصل واضح دون أي التفاف:

```
┌─────────────────────────────┐        ┌──────────────────────────────┐
│  Railway: Laravel API + DB  │  HTTPS │  AI Inference Server (خادمك) │
│  - كل منطق الأمان والأدوات  │ ─────→ │  vLLM / Ollamia (OpenAI API) │
│  - يظل يعمل كاملًا بدون AI  │ شبكة   │  GPU واحد (16-48GB VRAM)     │
└─────────────────────────────┘ آمنة  └──────────────────────────────┘
         ↑ HTTPS
      Flutter App
```

## 2. تجهيز خادم الاستدلال

أي خادم GPU (Hetzner GPU / Lambda / خادم داخلي) بنظام Linux + NVIDIA driver:

```bash
# انسخ deploy/ai/ إلى الخادم ثم:
cd deploy/ai
HF_TOKEN=hf_xxx docker compose up -d     # vLLM + GLM-4.6

# أو بدون Docker:
pip install "vllm>=0.7.0"
MODEL=zai-org/glm-4.6 ./serve_vllm.sh
```

**قواعد الشبكة:**
- لا تفتح المنفذ 8000 للإنترنت. اربط Railway بالخادم عبر أحد الخيارات:
  1. **WireGuard/Tailscale** بين خدمة Railway وخادم GPU (الأسهل والأأمن) — ثم `AI_INFERENCE_BASE_URL=http://<tailscale-ip>:8000/v1`.
  2. IP allowlist على جدار الخادم يقبل فقط IPs خروج Railway الثابتة (إن توفرت).
  3. نفق SSH محدود إن كان التأخير مقبولًا.
- فعّل `AI_INFERENCE_API_KEY` ومرر نفس القيمة لـ vLLM عبر وسيط `--api-key` (يدعمه vLLM) أو وكيل Nginx أمامي.

## 3. إعداد خدمة Railway (Laravel)

أضف المتغيرات في لوحة Railway:

```env
AI_PROVIDER=local_glm
AI_INFERENCE_BASE_URL=http://<private-ip-or-tailnet>:8000/v1
AI_MODEL=glm-4.6
AI_INFERENCE_API_KEY=<نفس مفتاح الخادم>
AI_TIMEOUT=45            # ارفعها قليلًا لشبكة بين مزودين
AI_RATE_LIMIT_PER_MINUTE=10
```

المتطلبات الأخرى موجودة أصلًا: الـ migrations الجديدة تُنشأ عند أول تشغيل (نظام `system_initialized` موجود في entrypoint — إن كانت القاعدة مهيأة من قبل شغّل يدويًا من جهازك: `php artisan migrate --force` بعد ضبط DB للإنتاج).

## 4. الصحة والمراقبة في الإنتاج

| نقطة الفحص | التكرار المقترح |
|---|---|
| `GET /api/v1/ai/health` | uptime monitor خارجي كل دقيقة — تنبيه عند `healthy=false` |
| `GET /up` (Railway) | مدمج مع healthcheck الحاوية |
| بطاقة صحة النموذج في `/admin/ai` | يدويًا / عند التنبيه |
| Docker healthcheck لخادم vLLM | مفعّل في `deploy/ai/docker-compose.yml` (restart + start_period 180s) |
| تبويب المراقبة (latency، errors، blocked) | مراجعة أسبوعية |

## 5. حجم GPU المطلوب في الإنتاج (ما يتطلب خادم GPU فعليًا)

| النموذج | VRAM للخدمة | جودة الفهم العربي العقاري | ملاحظة |
|---|---|---|---|
| GLM-4.6 (MoE ~355B) | 2×24GB على الأقل (A100/A6000) | ممتازة | الخيار الافتراضي في الإعداد |
| Qwen2.5-14B-Instruct | ~24GB واحدة | جيدة جدًا | توازن ممتاز |
| Qwen2.5-7B-Instruct | ~16GB (RTX 4090/A5000) | جيدة | حد أدنى معقول للإنتاج |
| glm4:9b عبر Ollama | ~12GB | جيدة | أسرع إعدادًا |
| على CPU خالص | بلا GPU | مقبولة | qwen2.5:7b بذاكرة 16GB RAM — 3-10 ثوانٍ للرد |

> مهما كان خيارك: البحث والنتائج والبطاقات **لا تعتمد على قوة النموذج** — النموذج يحسّن الصياغة فقط، والبنية تعمل حتى أسوأ نموذج (fallback) بلا انقطاع.

## 6. قائمة التحقق قبل الإطلاق

- [ ] `php artisan migrate --force` على قاعدة الإنتاج (جداول ai_*)
- [ ] `php artisan ai:reindex` ثم مقارنة عدد صفوف `ai_search_index` مع العقارات المنشورة
- [ ] `/api/v1/ai/health` = healthy عبر HTTPS من Railway
- [ ] تجربة من التطبيق: «أريد شقة غرفتين في صنعاء» تعيد بطاقات حقيقية
- [ ] تجربة «اكتب لي كود» تُرد برد النطاق دون تسريب
- [ ] ضبط المنبهات على `/ai/health`
- [ ] مراجعة `/admin/ai` → المراقبة بعد أول يوم تشغيل

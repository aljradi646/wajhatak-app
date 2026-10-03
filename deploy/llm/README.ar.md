# نموذج وجهتك الصغير الحقيقي — SmolLM2-135M-Instruct

- النموذج: HuggingFaceTB/SmolLM2-135M-Instruct
- ملف GGUF: SmolLM2-135M-Instruct-Q8_0.gguf
- الدقة: Q8_0
- حجم الأوزان: نحو 145MB، أقل من 200MB
- الترخيص: Apache 2.0
- الخادم: llama.cpp بواجهة OpenAI-compatible

المصادر:
https://huggingface.co/HuggingFaceTB/SmolLM2-135M-Instruct
https://huggingface.co/tensorblock/SmolLM2-135M-Instruct-GGUF

## التشغيل

docker compose -f deploy/llm/docker-compose.yml up -d --build
curl.exe http://localhost:11434/health

أو التطبيق كاملًا:

docker compose -f backend/docker-compose.yml up -d --build
curl.exe http://localhost:8080/api/v1/ai/health

عند الإقلاع الأول يقوم llama.cpp بتنزيل ملف GGUF تلقائيًا إلى /models، والـvolume يحتفظ بالكاش.

## Railway

أنشئ خدمة ثانية من نفس المستودع:
- الاسم: wajhatak-llm
- Root Directory: deploy/llm
- Volume mount: /models
- لا تنشئ Public Domain لخدمة النموذج.

في خدمة Laravel أضف:
AI_LLM_ENABLED=true
AI_LLM_MODE=grounded
AI_LLM_BASE_URL=http://${{wajhatak-llm.RAILWAY_PRIVATE_DOMAIN}}:8080/v1
AI_LLM_API_KEY=
AI_LLM_MODEL=wajhatak-smollm2-135m-instruct-q8_0
AI_LLM_MAX_OUTPUT_TOKENS=256

لا يحتاج النموذج إلى API key.

## الربط مع Flutter

Flutter يتصل بـ Laravel فقط:
Flutter -> /api/v1/ai/chat -> Laravel search/permissions/real data -> private LLM -> Laravel -> assistant UI

النموذج يصيغ الرد النهائي فقط؛ البحث والعقارات والعمليات الحساسة تبقى داخل Laravel.

## الموارد

ملف النموذج نحو 145MB، لكن RAM التشغيل أعلى من حجم الملف. Compose يضع حدًا افتراضيًا 1GB ويمكن رفعه في Railway.

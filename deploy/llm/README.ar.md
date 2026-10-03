# نموذج وجهتك المحلي المجاني

الاختيار الافتراضي هو Qwen3 0.6B عبر Ollama. هذا نموذج حقيقي صغير، ويبلغ حجم النسخة المنشورة على Ollama نحو 523MB، كما أن صفحة السلسلة تعرض نسخة 0.6B وتدعم أدوات الوكلاء.

Ollama يوفر واجهة OpenAI-compatible محليًا على http://localhost:11434/v1، وChat Completions يدعم tools/function calling، لذلك لا يحتاج التشغيل المحلي إلى API key.

## التشغيل

من جذر المستودع:

    docker compose -f deploy/llm/docker-compose.yml up -d

أو على Windows بعد تثبيت Ollama:

    ollama pull qwen3:0.6b
    ollama create wajhatak-qwen3:0.6b -f deploy/llm/Modelfile

ثم إعداد Laravel:

    AI_LLM_ENABLED=true
    AI_LLM_BASE_URL=http://127.0.0.1:11434/v1
    AI_LLM_MODEL=wajhatak-qwen3:0.6b
    AI_ALLOW_RULE_FALLBACK=true

## الإنتاج

النموذج مجاني للاستخدام المحلي، لكن تشغيل الاستدلال يحتاج موارد CPU/RAM. لا يوجد استدلال عام مجاني بلا حدود على Railway. لذلك تم فصل النموذج عن Laravel ويمكن تشغيله محليًا أو على خادم تملكه، ثم تغيير عنوانه فقط في البيئة.

يمكن رفع الجودة لاحقًا إلى qwen3:1.7b بتغيير AI_LLM_MODEL دون تعديل طبقة Agent.

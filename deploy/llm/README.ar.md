# خادم الاستدلال المحلي لوجهتك

هذا المجلد يشغّل Ollama كخدمة OpenAI-compatible. لا يتم تضمين نموذج داخل مستودع Laravel ولا داخل صورة التطبيق.

## التشغيل

شغّل Ollama:

    docker compose -f deploy/llm/docker-compose.yml up -d

ثم حمّل النموذج الذي اخترته على جهاز/خادم الاستدلال، وبعد ذلك اضبط Laravel:

    AI_LLM_ENABLED=true
    AI_LLM_BASE_URL=http://ollama:11434/v1
    AI_LLM_MODEL=<اسم النموذج>

في بيئة Railway يجب تشغيل Ollama أو vLLM كخدمة استدلال منفصلة لها عنوان داخلي/خاص يمكن لخدمة Laravel الوصول إليه. لا تضع API key أو عنوانًا سريًا داخل Flutter.

## ملاحظة إنتاجية

اختيار النموذج والـGPU/CPU وحجم الذاكرة يعتمد على نموذج الاستدلال الفعلي. طبقة Laravel لا تفترض نموذجًا محددًا، وتستخدم واجهة OpenAI Chat Completions مع Tool Calling.

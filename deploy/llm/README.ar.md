# نموذج وجهتك المحلي المجاني

الاختيار الافتراضي هو **Qwen3.5:0.8b** عبر Ollama. النسخة المتاحة حاليًا على Ollama بحجم يقارب 1GB، وتُصنّف سلسلة Qwen3.5 ضمن النماذج التي تدعم الأدوات والتفكير، كما تدعم الإدخال النصي والصور. هذا يجعلها مناسبة كبداية صغيرة لوكيل وجهتك، مع بقاء التطبيق مستوعبًا لنماذج أكبر لاحقًا. citeturn362426search0turn362426search1

## التشغيل المحلي

من جذر المستودع:

```powershell
docker compose -f deploy/llm/docker-compose.yml up -d
```

أو بعد تثبيت Ollama على Windows:

```powershell
ollama pull qwen3.5:0.8b
ollama create wajhatak-qwen3.5:0.8b -f deploy/llm/Modelfile
```

ثم في Laravel:

```env
AI_LLM_ENABLED=true
AI_LLM_BASE_URL=http://127.0.0.1:11434/v1
AI_LLM_API_KEY=
AI_LLM_MODEL=wajhatak-qwen3.5:0.8b
AI_ALLOW_RULE_FALLBACK=true
```

Ollama يوفّر واجهة OpenAI-compatible محلية، ولذلك لا يحتاج التشغيل المحلي إلى API مدفوع أو مفتاح مزود خارجي.

## Docker الكامل

الملف `backend/docker-compose.yml` يشغّل MySQL وLaravel وOllama معًا، ويضبط Laravel على:

```text
http://ollama:11434/v1
```

ويتم إنشاء النموذج `wajhatak-qwen3.5:0.8b` تلقائيًا من `Modelfile`.

## الإنتاج وRailway

لا يتم تضمين النموذج داخل صورة Laravel. الأفضل تشغيل خدمة الاستدلال كخدمة مستقلة ثم وضع عنوانها في متغيرات Railway:

```env
AI_LLM_ENABLED=true
AI_LLM_BASE_URL=http://<inference-service>:11434/v1
AI_LLM_MODEL=wajhatak-qwen3.5:0.8b
```

لا يوجد استدلال سحابي مجاني مضمون بلا حدود؛ المجانية هنا تأتي من استخدام نموذج مفتوح محليًا، بينما جهاز/خادم الاستدلال نفسه يحتاج موارد تشغيل.

يمكن تغيير النموذج لاحقًا إلى `qwen3.5:2b` عندما تتوفر موارد أكثر دون تعديل Tool Registry أو Laravel API.
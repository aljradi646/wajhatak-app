# نموذج وجهتك المحلي المجاني

الاختيار الافتراضي هو **Qwen3:1.7b** عبر Ollama. هذا الإصدار حجمه نحو **1.4GB** في نسخة Q4_K_M، وتعرض صفحة Ollama الرسمية Qwen3 كسلسلة ذات دعم للأدوات والتفكير، كما تذكر دعم أكثر من 100 لغة ولهجة. هذا يجعله خيارًا صغيرًا نسبيًا ومناسبًا لوكيل عقاري يعتمد على Tool Calling. citeturn208740search0turn208740search1

## التشغيل المحلي

من جذر المستودع:

```powershell
docker compose -f deploy/llm/docker-compose.yml up -d
```

أو بعد تثبيت Ollama على Windows:

```powershell
ollama pull qwen3:1.7b
ollama create wajhatak-qwen3:1.7b -f deploy/llm/Modelfile
```

ثم في Laravel:

```env
AI_LLM_ENABLED=true
AI_LLM_BASE_URL=http://127.0.0.1:11434/v1
AI_LLM_API_KEY=
AI_LLM_MODEL=wajhatak-qwen3:1.7b
AI_ALLOW_RULE_FALLBACK=true
```

Ollama يدعم Tool Calling وOpenAI-compatible API، لذلك طبقة Laravel الحالية ترسل أدوات البحث والعقار والمعاينة للنموذج دون كشف قاعدة البيانات له. citeturn876111search0turn876111search1

## Docker الكامل

الملف `backend/docker-compose.yml` يشغّل MySQL وLaravel وOllama معًا، وينتظر Laravel حتى تصبح خدمة الاستدلال سليمة. عنوانها داخل شبكة Docker:

```text
http://ollama:11434/v1
```

ويتم إنشاء `wajhatak-qwen3:1.7b` تلقائيًا.

## الإنتاج وRailway

لا يتم تضمين النموذج داخل صورة Laravel. في الإنتاج شغّل inference service مستقلة، ثم اضبط:

```env
AI_LLM_ENABLED=true
AI_LLM_BASE_URL=http://<inference-service>:11434/v1
AI_LLM_MODEL=wajhatak-qwen3:1.7b
```

المجانية هنا تعني عدم الحاجة إلى API مدفوع عند التشغيل المحلي؛ أما الاستدلال السحابي نفسه فيحتاج موارد تشغيل. لا يمكن اعتبار Railway خدمة inference مجانية بلا حدود.

عند توفر موارد أكبر يمكن رفع النموذج إلى `qwen3:4b` أو أكبر دون تغيير عقد Laravel/Tool Registry.

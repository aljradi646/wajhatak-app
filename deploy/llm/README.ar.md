# نموذج وجهتك المحلي المجاني

الاختيار الافتراضي هو **Qwen3:1.7b** عبر Ollama. نسخة Q4_K_M الحالية حجمها نحو **1.4GB**، وصفحة Ollama الرسمية تصنف Qwen3 ضمن النماذج ذات دعم الأدوات والتفكير وتذكر دعم أكثر من 100 لغة ولهجة. هذا مناسب لوكيل صغير يعتمد على Tool Calling مع إبقاء إمكانية الترقية إلى نموذج أكبر لاحقًا. citeturn208740search0turn208740search1

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

Ollama يدعم Tool Calling وواجهة OpenAI-compatible، وتستطيع طبقة Laravel الحالية إرسال أدوات العقارات والمعاينات للنموذج دون منحه وصولًا مباشرًا إلى قاعدة البيانات. citeturn876111search0turn876111search1

## Docker الكامل

الملف `backend/docker-compose.yml` يشغّل MySQL وLaravel وOllama معًا. ينتظر Laravel سلامة خدمة Ollama، ويستخدم:

```text
http://ollama:11434/v1
```

ثم يتم إنشاء `wajhatak-qwen3:1.7b` تلقائيًا.

## الإنتاج وRailway

لا تُضمّن ملفات النموذج داخل صورة Laravel. في الإنتاج شغّل inference service مستقلة، ثم اضبط:

```env
AI_LLM_ENABLED=true
AI_LLM_BASE_URL=http://<inference-service>:11434/v1
AI_LLM_MODEL=wajhatak-qwen3:1.7b
```

المجانية هنا تعني عدم الحاجة إلى API مدفوع عند التشغيل المحلي. خادم الاستدلال نفسه يحتاج موارد CPU/GPU/RAM؛ لا يوجد استدلال سحابي مجاني مضمون بلا حدود.

عند توفر موارد أكبر يمكن الانتقال إلى `qwen3:4b` أو إصدار أكبر دون تغيير عقد Laravel أو Tool Registry.
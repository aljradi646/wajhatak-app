# وكيل وجهتك الذكي — البنية الإنتاجية

المسار: Flutter → Laravel API → Guardrails → Conversation/Memory/Knowledge → LLM → Tool Calling → Laravel Services/DB → Tool Results → LLM → Grounded Response.

النموذج لا يصل إلى MySQL ولا يملك صلاحيات مستقلة. كل أداة تنفذ داخل Laravel وتخضع لـ Permission Guard.

## الاستدلال
عميل متوافق مع OpenAI Chat Completions ويدعم vLLM وOllama وأي خادم متوافق.
المتغيرات: AI_LLM_ENABLED وAI_LLM_BASE_URL وAI_LLM_API_KEY وAI_LLM_MODEL وAI_LLM_TIMEOUT وAI_LLM_MAX_OUTPUT_TOKENS وAI_LLM_MAX_TOOL_ROUNDS وAI_ALLOW_RULE_FALLBACK.

## الحماية
Grounding للبيانات الحية، Tool Calling مقيد بالأدوات، منع SQL والأسرار، تأكيد العمليات الحساسة، صلاحيات الخادم، وسجلات التدقيق.

## الذاكرة
context_state للمحادثة وai_user_memories للذاكرة طويلة المدى، مع منع تخزين الأسرار.

## النشر
Laravel وVite داخل Docker، وخادم النموذج خدمة مستقلة اختيارية تربط عبر AI_LLM_BASE_URL.

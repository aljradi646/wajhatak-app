#!/usr/bin/env bash
# =============================================================================
# بديل أخف لتشغيل النموذج المحلي عبر Ollama (يعمل حتى بدون GPU).
#
# ملاحظات إنتاجية:
#  - على CPU فقط: استخدم نموذجًا صغيرًا (qwen2.5:7b-instruct أو أصغر).
#    زمن الاستجابة سيكون أعلى (3-10 ثوانٍ للرد) لكنه مقبول للمساعد.
#  - مع GPU واحد: ollama يستفيد تلقائيًا من CUDA.
#
# التثبيت:  curl -fsSL https://ollama.com/install.sh | sh
# التشغيل:  ./serve_ollama.sh
#
# بعد التشغيل في Laravel:
#   AI_INFERENCE_BASE_URL=http://127.0.0.1:11434/v1
#   AI_MODEL=glm4:9b          (أو النموذج الذي تختاره)
# =============================================================================
set -euo pipefail

MODEL="${MODEL:-glm4:9b}"
OLLAMA_HOST="${OLLAMA_HOST:-0.0.0.0:11434}"

echo "==> سحب النموذج ${MODEL} (أول مرة فقط)..."
ollama pull "${MODEL}"

echo "==> تشغيل خادم Ollama على ${OLLAMA_HOST}"
exec ollama serve

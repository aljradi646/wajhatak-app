#!/usr/bin/env bash
# =============================================================================
# تشغيل خادم استدلال GLM المحلي عبر vLLM (OpenAI-compatible API)
# البيئة المستهدفة: خادم GPU داخل الشبكة الخاصة (Linux + NVIDIA CUDA)
#
# المتطلبات:
#   pip install "vllm>=0.7.0"     (CUDA 12.1+ ، ~24GB VRAM لنموذج GLM-4.6 FP16)
#
# الاستخدام:
#   ./serve_vllm.sh                                  # افتراضي: glm-4.6 على :8000
#   MODEL=glm-4.6 PORT=8000 ./serve_vllm.sh
#   MODEL=Qwen2.5-7B-Instruct MAX_LEN=8192 ./serve_vllm.sh   # GPU أصغر (16GB)
#
# بعد التشغيل: اضبط في Laravel
#   AI_INFERENCE_BASE_URL=http://<gpu-server-ip>:8000/v1
#   AI_MODEL=glm-4.6
# =============================================================================
set -euo pipefail

MODEL="${MODEL:-zai-org/glm-4.6}"
PORT="${PORT:-8000}"
HOST="${HOST:-0.0.0.0}"          # داخل الشبكة الخاصة فقط — لا تكشفه للإنترنت
MAX_LEN="${MAX_LEN:-16384}"
GPU_MEM_UTIL="${GPU_MEM_UTIL:-0.90}"

echo "==> تشغيل vLLM: model=${MODEL} host=${HOST} port=${PORT} ctx=${MAX_LEN}"

exec python -m vllm.entrypoints.openai.api_server \
  --model "${MODEL}" \
  --served-model-name glm-4.6 \
  --host "${HOST}" \
  --port "${PORT}" \
  --max-model-len "${MAX_LEN}" \
  --gpu-memory-utilization "${GPU_MEM_UTIL}" \
  --disable-log-requests

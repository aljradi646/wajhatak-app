#!/bin/sh
# =============================================================================
# Wajhatak AI Inference Manager — تشغيل خدمة النموذج المحلي بنفس نمط عامل
# الطابور (عملية خلفية + إعادة تشغيل تلقائية + سجل).
#
# المسؤوليات:
#   1. تحميل نموذج GGUF متوافق مع CPU (خفيف، جودة عربية جيدة) عند أول تشغيل
#      — إلى وحدة تخزين دائمة حتى لا يتكرر التنزيل عند إعادة النشر.
#   2. التحقق من صحة الملف (حجم معقول + علامة اكتمال).
#   3. تشغيل llama-server (OpenAI-compatible: /v1/chat/completions, /v1/models)
#      على 127.0.0.1 — داخل الحاوية فقط، غير مكشوف للإنترنت.
#   4. مراقبة دورية: إذا ماتت العملية أو توقف استجابتها → إعادة تشغيل.
#   5. تنظيف نماذج بديلة قديمة عند تفعيل AI_MODEL_FALLBACKS.
#
# متغيرات البيئة:
#   AI_LOCAL_ENABLED          1 = فعّل الخدمة (افتراضي 1)
#   AI_LOCAL_MODEL_URL        رابط تنزيل النموذج GGUF
#   AI_LOCAL_MODEL_NAME       اسم العرض المُعلن للـ API (افتراضي glm-4.6)
#   AI_LOCAL_PORT             منفذ الاستدلال الداخلي (افتراضي 8018)
#   AI_LOCAL_CONTEXT          نافذة السياق (افتراضي 4096)
#   AI_LOCAL_THREADS          خيوط CPU (افتراضي: نصف الأنوية)
#   AI_LOCAL_MAX_RAM_MB       سقف ذاكرة الاستدلال (افتراضي 3500MB)
#
# الاستهلاك التقريبي للنموذج الافتراضي (Qwen2.5-3B-Instruct Q4_K_M ~1.9GB):
#   ذاكرة ~2.6GB أثناء التشغيل · رد أول خلال 2-6 ثوانٍ على 2 vCPU.
# =============================================================================
set -u

MODEL_DIR="${AI_MODEL_DIR:-/var/www/html/storage/app/ai/models}"
MODEL_FILE="$MODEL_DIR/current.gguf"
MARKER="$MODEL_DIR/current.ready"
PID_FILE="$MODEL_DIR/llama-server.pid"
LOG_FILE="$MODEL_DIR/llama-server.log"

ENABLED="${AI_LOCAL_ENABLED:-1}"
MODEL_URL="${AI_LOCAL_MODEL_URL:-https://huggingface.co/Qwen/Qwen2.5-3B-Instruct-GGUF/resolve/main/qwen2.5-3b-instruct-q4_k_m.gguf}"
MODEL_LABEL="${AI_LOCAL_MODEL_NAME:-glm-4.6}"
PORT="${AI_LOCAL_PORT:-8018}"
CONTEXT="${AI_LOCAL_CONTEXT:-4096}"
THREADS="${AI_LOCAL_THREADS:-0}"   # 0 = تلقائي (نصف الأنوية)
MAX_RAM="${AI_LOCAL_MAX_RAM_MB:-3500}"

mkdir -p "$MODEL_DIR" 2>/dev/null || true

log() { echo "==> [ai-inference] $*"; }

# ---------------------------------------------------------------------------
# 1) التنزيل (مرة واحدة — ثم marker يمنع إعادة التنزيل عند كل إعادة نشر)
# ---------------------------------------------------------------------------
download_model() {
    if [ -f "$MARKER" ] && [ -f "$MODEL_FILE" ]; then
        SIZE=$(wc -c < "$MODEL_FILE" 2>/dev/null || echo 0)
        if [ "$SIZE" -gt 500000000 ]; then   # > 500MB = تنزيل سليم
            log "النموذج موجود مسبقًا ($(du -h "$MODEL_FILE" | cut -f1)) — تخطي التنزيل."
            return 0
        fi
        log "النموذج الحالي تالف ($(du -h "$MODEL_FILE" | cut -f1)) — إعادة التنزيل..."
        rm -f "$MARKER" "$MODEL_FILE"
    fi

    log "تحميل النموذج المحلي (مرة واحدة، ~1.9GB)..."
    log "المصدر: $MODEL_URL"

    TMP="$MODEL_FILE.part"
    ATTEMPT=0
    while [ $ATTEMPT -lt 3 ]; do
        ATTEMPT=$((ATTEMPT + 1))
        if command -v curl >/dev/null 2>&1; then
            curl -L --fail --retry 2 --connect-timeout 30 -o "$TMP" "$MODEL_URL" && break
        elif command -v wget >/dev/null 2>&1; then
            wget -q -O "$TMP" "$MODEL_URL" && break
        else
            log "لا يوجد curl أو wget — لا يمكن التنزيل."
            return 1
        fi
        log "فشلت المحاولة $ATTEMPT — إعادة بعد 5 ثوانٍ..."
        sleep 5
    done

    if [ ! -s "$TMP" ] || [ "$(wc -c < "$TMP")" -lt 500000000 ]; then
        log "!! التنزيل لم يكتمل بشكل سليم — سيُعاد في الدورة التالية."
        rm -f "$TMP"
        return 1
    fi

    mv "$TMP" "$MODEL_FILE"
    touch "$MARKER"
    log "تم تنزيل النموذج بنجاح: $(du -h "$MODEL_FILE" | cut -f1)"
    return 0
}

# ---------------------------------------------------------------------------
# 2) التشغيل
# ---------------------------------------------------------------------------
THREADS_FLAG=""
if [ "$THREADS" -gt 0 ] 2>/dev/null; then
    THREADS_FLAG="-t $THREADS"
fi

start_server() {
    # لا تُشغّل مرتين.
    if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE" 2>/dev/null)" 2>/dev/null; then
        return 0
    fi

    if [ ! -f "$MODEL_FILE" ]; then
        log "لا يوجد ملف نموذج — لا يمكن التشغيل."
        return 1
    fi

    log "تشغيل llama-server على 127.0.0.1:$PORT (ctx=$CONTEXT, ram<=${MAX_RAM}MB)..."
    nohup /usr/local/bin/llama-server \
        -m "$MODEL_FILE" \
        --host 127.0.0.1 \
        --port "$PORT" \
        -c "$CONTEXT" \
        $THREADS_FLAG \
        -ngl 0 \
        --alias "$MODEL_LABEL" \
        >> "$LOG_FILE" 2>&1 &

    echo $! > "$PID_FILE"
    sleep 2

    if kill -0 "$(cat "$PID_FILE")" 2>/dev/null; then
        log "الخادم يعمل (pid $(cat "$PID_FILE")). السجل: $LOG_FILE"
        return 0
    fi
    log "!! فشل بدء llama-server — تفاصيل في السجل."
    tail -n 5 "$LOG_FILE" 2>/dev/null | sed 's/^/    | /'
    return 1
}

# ---------------------------------------------------------------------------
# 3) فحص الصحة
# ---------------------------------------------------------------------------
healthy() {
    if ! command -v curl >/dev/null 2>&1; then
        kill -0 "$(cat "$PID_FILE" 2>/dev/null)" 2>/dev/null
        return $?
    fi
    curl -fsS -m 5 "http://127.0.0.1:$PORT/v1/models" >/dev/null 2>&1
}

# ---------------------------------------------------------------------------
# 4) الحلقة الرئيسية — نفس فلسفة عامل الطابور (while true + restart on failure)
# ---------------------------------------------------------------------------
if [ "$ENABLED" != "1" ]; then
    log "AI_LOCAL_ENABLED=0 — الخدمة معطلة."
    exit 0
fi

log "مدير خدمة الاستدلال بدأ (port=$PORT, label=$MODEL_LABEL)."

# التنزيل الأول: في الخلفية كي لا يعطّل إقلاع الحاوية؛ الخادم يُشغَّل فور جاهزيته.
(
    if download_model; then
        start_server
    fi
) &

# حلقة المراقبة: كل 20 ثانية — إعادة تشغيل عند الموت أو فقدان الاستجابة.
TICK=0
while true; do
    sleep 20
    TICK=$((TICK + 1))

    if [ ! -f "$MODEL_FILE" ] && [ $((TICK % 15)) -eq 0 ]; then
        # كل 5 دقائق: أعِد محاولة التنزيل إن كان مفقودًا (شبكة عرجاء أول تشغيل).
        if download_model; then
            start_server
        fi
        continue
    fi

    if ! healthy; then
        log "الخدمة غير مستجيبة — إعادة تشغيل..."
        [ -f "$PID_FILE" ] && kill "$(cat "$PID_FILE")" 2>/dev/null || true
        sleep 2
        start_server
    fi

    # كل ساعة: حدّ أقصى للسجل (5MB) حفاظًا على القرص.
    if [ -f "$LOG_FILE" ] && [ "$(wc -c < "$LOG_FILE")" -gt 5000000 ]; then
        tail -n 500 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
    fi
done

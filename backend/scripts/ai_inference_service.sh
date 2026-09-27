#!/bin/sh
# =============================================================================
# Wajhatak AI Inference Manager v2 — خدمة استدلال ذاتية التمهيد
#
# نفس نمط عامل الطابور: عملية خلفية + إعادة تشغيل تلقائية + سجلات.
# الجديد: لا تفشل أبدًا بسبب نقص أي مكوّن — تؤمّن كل شيء بنفسها:
#   1. المحرك (llama-server):  bundled في الصورة ← أو مخزنًا في الحجم الدائم
#      ← أو ثنائي جاهز من AI_LOCAL_BINARY_URL ← أو بناء من المصدر (apk+cmake).
#   2. النموذج: تنزيل GGUF متوافق مع CPU مرة واحدة إلى الحجم الدائم.
#   3. التشغيل والمراقبة: llama-server على 127.0.0.1 + watchdog كل 20 ثانية.
#
# الاستدعاءات:
#   sh ai_inference_service.sh daemon     ← entrypoint (خلفية + watchdog)
#   sh ai_inference_service.sh bootstrap  ← متزامن (php artisan ai:bootstrap)
#   sh ai_inference_service.sh status     ← سطر حالة سريع للسجلات
#
# متغيرات البيئة:
#   AI_LOCAL_ENABLED       1 = فعّل الخدمة (افتراضي)
#   AI_LOCAL_MODEL_URL     رابط النموذج GGUF (Qwen2.5-3B افتراضيًا ~1.9GB)
#   AI_LOCAL_MODEL_NAME    الاسم المُعلن في /v1/models (افتراضي glm-4.6)
#   AI_LOCAL_PORT          منفذ داخلي (افتراضي 8018 على 127.0.0.1)
#   AI_LOCAL_CONTEXT       نافذة السياق (4096)
#   AI_LOCAL_THREADS       خيوط CPU (0 = تلقائي)
#   AI_LOCAL_BINARY_URL    (اختياري) ثنائي llama-server جاهز — يتخطى البناء
# =============================================================================
set -u

BUNDLED_BIN="/usr/local/bin/llama-server"
CACHE_DIR="${AI_MODEL_DIR:-/var/www/html/storage/app/ai/models}"
CACHE_BIN="$CACHE_DIR/llama-server"
MODEL_FILE="$CACHE_DIR/current.gguf"
MARKER="$CACHE_DIR/current.ready"
PID_FILE="$CACHE_DIR/llama-server.pid"
LOG_FILE="$CACHE_DIR/llama-server.log"
BUILD_LOG="$CACHE_DIR/build.log"

ENABLED="${AI_LOCAL_ENABLED:-1}"
MODEL_URL="${AI_LOCAL_MODEL_URL:-https://huggingface.co/Qwen/Qwen2.5-3B-Instruct-GGUF/resolve/main/qwen2.5-3b-instruct-q4_k_m.gguf}"
MODEL_LABEL="${AI_LOCAL_MODEL_NAME:-glm-4.6}"
PORT="${AI_LOCAL_PORT:-8018}"
CONTEXT="${AI_LOCAL_CONTEXT:-4096}"
THREADS="${AI_LOCAL_THREADS:-0}"
BINARY_URL="${AI_LOCAL_BINARY_URL:-}"

mkdir -p "$CACHE_DIR" 2>/dev/null || true

log() { echo "==> [ai-inference] $(date '+%Y-%m-%d %H:%M:%S') $*"; }
have() { command -v "$1" >/dev/null 2>&1; }

# الثنائي الفعلي المستخدم: bundled ← cache ← (لا شيء)
active_binary() {
    if [ -x "$BUNDLED_BIN" ]; then
        echo "$BUNDLED_BIN"
    elif [ -x "$CACHE_BIN" ]; then
        echo "$CACHE_BIN"
    else
        echo ""
    fi
}

# ---------------------------------------------------------------------------
# 1) تأمين المحرك — لا تنجح إلا بأحد المسارات الأربعة
# ---------------------------------------------------------------------------
ensure_binary() {
    CURRENT=$(active_binary)
    if [ -n "$CURRENT" ]; then
        log "المحرك موجود: $CURRENT"
        return 0
    fi

    # (أ) ثنائي جاهز من رابط مخصص (يتخطى البناء تمامًا).
    if [ -n "$BINARY_URL" ] && have curl; then
        log "تنزيل محرك جاهز من: $BINARY_URL"
        if curl -L --fail --connect-timeout 20 -o "$CACHE_BIN.dl" "$BINARY_URL" 2>>"$BUILD_LOG"; then
            chmod +x "$CACHE_BIN.dl"
            mv "$CACHE_BIN.dl" "$CACHE_BIN"
            log "تم تنزيل المحرك إلى $CACHE_BIN."
            return 0
        fi
        rm -f "$CACHE_BIN.dl"
        log "فشل تنزيل الثنائي الجاهز — التحويل للبناء من المصدر."
    fi

    # (ب) بناء من المصدر داخل الحاوية (Alpine: apk + cmake + git).
    log "بناء llama-server من المصدر (مرة واحدة — 5 إلى 20 دقيقة حسب المعالج)..."
    log "تثبيت أدوات البناء (build-base cmake git)..."
    apk add --no-cache build-base cmake git >>"$BUILD_LOG" 2>&1 || {
        log "!! تعذر تثبيت أدوات البناء — راجع $BUILD_LOG"
        return 1
    }

    # خفّض التوازي على الحاويات الصغيرة لتفادي قتل البناء (OOM).
    JOBS=2
    if have awk; then
        AVAIL_KB=$(awk '/MemAvailable/{print int($2)}' /proc/meminfo 2>/dev/null || echo 0)
        if [ "${AVAIL_KB:-0}" -gt 0 ] && [ "$AVAIL_KB" -lt 1500000 ]; then
            JOBS=1
            log "الذاكرة المتاحة محدودة ($((AVAIL_KB / 1024))MB) — بناء بخيط واحد."
        fi
    fi

    SRC="$CACHE_DIR/src"
    rm -rf "$SRC"
    log "سحب كود llama.cpp (نسخة واحدة)..."
    git clone --depth 1 https://github.com/ggml-org/llama.cpp "$SRC" >>"$BUILD_LOG" 2>&1 || {
        log "!! فشل git clone — راجع $BUILD_LOG"
        return 1
    }

    log "تهيئة البناء (jobs=$JOBS)..."
    cmake -S "$SRC" -B "$SRC/build" \
        -DCMAKE_BUILD_TYPE=Release \
        -DGGML_NATIVE=OFF \
        -DGGML_OPENMP=OFF \
        -DLLAMA_CURL=OFF \
        -DLLAMA_BUILD_TESTS=OFF \
        -DLLAMA_BUILD_EXAMPLES=OFF \
        -DBUILD_SHARED_LIBS=OFF \
        >>"$BUILD_LOG" 2>&1 || { log "!! فشل cmake — راجع $BUILD_LOG"; return 1; }

    log "بناء llama-server (قد يستغرق عدة دقائق)... "
    cmake --build "$SRC/build" -j"$JOBS" --target llama-server >>"$BUILD_LOG" 2>&1 || {
        log "!! فشل البناء — آخر الأسطر:"
        tail -n 5 "$BUILD_LOG" 2>/dev/null | sed 's/^/    | /'
        return 1
    }

    BUILT="$SRC/build/bin/llama-server"
    if [ ! -x "$BUILT" ]; then
        log "!! الثنائي غير موجود بعد البناء — راجع $BUILD_LOG"
        return 1
    fi

    cp "$BUILT" "$CACHE_BIN" && chmod +x "$CACHE_BIN"
    rm -rf "$SRC"
    log "تم بناء المحرك وتخزينه في الحجم الدائم: $CACHE_BIN (يبقى بعد إعادة النشر)."
    return 0
}

# ---------------------------------------------------------------------------
# 2) تأمين النموذج — تنزيل مرة واحدة مع علامة اكتمال
# ---------------------------------------------------------------------------
ensure_model() {
    if [ -f "$MARKER" ] && [ -f "$MODEL_FILE" ]; then
        SIZE=$(wc -c < "$MODEL_FILE" 2>/dev/null || echo 0)
        if [ "$SIZE" -gt 500000000 ]; then
            log "النموذج جاهز مسبقًا ($(du -h "$MODEL_FILE" | cut -f1)) — تخطي التنزيل."
            return 0
        fi
        log "ملف النموذج تالف — إعادة التنزيل..."
        rm -f "$MARKER" "$MODEL_FILE"
    fi

    log "تنزيل النموذج المحلي (~1.9GB، مرة واحدة)..."
    log "المصدر: $MODEL_URL"

    TMP="$MODEL_FILE.part"
    ATTEMPT=0
    while [ $ATTEMPT -lt 3 ]; do
        ATTEMPT=$((ATTEMPT + 1))
        if have curl; then
            curl -L --fail --retry 2 --connect-timeout 30 -o "$TMP" "$MODEL_URL" && break
        elif have wget; then
            wget -q -O "$TMP" "$MODEL_URL" && break
        else
            log "!! لا يوجد curl أو wget في الحاوية."
            return 1
        fi
        log "فشلت المحاولة $ATTEMPT — إعادة بعد 5 ثوانٍ..."
        sleep 5
    done

    if [ ! -s "$TMP" ] || [ "$(wc -c < "$TMP")" -lt 500000000 ]; then
        log "!! التنزيل لم يكتمل — سيُعاد تلقائيًا."
        rm -f "$TMP"
        return 1
    fi

    mv "$TMP" "$MODEL_FILE"
    touch "$MARKER"
    log "تم تنزيل النموذج: $(du -h "$MODEL_FILE" | cut -f1)"
    return 0
}

# ---------------------------------------------------------------------------
# 3) تشغيل الخادم وفحص الصحة
# ---------------------------------------------------------------------------
THREADS_FLAG=""
if [ "$THREADS" -gt 0 ] 2>/dev/null; then
    THREADS_FLAG="-t $THREADS"
fi

start_server() {
    BIN=$(active_binary)
    if [ -z "$BIN" ] || [ ! -f "$MODEL_FILE" ]; then
        log "لا يمكن التشغيل بعد (محرك=$( [ -n "$BIN" ] && echo جاهز || echo مفقود )، نموذج=$( [ -f "$MODEL_FILE" ] && echo جاهز || echo مفقود ))"
        return 1
    fi

    if [ -f "$PID_FILE" ] && kill -0 "$(cat "$PID_FILE" 2>/dev/null)" 2>/dev/null; then
        return 0
    fi

    log "تشغيل llama-server على 127.0.0.1:$PORT (ctx=$CONTEXT, label=$MODEL_LABEL)..."
    nohup "$BIN" \
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
        log "الخادم يعمل (pid $(cat "$PID_FILE"))."
        return 0
    fi
    log "!! فشل بدء llama-server — آخر أسطر السجل:"
    tail -n 5 "$LOG_FILE" 2>/dev/null | sed 's/^/    | /'
    return 1
}

healthy() {
    if have curl; then
        curl -fsS -m 5 "http://127.0.0.1:$PORT/v1/models" >/dev/null 2>&1
    else
        kill -0 "$(cat "$PID_FILE" 2>/dev/null)" 2>/dev/null
    fi
}

# ---------------------------------------------------------------------------
# المسارات: bootstrap (متزامن) / daemon (خلفية) / status
# ---------------------------------------------------------------------------
bootstrap_all() {
    ensure_binary || return 1
    ensure_model || return 1
    start_server || return 1

    # انتظار حتى يستجيب فعليًا (تحميل النموذج للذاكرة).
    if have curl; then
        TRIES=0
        while [ $TRIES -lt 60 ]; do
            if healthy; then
                log "الاستدلال جاهز الآن على http://127.0.0.1:$PORT/v1 ✓"
                return 0
            fi
            TRIES=$((TRIES + 1))
            sleep 5
        done
        log "!! الخادم بدأ لكنه لا يستجيب بعد — قد يكون ما زال يحمّل النموذج للذاكرة."
        return 1
    fi

    return 0
}

daemon_loop() {
    log "مدير خدمة الاستدلال بدأ (port=$PORT, label=$MODEL_LABEL)."

    # التمهيد الأول في الخلفية كي لا يعطّل إقلاع الحاوية.
    (
        bootstrap_all
    ) &

    TICK=0
    while true; do
        sleep 20
        TICK=$((TICK + 1))

        # مكونات ناقصة؟ أعد التمهيد كل 5 دقائق (شبكة عرجاء / بناء فاشل).
        if [ -z "$(active_binary)" ] || [ ! -f "$MODEL_FILE" ]; then
            if [ $((TICK % 15)) -eq 0 ]; then
                log "مكونات ناقصة — إعادة محاولة التمهيد..."
                bootstrap_all
            fi
            continue
        fi

        # الخدمة تعمل؟ إن لا → إعادة تشغيل.
        if ! healthy; then
            log "الخدمة غير مستجيبة — إعادة تشغيل..."
            [ -f "$PID_FILE" ] && kill "$(cat "$PID_FILE")" 2>/dev/null || true
            sleep 2
            start_server
        fi

        # تقليم السجل كل ساعة (سقف 5MB).
        if [ -f "$LOG_FILE" ] && [ "$(wc -c < "$LOG_FILE")" -gt 5000000 ]; then
            tail -n 500 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
        fi
    done
}

quick_status() {
    BIN=$(active_binary)
    echo "engine=$([ -n "$BIN" ] && echo "$BIN" || echo missing) model=$([ -f "$MARKER" ] && echo ready || echo pending) pid=$( [ -f "$PID_FILE" ] && cat "$PID_FILE" || echo - )"
}

case "${1:-daemon}" in
    bootstrap) bootstrap_all ;;
    daemon)
        if [ "$ENABLED" != "1" ]; then
            log "AI_LOCAL_ENABLED=0 — الخدمة معطلة."
            exit 0
        fi
        daemon_loop
        ;;
    status) quick_status ;;
    *) echo "usage: $0 {daemon|bootstrap|status}" ;;
esac

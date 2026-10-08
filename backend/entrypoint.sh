#!/bin/sh
# =============================================================================
# Wajhatak Laravel entrypoint
#
# Runs every time a container starts. It:
#   1. Bootstraps a default .env when none exists (production defaults), so the
#      app never depends on dashboard variables being present.
#   2. Validates that a real MySQL connection is configured (never silently
#      falls back to SQLite) and splits a mysql:// URL out of DB_HOST if needed.
#   3. Waits for the database (via `db:show`, which works on a fresh DB).
#   4. Generates an APP_KEY on first boot when missing.
#   5. Prepares storage and creates the public storage symlink.
#   6. Migrations run on EVERY boot (idempotent) so the live database always
#      matches the deployed code — new tables/columns for the AI assistant,
#      agent verification and email codes included. Seeds run exactly ONCE,
#      guarded by the Setting 'system_initialized' (RealDataSeeder).
#   6b. Runs the AI engine self-test and `ai:doctor --fix` (real health check
#      of the assistant: schema, settings, index, live search, live chat) so
#      any assistant problem is visible in the deploy log and self-healed.
#   7. Caches config/routes/views.
#   8. For the "app" service: starts the queue worker (deferred notifications)
#      in the background and serves the app with `php artisan serve` on $PORT.
#
# المساعد العقاري الذكي محرك حتمي 100% يعمل داخل Laravel مباشرة:
# لا نموذج لغوي، لا خادم استدلال، ولا أي ملفات تُحمّل — يعمل فورًا على
# أي استضافة بأصغر موارد (حجم الصورة أقل من 500MB بلا أي نموذج).
# =============================================================================
set -e

SERVICE_TYPE="${RAILWAY_SERVICE_TYPE:-app}"
echo "==> [Wajhatak] Container starting (type: ${SERVICE_TYPE})"

# Compatibility guard: older Railway projects may still expose the Laravel
# image through a service carrying RAILWAY_SERVICE_TYPE=ai. Do NOT take the
# application offline in that case; the Laravel image remains a valid web app.
# A real LLM service uses deploy/llm/Dockerfile and never executes this script.
if [ "${SERVICE_TYPE}" = "ai" ]; then
    echo "!! [Wajhatak] Laravel image received RAILWAY_SERVICE_TYPE=ai; treating it as app for availability."
    echo "   The dedicated AI inference service should use deploy/llm/Dockerfile."
    SERVICE_TYPE=app
fi

# ---------------------------------------------------------------------------
# Boot the framework so we can run artisan reliably
# ---------------------------------------------------------------------------
cd /var/www/html

# ---------------------------------------------------------------------------
# 1. Default .env (production). Only written when missing; real environment
#    variables (from Railway) always take precedence over this file, so a
#    dashboard variable like DB_URL overrides these defaults automatically.
# ---------------------------------------------------------------------------
if [ ! -f .env ]; then
    echo "==> [Wajhatak] No .env found - writing production defaults..."
    cat > .env <<'ENV'
APP_ENV=production
APP_DEBUG=false
APP_LOCALE=ar
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=ar
APP_URL=http://localhost:8080
LOG_CHANNEL=stack
LOG_LEVEL=warning
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local
BROADCAST_CONNECTION=log
MAIL_MAILER=log
CURRENCY_DEFAULT=YER
LUX_ALLOW_DEMO_SEED=false
ENV
    # If running on Railway, APP_URL should point to the public domain so that
    # absolute links (queue jobs, notifications, emails) use the real https URL.
    if [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
        sed -i "s#^APP_URL=.*#APP_URL=https://${RAILWAY_PUBLIC_DOMAIN}#" .env
        echo "==> [Wajhatak] APP_URL set to https://${RAILWAY_PUBLIC_DOMAIN}"
    fi
fi

# The LUX_ALLOW_DEMO_SEED flag is no longer used: the demo (trial) dataset is
# disabled for production. Real data comes from RealDataSeeder only.

# ---------------------------------------------------------------------------
# 1.5. Refresh stale vendor/autoload when the image was rebuilt from a stale cache
#      or when an older Docker layer still contains the removed Pail package.
# ---------------------------------------------------------------------------
if [ ! -f vendor/autoload.php ] || [ -d vendor/laravel/pail ]; then
    echo "==> [Wajhatak] Installing Laravel dependencies for a clean runtime bootstrap..."
    composer install --no-interaction --no-progress --prefer-dist --no-dev --no-scripts --no-ansi
    rm -rf vendor/laravel/pail 2>/dev/null || true
    composer dump-autoload --optimize --no-dev --no-interaction --no-ansi
fi

# اختبار ذاتي لمحرك المساعد الحتمي (بلا قاعدة بيانات ولا vendor) — يكشف أي
# تعبير نمطي معطوب أو تراجع في فهم العربية قبل أن يصل للمستخدمين.
if [ -f scripts/ai_selftest/run.php ]; then
    if php scripts/ai_selftest/run.php >/dev/null 2>&1; then
        echo "==> [Wajhatak] AI engine self-test: OK"
    else
        echo "!! [Wajhatak] AI engine self-test FAILED — راجع: php scripts/ai_selftest/run.php" >&2
        php scripts/ai_selftest/run.php 2>&1 | tail -n 25 | sed 's/^/      | /' >&2 || true
        exit 1
    fi
fi

# ---------------------------------------------------------------------------
# 2. Resolve the production MySQL connection.
#
# Railway exposes MySQL connection values on the MySQL service itself:
# MYSQL_URL / MYSQLHOST / MYSQLPORT / MYSQLDATABASE / MYSQLUSER / MYSQLPASSWORD.
# A consuming app service must reference those variables explicitly. We always
# prefer these canonical MySQL variables over a generic DB_URL/DB_HOST because
# a generic value can accidentally point back to the application service.
# ---------------------------------------------------------------------------

if [ "${WJ_ALLOW_SQLITE:-false}" = "true" ]; then
    export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
else
    export DB_CONNECTION=mysql
fi

case "$DB_CONNECTION" in
    mysql) ;;
    sqlite)
        echo "==> [Wajhatak] SQLite explicitly enabled (WJ_ALLOW_SQLITE=true)." >&2
        ;;
    *)
        echo "!! [Wajhatak] DB_CONNECTION='$DB_CONNECTION' is unsupported. Production requires mysql." >&2
        exit 1
        ;;
esac

parse_mysql_url() {
    _u="$1"
    _creds="${_u#*://}"
    _auth="${_creds%%@*}"
    _rest="${_creds#*@}"
    _hostport="${_rest%%/*}"
    _db="${_rest#*/}"
    _db="${_db%%\?*}"
    _db="${_db%%#*}"
    _user="${_auth%%:*}"
    _pass="${_auth#*:}"

    case "$_hostport" in
        *:*)
            _host="${_hostport%%:*}"
            _port="${_hostport##*:}"
            ;;
        *)
            _host="$_hostport"
            _port=3306
            ;;
    esac

    export DB_HOST="$_host"
    export DB_PORT="${DB_PORT:-$_port}"
    export DB_DATABASE="$_db"
    export DB_USERNAME="$_user"
    export DB_PASSWORD="$_pass"

    unset _u _creds _auth _rest _hostport _db _user _pass _host _port
}

# 1) Canonical Railway MySQL URL.
if [ -n "${MYSQL_URL:-}" ]; then
    echo "==> [Wajhatak] Using Railway MYSQL_URL as the canonical database source."
    parse_mysql_url "${MYSQL_URL}"
# 2) Canonical Railway MySQL split variables.
elif [ -n "${MYSQLHOST:-}" ] && [ -n "${MYSQLDATABASE:-}" ] && [ -n "${MYSQLUSER:-}" ]; then
    echo "==> [Wajhatak] Using Railway MYSQLHOST/MYSQLDATABASE/MYSQLUSER variables."
    export DB_HOST="${MYSQLHOST}"
    export DB_PORT="${MYSQLPORT:-3306}"
    export DB_DATABASE="${MYSQLDATABASE}"
    export DB_USERNAME="${MYSQLUSER}"
    export DB_PASSWORD="${MYSQLPASSWORD:-}"
# 3) Explicit Laravel DB_URL fallback.
elif [ -n "${DB_URL:-}" ]; then
    case "${DB_URL}" in
        mysql://*|mariadb://*)
            echo "==> [Wajhatak] Using explicit DB_URL."
            parse_mysql_url "${DB_URL}"
            ;;
    esac
fi

# Fallback to split DB_* values only when no canonical MYSQL* source was
# supplied. Never replace a valid explicit DB_* value with empty values.
if [ -z "${DB_HOST:-}" ] && [ -n "${MYSQLHOST:-}" ]; then
    export DB_HOST="${MYSQLHOST}"
fi
if [ -z "${DB_PORT:-}" ] && [ -n "${MYSQLPORT:-}" ]; then
    export DB_PORT="${MYSQLPORT}"
fi
if [ -z "${DB_DATABASE:-}" ] && [ -n "${MYSQLDATABASE:-}" ]; then
    export DB_DATABASE="${MYSQLDATABASE}"
fi
if [ -z "${DB_USERNAME:-}" ] && [ -n "${MYSQLUSER:-}" ]; then
    export DB_USERNAME="${MYSQLUSER}"
fi
if [ -z "${DB_PASSWORD:-}" ] && [ -n "${MYSQLPASSWORD:-}" ]; then
    export DB_PASSWORD="${MYSQLPASSWORD}"
fi

# Never allow an accidental self-reference such as
# wajhatak-app.railway.internal:3306. That hostname belongs to this app
# service, not the MySQL service, and produces connection-refused errors.
SELF_HOST="${RAILWAY_PRIVATE_DOMAIN:-}"
if [ -z "$SELF_HOST" ] && [ -n "${RAILWAY_SERVICE_NAME:-}" ]; then
    SELF_HOST="${RAILWAY_SERVICE_NAME}.railway.internal"
fi

if [ -n "$SELF_HOST" ] && [ "${DB_HOST:-}" = "$SELF_HOST" ]; then
    echo "!! [Wajhatak] DB_HOST resolves to this application service ('$DB_HOST')." >&2
    echo "   Set the app service variables to references from the MySQL service:" >&2
    echo '   DB_URL='"$"'{{MySQL.MYSQL_URL}}' >&2
    echo '   or DB_HOST='"$"'{{MySQL.MYSQLHOST}}, DB_PORT='"$"'{{MySQL.MYSQLPORT}}, DB_DATABASE='"$"'{{MySQL.MYSQLDATABASE}},' >&2
    echo '      DB_USERNAME='"$"'{{MySQL.MYSQLUSER}}, DB_PASSWORD='"$"'{{MySQL.MYSQLPASSWORD}}' >&2
    exit 1
fi

DB_HOST_VALUE="${DB_HOST:-}"
case "$DB_HOST_VALUE" in
    ''|*\$\{\{*|*\$\{*)
        echo '!! [Wajhatak] MySQL connection is NOT configured (DB_HOST is empty or unresolved).' >&2
        echo '   Reference the MySQL service variables explicitly from the Laravel service.' >&2
        echo "   Current values: DB_CONNECTION=$DB_CONNECTION, DB_HOST='${DB_HOST:-}', DB_PORT='${DB_PORT:-}'," >&2
        echo "                    DB_DATABASE='${DB_DATABASE:-}', DB_USERNAME='${DB_USERNAME:-}'." >&2
        exit 1
        ;;
esac

if [ -z "${DB_DATABASE:-}" ] || [ -z "${DB_USERNAME:-}" ]; then
    echo "!! [Wajhatak] MySQL credentials are incomplete. DB_DATABASE and DB_USERNAME are required." >&2
    exit 1
fi

echo "==> [Wajhatak] Using MySQL at ${DB_HOST_VALUE}."
php artisan config:clear >/dev/null 2>&1 || true
echo "==> [Wajhatak] DB target: host=${DB_HOST_VALUE}, port=${DB_PORT:-3306}, database=${DB_DATABASE}, user=${DB_USERNAME}."
# ---------------------------------------------------------------------------
# 3. Wait for the database (with real diagnostics)
#
# Probe with `db:show`: it only needs a reachable database and does NOT fail
# on a fresh/empty database (unlike `migrate:status`, which errors with
# "Migration table not found" until the migrations repository exists).
# ---------------------------------------------------------------------------
MAX_RETRIES=60
RETRY=0
FIRST_ERR=""
until OUT=$(php artisan db:show 2>&1); do
    RETRY=$((RETRY + 1))
    if [ -n "$OUT" ]; then
        FIRST_ERR="$OUT"
    fi
    if [ "$RETRY" -eq 1 ]; then
        echo "    db:show failed - first diagnostic output:"
        printf '%s\n' "$OUT" | tail -n 8 | sed 's/^/      | /'
    fi
    if [ "$RETRY" -ge "$MAX_RETRIES" ]; then
        echo "!! [Wajhatak] Database not reachable after $MAX_RETRIES attempts." >&2
        case "$FIRST_ERR" in
            *1045*|*"Access denied"*)
                echo "   Likely cause: wrong MySQL credentials (MYSQLUSER / MYSQLPASSWORD) or the user lacks rights on this database." >&2
                ;;
            *1049*|*"Unknown database"*)
                echo "   Likely cause: the database name (MYSQLDATABASE / DB_DATABASE) is wrong or not provisioned yet by the MySQL service." >&2
                ;;
            *2002*|*2003*|*"Connection refused"*|*"Connection timed out"*|*"Operation timed out"*)
                echo "   Likely cause: the MySQL service is unreachable from this container." >&2
                echo "   - Is it provisioned and deployed in the SAME Railway project as this app?" >&2
                echo "   - Has it finished provisioning? (check the MySQL service logs / status)" >&2
                echo "   - Are this service's variables linked to that MySQL service?" >&2
                ;;
            *2005*|*"Unknown server host"*|*"getaddrinfo"|*"Name or service not known"*)
                echo "   Likely cause: DB_HOST does not resolve - the variable references a service that may not exist or is misspelled." >&2
                ;;
            *)
                echo "   Unexpected error - full message:" >&2
                printf '%s\n' "$FIRST_ERR" | tail -n 12 | sed 's/^/     | /' >&2
                ;;
        esac
        exit 1
    fi
    echo "    Database not ready (attempt $RETRY/$MAX_RETRIES), retrying in 3s..."
    sleep 3
done
echo "==> [Wajhatak] Database is reachable."

# ---------------------------------------------------------------------------
# 4. Application key (generate on first boot if missing)
# ---------------------------------------------------------------------------
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "==> [Wajhatak] APP_KEY missing, generating one for this runtime..."
    GEN_KEY=$(php artisan key:generate --show --force 2>/dev/null || php -r 'echo "base64:".base64_encode(random_bytes(32));')
    export APP_KEY="$GEN_KEY"
    echo "    Generated APP_KEY. NOTE: set APP_KEY explicitly in Railway variables"
    echo "    so it stays stable across redeploys (cross-deploy persistence)."
fi

# ---------------------------------------------------------------------------
# 5. Storage dirs + public storage symlink
# ---------------------------------------------------------------------------
echo "==> [Wajhatak] Preparing storage..."
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/fonts/cache
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
php artisan storage:link >/dev/null 2>&1 || echo "    storage:link unavailable (fallback route serves /storage)."

# Restore the committed real property photos into the runtime disk. Railway's
# disk is ephemeral, so the image-bundle (kept out of storage/ so the Docker
# build keeps it) is copied here on EVERY boot. This guarantees the exact
# relative paths stored in property_images.path resolve to a real JPEG and no
# property ever renders a broken or placeholder image.
if [ -d image-bundle/properties ]; then
    echo "==> [Wajhatak] Restoring property photos from image-bundle..."
    mkdir -p storage/app/public/properties
    cp -rf image-bundle/properties/. storage/app/public/properties/
    echo "==> [Wajhatak] Property photos restored."
fi

# ---------------------------------------------------------------------------
# Database provisioning:
#   • Migrations run on EVERY boot — they are idempotent (Laravel tracks the
#     migrations table), and this is what keeps a previously-provisioned
#     production database in sync with NEW code (new tables/columns for the
#     AI assistant, agent verification, email codes...). Skipping them on
#     later boots left the live DB missing the assistant tables, which made
#     every chat request fail with a generic error.
#   • Seeds run exactly ONCE: RealDataSeeder writes Setting
#     'system_initialized' = 1, and every later boot skips seeds so the
#     existing data is never touched or duplicated.
# ---------------------------------------------------------------------------
if [ "$SERVICE_TYPE" = "app" ]; then
    # -------------------------------------------------------------------------
    # المساعد الذكي محرك حتمي داخل Laravel — لا خدمة استدلال خلفية ولا مجلد نماذج.
    # -------------------------------------------------------------------------
    echo "==> [Wajhatak] Running migrations (idempotent — syncs new tables/columns with the live database)..."
    php artisan migrate --force

    # EmailTemplateSeeder is idempotent and intentionally runs on every boot.
    # This backfills/updates the five built-in production templates even when
    # system_initialized=1 from an older deployment.
    echo "==> [Wajhatak] Synchronizing production email templates..."
    php artisan db:seed --class=EmailTemplateSeeder --force

    export EXPECTED_TEMPLATES="email_verification agent_approved agent_rejected property_published property_rejected"
    MISSING_TEMPLATES=$(php artisan tinker --execute='
        $expected = explode(" ", trim(getenv("EXPECTED_TEMPLATES") ?: ""));
        $missing = array_values(array_filter($expected, fn ($key) => ! \App\Models\EmailTemplate::query()
            ->where("key", $key)
            ->where("is_system", true)
            ->where("is_active", true)
            ->where("status", "published")
            ->exists()));
        echo implode(" ", $missing);
    ' 2>/dev/null || true)

    if [ -n "$MISSING_TEMPLATES" ]; then
        echo "!! [Wajhatak] Required production email templates are still missing: $MISSING_TEMPLATES" >&2
        echo "   The deployment is stopped so the admin UI can never serve an incomplete email catalog." >&2
        exit 1
    fi
    echo "==> [Wajhatak] Production email template catalog: 5/5 synchronized."

    SEEDED_FLAG=$(php artisan tinker --execute="echo \App\Models\Setting::get('system_initialized','0') === '1' ? 'SEEDED' : 'PENDING';" 2>/dev/null || true)

    case "$SEEDED_FLAG" in
        *SEEDED*)
            echo "==> [Wajhatak] Data already seeded (system_initialized=1) — skipping seeds to protect existing data."
            ;;
        *)
            echo "==> [Wajhatak] First-time provisioning: seeding"
            echo "    roles/permissions/locations, the real Sana'a dataset and the admin account..."
            php artisan db:seed --class=DatabaseSeeder --force
            php artisan db:seed --class=RealDataSeeder --force
            php artisan db:seed --class=AdminUserSeeder --force
            ;;
    esac

    # 6b. صحة المساعد الذكي: فحص حقيقي من داخل التطبيق (مخطط + إعدادات +
    #     فهرس مقابل العقارات + بحث حقيقي + محادثة حقيقية)، مع إصلاح آلي
    #     لأي جدول/عمود ناقص أو فهرس فارغ. السبب صار ظاهرًا في سجل النشر
    #     بلا تخمين. غير قاتل: نُكمل التشغيل حتى لو أبلغ عن مشكلة.
    echo "==> [Wajhatak] AI assistant health check (ai:doctor --fix)..."
    php artisan ai:doctor --fix

    # 7. Cache config/routes/views (recomputed from current env each boot)
    echo "==> [Wajhatak] Caching config, routes and views..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    # 7b. Property-image self-healing. Railway's disk is ephemeral, so seeder
    #     images can be wiped on redeploy while DB rows survive. Running the
    #     fixer on every boot re-downloads/re-binds any missing image so the
    #     admin panel and the mobile app always render real photos. It is
    #     idempotent and non-fatal. Override with WJ_RUN_IMAGE_FIX=0 to disable.
    case "${WJ_RUN_IMAGE_FIX:-1}" in
        0|false|no)
            echo "==> [Wajhatak] Skipping property-image self-healing (WJ_RUN_IMAGE_FIX=0)."
            ;;
        *)
            echo "==> [Wajhatak] Self-healing property images..."
            php scripts/fix_property_images.php --quiet
            ;;
    esac
fi

# ---------------------------------------------------------------------------
# 8. Start the requested process
# ---------------------------------------------------------------------------
case "$SERVICE_TYPE" in
    worker)
        echo "==> [Wajhatak] Starting queue worker (foreground)..."
        exec php artisan queue:work --sleep=3 --tries=3 --timeout=60
        ;;
    scheduler)
        echo "==> [Wajhatak] Starting scheduler..."
        while true; do
            php artisan schedule:run --verbose --no-interaction || true
            sleep 60
        done
        ;;
    static)
        echo "==> [Wajhatak] Static asset service ready."
        exec tail -f /dev/null
        ;;
    *)
        # App service: HTTP only. Queue work is handled by the dedicated worker
        # service so deploys cannot accidentally create duplicate consumers.
        if [ "${START_EMBEDDED_QUEUE_WORKER:-false}" = "true" ]; then
            echo "==> [Wajhatak] Starting explicitly enabled embedded queue worker..."
            php artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3500 &
        fi

        echo "==> [Wajhatak] AI assistant: deterministic in-process engine (no model download, no external provider)."

        export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
        echo "==> [Wajhatak] Starting Laravel server: php artisan serve on :${PORT:-8080}"
        exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" --no-reload
        ;;
esac
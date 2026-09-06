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
#   6. ONE-TIME provisioning (guarded by the Setting 'system_initialized'):
#      creates all tables, seeds roles/permissions/locations, then the REAL
#      Sana'a dataset (agents عبدالرحمن & مهند + real property listings with
#      photos downloaded from the internet) and the control-panel admin.
#      After that flag is set, EVERY later boot skips migrations and seeds so
#      redeploys or restarts can never modify the database again.
#   7. Caches config/routes/views.
#   8. For the "app" service: starts the queue worker (deferred notifications)
#      in the background and serves the app with `php artisan serve` on $PORT.
#
# The same image backs three Railway services selected via
# RAILWAY_SERVICE_TYPE (default: app):
#   - app       : queue worker (background) + `php artisan serve` on $PORT
#   - worker    : `php artisan queue:work` (foreground daemon)
#   - scheduler : `php artisan schedule:run` loop
# =============================================================================
set -e

SERVICE_TYPE="${RAILWAY_SERVICE_TYPE:-app}"
echo "==> [Wajhatak] Container starting (type: ${SERVICE_TYPE})"

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
# 2. MySQL must be configured - never silently use SQLite in production.
# ---------------------------------------------------------------------------
if [ -z "${DB_CONNECTION:-}" ]; then
    export DB_CONNECTION=mysql
fi
case "$DB_CONNECTION" in
    mysql|pgsql) ;;
    *)
        echo "!! [Wajhatak] DB_CONNECTION='$DB_CONNECTION' is not supported. Set it to mysql (Railway MySQL service)." >&2
        exit 1
        ;;
esac

# If DB_HOST holds a full mysql:// URL (e.g. someone pasted MYSQL_URL into it),
# split it into host/port/database/user/password so Laravel connects correctly.
case "$DB_HOST" in
    mysql://*|mariadb://*)
        echo "==> [Wajhatak] DB_HOST contains a full URL - splitting it into DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD."
        _u="$DB_HOST"
        _creds="${_u#*://}"
        _auth="${_creds%%@*}"
        _rest="${_creds#*@}"
        _hostport="${_rest%%/*}"
        _db="${_rest#*/}"
        _db="${_db%%\?*}"
        _user="${_auth%%:*}"
        _pass="${_auth#*:}"
        case "$_hostport" in
            *:*) _host="${_hostport%%:*}"; _port="${_hostport##*:}" ;;
            *) _host="$_hostport"; _port="${DB_PORT:-3306}" ;;
        esac
        export DB_HOST="$_host" DB_PORT="$_port" DB_DATABASE="$_db" DB_USERNAME="$_user" DB_PASSWORD="$_pass"
        unset _u _creds _auth _rest _hostport _db _user _pass _host _port
        ;;
esac

DB_HOST_VALUE="${DB_HOST:-$MYSQLHOST}"
case "$DB_HOST_VALUE" in
    ''|*\$\{\{*|*\$\{*)
        echo '!! [Wajhatak] MySQL connection is NOT configured (DB_HOST is empty or an unresolved ${{...}} reference).' >&2
        echo '   To fix on Railway:' >&2
        echo '     1. Add a MySQL service to this project (default name is "MySQL"), then wait for it to provision.' >&2
        echo '     2. Redeploy this service - railway.toml injects DB_* from ${{MySQL.MYSQLHOST}} etc.' >&2
        echo '   Or set DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD (or MYSQL*) manually.' >&2
        echo "   Current values: DB_CONNECTION=$DB_CONNECTION, DB_HOST='$DB_HOST', DB_PORT='${DB_PORT:-}'," >&2
        echo "                    DB_DATABASE='$DB_DATABASE', DB_USERNAME='$DB_USERNAME'." >&2
        exit 1
        ;;
esac
echo "==> [Wajhatak] Using MySQL at ${DB_HOST_VALUE}."

# Drop any stale cached config so the fresh environment variables win.
php artisan config:clear >/dev/null 2>&1 || true

# Print the exact target we are connecting to (password hidden).
echo "==> [Wajhatak] DB target: host=${DB_HOST_VALUE}, port=${DB_PORT:-3306}, database=${DB_DATABASE:-<unset>}, user=${DB_USERNAME:-<unset>}."

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

# ---------------------------------------------------------------------------
# Only "app" and "worker" services provision the database. Provisioning runs
# exactly ONCE: RealDataSeeder finishes by writing Setting 'system_initialized'
# = 1, and every later boot (redeploy or restart) skips migrations AND seeds so
# the existing data is never touched.
# ---------------------------------------------------------------------------
if [ "$SERVICE_TYPE" != "static" ]; then
    SEEDED_FLAG=$(php artisan tinker --execute="echo \App\Models\Setting::get('system_initialized','0') === '1' ? 'SEEDED' : 'PENDING';" 2>/dev/null || true)

    case "$SEEDED_FLAG" in
        *SEEDED*)
            echo "==> [Wajhatak] Database already provisioned (system_initialized=1)."
            echo "    Skipping ALL migrations and seeds to protect your existing data."
            ;;
        *)
            echo "==> [Wajhatak] First-time provisioning: creating schema, then seeding"
            echo "    roles/permissions/locations, the real Sana'a dataset and the admin account..."
            php artisan migrate --force
            php artisan db:seed --class=DatabaseSeeder --force
            php artisan db:seed --class=RealDataSeeder --force
            php artisan db:seed --class=AdminUserSeeder --force
            ;;
    esac

    # 7. Cache config/routes/views (recomputed from current env each boot)
    echo "==> [Wajhatak] Caching config, routes and views..."
    php artisan optimize:clear >/dev/null 2>&1 || true
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true

    # 7b. Optional property-image self-healing. Railway's disk is ephemeral, so
    #     seeder images can be wiped on redeploy while DB rows survive. When
    #     WJ_RUN_IMAGE_FIX=1 we re-check every property's images on boot and
    #     re-download/re-bind anything missing. Idempotent and non-fatal; for a
    #     persistent copy, enable a Railway volume on storage/app/public.
    case "${WJ_RUN_IMAGE_FIX:-0}" in
        1|true|yes)
            echo "==> [Wajhatak] Self-healing property images (WJ_RUN_IMAGE_FIX=1)..."
            php scripts/fix_property_images.php --quiet || echo "    image fixer exited non-zero (non-fatal)."
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
        # App service: queue worker (background, self-healing) + artisan serve.
        echo "==> [Wajhatak] Starting queue worker (background) for deferred notifications..."
        (
            while :; do
                php artisan queue:work --sleep=3 --tries=3 --timeout=60 --max-time=3500 || true
                sleep 2
            done
        ) &

        export PHP_CLI_SERVER_WORKERS="${PHP_CLI_SERVER_WORKERS:-4}"
        echo "==> [Wajhatak] Starting Laravel server: php artisan serve on :${PORT:-8080}"
        exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}" --no-reload
        ;;
esac
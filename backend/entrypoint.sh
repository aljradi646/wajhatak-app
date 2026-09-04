#!/bin/sh
# =============================================================================
# Wajhatak Laravel entrypoint
#
# Runs every time a container starts. It:
#   1. Validates that a real MySQL connection is configured (never silently
#      falls back to SQLite).
#   2. Waits for the database to accept connections.
#   3. Generates an APP_KEY on first boot when missing.
#   4. Prepares storage and creates the public storage symlink.
#   5. Runs `php artisan migrate --seed --force` (creates all tables + seeds
#      roles/permissions/locations), then seeds the demo (trial) data and the
#      control-panel admin account. Every seeder is idempotent, so this is
#      safe on every boot.
#   6. Caches config/routes/views.
#   7. Starts the process for the requested service type.
#
# The same image backs three Railway services - app, worker, scheduler -
# selected via the RAILWAY_SERVICE_TYPE environment variable (default: app).
# =============================================================================
set -e

SERVICE_TYPE="${RAILWAY_SERVICE_TYPE:-app}"
echo "==> [Wajhatak] Container starting (type: ${SERVICE_TYPE})"

# ---------------------------------------------------------------------------
# Boot the framework so we can run artisan reliably
# ---------------------------------------------------------------------------
cd /var/www/html

# ---------------------------------------------------------------------------
# 1. MySQL must be configured - never silently use SQLite in production.
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
        echo "   Or set DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD (or MYSQL*) manually." >&2
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
# 2. Wait for the database (with real diagnostics)
# ---------------------------------------------------------------------------
MAX_RETRIES=60
RETRY=0
FIRST_ERR=""
until OUT=$(php artisan migrate:status 2>&1); do
    RETRY=$((RETRY + 1))
    if [ -n "$OUT" ]; then
        FIRST_ERR="$OUT"
    fi
    if [ "$RETRY" -eq 1 ]; then
        echo "    migrate:status failed - first diagnostic output:"
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
# 3. Application key (generate on first boot if missing)
# ---------------------------------------------------------------------------
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "==> [Wajhatak] APP_KEY missing, generating one for this runtime..."
    GEN_KEY=$(php artisan key:generate --show --force 2>/dev/null || php -r 'echo "base64:".base64_encode(random_bytes(32));')
    export APP_KEY="$GEN_KEY"
    echo "    Generated APP_KEY. NOTE: set APP_KEY explicitly in Railway variables"
    echo "    so it stays stable across redeploys (cross-deploy persistence)."
fi

# ---------------------------------------------------------------------------
# 4. Storage dirs + public storage symlink
# ---------------------------------------------------------------------------
echo "==> [Wajhatak] Preparing storage..."
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
php artisan storage:link >/dev/null 2>&1 || echo "    storage:link unavailable (fallback route serves /storage)."

# ---------------------------------------------------------------------------
# Only services that own the database (app + worker) run migrations/seeds.
# ---------------------------------------------------------------------------
if [ "$SERVICE_TYPE" != "static" ]; then
    # 5a. Create all tables + run the base seeder (roles/permissions/locations).
    #     --force so production never prompts for confirmation.
    echo "==> [Wajhatak] Running 'php artisan migrate --seed --force'..."
    php artisan migrate --seed --force

    # 5b. Demo/trial dataset (properties, agents, clients, chat, viewing
    #     requests, property images). Only runs when LUX_ALLOW_DEMO_SEED=true.
    echo "==> [Wajhatak] Seeding demo data (LUX_ALLOW_DEMO_SEED=$LUX_ALLOW_DEMO_SEED)..."
    php artisan db:seed --class=DemoDataSeeder --force || echo "    Demo data skipped (set LUX_ALLOW_DEMO_SEED=true to enable)."

    # 5c. Control-panel admin account (ADMIN_EMAIL / ADMIN_NAME / ADMIN_PASSWORD)
    echo "==> [Wajhatak] Ensuring control-panel admin account..."
    php artisan db:seed --class=AdminUserSeeder --force

    # 6. Cache config/routes/views (recomputed from current env each boot)
    echo "==> [Wajhatak] Caching config, routes and views..."
    php artisan optimize:clear >/dev/null 2>&1 || true
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# ---------------------------------------------------------------------------
# 7. Start the requested process
# ---------------------------------------------------------------------------
case "$SERVICE_TYPE" in
    worker)
        echo "==> [Wajhatak] Starting queue worker..."
        exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
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
        echo "==> [Wajhatak] Starting PHP-FPM (background)..."
        php-fpm &
        # Give FPM a moment to bind on 9000 before Caddy starts proxying.
        sleep 2
        echo "==> [Wajhatak] Starting Caddy (listening on :\${PORT:-8080})..."
        exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
        ;;
esac
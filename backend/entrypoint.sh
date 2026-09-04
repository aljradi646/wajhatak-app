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

# ---------------------------------------------------------------------------
# 2. Wait for the database
# ---------------------------------------------------------------------------
MAX_RETRIES=60
RETRY=0
until php artisan migrate:status >/dev/null 2>&1; do
    RETRY=$((RETRY + 1))
    if [ "$RETRY" -ge "$MAX_RETRIES" ]; then
        echo "!! [Wajhatak] Database not reachable after $MAX_RETRIES attempts." >&2
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
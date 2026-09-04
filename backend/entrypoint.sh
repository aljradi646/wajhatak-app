#!/bin/sh
# =============================================================================
# Wajhatak Laravel entrypoint
#
# Runs every time a container starts. It:
#   1. Waits for the database to accept connections.
#   2. Generates an APP_KEY on first boot when missing.
#   3. Prepares storage and creates the public storage symlink.
#   4. Runs migrations, then idempotently seeds roles/permissions/locations
#      and the default admin account (safe on every boot - seeders are
#      idempotent via updateOrCreate / firstOrCreate).
#   5. Caches config/routes/views.
#   6. Starts the process for the requested service type.
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
# 1. Wait for the database
# ---------------------------------------------------------------------------
MAX_RETRIES=60
RETRY=0
until php artisan migrate:status >/dev/null 2>&1; do
    RETRY=$((RETRY + 1))
    if [ "$RETRY" -ge "$MAX_RETRIES" ]; then
        echo "!! Database not reachable after $MAX_RETRIES attempts." >&2
        exit 1
    fi
    echo "    Database not ready (attempt $RETRY/$MAX_RETRIES), retrying in 3s..."
    sleep 3
done
echo "==> [Wajhatak] Database is reachable."

# ---------------------------------------------------------------------------
# 2. Application key (generate on first boot if missing)
# ---------------------------------------------------------------------------
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo "==> [Wajhatak] APP_KEY missing, generating one for this runtime..."
    GEN_KEY=$(php artisan key:generate --show --force 2>/dev/null || php -r 'echo "base64:".base64_encode(random_bytes(32));')
    export APP_KEY="$GEN_KEY"
    echo "    Generated APP_KEY. NOTE: set APP_KEY explicitly in Railway variables"
    echo "    so it stays stable across redeploys (cross-deploy persistence)."
fi

# ---------------------------------------------------------------------------
# 3. Storage dirs + public storage symlink
# ---------------------------------------------------------------------------
echo "==> [Wajhatak] Preparing storage..."
mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
php artisan storage:link >/dev/null 2>&1 || echo "    storage:link unavailable (fallback route serves /storage)."

# ---------------------------------------------------------------------------
# Only services that own the database (app + worker) run migrations/seeds.
# ---------------------------------------------------------------------------
if [ "$SERVICE_TYPE" != "static" ]; then
    # 4. Migrations
    echo "==> [Wajhatak] Running migrations..."
    php artisan migrate --force

    # 5. Idempotent seeding (roles/permissions/locations + admin account)
    echo "==> [Wajhatak] Seeding base data..."
    php artisan db:seed --class=DatabaseSeeder --force || echo "    Base seeder skipped."

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

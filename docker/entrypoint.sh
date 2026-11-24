#!/bin/sh
set -e

echo "Starting SABAYRA Cambodia Production Container..."

# Bind custom PORT if set by Render or Cloud PaaS
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    echo "Configuring Nginx to listen on port $PORT..."
    sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/http.d/default.conf
    sed -i "s/listen \[::\]:80;/listen \[::\]:$PORT;/g" /etc/nginx/http.d/default.conf
fi

# Ensure storage and database directories exist with proper permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/database
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod 666 /var/www/html/database/database.sqlite

# Create storage symlink
php artisan storage:link || true

# Clear stale caches so environment changes apply immediately
php artisan config:clear || true

# Database Health & Migration check (Quick test with automatic SQLite failover)
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Testing database connectivity..."
    DB_CONNECTED=false
    if php -r "require 'vendor/autoload.php'; \$app = require_once 'bootstrap/app.php'; \$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); Illuminate\Support\Facades\DB::connection()->getPdo();" > /dev/null 2>&1; then
        DB_CONNECTED=true
    fi

    if [ "$DB_CONNECTED" = "true" ]; then
        echo "Primary database connected successfully. Running migrations..."
        php artisan migrate --force || true
    else
        echo "Primary database is unreachable. Switching to internal SQLite database for 100% uptime..."
        export DB_CONNECTION=sqlite
        export DB_DATABASE=/var/www/html/database/database.sqlite
        php artisan migrate --force || true
    fi

    echo "Seeding system roles, default plans, and Super Admin..."
    php artisan db:seed --class=RolesAndPermissionsSeeder --force || true
    php artisan db:seed --class=PlansSeeder --force || true
    php artisan db:seed --class=DemoBusinessSeeder --force || true
    php artisan app:create-super-admin || true

    # Clean up any stale/broken domains in business records
    php artisan tinker --execute="foreach (\App\Models\Business::all() as \$b) { \$t = \$b->theme_settings ?? []; if (!empty(\$t['public_url']) && str_contains(\$t['public_url'], 'kheatopup.store')) { unset(\$t['public_url']); \$b->theme_settings = \$t; \$b->saveQuietly(); } }" || true
fi

# If APP_KEY is provided, cache optimizations after migrations
if [ -n "$APP_KEY" ]; then
    echo "Optimizing Laravel routes and views..."
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Start services via supervisord immediately so Render health check passes
echo "Starting Nginx & PHP-FPM on port ${PORT:-80}..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

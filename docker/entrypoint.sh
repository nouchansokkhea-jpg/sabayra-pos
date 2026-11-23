#!/bin/sh
set -e

echo "Starting SABAYRA Cambodia Production Container..."

# Bind custom PORT if set by Render or Cloud PaaS
if [ -n "$PORT" ] && [ "$PORT" != "80" ]; then
    echo "Configuring Nginx to listen on port $PORT..."
    sed -i "s/listen 80;/listen $PORT;/g" /etc/nginx/http.d/default.conf
    sed -i "s/listen \[::\]:80;/listen \[::\]:$PORT;/g" /etc/nginx/http.d/default.conf
fi

# Ensure storage directories exist with proper permissions
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink
php artisan storage:link || true

# Clear stale caches
php artisan config:clear || true

# Run database migrations with wait/retry loop
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "Waiting for database connection..."
    max_retries=15
    count=0
    until php artisan db:monitor || [ $count -ge $max_retries ]; do
        echo "Database unavailable, waiting 2 seconds... ($count/$max_retries)"
        sleep 2
        count=$((count+1))
    done

    echo "Running database migrations..."
    php artisan migrate --force

    echo "Seeding roles, plans, and Super Admin..."
    php artisan db:seed --class=RolesAndPermissionsSeeder --force || true
    php artisan db:seed --class=PlansSeeder --force || true
    php artisan app:create-super-admin || true
fi

# If APP_KEY is provided, cache optimizations after migrations
if [ -n "$APP_KEY" ]; then
    echo "Caching Laravel configuration, routes, and views..."
    php artisan config:cache || true
    php artisan route:cache || true
    php artisan view:cache || true
fi

# Start services via supervisord
echo "Starting Nginx & PHP-FPM..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

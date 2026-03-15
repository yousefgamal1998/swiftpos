#!/bin/sh
set -e

echo "=============================="
echo "  SwiftPOS Container Starting"
echo "=============================="

# Ensure storage & cache directories exist and are writable
mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage symlink if not present
php artisan storage:link --force 2>/dev/null || true

# Cache configuration for production performance
echo "[boot] Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Run database migrations (--force required in production)
echo "[boot] Running migrations..."
php artisan migrate --force

echo "[boot] Starting supervisord..."
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf

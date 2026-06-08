#!/usr/bin/env bash
set -e

cd /var/www/html

# storage 構造と権限を保証（マイグレーションは実行しない＝既存DBを変更しない）
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R ug+rwX storage bootstrap/cache || true

# 実行時 env（compose の environment）で config/route/view をキャッシュ
php artisan optimize:clear || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

exec "$@"

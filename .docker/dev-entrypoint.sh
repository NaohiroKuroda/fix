#!/usr/bin/env bash
# 開発コンテナ起動スクリプト。
# - 依存（vendor / node_modules）が無ければ導入（名前付きボリュームに保持される）
# - .env / APP_KEY / storage を保証
# - php artisan serve（:8000）と Vite dev server（:5173, HMR）を同時起動
#
# 注意: マイグレーションは実行しない（felix_total の既存DBを変更しないため）。
set -e
cd /var/www/html

# .env を保証（無ければ example から）
if [ ! -f .env ]; then
    cp .env.example .env
fi

# PHP 依存
if [ ! -f vendor/autoload.php ]; then
    echo "[dev] composer install ..."
    composer install --no-interaction --prefer-dist
fi

# Node 依存
if [ ! -d node_modules ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
    echo "[dev] npm install ..."
    npm install
fi

# APP_KEY を保証
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force
fi

# storage 構造と権限
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache || true

# dev では config:cache しない（compose の environment 上書きを即時反映するため）
php artisan optimize:clear || true

# Laravel(:8000) を背後で、Vite(:5173) を前面で起動。
# 注: `php artisan serve` は ServeCommand が .env の値をワーカーに再注入し、
#     compose の environment 上書き（DB_HOST=host.docker.internal 等）を無視するため使わない。
#     PHP ビルトインサーバ + Laravel 標準ルータなら環境変数をそのまま継承する。
echo "[dev] starting php built-in server (:8000) + vite (:5173)"
# Laravel 標準ルータ(server.php)は getcwd()/index.php を読むため public 内で起動する。
( cd public && php -S 0.0.0.0:8000 \
    /var/www/html/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php ) &

exec npm run dev -- --host 0.0.0.0

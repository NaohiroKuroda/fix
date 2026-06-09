# =========================================================
# Stage 1: build （PHP + Node 同居。wayfinder の vite プラグインが
#           ビルド時に `php artisan` を実行するため両方必要）
# =========================================================
FROM php:8.4-cli AS build

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev libonig-dev curl ca-certificates gnupg \
    && docker-php-ext-install pdo_mysql zip intl \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# PHP 依存（先に入れてレイヤキャッシュを効かせる）
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

# Node 依存
COPY package.json package-lock.json ./
RUN npm ci

# アプリ本体
COPY . .

# オートロード生成 → フロントビルド（wayfinder 生成のため一時的な APP_KEY を渡す）
RUN composer dump-autoload --optimize \
    && APP_KEY="base64:$(head -c 32 /dev/urandom | base64)" npm run build \
    && rm -rf node_modules

# =========================================================
# Stage 2: 実行（Apache + PHP8.4）
# =========================================================
FROM php:8.4-apache AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install pdo_mysql zip intl \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Apache: DocumentRoot を public/ に
COPY .docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# ビルド成果物（vendor・public/build を含む）をコピー
COPY --from=build /app /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY .docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["apache2-foreground"]

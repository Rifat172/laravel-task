# ====================== STAGE 1: Composer ======================
FROM composer:latest AS composer

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-interaction \
    --optimize-autoloader \
    --no-dev \
    --prefer-dist \
    --no-scripts \
    --no-plugins

# ====================== STAGE 2: PHP ======================
FROM php:8.4-fpm

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Копируем vendor
COPY --from=composer /app/vendor ./vendor

# Копируем весь проект
COPY . .

# Права доступа (важно для разработки с volume)
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Кэширование Laravel (можно отключить при активной разработке)
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 9000

# Для разработки удобнее запускать от root (volume mount работает лучше)
USER root

CMD ["php-fpm"]
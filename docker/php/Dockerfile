# ==============================================================================
# Stage 1: Composer Dependencies
# ==============================================================================
FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --classmap-authoritative \
    --no-scripts

# ==============================================================================
# Stage 2: Frontend Assets (Vite + Vue 3)
# ==============================================================================
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY public ./public
RUN npm run build

# ==============================================================================
# Stage 3: PHP-FPM 8.3 Runtime
# ==============================================================================
FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache \
    bash \
    icu-dev \
    libzip-dev \
    mysql-client \
    oniguruma-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        intl \
        bcmath \
        pcntl \
        opcache \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html
COPY --from=vendor --chown=www-data:www-data /app/vendor /var/www/html/vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY docker/php/entrypoint.sh /usr/local/bin/royal-entrypoint.sh

RUN chmod +x /usr/local/bin/royal-entrypoint.sh \
    && mkdir -p storage/framework/cache/data \
                storage/framework/sessions \
                storage/framework/views \
                storage/logs \
                bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/royal-entrypoint.sh"]
CMD ["php-fpm"]

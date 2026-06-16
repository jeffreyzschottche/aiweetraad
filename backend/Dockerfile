FROM php:8.3-cli-alpine AS runtime

WORKDIR /var/www/html

RUN apk add --no-cache bash curl libpq sqlite-libs \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        curl-dev \
        oniguruma-dev \
        postgresql-dev \
        sqlite-dev \
    && docker-php-ext-install \
        curl \
        mbstring \
        pdo_mysql \
        pdo_pgsql \
        pdo_sqlite \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-scripts \
    && php artisan package:discover --ansi \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database

USER www-data

EXPOSE 8000

CMD ["sh", "-c", "php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]

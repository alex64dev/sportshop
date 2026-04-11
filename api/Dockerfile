FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    icu-dev \
    libpq-dev \
    linux-headers \
    zip \
    unzip \
    git \
    curl

RUN docker-php-ext-install \
    intl \
    pdo_pgsql \
    opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/api

COPY . .
RUN if [ -f composer.json ]; then composer install --no-dev --no-scripts --no-autoloader --prefer-dist 2>/dev/null || true; fi
RUN chown -R www-data:www-data /var/www/api/var 2>/dev/null || true

EXPOSE 9000
CMD ["php-fpm"]

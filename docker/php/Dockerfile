FROM php:8.4-fpm-alpine

RUN apk add --no-cache git unzip \
    && apk add --no-cache --virtual .phpize-deps $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pcntl \
    && pecl install redis-6.3.0 \
    && docker-php-ext-enable redis \
    && apk del .phpize-deps \
    && rm -rf /tmp/pear \
    && cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --chmod=755 entrypoint.sh /usr/local/bin/app-entrypoint

WORKDIR /var/www/html

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

# syntax=docker/dockerfile:labs
FROM php:8-fpm AS server
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer zip opcache

RUN mkdir /app
WORKDIR /app
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN chown -R www-data:www-data .

USER www-data
RUN composer install --no-dev -o -n
USER root

COPY src src
COPY includes/PHP includes/PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY images/opcache.ini /tmp/opcache.ini
COPY images/fpm.conf /usr/local/etc/php-fpm.d/www.conf
RUN cat /tmp/opcache.ini >> $PHP_INI_DIR/php.ini

FROM caddy AS caddy
COPY --from=server /app /app
COPY images/Caddyfile /etc/caddy/Caddyfile

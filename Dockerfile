FROM php:8.2.10-fpm-alpine3.18

WORKDIR /var/www/app

RUN apk update && apk add git curl-dev libmcrypt-dev mariadb-client libzip-dev zlib-dev icu-dev zip unzip imap-dev \
    krb5-dev oniguruma-dev libpq-dev aspell-dev libedit-dev libxml2-dev libbz2 libxslt-dev tidyhtml-dev openrc openssh \
    libjpeg-turbo-dev libpng-dev libwebp-dev freetype-dev nano mc
RUN rc-update add sshd && ssh-keygen -A # Start SSHD on boot & generate keys
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
RUN sed -i -E 's/memory_limit = (.*)/memory_limit = 512M/g' "$PHP_INI_DIR/php.ini"
RUN apk add --update linux-headers
RUN apk --no-cache add pcre-dev $PHPIZE_DEPS \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && apk del pcre-dev $PHPIZE_DEPS

RUN docker-php-ext-install mbstring

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN chown -R 1000:1000 /var/www/app

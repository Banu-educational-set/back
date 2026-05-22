FROM php:8.3-fpm-alpine AS base

ARG UID=1000
ARG GID=1000

# Use Arvan Alpine mirror (works from Iran). Harmless elsewhere.
RUN sed -i 's|https://dl-cdn.alpinelinux.org|https://mirror.arvancloud.ir|g' /etc/apk/repositories

RUN apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        icu-dev \
        libzip-dev \
        zlib-dev \
        oniguruma-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        autoconf \
        g++ \
        make \
        linux-headers \
        shadow \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_mysql \
        mysqli \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
        pcntl \
    && apk del autoconf g++ make

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN (getent group ${GID} || addgroup -g ${GID} app) \
    && (getent passwd ${UID} || adduser -D -u ${UID} -G $(getent group ${GID} | cut -d: -f1) app)

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]

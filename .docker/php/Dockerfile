FROM php:8.3-cli-alpine

RUN apk add --no-cache \
    git \
    unzip \
    sqlite-dev \
    libxml2-dev \
    && docker-php-ext-install \
    pdo_sqlite \
    dom \
    xml \
    xmlwriter

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
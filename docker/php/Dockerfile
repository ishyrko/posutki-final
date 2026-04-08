FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    mysql-client \
    bash

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo_mysql \
    mysqli \
    zip \
    intl \
    opcache \
    gd \
    mbstring

# Install Redis extension
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/backend

# Copy PHP configuration
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Leave default user as root (official php-fpm image): master runs as root, pool workers run as
# www-data per php-fpm pool config. Forcing USER www-data breaks `composer install` into a bind-
# mounted ./backend when vendor/ is owned by the host user (www-data cannot create subdirs).
RUN chown -R www-data:www-data /var/www/backend

EXPOSE 9000

CMD ["php-fpm"]

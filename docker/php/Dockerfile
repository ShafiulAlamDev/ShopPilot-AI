# =============================================================================
# PHP 8.2 FPM Dockerfile — ShopPilot AI
# =============================================================================
FROM php:8.2-fpm-alpine

# ---------------------------------------------------------------------------
# System dependencies
# ---------------------------------------------------------------------------
RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    icu-dev \
    libxml2-dev \
    linux-headers \
    $PHPIZE_DEPS

# ---------------------------------------------------------------------------
# PHP extensions
# ---------------------------------------------------------------------------
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
        opcache

# Redis extension via PECL
RUN pecl install redis \
 && docker-php-ext-enable redis

# Xdebug (dev only — disabled by default via php.ini)
RUN pecl install xdebug \
 && docker-php-ext-enable xdebug

# ---------------------------------------------------------------------------
# Composer
# ---------------------------------------------------------------------------
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Non-root user for security
# ---------------------------------------------------------------------------
RUN addgroup -g 1000 appgroup \
 && adduser -u 1000 -G appgroup -s /bin/bash -D appuser

# ---------------------------------------------------------------------------
# Working directory
# ---------------------------------------------------------------------------
WORKDIR /var/www/api

# Give storage and bootstrap/cache write permissions
RUN mkdir -p storage/logs storage/framework/{sessions,views,cache} bootstrap/cache \
 && chown -R appuser:appgroup /var/www/api \
 && chmod -R 775 storage bootstrap/cache

USER appuser

EXPOSE 9000

CMD ["php-fpm"]

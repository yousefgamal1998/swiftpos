# ============================================================
# Stage 1: Install PHP dependencies (Composer)
# ============================================================
FROM php:8.3-cli-alpine AS composer-install

# Install Composer from the official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /build

# Install dependencies first (layer-cached unless lock file changes)
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --no-progress

# Copy the rest of the app and run post-install scripts
COPY . .
RUN composer dump-autoload --optimize


# ============================================================
# Stage 2: Build frontend assets (Vite + Vue 3 + TypeScript)
# ============================================================
FROM node:22-alpine AS node-build

WORKDIR /build

# Install npm dependencies first (layer-cached unless package files change)
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

# Copy only what Vite needs
COPY vite.config.js tsconfig.json tailwind.config.js postcss.config.js ./
COPY resources/ resources/

# Copy Ziggy from the Composer stage (app.ts imports from vendor/tightenco/ziggy)
COPY --from=composer-install /build/vendor/tightenco/ziggy vendor/tightenco/ziggy

# Run vite build directly (skip vue-tsc type-checking — that belongs in CI)
RUN npx vite build


# ============================================================
# Stage 3: Production image (PHP-FPM + Nginx + Supervisor)
# ============================================================
FROM php:8.3-fpm-alpine AS production

# Meta
LABEL maintainer="SwiftPOS Team"
LABEL description="SwiftPOS – Laravel 12 production image"

# Install system packages & PHP extensions
RUN apk add --no-cache \
        nginx \
        supervisor \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        oniguruma-dev \
        icu-dev \
        linux-headers \
        # Build tools needed by pecl install
        autoconf \
        gcc \
        g++ \
        make \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        bcmath \
        pcntl \
        opcache \
        gd \
        zip \
        intl \
        mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Clean up build dependencies to keep image small
    && apk del --no-cache linux-headers autoconf gcc g++ make \
    && rm -rf /tmp/pear

# PHP production tuning
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=16"; \
    echo "opcache.max_accelerated_files=20000"; \
    echo "opcache.validate_timestamps=0"; \
    echo "opcache.jit_buffer_size=64M"; \
    echo "opcache.jit=1255"; \
    echo "expose_php=Off"; \
    echo "upload_max_filesize=64M"; \
    echo "post_max_size=64M"; \
    echo "memory_limit=256M"; \
    echo "max_execution_time=300"; \
} > /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www/html

# Copy application code from composer stage (includes everything except dev deps)
COPY --from=composer-install /build /var/www/html

# Copy built frontend assets from node stage
COPY --from=node-build /build/public/build /var/www/html/public/build

# Copy Docker config files
COPY docker/nginx.conf      /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh   /usr/local/bin/entrypoint.sh

# Ensure entrypoint is executable
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create required directories and set permissions
RUN mkdir -p \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
        /var/log/supervisor \
        /run/nginx \
    && chown -R www-data:www-data \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache \
    && chmod -R 775 \
        /var/www/html/storage \
        /var/www/html/bootstrap/cache

# Remove dev-only files from the final image
RUN rm -rf \
    /var/www/html/node_modules \
    /var/www/html/tests \
    /var/www/html/.git \
    /var/www/html/.github \
    /var/www/html/*.patch

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/health || exit 1

ENTRYPOINT ["entrypoint.sh"]

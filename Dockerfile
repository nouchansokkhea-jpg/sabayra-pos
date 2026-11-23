# ==============================================================================
# Multi-stage Dockerfile for Laravel + Vite + Nginx on Render / Cloud
# ==============================================================================

# Stage 1: Build Frontend Assets
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2: Production PHP-FPM + Nginx (PHP 8.4 matching Symfony 8 / Laravel requirements)
FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    sqlite-dev

# Configure & Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_pgsql pdo_sqlite mbstring zip bcmath opcache pcntl

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application source
COPY . .

# Copy compiled assets from frontend stage
COPY --from=frontend /app/public/build ./public/build

# Install production PHP dependencies (ignoring platform minor variations)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php+

# Set up file permissions for storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Copy Nginx, Supervisord, and Entrypoint configurations
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Render listens on port 80 by default (entrypoint handles custom $PORT)
EXPOSE 80

CMD ["/usr/local/bin/entrypoint.sh"]

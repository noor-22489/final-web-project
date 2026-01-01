# Stage 1 - Build Frontend (Vite)
FROM node:18 AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install
COPY . .
RUN npm run build

# Stage 2 - Backend (Laravel + PHP + Composer)
FROM php:8.4-fpm AS backend

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl unzip libpq-dev libonig-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_mysql mbstring zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy app files
COPY . .

# Copy built frontend assets from frontend stage (Vite -> public)
COPY --from=frontend /app/public ./public

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure storage and cache directories exist and are writable (fixes "Please provide a valid cache path")
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache || true && \
    chmod -R 775 storage bootstrap/cache || true

# Laravel setup
RUN php artisan config:clear && \
    php artisan route:clear && \
    mkdir -p resources/views && \
    php artisan view:clear || echo "php artisan view:clear failed; continuing"

# Expose port and run PHP built-in server so the container listens on HTTP
EXPOSE 8080
ENV PORT=8080
CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT:-8080} -t public"]

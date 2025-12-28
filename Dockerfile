##### Build vendor (composer) stage #####
FROM composer:2 as vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --optimize-autoloader

##### Build frontend (node) stage (optional) #####
FROM node:18 as node_builder
WORKDIR /app
COPY package*.json ./
RUN if [ -f package-lock.json ]; then \
            npm ci --no-audit --no-fund --no-progress; \
        else \
            npm install --no-audit --no-fund --no-progress; \
        fi
COPY . .
RUN if [ -f package.json ] && grep -q "build" package.json; then npm run build; fi || true

##### Production image #####
FROM php:8.2-fpm

ENV APP_ENV=production \
    APP_DEBUG=false \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y \
    git zip unzip libzip-dev libpng-dev libonig-dev libxml2-dev curl \
    libfreetype6-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# copy composer binary from official composer image (fallback)
COPY --from=vendor /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# copy application files and installed vendor from vendor stage
COPY --from=vendor /app/vendor ./vendor
COPY --from=vendor /app/composer.* ./
COPY . .

# copy built frontend assets if available
COPY --from=node_builder /app/public ./public

# Runtime permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Add entrypoint to perform runtime housekeeping (migrations/optimize are optional)
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm"]

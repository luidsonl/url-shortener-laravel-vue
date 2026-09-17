# ---- Frontend build stage (Vue/Vite) ----
FROM node:22-alpine AS frontend

WORKDIR /app

# Install dependencies first for better caching
COPY package.json package-lock.json ./
RUN npm ci

# Copy the source needed by Vite and build production assets
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

# ---- Application stage (Nginx + PHP-FPM) ----
FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    nginx \
    supervisor \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    netcat-traditional

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install dependencies (production-ready)
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist --no-dev --optimize-autoloader

# Copy the rest of the application
COPY . .

# Copy the Vite-built assets from the frontend stage
COPY --from=frontend /app/public/build /var/www/public/build

# Configure nginx (serves the app and proxies PHP to php-fpm)
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
RUN rm -f /etc/nginx/sites-enabled/default

# Configure Supervisor to run nginx + php-fpm in a single container
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Generate fresh package manifest without dev dependencies
RUN php artisan package:discover --ansi

# Create entrypoint script
RUN cat > /usr/local/bin/entrypoint.sh <<'EOF'
#!/bin/bash
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Ensure writable paths for the runtime
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Wait for database to be ready (if DB_HOST is set)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database ($DB_HOST)..."
    until nc -z "$DB_HOST" "${DB_PORT:-5432}"; do
        echo "Postgres is unavailable - sleeping"
        sleep 1
    done
    echo "Postgres is up"
fi

# Wait for redis to be ready (if REDIS_HOST is set)
if [ -n "$REDIS_HOST" ]; then
    echo "Waiting for redis ($REDIS_HOST)..."
    until nc -z "$REDIS_HOST" "${REDIS_PORT:-6379}"; do
        echo "Redis is unavailable - sleeping"
        sleep 1
    done
    echo "Redis is up"
fi

# Generate APP_KEY only when missing, so tokens stay valid across restarts
if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --no-interaction --force
fi

# Run migrations only on the primary app (disable with RUN_MIGRATIONS=false)
if [ "$RUN_MIGRATIONS" != "false" ]; then
    php artisan migrate --force
fi

# Cache the config in production for performance
if [ "$APP_ENV" = "production" ]; then
    php artisan config:cache || true
fi

# Execute CMD
exec "$@"
EOF
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
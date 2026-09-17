FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
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

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Generate fresh package manifest without dev dependencies
RUN php artisan package:discover --ansi

# Create entrypoint script
RUN echo '#!/bin/bash\n\
    set -e\n\
    if [ ! -f .env ]; then\n\
    cp .env.example .env\n\
    fi\n\
    \n\
    # Wait for database to be ready (if DB_HOST is set)\n\
    if [ -n "$DB_HOST" ]; then\n\
    echo "Waiting for database ($DB_HOST)..."\n\
    until nc -z "$DB_HOST" 5432; do\n\
    echo "Postgres is unavailable - sleeping"\n\
    sleep 1\n\
    done\n\
    echo "Postgres is up - executing commands"\n\
    fi\n\
    \n\
    php artisan key:generate --no-interaction --force\n\
    php artisan migrate --force\n\
    \n\
    # Execute CMD\n\
    exec "$@"' > /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

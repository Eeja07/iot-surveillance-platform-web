FROM php:8.2-fpm

# Timezone
ENV TZ=Asia/Jakarta

# Install dependency sistem
RUN apt-get update && apt-get install -y \
    git curl unzip libpng-dev libonig-dev libxml2-dev \
    libzip-dev zip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Opsional: supaya permission lebih aman di dev
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data
USER www-data

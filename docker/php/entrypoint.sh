#!/usr/bin/env bash
set -e

# Pindah ke direktori kerja
cd /var/www/html

# Hanya lakukan hal-hal penting untuk startup
if [ ! -f ".env" ]; then
    echo "Creating .env file..."
    cp .env.example .env

    echo "Ensuring APP_KEY is set..."
    grep -q "APP_KEY=" .env || echo "APP_KEY=" >> .env
    php artisan key:generate
fi

# Perintah 'storage:link' dihapus karena kita menggunakan volume bersama

# Perbaiki izin folder
echo "Fixing ownership and permissions for storage and cache..."
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Jalankan perintah utama dari Dockerfile (yaitu "php-fpm")
echo "Handing over to main container command (php-fpm)..."
exec docker-php-entrypoint "$@"

#!/bin/sh
set -e

echo "Running container startup script..."

# 1. Create Laravel runtime directories if missing
echo "Ensuring Laravel runtime directories exist..."
mkdir -p bootstrap/cache
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs

# 2. Ensure correct permissions
chmod -R 775 bootstrap/cache storage 2>/dev/null || true

# 3. Verify vendor/autoload.php exists, run composer install if missing
if [ ! -f vendor/autoload.php ]; then
    echo "vendor/autoload.php not found. Running composer install..."
    composer install --no-interaction --prefer-dist
else
    echo "Composer dependencies are already installed."
fi

# 4. Finally execute the original container command passed as arguments
echo "Executing container command: $@"
exec "$@"
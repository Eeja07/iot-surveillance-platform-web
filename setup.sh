#!/bin/bash

# Ensure required runtime directories exist and have proper permissions
echo "Ensuring Laravel runtime directories exist..."
mkdir -p bootstrap/cache
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p storage/logs
chmod -R 775 bootstrap/cache storage 2>/dev/null || true

# Copy .env.example to .env if not already present
if [ ! -f .env ]; then
    echo "Copying .env.example to .env..."
    cp .env.example .env
else
    echo ".env file already exists."
fi

# Install Composer dependencies
if [ -f composer.json ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

# Install Node dependencies
if [ -f package.json ]; then
    echo "Installing Node dependencies..."
    npm install
fi

# Build Vite assets
if [ -f package.json ]; then
    echo "Building Vite assets..."
    npm run build
fi

# Generate Laravel application key if not set
if [ -f .env ]; then
    if ! grep -q "APP_KEY=base64:" .env || [ -z "$(grep "APP_KEY=" .env | cut -d= -f2)" ]; then
        echo "Generating Laravel application key..."
        php artisan key:generate
    else
        echo "Laravel application key already exists."
    fi
fi

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ] && [ ! -d public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link
else
    echo "Storage symlink/directory already exists."
fi

# Clear Laravel caches
echo "Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run database migrations if database is accessible
echo "Running database migrations..."
php artisan migrate --force || echo "Database migration skipped (database may not be online yet)."

echo "Setup completed successfully!"

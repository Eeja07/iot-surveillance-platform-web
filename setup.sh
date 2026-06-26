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
        docker compose run --rm app php artisan key:generate
    else
        echo "Laravel application key already exists."
    fi
fi

# Create storage symlink if it doesn't exist
if [ ! -L public/storage ] && [ ! -d public/storage ]; then
    echo "Creating storage symlink..."
    docker compose run --rm app php artisan storage:link
else
    echo "Storage symlink/directory already exists."
fi

echo "Setup completed successfully!"

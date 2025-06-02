#!/bin/bash
set -e # Exit immediately if a command exits with a non-zero status.

echo "--- Running custom build script for Laravel ---"

# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# If you have frontend assets (e.g., with Vite/Mix), build them
if [ -f "package.json" ]; then
    echo "Installing Node.js dependencies..."
    npm install --production # or yarn install --production
    echo "Building frontend assets..."
    npm run build # or yarn build
fi

# Optimize Laravel for production
php artisan optimize:clear
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache

echo "--- Custom build script finished ---"

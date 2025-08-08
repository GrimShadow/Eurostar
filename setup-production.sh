#!/bin/bash

# Production Setup Script for Eurostar
echo "Setting up Eurostar production environment..."

# Set proper permissions for Laravel
echo "Setting Laravel permissions..."
sudo chown -R www-data:www-data /var/www/Eurostar
sudo chmod -R 755 /var/www/Eurostar
sudo chmod -R 775 /var/www/Eurostar/storage
sudo chmod -R 775 /var/www/Eurostar/bootstrap/cache

# Create log file with proper permissions
echo "Creating log file..."
sudo touch /var/www/Eurostar/storage/logs/laravel.log
sudo chown www-data:www-data /var/www/Eurostar/storage/logs/laravel.log
sudo chmod 664 /var/www/Eurostar/storage/logs/laravel.log

# Install dependencies
echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader

# Clear caches
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Generate application key if not exists
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
    php artisan key:generate
fi

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Optimize for production
echo "Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Production setup complete!" 
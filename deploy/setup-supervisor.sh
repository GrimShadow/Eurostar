#!/bin/bash

# Ubuntu Supervisor Setup Script for Laravel Eurostar
# Alternative to systemd services

set -e

echo "🚀 Setting up Laravel Eurostar with Supervisor..."

# Configuration
APP_PATH="/var/www/Eurostar"
APP_USER="www-data"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run this script with sudo"
    exit 1
fi

# Install Supervisor if not already installed
if ! command -v supervisorctl &> /dev/null; then
    echo "📦 Installing Supervisor..."
    apt-get update
    apt-get install -y supervisor
fi

# Check if application directory exists
if [ ! -d "$APP_PATH" ]; then
    echo "❌ Application directory $APP_PATH does not exist"
    echo "Please adjust APP_PATH in this script or deploy your application first"
    exit 1
fi

echo "📂 Application path: $APP_PATH"

# Copy supervisor configuration files
echo "📋 Installing Supervisor configuration files..."
cp deploy/supervisor/laravel-scheduler.conf /etc/supervisor/conf.d/
cp deploy/supervisor/laravel-queue.conf /etc/supervisor/conf.d/

# Update configuration files with correct paths
sed -i "s|/var/www/Eurostar|$APP_PATH|g" /etc/supervisor/conf.d/laravel-scheduler.conf
sed -i "s|/var/www/Eurostar|$APP_PATH|g" /etc/supervisor/conf.d/laravel-queue.conf

# Create log directory
mkdir -p /var/log/supervisor

# Reload supervisor configuration
echo "🔄 Reloading Supervisor configuration..."
supervisorctl reread
supervisorctl update

# Start the programs
echo "🎯 Starting Laravel processes..."
supervisorctl start laravel-scheduler
supervisorctl start laravel-queue:*

# Show status
echo "📊 Checking status..."
supervisorctl status

echo ""
echo "🎉 Setup complete!"
echo ""
echo "📋 Supervisor Management Commands:"
echo "  sudo supervisorctl start laravel-scheduler    # Start scheduler"
echo "  sudo supervisorctl stop laravel-scheduler     # Stop scheduler"
echo "  sudo supervisorctl restart laravel-scheduler  # Restart scheduler"
echo ""
echo "  sudo supervisorctl start laravel-queue:*      # Start all queue workers"
echo "  sudo supervisorctl stop laravel-queue:*       # Stop all queue workers"
echo "  sudo supervisorctl restart laravel-queue:*    # Restart all queue workers"
echo ""
echo "📊 Monitoring Commands:"
echo "  sudo supervisorctl status                      # Show all process status"
echo "  sudo supervisorctl tail -f laravel-scheduler  # Follow scheduler logs"
echo "  sudo supervisorctl tail -f laravel-queue      # Follow queue logs"
echo ""
echo "🔧 Configuration files:"
echo "  /etc/supervisor/conf.d/laravel-scheduler.conf"
echo "  /etc/supervisor/conf.d/laravel-queue.conf" 
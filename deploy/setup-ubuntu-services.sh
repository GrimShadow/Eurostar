#!/bin/bash

# Ubuntu Production Setup Script for Laravel Eurostar
# Run this script with sudo on your Ubuntu server

set -e

echo "🚀 Setting up Laravel Eurostar services for Ubuntu production..."

# Configuration
APP_PATH="/var/www/Eurostar"
APP_USER="www-data"
APP_GROUP="www-data"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Please run this script with sudo"
    exit 1
fi

# Check if application directory exists
if [ ! -d "$APP_PATH" ]; then
    echo "❌ Application directory $APP_PATH does not exist"
    echo "Please adjust APP_PATH in this script or deploy your application first"
    exit 1
fi

echo "📂 Application path: $APP_PATH"
echo "👤 Running as user: $APP_USER"

# Copy systemd service files
echo "📋 Installing systemd service files..."
cp deploy/systemd/laravel-scheduler.service /etc/systemd/system/
cp deploy/systemd/laravel-queue.service /etc/systemd/system/

# Update service files with correct paths
sed -i "s|/var/www/Eurostar|$APP_PATH|g" /etc/systemd/system/laravel-scheduler.service
sed -i "s|/var/www/Eurostar|$APP_PATH|g" /etc/systemd/system/laravel-queue.service

# Set correct permissions
chown root:root /etc/systemd/system/laravel-scheduler.service
chown root:root /etc/systemd/system/laravel-queue.service
chmod 644 /etc/systemd/system/laravel-scheduler.service
chmod 644 /etc/systemd/system/laravel-queue.service

# Reload systemd
echo "🔄 Reloading systemd daemon..."
systemctl daemon-reload

# Enable services (start on boot)
echo "✅ Enabling services to start on boot..."
systemctl enable laravel-scheduler.service
systemctl enable laravel-queue.service

# Start services
echo "🎯 Starting services..."
systemctl start laravel-scheduler.service
systemctl start laravel-queue.service

# Check service status
echo "📊 Checking service status..."
echo ""
echo "=== Laravel Scheduler Status ==="
systemctl status laravel-scheduler.service --no-pager
echo ""
echo "=== Laravel Queue Worker Status ==="
systemctl status laravel-queue.service --no-pager

echo ""
echo "🎉 Setup complete!"
echo ""
echo "📋 Service Management Commands:"
echo "  sudo systemctl start laravel-scheduler.service    # Start scheduler"
echo "  sudo systemctl stop laravel-scheduler.service     # Stop scheduler"
echo "  sudo systemctl restart laravel-scheduler.service  # Restart scheduler"
echo ""
echo "  sudo systemctl start laravel-queue.service        # Start queue worker"
echo "  sudo systemctl stop laravel-queue.service         # Stop queue worker"
echo "  sudo systemctl restart laravel-queue.service      # Restart queue worker"
echo ""
echo "📊 Monitoring Commands:"
echo "  sudo systemctl status laravel-scheduler.service   # Check scheduler status"
echo "  sudo systemctl status laravel-queue.service       # Check queue status"
echo "  sudo journalctl -u laravel-scheduler.service -f   # Follow scheduler logs"
echo "  sudo journalctl -u laravel-queue.service -f       # Follow queue logs"
echo ""
echo "🔧 Configuration files:"
echo "  /etc/systemd/system/laravel-scheduler.service"
echo "  /etc/systemd/system/laravel-queue.service" 
# Deployment Files

This directory contains configuration files and scripts for deploying the Laravel Eurostar application on Ubuntu production servers.

## Quick Start

For Ubuntu production deployment:

1. **Deploy your application** to `/var/www/eurostar`
2. **Choose your process manager:**
   - **Systemd (recommended):** `sudo ./setup-ubuntu-services.sh`
   - **Supervisor:** `sudo ./setup-supervisor.sh`

## Files

### Systemd Services (Recommended)
- `systemd/laravel-scheduler.service` - Service file for Laravel scheduler
- `systemd/laravel-queue.service` - Service file for queue worker
- `setup-ubuntu-services.sh` - Automated setup script for systemd

### Supervisor (Alternative)
- `supervisor/laravel-scheduler.conf` - Supervisor config for scheduler
- `supervisor/laravel-queue.conf` - Supervisor config for queue worker  
- `setup-supervisor.sh` - Automated setup script for supervisor

### Documentation
- `UBUNTU-DEPLOYMENT.md` - Complete deployment guide

## What Gets Installed

Both approaches set up:
- **Laravel Scheduler:** Runs continuously to execute scheduled commands:
  - Train rule processing (every minute)
  - Automated announcements (every minute)
  - GTFS data downloads (daily at 3:00 AM)
- **Queue Worker:** Processes background jobs including:
  - Train rule processing jobs
  - GTFS data download and import jobs
  - Automated announcement jobs
- **Automatic startup:** Services start when server boots
- **Auto-restart:** Services restart if they crash
- **Logging:** All output logged to system logs

## Requirements

- Ubuntu 18.04+
- PHP 8.1+
- Laravel application deployed
- Database configured
- Root/sudo access for setup

## How to Check Which Process Manager You're Using

If you've already deployed and can't remember which process manager you chose, run these commands on your production server:

### Check for Systemd Services
```bash
# Check if systemd services exist and are running
sudo systemctl status laravel-scheduler.service
sudo systemctl status laravel-queue.service

# List all Laravel-related systemd services
sudo systemctl list-units --type=service | grep laravel
```

### Check for Supervisor
```bash
# Check if supervisor is installed and running Laravel processes
sudo supervisorctl status

# Look specifically for Laravel processes
sudo supervisorctl status | grep laravel
```

### Quick Detection Script
```bash
# Run this one-liner to detect your setup
if sudo systemctl is-active laravel-scheduler.service >/dev/null 2>&1; then
    echo "✅ Using SYSTEMD"
    sudo systemctl status laravel-scheduler.service laravel-queue.service
elif sudo supervisorctl status | grep -q laravel 2>/dev/null; then
    echo "✅ Using SUPERVISOR" 
    sudo supervisorctl status | grep laravel
else
    echo "❌ No Laravel services found - not deployed yet"
fi
```

See `UBUNTU-DEPLOYMENT.md` for complete instructions. 
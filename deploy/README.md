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

See `UBUNTU-DEPLOYMENT.md` for complete instructions. 
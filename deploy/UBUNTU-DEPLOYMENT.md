# Ubuntu Production Deployment Guide

This guide explains how to set up the Laravel Eurostar application's background processes on Ubuntu production servers. You have two options: **Systemd Services** (recommended) or **Supervisor**.

## Prerequisites

- Ubuntu 18.04+ server
- PHP 8.1+ installed
- Laravel application deployed to `/var/www/eurostar` (or adjust paths accordingly)
- MySQL/MariaDB configured
- Composer dependencies installed
- Application `.env` file configured

## Option 1: Systemd Services (Recommended)

Systemd is the modern init system for Ubuntu and provides excellent process management with automatic restarts and logging.

### Quick Setup

1. **Deploy your application** to `/var/www/eurostar`

2. **Set permissions:**
   ```bash
   sudo chown -R www-data:www-data /var/www/eurostar
   sudo chmod -R 755 /var/www/eurostar
   ```

3. **Run the setup script:**
   ```bash
   cd /var/www/eurostar
   sudo chmod +x deploy/setup-ubuntu-services.sh
   sudo ./deploy/setup-ubuntu-services.sh
   ```

### Manual Setup

If you prefer to set up manually:

1. **Copy service files:**
   ```bash
   sudo cp deploy/systemd/laravel-scheduler.service /etc/systemd/system/
   sudo cp deploy/systemd/laravel-queue.service /etc/systemd/system/
   ```

2. **Update paths in service files if needed:**
   ```bash
   sudo nano /etc/systemd/system/laravel-scheduler.service
   sudo nano /etc/systemd/system/laravel-queue.service
   ```

3. **Enable and start services:**
   ```bash
   sudo systemctl daemon-reload
   sudo systemctl enable laravel-scheduler.service
   sudo systemctl enable laravel-queue.service
   sudo systemctl start laravel-scheduler.service
   sudo systemctl start laravel-queue.service
   ```

### Service Management

**Check status:**
```bash
sudo systemctl status laravel-scheduler.service
sudo systemctl status laravel-queue.service
```

**Start/Stop/Restart:**
```bash
sudo systemctl start laravel-scheduler.service
sudo systemctl stop laravel-scheduler.service
sudo systemctl restart laravel-scheduler.service

sudo systemctl start laravel-queue.service
sudo systemctl stop laravel-queue.service
sudo systemctl restart laravel-queue.service
```

**View logs:**
```bash
sudo journalctl -u laravel-scheduler.service -f
sudo journalctl -u laravel-queue.service -f
```

## Option 2: Supervisor

Supervisor is a popular process control system for UNIX-like operating systems.

### Quick Setup

1. **Deploy your application** to `/var/www/eurostar`

2. **Run the setup script:**
   ```bash
   cd /var/www/eurostar
   sudo chmod +x deploy/setup-supervisor.sh
   sudo ./deploy/setup-supervisor.sh
   ```

### Manual Setup

1. **Install Supervisor:**
   ```bash
   sudo apt-get update
   sudo apt-get install supervisor
   ```

2. **Copy configuration files:**
   ```bash
   sudo cp deploy/supervisor/laravel-scheduler.conf /etc/supervisor/conf.d/
   sudo cp deploy/supervisor/laravel-queue.conf /etc/supervisor/conf.d/
   ```

3. **Update and start:**
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-scheduler
   sudo supervisorctl start laravel-queue:*
   ```

### Supervisor Management

**Check status:**
```bash
sudo supervisorctl status
```

**Start/Stop/Restart:**
```bash
sudo supervisorctl start laravel-scheduler
sudo supervisorctl stop laravel-scheduler
sudo supervisorctl restart laravel-scheduler

sudo supervisorctl start laravel-queue:*
sudo supervisorctl stop laravel-queue:*
sudo supervisorctl restart laravel-queue:*
```

**View logs:**
```bash
sudo supervisorctl tail -f laravel-scheduler
sudo supervisorctl tail -f laravel-queue
```

## What These Services Do

### Laravel Scheduler (`laravel-scheduler.service`)
- Runs `php artisan schedule:work`
- Executes scheduled commands including:
  - `ProcessTrainRules` command every minute
  - `ProcessAutomatedAnnouncements` command every minute  
  - `DownloadGtfsData` command daily at 3:00 AM
- Enqueues jobs for background processing

### Laravel Queue Worker (`laravel-queue.service`)
- Runs `php artisan queue:work --queue=default,train-rules`
- Processes queued jobs including:
  - Train rule processing jobs (train-rules queue)
  - GTFS data download jobs (default queue)
  - Automated announcement jobs (default queue)
- Handles individual job processing in isolation

## Configuration Customization

### Adjust Application Path

If your application is not in `/var/www/eurostar`, update the paths in:
- Service files: `WorkingDirectory` and `ExecStart`
- Supervisor configs: `directory` and `command`

### Queue Worker Settings

The queue worker includes these settings:
- `--queue=default,train-rules`: Process jobs from both default queue (GTFS downloads, announcements) and train-rules queue
- `--sleep=3`: Sleep 3 seconds between job checks
- `--tries=3`: Retry failed jobs up to 3 times
- `--max-time=3600`: Restart worker every hour
- `--memory=512`: Restart if memory exceeds 512MB

Adjust these in the service/config files as needed.

### Multiple Queue Workers

For high load, you can run multiple queue workers:

**Systemd:** Create additional service files (`laravel-queue-2.service`, etc.)

**Supervisor:** Increase `numprocs=2` to `numprocs=4` in the config file

## Monitoring and Troubleshooting

### Check if services are running:
```bash
# Systemd
sudo systemctl is-active laravel-scheduler.service
sudo systemctl is-active laravel-queue.service

# Supervisor
sudo supervisorctl status
```

### Check queue status:
```bash
cd /var/www/eurostar
php artisan trains:queue status
```

### View application logs:
```bash
tail -f /var/www/eurostar/storage/logs/laravel.log
```

### Common Issues

1. **Permission errors:** Ensure `www-data` user can access the application directory
2. **Database connection:** Check `.env` database settings
3. **PHP path:** Update `/usr/bin/php` if PHP is installed elsewhere
4. **Memory limits:** Increase PHP memory_limit in php.ini

## Automatic Startup

Both systemd and supervisor are configured to:
- Start automatically when the server boots
- Restart automatically if processes crash
- Continue running even when you log out

## Security Considerations

- Services run as `www-data` user (limited privileges)
- No external network access required for queue processing
- Logs are stored in system directories with proper permissions
- Service files are owned by root and not writable by application user

## Performance Tuning

For high-volume processing:
1. Increase queue worker count (`numprocs` or multiple services)
2. Optimize database queries in train rule processing
3. Consider using Redis for queue backend
4. Monitor memory usage and adjust limits
5. Use Laravel Horizon for advanced queue management

---

Choose the approach that best fits your infrastructure. Systemd is more modern and integrated with Ubuntu, while Supervisor offers more granular process control and is widely used in the Laravel community. 
# Eurostar Train Management System

A comprehensive Laravel application designed to manage train schedules, announcements, and real-time updates through the Aviavox system. This platform facilitates GTFS data management, automated announcements, train rule processing, and real-time train status updates.

## Key Features

- **GTFS Data Management**: Download, process, and sync GTFS static and realtime data
- **Text and Audio Announcements**: Create, manage, and schedule both types
- **Aviavox Integration**: Direct integration with the Aviavox system for real-time audio announcements
- **Automated Train Rules**: Process train status changes and trigger automated actions
- **Real-time Updates**: Fetch and process GTFS Realtime data every 30 seconds
- **User Authentication**: Secure access and role-based management
- **Scheduling and Recurrence**: Schedule announcements with customizable recurrence options
- **Livewire Integration**: Real-time updates for an efficient user experience
- **Responsive Design**: Optimized for both desktop and mobile devices

## System Requirements

- **PHP**: Version 8.4 or higher
- **Composer**
- **Node.js & NPM**
- **Database**: MySQL or SQLite
- **Operating System**: Linux (Ubuntu recommended) or Windows Server
- **Process Manager**: Supervisor or Systemd (for Linux) / NSSM (for Windows)

## Installation Guide

### Step 1: Clone the Repository
```bash
git clone https://github.com/yourusername/eurostar.git
cd eurostar
```

### Step 2: Install Backend Dependencies
```bash
composer install
```

### Step 3: Install and Compile Frontend Assets
```bash
npm install
npm run build
```

### Step 4: Configure Environment Variables
```bash
cp .env.example .env
php artisan key:generate
```

- **Database**: Update `.env` with your database connection details.
- **Cache**: Configure cache driver (default: `database`)

### Step 5: Run Migrations and Seeders
```bash
php artisan migrate
php artisan db:seed
```

## Production Commands

### Required Background Processes

These processes must be running continuously in production:

#### 1. Laravel Scheduler
The scheduler runs all scheduled commands automatically. This is the **most critical** process.

```bash
php artisan schedule:work
```

**What it runs:**
- `gtfs:fetch-realtime` - Every 30 seconds (fetches real-time train updates)
- `cache:cleanup-expired` - Hourly (cleans expired cache entries)
- `trains:process-rules` - Every 3 minutes (processes automated train rules)
- `announcements:process-automated` - Every minute (processes automated announcements)
- `gtfs:download` - Daily at 3:00 AM (downloads and processes GTFS static data)
- `trains:cleanup-executions` - Daily (cleans old rule execution records)

**Setup as a service:**
- **Linux (Systemd)**: See `deploy/setup-ubuntu-services.sh`
- **Linux (Supervisor)**: See `deploy/setup-supervisor.sh`
- **Windows**: Use NSSM or Task Scheduler

#### 2. Queue Worker
Processes background jobs including GTFS downloads, train rule processing, and announcements.

```bash
php artisan queue:work --queue=default,train-rules --tries=3 --timeout=3600
```

**What it processes:**
- GTFS data download and processing jobs
- Train rule evaluation and action jobs
- Automated announcement jobs
- Other queued background tasks

**Setup as a service:**
- **Linux (Systemd)**: See `deploy/systemd/laravel-queue.service`
- **Linux (Supervisor)**: See `deploy/supervisor/laravel-queue.conf`
- **Windows**: Use NSSM

### GTFS Commands

#### Download GTFS Static Data
Downloads and processes GTFS static data (scheduled daily at 3:00 AM, but can be run manually):

```bash
php artisan gtfs:download
```

**What it does:**
- Downloads GTFS ZIP file from configured URL
- Extracts and processes stops, routes, trips, stop times, and calendar dates
- Deletes old GTFS data before inserting new data to keep database clean
- Processes data in batches for performance

**When to run manually:**
- Initial setup
- After changing GTFS source URL
- To force a fresh download outside of scheduled time

#### Fetch GTFS Realtime Data
Fetches and processes real-time train updates (scheduled every 30 seconds):

```bash
php artisan gtfs:fetch-realtime
```

**What it does:**
- Fetches real-time data from configured GTFS Realtime URL
- Updates train delays, platform changes, and cancellations
- Marks updates with `is_realtime_update` flag for UI highlighting

**When to run manually:**
- Testing realtime integration
- Debugging realtime data issues

### Cache Commands

#### Clean Expired Cache Entries
Removes expired entries from the database cache table (scheduled hourly):

```bash
# Clean expired cache entries
php artisan cache:cleanup-expired

# Also clean expired cache locks
php artisan cache:cleanup-expired --locks
```

**What it does:**
- Deletes expired cache entries from the `cache` table
- Optionally cleans expired cache locks
- Prevents database bloat from accumulating cache entries
- Logs cleanup results

**When to run manually:**
- If cache table is growing unexpectedly
- After changing cache TTL settings
- To free up database space immediately

### Train Rules Commands

#### Process Train Rules
Evaluates and processes automated train rules (scheduled every 3 minutes):

```bash
php artisan trains:process-rules

# With debug output
php artisan trains:process-rules --debug
```

**What it does:**
- Loads all active train rules with their conditions
- Enqueues jobs to evaluate rules against current train data
- Processes actions when conditions are met (status changes, announcements, etc.)

#### Cleanup Rule Executions
Cleans old rule execution records (scheduled daily):

```bash
php artisan trains:cleanup-executions
```

**What it does:**
- Removes old `train_rule_executions` records to prevent table bloat
- Keeps execution history manageable

### Announcement Commands

#### Process Automated Announcements
Processes automated announcement rules (scheduled every minute):

```bash
php artisan announcements:process-automated

# With debug output
php artisan announcements:process-automated --debug
```

**What it does:**
- Evaluates automated announcement rules
- Triggers announcements when conditions are met
- Sends announcements via Aviavox integration

### Maintenance Commands

#### Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Database Maintenance
```bash
# Run migrations
php artisan migrate

# Run migrations in production (no confirmation)
php artisan migrate --force

# Rollback last migration
php artisan migrate:rollback
```

## Production Setup

### Linux (Ubuntu) Deployment

For complete Ubuntu deployment instructions, see `deploy/UBUNTU-DEPLOYMENT.md`.

**Quick setup:**
```bash
# Using Systemd (recommended)
sudo ./deploy/setup-ubuntu-services.sh

# Or using Supervisor
sudo ./deploy/setup-supervisor.sh
```

This will set up:
- Laravel Scheduler service (runs `schedule:work`)
- Queue Worker service (runs `queue:work`)
- Automatic startup on boot
- Auto-restart on failure
- System logging

### Windows Deployment

#### Using NSSM

1. **Download NSSM**: [nssm.cc](https://nssm.cc/)

2. **Install Scheduler Service**
   ```bash
   nssm install EurostarScheduler "C:\path\to\php\php.exe" "artisan schedule:work"
   nssm set EurostarScheduler AppDirectory "C:\path\to\eurostar"
   nssm set EurostarScheduler DisplayName "Eurostar Scheduler"
   nssm set EurostarScheduler Start SERVICE_AUTO_START
   ```

3. **Install Queue Worker Service**
   ```bash
   nssm install EurostarQueue "C:\path\to\php\php.exe" "artisan queue:work --queue=default,train-rules --tries=3 --timeout=3600"
   nssm set EurostarQueue AppDirectory "C:\path\to\eurostar"
   nssm set EurostarQueue DisplayName "Eurostar Queue Worker"
   nssm set EurostarQueue Start SERVICE_AUTO_START
   ```

4. **Start Services**
   ```bash
   net start EurostarScheduler
   net start EurostarQueue
   ```

### Verifying Services Are Running

**Linux:**
```bash
# Check scheduler
sudo systemctl status laravel-scheduler.service

# Check queue worker
sudo systemctl status laravel-queue.service

# View logs
sudo journalctl -u laravel-scheduler.service -f
sudo journalctl -u laravel-queue.service -f
```

**Windows:**
```bash
# Check services
sc query EurostarScheduler
sc query EurostarQueue

# View logs in Event Viewer or service logs
```

## Configuration Instructions

### Setting Up GTFS Integration

1. **Navigate to Settings → GTFS Settings**
2. **Configure GTFS Static Data:**
   - **GTFS URL**: URL to download GTFS ZIP file
   - **Active**: Enable/disable automatic downloads
   - **Next Download**: Schedule next download time
3. **Configure GTFS Realtime:**
   - **Realtime URL**: URL to fetch GTFS Realtime JSON data
   - **Active**: Enable/disable realtime updates

### Setting Up Aviavox Integration

1. **Navigate to Settings → Aviavox Settings**
2. **Enter Aviavox Connection Details:**
   - **IP Address**
   - **Port**
   - **Username**
   - **Password**
3. **Test the Connection**: Use the "Test Connection" button to ensure connectivity.

### Setting Up Log Settings

1. **Navigate to Settings → Log Settings**
2. **Toggle log types on/off:**
   - GTFS Error/Debug/Information logs
   - Aviavox Error/Debug/Information logs
   - Rules Error/Debug/Information logs
   - Announcement Error/Debug/Information logs

## Using the Application

1. **Access**: Open your browser and navigate to your application URL
2. **Login**: Use your credentials to access the system
3. **Create Announcements**:
   - **Choose Type**: Select between text or audio
   - **Set Details**: Schedule the announcement and configure recurrence options if needed
   - **Manage Announcements**: View, update, or delete announcements as required
4. **Manage Train Rules**:
   - Create automated rules based on train conditions
   - Set up triggers for status changes, delays, platform changes, etc.
   - Configure actions (status updates, announcements, platform updates)

## Troubleshooting

### Common Issues

1. **Scheduler Not Running**:
   - Verify `schedule:work` process is running
   - Check service status: `systemctl status laravel-scheduler.service` (Linux) or `sc query EurostarScheduler` (Windows)
   - Review logs in `storage/logs/laravel.log` or system logs

2. **Queue Jobs Not Processing**:
   - Verify `queue:work` process is running
   - Check service status: `systemctl status laravel-queue.service` (Linux) or `sc query EurostarQueue` (Windows)
   - Check queue connection in `.env` file
   - Review failed jobs: `php artisan queue:failed`

3. **GTFS Download Fails**:
   - Verify GTFS URL is accessible
   - Check GTFS settings are configured correctly
   - Review GTFS logs (if enabled in Log Settings)
   - Check disk space for temporary files

4. **Realtime Updates Not Working**:
   - Verify realtime URL is accessible and returns valid JSON
   - Check GTFS Realtime settings
   - Review GTFS logs for errors
   - Ensure `gtfs:fetch-realtime` command is scheduled and running

5. **Cache Table Growing Too Large**:
   - Verify `cache:cleanup-expired` is scheduled and running
   - Run cleanup manually: `php artisan cache:cleanup-expired`
   - Consider switching to file-based or Redis cache if issue persists

6. **Aviavox Connection Fails**:
   - Ensure network connectivity
   - Double-check Aviavox settings (IP, port, credentials)
   - Verify the Aviavox server is online
   - Test connection from Settings page

7. **Service Not Starting**:
   - Verify PHP path is correct in service configuration
   - Check folder permissions (Linux: `chown -R www-data:www-data /var/www/Eurostar`)
   - Review error logs in `storage/logs/laravel.log`
   - Check system logs for service errors

## Security Recommendations

- **Update Regularly**: Keep your Laravel and dependencies up to date
- **Strong Passwords**: Use secure passwords for authentication
- **File Permissions**: Restrict file permissions for security (Linux: `chmod -R 755` for directories, `644` for files)
- **Database Backups**: Regularly back up your database to prevent data loss
- **Environment Variables**: Never commit `.env` file to version control
- **HTTPS**: Use HTTPS in production for secure communication

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Support and Contributions

- **Issues**: Report issues on the GitHub repository
- **Contributions**: Feel free to fork the repository and submit pull requests
- **Contact**: For support, reach out to your system administrator

# Aviavox Announcements Manager

A Laravel-based web application for managing and scheduling announcements through the Aviavox system. This application allows you to create, schedule, and manage both text and audio announcements, with direct integration to Aviavox announcement systems.

## Features

- Create and manage text announcements
- Schedule audio announcements through Aviavox
- Real-time updates using Livewire
- User authentication and authorization
- Announcement scheduling and recurrence
- Aviavox system integration
- Responsive web interface

## Requirements

- PHP >= 8.1
- Composer
- Node.js & NPM
- SQLite or MySQL
- Windows Server or Windows 10/11
- NSSM (Non-Sucking Service Manager)

## Installation

1. Clone the repository:

bash
git clone https://github.com/yourusername/aviavox-announcements.git
cd aviavox-announcements

2. Install dependencies:

bash
composer install


3. Install and compile frontend assets:

bash
npm install
npm run build

4. Create environment file:

cp .env.example .env

5. Generate application key:

bash
php artisan key:generate

6. Configure your database in `.env`

7. Run migrations:
   
bash
php artisan migrate


## Running as a Windows Service

### Using NSSM (Recommended)

1. Download NSSM from [nssm.cc](https://nssm.cc/)

2. Create a startup batch file (`start-laravel.bat`):
batch
@echo off
cd C:\path\to\aviavox-announcements
php artisan serve --port=8000

3. Install the service using NSSM:

batch
nssm install AviavoxAnnouncements "C:\path\to\start-laravel.bat"
nssm set AviavoxAnnouncements DisplayName "Aviavox Announcements"
nssm set AviavoxAnnouncements Description "Aviavox Announcements Management System"
nssm set AviavoxAnnouncements AppDirectory "C:\path\to\aviavox-announcements"
nssm set AviavoxAnnouncements Start SERVICE_AUTO_START

4. Start the service:

batch
net start AviavoxAnnouncements


### Service Management

- Start: `net start AviavoxAnnouncements`
- Stop: `net stop AviavoxAnnouncements`
- Remove: `nssm remove AviavoxAnnouncements`
- Status: `sc query AviavoxAnnouncements`

You can also manage the service through Windows Services (services.msc)

## Configuration

### Aviavox Settings

1. Navigate to the Settings page
2. Configure Aviavox connection details:
   - IP Address
   - Port
   - Username
   - Password
3. Test the connection using the "Test Connection" button

## Usage

1. Access the application through your web browser
2. Log in with your credentials
3. Navigate to the Announcements page
4. Create new announcements:
   - Choose between text or audio type
   - Set scheduling details
   - Specify recurrence if needed
   - Select area and author

## Troubleshooting

### Common Issues

1. Service won't start:
   - Check PHP path in system environment variables
   - Verify folder permissions
   - Check error logs in storage/logs

2. Aviavox connection fails:
   - Verify network connectivity
   - Check Aviavox settings
   - Ensure correct credentials

## Security

Remember to:
- Keep your application up to date
- Use strong passwords
- Configure proper file permissions
- Regularly backup your database

## License

This application is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Support

For support, please create an issue in the GitHub repository or contact your system administrator.

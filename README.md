
# Aviavox Announcements Manager

A comprehensive Laravel application designed to manage and schedule announcements seamlessly through the Aviavox system. This platform facilitates the creation of both text and audio announcements, with capabilities to test and directly communicate with Aviavox systems.

## Key Features

- **Text and Audio Announcements**: Create, manage, and schedule both types.
- **Aviavox Integration**: Direct integration with the Aviavox system for real-time audio announcements.
- **User Authentication**: Secure access and role-based management.
- **Scheduling and Recurrence**: Schedule announcements with customizable recurrence options.
- **Livewire Integration**: Real-time updates for an efficient user experience.
- **Responsive Design**: Optimized for both desktop and mobile devices.

## System Requirements

- **PHP**: Version 8.1 or higher
- **Composer**
- **Node.js & NPM**
- **Database**: SQLite or MySQL
- **Operating System**: Windows 10/11 or Windows Server
- **NSSM**: For running the application as a Windows service

## Installation Guide

### Step 1: Clone the Repository
\`\`\`bash
git clone https://github.com/yourusername/aviavox-announcements.git
cd aviavox-announcements
\`\`\`

### Step 2: Install Backend Dependencies
\`\`\`bash
composer install
\`\`\`

### Step 3: Install and Compile Frontend Assets
\`\`\`bash
npm install
npm run build
\`\`\`

### Step 4: Configure Environment Variables
\`\`\`bash
cp .env.example .env
php artisan key:generate
\`\`\`

- **Database**: Update \`.env\` with your database connection details.

### Step 5: Run Migrations
\`\`\`bash
php artisan migrate
\`\`\`

## Running as a Windows Service

### Using NSSM

1. **Download NSSM**: [nssm.cc](https://nssm.cc/)

2. **Create a Startup Batch File**
   \`\`\`batch
   @echo off
   cd C:\path\to\aviavox-announcements
   php artisan serve --host=127.0.0.1 --port=8000
   \`\`\`

3. **Install the Service with NSSM**
   \`\`\`bash
   nssm install AviavoxAnnouncements "C:\path\to\php\php.exe" "artisan serve --host=127.0.0.1 --port=8000"
   nssm set AviavoxAnnouncements AppDirectory "C:\path\to\aviavox-announcements"
   nssm set AviavoxAnnouncements DisplayName "Aviavox Announcements"
   nssm set AviavoxAnnouncements Description "Service for Aviavox Announcements Management"
   nssm set AviavoxAnnouncements Start SERVICE_AUTO_START
   \`\`\`

4. **Start the Service**
   \`\`\`bash
   net start AviavoxAnnouncements
   \`\`\`

### Managing the Service
- **Start**: \`net start AviavoxAnnouncements\`
- **Stop**: \`net stop AviavoxAnnouncements\`
- **Remove**: \`nssm remove AviavoxAnnouncements\`
- **Check Status**: \`sc query AviavoxAnnouncements\`

Alternatively, use the Windows Services Manager (\`services.msc\`).

## Configuration Instructions

### Setting Up Aviavox Integration

1. **Navigate to the Settings Page**
2. **Enter Aviavox Connection Details**:
   - **IP Address**
   - **Port**
   - **Username**
   - **Password**
3. **Test the Connection**: Use the "Test Connection" button to ensure connectivity.

## Using the Application

1. **Access**: Open your browser and navigate to \`http://127.0.0.1:8000\` (or your specified host and port).
2. **Login**: Use your credentials to access the system.
3. **Create Announcements**:
   - **Choose Type**: Select between text or audio.
   - **Set Details**: Schedule the announcement and configure recurrence options if needed.
   - **Manage Announcements**: View, update, or delete announcements as required.

## Troubleshooting

### Common Issues

1. **Service Not Starting**:
   - Verify your PHP path is set in system environment variables.
   - Check folder permissions.
   - Review error logs in \`storage/logs\`.

2. **Aviavox Connection Fails**:
   - Ensure network connectivity.
   - Double-check Aviavox settings (IP, port, credentials).
   - Verify the Aviavox server is online.

## Security Recommendations

- **Update Regularly**: Keep your Laravel and dependencies up to date.
- **Strong Passwords**: Use secure passwords for authentication.
- **File Permissions**: Restrict file permissions for security.
- **Database Backups**: Regularly back up your database to prevent data loss.

## License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

## Support and Contributions

- **Issues**: Report issues on the GitHub repository.
- **Contributions**: Feel free to fork the repository and submit pull requests.
- **Contact**: For support, reach out to your system administrator.

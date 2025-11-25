# Admin Settings

The Admin Settings page provides centralized control over system-wide configurations, maintenance, caching, logging, and user management. This page is accessible only to administrators and serves as the command center for system administration.

## Overview

The Admin Settings page is organized into several sections, each managing a specific aspect of the system:

- **System Information**: View current environment and debug settings
- **Maintenance Mode**: Control system access during maintenance
- **Cache Management**: Clear various types of cached data
- **Banner Settings**: Control status banner visibility
- **Log Settings**: Configure what types of logs are written
- **Active Users**: Monitor and manage currently logged-in users
- **Group Management**: Create and manage user groups

---

## System Information

The System Information section displays read-only information about your system configuration.

### Environment

Shows the current application environment:
- **Production**: Live system serving real users
- **Staging**: Testing environment before production
- **Local**: Development environment

### Debug Mode

Indicates whether debug mode is enabled:
- **Enabled**: Detailed error messages and debugging information are shown (use in development only)
- **Disabled**: Standard error handling (recommended for production)

**Note**: These settings are configured in your environment files and cannot be changed from this interface. Contact your system administrator if changes are needed.

---

## Maintenance Mode

Maintenance Mode allows you to restrict system access to administrators only, which is useful during system updates, migrations, or troubleshooting.

### How It Works

When Maintenance Mode is enabled:
- **Administrators** can still access the system normally
- **Regular users** see a maintenance message and cannot access the system
- All user sessions remain intact (users aren't logged out)

### Enabling Maintenance Mode

1. Locate the **Maintenance Mode** toggle switch
2. Click the toggle to enable (it turns red when active)
3. The system immediately restricts access to non-administrators

### Disabling Maintenance Mode

1. Click the toggle switch again to disable
2. The toggle returns to gray (inactive)
3. All users can immediately access the system again

### When to Use

- **Before system updates**: Enable maintenance mode to prevent users from accessing the system during updates
- **During troubleshooting**: Restrict access while investigating issues
- **Scheduled maintenance**: Use during planned maintenance windows
- **Data migrations**: Enable when performing database migrations or large data operations

**Important**: Always disable maintenance mode after completing your work. Users will be unable to access the system until it's disabled.

---

## Cache Management

The Cache Management section provides tools to clear various types of cached data. Clearing cache can help resolve issues with stale data or after configuration changes.

### Clear Application Cache

Clears the general application cache, which stores:
- Configuration data
- Route information
- Service provider data
- Other application-level cached data

**When to use:**
- After changing configuration files
- When experiencing issues with cached configuration
- After updating system settings

**How to use:**
1. Click the **"Clear Cache"** button
2. Wait for confirmation (if available)
3. The cache is cleared immediately

### Clear View Cache

Clears compiled view templates and cached Blade views.

**When to use:**
- After modifying Blade templates
- When view changes aren't appearing
- After updating view-related code

**How to use:**
1. Click the **"Clear Views"** button
2. Views are recompiled on the next request
3. Changes take effect immediately

### Clear All Announcements

Removes all announcements from the system database. This action is **permanent and cannot be undone**.

**Warning**: This will delete all announcements, including:
- Active announcements
- Scheduled announcements
- Completed announcements
- Historical announcement data

**When to use:**
- Starting fresh with announcements
- Cleaning up test data
- Removing all historical announcements

**How to use:**
1. Click the **"Clear Announcements"** button
2. Confirm the action in the popup dialog
3. All announcements are permanently deleted
4. A success message confirms the action

**Best Practice**: Consider exporting or backing up announcements before clearing if you need to preserve historical data.

---

## Banner Settings

The Banner Settings section controls the visibility of the status banner that appears at the top of the dashboard.

### Status Banner

The status banner displays system health information, including:
- Real-time update availability
- GTFS feed connection status
- Service alerts

### Toggle Banner Visibility

**Enable Banner:**
1. Locate the **"Status Banner Visibility"** toggle
2. Click to enable (toggle turns blue)
3. Banner appears at the top of the dashboard when there are status alerts

**Disable Banner:**
1. Click the toggle to disable (toggle turns gray)
2. Banner is hidden even when status alerts exist

### When Banner Appears

The banner automatically appears when:
- No real-time updates are available
- GTFS heartbeat hasn't been received in the last 5 minutes
- System detects connectivity issues

**Note**: Even when the banner is disabled in settings, critical system alerts may still be displayed through other mechanisms.

---

## Log Settings

Log Settings allow you to control which types of logs are written to help with debugging and monitoring. You can enable or disable logging for different system components and log levels.

### Log Categories

The system supports logging for four main categories:

1. **GTFS Logging**: Logs related to GTFS data processing and real-time updates
2. **Aviavox Logging**: Logs related to Aviavox announcement system integration
3. **Automatic Rules Logging**: Logs related to automated announcement rules
4. **Announcement Logging**: Logs related to announcement creation and management

### Log Levels

Each category supports three log levels:

- **Error Logs**: Critical errors and failures that need immediate attention
- **Debug Logs**: Detailed debugging information for troubleshooting
- **Information Logs**: General informational messages about system operations

### Configuring Log Settings

**To enable logging:**
1. Find the category and log level you want to enable
2. Click the toggle switch (turns green when enabled)
3. Changes are saved immediately
4. Logs of that type will now be written

**To disable logging:**
1. Click the toggle switch again (turns gray when disabled)
2. Changes are saved immediately
3. Logs of that type will no longer be written

### Recommended Settings

**For Production:**
- Enable: Error logs for all categories
- Enable: Information logs for critical categories (GTFS, Announcements)
- Disable: Debug logs (too verbose for production)

**For Development/Staging:**
- Enable: All log types for comprehensive debugging
- Monitor log files regularly to understand system behavior

**For Troubleshooting:**
- Enable: Debug logs for the specific category you're investigating
- Review logs after reproducing the issue
- Disable debug logs after troubleshooting to reduce log file size

### Log File Location

Logs are written to: `storage/logs/laravel.log`

You can view and export logs from the Logs section in Settings.

### Impact on Performance

- **Error and Information logs**: Minimal performance impact
- **Debug logs**: Can impact performance if enabled for all categories simultaneously
- **Best practice**: Enable debug logs only when needed for troubleshooting

---

## Active Users

The Active Users section displays all users who are currently logged into the system or have been active recently.

### What is an Active User?

A user is considered "active" if they:
- Are currently logged into the system
- Have had activity within the last 5 minutes
- Have a valid session

### Viewing Active Users

The Active Users table displays:
- **Name**: User's full name
- **Email**: User's email address
- **Last Activity**: Time since last activity (e.g., "2 minutes ago", "1 hour ago")
- **Actions**: Options to manage the user

### Logging Out Users

You can force a user to log out of the system:

**Step-by-step:**
1. Locate the user in the Active Users table
2. Click the **"Log Out"** button in the Actions column
3. Confirm the action in the popup dialog
4. The user's session is immediately terminated
5. They will be logged out on their next page interaction

**When to use:**
- User reports suspicious activity on their account
- User forgot to log out on a shared computer
- Security concerns require immediate session termination
- Testing session management

**What happens:**
- User's session is deleted from the database
- User's last activity timestamp is updated
- User is removed from the active users list
- User must log in again to access the system

**Note**: The user won't see an immediate notification. They'll be logged out when they try to perform their next action.

### Empty State

If no users are currently active, you'll see:
- Message: "No active users found."
- This is normal during off-hours or when the system is not in use

---

## Group Management

The Group Management section allows you to create, edit, and manage user groups. Groups are used to organize users and control access to specific routes, zones, and features.

### What are Groups?

Groups are collections of users that share:
- Access to specific train routes
- Zone assignments for announcements
- Customized dashboard views
- Shared settings and configurations

### Viewing Groups

The Groups table displays:
- **Name**: Group name
- **Description**: Group description (if provided)
- **Status**: Active or Inactive
- **Users**: Number of users in the group (hover to see list)
- **Zones**: Number of zones assigned (hover to see list)
- **Actions**: Edit and Delete buttons

### Creating a New Group

**Step-by-step:**
1. Click the **"Add Group"** button
2. Fill in the group details:
   - **Name**: Required, unique group name (minimum 2 characters)
   - **Description**: Optional description of the group's purpose
   - **Image**: Optional group image/logo
   - **Active**: Checkbox to set group as active (checked by default)
3. **Select Users**: Choose users to add to the group (optional)
4. **Select Zones**: Choose zones to assign to the group (optional)
5. Click **"Save"** to create the group

**Group Name Requirements:**
- Must be unique (cannot duplicate existing group names)
- Minimum 2 characters
- Should be descriptive and clear

### Editing a Group

**Step-by-step:**
1. Find the group in the Groups table
2. Click the **"Edit"** button
3. Modify any group details:
   - Change name, description, or image
   - Add or remove users
   - Add or remove zones
   - Toggle active status
4. Click **"Save"** to update the group

**Note**: Group names must remain unique. If you change a group name to match another group, you'll receive a validation error.

### Deleting a Group

**Step-by-step:**
1. Find the group in the Groups table
2. Click the **"Delete"** button
3. Confirm the deletion in the popup dialog
4. The group is permanently deleted

**Warning**: Deleting a group:
- Removes all user associations
- Removes all zone assignments
- Cannot be undone
- May affect dashboard views and route access

**Before deleting**: Consider deactivating the group instead if you might need it later.

### Activating/Deactivating Groups

**To deactivate a group:**
1. Edit the group
2. Uncheck the **"Active"** checkbox
3. Save the group

**To activate a group:**
1. Edit the group
2. Check the **"Active"** checkbox
3. Save the group

**What happens:**
- **Active groups**: Users can access group-specific features and dashboards
- **Inactive groups**: Group exists but users cannot access group features

### Managing Group Users

**Adding users:**
1. Edit the group
2. Select users from the available users list
3. Selected users appear in the "Selected Users" section
4. Save to add users to the group

**Removing users:**
1. Edit the group
2. In the "Selected Users" section, remove individual users
3. Or click "Clear Selection" to remove all users
4. Save to update the group

**Viewing group users:**
- Hover over the user count in the Groups table
- A tooltip shows all users in that group

### Managing Group Zones

**Adding zones:**
1. Edit the group
2. Select zones from the available zones list
3. Selected zones appear in the "Selected Zones" section
4. Save to assign zones to the group

**Removing zones:**
1. Edit the group
2. In the "Selected Zones" section, remove individual zones
3. Or click "Clear Selection" to remove all zones
4. Save to update the group

**Viewing group zones:**
- Hover over the zone count in the Groups table
- A tooltip shows all zones assigned to that group

### Group Images

You can upload an image/logo for each group:
- **Supported formats**: Standard image formats (JPEG, PNG, etc.)
- **Maximum size**: 2MB
- **Storage**: Images are stored in the `public/storage/groups` directory
- **Usage**: Group images can be displayed in dashboards and reports

**To add/change group image:**
1. Edit the group
2. Click to select an image file
3. Image is uploaded when you save the group

---

## Best Practices

### Maintenance Mode

- **Always disable** maintenance mode after completing work
- **Notify users** before enabling maintenance mode (if possible)
- **Test in staging** before enabling on production
- **Set a time limit** for maintenance windows

### Cache Management

- **Clear cache** after configuration changes
- **Clear views** after template updates
- **Backup data** before clearing announcements
- **Monitor performance** after clearing cache (may temporarily slow down)

### Log Settings

- **Enable error logs** in production for monitoring
- **Enable debug logs** only when troubleshooting
- **Review logs regularly** to identify issues early
- **Disable unnecessary logs** to reduce log file size

### Active Users

- **Monitor regularly** for security purposes
- **Log out inactive sessions** on shared computers
- **Investigate unusual activity** immediately
- **Document logouts** for audit purposes

### Group Management

- **Use descriptive names** for easy identification
- **Keep groups organized** by department or function
- **Review group membership** periodically
- **Deactivate before deleting** if unsure about removal
- **Document group purposes** in descriptions

---

## Troubleshooting

### Maintenance Mode Won't Disable

**Symptoms**: Toggle appears stuck or doesn't respond

**Solutions:**
- Refresh the page and try again
- Check browser console for JavaScript errors
- Verify you have administrator permissions
- Check database connection

### Cache Not Clearing

**Symptoms**: Changes not appearing after clearing cache

**Solutions:**
- Clear both application cache and view cache
- Hard refresh your browser (Ctrl+F5 or Cmd+Shift+R)
- Check file permissions on cache directories
- Restart the application server if needed

### Logs Not Appearing

**Symptoms**: Enabled log types not writing to log file

**Solutions:**
- Verify log file permissions (must be writable)
- Check disk space (logs require available storage)
- Review log settings (ensure correct toggles are enabled)
- Check application logs for log writing errors

### Active Users Not Updating

**Symptoms**: Users appear active after logging out

**Solutions:**
- Wait a few minutes (5-minute activity window)
- Manually log out the user if needed
- Check session cleanup jobs are running
- Verify database connectivity

### Group Changes Not Reflecting

**Symptoms**: Group edits not appearing in dashboards

**Solutions:**
- Clear application cache
- Verify group is set to "Active"
- Check user permissions and group associations
- Refresh dashboard views

---

## Security Considerations

### Administrator Access

- Only users with administrator role can access Admin Settings
- All actions are logged for audit purposes
- Sensitive operations require confirmation

### User Management

- Logging out users should be done with caution
- Document reasons for forced logouts
- Monitor for suspicious user activity

### Maintenance Mode

- Use maintenance mode responsibly
- Notify users when possible
- Set clear maintenance windows
- Always disable after completion

### Group Management

- Review group permissions regularly
- Remove users from groups when they change roles
- Audit group memberships periodically
- Secure group management access

---

## Summary

The Admin Settings page provides comprehensive control over system administration. Key features include:

- **System monitoring**: View environment and debug status
- **Access control**: Maintenance mode for system protection
- **Performance management**: Cache clearing tools
- **Visibility control**: Banner and status display settings
- **Debugging support**: Configurable logging for troubleshooting
- **User management**: Monitor and manage active sessions
- **Organization**: Group creation and management

All settings take effect immediately and are designed to help administrators maintain system health, security, and performance.

For additional help with specific settings, refer to the relevant sections in this documentation or contact your system administrator.

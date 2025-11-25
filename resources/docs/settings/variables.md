# Variables

The Variables page allows you to configure system-wide variables that control check-in timing and manage reason codes used throughout the application. These settings affect how the system calculates check-in times and categorizes operational information.

## Overview

The Variables page is divided into two main sections:

- **Check-in Time Settings**: Configure when check-in starts for trains (global and train-specific)
- **Reasons**: Manage reason codes used for categorizing delays, cancellations, and other operational events

---

## Check-in Time Settings

Check-in time settings determine when passenger check-in begins for trains. You can set a global default that applies to all trains, and override it for specific trains that have different check-in requirements.

### Understanding Check-in Times

**What is a check-in time offset?**
- The number of minutes before a train's departure time that check-in begins
- For example, if a train departs at 10:00 AM and the offset is 90 minutes, check-in starts at 8:30 AM
- This allows passengers to know when they can begin checking in for their journey

**How it works:**
- The system calculates check-in start time by subtracting the offset from the departure time
- Check-in times are displayed on the dashboard and in the API
- The system shows "minutes until check-in starts" to help passengers plan

### Global Check-in Time Setting

The global setting applies to all trains by default. If a train doesn't have a specific setting, it uses the global value.

**Default Value:**
- The system defaults to **90 minutes** before departure
- This is a common standard for international train services

**Setting the Global Offset:**

1. **Locate the Global Check-in Time Setting section**
   - You'll see a description explaining what the setting does
   - The current value is displayed in the input field

2. **Enter the new offset**
   - Type the number of minutes in the "Minutes Before Departure" field
   - Minimum value: 1 minute
   - Common values: 60, 90, 120 minutes

3. **Save the setting**
   - Click the **"Save Global Setting"** button
   - A success message confirms the update
   - The new setting applies immediately to all trains without specific settings

**Example:**
- If you set the global offset to **120 minutes**
- A train departing at 14:30 will have check-in starting at 12:30
- All trains without specific settings will use this 120-minute offset

### Specific Train Settings

Some trains may require different check-in times due to operational requirements, route characteristics, or special circumstances. You can override the global setting for individual trains.

**When to use specific train settings:**
- Trains with longer boarding requirements
- International trains requiring additional documentation checks
- Trains with special security procedures
- Trains with different operational standards

**Adding a Specific Train Setting:**

1. **Locate the "Add New Train Setting" section**
   - This appears in the Specific Train Settings area
   - You'll see two input fields

2. **Enter the Train ID**
   - Type the train identifier (e.g., "9133", "9145")
   - This should match the train number used in the system
   - The Train ID is case-sensitive and must match exactly

3. **Enter the Check-in Offset**
   - Type the number of minutes before departure for this specific train
   - Minimum value: 1 minute
   - This value will override the global setting for this train

4. **Add the setting**
   - Click the **"Add Train Setting"** button
   - A success message confirms the addition
   - The train appears in the table below

**Example:**
- Train ID: **9133**
- Minutes Before Departure: **120**
- This train will have check-in starting 120 minutes before departure, regardless of the global setting

**Viewing Existing Train Settings:**

- All specific train settings are displayed in a table below the form
- The table shows:
  - **Train ID**: The train identifier
  - **Minutes Before Departure**: The check-in offset for that train
  - **Actions**: Remove button

**Removing a Specific Train Setting:**

1. **Find the train in the table**
   - Locate the train ID you want to remove

2. **Click Remove**
   - Click the **"Remove"** button in the Actions column
   - A success message confirms the removal
   - The train will now use the global check-in time setting

**Important Notes:**

- **Train ID Matching**: The Train ID must match exactly how it appears in the GTFS data (usually the trip_short_name or first part of trip_id)
- **Priority**: Specific train settings always override the global setting
- **No Duplicates**: Each train can only have one specific setting
- **Case Sensitivity**: Train IDs are case-sensitive
- **Immediate Effect**: Changes take effect immediately and affect check-in time calculations

### How Check-in Times Are Calculated

The system uses the following logic to determine check-in start time:

1. **Check for specific train setting**
   - If the train has a specific setting, use that offset
   - Otherwise, use the global setting

2. **Calculate check-in start time**
   - Subtract the offset from the departure time
   - Example: Departure 10:00 - 90 minutes = Check-in starts 8:30

3. **Handle edge cases**
   - If check-in time is in the past but departure is in the future, check-in has already started
   - If both check-in and departure are in the past, the calculation is for the next day

4. **Display to users**
   - Show check-in start time on dashboard
   - Show "minutes until check-in starts" countdown
   - Update in real-time as time progresses

---

## Reasons

Reasons are standardized codes used to categorize and explain operational events such as delays, cancellations, service changes, and other status updates. Managing reasons helps maintain consistency in how information is communicated throughout the system.

### What are Reasons?

Reasons consist of three components:

- **Code**: A short identifier (e.g., "WEATHER", "TECHNICAL", "STRIKE")
- **Name**: A descriptive name (e.g., "Weather Conditions", "Technical Issue", "Industrial Action")
- **Description**: Optional detailed explanation of what the reason means

**Purpose:**
- Standardize status explanations across the system
- Provide consistent terminology for announcements
- Enable reporting and analysis of operational issues
- Support automated rule triggers and announcements

### Viewing Reasons

The Reasons section displays all configured reasons in a table format:

**Table Columns:**
- **Code**: The reason code identifier
- **Name**: The reason name
- **Description**: The detailed description (if provided)
- **Actions**: Edit and Delete buttons

**Empty State:**
- If no reasons exist, you'll see: "No reasons found"
- You can start adding reasons using the form above the table

### Adding a New Reason

**Step-by-step process:**

1. **Fill in the form fields**
   - **Code**: Enter a short, unique identifier (required, max 50 characters)
     - Use uppercase letters and underscores (e.g., "WEATHER_DELAY", "TECHNICAL_ISSUE")
     - Keep codes concise and descriptive
   - **Name**: Enter a clear, descriptive name (required, max 255 characters)
     - This is what users will see (e.g., "Weather Delay", "Technical Issue")
   - **Description**: Enter optional details about when/how to use this reason
     - Helpful for other administrators to understand the reason's purpose

2. **Submit the form**
   - Click the **"Add Reason"** button
   - The reason is saved and appears in the table immediately
   - The form clears, ready for the next reason

**Validation Rules:**
- **Code**: Must be unique (cannot duplicate existing codes)
- **Code**: Required field, cannot be empty
- **Name**: Required field, cannot be empty
- **Description**: Optional field

**Example:**
- Code: `WEATHER`
- Name: `Weather Conditions`
- Description: `Use when delays or cancellations are caused by adverse weather conditions such as snow, ice, or high winds`

### Editing an Existing Reason

**Step-by-step process:**

1. **Find the reason in the table**
   - Locate the reason you want to edit

2. **Click Edit**
   - Click the **"Edit"** button in the Actions column
   - The form fields populate with the current reason data
   - The button changes to "Update Reason"
   - A "Cancel" button appears

3. **Make your changes**
   - Modify any of the fields (Code, Name, or Description)
   - Note: Code must remain unique if changed

4. **Save or cancel**
   - Click **"Update Reason"** to save changes
   - Or click **"Cancel"** to discard changes and return to the form

**Important Notes:**
- **Code Uniqueness**: If you change a code, it must not conflict with existing codes
- **Immediate Effect**: Changes take effect immediately throughout the system
- **No Undo**: There's no undo function, so be careful when editing

### Deleting a Reason

**Step-by-step process:**

1. **Find the reason in the table**
   - Locate the reason you want to delete

2. **Click Delete**
   - Click the **"Delete"** button in the Actions column
   - The reason is immediately removed from the system

**Warning:**
- **Permanent Action**: Deletion cannot be undone
- **System Impact**: If the reason is in use elsewhere, those references may need updating
- **No Confirmation**: The deletion happens immediately (no confirmation dialog)

**Before Deleting:**
- Check if the reason is used in automated rules
- Verify it's not referenced in announcements
- Consider if you might need it again (you'd have to recreate it)

### Reason Code Best Practices

**Naming Conventions:**
- Use uppercase letters for codes
- Separate words with underscores (e.g., `TECHNICAL_ISSUE`)
- Keep codes short but descriptive
- Avoid special characters

**Common Reason Categories:**
- **Weather**: `WEATHER`, `SNOW`, `ICE`, `HIGH_WINDS`
- **Technical**: `TECHNICAL_ISSUE`, `SIGNAL_FAILURE`, `TRACK_MAINTENANCE`
- **Operational**: `CREW_SHORTAGE`, `EQUIPMENT_FAULT`, `OPERATIONAL_INCIDENT`
- **External**: `STRIKE`, `SECURITY_INCIDENT`, `MEDICAL_EMERGENCY`
- **Schedule**: `SCHEDULE_CHANGE`, `CONNECTION_DELAY`, `ROUTE_ALTERATION`

**Organizing Reasons:**
- Group related reasons with similar prefixes
- Use consistent naming patterns
- Document reasons clearly in descriptions
- Review and consolidate duplicate reasons periodically

---

## Best Practices

### Check-in Time Settings

**Global Setting:**
- Set based on your operational requirements
- Consider average boarding time needed
- Account for security and documentation checks
- Review periodically to ensure it meets passenger needs

**Specific Train Settings:**
- Only create specific settings when truly needed
- Document why a train needs a different check-in time
- Review specific settings periodically to ensure they're still needed
- Remove specific settings when trains are retired or requirements change

**Maintenance:**
- Keep a list of trains with specific settings
- Review settings when train schedules change
- Test check-in time calculations after making changes
- Monitor passenger feedback about check-in timing

### Reasons Management

**Creating Reasons:**
- Plan your reason structure before adding many reasons
- Use consistent naming conventions
- Write clear descriptions for future reference
- Consider how reasons will be used in automated rules

**Maintaining Reasons:**
- Review reasons periodically for duplicates
- Update descriptions if usage patterns change
- Archive or remove reasons that are no longer used
- Keep reason codes aligned with operational terminology

**Documentation:**
- Document the purpose of each reason in its description
- Maintain a reference guide for common reasons
- Train staff on which reasons to use in different situations
- Review reason usage in reports to identify patterns

---

## Troubleshooting

### Check-in Times Not Updating

**Symptoms**: Changes to check-in settings don't appear on the dashboard

**Solutions:**
- Clear application cache (see Admin Settings)
- Verify the Train ID matches exactly (case-sensitive)
- Check that you clicked "Save" after making changes
- Refresh the dashboard page
- Verify the train is using the correct setting (global vs. specific)

### Train ID Not Matching

**Symptoms**: Specific train setting doesn't apply

**Solutions:**
- Check the exact Train ID format in GTFS data
- Verify case sensitivity (uppercase vs. lowercase)
- Ensure there are no extra spaces in the Train ID
- Check if the train number format has changed
- Try using just the numeric part if the full ID doesn't work

### Reason Code Conflicts

**Symptoms**: Cannot save a reason because code already exists

**Solutions:**
- Check the existing reasons table for duplicates
- Use a different code name
- Edit the existing reason if you meant to update it
- Consider adding a suffix (e.g., `WEATHER_1`, `WEATHER_2`)

### Reasons Not Appearing in Dropdowns

**Symptoms**: New reasons don't show in rule creation or other forms

**Solutions:**
- Refresh the page
- Clear browser cache
- Verify the reason was saved successfully
- Check if there are filters limiting which reasons appear
- Ensure the reason form was submitted correctly

---

## Integration with Other Features

### Check-in Times and Dashboard

- Check-in start times appear on train cards
- "Minutes until check-in starts" countdown is displayed
- Real-time updates show when check-in begins
- API endpoints include check-in time information

### Check-in Times and Automated Rules

- Rules can trigger based on "minutes until check-in starts"
- Useful for sending announcements when check-in opens
- Can create rules for specific check-in time windows
- Supports automated passenger notifications

### Reasons and Status Updates

- Reasons can be associated with train status updates
- Used to explain why a train is delayed or cancelled
- Appear in announcements and notifications
- Support reporting and analysis

### Reasons and Automated Rules

- Rules can trigger based on reason codes
- Different announcements for different reason types
- Conditional logic based on reason categories
- Support for reason-based routing and zones

---

## Summary

The Variables page provides essential configuration for:

- **Check-in Timing**: Control when passengers can check in for trains
  - Global default for all trains
  - Specific overrides for individual trains
  - Real-time calculation and display

- **Reason Management**: Standardize operational event categorization
  - Create, edit, and delete reason codes
  - Maintain consistent terminology
  - Support automated systems and reporting

Both features are critical for operational efficiency and passenger communication. Regular review and maintenance of these settings ensures the system accurately reflects your operational requirements.

For questions or issues with variables configuration, consult your system administrator or refer to the relevant sections in this documentation.

# Dashboard

The dashboard is your main view for monitoring and managing train schedules. It displays real-time train information in two complementary views: the **Train Grid** (card-based layout) and the **Train Table** (compact list view).

## Overview

The dashboard automatically refreshes every 30 seconds when the page is visible, ensuring you always see the latest train information. A live clock in the bottom-right corner shows the current time, updating every second.

### Main Components

- **Train Grid**: Visual card-based display showing detailed train information
- **Train Table**: Compact table view for quick scanning of multiple trains
- **Current Time Display**: Live clock showing the current time (bottom-right corner)

---

## Train Grid

The Train Grid displays trains as individual cards, making it easy to see detailed information at a glance.

### Date Selection

At the top of the Train Grid, you'll find a date selector that allows you to view trains for different dates.

**How to use:**
- Click the date field to open a calendar picker
- Select any date from today up to 30 days in the future
- Click the **"Today"** button to quickly return to today's schedule
- The grid automatically loads trains for the selected date

**What you'll see:**
- A loading indicator appears while trains are being loaded
- The header shows how many trains are scheduled for the selected date
- If no trains are found, you'll see a helpful message with an option to return to today's trains

### Train Cards

Each train is displayed as a card showing essential information:

**Card Information:**
- **Route Name**: The full name of the train route (e.g., "Amsterdam Centraal to Brussels Midi")
- **Route Short Name**: The abbreviated route identifier
- **Stop Name**: The current stop being displayed
- **Arrival Time**: When the train arrives at this stop (large, bold display)
- **Departure Time**: When the train departs from this stop (large, bold display)
- **Platform Numbers**: The platform where the train arrives and departs
- **Status Badge**: Color-coded status indicator showing the train's current status

**Real-Time Updates:**
- Times and platforms highlighted in **orange** indicate real-time updates from the GTFS feed
- If a departure time has changed, you'll see the original time crossed out with the new time displayed in orange
- Platform numbers highlighted in orange indicate they've been updated from real-time data

**Platform Information:**
- Platform numbers are shown for both arrival and departure
- If a platform hasn't been assigned yet, you'll see **"TBD"** (To Be Determined)
- Platform assignments can come from:
  - Scheduled platform data
  - Manual assignments
  - Real-time updates (shown in orange)

**View Route Button:**
- Click the route icon (three vertical lines) on any train card to view the complete journey
- This opens a modal showing all stops along the route in sequence

### Updating Train Status

You can update a train's status, departure time, and platform assignment directly from the dashboard.

**Step-by-step process:**

1. **Select a train**: Click the **"Select"** button on any train card
2. **Update modal opens**: A modal window appears with the current train information
3. **Make your changes**:
   - **Status**: Choose from the dropdown (On-time, Delayed, Cancelled, Completed)
   - **Departure Time**: Enter a new time if the schedule has changed
   - **Platform**: Enter the platform number if it differs from what's shown
4. **Save changes**: Click **"Update Status"** to save your changes
5. **Cancel**: Click **"Cancel"** to close without saving

**What happens after updating:**
- Your changes are saved immediately
- The train card updates to reflect the new status and information
- All users viewing the dashboard see the update in real-time (no page refresh needed)
- Status colors update automatically based on the selected status

**Status Options:**
- **On-time**: Train is running according to schedule
- **Delayed**: Train is running behind schedule
- **Cancelled**: Train service has been cancelled
- **Completed**: Train has completed its journey

### Viewing Train Route

To see the complete journey for any train, including all stops along the route:

1. Click the **route icon** (three vertical lines) on a train card
2. A modal opens showing the full route
3. The route displays:
   - All stops in sequence (numbered)
   - Arrival and departure times at each stop
   - A visual progress indicator showing the journey
   - The current position of the train (indicated by a train icon)

**Route Modal Features:**
- Stops are displayed in order from departure to arrival
- The first stop shows departure time
- Intermediate stops show both arrival and departure times
- The last stop shows arrival time
- A progress bar visually represents the journey
- Click the X button or click outside the modal to close

---

## Train Table

The Train Table provides a compact, list-based view of trains, making it easy to scan through multiple trains quickly.

### Table Display

The table shows trains in rows with the following columns:

- **Train**: The train number/identifier
- **Platform**: The departure platform
- **Time**: The departure time
- **Route**: The full route name

### Pagination

When there are many trains, the table uses pagination to organize them into pages.

**How pagination works:**
- The table shows 8 trains per page by default
- At the bottom of the table, you'll see: "Showing X to Y of Z results"
  - X = First train number on this page
  - Y = Last train number on this page
  - Z = Total number of trains

**Navigating pages:**
- Click **"Previous"** to go to the previous page
- Click **"Next"** to go to the next page
- Navigation buttons are disabled when you're on the first or last page

**Mobile view:**
- On smaller screens, pagination controls are simplified
- Previous and Next buttons are always visible

---

## Status Colors and Meanings

Trains are color-coded by their status to make it easy to identify their condition at a glance.

### Status Color System

**On-time (Green)**
- Indicates the train is running according to schedule
- Color: Green (#22C55E or similar)
- Use when: Train is operating normally

**Delayed (Yellow/Orange)**
- Indicates the train is running behind schedule
- Color: Yellow/Orange (#EAB308 or similar)
- Use when: Train departure or arrival is later than scheduled

**Cancelled (Red)**
- Indicates the train service has been cancelled
- Color: Red (#EF4444 or similar)
- Use when: Train will not operate

**Completed (Gray)**
- Indicates the train has finished its journey
- Color: Gray (#9CA3AF or similar)
- Use when: Train has reached its final destination

### Customizing Status Colors

Status colors can be customized by administrators in the system settings. The colors you see are configured to match your organization's standards and are consistent across all views.

---

## Real-Time Updates

The dashboard automatically stays up-to-date with the latest train information.

### Automatic Refresh

**How it works:**
- The dashboard checks for updates every 30 seconds
- Updates only occur when the page is visible in your browser
- If you switch to another tab, updates pause to save resources
- When you return to the tab, updates resume automatically

**What gets updated:**
- Train arrival and departure times
- Platform assignments
- Train statuses
- New trains appearing in the schedule
- Trains that have departed (removed after 30 minutes)

### Real-Time Update Indicators

**Orange highlighting:**
- Times, platforms, or other information highlighted in orange indicate real-time updates
- These updates come directly from the GTFS real-time feed
- Orange highlighting helps you quickly identify what has changed

**Instant status updates:**
- When you or another user updates a train status, all connected users see the change immediately
- No page refresh is needed
- Changes are broadcast in real-time using WebSocket technology

**Status change notifications:**
- The system uses events to notify all users of status changes
- Updates appear instantly across all open dashboard sessions

---

## Empty States

When no trains are scheduled or found, the dashboard displays helpful messages.

### No Trains Found

**What you'll see:**
- A centered message with an icon
- Text explaining why no trains are shown
- A button to return to today's trains

**Common scenarios:**
- **Selected date has no trains**: "No trains are scheduled for [date] on your selected routes."
- **Today has no trains**: "No trains are currently scheduled for your selected routes today."

**What to do:**
- Click the **"View Today's Trains"** button to return to today's schedule
- Check your route selections in settings
- Verify the date you've selected

### Loading States

**While loading:**
- A blue loading indicator appears
- Message: "Loading trains for selected date..."
- The grid is temporarily hidden during loading

**After loading:**
- Trains appear in the grid
- Loading indicator disappears
- If no trains found, empty state message appears

---

## Group Dashboards

The system supports both a main dashboard and group-specific dashboards.

### Main Dashboard vs Group Dashboard

**Main Dashboard:**
- Shows all trains from all active routes
- Accessible to all users
- Displays trains based on global route selections

**Group Dashboard:**
- Shows trains filtered by group-specific route selections
- Each group can have its own set of selected routes
- Useful for departments or teams that focus on specific routes
- Access via group-specific URLs

### How Group Filtering Works

**Route Selection:**
- Groups can select which routes to display
- Only trains from selected routes appear in the group dashboard
- Route selections are managed by administrators

**What's displayed:**
- Train Grid shows trains matching the group's route selections
- Train Table shows trains matching the group's route selections
- Date selection works the same as the main dashboard
- All other features (status updates, route viewing) work identically

**Benefits:**
- Focused view for specific operational areas
- Reduced clutter by showing only relevant trains
- Customized experience per group or department

---

## Tips and Best Practices

### Efficient Navigation

- Use the **"Today"** button to quickly return to today's schedule
- The date selector remembers your last selection
- Use the Train Table for quick scanning when you need to see many trains

### Status Updates

- Update status as soon as you receive information about delays or changes
- Changes are visible to all users immediately
- Use the platform field to update platform assignments when they change

### Real-Time Information

- Orange highlighting helps you quickly spot real-time updates
- Check the current time display to verify you're seeing the latest information
- The dashboard automatically removes trains that departed more than 30 minutes ago

### Viewing Routes

- Use the route view to see the complete journey when planning or troubleshooting
- The route modal shows all stops in sequence, making it easy to understand the full trip
- Close the route modal by clicking the X or clicking outside the modal

---

## Troubleshooting

### Trains Not Appearing

**Check the following:**
- Verify the date you've selected (try clicking "Today")
- Confirm routes are selected in settings
- Check if trains have already departed (they're removed 30 minutes after departure)

### Status Not Updating

**If status changes aren't appearing:**
- Wait a few seconds (updates happen every 30 seconds)
- Check your internet connection
- Try refreshing the page if updates seem stuck

### Platform Shows "TBD"

**This is normal when:**
- Platform hasn't been assigned yet
- Real-time data hasn't provided platform information
- Manual assignment hasn't been made

**To fix:**
- Update the train status and enter the platform number
- Wait for real-time data to provide platform information
- Check with operations for platform assignments

---

## Summary

The dashboard provides a comprehensive view of train schedules with real-time updates, easy status management, and flexible viewing options. Whether you're monitoring trains, updating statuses, or viewing routes, the dashboard keeps you informed with automatic updates and intuitive controls.

**Key Features:**
- Automatic updates every 30 seconds
- Easy date navigation (today + 30 days)
- Detailed train cards with all essential information
- Quick status updates with real-time broadcasting
- Complete route viewing
- Compact table view for scanning
- Color-coded status system
- Group-specific filtering

For technical details about the API or system configuration, see the [API Documentation](../api/index.md).

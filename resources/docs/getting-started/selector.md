# Group Selector

The Group Selector is your landing page after logging into the system. It displays all the groups you have access to, allowing you to choose which group's dashboard and views you want to use.

## Overview

After logging in, you'll be redirected to the Group Selector page. This page shows a grid of group cards, each representing a group you've been added to. Each group provides access to customized views, dashboards, and features based on your group membership.

**Key Concept**: The views and features you can access are determined by which groups you belong to. You'll only see groups that you've been added to by an administrator.

---

## How It Works

### Group-Based Access

**What you see:**
- Only groups you're a member of appear on the selector page
- Only active groups are displayed (inactive groups are hidden)
- Groups are sorted alphabetically by name

**What you don't see:**
- Groups you haven't been added to
- Inactive groups (even if you're a member)
- Groups that have been deleted

**Access Control:**
- You can only access dashboards and views for groups you belong to
- If you try to access a group you're not a member of, you'll see an access denied message
- Group membership is managed by administrators in the Admin Settings

### Group Cards

Each group is displayed as a card with the following features:

**Visual Elements:**
- **Group Name**: Displayed prominently on the card
- **Group Image**: If the group has a custom image, it appears as the background
- **Default Background**: If no image is set, a gradient background is used
- **Hover Effects**: Cards have smooth animations when you hover over them
  - Shadow increases
  - Image scales slightly (if present)
  - Arrow icon appears in the top-right corner

**Card Information:**
- Group name is always visible
- Cards are uniform in size (240x240 pixels)
- Cards are arranged in a responsive grid layout

### Navigating to a Group Dashboard

**To access a group's dashboard:**

1. **Find the group card** you want to access
2. **Click anywhere on the card**
3. **You'll be taken to that group's dashboard**

**What happens:**
- The group's dashboard loads with views filtered for that group
- Train information is filtered based on the group's route selections
- Announcements and features are customized for that group
- The URL changes to reflect the selected group (e.g., `/group-name/dashboard`)

**Group-Specific Views:**
- Each group can have different route selections
- Each group can have different zone assignments
- Each group can have different train table configurations
- Dashboard content is filtered based on group settings

---

## Understanding Group Access

### Why Groups Matter

Groups determine what you can see and do in the system:

**Dashboard Content:**
- Train schedules are filtered to show only routes selected for your group
- Train tables show only trains relevant to your group's routes
- Announcements may be filtered by group zones

**Feature Access:**
- Some features may be group-specific
- Route selections are managed per group
- Zone assignments affect announcement targeting

**Customization:**
- Each group can have its own image/logo
- Group names help identify different operational areas
- Groups can represent departments, locations, or functional areas

### Multiple Group Membership

**If you belong to multiple groups:**
- All your groups appear on the selector page
- You can switch between groups by returning to the selector
- Each group provides a different view of the system
- Group settings are independent (changing one doesn't affect others)

**Best Practice:**
- Choose the group that matches your current operational focus
- Return to the selector to switch between groups as needed
- Remember that each group shows different filtered information

### No Groups Assigned

**If you don't see any groups:**
- You may not have been added to any groups yet
- Contact your administrator to be added to a group
- Administrators can add you to groups in Admin Settings → Group Management

**If groups are missing:**
- The groups may be inactive (administrators can activate them)
- You may have been removed from a group
- Contact your administrator if you expect to see a group

---

## Admin Features

### Settings Access

**For Administrators:**
- A **Settings** button appears in the bottom-right corner
- This button is only visible to users with administrator role
- Clicking it takes you to the Settings page

**Settings Button:**
- Fixed position in bottom-right corner
- Always accessible from the selector page
- Provides quick access to system configuration

---

## Visual Guide

### Group Card Layout

```
┌─────────────────────┐
│                     │
│   [Group Image]     │  ← Custom image or gradient background
│   or Gradient       │
│                     │
│                     │
│                     │
│                     │
│   Group Name        │  ← Group name at bottom
│   →                 │  ← Arrow appears on hover
└─────────────────────┘
```

### Selector Page Layout

```
┌─────────────────────────────────────────┐
│                                         │
│    [Group 1]    [Group 2]    [Group 3]  │
│                                         │
│    [Group 4]    [Group 5]              │
│                                         │
│                                         │
│                              [Settings] │  ← Admin only
└─────────────────────────────────────────┘
```

---

## Common Scenarios

### Scenario 1: First Time Login

**What happens:**
1. You log in successfully
2. You're redirected to the selector page
3. You see groups you've been added to
4. Click a group to access its dashboard

**If no groups appear:**
- Contact your administrator
- You may need to be added to a group first

### Scenario 2: Switching Between Groups

**How to switch:**
1. Use the browser's back button to return to the selector
2. Or navigate directly to `/selector` in your browser
3. Click a different group card
4. You'll see that group's filtered dashboard

**Why switch:**
- Different groups show different routes and trains
- You may need to monitor multiple operational areas
- Each group provides a focused view

### Scenario 3: Group Access Denied

**If you see an access denied message:**
- You tried to access a group you're not a member of
- Return to the selector page
- Only click on groups that appear on your selector page
- Contact your administrator if you believe you should have access

---

## Tips and Best Practices

### Using the Selector

- **Bookmark your most-used group**: After clicking a group, bookmark that dashboard URL for quick access
- **Check group names**: Group names help identify which operational area they represent
- **Use group images**: Custom group images make it easier to quickly identify groups
- **Return to selector**: Use the selector as a hub to switch between different views

### Understanding Your Access

- **Ask administrators**: If you're unsure which group to use, ask your administrator
- **Check group descriptions**: Administrators can add descriptions to groups (visible in group management)
- **Multiple groups**: If you belong to multiple groups, each provides a different perspective
- **Stay in your group**: Use the group assigned to your operational area for the most relevant information

### Troubleshooting

**Groups not appearing:**
- Verify you're logged in with the correct account
- Check with your administrator about group membership
- Ensure groups are set to "Active" status

**Can't access a group:**
- Only access groups that appear on your selector page
- If a group disappears, you may have been removed
- Contact your administrator for access issues

**Settings button missing:**
- Only administrators see the settings button
- If you need admin access, contact your system administrator

---

## Summary

The Group Selector is your entry point to the system and determines what you can see:

- **Shows your groups**: Only groups you're a member of are displayed
- **Group-based access**: Views and features are filtered by group membership
- **Easy navigation**: Click any group card to access that group's dashboard
- **Customized views**: Each group provides filtered, relevant information
- **Admin access**: Administrators have quick access to settings

**Remember**: Your access to views, dashboards, and features is determined by which groups you've been added to. If you need access to additional groups or features, contact your system administrator.

For more information about groups and how they're managed, see the [Admin Settings documentation](../settings/admin-settings.md#group-management).


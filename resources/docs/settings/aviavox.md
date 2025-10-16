# Aviavox Settings

The **Aviavox Settings** section allows you to configure and manage all settings related to the **Aviavox connection** and **audio template management**.
These settings enable seamless communication between the **SignaRail server** and the **Aviavox controller**, allowing the system to generate and play audio announcements across stations and platforms.

---

## Configuration

In this section, you can set up and manage the **Aviavox server connection**.
A valid connection ensures that SignaRail can send announcement messages directly to the Aviavox controller.

### Aviavox Connection Details

To configure the connection between **Aviavox** and **SignaRail**, provide the following information (obtainable from your IT Administrator or Aviavox representative):

* **IP Address**
* **Port**
* **Username**
* **Password**

Once all details are entered, click **Save Settings** to store the connection details in the SignaRail database.

---

### Test Connection

After saving your configuration, verify that the SignaRail–Aviavox connection is active by selecting **Test Connection**.

* If the test is successful, the connection is properly configured.
* If the test fails, review the **Logs** (found under the *Settings* menu) or contact your **IT Administrator** for assistance.

---

## Zones

Zones define where audio announcements will be played within a terminal or platform.
You must specify these zones to ensure Aviavox sends announcements to the correct areas.

### What is a Zone?

A **Zone** represents a defined location—such as a terminal, concourse, or platform—treated as an **isolated audio area** by the Aviavox controller.
Multiple zones can be linked to a single announcement, depending on its intended scope.

---

### Adding Zones

To add a new zone:

1. Enter the **Zone Value** provided by Aviavox.
2. Click **Add Zone**.

The new zone will be added to the SignaRail platform and can be used in:

* The **Announcement Wizard**, or
* **Automated Rules and Triggers** for scheduled or rule-based announcements.

---

### Deleting Zones

To delete a zone:

1. Locate the zone in the **Existing Zones** table.
2. Click the **Delete** action on the right-hand side.

---

## Announcement History

The **Announcement History** table displays all announcements triggered via SignaRail, whether manually or automatically.
It provides a complete record of announcement activity for reference and auditing.

The table includes the following information:

* **Type** – Indicates if the announcement was *Manual* or *Automatic*.
* **Message** – The friendly name or title of the announcement.
* **Time** – The date and time the announcement was triggered.
* **Author** – The user who made the announcement (or *System* if automatic).
* **Zone** – The zones in which the announcement was played.
* **Status** – The current state of the announcement (*In Progress*, *Completed*, etc.).

---

## Announcement Templates

An **Announcement Template** defines the structure and content of an audio message that will be sent to the Aviavox controller for playback.

### Adding Announcement Templates

To add a new template, provide the required information used to generate the Aviavox message:

* **Friendly Name**
* **Template Name**
* **XML Template**

Example XML message:

```xml
<AIP>
  <MessageID>AnnouncementTriggerRequest</MessageID>
  <MessageData>
    <AnnouncementData>
      <Item ID="MessageName" Value="CHECKING_WELCOME_CLOSED"/>
      <Item ID="TrainNumber" Value="1234"/>
      <Item ID="Route" Value="GBR_LON"/>
      <Item ID="ScheduledTime" Value="2024-08-22T07:45:00Z"/>
      <Item ID="Zones" Value="Terminal"/>
    </AnnouncementData>
  </MessageData>
</AIP>
```

After inputting the XML template and clicking outside the input box, **SignaRail** automatically detects and displays the available **variables** within the template.
These appear as **dropdown selectors**.

---

### Template Variables

Each template variable corresponds to a dynamic data point that can be updated automatically or manually.
Common variables include:

* **Route**
* **Zone**
* **Train Number**
* **Date and Time**

Variables can be automatically assigned using **Rules and Triggers**, or manually configured through the **Announcement Wizard**.

---

## Existing Announcement Templates

The **Existing Announcement Templates** table lists all templates currently configured in the system.
This table provides the following details:

* **Friendly Name**
* **Template Name**
* **Variables**
* **Created At**
* **Actions**

### View XML

Click **View XML** to display the full XML structure of the selected template.

### Edit

Click **Edit** to modify an existing announcement template.

### Delete

Click **Delete** to remove outdated or unused templates.

---

## Aviavox Responses

When an announcement is triggered, the **Aviavox controller** sends a response containing the generated **audio message in text form**.
You can view these responses in the **Aviavox Announcements** table, which includes:

* **Announcement ID**
* **Status**
* **Message Name**
* **Received At**
* **Actions**

These response messages can also be retrieved via the **SignaRail API**, enabling integration with **digital signage systems** to display the audio message text visually.

---

## Custom Announcements

The **Custom Announcements** feature allows advanced users to design and send **bespoke audio messages** outside of predefined templates.
This is ideal for special events, service disruptions, or one-off announcements that fall outside regular automated operations.

---

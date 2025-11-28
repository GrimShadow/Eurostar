# Rules and Triggers

## Train Status Rules

In this section, users can create rules that automatically change train statuses, trigger announcements, or update platform information based on conditions like time, current status, or train properties.

### Creating new Rules

To create a rule, use the "Create New Rule" section by supplying conditional information for the rule.

The basic principle of a rule is to have the following components:

- **One or more Conditions:** Conditions define when the rule should trigger
- **An Operator:** Defines how to compare the condition value
- **A Value:** The value to compare against
- **An Action:** What to do when the condition is met

#### Condition Types

Conditions are organized into several categories:

##### Time-Based Conditions

- **Time Until Departure**: Number of minutes until the train's scheduled departure time
  - Value type: Number (minutes)
  - Example: "Time Until Departure > 30" means the train departs in more than 30 minutes

- **Time After Departure**: Number of minutes after the train's scheduled departure time
  - Value type: Number (minutes)
  - Example: "Time After Departure > 5" means the train departed more than 5 minutes ago

- **Time Until Arrival**: Number of minutes until the train's scheduled arrival time at the final destination
  - Value type: Number (minutes)
  - Example: "Time Until Arrival < 10" means the train arrives in less than 10 minutes

- **Time After Arrival**: Number of minutes after the train's scheduled arrival time
  - Value type: Number (minutes)
  - Example: "Time After Arrival > 0" means the train has already arrived

- **Minutes Until Check-in Starts**: Number of minutes until check-in begins for the train
  - Value type: Number (minutes)
  - Example: "Minutes Until Check-in Starts <= 15" means check-in starts in 15 minutes or less

- **Time Range**: Current time falls within a specified time range
  - Value type: Time range (start time - end time)
  - Example: "Time Range = 08:00-10:00" means current time is between 8 AM and 10 AM

- **Day of Week**: Current day matches specified days
  - Value type: Select (Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday)
  - Example: "Day of Week = Monday" means the rule only applies on Mondays

- **Is Peak Time**: Whether current time is during peak hours (7-9 AM or 4-7 PM)
  - Value type: Boolean (true/false)
  - Example: "Is Peak Time = true" means the rule applies during peak hours

##### Realtime Data Conditions

- **Delay (Minutes)**: The delay in minutes compared to scheduled departure time
  - Value type: Number (minutes)
  - Example: "Delay (Minutes) > 10" means the train is delayed by more than 10 minutes

- **Delay (Percentage)**: The delay as a percentage of total journey time
  - Value type: Number (percentage)
  - Example: "Delay (Percentage) > 5" means the delay is more than 5% of journey time

- **Platform Changed**: Whether the platform has changed from the scheduled platform
  - Value type: Boolean (true/false)
  - Example: "Platform Changed = true" means the platform has been changed

- **Is Cancelled**: Whether the train has been cancelled
  - Value type: Boolean (true/false)
  - Example: "Is Cancelled = true" means the train is cancelled

- **Has Realtime Update**: Whether the train has received a realtime update
  - Value type: Boolean (true/false)
  - Example: "Has Realtime Update = true" means realtime data is available

##### Route/Service Conditions

- **Route ID**: The route identifier
  - Value type: Text
  - Example: "Route ID = 9000" means the rule applies to route 9000

- **Direction**: The direction of travel (0 or 1)
  - Value type: Text/Number
  - Example: "Direction = 0" means the rule applies to direction 0

- **Destination Station**: The final destination station name or ID
  - Value type: Text
  - Example: "Destination Station = St-Pancras-International"

- **Wheelchair Accessible**: Whether the train is wheelchair accessible
  - Value type: Boolean (true/false)
  - Example: "Wheelchair Accessible = true"

##### General Conditions

- **Current Status**: The current status of the train
  - Value type: Select (from available statuses)
  - Example: "Current Status = Delayed" means the train is currently delayed

- **Train Number**: The train number
  - Value type: Text
  - Example: "Train Number = 9115" means the rule applies to train 9115

#### Operators

Operators define how to compare the condition value:

- **>** (Greater Than): The condition value is greater than the specified value
- **>=** (Greater Than or Equal To): The condition value is greater than or equal to the specified value
- **<** (Less Than): The condition value is less than the specified value
- **<=** (Less Than or Equal To): The condition value is less than or equal to the specified value
- **=** (Equals): The condition value equals the specified value (for time-based conditions, uses a 1-minute tolerance window)
- **!=** (Not Equal To): The condition value does not equal the specified value

#### Value Types

The value field type changes based on the selected condition type:

- **Number**: For time-based conditions (minutes) and delay conditions
- **Text**: For train numbers, route IDs, destination stations, and platform values
- **Select**: For status conditions (dropdown of available statuses) and day of week
- **Boolean**: For true/false conditions like platform changed, is cancelled, etc.
- **Time Range**: For time range conditions (start time - end time format)

### Adding Conditions

Rules can have multiple conditions combined using logical operators. This allows you to create complex rules that trigger only when all (or any) conditions are met.

#### Adding Multiple Conditions

1. Click the "Add Condition" button to add additional conditions
2. Each condition after the first will have a logical operator selector
3. Choose how conditions should be combined:
   - **AND**: All conditions must be true for the rule to trigger
   - **OR**: Any condition being true will trigger the rule

#### Condition Evaluation

Conditions are evaluated in the order they appear:

- The first condition is always evaluated
- Subsequent conditions are combined using their logical operator (AND/OR)
- For AND: `condition1 AND condition2 AND condition3` - all must be true
- For OR: `condition1 OR condition2 OR condition3` - any one can be true
- Mixed: `condition1 AND (condition2 OR condition3)` - condition1 must be true AND at least one of condition2 or condition3

#### Examples

**Example 1: Simple Rule**
- Condition: Time Until Departure < 15
- Action: Set Status to "Boarding"
- Result: When a train is 15 minutes or less from departure, set status to "Boarding"

**Example 2: Multiple Conditions with AND**
- Condition 1: Time Until Departure < 30
- Condition 2 (AND): Delay (Minutes) > 10
- Action: Set Status to "Delayed"
- Result: When a train is less than 30 minutes from departure AND delayed by more than 10 minutes, set status to "Delayed"

**Example 3: Multiple Conditions with OR**
- Condition 1: Is Cancelled = true
- Condition 2 (OR): Current Status = Cancelled
- Action: Make Announcement
- Result: When a train is cancelled OR already has cancelled status, make an announcement

**Example 4: Complex Rule**
- Condition 1: Time Until Departure < 60
- Condition 2 (AND): Day of Week = Monday, Tuesday, Wednesday, Thursday, Friday
- Condition 3 (AND): Is Peak Time = true
- Action: Make Announcement
- Result: During peak hours on weekdays, when a train is less than 60 minutes from departure, make an announcement

### Action Types

When a rule's conditions are met, one of three actions can be triggered:

#### 1. Set Status

Changes the train's status to a selected status from your configured statuses.

**Configuration:**
- **Status**: Select from the dropdown of available train statuses
- The status will be applied to all stops configured for the train in active groups

**Example:**
- Condition: Time Until Departure < 10
- Action: Set Status to "Boarding"
- Result: When a train is 10 minutes or less from departure, automatically change status to "Boarding"

#### 2. Make Announcement

Triggers an automated announcement using AviaVox templates.

**Configuration:**
- **Template**: Select an AviaVox announcement template
- **Zone Strategy**: Choose how zones are selected:
  - **Group Zones**: Uses all zones associated with the group viewing the train
  - **Specific Zone**: Select a specific zone for the announcement
- **Template Variables**: Configure variables required by the template:
  - **Manual Variables**: Enter static values that will be used in the announcement
  - **Dynamic Variables**: Variables that are automatically populated from train data at runtime
- **Zone**: If using "Specific Zone" strategy, select the zone

**Example:**
- Condition: Time Until Departure < 5 AND Platform Changed = true
- Action: Make Announcement
  - Template: "Platform Change"
  - Zone Strategy: Group Zones
  - Variables: Platform number (dynamic)
- Result: When a train is 5 minutes from departure and platform has changed, announce the platform change to all group zones

#### 3. Update Platform

Updates the platform information for the train.

**Configuration:**
- **Platform**: Enter the platform number or code
- The platform will be updated for all stops configured for the train

**Example:**
- Condition: Specific Platform = 15
- Action: Update Platform to "15A"
- Result: When a train is scheduled for platform 15, update it to platform 15A

#### Rule Priority and Execution Mode

- **Priority**: Rules with higher priority values are evaluated first (default: 0)
- **Execution Mode**: 
  - **First Match**: Stop evaluating rules after the first matching rule is found
  - **All Matches**: Evaluate all rules and apply all matching actions

### Existing Rules

The "Existing Rules" section displays all created rules in a paginated table.

#### Viewing Rules

The table shows:
- **Conditions**: All conditions for the rule with their operators and values
- **Action**: The action type and its configuration
- **Priority**: The rule's priority value
- **Status**: Whether the rule is active or inactive
- **Created**: When the rule was created

#### Editing Rules

1. Click the "Edit" button on any rule
2. The rule's configuration will be loaded into the form
3. Modify conditions, action, priority, or execution mode as needed
4. Click "Save" to update the rule
5. Click "Cancel" to discard changes

#### Toggling Rule Status

- Click the toggle button to activate or deactivate a rule
- **Active** rules are evaluated and can trigger actions
- **Inactive** rules are ignored during rule evaluation
- This allows you to temporarily disable rules without deleting them

#### Deleting Rules

1. Click the "Delete" button on a rule
2. Confirm the deletion
3. The rule and all its conditions will be permanently removed

#### Rule Execution

Rules are automatically evaluated every 3 minutes by the `trains:process-rules` command. When conditions are met:

1. The rule's action is applied
2. A record is created in `train_rule_executions` to prevent duplicate executions
3. Rules are evaluated per stop for trains configured in active groups
4. Only active rules are evaluated
5. Rules are evaluated in priority order (highest first)

## Automated Announcements

Automated Announcements allow you to schedule announcements to be made automatically at regular intervals during specific time periods. Unlike Train Status Rules which are triggered by train conditions, Automated Announcements are time-based and repeat on a schedule.

### Creating Automated Announcement Rules

To create an automated announcement rule, fill in the following information:

#### Basic Configuration

- **Rule Name**: A descriptive name for the announcement rule (e.g., "Terminal Safety Announcement")
- **Start Time**: The time when announcements should begin (24-hour format, e.g., 08:00)
- **End Time**: The time when announcements should stop (must be after start time, e.g., 20:00)
- **Interval (minutes)**: How often the announcement should be made (1-1440 minutes, e.g., 40 means every 40 minutes)

#### Schedule Configuration

- **Active Days**: Select which days of the week the announcement should be active
  - Check the boxes for Monday through Sunday
  - At least one day must be selected
  - Example: Check Monday-Friday for weekday-only announcements

#### Announcement Details

- **Announcement Template**: Select an AviaVox template to use for the announcement
  - Templates define the announcement structure and required variables
  - Only templates configured in the system are available

- **Zone**: Select the zone where the announcement should be made
  - Zones are configured in the Admin Settings
  - The announcement will be sent to the selected zone

#### Template Variables

After selecting a template, you'll see fields for any variables required by that template:

- Fill in the values for each variable
- Variables marked as "zone" are automatically populated
- Other variables must be entered manually
- Example: A "safety_reminder" template might require a "message" variable

#### Active Status

- **Rule is active**: Check this box to enable the rule
- Unchecked rules will not trigger announcements
- You can toggle this without deleting the rule

### Existing Automated Announcement Rules

The table displays all automated announcement rules with:

- **Rule Name**: The name you assigned
- **Schedule**: Shows the time range, interval, and active days
- **Template**: The AviaVox template being used
- **Zone**: The zone where announcements are made
- **Last Triggered**: When the announcement was last triggered (or "Never")
- **Status**: Active or Inactive indicator
- **Actions**: 
  - **Enable/Disable**: Toggle the rule's active status
  - **Delete**: Permanently remove the rule

### How Automated Announcements Work

1. The `announcements:process-automated` command runs every minute
2. It checks all active automated announcement rules
3. For each rule:
   - Verifies current time is within the start/end time range
   - Checks if current day is in the active days list
   - Calculates if the interval has passed since the last trigger
   - If all conditions are met, triggers the announcement
4. The announcement is sent to the AviaVox system using the configured template and variables
5. The `last_triggered_at` timestamp is updated to prevent duplicate triggers within the interval

### Example Use Cases

**Safety Announcement Every 30 Minutes**
- Name: "Terminal Safety Reminder"
- Start Time: 06:00
- End Time: 22:00
- Interval: 30 minutes
- Days: All days
- Template: "Safety Reminder"
- Zone: "Terminal"
- Result: Safety announcement plays every 30 minutes from 6 AM to 10 PM daily

**Weekend Welcome Message**
- Name: "Weekend Welcome"
- Start Time: 09:00
- End Time: 18:00
- Interval: 60 minutes
- Days: Saturday, Sunday
- Template: "Welcome Message"
- Zone: "Lounge"
- Result: Welcome message plays every hour on weekends from 9 AM to 6 PM

**Peak Hour Boarding Reminder**
- Name: "Peak Hour Boarding"
- Start Time: 07:00
- End Time: 09:00
- Interval: 15 minutes
- Days: Monday, Tuesday, Wednesday, Thursday, Friday
- Template: "Boarding Reminder"
- Zone: "Platform"
- Result: Boarding reminder every 15 minutes during weekday morning peak hours

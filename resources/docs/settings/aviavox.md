# Aviavox Settings
Configure and manage all settings related to the Aviavox connection and audio tempalte management. 

## Configuration
In this section, you are able to configure and manage the connection settings of the Aviavox server to enable communication from the SignaRail server to the Aviavox controller. 


### Aviavox Connection Details

For the connection to be set and configured between Aviavox and SignaRail you will need to supply the following information in this section which can be obtained from Aviavox or your IT Administrator:

- **IP Address**
- **Port**
- **Username**
- **Password**

Once the information has been supplied, you will need to click Save Settings to save this connection information to the SignaRail database.


### Test Connection
Once you have configured your Aviavox connection details, you can confirm if the binding between Aviavox and SignaRail is working by selecting the Test Connection button. 

If the test fails, please consult the Logs in the Settings Menu or contact your IT Administrator. 


## Zones
When making use of Avaivox to make Audio announcements in the terminals and platforms, you would need to speceify the Zones in which the announcement will be made

### What is a Zone?
A Zone is a set location in a termnial or platform to which the Aviavox controller will treat as an isolated are to make the audio announcements.

You can bind multiple zones to an Announcement depending on the objective of the announcement

### Addind Zones
To add a Zone, input the Zone Value supplied to you from Aviavox and select Add Zone.

THis will add the Zone to the SignaRail platform to be used in the Announcement Wizard or with Automated Rules and Triggers that will make announcements automatically. 

### Deleting Zones
To Delete a Zone, you can select an announcment in the Existing Zones table and select the Delete action on the right side of the table.



## Announcement History
Each announcement that is made using the SignaRail platform will display in the Announcement Histry table.

Users are able to see the following infomration in the table:
- **Type**: The type of announcement that was made. For example Manual or Automatic announcement. 
- **Message**: The Friendly name of the announcement that was made.
- **Time**: The date and time that the announcement was triggered.
- **Author**: The Author of the announcement. System if automatic. User if it was manually troggered. 
- **Zone**: The zones that played the audio announcement. 
- **Status**: The status of the announcement such as in progress or completed. 


## Announcement Templates
An Announcement template is the structured message that will be sent to the Aviavox controller to execute and audio message.

### Adding Announcement Templates
To add an announcement template, you would need to supply the relevant information that structures the meesage that will be sent to the Aviavix controller. 
THis information will include the following:
- **Friendly Name**
- **Template Name**
- **XML Template**


Below is and example of a message that would be sent to the Aviavox controller:

'code'<AIP>
<MessageID>AnnouncementTriggerRequest</MessageID>
<MessageData>
<AnnouncementData>
<Item ID="MessageName" Value

"CHECKING_WELCOME_CLOSED"/>

<Item ID="TrainNumber" Value="1234"/>
<Item ID="Route" Value="GBR_LON"/>
<Item ID="ScheduledTime" Value="2024-08-22T07:45:00Z"/>

<Item ID="Zones" Value="Terminal"/>

</AnnouncementData>
</MessageData>
</AIP>'code'


## Existing Announcements


## Aviavox Responses



## Custome Announcements
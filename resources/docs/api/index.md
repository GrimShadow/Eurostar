# API Documentation

Access and integrate with the **Eurostar Announcement System** using our RESTful API.
This documentation provides everything you need to authenticate, interact with resources, and automate workflows through the API.

---

## Overview

The Eurostar API provides programmatic access to system functionality, allowing developers to:

* Create and manage announcements
* Retrieve real-time train and route data
* Configure system settings
* Monitor operational status
* Integrate with external and internal systems

All API endpoints return **JSON responses** and require authentication via API tokens.

---

## Authentication

### API Tokens

All requests to the Eurostar API must be authenticated using a valid **API token**.

To create a token:

1. Navigate to **Settings → API Tokens**
2. Click **Create New Token**
3. Enter a name and select the desired permissions
4. Copy the generated token
5. Include it in your API request headers

### Request Headers

Every API call must include the following headers:

```http
Authorization: Bearer YOUR_API_TOKEN
Content-Type: application/json
Accept: application/json
```

### Token Permissions

Each token can be scoped with specific access levels:

| Permission        | Description                                 |
| ----------------- | ------------------------------------------- |
| **Full Access**   | Grants access to all API endpoints          |
| **Read Only**     | Restricts access to `GET` requests          |
| **Announcements** | Access to announcement endpoints only       |
| **Data**          | Access to train, station, and route data    |
| **Settings**      | Access to configuration and system settings |

---

## Base URL

All endpoints are relative to your organization’s domain:

```
https://your-domain.com/api
```

---

## Rate Limiting

To ensure fair usage, rate limits apply per token:

| Limit Type | Requests | Time Window |
| ---------- | -------- | ----------- |
| Per Minute | 100      | 1 minute    |
| Per Hour   | 1,000    | 1 hour      |
| Per Day    | 10,000   | 24 hours    |

Each API response includes rate-limit headers:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

---

## Response Format

### Success Response

```json
{
  "success": true,
  "data": {
    // Response data
  },
  "message": "Operation completed successfully"
}
```

### Error Response

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "field": "The field is required"
    }
  }
}
```

### Pagination

All list endpoints support pagination:

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "current_page": 1,
    "per_page": 15,
    "total": 150,
    "last_page": 10,
    "from": 1,
    "to": 15
  }
}
```

---

## Endpoints

### Announcements

#### List Announcements

```http
GET /api/announcements
```

**Query Parameters:**

| Parameter  | Type    | Description                                                   |
| ---------- | ------- | ------------------------------------------------------------- |
| `page`     | integer | Page number                                                   |
| `per_page` | integer | Items per page (max 100)                                      |
| `status`   | string  | Filter by status (`active`, `pending`, `completed`, `failed`) |
| `priority` | string  | Filter by priority (`high`, `medium`, `low`)                  |
| `group_id` | integer | Filter by group ID                                            |
| `route_id` | integer | Filter by route ID                                            |

**Example Response:**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Service Update",
      "message": "Service 1234 is delayed by 15 minutes",
      "status": "active",
      "priority": "high",
      "created_at": "2024-01-15T10:30:00Z",
      "updated_at": "2024-01-15T10:30:00Z"
    }
  ]
}
```

#### Create Announcement

```http
POST /api/announcements
```

**Request Body:**

```json
{
  "title": "Service Update",
  "message": "Service 1234 is delayed by 15 minutes",
  "priority": "high",
  "group_ids": [1, 2],
  "route_ids": [123, 124],
  "scheduled_at": "2024-01-15T11:00:00Z"
}
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Service Update",
    "message": "Service 1234 is delayed by 15 minutes",
    "status": "pending",
    "priority": "high",
    "created_at": "2024-01-15T10:30:00Z"
  }
}
```

#### Get / Update / Delete Announcement

```http
GET    /api/announcements/{id}
PUT    /api/announcements/{id}
DELETE /api/announcements/{id}
```

---

### Train Data

#### List Trains

```http
GET /api/trains
```

**Query Parameters:**

| Parameter    | Type    | Description                                          |
| ------------ | ------- | ---------------------------------------------------- |
| `route_id`   | integer | Filter by route                                      |
| `station_id` | integer | Filter by station                                    |
| `status`     | string  | Filter by status (`on_time`, `delayed`, `cancelled`) |
| `date`       | date    | Filter by date (YYYY-MM-DD)                          |

#### Get Train Details

```http
GET /api/trains/{id}
```

#### Get Station Information

```http
GET /api/stations
```

---

### Groups

```http
GET    /api/groups
POST   /api/groups
PUT    /api/groups/{id}
DELETE /api/groups/{id}
```

**Create Group Example:**

```json
{
  "name": "London Routes",
  "description": "Routes serving London stations",
  "route_ids": [1, 2, 3]
}
```

---

### Rules

```http
GET    /api/rules
POST   /api/rules
PUT    /api/rules/{id}
DELETE /api/rules/{id}
```

**Create Rule Example:**

```json
{
  "name": "Delay Notification",
  "description": "Notify when train is delayed",
  "trigger_event": "delay",
  "trigger_conditions": {
    "delay_minutes": 5
  },
  "message_template": "Service {route} is delayed by {delay} minutes",
  "target_groups": [1, 2],
  "active": true
}
```

---

### System Status

#### Get System Status

```http
GET /api/status
```

**Example Response:**

```json
{
  "success": true,
  "data": {
    "system_status": "operational",
    "gtfs_status": "connected",
    "aviavox_status": "connected",
    "last_gtfs_update": "2024-01-15T10:25:00Z",
    "active_announcements": 5,
    "pending_announcements": 2
  }
}
```

#### Get System Health

```http
GET /api/health
```

---

## Error Handling

### HTTP Status Codes

| Code | Meaning                                      |
| ---- | -------------------------------------------- |
| 200  | OK — Request successful                      |
| 201  | Created — Resource created                   |
| 400  | Bad Request — Invalid data                   |
| 401  | Unauthorized — Missing or invalid token      |
| 403  | Forbidden — Insufficient permissions         |
| 404  | Not Found — Resource not found               |
| 422  | Validation Error — Invalid input             |
| 429  | Too Many Requests — Rate limit exceeded      |
| 500  | Internal Server Error — General server error |

### Application Error Codes

| Code                   | Description                     |
| ---------------------- | ------------------------------- |
| `VALIDATION_ERROR`     | Request data validation failed  |
| `AUTHENTICATION_ERROR` | Invalid or missing credentials  |
| `AUTHORIZATION_ERROR`  | Insufficient permissions        |
| `RESOURCE_NOT_FOUND`   | Requested resource not found    |
| `RATE_LIMIT_EXCEEDED`  | Too many requests               |
| `SERVICE_UNAVAILABLE`  | Service temporarily unavailable |
| `INTERNAL_ERROR`       | General internal server error   |

---

## SDKs and Libraries

### PHP

```php
use EurostarAnnouncements\Client;

$client = new Client('YOUR_API_TOKEN', 'https://your-domain.com');

$announcement = $client->announcements()->create([
    'title' => 'Service Update',
    'message' => 'Service 1234 is delayed by 15 minutes',
    'priority' => 'high'
]);
```

### JavaScript

```javascript
import { EurostarClient } from '@eurostar/announcements-api';

const client = new EurostarClient('YOUR_API_TOKEN', 'https://your-domain.com');

const announcement = await client.announcements.create({
    title: 'Service Update',
    message: 'Service 1234 is delayed by 15 minutes',
    priority: 'high'
});
```

### Python

```python
from eurostar_announcements import Client

client = Client('YOUR_API_TOKEN', 'https://your-domain.com')

announcement = client.announcements.create({
    'title': 'Service Update',
    'message': 'Service 1234 is delayed by 15 minutes',
    'priority': 'high'
})
```

---

## Webhooks

### Setting Up Webhooks

1. Go to **Settings → Webhooks**
2. Click **Create Webhook**
3. Enter your **webhook URL** and select event types
4. Configure authentication (optional)
5. Click **Test Delivery** to verify connectivity

### Supported Events

* `announcement.created`
* `announcement.updated`
* `announcement.deleted`
* `train.status_changed`
* `rule.triggered`
* `system.status_changed`

**Webhook Payload Example:**

```json
{
  "event": "announcement.created",
  "timestamp": "2024-01-15T10:30:00Z",
  "data": {
    "id": 1,
    "title": "Service Update",
    "message": "Service 1234 is delayed by 15 minutes",
    "priority": "high"
  }
}
```

---

## Testing

### Tools

You can test the API using any of the following tools:

* **Postman** (recommended)
* **cURL**
* **Insomnia**
* **SDKs** (PHP, JS, Python)

### Sandbox Environment

Use the sandbox API for testing and integration validation:

```
https://sandbox.your-domain.com/api
```

### Interactive Documentation

Explore and test endpoints in real time:

```
https://your-domain.com/api/docs
```

---

## Support

### Getting Help

If you encounter issues:

* Review this documentation
* Check detailed error messages
* Contact the **Support Team**
* Submit a ticket through the helpdesk portal

### Status Page

Monitor system uptime and incident reports at:

```
https://status.your-domain.com
```

---

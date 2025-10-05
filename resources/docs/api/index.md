# API Documentation

Access and integrate with the Eurostar Announcement System through our comprehensive REST API.

## Overview

The API provides programmatic access to all system features, allowing you to:

- Create and manage announcements
- Retrieve real-time train data
- Configure system settings
- Monitor system status
- Integrate with external systems

## Authentication

### API Tokens

All API requests require authentication using API tokens:

1. Navigate to **Settings > API Tokens**
2. Click **Create New Token**
3. Enter token name and select permissions
4. Copy the generated token
5. Include token in API requests

### Request Headers

Include the following headers in all API requests:

```http
Authorization: Bearer YOUR_API_TOKEN
Content-Type: application/json
Accept: application/json
```

### Token Permissions

API tokens can have different permission levels:

- **Full Access**: All API endpoints
- **Read Only**: GET requests only
- **Announcements**: Announcement-related endpoints
- **Data**: Data retrieval endpoints
- **Settings**: Configuration endpoints

## Base URL

All API endpoints are relative to:

```
https://your-domain.com/api
```

## Rate Limiting

API requests are rate limited to prevent abuse:

- **100 requests per minute** per token
- **1000 requests per hour** per token
- **10,000 requests per day** per token

Rate limit headers are included in responses:

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

## Response Format

All API responses follow a consistent format:

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

List endpoints support pagination:

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

## Endpoints

### Announcements

#### List Announcements

```http
GET /api/announcements
```

**Query Parameters:**
- `page` (integer): Page number
- `per_page` (integer): Items per page (max 100)
- `status` (string): Filter by status (active, pending, completed, failed)
- `priority` (string): Filter by priority (high, medium, low)
- `group_id` (integer): Filter by group ID
- `route_id` (integer): Filter by route ID

**Response:**
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

**Response:**
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

#### Get Announcement

```http
GET /api/announcements/{id}
```

#### Update Announcement

```http
PUT /api/announcements/{id}
```

#### Delete Announcement

```http
DELETE /api/announcements/{id}
```

### Train Data

#### List Trains

```http
GET /api/trains
```

**Query Parameters:**
- `route_id` (integer): Filter by route ID
- `station_id` (integer): Filter by station ID
- `status` (string): Filter by status (on_time, delayed, cancelled)
- `date` (date): Filter by date (YYYY-MM-DD)

#### Get Train Details

```http
GET /api/trains/{id}
```

#### Get Station Information

```http
GET /api/stations
```

**Query Parameters:**
- `search` (string): Search by station name
- `route_id` (integer): Filter by route ID

### Groups

#### List Groups

```http
GET /api/groups
```

#### Create Group

```http
POST /api/groups
```

**Request Body:**
```json
{
    "name": "London Routes",
    "description": "Routes serving London stations",
    "route_ids": [1, 2, 3]
}
```

#### Update Group

```http
PUT /api/groups/{id}
```

#### Delete Group

```http
DELETE /api/groups/{id}
```

### Rules

#### List Rules

```http
GET /api/rules
```

#### Create Rule

```http
POST /api/rules
```

**Request Body:**
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

#### Update Rule

```http
PUT /api/rules/{id}
```

#### Delete Rule

```http
DELETE /api/rules/{id}
```

### System Status

#### Get System Status

```http
GET /api/status
```

**Response:**
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

## Error Codes

### HTTP Status Codes

- `200 OK`: Request successful
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Insufficient permissions
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation error
- `429 Too Many Requests`: Rate limit exceeded
- `500 Internal Server Error`: Server error

### Application Error Codes

- `VALIDATION_ERROR`: Request data validation failed
- `AUTHENTICATION_ERROR`: Invalid or missing authentication
- `AUTHORIZATION_ERROR`: Insufficient permissions
- `RESOURCE_NOT_FOUND`: Requested resource doesn't exist
- `RATE_LIMIT_EXCEEDED`: Too many requests
- `SERVICE_UNAVAILABLE`: Service temporarily unavailable
- `INTERNAL_ERROR`: Internal server error

## SDKs and Libraries

### PHP

```php
use EurostarAnnouncements\Client;

$client = new Client('YOUR_API_TOKEN', 'https://your-domain.com');

// Create announcement
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

// Create announcement
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

# Create announcement
announcement = client.announcements.create({
    'title': 'Service Update',
    'message': 'Service 1234 is delayed by 15 minutes',
    'priority': 'high'
})
```

## Webhooks

### Setting Up Webhooks

Configure webhooks to receive real-time notifications:

1. Navigate to **Settings > Webhooks**
2. Click **Create Webhook**
3. Enter webhook URL and select events
4. Configure authentication if needed
5. Test webhook delivery

### Webhook Events

- `announcement.created`: New announcement created
- `announcement.updated`: Announcement updated
- `announcement.deleted`: Announcement deleted
- `train.status_changed`: Train status updated
- `rule.triggered`: Automated rule triggered
- `system.status_changed`: System status updated

### Webhook Payload

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

## Testing

### API Testing Tools

- **Postman**: Import our API collection
- **curl**: Command-line testing
- **Insomnia**: API testing client
- **Custom Scripts**: Use our SDKs

### Test Environment

Use our sandbox environment for testing:

```
https://sandbox.your-domain.com/api
```

### API Documentation

Interactive API documentation available at:

```
https://your-domain.com/api/docs
```

## Support

### Getting Help

- Check API documentation
- Review error messages
- Contact support team
- Submit support ticket

### Status Page

Monitor API status at:

```
https://status.your-domain.com
```

# API Endpoints Documentation

Complete reference for all API endpoints in the Eurostar Announcement System, including request/response formats and Postman examples.

---

## Table of Contents

1. [Authentication](#authentication)
2. [User Management](#user-management)
3. [Train Data](#train-data)
4. [GTFS Updates](#gtfs-updates)
5. [GTFS Status Management](#gtfs-status-management)
6. [Announcements](#announcements)
7. [Broker (Pending Announcements)](#broker-pending-announcements)
8. [Aviavox](#aviavox)
9. [Heartbeat](#heartbeat)

---

## Authentication

### Generate API Token

Obtain an API token by authenticating with your email and password.

**Endpoint:** `POST /api/sanctum/token`

**Authentication:** Not required

**Request Headers:**
```http
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "email": "user@example.com",
  "password": "your-password",
  "device_name": "My API Client"
}
```

**Validation Rules:**
- `email`: required, must be valid email
- `password`: required
- `device_name`: required, string

**Success Response (200):**
```json
{
  "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
}
```

**Error Response (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/sanctum/token \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "user@example.com",
    "password": "your-password",
    "device_name": "My API Client"
  }'
```

**Postman Collection:**
```json
{
  "name": "Generate API Token",
  "request": {
    "method": "POST",
    "header": [
      {
        "key": "Content-Type",
        "value": "application/json"
      },
      {
        "key": "Accept",
        "value": "application/json"
      }
    ],
    "body": {
      "mode": "raw",
      "raw": "{\n  \"email\": \"user@example.com\",\n  \"password\": \"your-password\",\n  \"device_name\": \"My API Client\"\n}"
    },
    "url": {
      "raw": "{{base_url}}/api/sanctum/token",
      "host": ["{{base_url}}"],
      "path": ["api", "sanctum", "token"]
    }
  }
}
```

---

## User Management

All user management endpoints require authentication via Bearer token.

### List All Users

**Endpoint:** `GET /api/users`

**Authentication:** Required (Bearer token)

**Request Headers:**
```http
Authorization: Bearer YOUR_API_TOKEN
Accept: application/json
```

**Success Response (200):**
```json
{
  "users": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "administrator",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  ]
}
```

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/users \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

**Postman Collection:**
```json
{
  "name": "List All Users",
  "request": {
    "method": "GET",
    "header": [
      {
        "key": "Authorization",
        "value": "Bearer {{api_token}}"
      },
      {
        "key": "Accept",
        "value": "application/json"
      }
    ],
    "url": {
      "raw": "{{base_url}}/api/users",
      "host": ["{{base_url}}"],
      "path": ["api", "users"]
    }
  }
}
```

### Create User

**Endpoint:** `POST /api/users`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "name": "Jane Doe",
  "email": "jane@example.com",
  "password": "secure-password-123",
  "role": "user"
}
```

**Validation Rules:**
- `name`: required, minimum 2 characters
- `email`: required, valid email, unique
- `password`: required, minimum 8 characters
- `role`: required, must be `user` or `administrator`

**Success Response (201):**
```json
{
  "message": "User created successfully",
  "user": {
    "id": 2,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "role": "user",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Error Response (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password must be at least 8 characters."]
  }
}
```

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/users \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "password": "secure-password-123",
    "role": "user"
  }'
```

### Update User

**Endpoint:** `PUT /api/users/{id}`

**Authentication:** Required (Bearer token)

**URL Parameters:**
- `id`: User ID (integer)

**Request Body:**
```json
{
  "name": "Jane Smith",
  "email": "jane.smith@example.com",
  "password": "new-password-123",
  "role": "administrator"
}
```

**Validation Rules:**
- `name`: required, minimum 2 characters
- `email`: required, valid email, unique (excluding current user)
- `password`: optional, minimum 8 characters if provided
- `role`: required, must be `user` or `administrator`

**Success Response (200):**
```json
{
  "message": "User updated successfully",
  "user": {
    "id": 2,
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "role": "administrator",
    "updated_at": "2024-01-15T11:00:00.000000Z"
  }
}
```

**cURL Example:**
```bash
curl -X PUT https://your-domain.com/api/users/2 \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Jane Smith",
    "email": "jane.smith@example.com",
    "password": "new-password-123",
    "role": "administrator"
  }'
```

### Delete User

**Endpoint:** `DELETE /api/users/{id}`

**Authentication:** Required (Bearer token)

**URL Parameters:**
- `id`: User ID (integer)

**Success Response (200):**
```json
{
  "message": "User deleted successfully"
}
```

**Error Response (404):**
```json
{
  "message": "No query results for model [App\\Models\\User] {id}"
}
```

**cURL Example:**
```bash
curl -X DELETE https://your-domain.com/api/users/2 \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

---

## Train Data

### Get Today's Trains

Retrieve all trains scheduled for today with their stops, statuses, platforms, and check-in information.

**Endpoint:** `GET /api/trains/today`

**Authentication:** Required (Bearer token)

**Request Headers:**
```http
Authorization: Bearer YOUR_API_TOKEN
Accept: application/json
```

**Success Response (200):**
```json
{
  "data": {
    "stops": [
      {
        "number": "9133",
        "trip_id": "9133-1124",
        "departure": "10:30",
        "route_name": "Amsterdam Centraal to Brussels Midi",
        "train_id": "BRU",
        "status": "On-time",
        "status_color": "34,197,94",
        "status_color_hex": "#22C55E",
        "departure_platform": "15",
        "arrival_platform": "1",
        "stops": [
          {
            "stop_id": "amsterdam_centraal_15",
            "stop_name": "Amsterdam Centraal",
            "arrival_time": "10:30",
            "departure_time": "10:30",
            "new_departure_time": null,
            "new_depart_min": null,
            "stop_sequence": 1,
            "status": "on-time",
            "status_color": "34,197,94",
            "status_color_hex": "#22C55E",
            "status_updated_at": "2024-01-15 10:25:00",
            "departure_platform": "15",
            "arrival_platform": "15",
            "check_in_time": 90,
            "check_in_starts": "09:00",
            "minutes_until_check_in_starts": 0
          }
        ]
      }
    ]
  },
  "meta": {
    "date": "2024-01-15",
    "count": 45,
    "time_window": {
      "start": "00:00:00",
      "end": "23:59:59"
    }
  }
}
```

**Response Notes:**
- Response is cached for 1 minute
- Includes only trains from active selected routes
- Check-in times are calculated based on global and train-specific settings
- Status colors are provided in both RGB and hex formats

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/trains/today \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

**Postman Collection:**
```json
{
  "name": "Get Today's Trains",
  "request": {
    "method": "GET",
    "header": [
      {
        "key": "Authorization",
        "value": "Bearer {{api_token}}"
      },
      {
        "key": "Accept",
        "value": "application/json"
      }
    ],
    "url": {
      "raw": "{{base_url}}/api/trains/today",
      "host": ["{{base_url}}"],
      "path": ["api", "trains", "today"]
    }
  }
}
```

---

## GTFS Updates

### Store GTFS Realtime Update

Store a GTFS realtime feed update in the system.

**Endpoint:** `POST /api/gtfs/update`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "header": {
    "gtfs_realtime_version": "2.0",
    "incrementality": 0,
    "timestamp": 1705315200
  },
  "entity": [
    {
      "id": "trip_update_1",
      "trip_update": {
        "trip": {
          "trip_id": "9133-1124",
          "route_id": "IC",
          "direction_id": 0
        },
        "stop_time_update": [
          {
            "stop_sequence": 1,
            "stop_id": "amsterdam_centraal_15",
            "arrival": {
              "time": 1705315200
            },
            "departure": {
              "time": 1705315260
            }
          }
        ]
      }
    }
  ]
}
```

**Validation Rules:**
- `header`: required, object
  - `header.gtfs_realtime_version`: required, string
  - `header.incrementality`: required, integer
  - `header.timestamp`: required, integer (Unix timestamp)
- `entity`: required, array
  - `entity.*.id`: required, string
  - `entity.*.trip_update`: required, object
  - `entity.*.trip_update.stop_time_update`: required, array

**Success Response (201):**
```json
{
  "message": "GTFS update stored successfully",
  "data": {
    "id": 1,
    "gtfs_realtime_version": "2.0",
    "incrementality": 0,
    "timestamp": "2024-01-15T10:30:00.000000Z",
    "entity_data": [...],
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/gtfs/update \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "header": {
      "gtfs_realtime_version": "2.0",
      "incrementality": 0,
      "timestamp": 1705315200
    },
    "entity": [
      {
        "id": "trip_update_1",
        "trip_update": {
          "trip": {
            "trip_id": "9133-1124",
            "route_id": "IC"
          },
          "stop_time_update": [
            {
              "stop_sequence": 1,
              "stop_id": "amsterdam_centraal_15"
            }
          ]
        }
      }
    ]
  }'
```

### List GTFS Updates

**Endpoint:** `GET /api/gtfs/updates`

**Authentication:** Required (Bearer token)

**Query Parameters:**
- `page`: Page number (default: 1)

**Success Response (200):**
```json
{
  "current_page": 1,
  "data": [
    {
      "id": 1,
      "gtfs_realtime_version": "2.0",
      "incrementality": 0,
      "timestamp": "2024-01-15T10:30:00.000000Z",
      "entity_data": [...],
      "created_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "first_page_url": "http://your-domain.com/api/gtfs/updates?page=1",
  "from": 1,
  "last_page": 10,
  "last_page_url": "http://your-domain.com/api/gtfs/updates?page=10",
  "links": [...],
  "next_page_url": "http://your-domain.com/api/gtfs/updates?page=2",
  "path": "http://your-domain.com/api/gtfs/updates",
  "per_page": 15,
  "prev_page_url": null,
  "to": 15,
  "total": 150
}
```

**cURL Example:**
```bash
curl -X GET "https://your-domain.com/api/gtfs/updates?page=1" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

### Get Specific GTFS Update

**Endpoint:** `GET /api/gtfs/updates/{id}`

**Authentication:** Required (Bearer token)

**URL Parameters:**
- `id`: GTFS Update ID (integer)

**Success Response (200):**
```json
{
  "id": 1,
  "gtfs_realtime_version": "2.0",
  "incrementality": 0,
  "timestamp": "2024-01-15T10:30:00.000000Z",
  "entity_data": [...],
  "created_at": "2024-01-15T10:30:00.000000Z",
  "updated_at": "2024-01-15T10:30:00.000000Z"
}
```

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/gtfs/updates/1 \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

---

## GTFS Status Management

### Update Stop Status

Update the status of a specific stop for a train trip.

**Endpoint:** `POST /api/gtfs/stop-status`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "trip_id": "9133-1124",
  "stop_id": "amsterdam_centraal_15",
  "status": "delayed",
  "actual_arrival_time": "10:35:00",
  "actual_departure_time": "10:40:00",
  "platform_code": "15",
  "departure_platform": "15",
  "arrival_platform": "15"
}
```

**Validation Rules:**
- `trip_id`: required, string
- `stop_id`: required, string
- `status`: required, must be one of: `on-time`, `delayed`, `cancelled`, `completed`
- `actual_arrival_time`: optional, format: `H:i:s`
- `actual_departure_time`: optional, format: `H:i:s`
- `platform_code`: optional, string
- `departure_platform`: optional, string
- `arrival_platform`: optional, string

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "trip_id": "9133-1124",
    "stop_id": "amsterdam_centraal_15",
    "status": "delayed",
    "status_color": "234,179,8",
    "status_color_hex": "#EAB308",
    "actual_arrival_time": "10:35:00",
    "actual_departure_time": "10:40:00",
    "departure_platform": "15",
    "arrival_platform": "15",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Error Response (500):**
```json
{
  "success": false,
  "error": "Failed to update stop status"
}
```

**Note:** This endpoint triggers a `TrainStatusUpdated` event that broadcasts to connected clients.

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/gtfs/stop-status \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "trip_id": "9133-1124",
    "stop_id": "amsterdam_centraal_15",
    "status": "delayed",
    "departure_platform": "15"
  }'
```

### Get Stop Statuses for Trip

**Endpoint:** `GET /api/gtfs/stop-statuses/{tripId}`

**Authentication:** Required (Bearer token)

**URL Parameters:**
- `tripId`: Trip ID (string)

**Success Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "trip_id": "9133-1124",
      "stop_id": "amsterdam_centraal_15",
      "status": "on-time",
      "status_color": "34,197,94",
      "status_color_hex": "#22C55E",
      "departure_platform": "15",
      "arrival_platform": "15",
      "scheduled_arrival_time": "10:30:00",
      "scheduled_departure_time": "10:30:00",
      "updated_at": "2024-01-15T10:25:00.000000Z"
    }
  ]
}
```

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/gtfs/stop-statuses/9133-1124 \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

### Update Train Status

Update the overall status for a train trip. This will also update all stop statuses for the trip.

**Endpoint:** `POST /api/gtfs/train-status`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "trip_id": "9133-1124",
  "status": "delayed"
}
```

**Validation Rules:**
- `trip_id`: required, string
- `status`: required, string (must match a valid status in the system)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "trip_id": "9133-1124",
    "status": "delayed",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Note:** This endpoint updates all stop statuses for the trip and triggers a `TrainStatusUpdated` event.

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/gtfs/train-status \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "trip_id": "9133-1124",
    "status": "delayed"
  }'
```

### Get Train Status

**Endpoint:** `GET /api/gtfs/train-status/{tripId}`

**Authentication:** Required (Bearer token)

**URL Parameters:**
- `tripId`: Trip ID (string)

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "trip_id": "9133-1124",
    "status": "delayed",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

**Response (200) when no status exists:**
```json
{
  "success": true,
  "data": null
}
```

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/gtfs/train-status/9133-1124 \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

---

## Announcements

### List All Announcements

**Endpoint:** `GET /api/announcements`

**Authentication:** Required (Bearer token)

**Success Response (200):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "type": "audio",
      "message": "Service 9133 is delayed by 15 minutes",
      "scheduled_time": "2024-01-15T11:00:00.000000Z",
      "author": "System",
      "area": "Terminal",
      "status": "completed",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T11:00:00.000000Z"
    }
  ]
}
```

**Response Notes:**
- Results are ordered by `scheduled_time` descending (most recent first)
- Returns all announcements regardless of status

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/announcements \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

### Get Latest Announcements

**Endpoint:** `GET /api/announcements/latest`

**Authentication:** Required (Bearer token)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "type": "audio",
      "message": "Service 9133 is delayed by 15 minutes",
      "scheduled_time": "2024-01-15T11:00:00.000000Z",
      "author": "System",
      "area": "Terminal",
      "status": "completed",
      "created_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "count": 5
}
```

**Response Notes:**
- Returns the 5 most recent announcements
- Ordered by `created_at` descending
- Includes ISO 8601 formatted timestamps

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/announcements/latest \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

---

## Broker (Pending Announcements)

### Get Pending Announcements

Retrieve all pending announcements. Announcements are automatically marked as "processing" when fetched.

**Endpoint:** `GET /api/broker/pending-announcements`

**Authentication:** Required (Bearer token)

**Success Response (200):**
```json
{
  "announcements": [
    {
      "id": 1,
      "announcement_id": 123,
      "status": "processing",
      "message": "Service 9133 is delayed",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  ]
}
```

**Response Notes:**
- Only returns announcements with status "pending"
- Announcements are automatically updated to "processing" status when fetched
- Results are ordered by `created_at` ascending (oldest first)

**cURL Example:**
```bash
curl -X GET https://your-domain.com/api/broker/pending-announcements \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

### Update Announcement Status

Update the status of a pending announcement after processing.

**Endpoint:** `POST /api/broker/announcement/{id}/status`

**Authentication:** Required (Bearer token)

**URL Parameters:**
- `id`: Pending Announcement ID (integer)

**Request Body:**
```json
{
  "status": "completed",
  "response": "Announcement sent successfully"
}
```

**Validation Rules:**
- `status`: required, must be `completed` or `failed`
- `response`: required, string

**Success Response (200):**
```json
{
  "message": "Status updated successfully"
}
```

**Error Response (404):**
```json
{
  "message": "No query results for model [App\\Models\\PendingAnnouncement] {id}"
}
```

**Response Notes:**
- Sets `processed_at` timestamp automatically
- Use `completed` for successful processing
- Use `failed` for failed processing and include error details in `response`

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/broker/announcement/1/status \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed",
    "response": "Announcement sent successfully"
  }'
```

---

## Aviavox

### Handle Aviavox Webhook Response

Receive webhook responses from Aviavox system. Supports both GET and POST requests.

**Endpoint:** `POST /api/aviavox/response` or `GET /api/aviavox/response`

**Authentication:** Not required (public webhook endpoint)

**Request Headers (POST):**
```http
Content-Type: application/xml
```

**Request Body (POST - XML):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<AIP>
  <Announcement>
    <ID>12345</ID>
    <Status>Completed</Status>
    <MessageName>CHECKIN_AWARE_FAULT</MessageName>
    <Text>Service 9133 check-in is now open</Text>
    <Zones>Terminal</Zones>
    <Description>Check-in announcement</Description>
    <ChainID>67890</ChainID>
  </Announcement>
</AIP>
```

**Request (GET with query parameters):**
```
GET /api/aviavox/response?status=completed&id=12345
```

**Success Response (200):**
```json
{
  "status": "success"
}
```

**Error Response (200) - XML Parse Error:**
```json
{
  "status": "error",
  "message": "Failed to parse XML"
}
```

**Response Notes:**
- GET requests are typically used for health checks
- POST requests with XML are parsed and stored
- Responses are logged and stored in the database
- Supports various response types: announcement, unnamed, unknown

**cURL Example (POST with XML):**
```bash
curl -X POST https://your-domain.com/api/aviavox/response \
  -H "Content-Type: application/xml" \
  -d '<?xml version="1.0" encoding="UTF-8"?>
<AIP>
  <Announcement>
    <ID>12345</ID>
    <Status>Completed</Status>
    <MessageName>CHECKIN_AWARE_FAULT</MessageName>
    <Text>Service 9133 check-in is now open</Text>
    <Zones>Terminal</Zones>
  </Announcement>
</AIP>'
```

**cURL Example (GET):**
```bash
curl -X GET "https://your-domain.com/api/aviavox/response?status=completed&id=12345"
```

### Get Aviavox Responses

Retrieve stored Aviavox responses with filtering and pagination.

**Endpoint:** `GET /api/aviavox/responses`

**Authentication:** Required (Bearer token)

**Query Parameters:**
- `status`: Filter by status (string)
- `message_name`: Filter by message name (string)
- `start_date`: Filter by start date (YYYY-MM-DD)
- `end_date`: Filter by end date (YYYY-MM-DD)
- `per_page`: Items per page (integer, default: 10)

**Success Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "announcement_id": "12345",
      "status": "Completed",
      "message_name": "CHECKIN_AWARE_FAULT",
      "text_content": "Service 9133 check-in is now open",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "raw_response": "<?xml version=\"1.0\"?>..."
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50
  }
}
```

**Response Notes:**
- Results are ordered by `created_at` descending
- `text_content` is extracted from XML and cleaned
- `raw_response` contains the original XML

**cURL Example:**
```bash
curl -X GET "https://your-domain.com/api/aviavox/responses?status=Completed&per_page=20" \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Accept: application/json"
```

---

## Heartbeat

### Send GTFS Heartbeat

Send a heartbeat signal to indicate GTFS realtime feed is active.

**Endpoint:** `POST /api/gtfs/heartbeat`

**Authentication:** Required (Bearer token)

**Request Body:**
```json
{
  "timestamp": "2024-01-15T10:30:00Z",
  "status": "up",
  "statusReason": "All systems operational",
  "lastUpdateSentTimestamp": "2024-01-15T10:29:00Z"
}
```

**Validation Rules:**
- `timestamp`: required, valid date/time
- `status`: required, string
- `statusReason`: optional, string
- `lastUpdateSentTimestamp`: required, valid date/time

**Success Response (200):**
```json
{
  "status": "success",
  "message": "Heartbeat received"
}
```

**Error Response (401):**
```json
{
  "status": "error",
  "message": "Unauthorized"
}
```

**Error Response (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "timestamp": ["The timestamp field is required."]
  }
}
```

**Response Notes:**
- Used to monitor GTFS realtime feed health
- Status banner component checks heartbeat status via polling

**cURL Example:**
```bash
curl -X POST https://your-domain.com/api/gtfs/heartbeat \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "timestamp": "2024-01-15T10:30:00Z",
    "status": "up",
    "statusReason": "All systems operational",
    "lastUpdateSentTimestamp": "2024-01-15T10:29:00Z"
  }'
```

---

## Error Handling

### Common HTTP Status Codes

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

### Error Response Format

```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid",
    "details": {
      "field": ["The field is required"]
    }
  }
}
```

---

## Postman Collection

A complete Postman collection is available for import. See `eurostar-api.postman_collection.json` for the full collection with all endpoints pre-configured.

### Environment Variables

Set up the following environment variables in Postman:

- `base_url`: Your API base URL (e.g., `https://your-domain.com`)
- `api_token`: Your API token (obtained from `/api/sanctum/token`)

### Import Instructions

1. Open Postman
2. Click **Import**
3. Select `eurostar-api.postman_collection.json`
4. Configure environment variables
5. Start testing endpoints

---

## Support

For additional help or questions about the API:

- Review the main [API Documentation](./index.md)
- Check error messages for detailed validation feedback
- Contact the support team for integration assistance


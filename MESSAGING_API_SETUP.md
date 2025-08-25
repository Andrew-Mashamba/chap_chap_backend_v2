# Messaging API Setup Guide

## Overview
This messaging API provides SMS and Email sending capabilities with automatic fallback from SMS to Email when SMS fails.

## Environment Variables
Add the following to your `.env` file:

```env
# Twilio Configuration
TWILIO_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_FROM=+1234567890  # Your Twilio phone number

# Email Configuration (already in Laravel)
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_PORT=587
MAIL_USERNAME=your_email_username
MAIL_PASSWORD=your_email_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@chapchap.com
MAIL_FROM_NAME="ChapChap"
```

## API Endpoints

### 1. Send Single Message
**Endpoint:** `POST /api/messaging/send`

**Headers:**
```
Authorization: Bearer YOUR_API_TOKEN
Content-Type: application/json
```

**Request Body:**
```json
{
  "phone": "+254712345678",
  "email": "user@example.com",
  "message": "Your order has been confirmed",
  "subject": "Order Confirmation",
  "prefer_sms": true
}
```

**Response:**
```json
{
  "success": true,
  "method": "sms",
  "error": null
}
```

### 2. Send Bulk Messages
**Endpoint:** `POST /api/messaging/send-bulk`

**Headers:**
```
Authorization: Bearer YOUR_API_TOKEN
Content-Type: application/json
```

**Request Body:**
```json
{
  "recipients": [
    {
      "phone": "+254712345678",
      "email": "user1@example.com",
      "prefer_sms": true
    },
    {
      "phone": "+254787654321",
      "email": "user2@example.com",
      "prefer_sms": false
    }
  ],
  "message": "Special promotion for all users",
  "subject": "ChapChap Promotion"
}
```

**Response:**
```json
{
  "success": true,
  "results": {
    "total": 2,
    "success": 2,
    "failed": 0,
    "details": [
      {
        "recipient": {
          "phone": "+254712345678",
          "email": "user1@example.com",
          "prefer_sms": true
        },
        "result": {
          "success": true,
          "method": "sms",
          "error": null
        }
      },
      {
        "recipient": {
          "phone": "+254787654321",
          "email": "user2@example.com",
          "prefer_sms": false
        },
        "result": {
          "success": true,
          "method": "email",
          "error": null
        }
      }
    ]
  }
}
```

## Authentication
All messaging endpoints require authentication using Laravel Sanctum. Include your API token in the Authorization header.

## Fallback Logic
1. If `prefer_sms` is true and phone number is provided, SMS is attempted first
2. If SMS fails or `prefer_sms` is false, email is attempted
3. At least one contact method (phone or email) must be provided

## Error Handling
- 422: Validation errors (missing required fields, invalid formats)
- 401: Unauthorized (missing or invalid API token)
- 500: Server error (messaging service failure)

## Testing
To test the implementation:

1. First, ensure you have valid Twilio credentials in your .env file
2. Generate an API token for authentication
3. Use Postman or curl to test the endpoints

Example curl command:
```bash
curl -X POST http://your-domain/api/messaging/send \
  -H "Authorization: Bearer YOUR_API_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+254712345678",
    "email": "test@example.com",
    "message": "Test message",
    "prefer_sms": true
  }'
```
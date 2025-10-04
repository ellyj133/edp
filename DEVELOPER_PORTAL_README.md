# Developer Portal Implementation

This document describes the new Developer Portal features for API key management and API access.

## Features Implemented

### 1. Developer Portal Page (`developer-portal.php`)

A comprehensive portal for developers to manage their API keys and access documentation.

**Key Features:**
- **API Key Management**: Generate, view, activate/deactivate, and delete API keys
- **Environment Support**: Separate keys for `sandbox` (testing) and `live` (production) environments
- **Security**: CSRF protection, hashed API secrets, user authentication required
- **User Interface**: Clean, modern UI with tabbed navigation
- **Real-time Feedback**: Success/error messages for all actions

**Tabs:**
- **API Keys**: Manage your API keys
- **Documentation**: Comprehensive API documentation with examples
- **Usage**: API usage analytics (placeholder for future implementation)

### 2. API Authentication Middleware (`api/auth.php`)

Handles authentication and authorization for all API requests.

**Features:**
- Bearer token authentication via `Authorization` header
- Environment detection from API key prefix (`feza_sandbox_*` or `feza_live_*`)
- Rate limiting with configurable limits per key
- Request logging to `api_logs` table
- Standard error responses with HTTP status codes

**Rate Limit Headers:**
```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1640995200
```

### 3. Products API Endpoint (`api/v1/products.php`)

RESTful API endpoint for retrieving product listings.

**Endpoint:** `GET /api/v1/products`

**Query Parameters:**
- `page` (integer): Page number (default: 1)
- `limit` (integer): Items per page (default: 20, max: 100)
- `category` (string): Filter by category slug
- `search` (string): Search products by name or description
- `min_price` (float): Minimum price filter
- `max_price` (float): Maximum price filter

**Example Request:**
```bash
curl -X GET "https://api.fezamarket.com/v1/products?page=1&limit=20" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

**Example Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Product Name",
      "description": "Product description",
      "price": 29.99,
      "currency": "USD",
      "stock_quantity": 10,
      "image_url": "https://example.com/image.jpg",
      "category": {
        "name": "Electronics",
        "slug": "electronics"
      },
      "seller": "seller_username"
    }
  ],
  "pagination": {
    "current_page": 1,
    "total_pages": 5,
    "total_items": 100,
    "per_page": 20
  }
}
```

### 4. Database Schema Updates

**Migration File:** `database/migrations/add_environment_to_api_keys.sql`

Adds `environment` column to `api_keys` table:
```sql
ALTER TABLE `api_keys` 
ADD COLUMN `environment` ENUM('sandbox', 'live') NOT NULL DEFAULT 'sandbox' AFTER `name`;

ALTER TABLE `api_keys`
ADD INDEX `idx_environment` (`environment`);
```

### 5. Updated Developers Page (`developers.php`)

Links updated to point to the new Developer Portal:
- "Developer Portal" link to manage API keys
- "Documentation" link to API docs
- "Access Sandbox" link to portal
- "View Analytics" link to usage dashboard

## Environment Differentiation

The API automatically detects the environment from the API key prefix:

- **Sandbox Keys**: Start with `feza_sandbox_*`
  - Base URL: `https://api-sandbox.fezamarket.com`
  - Use for testing with sample data
  - Safe to experiment without affecting production

- **Live Keys**: Start with `feza_live_*`
  - Base URL: `https://api.fezamarket.com`
  - Production environment with real data
  - Actual transactions and data modifications

## Security Features

1. **API Secret Hashing**: Secrets are hashed with SHA-256 before storage
2. **CSRF Protection**: All forms include CSRF tokens
3. **User Authentication**: Portal requires user login
4. **Rate Limiting**: Prevents API abuse
5. **Request Logging**: All API requests logged for audit
6. **Key Ownership**: Users can only manage their own keys

## API Key Lifecycle

1. **Creation**: User generates key via Developer Portal
   - Provide name and select environment
   - API key and secret generated
   - Secret shown once (must be saved)

2. **Usage**: Include key in Authorization header
   - `Authorization: Bearer feza_sandbox_...` or `Authorization: Bearer feza_live_...`

3. **Management**: Activate, deactivate, or delete keys
   - Deactivated keys return 403 Forbidden
   - Deleted keys are permanently removed

4. **Monitoring**: Track usage via portal
   - Last used timestamp
   - Rate limit consumption (future)
   - Request logs (future)

## Rate Limiting

Default limits:
- **Free Tier**: 100 requests/hour
- **Pro Tier**: 1,000 requests/hour
- **Enterprise**: Custom limits

Rate limits are enforced per API key and tracked in the database.

## Error Handling

Standard HTTP status codes:
- `200` - Success
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (missing or invalid API key)
- `403` - Forbidden (inactive key)
- `404` - Not Found
- `429` - Rate Limit Exceeded
- `500` - Internal Server Error

Error response format:
```json
{
  "success": false,
  "error": {
    "code": "INVALID_REQUEST",
    "message": "Missing required parameter: name"
  }
}
```

## Future Enhancements

- [ ] Additional API endpoints (orders, customers, etc.)
- [ ] Webhooks for event notifications
- [ ] API usage analytics dashboard
- [ ] GraphQL API support
- [ ] SDK libraries (PHP, Python, JavaScript)
- [ ] API versioning (v2, v3, etc.)
- [ ] OAuth 2.0 support
- [ ] IP whitelisting
- [ ] Custom rate limits per key

## Testing

To test the implementation:

1. **Create API Key**:
   - Login to the platform
   - Navigate to Developer Portal
   - Create a sandbox key
   - Save the API secret

2. **Test API Request**:
   ```bash
   curl -X GET "http://localhost/api/v1/products" \
     -H "Authorization: Bearer YOUR_SANDBOX_KEY"
   ```

3. **Verify Rate Limiting**:
   - Make multiple requests
   - Check rate limit headers in response
   - Exceed limit to test 429 response

## Files Modified/Created

- `developer-portal.php` - New developer portal page
- `api/auth.php` - New API authentication middleware
- `api/v1/products.php` - New products API endpoint
- `api/.htaccess` - URL rewriting for clean API URLs
- `database/migrations/add_environment_to_api_keys.sql` - Database migration
- `developers.php` - Updated with portal links
- `category.php` - Fixed CSS layout issues

## Category Page Layout Fix

In addition to the developer portal, the category page layout was fixed:

**Issues Fixed:**
- Conflicting CSS rules between global styles and inline styles
- Filter sidebar and product grid overlapping
- Improper two-column layout
- Missing sticky positioning on sidebar

**Changes Made:**
- Used `!important` to override global styles
- Explicit grid layout: `grid-template-columns: 250px 1fr`
- Proper sticky positioning with `max-height`
- Mobile-responsive single-column layout

## Support

For questions or issues:
- Check the Documentation tab in Developer Portal
- Contact support via the platform
- Review API logs for debugging

# Purchase Flows API Documentation

Complete API reference for all e-commerce purchase-related operations.

## Table of Contents
1. [Authentication](#authentication)
2. [Cart Operations](#cart-operations)
3. [Wishlist Operations](#wishlist-operations)
4. [Watchlist Operations](#watchlist-operations)
5. [Buy It Now](#buy-it-now)
6. [Checkout](#checkout)
7. [Error Handling](#error-handling)
8. [Response Formats](#response-formats)

---

## Authentication

All purchase operations require user authentication. APIs return `401 Unauthorized` if the user is not logged in.

**Headers Required:**
```
Content-Type: application/json
```

**Session Required:**
- User must be logged in with valid session
- CSRF token required for POST/PUT/DELETE operations

---

## Cart Operations

### Add Item to Cart

**Endpoint:** `POST /api/cart.php`

**Request Body:**
```json
{
  "action": "add",
  "product_id": 123,
  "quantity": 2
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item added to cart",
  "data": {
    "count": 5,
    "total": 149.99
  }
}
```

**Validation Rules:**
- Product must exist
- Product must be active
- Sufficient stock must be available
- Quantity must be positive integer

**Error Responses:**
- `400`: Invalid product or quantity
- `400`: Product not found
- `400`: Product is not available
- `400`: Insufficient stock available
- `401`: Please login to manage your cart

---

### Update Cart Item

**Endpoint:** `POST /api/cart.php`

**Request Body:**
```json
{
  "action": "update",
  "product_id": 123,
  "quantity": 3
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Cart updated",
  "data": {
    "count": 6,
    "total": 199.99
  }
}
```

**Validation Rules:**
- Product must exist in cart
- New quantity must not exceed stock
- Quantity must be positive integer

---

### Remove Item from Cart

**Endpoint:** `POST /api/cart.php`

**Request Body:**
```json
{
  "action": "remove",
  "product_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item removed from cart",
  "data": {
    "count": 3,
    "total": 99.99
  }
}
```

---

### Clear Cart

**Endpoint:** `POST /api/cart.php`

**Request Body:**
```json
{
  "action": "clear"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Cart cleared",
  "data": {
    "count": 0,
    "total": 0
  }
}
```

---

### Get Cart

**Endpoint:** `GET /api/cart.php`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "product_id": 123,
        "name": "Product Name",
        "price": 49.99,
        "quantity": 2,
        "subtotal": 99.98,
        "image_url": "/uploads/products/image.jpg"
      }
    ],
    "total": 99.98,
    "count": 2
  }
}
```

---

## Wishlist Operations

### Add to Wishlist

**Endpoint:** `POST /api/wishlist.php`

**Request Body:**
```json
{
  "action": "add",
  "product_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item added to wishlist",
  "data": {}
}
```

**Validation Rules:**
- Product must exist
- Item cannot already be in wishlist (duplicate prevention)

**Error Responses:**
- `400`: Invalid product
- `400`: Product not found
- `400`: Item already in wishlist or failed to add
- `401`: Please login to manage your wishlist

---

### Remove from Wishlist

**Endpoint:** `POST /api/wishlist.php`

**Request Body:**
```json
{
  "action": "remove",
  "product_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item removed from wishlist",
  "data": {}
}
```

---

### Check if in Wishlist

**Endpoint:** `POST /api/wishlist.php`

**Request Body:**
```json
{
  "action": "check",
  "product_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "in_wishlist": true
  }
}
```

---

### Get Wishlist

**Endpoint:** `GET /api/wishlist.php`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "product_id": 123,
        "name": "Product Name",
        "price": 49.99,
        "image_url": "/uploads/products/image.jpg",
        "created_at": "2024-01-15 10:30:00"
      }
    ]
  }
}
```

---

## Watchlist Operations

### Add to Watchlist

**Endpoint:** `POST /api/watchlist.php`

**Request Body:**
```json
{
  "action": "add",
  "product_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item added to watchlist",
  "data": {}
}
```

**Validation Rules:**
- Product must exist
- Item cannot already be in watchlist

**Error Responses:**
- `400`: Invalid product
- `400`: Product not found
- `400`: Item already in watchlist or failed to add
- `401`: Please login to manage your watchlist

---

### Remove from Watchlist

**Endpoint:** `POST /api/watchlist.php`

**Request Body:**
```json
{
  "action": "remove",
  "product_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Item removed from watchlist",
  "data": {}
}
```

---

### Check if in Watchlist

**Endpoint:** `POST /api/watchlist.php`

**Request Body:**
```json
{
  "action": "check",
  "product_id": 123
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "in_watchlist": true
  }
}
```

---

### Get Watchlist

**Endpoint:** `GET /api/watchlist.php`

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success",
  "data": {
    "items": [
      {
        "product_id": 123,
        "name": "Product Name",
        "price": 49.99,
        "image_url": "/uploads/products/image.jpg",
        "created_at": "2024-01-15 10:30:00"
      }
    ]
  }
}
```

---

## Buy It Now

Buy It Now adds the product to cart and immediately redirects to checkout.

**Endpoint:** `POST /product.php?id={product_id}` or `POST /product/{product_id}`

**Request Body (Form Data):**
```
action=buy_now
quantity=1
csrf_token={token}
```

**Success Response (200):**
```json
{
  "success": true,
  "redirect": "/checkout.php"
}
```

**Validation Rules:**
- User must be authenticated
- Product must exist and be active
- Sufficient stock must be available
- CSRF token must be valid

**Error Responses:**
- `400`: Insufficient stock
- `401`: Please login to purchase
- `400`: Invalid request

**Flow:**
1. Validates product availability
2. Adds item to cart
3. Returns redirect URL to checkout
4. Frontend redirects to checkout page

---

## Checkout

### View Checkout Page

**Endpoint:** `GET /checkout.php`

**Requirements:**
- User must be logged in
- Cart must not be empty
- All cart items must be valid (active products with sufficient stock)

**Validations Performed:**
- Cart not empty
- Products still exist
- Products are active
- Sufficient stock for all items

**Error Redirects:**
- `/cart.php?error=empty_cart`: Cart is empty
- `/cart.php?error=product_unavailable`: Product no longer available
- `/cart.php?error=product_inactive`: Product is not active
- `/cart.php?error=insufficient_stock`: Not enough stock

---

### Process Checkout

**Endpoint:** `POST /checkout.php`

**Request Body (Form Data):**
```
billing_address_id=1
shipping_address_id=2
payment_method_id=1
use_wallet_credit=true
csrf_token={token}
```

**Process Flow:**
1. Validate CSRF token and rate limit
2. Calculate totals (subtotal, tax, shipping)
3. Apply wallet credit if selected
4. Create order with pending status
5. Process payment
6. Decrease stock atomically
7. Clear cart
8. Send order confirmation email
9. Redirect to order confirmation

**Success Redirect:**
```
/order-confirmation.php?order={order_number}
```

**Validation:**
- All cart items are still valid
- Stock is available for all items
- Payment processing succeeds
- Stock decrement is atomic (prevents overselling)

**Transaction Safety:**
- Order creation wrapped in database transaction
- Rollback on any failure
- Cart only cleared on successful order

---

## Error Handling

### Standard Error Response Format

```json
{
  "error": "Error message here"
}
```

**HTTP Status Codes:**
- `200`: Success
- `400`: Bad Request (validation error)
- `401`: Unauthorized (not logged in)
- `403`: Forbidden (insufficient permissions)
- `404`: Not Found
- `405`: Method Not Allowed
- `500`: Internal Server Error

### Common Errors

**Authentication Errors:**
```json
{
  "error": "Please login to manage your cart"
}
```

**Validation Errors:**
```json
{
  "error": "Invalid product or quantity"
}
```

**Stock Errors:**
```json
{
  "error": "Insufficient stock available"
}
```

**Product Errors:**
```json
{
  "error": "Product not found"
}
```
```json
{
  "error": "Product is not available"
}
```

---

## Response Formats

### Success Response Structure

All successful API calls follow this format:

```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data here
  }
}
```

### Error Response Structure

All errors follow this format:

```json
{
  "error": "Error message"
}
```

### Standard Data Fields

**Cart Item:**
```json
{
  "product_id": 123,
  "name": "Product Name",
  "price": 49.99,
  "quantity": 2,
  "subtotal": 99.98,
  "image_url": "/uploads/products/image.jpg",
  "sku": "PROD-123",
  "stock_quantity": 10
}
```

**Wishlist/Watchlist Item:**
```json
{
  "product_id": 123,
  "name": "Product Name",
  "price": 49.99,
  "image_url": "/uploads/products/image.jpg",
  "created_at": "2024-01-15 10:30:00",
  "stock_quantity": 10,
  "status": "active"
}
```

---

## Security Features

### CSRF Protection
- All state-changing operations require CSRF token
- Token validated on server side
- Token included in forms and AJAX requests

### Authentication
- Session-based authentication
- All APIs check `Session::isLoggedIn()`
- Unauthorized requests return 401

### Input Validation
- Product IDs validated as positive integers
- Quantities validated as positive integers
- Product existence validated
- Stock availability validated
- Product status validated (must be 'active')

### SQL Injection Prevention
- All queries use prepared statements
- Parameters properly bound
- No direct SQL string concatenation

### Rate Limiting
- Checkout process includes rate limiting
- Prevents abuse and brute force attempts

---

## Progressive Enhancement

All purchase operations support graceful degradation:

1. **JavaScript Enabled:**
   - AJAX requests to API endpoints
   - Immediate UI feedback
   - No page reload
   - Better UX with loading states

2. **JavaScript Disabled:**
   - Form submissions to same endpoints
   - Server-side processing
   - Redirect with flash messages
   - Full functionality maintained

---

## Best Practices

### Frontend Integration

**Using Fetch API:**
```javascript
async function addToCart(productId, quantity) {
  try {
    const response = await fetch('/api/cart.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'add',
        product_id: productId,
        quantity: quantity
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      // Update UI
      updateCartCount(data.data.count);
      showSuccessMessage(data.message);
    } else {
      showErrorMessage(data.error);
    }
  } catch (error) {
    console.error('Error:', error);
    showErrorMessage('Network error occurred');
  }
}
```

**CSRF Token:**
```javascript
// Include CSRF token from meta tag or inline script
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Or from inline script
const csrfToken = window.csrfToken;
```

### Error Handling

Always handle both success and error cases:

```javascript
if (data.success) {
  // Success handling
} else if (data.error) {
  // Error handling
} else {
  // Unexpected response format
}
```

### Loading States

Show loading indicators during API calls:

```javascript
button.disabled = true;
button.textContent = 'Adding...';

// Make API call

button.disabled = false;
button.textContent = 'Add to Cart';
```

---

## Testing

### Manual Testing

1. **Add to Cart:**
   - Add items with valid quantity
   - Try adding out-of-stock items
   - Try adding inactive products
   - Verify cart count updates

2. **Update Cart:**
   - Increase/decrease quantities
   - Try exceeding stock
   - Remove items
   - Clear cart

3. **Wishlist/Watchlist:**
   - Add items
   - Remove items
   - Check duplicate prevention
   - Verify list display

4. **Buy It Now:**
   - Buy with sufficient stock
   - Try buying out-of-stock
   - Verify redirect to checkout

5. **Checkout:**
   - Complete full checkout
   - Test with empty cart
   - Test with invalid products
   - Test with insufficient stock

### API Testing with cURL

**Add to Cart:**
```bash
curl -X POST http://localhost/api/cart.php \
  -H "Content-Type: application/json" \
  -d '{"action":"add","product_id":1,"quantity":2}' \
  --cookie "PHPSESSID=your_session_id"
```

**Get Cart:**
```bash
curl -X GET http://localhost/api/cart.php \
  --cookie "PHPSESSID=your_session_id"
```

---

## Migration Notes

If migrating from older implementation:

1. Update AJAX calls to use new `/api/` endpoints
2. Update response handling for new format
3. Include CSRF tokens in all requests
4. Update error handling for new error format
5. Test all purchase flows end-to-end

---

## Support

For issues or questions:
- Check error messages in browser console
- Review server error logs
- Verify session is active
- Confirm CSRF token is valid
- Validate product exists and is active

---

Last Updated: 2024-01-15
Version: 1.0.0

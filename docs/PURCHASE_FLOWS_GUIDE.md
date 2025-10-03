# Purchase Flows Implementation Guide

A complete guide for developers implementing or customizing purchase flows in the e-commerce platform.

## Overview

This platform provides a complete purchase experience with the following features:

✅ Add to Cart with quantity and variant support  
✅ Update/Remove Cart items  
✅ Wishlist for saving items  
✅ Watchlist for price monitoring  
✅ Buy It Now for fast checkout  
✅ Complete checkout with validation  

All features include:
- Server-side validation
- CSRF protection
- Authentication checks
- Stock management
- Error handling
- Progressive enhancement

---

## Quick Start

### 1. Add to Cart Button

**Simple Implementation:**

```html
<button onclick="addToCart(<?= $productId ?>, 1)">Add to Cart</button>

<script>
async function addToCart(productId, quantity) {
  try {
    const response = await fetch('/api/cart.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: 'add',
        product_id: productId,
        quantity: quantity
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      alert('Added to cart!');
      updateCartBadge(data.data.count);
    } else {
      alert(data.error || 'Failed to add to cart');
    }
  } catch (error) {
    alert('Network error occurred');
  }
}

function updateCartBadge(count) {
  document.querySelector('.cart-count').textContent = count;
}
</script>
```

### 2. Wishlist Button

```html
<button id="wishlist-btn" onclick="toggleWishlist(<?= $productId ?>)">
  <?= $isWishlisted ? '❤️ In Wishlist' : '🤍 Add to Wishlist' ?>
</button>

<script>
async function toggleWishlist(productId) {
  const btn = document.getElementById('wishlist-btn');
  const isWishlisted = btn.textContent.includes('In Wishlist');
  
  try {
    const response = await fetch('/api/wishlist.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        action: isWishlisted ? 'remove' : 'add',
        product_id: productId
      })
    });
    
    const data = await response.json();
    
    if (data.success) {
      btn.textContent = isWishlisted ? '🤍 Add to Wishlist' : '❤️ In Wishlist';
    } else {
      alert(data.error || 'Failed to update wishlist');
    }
  } catch (error) {
    alert('Network error occurred');
  }
}
</script>
```

### 3. Buy It Now Button

```html
<button onclick="buyNow(<?= $productId ?>, 1)">Buy It Now</button>

<script>
async function buyNow(productId, quantity) {
  try {
    const response = await fetch(`/product.php?id=${productId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=buy_now&quantity=${quantity}&csrf_token=${csrfToken}`
    });
    
    const data = await response.json();
    
    if (data.success && data.redirect) {
      window.location.href = data.redirect;
    } else {
      alert(data.message || 'Failed to process purchase');
    }
  } catch (error) {
    alert('Network error occurred');
  }
}
</script>
```

---

## Complete Product Page Integration

Here's a complete product page implementation with all features:

```php
<?php
// product.php
require_once __DIR__ . '/includes/init.php';

$productId = (int)($_GET['id'] ?? 0);
$isLoggedIn = Session::isLoggedIn();
$userId = $isLoggedIn ? Session::getUserId() : null;

// Get product
$product = (new Product())->find($productId);
if (!$product) {
    header('HTTP/1.1 404 Not Found');
    exit('Product not found');
}

// Check wishlist/watchlist status
$isWishlisted = false;
$isWatchlisted = false;
if ($userId) {
    $isWishlisted = (new Wishlist())->isInWishlist($userId, $productId);
    $isWatchlisted = (new Watchlist())->isInWatchlist($userId, $productId);
}

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login']);
        exit;
    }
    
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        exit;
    }
    
    $cart = new Cart();
    
    switch ($_POST['action']) {
        case 'add_to_cart':
            $quantity = (int)($_POST['quantity'] ?? 1);
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit;
            }
            $result = $cart->addItem($userId, $productId, $quantity);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Added to cart' : 'Failed to add'
            ]);
            exit;
            
        case 'buy_now':
            $quantity = (int)($_POST['quantity'] ?? 1);
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
                exit;
            }
            $cart->addItem($userId, $productId, $quantity);
            echo json_encode(['success' => true, 'redirect' => '/checkout.php']);
            exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($product['name']) ?></title>
    <meta name="csrf-token" content="<?= csrfToken() ?>">
</head>
<body>
    <div class="product-page">
        <h1><?= htmlspecialchars($product['name']) ?></h1>
        <p class="price">$<?= number_format($product['price'], 2) ?></p>
        
        <?php if ($product['stock_quantity'] > 0): ?>
            <div class="quantity-selector">
                <label>Quantity:</label>
                <input type="number" id="quantity" value="1" min="1" 
                       max="<?= min(10, $product['stock_quantity']) ?>">
            </div>
            
            <button class="btn-primary" onclick="buyNow()">Buy It Now</button>
            <button class="btn-secondary" onclick="addToCart()">Add to Cart</button>
            <button class="btn-outline" onclick="toggleWishlist()">
                <?= $isWishlisted ? '❤️ In Wishlist' : '🤍 Add to Wishlist' ?>
            </button>
            <button class="btn-outline" onclick="toggleWatchlist()">
                <?= $isWatchlisted ? '👁️ Watching' : '👁️ Watch' ?>
            </button>
            
            <p class="stock-info"><?= $product['stock_quantity'] ?> available</p>
        <?php else: ?>
            <p class="out-of-stock">Currently unavailable</p>
        <?php endif; ?>
    </div>
    
    <script>
    const productId = <?= $productId ?>;
    const isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
    const csrfToken = '<?= csrfToken() ?>';
    
    async function addToCart() {
        if (!isLoggedIn) {
            window.location.href = '/login.php';
            return;
        }
        
        const quantity = document.getElementById('quantity').value;
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add_to_cart&quantity=${quantity}&csrf_token=${csrfToken}`
        });
        
        const data = await response.json();
        alert(data.message);
    }
    
    async function buyNow() {
        if (!isLoggedIn) {
            window.location.href = '/login.php';
            return;
        }
        
        const quantity = document.getElementById('quantity').value;
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=buy_now&quantity=${quantity}&csrf_token=${csrfToken}`
        });
        
        const data = await response.json();
        if (data.success && data.redirect) {
            window.location.href = data.redirect;
        }
    }
    
    async function toggleWishlist() {
        if (!isLoggedIn) {
            window.location.href = '/login.php';
            return;
        }
        
        const btn = event.target;
        const isWishlisted = btn.textContent.includes('In Wishlist');
        
        const response = await fetch('/api/wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: isWishlisted ? 'remove' : 'add',
                product_id: productId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            btn.textContent = isWishlisted ? '🤍 Add to Wishlist' : '❤️ In Wishlist';
        }
    }
    
    async function toggleWatchlist() {
        if (!isLoggedIn) {
            window.location.href = '/login.php';
            return;
        }
        
        const btn = event.target;
        const isWatchlisted = btn.textContent.includes('Watching');
        
        const response = await fetch('/api/watchlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: isWatchlisted ? 'remove' : 'add',
                product_id: productId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            btn.textContent = isWatchlisted ? '👁️ Watch' : '👁️ Watching';
        }
    }
    </script>
</body>
</html>
```

---

## Cart Page Implementation

```php
<?php
// cart.php
require_once __DIR__ . '/includes/init.php';
Session::requireLogin();

$userId = Session::getUserId();
$cart = new Cart();
$items = $cart->getCartItems($userId);
$total = $cart->getCartTotal($userId);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart</title>
</head>
<body>
    <h1>Shopping Cart</h1>
    
    <?php if (empty($items)): ?>
        <p>Your cart is empty</p>
        <a href="/products.php">Continue Shopping</a>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr data-product-id="<?= $item['product_id'] ?>">
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td>
                        <input type="number" value="<?= $item['quantity'] ?>" 
                               min="1" max="<?= $item['stock_quantity'] ?>"
                               onchange="updateQuantity(<?= $item['product_id'] ?>, this.value)">
                    </td>
                    <td>$<?= number_format($item['subtotal'], 2) ?></td>
                    <td>
                        <button onclick="removeItem(<?= $item['product_id'] ?>)">Remove</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="cart-summary">
            <h3>Total: $<?= number_format($total, 2) ?></h3>
            <a href="/checkout.php" class="btn-primary">Proceed to Checkout</a>
            <button onclick="clearCart()" class="btn-outline">Clear Cart</button>
        </div>
    <?php endif; ?>
    
    <script>
    async function updateQuantity(productId, quantity) {
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update',
                product_id: productId,
                quantity: parseInt(quantity)
            })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        } else {
            alert(data.error);
        }
    }
    
    async function removeItem(productId) {
        if (!confirm('Remove this item?')) return;
        
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'remove',
                product_id: productId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        }
    }
    
    async function clearCart() {
        if (!confirm('Clear entire cart?')) return;
        
        const response = await fetch('/api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear' })
        });
        
        const data = await response.json();
        if (data.success) {
            location.reload();
        }
    }
    </script>
</body>
</html>
```

---

## Best Practices

### 1. Always Check Authentication

```javascript
if (!isLoggedIn) {
    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
}
```

### 2. Include CSRF Tokens

```html
<meta name="csrf-token" content="<?= csrfToken() ?>">

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
</script>
```

### 3. Handle Errors Gracefully

```javascript
try {
    const response = await fetch('/api/cart.php', { ... });
    const data = await response.json();
    
    if (data.success) {
        // Success handling
    } else {
        showError(data.error || 'Operation failed');
    }
} catch (error) {
    console.error('Network error:', error);
    showError('Network error occurred. Please try again.');
}
```

### 4. Provide User Feedback

Replace `alert()` with better UI:

```javascript
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
```

### 5. Update UI Immediately

```javascript
async function addToCart(productId, quantity) {
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Adding...';
    
    try {
        const response = await fetch('/api/cart.php', { ... });
        const data = await response.json();
        
        if (data.success) {
            updateCartBadge(data.data.count);
            showToast('Added to cart!');
        }
    } finally {
        btn.disabled = false;
        btn.textContent = 'Add to Cart';
    }
}
```

### 6. Validate on Client and Server

**Client-side:**
```javascript
const quantity = parseInt(document.getElementById('quantity').value);
if (quantity < 1 || quantity > maxStock) {
    showError('Invalid quantity');
    return;
}
```

**Server-side:**
```php
$quantity = (int)($_POST['quantity'] ?? 0);
if ($quantity < 1) {
    errorResponse('Invalid quantity');
}
```

---

## Common Patterns

### Loading States

```javascript
class LoadingButton {
    constructor(button) {
        this.button = button;
        this.originalText = button.textContent;
    }
    
    start() {
        this.button.disabled = true;
        this.button.classList.add('loading');
        this.button.textContent = 'Loading...';
    }
    
    stop() {
        this.button.disabled = false;
        this.button.classList.remove('loading');
        this.button.textContent = this.originalText;
    }
}

// Usage
const btn = new LoadingButton(document.getElementById('add-to-cart'));
btn.start();
// ... async operation
btn.stop();
```

### Debounced Updates

```javascript
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

const updateQuantityDebounced = debounce(updateQuantity, 500);

// Usage
<input onchange="updateQuantityDebounced(productId, this.value)">
```

### Cart Count Badge

```javascript
function updateCartBadge(count) {
    const badge = document.querySelector('.cart-count');
    if (badge) {
        badge.textContent = count;
        badge.classList.add('bounce');
        setTimeout(() => badge.classList.remove('bounce'), 300);
    }
}
```

---

## Troubleshooting

### Issue: Cart not updating

**Check:**
1. User is logged in
2. CSRF token is valid
3. Product exists and is active
4. Stock is available
5. Check browser console for errors
6. Check server error logs

### Issue: 401 Unauthorized

**Solution:**
```javascript
if (response.status === 401) {
    window.location.href = '/login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
}
```

### Issue: Stock validation failing

**Check:**
1. Stock quantity in database
2. Existing cart quantity
3. Total quantity not exceeding stock

### Issue: CSRF token invalid

**Solution:**
```php
// Regenerate token if stale
if (!verifyCsrfToken($token)) {
    // Generate new token
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    errorResponse('Token expired. Please try again.');
}
```

---

## Testing Checklist

- [ ] Add to cart with valid quantity
- [ ] Add to cart with excessive quantity (should fail)
- [ ] Add out-of-stock product (should fail)
- [ ] Update cart item quantity
- [ ] Remove cart item
- [ ] Clear cart
- [ ] Add to wishlist
- [ ] Remove from wishlist
- [ ] Toggle wishlist repeatedly
- [ ] Add to watchlist
- [ ] Buy it now with valid stock
- [ ] Buy it now with no stock (should fail)
- [ ] Complete checkout with valid cart
- [ ] Checkout with empty cart (should redirect)
- [ ] Checkout with invalid product (should fail)
- [ ] Test without login (should require login)
- [ ] Test with JavaScript disabled (should use forms)

---

## Performance Tips

1. **Batch Updates:**
   ```javascript
   // Instead of multiple API calls
   await Promise.all([
       addToCart(product1),
       addToCart(product2),
       addToCart(product3)
   ]);
   ```

2. **Cache Cart Count:**
   ```javascript
   let cachedCartCount = null;
   let cacheTime = Date.now();
   
   async function getCartCount() {
       if (cachedCartCount && Date.now() - cacheTime < 5000) {
           return cachedCartCount;
       }
       
       const response = await fetch('/api/cart.php');
       const data = await response.json();
       cachedCartCount = data.data.count;
       cacheTime = Date.now();
       return cachedCartCount;
   }
   ```

3. **Optimistic UI Updates:**
   ```javascript
   // Update UI immediately
   updateCartBadge(currentCount + 1);
   
   // Then make API call
   const response = await fetch('/api/cart.php', ...);
   if (!response.ok) {
       // Rollback on failure
       updateCartBadge(currentCount);
   }
   ```

---

## Additional Resources

- [API Documentation](./PURCHASE_FLOWS_API.md)
- [Implementation Complete](../IMPLEMENTATION_COMPLETE.md)
- [E-commerce Fix Summary](../ECOMMERCE_FIX_SUMMARY.md)

---

Last Updated: 2024-01-15
Version: 1.0.0

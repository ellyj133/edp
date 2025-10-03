# Purchase Flow Regression Testing Guide

This document outlines the testing procedures for the three post-merge regression fixes.

## Prerequisites
- Web server (Apache/Nginx) with mod_rewrite enabled
- PHP 8.0+ 
- Database with product data
- At least one active product in the database

## Test A: Homepage Options → Product Page

### Test Case 1: Options Button Navigation
**Steps:**
1. Navigate to homepage (/)
2. Locate any product card in the product sections:
   - "Fresh for your style"
   - "Rollbacks on furniture"
   - "Save big on all your faves"
   - "Shop new for him & her"
3. Click the "Options" button

**Expected Result:**
- Should navigate to `/product/{id}` or `/product/{slug}`
- Product detail page should load successfully
- Product information should display correctly
- No 404 error

**Failure Indicators:**
- 404 Page Not Found error
- Blank page
- URL like `/product/undefined` or `/product/#`

### Debug Steps if Test Fails:
1. Check browser console for JavaScript errors
2. Verify product data has valid `id` and `slug` fields
3. Check .htaccess RewriteRule for product routes
4. Verify product.php can handle both `?id=` and slug parameters
5. Check Apache mod_rewrite is enabled: `apache2ctl -M | grep rewrite`

---

## Test B: Wishlist/Watchlist from Homepage

### Test Case 2: Wishlist - Logged Out User
**Steps:**
1. Navigate to homepage (/)
2. Ensure you are logged out
3. Click any wishlist heart button (♡)

**Expected Result:**
- Toast notification: "Please login to continue"
- After 1 second, redirect to `/login.php?redirect={current_url}`

### Test Case 3: Wishlist - Logged In User
**Steps:**
1. Log in to the application
2. Navigate to homepage (/)
3. Click any wishlist heart button (♡)

**Expected Result:**
- Toast notification: "Item added to wishlist" (success - green)
- Button updates (if implemented with state tracking)
- No error in browser console

**Failure Indicators:**
- Error toast: "Network error. Please try again."
- Console error: `toggleWishlist is not defined`
- API error response in Network tab

### Test Case 4: Watchlist - Logged In User
**Steps:**
1. Log in to the application
2. Navigate to homepage (/)
3. If watchlist buttons exist on homepage, click one

**Expected Result:**
- Toast notification: "Item added to watchlist"
- No errors

### Debug Steps if Tests Fail:
1. **Check JavaScript Files Loaded:**
   - Open browser DevTools → Network tab
   - Verify `/assets/js/ui.js` loads (200 OK)
   - Verify `/assets/js/purchase-flows.js` loads (200 OK)

2. **Check Global Variables:**
   - Open browser console
   - Type: `window.isLoggedIn` → should be `true` or `false`
   - Type: `window.csrfToken` → should be a string token
   - Type: `typeof toggleWishlist` → should be `"function"`

3. **Check API Response:**
   - Open DevTools → Network tab
   - Click wishlist button
   - Find POST request to `/api/wishlist.php`
   - Check Response tab, should be:
     ```json
     {
       "success": true,
       "message": "Item added to wishlist",
       "data": []
     }
     ```
   - If format is wrong, check api/wishlist.php uses `successResponse([], 'message')`

4. **Check Authentication:**
   - Verify user is logged in: session exists
   - Check `/api/wishlist.php` returns 401 if not logged in

---

## Test C: Buy It Now → Checkout

### Test Case 5: Buy It Now - Logged Out
**Steps:**
1. Navigate to any product page
2. Ensure you are logged out
3. Click "Buy It Now" button

**Expected Result:**
- Toast notification: "Please login to continue"
- Redirect to login page

### Test Case 6: Buy It Now - No Addresses
**Steps:**
1. Log in with a user that has no saved addresses
2. Navigate to any product page
3. Click "Buy It Now" button

**Expected Result:**
- Product added to cart
- Redirect to `/checkout.php`
- Checkout page loads successfully
- Message or form: "No addresses found. Add an address first."
- **NO PHP Fatal Error**

**Failure Indicators:**
- Fatal error: `Call to undefined method User::getUserAddresses()`
- 500 Internal Server Error
- White screen

### Test Case 7: Buy It Now - With Addresses
**Steps:**
1. Log in with a user that has saved addresses
2. Navigate to any product page
3. Click "Buy It Now" button

**Expected Result:**
- Product added to cart
- Redirect to `/checkout.php`
- Checkout page displays:
  - Cart items
  - Shipping address selection (radio buttons)
  - Billing address selection
  - Payment method section
- User can select addresses and proceed

### Debug Steps if Tests Fail:

1. **Check User Model:**
   - File: `/home/runner/work/edp/edp/includes/models.php`
   - Verify `getAddresses($userId)` method exists (line ~115)
   - Verify `getUserAddresses($userId)` alias exists (line ~120)

2. **Check Checkout Call:**
   - File: `/home/runner/work/edp/edp/checkout.php`
   - Line ~92 should call: `$addresses = $user->getAddresses($userId);`
   - NOT: `$addresses = $user->getUserAddresses($userId);`

3. **Check Database:**
   - Verify `addresses` table exists
   - Schema should have: `id, user_id, type, address_line1, address_line2, city, state, postal_code, country, is_default, created_at`

4. **Check Error Logs:**
   - PHP error log location: usually `/var/log/apache2/error.log` or `/var/log/php-fpm/error.log`
   - Look for stack traces showing the exact error

---

## Common Issues and Solutions

### Issue: "toggleWishlist is not defined"
**Solution:** 
- Ensure purchase-flows.js is included in index.php before closing `</body>`
- Check file path is correct: `/assets/js/purchase-flows.js`
- Clear browser cache

### Issue: "CSRF token mismatch" 
**Solution:**
- Verify `window.csrfToken` is set on page load
- Check session is active
- Ensure token is sent in fetch headers or request body

### Issue: Product URLs go to 404
**Solution:**
- Check .htaccess has product rewrite rule
- Verify mod_rewrite is enabled in Apache
- Test with direct URL: `/product.php?id=1`
- Check product slug/id in database

### Issue: Checkout shows "getUserAddresses" error even after fix
**Solution:**
- Clear PHP opcode cache: `service php-fpm restart` or `systemctl reload apache2`
- Verify the correct files are deployed
- Check git diff to ensure changes were committed

---

## Automated Testing (Optional)

If you have PHPUnit installed, run:
```bash
cd /home/runner/work/edp/edp
./vendor/bin/phpunit tests/PurchaseFlowsIntegrationTest.php
```

---

## Success Criteria

All tests must pass with no errors:
- ✅ Options button navigates to product detail page
- ✅ Wishlist button works for logged-in users
- ✅ Watchlist button works for logged-in users  
- ✅ Buy It Now loads checkout page without errors
- ✅ Checkout displays addresses or address form
- ✅ No JavaScript console errors
- ✅ No PHP fatal errors
- ✅ Proper toast notifications on all actions

---

## Rollback Plan

If issues persist after deployment:

1. **Revert .htaccess changes:**
   ```bash
   git checkout HEAD~1 -- .htaccess
   ```

2. **Revert all changes:**
   ```bash
   git revert HEAD
   git push
   ```

3. **Emergency fix:**
   - Temporarily change all `/product/{slug}` URLs back to `/product.php?id={id}` in fetchRealProducts()
   - This will make Options buttons work immediately while investigating routing issues

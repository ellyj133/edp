# Purchase Flows Manual Testing Checklist

Comprehensive manual testing checklist for all e-commerce purchase flows.

## Pre-Test Setup

### Test Accounts
- [ ] Create test buyer account (email: buyer@test.com)
- [ ] Create test seller account (email: seller@test.com)
- [ ] Verify test accounts have correct permissions

### Test Products
- [ ] Create product with sufficient stock (10+ units)
- [ ] Create product with limited stock (2 units)
- [ ] Create product with no stock (0 units)
- [ ] Create inactive product (status = 'inactive')

### Environment
- [ ] Clear browser cache
- [ ] Enable browser DevTools console
- [ ] Disable ad blockers
- [ ] Test in Chrome/Firefox/Safari
- [ ] Test on mobile viewport

---

## 1. Add to Cart Tests

### Basic Add to Cart
- [ ] Navigate to product page
- [ ] Click "Add to Cart" button
- [ ] Verify success toast notification appears
- [ ] Verify cart count badge updates in header
- [ ] Check browser console for errors
- [ ] Verify item appears in cart page

### Quantity Selection
- [ ] Set quantity to 2
- [ ] Click "Add to Cart"
- [ ] Verify correct quantity added
- [ ] Navigate to cart page
- [ ] Verify quantity is 2

### Stock Validation
- [ ] Add product to cart with max quantity
- [ ] Try adding more (should fail)
- [ ] Verify error message shows insufficient stock
- [ ] Try adding out-of-stock product (should fail)

### Duplicate Items
- [ ] Add product to cart
- [ ] Add same product again
- [ ] Verify quantity increases (not duplicate entry)
- [ ] Check cart page shows single line with combined quantity

### Error Cases
- [ ] Test without login (should redirect to login)
- [ ] Test with invalid product ID (should show error)
- [ ] Test with inactive product (should show error)
- [ ] Test with network disconnected (should show network error)

**Pass Criteria:** All tests pass with appropriate feedback

---

## 2. Cart Management Tests

### Update Quantity
- [ ] Navigate to cart page
- [ ] Increase quantity using input
- [ ] Verify cart total updates
- [ ] Verify success toast appears
- [ ] Decrease quantity
- [ ] Verify cart total updates correctly

### Stock Limit Validation
- [ ] Try updating quantity beyond stock
- [ ] Verify error message appears
- [ ] Verify quantity not updated
- [ ] Check that cart remains valid

### Remove Item
- [ ] Click remove button on cart item
- [ ] Verify confirmation dialog appears
- [ ] Confirm removal
- [ ] Verify success toast
- [ ] Verify item removed from cart
- [ ] Verify cart total updates
- [ ] Verify cart count badge updates

### Clear Cart
- [ ] Add multiple items to cart
- [ ] Click "Clear Cart" button
- [ ] Verify confirmation dialog
- [ ] Confirm clear
- [ ] Verify all items removed
- [ ] Verify cart count shows 0
- [ ] Verify "Cart is empty" message displays

### Empty Cart
- [ ] Remove all items individually
- [ ] Verify empty cart message
- [ ] Verify checkout button disabled or hidden
- [ ] Verify "Continue Shopping" link present

**Pass Criteria:** All cart operations work correctly with proper feedback

---

## 3. Wishlist Tests

### Add to Wishlist
- [ ] Navigate to product page
- [ ] Click "Add to Wishlist" button
- [ ] Verify success toast appears
- [ ] Verify button text changes to "Remove from Wishlist" or shows heart icon
- [ ] Navigate to wishlist page
- [ ] Verify product appears in wishlist

### Remove from Wishlist
- [ ] Click "Remove from Wishlist" button
- [ ] Verify success toast appears
- [ ] Verify button text changes back
- [ ] Refresh wishlist page
- [ ] Verify product no longer in wishlist

### Toggle Wishlist
- [ ] Add to wishlist
- [ ] Remove from wishlist
- [ ] Add again
- [ ] Verify works correctly each time

### Wishlist Page
- [ ] Navigate to /wishlist.php
- [ ] Verify all wishlisted products display
- [ ] Verify product images load
- [ ] Verify product prices display
- [ ] Verify "Add to Cart" buttons present
- [ ] Click "Add to Cart" from wishlist
- [ ] Verify item added to cart

### Error Cases
- [ ] Test without login (should redirect)
- [ ] Test with invalid product (should error)
- [ ] Try adding duplicate (should show already in wishlist)

**Pass Criteria:** Wishlist operations work seamlessly

---

## 4. Watchlist Tests

### Add to Watchlist
- [ ] Navigate to product page
- [ ] Click "Watch" or "Add to Watchlist" button
- [ ] Verify success toast appears
- [ ] Verify button text changes to "Watching"
- [ ] Navigate to watchlist page
- [ ] Verify product appears in watchlist

### Remove from Watchlist
- [ ] Click "Remove from Watchlist" button
- [ ] Verify success toast appears
- [ ] Verify button text changes back
- [ ] Refresh watchlist page
- [ ] Verify product no longer in watchlist

### Watchlist Page
- [ ] Navigate to /watchlist.php
- [ ] Verify all watched products display
- [ ] Verify product details accurate
- [ ] Test actions available (add to cart, wishlist, etc.)

### Duplicate Prevention
- [ ] Try adding same product twice
- [ ] Verify duplicate prevention works
- [ ] Verify appropriate error message

**Pass Criteria:** Watchlist functionality mirrors wishlist quality

---

## 5. Buy It Now Tests

### Basic Buy It Now
- [ ] Navigate to product page
- [ ] Set quantity to 1
- [ ] Click "Buy It Now"
- [ ] Verify success toast appears
- [ ] Verify redirect to checkout
- [ ] Verify product in cart on checkout page

### Quantity Selection
- [ ] Set quantity to 3
- [ ] Click "Buy It Now"
- [ ] Verify correct quantity added
- [ ] Verify shown on checkout page

### Stock Validation
- [ ] Try Buy It Now with quantity exceeding stock
- [ ] Verify error message
- [ ] Verify no redirect
- [ ] Verify cart not modified

### Out of Stock
- [ ] Try Buy It Now on out-of-stock product
- [ ] Verify error message
- [ ] Verify no redirect

### Authentication
- [ ] Logout
- [ ] Click "Buy It Now"
- [ ] Verify redirect to login page
- [ ] Login
- [ ] Verify redirect back to product page

**Pass Criteria:** Buy It Now provides fast path to checkout

---

## 6. Checkout Tests

### Checkout Access
- [ ] Navigate to /checkout.php with empty cart
- [ ] Verify redirect to cart page with error message
- [ ] Add items to cart
- [ ] Navigate to /checkout.php
- [ ] Verify checkout page loads

### Cart Validation on Checkout
- [ ] Add product to cart
- [ ] In another tab, decrease product stock to 0
- [ ] Try to checkout
- [ ] Verify validation prevents checkout
- [ ] Verify error message explains issue

### Product Validation
- [ ] Add product to cart
- [ ] In database, set product status to 'inactive'
- [ ] Try to checkout
- [ ] Verify checkout blocked
- [ ] Verify error message
- [ ] Verify product removed from cart

### Complete Checkout
- [ ] Fill in shipping address
- [ ] Fill in billing address
- [ ] Select payment method
- [ ] Click "Place Order"
- [ ] Verify order created
- [ ] Verify redirect to confirmation page
- [ ] Verify cart cleared
- [ ] Verify stock decreased

### Order Confirmation
- [ ] Verify order number displayed
- [ ] Verify order total correct
- [ ] Verify items listed correctly
- [ ] Verify confirmation email sent (check logs)

### Error Handling
- [ ] Try checkout with invalid payment method
- [ ] Verify appropriate error message
- [ ] Verify order not created
- [ ] Verify cart not cleared

**Pass Criteria:** Complete checkout process works end-to-end

---

## 7. Progressive Enhancement Tests

### JavaScript Disabled
- [ ] Disable JavaScript in browser
- [ ] Navigate to product page
- [ ] Verify Add to Cart button still visible
- [ ] Click "Add to Cart"
- [ ] Verify form submission works
- [ ] Verify redirect or page reload
- [ ] Verify item in cart

### Network Errors
- [ ] Use browser DevTools to simulate offline
- [ ] Try Add to Cart
- [ ] Verify network error message
- [ ] Reconnect network
- [ ] Verify subsequent actions work

### Slow Network
- [ ] Throttle network to slow 3G
- [ ] Click "Add to Cart"
- [ ] Verify loading state shows
- [ ] Verify button disabled during load
- [ ] Verify success after completion

**Pass Criteria:** Functionality gracefully degrades

---

## 8. UX and Accessibility Tests

### Loading States
- [ ] Click any action button
- [ ] Verify button shows loading state
- [ ] Verify button disabled during operation
- [ ] Verify button returns to normal after completion

### Notifications
- [ ] Verify success toasts are green/positive
- [ ] Verify error toasts are red/negative
- [ ] Verify warning toasts are yellow/amber
- [ ] Verify toasts auto-dismiss after few seconds
- [ ] Verify toasts can be manually closed

### Keyboard Navigation
- [ ] Tab through product page controls
- [ ] Verify all buttons are focusable
- [ ] Press Enter on focused "Add to Cart"
- [ ] Verify action triggered
- [ ] Test with screen reader (optional)

### Mobile Experience
- [ ] Test on mobile viewport (or real device)
- [ ] Verify buttons are touch-friendly
- [ ] Verify quantity input works on mobile
- [ ] Verify toasts visible on mobile
- [ ] Verify no horizontal scroll

**Pass Criteria:** Excellent UX across all devices

---

## 9. Security Tests

### CSRF Protection
- [ ] Open browser DevTools
- [ ] Submit cart action without CSRF token
- [ ] Verify request rejected
- [ ] Verify error message

### Authentication
- [ ] Logout
- [ ] Try accessing /api/cart.php directly
- [ ] Verify 401 Unauthorized response
- [ ] Try accessing /checkout.php
- [ ] Verify redirect to login

### SQL Injection
- [ ] Try product_id with SQL injection: `1' OR '1'='1`
- [ ] Verify properly escaped
- [ ] Verify no SQL error
- [ ] Try in quantity field
- [ ] Verify validation rejects invalid input

**Pass Criteria:** All security measures effective

---

## 10. Performance Tests

### Page Load
- [ ] Clear cache
- [ ] Load product page
- [ ] Verify page loads in < 3 seconds
- [ ] Check Network tab for issues
- [ ] Verify images lazy load

### API Response Times
- [ ] Monitor Network tab
- [ ] Click "Add to Cart"
- [ ] Verify API responds in < 500ms
- [ ] Test with multiple concurrent requests
- [ ] Verify no significant slowdown

### Cart Count Updates
- [ ] Add 5 items to cart quickly
- [ ] Verify count updates each time
- [ ] Verify no race conditions
- [ ] Verify final count is accurate

**Pass Criteria:** Performance is acceptable

---

## 11. Edge Cases

### Multiple Tabs
- [ ] Open product in two tabs
- [ ] Add to cart in tab 1
- [ ] Add to cart in tab 2
- [ ] Check cart page
- [ ] Verify quantity correctly combined

### Session Timeout
- [ ] Login
- [ ] Wait for session timeout (or manually expire)
- [ ] Try Add to Cart
- [ ] Verify redirect to login
- [ ] Login again
- [ ] Verify can continue

### Invalid Product ID
- [ ] Navigate to /product.php?id=99999
- [ ] Verify 404 error page
- [ ] Try Add to Cart via API with invalid ID
- [ ] Verify appropriate error

### Zero Quantity
- [ ] Try adding with quantity = 0
- [ ] Verify validation prevents
- [ ] Try negative quantity
- [ ] Verify validation prevents

**Pass Criteria:** All edge cases handled gracefully

---

## Test Results Summary

**Date:** _______________  
**Tester:** _______________  
**Environment:** _______________

### Results
- Total Tests: _____ / _____
- Passed: _____
- Failed: _____
- Skipped: _____

### Critical Issues
1. _______________________________________________
2. _______________________________________________
3. _______________________________________________

### Minor Issues
1. _______________________________________________
2. _______________________________________________
3. _______________________________________________

### Recommendations
1. _______________________________________________
2. _______________________________________________
3. _______________________________________________

### Sign-Off
- [ ] All critical functionality working
- [ ] Security measures validated
- [ ] Performance acceptable
- [ ] UX meets standards
- [ ] Ready for production

**Tester Signature:** _______________  
**Date:** _______________

---

## Browser Compatibility Matrix

| Feature | Chrome | Firefox | Safari | Edge | Mobile |
|---------|--------|---------|--------|------|--------|
| Add to Cart | ☐ | ☐ | ☐ | ☐ | ☐ |
| Update Cart | ☐ | ☐ | ☐ | ☐ | ☐ |
| Wishlist | ☐ | ☐ | ☐ | ☐ | ☐ |
| Watchlist | ☐ | ☐ | ☐ | ☐ | ☐ |
| Buy It Now | ☐ | ☐ | ☐ | ☐ | ☐ |
| Checkout | ☐ | ☐ | ☐ | ☐ | ☐ |
| Toast Notifications | ☐ | ☐ | ☐ | ☐ | ☐ |

---

## Automated Test Coverage

- [ ] Integration tests passing: tests/PurchaseFlowsIntegrationTest.php
- [ ] Code validation passing: tests/EcommerceCodeValidation.php
- [ ] All PHP syntax valid
- [ ] No console errors in browser

---

## Notes and Observations

```
[Space for additional notes during testing]






```

---

Last Updated: 2024-01-15
Version: 1.0.0

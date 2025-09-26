<?php
/**
 * Shopping Cart Page (Revised)
 * Ensures no auto-opening side cart drawer logic leaks here; page-specific only.
 */

require_once __DIR__ . '/includes/init.php';

Session::requireLogin();

$userId     = Session::getUserId();
$cart       = new Cart();
$cartItems  = $cart->getCartItems($userId);

// Totals
$subtotal = 0.0;
foreach ($cartItems as $item) {
    $subtotal += ($item['price'] * $item['quantity']);
}

$taxRate         = 8.25; // Could be pulled from system settings
$taxAmount       = $subtotal * ($taxRate / 100);
$shippingCutoff  = 50.00;
$shippingAmount  = $subtotal >= $shippingCutoff ? 0.00 : 9.99;
$total           = $subtotal + $taxAmount + $shippingAmount;

$page_title = 'Shopping Cart';
includeHeader($page_title);
?>
<div class="container" data-page-context="cart-page">
    <h1 class="mb-4">Shopping Cart</h1>

    <?php if (empty($cartItems)): ?>
        <div class="text-center" style="padding:3rem;">
            <div style="font-size:4rem;margin-bottom:1rem;">🛒</div>
            <h3>Your cart is empty</h3>
            <p class="text-muted mb-3">Looks like you haven't added anything yet.</p>
            <a href="/products.php" class="btn btn-lg">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cart Items -->
            <div class="col-8">
                <div class="card">
                    <div class="card-body p-0">
                        <?php foreach ($cartItems as $item): 
                            $lineTotal = $item['price'] * $item['quantity'];
                            $maxQty    = (int)$item['stock_quantity'];
                        ?>
                        <div class="cart-item d-flex align-items-center" style="padding:1.25rem 1.25rem; border-bottom:1px solid #eee;">
                            <div class="item-image" style="width:92px;height:92px;flex-shrink:0;margin-right:1rem;">
                                <img src="<?php echo getProductImageUrl($item['product_image']); ?>"
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
                            </div>

                            <div class="item-info flex-grow-1" style="min-width:220px;">
                                <h4 style="margin:0 0 .4rem; font-size:16px; line-height:1.25;">
                                    <a href="/product.php?id=<?php echo $item['product_id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h4>
                                <div class="text-muted" style="font-size:.8rem;">
                                    Vendor: <?php echo htmlspecialchars($item['vendor_name'] ?? 'Unknown'); ?>
                                    &nbsp;|&nbsp; SKU: <?php echo htmlspecialchars($item['sku']); ?>
                                </div>
                                <div style="margin-top:.5rem;font-weight:600;">
                                    <?php echo formatPrice($item['price']); ?>
                                </div>
                            </div>

                            <div class="item-qty" style="margin:0 1rem;text-align:center;">
                                <label for="qty_<?php echo $item['product_id']; ?>" style="display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.5px;color:#666;margin-bottom:.35rem;">Qty</label>
                                <div class="d-flex align-items-center" style="gap:.4rem;">
                                    <button type="button"
                                            class="btn btn-sm btn-outline qty-decrease"
                                            data-product-id="<?php echo $item['product_id']; ?>"
                                            aria-label="Decrease quantity">−</button>
                                    <input type="number"
                                           id="qty_<?php echo $item['product_id']; ?>"
                                           class="form-control quantity-input"
                                           data-product-id="<?php echo $item['product_id']; ?>"
                                           value="<?php echo (int)$item['quantity']; ?>"
                                           min="1"
                                           max="<?php echo $maxQty; ?>"
                                           style="width:70px;padding:.35rem .5rem;text-align:center;">
                                    <button type="button"
                                            class="btn btn-sm btn-outline qty-increase"
                                            data-product-id="<?php echo $item['product_id']; ?>"
                                            aria-label="Increase quantity">+</button>
                                </div>
                                <small class="text-muted" style="display:block;margin-top:.35rem;"><?php echo $maxQty; ?> avail.</small>
                            </div>

                            <div class="item-line-total" style="width:110px;text-align:right;font-weight:600;">
                                <span class="line-total" data-product-id="<?php echo $item['product_id']; ?>">
                                    <?php echo formatPrice($lineTotal); ?>
                                </span>
                            </div>

                            <div class="item-actions" style="margin-left:1rem;">
                                <button class="btn btn-sm btn-danger remove-from-cart"
                                        data-product-id="<?php echo $item['product_id']; ?>">
                                    Remove
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-3 d-flex" style="gap:.75rem;">
                    <a href="/products.php" class="btn btn-outline">Continue Shopping</a>
                    <button id="clear-cart" class="btn btn-outline btn-danger">Clear Cart</button>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title" style="margin-top:0;">Order Summary</h3>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <span class="cart-subtotal" data-subtotal="<?php echo number_format($subtotal, 2, '.', ''); ?>">
                                <?php echo formatPrice($subtotal); ?>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Tax (<?php echo $taxRate; ?>%):</span>
                            <span class="cart-tax" data-tax-rate="<?php echo $taxRate; ?>">
                                <?php echo formatPrice($taxAmount); ?>
                            </span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span>Shipping:</span>
                            <span class="cart-shipping" data-threshold="<?php echo number_format($shippingCutoff, 2, '.', ''); ?>">
                                <?php if ($shippingAmount > 0): ?>
                                    <span data-shipping-amount="<?php echo number_format($shippingAmount, 2, '.', ''); ?>">
                                        <?php echo formatPrice($shippingAmount); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-success" data-shipping-amount="0.00">FREE</span>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if ($subtotal < $shippingCutoff && $subtotal > 0): ?>
                            <div class="alert alert-info mb-3" style="font-size:.8rem;" data-free-shipping-msg>
                                Add <?php echo formatPrice($shippingCutoff - $subtotal); ?> more for free shipping!
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success mb-3 d-none" style="font-size:.8rem;" data-free-shipping-msg></div>
                        <?php endif; ?>

                        <hr>

                        <div class="d-flex justify-content-between mb-4" style="font-size:1.25rem;font-weight:700;">
                            <span>Total:</span>
                            <span class="cart-total" data-total="<?php echo number_format($total, 2, '.', ''); ?>">
                                <?php echo formatPrice($total); ?>
                            </span>
                        </div>

                        <a href="/checkout.php" class="btn btn-lg btn-success w-100">
                            Proceed to Checkout
                        </a>

                        <div class="mt-3 text-center">
                            <small class="text-muted">
                                <i>🔒 Secure Checkout</i><br>
                                SSL encrypted and safe
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Recommended Products -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h4 style="margin-top:0;">You might also like</h4>
                        <div id="cart-recommendations">
                            <p class="text-muted mb-0">Loading recommendations...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
/**
 * Cart Page JS (self-contained; no side drawer open logic here).
 * Uses unified /api/cart.php endpoint.
 */
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('cart-recommendations')) {
        loadCartRecommendations();
    }
    bindQuantityControls();
    bindRemoval();
    bindClearCart();
});

function apiCart(action, payload = {}) {
    return fetch('/api/cart.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(Object.assign({action}, payload))
    }).then(r => r.json());
}

function bindQuantityControls() {
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', onQuantityChange);
        input.addEventListener('input', () => {
            if (parseInt(input.value, 10) < 1) input.value = 1;
        });
    });

    document.querySelectorAll('.qty-decrease').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.productId;
            const input = document.getElementById('qty_' + id);
            if (!input) return;
            let val = parseInt(input.value, 10);
            if (val > 1) {
                input.value = val - 1;
                input.dispatchEvent(new Event('change'));
            }
        });
    });

    document.querySelectorAll('.qty-increase').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.productId;
            const input = document.getElementById('qty_' + id);
            if (!input) return;
            let val = parseInt(input.value, 10);
            const max = parseInt(input.getAttribute('max'), 10);
            if (isNaN(max) || val < max) {
                input.value = val + 1;
                input.dispatchEvent(new Event('change'));
            }
        });
    });
}

let qtyUpdateQueue = {};
function onQuantityChange(e) {
    const input = e.target;
    const productId = input.dataset.productId;
    const quantity = parseInt(input.value, 10);
    if (!productId || quantity < 1) return;

    // Debounce rapid changes per product
    clearTimeout(qtyUpdateQueue[productId]);
    qtyUpdateQueue[productId] = setTimeout(() => {
        apiCart('update', {product_id: parseInt(productId, 10), quantity})
            .then(data => {
                if (data.success) {
                    recalcLine(productId, quantity);
                    refreshTotals();
                } else {
                    alert(data.message || 'Failed to update quantity');
                }
            })
            .catch(() => alert('Error updating cart'));
    }, 350);
}

function recalcLine(productId, quantity) {
    const input = document.getElementById('qty_' + productId);
    if (!input) return;
    // Price is not embedded per input, derive from line total or initial price element
    // We stored original unit price implicitly: find line total span and recalc from previous value if needed.
    const lineSpan = document.querySelector('.line-total[data-product-id="'+productId+'"]');
    if (!lineSpan) return;

    // Extract unit price from sibling info (safer: embed data attribute)
    if (!lineSpan.dataset.unitPrice) {
        // Attempt to derive (line total / old qty) only once
        const currentDisplayed = parseFloat(lineSpan.textContent.replace(/[^0-9.]/g,'') || '0');
        const previousQty = parseInt(input.getAttribute('data-prev-qty') || quantity, 10) || 1;
        const unit = previousQty ? (currentDisplayed / previousQty) : currentDisplayed;
        lineSpan.dataset.unitPrice = unit.toFixed(2);
    }

    const unitPrice = parseFloat(lineSpan.dataset.unitPrice);
    const newLineTotal = unitPrice * quantity;

    lineSpan.textContent = formatCurrency(newLineTotal);
    input.setAttribute('data-prev-qty', quantity.toString());
}

function bindRemoval() {
    document.querySelectorAll('.remove-from-cart').forEach(btn => {
        btn.addEventListener('click', () => {
            const productId = btn.dataset.productId;
            if (!productId) return;
            if (!confirm('Remove this item from the cart?')) return;
            apiCart('remove', {product_id: parseInt(productId, 10)})
                .then(data => {
                    if (data.success) {
                        const wrapper = btn.closest('.cart-item');
                        if (wrapper) wrapper.remove();
                        if (!document.querySelector('.cart-item')) {
                            location.reload(); // Simpler: fallback to empty cart view
                        } else {
                            refreshTotals();
                        }
                    } else {
                        alert(data.message || 'Failed to remove item');
                    }
                })
                .catch(() => alert('Error removing item'));
        });
    });
}

function bindClearCart() {
    const clearBtn = document.getElementById('clear-cart');
    if (!clearBtn) return;
    clearBtn.addEventListener('click', () => {
        if (!confirm('Clear all items from your cart?')) return;
        apiCart('clear')
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to clear cart');
                }
            })
            .catch(() => alert('Error clearing cart'));
    });
}

function refreshTotals() {
    // Recompute subtotal from line totals
    let newSubtotal = 0;
    document.querySelectorAll('.line-total').forEach(span => {
        const val = parseFloat(span.textContent.replace(/[^0-9.]/g,'') || '0');
        newSubtotal += val;
    });

    const subtotalEl = document.querySelector('.cart-subtotal');
    const taxEl      = document.querySelector('.cart-tax');
    const shippingEl = document.querySelector('.cart-shipping [data-shipping-amount], .cart-shipping[data-shipping-amount]');
    const totalEl    = document.querySelector('.cart-total');
    const freeMsg    = document.querySelector('[data-free-shipping-msg]');
    const shippingWrapper = document.querySelector('.cart-shipping');

    if (!subtotalEl || !taxEl || !totalEl) return;

    const taxRate = parseFloat(taxEl.getAttribute('data-tax-rate')) || 0;
    const threshold = parseFloat(shippingWrapper?.getAttribute('data-threshold')) || 50;
    let shippingAmt = newSubtotal >= threshold ? 0 : 9.99;

    subtotalEl.textContent = formatCurrency(newSubtotal);
    subtotalEl.setAttribute('data-subtotal', newSubtotal.toFixed(2));

    const taxAmount = newSubtotal * (taxRate / 100);
    taxEl.textContent = formatCurrency(taxAmount);

    if (shippingEl) {
        shippingEl.textContent = shippingAmt === 0 ? 'FREE' : formatCurrency(shippingAmt);
        shippingEl.setAttribute('data-shipping-amount', shippingAmt.toFixed(2));
        if (shippingAmt === 0) {
            shippingEl.classList.add('text-success');
        } else {
            shippingEl.classList.remove('text-success');
        }
    }

    if (freeMsg) {
        if (newSubtotal === 0) {
            freeMsg.classList.add('d-none');
        } else if (newSubtotal < threshold) {
            freeMsg.classList.remove('d-none','alert-success');
            freeMsg.classList.add('alert-info');
            freeMsg.textContent = 'Add ' + formatCurrency(threshold - newSubtotal) + ' more for free shipping!';
        } else {
            freeMsg.classList.remove('d-none','alert-info');
            freeMsg.classList.add('alert-success');
            freeMsg.textContent = 'You have unlocked free shipping!';
        }
    }

    const grandTotal = newSubtotal + taxAmount + shippingAmt;
    totalEl.textContent = formatCurrency(grandTotal);
    totalEl.setAttribute('data-total', grandTotal.toFixed(2));
}

function formatCurrency(amount) {
    return '$' + (amount || 0).toFixed(2);
}

function loadCartRecommendations() {
    fetch('/api/recommendations.php?type=cart')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('cart-recommendations');
            if (!container) return;
            if (data.success && Array.isArray(data.data) && data.data.length) {
                container.innerHTML = data.data.map(product => {
                    const price = product.price || '';
                    const img = product.image_url || '/images/placeholder-product.png';
                    return `
                        <div style="display:flex;align-items:center;margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid #eee;">
                            <img src="${img}" alt="${escapeHTML(product.name)}"
                                 style="width:60px;height:60px;object-fit:cover;border-radius:4px;margin-right:1rem;">
                            <div style="flex:1;">
                                <h6 style="margin:0 0 .3rem;font-size:.85rem;">
                                    <a href="/product.php?id=${product.id}">${escapeHTML(product.name)}</a>
                                </h6>
                                <p style="margin:0 0 .5rem;font-weight:600;">${price}</p>
                                <button class="btn btn-sm add-reco-to-cart" data-product-id="${product.id}">
                                    Add
                                </button>
                            </div>
                        </div>`;
                }).join('');
                bindRecommendationAdd();
            } else {
                container.innerHTML = '<p class="text-muted mb-0">No recommendations available.</p>';
            }
        })
        .catch(() => {
            const container = document.getElementById('cart-recommendations');
            if (container) container.innerHTML = '<p class="text-muted mb-0">Unable to load recommendations.</p>';
        });
}

function bindRecommendationAdd() {
    document.querySelectorAll('.add-reco-to-cart').forEach(btn => {
        btn.addEventListener('click', () => {
            const productId = parseInt(btn.dataset.productId, 10);
            if (!productId) return;
            btn.disabled = true;
            btn.textContent = 'Adding...';
            apiCart('add', {product_id: productId, quantity: 1})
                .then(data => {
                    if (data.success) {
                        btn.textContent = 'Added!';
                        setTimeout(() => {
                            btn.disabled = false;
                            btn.textContent = 'Add';
                        }, 1500);
                        // Optionally refresh totals via separate fetch if needed
                        // For now, we won't auto-inject item into main list without full reload.
                    } else {
                        btn.disabled = false;
                        btn.textContent = 'Add';
                        alert(data.message || 'Failed to add item');
                    }
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.textContent = 'Add';
                    alert('Error adding item');
                });
        });
    });
}

function escapeHTML(str) {
    return (str || '').replace(/[&<>"']/g, ch => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
    }[ch]));
}
</script>

<?php includeFooter(); ?>
# Homepage Product Curation and Category Page Layout Fix

## Summary
This fix addresses two critical issues in the e-commerce platform:
1. Homepage product sections were displaying repetitive products
2. Category page had a layout bug where products overlapped the header

## Changes Made

### Issue 1: Homepage Product Curation ✅

**Problem:** All product carousels on the homepage were showing the same products because they all used `fetchRealProducts()` which simply queries by `created_at DESC`.

**Solution:** Implemented specialized product fetching for each section:

#### Modified Files:
- `index.php` - Updated product fetching to use curated helper functions
- `includes/template-helpers.php` - Enhanced `getTrendingProducts()` to use sales-based ranking

#### Changes:

1. **Flash Deals Section**
   - **Before:** Used `fetchRealProducts(12)` (just newest products)
   - **After:** Uses `get_deals_section(12)` which queries products with `compare_price > price` ordered by `discount_percent DESC`
   - **Result:** Shows products with actual discounts, sorted by highest discount

2. **Trending Products Section**
   - **Before:** Used `fetchRealProducts(10)` (just newest products)
   - **After:** Uses `get_trending_products(10)` with enhanced query
   - **Query Logic:** 
     ```sql
     SELECT p.*, SUM(oi.quantity) AS sold
     FROM products p
     LEFT JOIN order_items oi ON oi.product_id = p.id
     LEFT JOIN orders o ON o.id = oi.order_id 
       AND o.created_at >= date('now', '-7 days')
       AND o.status IN ('paid','shipped','delivered')
     GROUP BY p.id
     ORDER BY sold DESC, p.created_at DESC
     ```
   - **Result:** Shows products with most sales in the last 7 days

3. **Halloween/New Arrivals Section**
   - **Before:** Reused `array_slice($trending_products, 0, 6)` (duplicate products)
   - **After:** Uses `get_new_arrivals(6)` which gets newest products
   - **Result:** Shows unique newest products, different from trending

4. **Furniture Section**
   - **Before:** Used generic `fetchRealProducts(10, 4)`
   - **After:** Uses `get_furniture_section_content()` with category-specific logic
   - **Result:** Properly curated furniture category products

### Issue 2: Category Page Layout Bug ✅

**Problem:** The `.category-content` CSS rule had `position: absolute` which was meant for category cards on the homepage but was also affecting the category.php page, causing products to overlap the header.

**Solution:** Scoped CSS rules appropriately to different contexts.

#### Modified Files:
- `css/styles.css` - Fixed conflicting CSS selectors

#### Changes:

**Before:**
```css
.category-content {
    position: absolute;
    bottom: 20px;
    left: 20px;
    right: 20px;
    color: white;
    z-index: 2;
}
```

**After:**
```css
/* Category card content overlay (for homepage category cards) */
.category-card .category-content {
    position: absolute;
    bottom: 20px;
    left: 20px;
    right: 20px;
    color: white;
    z-index: 2;
}

/* Category page main content area (for category.php page) */
.container > .category-content {
    position: relative;
    display: flex;
    gap: 2rem;
    margin-top: 2rem;
    z-index: 1;
}
```

**Result:** 
- Homepage category cards maintain their overlay styling
- Category page layout uses proper flexbox layout without overlapping header
- Header z-index (1000) properly sits above content z-index (1)

## Technical Details

### Product Query Improvements

The `getTrendingProducts()` function now:
1. Joins with `order_items` and `orders` tables
2. Filters orders from last 7 days with status 'paid', 'shipped', or 'delivered'
3. Sums quantity sold per product
4. Orders by sales volume DESC, then by creation date
5. Falls back to newest products if no sales data exists

### CSS Specificity Fix

The issue was CSS selector collision where a single `.category-content` rule applied to both:
- Homepage category card overlays (needs `position: absolute`)
- Category page main content container (needs `position: relative`)

By using more specific selectors (`.category-card .category-content` and `.container > .category-content`), we resolved the conflict.

## Testing

### Verification Steps:

1. **Homepage Product Diversity**
   - Visit homepage
   - Compare products in "Flash Deals", "Trending", and "New Arrivals" sections
   - Verify each section shows different products
   - Verify "Flash Deals" shows products with discount badges

2. **Category Page Layout**
   - Visit any category page (e.g., `/category.php?name=electronics`)
   - Verify header is visible and not covered by products
   - Verify products grid displays below header
   - Verify sidebar and products section are side-by-side

### Expected Results:

✅ Each homepage section displays unique, curated products
✅ Flash Deals shows discounted products with highest discounts first
✅ Trending shows products with most recent sales
✅ Category page header remains visible above all content
✅ Category page layout uses proper flex display

## Files Modified

1. `css/styles.css` - 16 lines changed
2. `includes/template-helpers.php` - 22 lines changed  
3. `index.php` - 18 lines changed

**Total:** 56 lines changed across 3 files

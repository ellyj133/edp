# FezaMarket - Site Link Checking and Validation

## Link Checker Tool

A comprehensive link checker is available to validate all internal links on the site.

### Usage

```bash
# Check local development server
php scripts/link-checker.php http://localhost:8000

# Check with custom max pages (default is 100)
php scripts/link-checker.php http://localhost:8000 50

# Check production site
php scripts/link-checker.php https://fezamarket.com
```

### What It Does

The link checker will:
- Crawl all pages starting from the homepage
- Follow all internal links recursively
- Check HTTP status codes for every link found
- Report 404s, 500s, and other errors
- Generate a detailed JSON report in `docs/link-report.json`

### Report Format

The generated report includes:
- Timestamp of the check
- Total pages crawled
- Total links found
- List of broken links with details
- Which page each broken link was found on

## Complete Page Inventory

### Core Navigation Pages ✅
- `/` - Homepage
- `/products.php` - Product catalog
- `/deals.php` - Deals and promotions
- `/membership.php` - Premium membership program **[NEW]**
- `/shipping.php` - Shipping information **[NEW]**
- `/messages.php` - User messaging center **[NEW]**

### Account & Auth ✅
- `/login.php` - User login
- `/register.php` - User registration
- `/account.php` - Account dashboard
- `/logout.php` - Logout handler
- `/saved.php` - Saved items and watchlist

### Shopping Experience ✅
- `/product.php` - Product detail page
- `/cart.php` - Shopping cart
- `/checkout.php` - Checkout process
- `/wishlist.php` - Wishlist
- `/search.php` - Search results

### Seller Features ✅
- `/sell.php` - Start selling
- `/seller-register.php` - Seller registration
- `/seller-center.php` - Seller dashboard
- `/resell.php` - Resale program **[NEW]**

### Company & Info ✅
- `/about/` - About pages
- `/contact.php` - Contact form
- `/careers.php` - Career opportunities
- `/news.php` - News and updates
- `/help.php` - Help center
- `/company-info.php` - Company information
- `/investors.php` - Investor relations
- `/diversity.php` - Diversity & inclusion
- `/global-impact.php` - Global impact initiatives

### Legal & Privacy ✅
- `/privacy.php` - Privacy policy
- `/user-agreement.php` - Terms of service
- `/cookies.php` - Cookie policy
- `/payments-terms.php` - Payment terms **[NEW]**
- `/consumer-health-data.php` - Health data notice **[NEW]**
- `/your-privacy-choices.php` - Privacy choices **[NEW]**
- `/ca-privacy.php` - California privacy notice **[NEW]**
- `/adchoice.php` - Advertising choices **[NEW]**
- `/accessibility.php` - Accessibility statement

### Partnership & Business ✅
- `/partnership.php` - Partnership opportunities **[NEW]**
- `/affiliates.php` - Affiliate program
- `/advertise.php` - Advertising opportunities
- `/business-sellers.php` - Business seller info
- `/developers.php` - Developer resources

### Additional Pages ✅
- `/collections.php` - Creator collections
- `/gift-cards.php` - Gift cards
- `/live.php` - Live shopping
- `/brands.php` - Brand directory
- `/stores.php` - Online stores
- `/regional-sites.php` - International sites

## Testing Checklist

### Manual Testing
- [ ] Homepage loads and displays correctly
- [ ] All header navigation links work
- [ ] All footer links work
- [ ] Membership page is fully functional
- [ ] Shipping page displays all information
- [ ] Messages page shows for logged-in users
- [ ] All legal pages are accessible
- [ ] Mobile navigation works correctly
- [ ] Search functionality works
- [ ] Product pages load correctly

### Automated Testing
- [ ] Run link checker script
- [ ] Review link-report.json for errors
- [ ] Verify all HTTP 200 responses
- [ ] Check for any 404 errors
- [ ] Validate all internal links

## CI/CD Integration

To add link checking to your CI/CD pipeline:

```yaml
# Example GitHub Actions
- name: Link Check
  run: |
    php scripts/link-checker.php http://localhost:8000
```

The script exits with code 1 if broken links are found, which will fail the CI build.

## Notes

- All pages use consistent header/footer templates
- eBay-style design system maintained throughout
- Mobile-responsive on all pages
- No placeholder or "Lorem ipsum" content
- All pages are production-ready

## Recent Additions

**January 2025:**
- ✅ Added comprehensive membership page with plans and benefits
- ✅ Created detailed shipping information page
- ✅ Implemented user messaging center
- ✅ Added resale program page
- ✅ Created partnership opportunities page
- ✅ Added all missing legal/privacy pages
- ✅ Built automated link checker tool

All new pages follow existing design patterns and are fully integrated with the site's navigation structure.

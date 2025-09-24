# Admin Panel Setup and Usage Guide

## Admin Bypass Mode

The Admin Panel includes an **Admin Bypass** feature for development and testing purposes.

### Configuration

**Environment Variable (.env file):**
```bash
ADMIN_BYPASS=true  # Default: true for development
```

**PHP Configuration (config/config.php):**
```php
define('ADMIN_BYPASS', env('ADMIN_BYPASS', true));
```

### How It Works

When `ADMIN_BYPASS=true`:
- ✅ All authentication checks are bypassed for admin routes
- ✅ Admin session is automatically created
- ✅ No login required to access admin features
- ✅ Warning notice displayed in admin interface

When `ADMIN_BYPASS=false` (Production):
- ❌ Normal authentication required
- ❌ Must login with admin credentials
- ❌ Session validation enforced
- ❌ Unauthorized access redirected to login

### Security Notice

⚠️ **IMPORTANT**: Set `ADMIN_BYPASS=false` in production environments!

## Database Setup

### Schema Migration

Apply the comprehensive admin schema:

```bash
# Using MySQL/MariaDB command line
mysql -u username -p database_name < database/schema.sql

# Or using PHP migration script
php database/migrate.php
```

### Required Tables

The admin panel uses these database tables:

**Core Admin Tables:**
- `dashboard_widgets` - Dashboard widget configurations
- `kpi_daily` - Daily KPI metrics
- `system_alerts` - System alerts and notifications
- `activity_feed` - Admin activity tracking

**User Management:**
- `user_profiles` - Extended user profile data
- `user_roles` - Role definitions
- `user_logins` - Login history tracking
- `user_documents` - Document storage
- `user_audit_logs` - User action auditing

**Analytics & Reporting:**
- `fact_sales` - Sales analytics data
- `fact_users` - User analytics data
- `fact_campaigns` - Campaign analytics
- `report_jobs` - Scheduled reports

[See full schema in database/schema.sql]

## Seeding Demo Data

Load realistic demo data for testing:

```bash
# Run the seeder script
php database/seed_admin_data.php

# Or use the admin interface
curl -X POST http://localhost/admin/api/seed-data
```

## Admin Module Structure

The admin panel is organized into 21 comprehensive modules:

### 1. Dashboard
- **URL**: `/admin/`
- **Features**: KPIs, alerts, activity feeds, customizable widgets
- **Real-time**: WebSocket updates for live metrics

### 2. User Management  
- **URL**: `/admin/users/`
- **Features**: User directory, profiles, bulk operations, audit logging
- **Capabilities**: Create, suspend, activate, delete users

### 3. Roles & Permissions
- **URL**: `/admin/roles/`
- **Features**: RBAC system, permission matrix, role cloning
- **Note**: Admin Bypass overrides all permission checks

### 4. KYC & Verification
- **URL**: `/admin/kyc/`
- **Features**: Document upload, OCR processing, approval workflow
- **Integration**: Pluggable verification services

### 5. Product Management
- **URL**: `/admin/products/`
- **Features**: CRUD operations, bulk CSV upload, moderation
- **Advanced**: Variants, media management, approval workflow

### 6. Inventory Management
- **URL**: `/admin/inventory/`
- **Features**: Multi-warehouse stock tracking, adjustments, alerts
- **Automation**: Low stock notifications, reorder points

### 7. Categories & SEO
- **URL**: `/admin/categories/`
- **Features**: Tree structure, SEO metadata, URL redirects
- **Tools**: Drag-drop interface, sitemap generation

### 8. Order Management
- **URL**: `/admin/orders/`
- **Features**: Order lifecycle, partial shipments, invoice generation
- **Integration**: Shipping and payment gateways

### 9. Payment Tracking
- **URL**: `/admin/payments/`
- **Features**: Transaction monitoring, reconciliation, fraud detection
- **Reporting**: Settlement reports, gateway analytics

### 10. Payout Management
- **URL**: `/admin/payouts/`
- **Features**: Vendor wallet management, approval workflow
- **Automation**: Auto-payout rules, batch processing

### 11. Dispute Resolution
- **URL**: `/admin/disputes/`
- **Features**: Case management, evidence uploads, SLA tracking
- **Communication**: Built-in messaging system

### 12. Marketing Campaigns
- **URL**: `/admin/campaigns/`
- **Features**: Campaign builder, audience targeting, A/B testing
- **Analytics**: Performance tracking, ROI analysis

### 13. Coupons & Discounts
- **URL**: `/admin/coupons/`
- **Features**: Rules engine, usage tracking, reporting
- **Types**: Fixed amount, percentage, BOGO, free shipping

### 14. Loyalty & Rewards
- **URL**: `/admin/loyalty/`
- **Features**: Points system, tier management, expiration rules
- **Gamification**: Achievement tracking, bonus campaigns

### 15. Analytics & Reports
- **URL**: `/admin/analytics/`
- **Features**: Business intelligence, custom reports, data export
- **Visualization**: Charts, graphs, trend analysis

### 16. Custom Dashboards
- **URL**: `/admin/dashboards/`
- **Features**: Drag-drop widgets, multiple dashboards, sharing
- **Customization**: Personal and team dashboards

### 17. Content Management
- **URL**: `/admin/cms/`
- **Features**: Pages, blog posts, media library, templates
- **SEO**: Meta tags, sitemap integration

### 18. System Settings
- **URL**: `/admin/settings/`
- **Features**: General settings, localization, email configuration
- **Categories**: Grouped settings with validation

### 19. API & Integrations
- **URL**: `/admin/integrations/`
- **Features**: API key management, webhooks, third-party integrations
- **Monitoring**: Usage tracking, rate limiting

### 20. System Maintenance
- **URL**: `/admin/maintenance/`
- **Features**: Job monitoring, cache management, backup/restore
- **Health**: System status, performance metrics

### 21. Live Streaming Management
- **URL**: `/admin/streaming/`
- **Features**: Stream scheduling, chat moderation, analytics
- **RTMP**: Stream key management, viewer tracking

## Screenshot Generation

Automatically generate screenshots of all admin pages:

### Prerequisites

```bash
# Install Node.js dependencies
npm install

# Install Playwright browsers
npm run install-browsers
```

### Generate Screenshots

```bash
# Generate screenshots for all admin pages
npm run admin:screenshots

# Run with smoke tests first
node screenshot-automation.js --test
```

Screenshots are saved to `docs/screenshots/` with:
- Individual PNG files for each admin page
- README.md index with all screenshots
- Automatic timestamp and summary

### Configuration

Edit `screenshot-automation.js` to customize:

```javascript
const config = {
    baseUrl: 'http://localhost:8000',  // Your local server
    viewport: { width: 1920, height: 1080 },
    screenshotDir: './docs/screenshots',
    timeout: 30000
};
```

## E2E Testing

Run basic smoke tests to verify admin functionality:

```bash
# Run all smoke tests
npm test

# Run specific test
npx playwright test admin-smoke.spec.js

# Run with headed browser (for debugging)
npx playwright test --headed
```

### Test Coverage

- ✅ Page loading and navigation
- ✅ Admin Bypass functionality
- ✅ Responsive design
- ✅ Search functionality
- ✅ Data table rendering
- ✅ Core UI elements

## Development Workflow

### 1. Setup Environment

```bash
# Clone repository
git clone <repository-url>
cd epd

# Install PHP dependencies
composer install

# Install Node.js dependencies  
npm install

# Copy environment file
cp .env.example .env

# Configure database
# Edit .env with your database settings
```

### 2. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE ecommerce_platform"

# Apply schema
mysql -u username -p ecommerce_platform < database/schema.sql

# Seed demo data (optional)
php database/seed_admin_data.php
```

### 3. Enable Admin Bypass

In `.env` file:
```bash
ADMIN_BYPASS=true
```

### 4. Start Development Server

```bash
# PHP built-in server
php -S localhost:8000

# Or use your preferred local server (Apache, Nginx, etc.)
```

### 5. Access Admin Panel

Open: `http://localhost:8000/admin/`

The Admin Bypass notice should appear, indicating you have full access.

### 6. Generate Documentation

```bash
# Take screenshots
npm run admin:screenshots

# Run smoke tests
npm test
```

## Production Deployment

### Security Checklist

- [ ] Set `ADMIN_BYPASS=false` in production `.env`
- [ ] Configure proper admin user accounts
- [ ] Enable HTTPS for admin routes
- [ ] Set up proper database backups
- [ ] Configure email notifications
- [ ] Enable audit logging
- [ ] Set up monitoring and alerts

### Performance Optimization

- [ ] Enable database query caching
- [ ] Configure Redis for session storage
- [ ] Set up CDN for static assets
- [ ] Enable gzip compression
- [ ] Configure database indices
- [ ] Set up monitoring dashboards

## Troubleshooting

### Admin Bypass Not Working

1. Check `.env` file has `ADMIN_BYPASS=true`
2. Verify `config/config.php` loads environment correctly
3. Clear any cached configurations
4. Check PHP error logs

### Database Connection Issues

1. Verify database credentials in `.env`
2. Check database server is running
3. Ensure database exists and schema is applied
4. Test connection with: `php -r "require 'includes/db.php'; db();"`

### Screenshot Generation Fails

1. Install Playwright browsers: `npm run install-browsers`
2. Check base URL is correct and server is running
3. Verify Admin Bypass is enabled
4. Check for JavaScript errors in browser console

### Navigation Issues

1. Check all admin module directories exist
2. Verify `.htaccess` or server rewrites are configured
3. Check for PHP syntax errors: `php -l admin/*/index.php`

## Support

For additional help:

1. Check the generated screenshots for visual reference
2. Review E2E test results for functionality verification
3. Check PHP error logs for server-side issues
4. Verify database schema matches requirements

## Architecture Notes

The admin panel follows these design principles:

- **Modular Structure**: Each module is self-contained
- **Admin Bypass**: Development-friendly authentication bypass
- **Responsive Design**: Works on desktop, tablet, and mobile
- **Real-time Updates**: WebSocket support for live data
- **Audit Trail**: Comprehensive logging of admin actions
- **Role-based Access**: Granular permission system
- **API-driven**: RESTful APIs for all admin operations
- **Testing**: Automated E2E tests and screenshot generation
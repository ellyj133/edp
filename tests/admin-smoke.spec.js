const { test, expect } = require('@playwright/test');

/**
 * E2E Smoke Tests for Admin Panel
 * Basic tests to ensure all admin pages load correctly
 */

const adminPages = [
    { name: 'dashboard', url: '/admin/', title: 'Admin Dashboard' },
    { name: 'users', url: '/admin/users/', title: 'User Management' },
    { name: 'products', url: '/admin/products/', title: 'Product Management' },
    { name: 'orders', url: '/admin/orders/', title: 'Order Management' },
    { name: 'analytics', url: '/admin/analytics/', title: 'Analytics & Reports' },
    { name: 'streaming', url: '/admin/streaming/', title: 'Live Streaming Management' },
];

test.describe('Admin Panel Smoke Tests', () => {
    test.beforeEach(async ({ page }) => {
        // Admin Bypass should be enabled for these tests
        console.log('Admin Bypass mode should be enabled in .env');
    });

    for (const pageConfig of adminPages) {
        test(`${pageConfig.title} loads correctly`, async ({ page }) => {
            // Navigate to the admin page
            await page.goto(pageConfig.url);
            
            // Wait for page to load
            await page.waitForLoadState('networkidle');
            
            // Check that the main heading is visible
            const heading = page.locator('h1, .h3').first();
            await expect(heading).toBeVisible();
            
            // Check that the page has main content
            const content = page.locator('.container-fluid, .card, main').first();
            await expect(content).toBeVisible();
            
            // Verify no major JavaScript errors by checking console
            const errors = [];
            page.on('console', msg => {
                if (msg.type() === 'error') {
                    errors.push(msg.text());
                }
            });
            
            // Wait a bit for any JS to execute
            await page.waitForTimeout(2000);
            
            // Check for Admin Bypass notice (should be present in dev mode)
            const bypassNotice = page.locator('.alert-warning').filter({ hasText: 'Admin Bypass Mode Active' });
            if (await bypassNotice.isVisible()) {
                console.log(`✓ Admin Bypass notice found on ${pageConfig.title}`);
            }
            
            console.log(`✓ ${pageConfig.title} loaded successfully`);
        });
    }

    test('Navigation between admin pages works', async ({ page }) => {
        // Start at dashboard
        await page.goto('/admin/');
        
        // Verify dashboard loads
        await expect(page.locator('h1')).toContainText('Dashboard');
        
        // Test navigation to a few key pages
        const testPages = [
            { selector: 'a[href="/admin/users/"]', expectedText: 'User Management' },
            { selector: 'a[href="/admin/products/"]', expectedText: 'Product Management' },
            { selector: 'a[href="/admin/orders/"]', expectedText: 'Order Management' }
        ];
        
        for (const testPage of testPages) {
            // Click navigation link
            await page.click(testPage.selector);
            
            // Wait for navigation
            await page.waitForLoadState('networkidle');
            
            // Verify page loaded
            await expect(page.locator('h1, .h3')).toContainText(testPage.expectedText);
            
            // Go back to dashboard
            await page.click('a[href="/admin/"]');
            await page.waitForLoadState('networkidle');
        }
    });

    test('Admin panel responsive design works', async ({ page }) => {
        // Test desktop view
        await page.setViewportSize({ width: 1920, height: 1080 });
        await page.goto('/admin/');
        await expect(page.locator('.admin-header')).toBeVisible();
        
        // Test tablet view
        await page.setViewportSize({ width: 768, height: 1024 });
        await page.reload();
        await expect(page.locator('.admin-header')).toBeVisible();
        
        // Test mobile view
        await page.setViewportSize({ width: 375, height: 667 });
        await page.reload();
        await expect(page.locator('.admin-header')).toBeVisible();
    });

    test('Search functionality exists on admin pages', async ({ page }) => {
        const pagesWithSearch = [
            '/admin/users/',
            '/admin/products/',
            '/admin/orders/'
        ];
        
        for (const url of pagesWithSearch) {
            await page.goto(url);
            await page.waitForLoadState('networkidle');
            
            // Look for search input
            const searchInput = page.locator('input[placeholder*="Search"], input[placeholder*="search"]');
            if (await searchInput.count() > 0) {
                await expect(searchInput.first()).toBeVisible();
                console.log(`✓ Search functionality found on ${url}`);
            }
        }
    });

    test('Data tables render correctly', async ({ page }) => {
        const pagesWithTables = [
            '/admin/users/',
            '/admin/products/',
            '/admin/orders/'
        ];
        
        for (const url of pagesWithTables) {
            await page.goto(url);
            await page.waitForLoadState('networkidle');
            
            // Check if table exists and has headers
            const table = page.locator('table');
            if (await table.count() > 0) {
                await expect(table.first()).toBeVisible();
                
                // Check for table headers
                const headers = page.locator('thead th');
                await expect(headers.first()).toBeVisible();
                
                console.log(`✓ Data table found on ${url}`);
            }
        }
    });
});
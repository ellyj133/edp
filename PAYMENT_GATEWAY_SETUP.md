# Payment Gateway Setup Guide

This guide provides detailed instructions for setting up payment gateway integrations for the E-Commerce Platform. The platform supports Stripe, PayPal, and Mobile Momo Rwanda payment methods.

## Table of Contents
1. [Stripe Setup](#stripe-setup)
2. [PayPal Setup](#paypal-setup)
3. [Mobile Momo Rwanda Setup](#mobile-momo-rwanda-setup)
4. [Configuration](#configuration)
5. [Testing](#testing)

---

## Stripe Setup

Stripe is a popular payment processor that supports credit cards, debit cards, and various digital wallets.

### Step 1: Create a Stripe Account
1. Go to [https://stripe.com](https://stripe.com)
2. Click "Start now" or "Sign up"
3. Complete the registration process with your business information
4. Verify your email address

### Step 2: Get Your API Keys
1. Log in to your Stripe Dashboard at [https://dashboard.stripe.com](https://dashboard.stripe.com)
2. Click on "Developers" in the left sidebar
3. Click on "API keys"
4. You'll see two types of keys:
   - **Publishable key** (starts with `pk_test_` or `pk_live_`)
   - **Secret key** (starts with `sk_test_` or `sk_live_`)

### Step 3: Configure Stripe in Your Application
Add the following to your `.env` file or configuration:

```bash
# Stripe Configuration
STRIPE_SECRET_KEY=sk_test_your_secret_key_here
STRIPE_PUBLISHABLE_KEY=pk_test_your_publishable_key_here
```

**Important Security Notes:**
- Never commit your secret keys to version control
- Use test keys (`sk_test_` and `pk_test_`) during development
- Switch to live keys (`sk_live_` and `pk_live_`) only in production
- The secret key should never be exposed to the client-side code

### Step 4: Enable Payment Methods
1. In the Stripe Dashboard, go to "Settings" > "Payment methods"
2. Enable the payment methods you want to accept (cards, Apple Pay, Google Pay, etc.)

---

## PayPal Setup

PayPal is a widely-used digital payment platform that allows customers to pay using their PayPal account or cards.

### Step 1: Create a PayPal Business Account
1. Go to [https://www.paypal.com/business](https://www.paypal.com/business)
2. Click "Sign Up" and select "Business Account"
3. Complete the registration process
4. Verify your email and add bank account information

### Step 2: Create REST API Credentials
1. Log in to your PayPal account
2. Go to [https://developer.paypal.com](https://developer.paypal.com)
3. Click "Dashboard" in the top right
4. Click "My Apps & Credentials"
5. Switch to "Sandbox" tab for testing or "Live" tab for production
6. Click "Create App"
7. Give your app a name and click "Create App"

### Step 3: Get Your API Credentials
After creating your app, you'll see:
- **Client ID** - Your public identifier
- **Secret** - Your private key (click "Show" to reveal it)

### Step 4: Configure PayPal in Your Application
Add the following to your `.env` file or configuration:

```bash
# PayPal Configuration
PAYPAL_CLIENT_ID=your_client_id_here
PAYPAL_CLIENT_SECRET=your_client_secret_here
PAYPAL_MODE=sandbox  # Use 'sandbox' for testing, 'live' for production
```

**Important Notes:**
- Use Sandbox credentials for testing
- Never expose your Client Secret in client-side code
- You need to enable specific payment methods in your PayPal account settings

### Step 5: Configure Webhooks (Optional but Recommended)
1. In the PayPal Developer Dashboard, go to your app
2. Scroll down to "Webhooks"
3. Click "Add Webhook"
4. Enter your webhook URL: `https://yourdomain.com/api/paypal-webhook.php`
5. Select the events you want to receive notifications for

---

## Mobile Momo Rwanda Setup

Mobile Momo (MTN Mobile Money) is the most popular mobile payment method in Rwanda.

### Step 1: Register as an MTN Mobile Money Merchant
1. Contact MTN Rwanda Business Services:
   - Email: business@mtn.co.rw
   - Phone: +250 788 180 000
   - Website: [https://www.mtn.co.rw/business/mobile-money/](https://www.mtn.co.rw/business/mobile-money/)
2. Submit required business documentation:
   - Business registration certificate
   - Tax identification number (TIN)
   - ID of business owner/representative
   - Bank account details

### Step 2: Get API Access
1. Register for MTN Developer Portal at [https://momodeveloper.mtn.com](https://momodeveloper.mtn.com)
2. Create an account and complete verification
3. Subscribe to the "Collection" product (for receiving payments)
4. Create an API User:
   ```bash
   POST /v1_0/apiuser
   ```

### Step 3: Get Your API Credentials
After approval, you'll receive:
- **API Key** (Subscription Key / Ocp-Apim-Subscription-Key)
- **API Secret** (User Secret)
- **Merchant ID** (API User ID)

### Step 4: Configure Mobile Momo in Your Application
Add the following to your `.env` file or configuration:

```bash
# Mobile Momo Rwanda Configuration
MOBILE_MOMO_API_KEY=your_subscription_key_here
MOBILE_MOMO_API_SECRET=your_api_secret_here
MOBILE_MOMO_MERCHANT_ID=your_api_user_id_here
MOBILE_MOMO_ENVIRONMENT=sandbox  # Use 'sandbox' for testing, 'production' for live
```

### Step 5: Test in Sandbox Environment
1. Use MTN's sandbox environment for testing
2. Sandbox API Base URL: `https://sandbox.momodeveloper.mtn.com`
3. Use test phone numbers provided by MTN:
   - Format: `25078XXXXXXX` (Rwanda country code + phone number)

### Step 6: Go Live
1. Complete MTN's business verification process
2. Get production credentials
3. Update your configuration to use production environment
4. Test with small real transactions before full deployment

---

## Configuration

### Application Configuration File

Edit your `config/config.php` or `.env` file to set the default payment gateway:

```php
// Default Payment Gateway
define('PAYMENT_GATEWAY', 'stripe'); // Options: 'stripe', 'paypal', 'mobile_momo_rwanda', 'mock'

// Environment
define('APP_ENV', 'development'); // Use 'production' for live environment

// Stripe
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');

// PayPal
define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: '');
define('PAYPAL_CLIENT_SECRET', getenv('PAYPAL_CLIENT_SECRET') ?: '');

// Mobile Momo Rwanda
define('MOBILE_MOMO_API_KEY', getenv('MOBILE_MOMO_API_KEY') ?: '');
define('MOBILE_MOMO_API_SECRET', getenv('MOBILE_MOMO_API_SECRET') ?: '');
define('MOBILE_MOMO_MERCHANT_ID', getenv('MOBILE_MOMO_MERCHANT_ID') ?: '');
```

### Example `.env` File

Create a `.env` file in your project root (copy from `.env.example`):

```bash
# Application Environment
APP_ENV=development

# Default Payment Gateway
PAYMENT_GATEWAY=stripe

# Stripe Configuration
STRIPE_SECRET_KEY=sk_test_51234567890abcdefghijklmnopqrstuvwxyz
STRIPE_PUBLISHABLE_KEY=pk_test_51234567890abcdefghijklmnopqrstuvwxyz

# PayPal Configuration
PAYPAL_CLIENT_ID=AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUu
PAYPAL_CLIENT_SECRET=EeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz

# Mobile Momo Rwanda Configuration
MOBILE_MOMO_API_KEY=1234567890abcdef1234567890abcdef
MOBILE_MOMO_API_SECRET=abcdef1234567890abcdef1234567890
MOBILE_MOMO_MERCHANT_ID=12345678-1234-1234-1234-123456789012
```

---

## Testing

### Test Payment Processing

#### Using Stripe Test Mode
Stripe provides test card numbers:
- **Successful payment**: `4242 4242 4242 4242`
- **Declined payment**: `4000 0000 0000 0002`
- **Requires authentication**: `4000 0025 0000 3155`
- Use any future expiry date and any 3-digit CVC

Full list: [https://stripe.com/docs/testing](https://stripe.com/docs/testing)

#### Using PayPal Sandbox
1. Create sandbox test accounts at [https://developer.paypal.com/dashboard/accounts](https://developer.paypal.com/dashboard/accounts)
2. Use test buyer and seller accounts
3. Login with sandbox credentials during checkout

#### Using Mobile Momo Sandbox
1. Use MTN's sandbox environment
2. Test with provided test phone numbers
3. Monitor API responses in the developer portal

### Test Script Example

```php
<?php
require_once 'includes/init.php';
require_once 'includes/payment_gateways.php';

// Test Stripe
try {
    $stripe = new StripePaymentGateway();
    $result = $stripe->processPayment(100.00, 'tok_visa', [
        'id' => 1,
        'order_number' => 'TEST-001',
        'customer_email' => 'customer@example.com'
    ]);
    echo "Stripe Test: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    print_r($result);
} catch (Exception $e) {
    echo "Stripe Error: " . $e->getMessage() . "\n";
}

// Test PayPal
try {
    $paypal = new PayPalPaymentGateway();
    $result = $paypal->processPayment(50.00, 'paypal_token', [
        'id' => 2,
        'order_number' => 'TEST-002'
    ]);
    echo "PayPal Test: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    print_r($result);
} catch (Exception $e) {
    echo "PayPal Error: " . $e->getMessage() . "\n";
}

// Test Mobile Momo
try {
    $momo = new MobileMomoRwandaPaymentGateway();
    $result = $momo->processPayment(10000, '250788123456', [
        'id' => 3,
        'order_number' => 'TEST-003'
    ]);
    echo "Mobile Momo Test: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    print_r($result);
} catch (Exception $e) {
    echo "Mobile Momo Error: " . $e->getMessage() . "\n";
}
?>
```

---

## Security Best Practices

1. **Never expose secret keys**: Keep API secrets in environment variables or secure configuration files
2. **Use HTTPS**: Always use SSL/TLS certificates for your website
3. **Validate webhooks**: Verify webhook signatures to ensure they're from the payment provider
4. **Log transactions**: Keep detailed logs of all payment transactions
5. **PCI Compliance**: Never store raw credit card details
6. **Use tokenization**: Store only payment tokens, never actual card numbers
7. **Regular audits**: Review payment logs and security regularly
8. **Rate limiting**: Implement rate limiting on payment endpoints
9. **Error handling**: Don't expose sensitive error details to end users
10. **Test thoroughly**: Always test in sandbox before going live

---

## Troubleshooting

### Common Issues

#### Stripe Issues
- **"Invalid API key"**: Check that you're using the correct secret key for your environment
- **"Payment declined"**: Use Stripe's test card numbers in test mode
- **CORS errors**: Ensure your domain is whitelisted in Stripe Dashboard

#### PayPal Issues
- **"Authentication failed"**: Verify your Client ID and Secret are correct
- **"Sandbox/Live mismatch"**: Ensure you're using credentials from the correct environment
- **"Payment not completed"**: Customer must complete the PayPal redirect flow

#### Mobile Momo Issues
- **"Invalid subscription key"**: Verify your API key is correctly configured
- **"Transaction timeout"**: Mobile Money transactions may take 30-60 seconds
- **"Invalid phone number"**: Ensure phone numbers are in format: 25078XXXXXXX

### Support Resources
- **Stripe**: [https://support.stripe.com](https://support.stripe.com)
- **PayPal**: [https://developer.paypal.com/support/](https://developer.paypal.com/support/)
- **MTN Mobile Money**: business@mtn.co.rw or +250 788 180 000

---

## Additional Resources

- [Stripe API Documentation](https://stripe.com/docs/api)
- [PayPal REST API Documentation](https://developer.paypal.com/docs/api/overview/)
- [MTN Mobile Money API Documentation](https://momodeveloper.mtn.com/api-documentation/)
- [PCI DSS Compliance Guide](https://www.pcisecuritystandards.org/)

---

## License

This documentation is part of the E-Commerce Platform project. For licensing information, please refer to the main project LICENSE file.

---

**Last Updated**: 2024
**Version**: 1.0

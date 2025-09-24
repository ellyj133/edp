<?php
/**
 * Payment Gateway Strategy Pattern
 * Supports multiple payment gateways with unified interface
 */

interface PaymentGatewayInterface {
    public function processPayment($amount, $paymentToken, $orderData);
    public function refundPayment($transactionId, $amount, $reason = null);
    public function getTransactionStatus($transactionId);
}

/**
 * Mock Payment Gateway - For testing
 */
class MockPaymentGateway implements PaymentGatewayInterface {
    public function processPayment($amount, $paymentToken, $orderData) {
        // Simulate payment processing
        sleep(1);
        
        // Simulate success/failure (90% success rate)
        $success = rand(1, 10) <= 9;
        
        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'MOCK_' . strtoupper(uniqid()),
                'method' => 'mock',
                'amount' => $amount,
                'status' => 'completed'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Mock payment failed - insufficient funds',
                'code' => 'INSUFFICIENT_FUNDS'
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        return [
            'success' => true,
            'refund_id' => 'REFUND_' . strtoupper(uniqid()),
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function getTransactionStatus($transactionId) {
        return [
            'status' => 'completed',
            'amount' => 0,
            'currency' => 'USD'
        ];
    }
}

/**
 * Stripe Payment Gateway
 */
class StripePaymentGateway implements PaymentGatewayInterface {
    private $secretKey;
    
    public function __construct() {
        $this->secretKey = STRIPE_SECRET_KEY;
        if (empty($this->secretKey)) {
            throw new Exception('Stripe secret key not configured');
        }
    }
    
    public function processPayment($amount, $paymentToken, $orderData) {
        // Convert amount to cents
        $amountCents = $amount * 100;
        
        $payload = [
            'amount' => $amountCents,
            'currency' => 'usd',
            'source' => $paymentToken,
            'description' => 'Order #' . ($orderData['order_number'] ?? 'Unknown'),
            'metadata' => [
                'order_id' => $orderData['id'] ?? null,
                'customer_email' => $orderData['customer_email'] ?? null
            ]
        ];
        
        $response = $this->makeStripeRequest('charges', $payload);
        
        if ($response && $response['status'] === 'succeeded') {
            return [
                'success' => true,
                'transaction_id' => $response['id'],
                'method' => 'stripe',
                'amount' => $amount,
                'status' => 'completed'
            ];
        } else {
            return [
                'success' => false,
                'error' => $response['failure_message'] ?? 'Payment processing failed',
                'code' => $response['failure_code'] ?? 'UNKNOWN_ERROR'
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        $payload = [
            'charge' => $transactionId,
            'amount' => $amount * 100, // Convert to cents
            'reason' => $reason ?: 'requested_by_customer'
        ];
        
        $response = $this->makeStripeRequest('refunds', $payload);
        
        if ($response && $response['status'] === 'succeeded') {
            return [
                'success' => true,
                'refund_id' => $response['id'],
                'amount' => $amount,
                'status' => 'completed'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Refund processing failed',
                'response' => $response
            ];
        }
    }
    
    public function getTransactionStatus($transactionId) {
        $response = $this->makeStripeRequest("charges/{$transactionId}", null, 'GET');
        
        return [
            'status' => $response['status'] ?? 'unknown',
            'amount' => ($response['amount'] ?? 0) / 100,
            'currency' => strtoupper($response['currency'] ?? 'USD')
        ];
    }
    
    private function makeStripeRequest($endpoint, $data = null, $method = 'POST') {
        $url = "https://api.stripe.com/v1/{$endpoint}";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded'
        ]);
        
        if ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('Stripe API error: ' . ($decoded['error']['message'] ?? 'Unknown error'));
        }
        
        return $decoded;
    }
}

/**
 * PayPal Payment Gateway
 */
class PayPalPaymentGateway implements PaymentGatewayInterface {
    private $clientId;
    private $clientSecret;
    private $sandbox;
    
    public function __construct() {
        $this->clientId = PAYPAL_CLIENT_ID;
        $this->clientSecret = PAYPAL_CLIENT_SECRET;
        $this->sandbox = APP_ENV === 'development';
        
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('PayPal credentials not configured');
        }
    }
    
    public function processPayment($amount, $paymentToken, $orderData) {
        // PayPal integration would be implemented here
        // This is a placeholder for the PayPal REST API integration
        
        return [
            'success' => true,
            'transaction_id' => 'PAYPAL_' . strtoupper(uniqid()),
            'method' => 'paypal',
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        return [
            'success' => true,
            'refund_id' => 'PAYPAL_REFUND_' . strtoupper(uniqid()),
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function getTransactionStatus($transactionId) {
        return [
            'status' => 'completed',
            'amount' => 0,
            'currency' => 'USD'
        ];
    }
}

/**
 * Flutterwave Payment Gateway (for Mobile Money)
 */
class FlutterwavePaymentGateway implements PaymentGatewayInterface {
    private $publicKey;
    private $secretKey;
    
    public function __construct() {
        $this->publicKey = $_ENV['FLUTTERWAVE_PUBLIC_KEY'] ?? '';
        $this->secretKey = $_ENV['FLUTTERWAVE_SECRET_KEY'] ?? '';
        
        if (empty($this->publicKey) || empty($this->secretKey)) {
            throw new Exception('Flutterwave credentials not configured');
        }
    }
    
    public function processPayment($amount, $paymentToken, $orderData) {
        // Flutterwave Rave API integration would be implemented here
        
        return [
            'success' => true,
            'transaction_id' => 'FLW_' . strtoupper(uniqid()),
            'method' => 'flutterwave',
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        return [
            'success' => true,
            'refund_id' => 'FLW_REFUND_' . strtoupper(uniqid()),
            'amount' => $amount,
            'status' => 'completed'
        ];
    }
    
    public function getTransactionStatus($transactionId) {
        return [
            'status' => 'completed',
            'amount' => 0,
            'currency' => 'USD'
        ];
    }
}

/**
 * Payment Gateway Factory
 */
class PaymentGatewayFactory {
    public static function create($gateway = null) {
        $gateway = $gateway ?: PAYMENT_GATEWAY;
        
        switch ($gateway) {
            case 'stripe':
                return new StripePaymentGateway();
            case 'paypal':
                return new PayPalPaymentGateway();
            case 'flutterwave':
                return new FlutterwavePaymentGateway();
            case 'mock':
            default:
                return new MockPaymentGateway();
        }
    }
}

/**
 * Main payment processing function
 */
function processPayment($orderId, $paymentMethodId, $amount, $gateway = null) {
    try {
        $paymentToken = new PaymentToken();
        $order = new Order();
        $transaction = new Transaction();
        
        // Get payment method details
        $paymentMethod = $paymentToken->find($paymentMethodId);
        if (!$paymentMethod) {
            throw new Exception('Payment method not found');
        }
        
        // Get order details
        $orderData = $order->find($orderId);
        if (!$orderData) {
            throw new Exception('Order not found');
        }
        
        // Get appropriate gateway
        $gatewayInstance = PaymentGatewayFactory::create($gateway);
        
        // Process payment
        $result = $gatewayInstance->processPayment($amount, $paymentMethod['token'], $orderData);
        
        // Record transaction
        if ($result['success']) {
            $transaction->create([
                'order_id' => $orderId,
                'gateway' => $result['method'],
                'transaction_id' => $result['transaction_id'],
                'amount' => $amount,
                'status' => $result['status'],
                'response_data' => json_encode($result)
            ]);
        }
        
        return $result;
        
    } catch (Exception $e) {
        Logger::error("Payment processing error: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmation($orderId) {
    try {
        $order = new Order();
        $user = new User();
        
        $orderData = $order->find($orderId);
        $userData = $user->find($orderData['user_id']);
        
        $emailData = [
            'to_email' => $userData['email'],
            'to_name' => $userData['first_name'] . ' ' . $userData['last_name'],
            'subject' => 'Order Confirmation - ' . $orderData['order_number'],
            'template' => 'order_confirmation',
            'data' => [
                'order' => $orderData,
                'user' => $userData,
                'order_items' => $order->getOrderItems($orderId)
            ]
        ];
        
        return sendEmail($emailData);
        
    } catch (Exception $e) {
        Logger::error("Failed to send order confirmation email: " . $e->getMessage());
        return false;
    }
}

/**
 * Webhook handler for payment gateway notifications
 */
function handlePaymentWebhook($gateway, $payload) {
    try {
        $transaction = new Transaction();
        $order = new Order();
        
        switch ($gateway) {
            case 'stripe':
                if ($payload['type'] === 'payment_intent.succeeded') {
                    $transactionId = $payload['data']['object']['charges']['data'][0]['id'];
                    $amount = $payload['data']['object']['amount'] / 100;
                    
                    // Update transaction status
                    $txn = $transaction->findByTransactionId($transactionId);
                    if ($txn) {
                        $transaction->update($txn['id'], ['status' => 'completed']);
                        $order->update($txn['order_id'], ['payment_status' => 'paid', 'status' => 'processing']);
                    }
                }
                break;
                
            case 'paypal':
                // Handle PayPal webhooks
                break;
                
            case 'flutterwave':
                // Handle Flutterwave webhooks
                break;
        }
        
        return true;
        
    } catch (Exception $e) {
        Logger::error("Webhook processing error: " . $e->getMessage());
        return false;
    }
}
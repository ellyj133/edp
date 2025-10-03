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
    private $baseUrl;
    
    public function __construct() {
        $this->clientId = defined('PAYPAL_CLIENT_ID') ? PAYPAL_CLIENT_ID : '';
        $this->clientSecret = defined('PAYPAL_CLIENT_SECRET') ? PAYPAL_CLIENT_SECRET : '';
        $this->sandbox = defined('APP_ENV') ? (APP_ENV === 'development') : true;
        $this->baseUrl = $this->sandbox 
            ? 'https://api-m.sandbox.paypal.com' 
            : 'https://api-m.paypal.com';
        
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('PayPal credentials not configured. Please set PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in your configuration.');
        }
    }
    
    /**
     * Get PayPal access token for API authentication
     */
    private function getAccessToken() {
        $ch = curl_init($this->baseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get PayPal access token');
        }
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    public function processPayment($amount, $paymentToken, $orderData) {
        try {
            $accessToken = $this->getAccessToken();
            
            // Create PayPal order
            $payload = [
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2, '.', '')
                    ],
                    'description' => 'Order #' . ($orderData['order_number'] ?? 'Unknown'),
                    'reference_id' => $orderData['id'] ?? uniqid()
                ]],
                'payment_source' => [
                    'paypal' => [
                        'experience_context' => [
                            'payment_method_preference' => 'IMMEDIATE_PAYMENT_REQUIRED',
                            'user_action' => 'PAY_NOW'
                        ]
                    ]
                ]
            ];
            
            $ch = curl_init($this->baseUrl . '/v2/checkout/orders');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($httpCode === 201 && isset($data['id'])) {
                return [
                    'success' => true,
                    'transaction_id' => $data['id'],
                    'method' => 'paypal',
                    'amount' => $amount,
                    'status' => 'pending', // PayPal orders need to be captured
                    'approval_url' => $data['links'][1]['href'] ?? null // Link for customer approval
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $data['message'] ?? 'PayPal payment processing failed',
                    'code' => $data['name'] ?? 'UNKNOWN_ERROR'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'EXCEPTION'
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        try {
            $accessToken = $this->getAccessToken();
            
            $payload = [
                'amount' => [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => 'USD'
                ],
                'note_to_payer' => $reason ?: 'Refund processed'
            ];
            
            $ch = curl_init($this->baseUrl . "/v2/payments/captures/{$transactionId}/refund");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            if ($httpCode === 201 && isset($data['id'])) {
                return [
                    'success' => true,
                    'refund_id' => $data['id'],
                    'amount' => $amount,
                    'status' => $data['status'] ?? 'completed'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'PayPal refund failed',
                    'response' => $data
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getTransactionStatus($transactionId) {
        try {
            $accessToken = $this->getAccessToken();
            
            $ch = curl_init($this->baseUrl . "/v2/checkout/orders/{$transactionId}");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ]);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $data = json_decode($response, true);
            
            return [
                'status' => $data['status'] ?? 'unknown',
                'amount' => $data['purchase_units'][0]['amount']['value'] ?? 0,
                'currency' => $data['purchase_units'][0]['amount']['currency_code'] ?? 'USD'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'amount' => 0,
                'currency' => 'USD',
                'error' => $e->getMessage()
            ];
        }
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
 * Mobile Momo Rwanda Payment Gateway
 * Integrates with MTN Mobile Money and Airtel Money for Rwanda
 */
class MobileMomoRwandaPaymentGateway implements PaymentGatewayInterface {
    private $apiKey;
    private $apiSecret;
    private $merchantId;
    private $sandbox;
    private $baseUrl;
    
    public function __construct() {
        $this->apiKey = defined('MOBILE_MOMO_API_KEY') ? MOBILE_MOMO_API_KEY : '';
        $this->apiSecret = defined('MOBILE_MOMO_API_SECRET') ? MOBILE_MOMO_API_SECRET : '';
        $this->merchantId = defined('MOBILE_MOMO_MERCHANT_ID') ? MOBILE_MOMO_MERCHANT_ID : '';
        $this->sandbox = defined('APP_ENV') ? (APP_ENV === 'development') : true;
        
        // MTN Mobile Money Collection API endpoints
        $this->baseUrl = $this->sandbox 
            ? 'https://sandbox.momodeveloper.mtn.com/collection/v1_0'
            : 'https://proxy.momoapi.mtn.com/collection/v1_0';
        
        if (empty($this->apiKey) || empty($this->apiSecret) || empty($this->merchantId)) {
            throw new Exception('Mobile Momo Rwanda credentials not configured. Please set MOBILE_MOMO_API_KEY, MOBILE_MOMO_API_SECRET, and MOBILE_MOMO_MERCHANT_ID.');
        }
    }
    
    /**
     * Get access token for MTN Mobile Money API
     */
    private function getAccessToken() {
        $ch = curl_init($this->baseUrl . '/../token/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->apiKey . ':' . $this->apiSecret),
            'Ocp-Apim-Subscription-Key: ' . $this->apiKey
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception('Failed to get Mobile Momo access token');
        }
        
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    public function processPayment($amount, $paymentToken, $orderData) {
        try {
            $accessToken = $this->getAccessToken();
            $referenceId = uniqid('MOMO_', true);
            
            // Request to Pay - MTN Mobile Money Collection API
            $payload = [
                'amount' => number_format($amount, 0, '', ''), // Amount without decimals for RWF
                'currency' => 'RWF', // Rwandan Franc
                'externalId' => $orderData['order_number'] ?? $referenceId,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $paymentToken // Phone number in format 25078XXXXXXX
                ],
                'payerMessage' => 'Payment for Order #' . ($orderData['order_number'] ?? 'Unknown'),
                'payeeNote' => 'Order payment received'
            ];
            
            $ch = curl_init($this->baseUrl . '/requesttopay');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
                'X-Reference-Id: ' . $referenceId,
                'X-Target-Environment: ' . ($this->sandbox ? 'sandbox' : 'mtnrwanda'),
                'Ocp-Apim-Subscription-Key: ' . $this->apiKey
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 202) {
                // Request accepted, check status after a delay
                sleep(3);
                $status = $this->getTransactionStatus($referenceId);
                
                return [
                    'success' => $status['status'] === 'SUCCESSFUL',
                    'transaction_id' => $referenceId,
                    'method' => 'mobile_momo_rwanda',
                    'amount' => $amount,
                    'status' => $status['status'] === 'SUCCESSFUL' ? 'completed' : 'pending',
                    'provider' => 'MTN Mobile Money Rwanda'
                ];
            } else {
                $error = json_decode($response, true);
                return [
                    'success' => false,
                    'error' => $error['message'] ?? 'Mobile Momo payment request failed',
                    'code' => $error['code'] ?? 'UNKNOWN_ERROR'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'EXCEPTION'
            ];
        }
    }
    
    public function refundPayment($transactionId, $amount, $reason = null) {
        // Mobile Money refunds typically require manual processing
        // Log the refund request for manual processing
        return [
            'success' => false,
            'error' => 'Mobile Momo refunds must be processed manually. Please contact support with transaction ID: ' . $transactionId,
            'code' => 'MANUAL_REFUND_REQUIRED',
            'transaction_id' => $transactionId,
            'amount' => $amount
        ];
    }
    
    public function getTransactionStatus($transactionId) {
        try {
            $accessToken = $this->getAccessToken();
            
            $ch = curl_init($this->baseUrl . '/requesttopay/' . $transactionId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken,
                'X-Target-Environment: ' . ($this->sandbox ? 'sandbox' : 'mtnrwanda'),
                'Ocp-Apim-Subscription-Key: ' . $this->apiKey
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return [
                    'status' => $data['status'] ?? 'UNKNOWN', // SUCCESSFUL, PENDING, FAILED
                    'amount' => $data['amount'] ?? 0,
                    'currency' => $data['currency'] ?? 'RWF',
                    'reason' => $data['reason'] ?? null
                ];
            } else {
                return [
                    'status' => 'ERROR',
                    'amount' => 0,
                    'currency' => 'RWF',
                    'error' => 'Failed to retrieve transaction status'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'ERROR',
                'amount' => 0,
                'currency' => 'RWF',
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Payment Gateway Factory
 */
class PaymentGatewayFactory {
    public static function create($gateway = null) {
        $gateway = $gateway ?: (defined('PAYMENT_GATEWAY') ? PAYMENT_GATEWAY : 'mock');
        
        switch ($gateway) {
            case 'stripe':
                return new StripePaymentGateway();
            case 'paypal':
                return new PayPalPaymentGateway();
            case 'flutterwave':
                return new FlutterwavePaymentGateway();
            case 'mobile_momo':
            case 'mobile_momo_rwanda':
                return new MobileMomoRwandaPaymentGateway();
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
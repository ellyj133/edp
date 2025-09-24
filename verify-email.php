<?php
/**
 * Email Verification Page - OTP Based
 * E-Commerce Platform
 */

require_once __DIR__ . '/includes/init.php';

$email = $_GET['email'] ?? '';
$errors = [];
$success_message = '';

// Redirect to register if no email provided
if (empty($email)) {
    redirect('/register.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    
    if (empty($otp)) {
        $errors[] = 'Please enter the verification code.';
    } else {
        try {
            // Find user by email
            $user = new User();
            $userData = $user->findByEmail($email);
            
            if ($userData) {
                if ($userData['status'] === 'active' && $userData['verified_at']) {
                    $success_message = 'This email has already been verified. You can now log in.';
                } else {
                    // Use secure EmailTokenManager for verification
                    $tokenManager = new EmailTokenManager();
                    $verificationResult = $tokenManager->verifyToken(
                        $otp, 
                        'email_verification', 
                        $userData['id'], 
                        $userData['email']
                    );
                    
                    if ($verificationResult['success']) {
                        // Verify the user account
                        $verified = $user->verifyEmail($userData['id']);
                        
                        if ($verified) {
                            $success_message = 'Email verified successfully! You can now proceed to login.';
                            Logger::info("Email verified for user {$userData['email']}");
                        } else {
                            $errors[] = 'Failed to verify your email. Please try again.';
                        }
                    } else {
                        // Use generic error message for security
                        if ($verificationResult['rate_limited']) {
                            $errors[] = 'Too many verification attempts. Please wait before trying again.';
                        } else {
                            $errors[] = 'Invalid or expired verification code. Please try again.';
                        }
                        Logger::warning("Failed OTP verification for email: {$email}");
                    }
                }
            } else {
                $errors[] = 'User not found.';
            }
            
        } catch (Exception $e) {
            $errors[] = 'Database error. Please try again.';
            Logger::error("Email verification error: " . $e->getMessage());
        }
    }
}

$page_title = 'Verify Email';
includeHeader($page_title);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <style>
        :root { 
            --primary-color: #0052cc; 
            --primary-hover: #0041a3; 
            --secondary-color: #f4f7f6; 
            --text-color: #333; 
            --light-text-color: #777; 
            --border-color: #ddd; 
            --error-bg: #f8d7da; 
            --error-text: #721c24; 
            --success-bg: #d4edda; 
            --success-text: #155724; 
            --footer-bg: #ffffff; 
            --footer-text: #555555; 
        }
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
            background-color: var(--secondary-color); 
        }
        main.auth-container { 
            flex-grow: 1; 
            display: flex; 
            width: 100%; 
        }
        .auth-panel { 
            flex: 1; 
            background: linear-gradient(135deg, #0052cc, #007bff); 
            color: white; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            padding: 50px; 
            text-align: center; 
        }
        .auth-panel h2 { 
            font-size: 2rem; 
            margin-bottom: 15px; 
        }
        .auth-panel p { 
            font-size: 1.1rem; 
            line-height: 1.6; 
            max-width: 350px; 
        }
        .auth-form-section { 
            flex: 1; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 50px; 
            background: #fff; 
        }
        .form-box { 
            width: 100%; 
            max-width: 400px; 
            text-align: center; 
        }
        .form-box h1 { 
            color: var(--text-color); 
            margin-bottom: 10px; 
            font-size: 2.2rem; 
        }
        .form-box .form-subtitle { 
            color: var(--light-text-color); 
            margin-bottom: 30px; 
        }
        .form-box .form-subtitle strong { 
            color: var(--text-color); 
        }
        .form-group input { 
            width: 100%; 
            padding: 12px 15px; 
            border: 1px solid var(--border-color); 
            border-radius: 5px; 
            box-sizing: border-box; 
            font-size: 1.5rem; 
            transition: border-color 0.3s; 
            text-align: center; 
            letter-spacing: 0.5em; 
        }
        .form-group input:focus { 
            outline: none; 
            border-color: var(--primary-color); 
        }
        .auth-button { 
            width: 100%; 
            padding: 14px; 
            background-color: var(--primary-color); 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 1.1rem; 
            font-weight: 700; 
            transition: background-color 0.3s; 
            margin-top: 20px; 
        }
        .auth-button:hover { 
            background-color: var(--primary-hover); 
        }
        .message-area { 
            margin-bottom: 20px; 
        }
        .message { 
            padding: 15px; 
            border-radius: 5px; 
            text-align: center; 
        }
        .error-message { 
            color: var(--error-text); 
            background-color: var(--error-bg); 
        }
        .success-message { 
            color: var(--success-text); 
            background-color: var(--success-bg); 
        }
        .bottom-link { 
            margin-top: 25px; 
        }
        .bottom-link a { 
            color: var(--primary-color); 
            text-decoration: none; 
            font-weight: 600; 
        }
        @media (max-width: 992px) { 
            .auth-panel { 
                display: none; 
            } 
            .auth-form-section { 
                padding: 30px; 
            } 
        }
        
        /* Enhanced OTP Input Styles */
        .otp-container {
            margin: 20px 0;
        }
        
        .otp-inputs {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .otp-input {
            width: 45px;
            height: 50px;
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: white;
            transition: all 0.2s ease;
            outline: none;
        }
        
        .otp-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 82, 204, 0.1);
            transform: scale(1.02);
        }
        
        .otp-input.filled {
            border-color: var(--primary-color);
            background-color: rgba(0, 82, 204, 0.05);
        }
        
        .otp-input.error {
            border-color: #dc3545;
            background-color: rgba(220, 53, 69, 0.05);
            animation: shake 0.3s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .otp-timer-container {
            margin: 15px 0;
            text-align: center;
        }
        
        .otp-timer {
            font-size: 14px;
            color: var(--light-text-color);
            margin-bottom: 10px;
        }
        
        .otp-timer.warning {
            color: #ff6b35;
            font-weight: 600;
        }
        
        .resend-container {
            margin-top: 10px;
        }
        
        .resend-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            font-size: 14px;
            padding: 5px;
        }
        
        .resend-btn:disabled {
            color: var(--light-text-color);
            cursor: not-allowed;
            text-decoration: none;
        }
        
        .resend-btn:hover:not(:disabled) {
            color: var(--primary-hover);
        }
        
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .otp-input {
                background: #2a2a2a;
                border-color: #444;
                color: white;
            }
            
            .otp-input:focus {
                border-color: #4a90e2;
                box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.2);
            }
            
            .otp-input.filled {
                border-color: #4a90e2;
                background-color: rgba(74, 144, 226, 0.1);
            }
        }
        
        /* Mobile optimizations */
        @media (max-width: 480px) {
            .otp-inputs {
                gap: 6px;
            }
            
            .otp-input {
                width: 40px;
                height: 45px;
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <main class="auth-container">
        <div class="auth-panel">
            <h2>One Last Step</h2>
            <p>Confirm your email to secure your account and unlock all features.</p>
        </div>
        <div class="auth-form-section">
            <div class="form-box">
                <h1>Verify Your Email</h1>
                <p class="form-subtitle">A 6-digit code has been sent to<br><strong><?php echo htmlspecialchars($email); ?></strong></p>
                
                <div class="message-area">
                    <?php if (!empty($errors)): ?>
                        <div class="message error-message"><?php echo htmlspecialchars($errors[0]); ?></div>
                    <?php endif; ?>
                    <?php if ($success_message): ?>
                        <div class="message success-message"><?php echo htmlspecialchars($success_message); ?></div>
                    <?php endif; ?>
                </div>
                
                <?php if (!$success_message): ?>
                    <form action="verify-email.php?email=<?php echo urlencode($email); ?>" method="post" id="otpForm">
                        <?php echo csrfTokenInput(); ?>
                        
                        <!-- 6-Box OTP Input -->
                        <div class="otp-container">
                            <label for="otp-input-1" class="sr-only">Enter 6-digit verification code</label>
                            <div class="otp-inputs" role="group" aria-labelledby="otp-label">
                                <span id="otp-label" class="sr-only">6-digit verification code input</span>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <input type="text" 
                                           id="otp-input-<?php echo $i; ?>" 
                                           class="otp-input" 
                                           maxlength="1" 
                                           inputmode="numeric" 
                                           pattern="[0-9]" 
                                           autocomplete="one-time-code"
                                           aria-label="Digit <?php echo $i; ?> of verification code"
                                           <?php echo $i === 1 ? 'autofocus' : ''; ?>>
                                <?php endfor; ?>
                            </div>
                            <input type="hidden" name="otp" id="hiddenOtp">
                        </div>
                        
                        <!-- Timer and Resend -->
                        <div class="otp-timer-container">
                            <div id="otpTimer" class="otp-timer" style="display: none;">
                                Code expires in: <span id="timerDisplay">15:00</span>
                            </div>
                            <div id="resendContainer" class="resend-container">
                                <button type="button" id="resendBtn" class="resend-btn" disabled>
                                    Resend code <span id="resendCooldown">(30s)</span>
                                </button>
                            </div>
                        </div>
                        
                        <button type="submit" class="auth-button" id="verifyBtn" disabled>Verify Account</button>
                    </form>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="bottom-link">
                        <a href="login.php">Proceed to Login</a>
                    </div>
                <?php endif; ?>
                
                <div class="bottom-link">
                    <a href="resend-verification.php?email=<?php echo urlencode($email); ?>">Resend Code</a>
                </div>
            </div>
        </div>
    </main>
</body>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // OTP Input Management
    const otpInputs = document.querySelectorAll('.otp-input');
    const hiddenOtpInput = document.getElementById('hiddenOtp');
    const verifyBtn = document.getElementById('verifyBtn');
    const otpForm = document.getElementById('otpForm');
    const timerDisplay = document.getElementById('timerDisplay');
    const otpTimer = document.getElementById('otpTimer');
    const resendBtn = document.getElementById('resendBtn');
    const resendCooldown = document.getElementById('resendCooldown');
    
    let expiryTime = Date.now() + (15 * 60 * 1000); // 15 minutes from now
    let resendCooldownTime = 30; // 30 seconds cooldown
    
    // Initialize timers
    startExpiryTimer();
    startResendCooldown();
    
    // OTP Input functionality
    otpInputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Only allow digits
            if (!/^\d$/.test(value) && value !== '') {
                e.target.value = '';
                return;
            }
            
            // Update visual state
            if (value) {
                e.target.classList.add('filled');
                // Auto-focus next input
                if (index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
            } else {
                e.target.classList.remove('filled');
            }
            
            updateHiddenInput();
            updateVerifyButton();
        });
        
        input.addEventListener('keydown', function(e) {
            // Handle backspace
            if (e.key === 'Backspace') {
                if (!e.target.value && index > 0) {
                    // Move to previous input if current is empty
                    otpInputs[index - 1].focus();
                    otpInputs[index - 1].value = '';
                    otpInputs[index - 1].classList.remove('filled');
                }
                updateHiddenInput();
                updateVerifyButton();
            }
            
            // Handle arrow keys
            if (e.key === 'ArrowLeft' && index > 0) {
                otpInputs[index - 1].focus();
            }
            if (e.key === 'ArrowRight' && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });
        
        // Handle paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const digits = pastedText.replace(/\D/g, '').substring(0, 6);
            
            if (digits.length > 0) {
                fillOtpInputs(digits);
            }
        });
        
        // Clear error state on focus
        input.addEventListener('focus', function() {
            otpInputs.forEach(inp => inp.classList.remove('error'));
        });
    });
    
    function fillOtpInputs(digits) {
        otpInputs.forEach((input, index) => {
            if (index < digits.length) {
                input.value = digits[index];
                input.classList.add('filled');
            } else {
                input.value = '';
                input.classList.remove('filled');
            }
        });
        
        // Focus the next empty input or the last input
        const nextEmptyIndex = digits.length < 6 ? digits.length : 5;
        otpInputs[nextEmptyIndex].focus();
        
        updateHiddenInput();
        updateVerifyButton();
    }
    
    function updateHiddenInput() {
        const otpValue = Array.from(otpInputs).map(input => input.value).join('');
        hiddenOtpInput.value = otpValue;
    }
    
    function updateVerifyButton() {
        const otpValue = Array.from(otpInputs).map(input => input.value).join('');
        verifyBtn.disabled = otpValue.length !== 6;
    }
    
    function startExpiryTimer() {
        otpTimer.style.display = 'block';
        
        const timer = setInterval(() => {
            const now = Date.now();
            const timeLeft = Math.max(0, expiryTime - now);
            
            if (timeLeft === 0) {
                clearInterval(timer);
                document.getElementById('timerDisplay').textContent = 'Expired';
                otpTimer.classList.add('warning');
                verifyBtn.disabled = true;
                otpInputs.forEach(input => {
                    input.disabled = true;
                    input.classList.add('error');
                });
                return;
            }
            
            const minutes = Math.floor(timeLeft / 60000);
            const seconds = Math.floor((timeLeft % 60000) / 1000);
            const display = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            document.getElementById('timerDisplay').textContent = display;
            
            // Warning state when less than 2 minutes
            if (timeLeft < 120000) {
                otpTimer.classList.add('warning');
            }
        }, 1000);
    }
    
    function startResendCooldown() {
        resendBtn.disabled = true;
        
        const cooldownTimer = setInterval(() => {
            resendCooldownTime--;
            resendCooldown.textContent = `(${resendCooldownTime}s)`;
            
            if (resendCooldownTime <= 0) {
                clearInterval(cooldownTimer);
                resendBtn.disabled = false;
                resendCooldown.textContent = '';
            }
        }, 1000);
    }
    
    // Resend functionality
    resendBtn.addEventListener('click', function() {
        if (this.disabled) return;
        
        // Reset form
        otpInputs.forEach(input => {
            input.value = '';
            input.classList.remove('filled', 'error');
            input.disabled = false;
        });
        
        // Reset timer
        expiryTime = Date.now() + (15 * 60 * 1000);
        otpTimer.classList.remove('warning');
        
        // Reset resend cooldown
        resendCooldownTime = 30;
        startResendCooldown();
        
        // Focus first input
        otpInputs[0].focus();
        
        // Redirect to resend page
        window.location.href = `resend-verification.php?email=${encodeURIComponent('<?php echo addslashes($email); ?>')}`;
    });
    
    // Form submission
    otpForm.addEventListener('submit', function(e) {
        const otpValue = hiddenOtpInput.value;
        if (otpValue.length !== 6) {
            e.preventDefault();
            otpInputs.forEach(input => input.classList.add('error'));
            
            // Show error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'message error-message';
            errorDiv.textContent = 'Please enter all 6 digits of the verification code.';
            
            const existingError = document.querySelector('.error-message');
            if (existingError) {
                existingError.remove();
            }
            
            document.querySelector('.message-area').appendChild(errorDiv);
            
            // Focus first empty input
            const firstEmpty = Array.from(otpInputs).find(input => !input.value);
            if (firstEmpty) firstEmpty.focus();
            else otpInputs[0].focus();
        }
    });
    
    // Auto-focus first input
    if (otpInputs[0]) {
        otpInputs[0].focus();
    }
});
</script>

</html>

<?php includeFooter(); ?>
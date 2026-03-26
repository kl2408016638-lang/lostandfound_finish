<?php
session_start();
include 'db_connect.php';
require_once 'config/register_2fa.php';


if (!isset($_SESSION['2fa_user_id']) || !isset($_SESSION['2fa_user_email'])) {
    header("Location: login.php");
    exit();
}

$twoFA = new Register2FA($connect);
$message = '';
$message_class = '';

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['verify_otp'])) {
        $otp_code = trim($_POST['otp_code']);
        $user_id = $_SESSION['2fa_user_id'];
        
        $result = $twoFA->verifyOTP($user_id, $otp_code);
        
        if ($result['success']) {
            // OTP sah - login kan user
            $_SESSION['user_id'] = $_SESSION['2fa_user_id'];
            $_SESSION['name'] = $_SESSION['2fa_user_name'];
            $_SESSION['role'] = $_SESSION['2fa_user_role'];
            $_SESSION['email'] = $_SESSION['2fa_user_email'];
            
            // Clear 2FA session
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_user_email']);
            unset($_SESSION['2fa_user_name']);
            unset($_SESSION['2fa_user_role']);
            
            // Redirect based on role
            if($_SESSION['role'] == 'user') {
                header("Location: user_dashboard.php");
            } else {
                header("Location: admin_profile.php");
            }
            exit();
        } else {
            $message = $result['message'];
            $message_class = 'error';
        }
    }
    
    // Handle resend OTP
    if (isset($_POST['resend_otp'])) {
        $user_id = $_SESSION['2fa_user_id'];
        $email = $_SESSION['2fa_user_email'];
        $name = $_SESSION['2fa_user_name'];
        
        $otp_result = $twoFA->sendVerificationOTP($user_id, $email, $name);
        
        if ($otp_result['success']) {
            $message = "New OTP has been sent to your email.";
            $message_class = 'success';
        } else {
            $message = "Failed to resend OTP: " . $otp_result['message'];
            $message_class = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP - Login - Surau Ismail Kharofa</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            display: flex;
            max-width: 900px;
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .design-side {
            flex: 1;
            background: linear-gradient(135deg, #2c5530 0%, #3a7c3e 100%);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
        }
        
        .design-title {
            color: white;
            font-size: 24px;
            margin: 20px 0 10px;
        }
        
        .design-text {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            line-height: 1.6;
        }
        
        .form-side {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-container {
            max-width: 380px;
            width: 100%;
            margin: 0 auto;
        }
        
        h2 {
            font-size: 28px;
            color: #2c5530;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .email-info {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .email-info strong {
            color: #2c5530;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .otp-input {
            width: 100%;
            padding: 15px;
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            border: 2px solid #ddd;
            border-radius: 8px;
            outline: none;
        }
        
        .otp-input:focus {
            border-color: #2c5530;
        }
        
        .verify-btn {
            width: 100%;
            padding: 14px;
            background: #2c5530;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-bottom: 10px;
        }
        
        .verify-btn:hover {
            background: #3a7c3e;
        }
        
        .resend-btn {
            width: 100%;
            padding: 12px;
            background: white;
            color: #2c5530;
            border: 2px solid #2c5530;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .resend-btn:hover {
            background: #f0f7f0;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            color: #2c5530;
        }
        
        @media (max-width: 700px) {
            .container {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- LEFT SIDE - Design -->
    <div class="design-side">
        <img src="Logo.png" alt="logo" class="logo">
        <h3 class="design-title">Surau Ismail Kharofa</h3>
        <p class="design-text">Lost And Found System</p>
        <p class="design-text" style="margin-top: 20px; font-style: italic;">
            "Two-Factor Authentication enabled for this account"
        </p>
    </div>
    
    <!-- RIGHT SIDE - OTP Form -->
    <div class="form-side">
        <div class="form-container">
            <h2>Verify Login</h2>
            
            <div class="email-info">
                📧 We've sent a 6-digit code to:<br>
                <strong><?php echo $_SESSION['2fa_user_email']; ?></strong>
            </div>
            
            <?php if($message != ""): ?>
                <div class="message <?php echo $message_class; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Enter 6-digit OTP Code</label>
                    <input type="text" 
                           name="otp_code" 
                           class="otp-input" 
                           maxlength="6" 
                           pattern="[0-9]{6}"
                           placeholder="000000"
                           required>
                </div>
                
                <button type="submit" name="verify_otp" class="verify-btn">
                    Verify & Login
                </button>
                
                <button type="submit" name="resend_otp" class="resend-btn">
                    🔄 Resend OTP
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
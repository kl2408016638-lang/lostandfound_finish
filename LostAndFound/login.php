<?php
session_start();
include 'db_connect.php';
require_once 'config/register_2fa.php';

$message = "";

if(isset($_POST['login'])) {
    $role = $_POST['role']; // user/admin
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validate
    if(empty($name) || empty($password)) {
    $message = "Error: Name and Password are required!";
    } elseif(strlen($password) < 8) {
        $message = "Error: Password must be at least 8 characters!";
    } elseif(!preg_match('/[A-Z]/', $password)) {
        $message = "Error: Password must contain at least 1 uppercase letter!";
    } elseif(!preg_match('/[a-z]/', $password)) {
        $message = "Error: Password must contain at least 1 lowercase letter!";
    } elseif(!preg_match('/[0-9]/', $password)) {
        $message = "Error: Password must contain at least 1 number!";
    } elseif(!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $message = "Error: Password must contain at least 1 symbol (!@#$%^&*)!";
    } else {
        // Query based on role
        $sql = "SELECT * FROM accounts WHERE role='$role' AND name='$name'";
        
        $result = mysqli_query($connect, $sql);
        
        if(mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if($password === $user['password']) {
                
                // Check 2FA status - skip if new register
                    $just_registered = isset($_SESSION['just_registered']) && $_SESSION['just_registered'] === true;
                    unset($_SESSION['just_registered']); // Buang flag terus

                    if(!$just_registered && isset($user['is_2fa_enabled']) && $user['is_2fa_enabled'] == 1) {
                                        
                    $_SESSION['2fa_user_id'] = $user['id'];
                    $_SESSION['2fa_user_email'] = $user['email'];
                    $_SESSION['2fa_user_name'] = $user['name'];
                    $_SESSION['2fa_user_role'] = $user['role'];
                    
                    // Send OTP
                    $twoFA = new Register2FA($connect);
                    $otp_result = $twoFA->sendVerificationOTP($user['id'], $user['email'], $user['name']);
                    
                    if($otp_result['success']) {
                        header("Location: verify-login-otp.php");
                        exit();
                    } else {
                        $message = "Error: Fail to send OTP. Please try again.";
                    }
                } else {
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['contactnum'] = $user['contactnum'];
                    
                    // Log admin action
                    if(file_exists('admin_logger.php') && $_SESSION['role'] == 'admin') {
                        include 'admin_logger.php';
                        logAdminAction($connect, $_SESSION['user_id'], $_SESSION['name'], 'login', null, null, null, 'Admin logged into system');
                    }
                    
                    // Redirect based on role
                    if($role == 'user') {
                        header("Location: user_dashboard.php");
                    } else {
                        header("Location: admin_profile.php");
                    }
                    exit();
                }
                
            } else {
                $message = "Error: Invalid password!";
            }
        } else {
            $message = "Error: User not found!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Surau Ismail Kharofa</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    

    :root {
        --green-deep:   #0f3522;
        --green-dark:   #1b4d35;
        --green-mid:    #2e7d52;
        --green-light:  #3aaf6e;
        --gold:         #c9a84c;
        --gold-light:   #f0d080;
        --cream:        #fdf8f0;
        --white:        #ffffff;
        --text-dark:    #0f2418;
        --text-mid:     #3d6b52;
        --text-light:   #8aab98;
        --border:       #cde8d8;
    }

    * {
        margin: 0; padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Nunito', sans-serif;
        background: var(--green-deep);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 24px;
        position: relative;
        overflow-x: hidden;
    }

    /* Background geometric pattern */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background-image:
            radial-gradient(circle at 15% 25%, rgba(58,175,110,0.12) 0%, transparent 45%),
            radial-gradient(circle at 85% 75%, rgba(201,168,76,0.10) 0%, transparent 45%),
            radial-gradient(circle at 50% 50%, rgba(46,125,82,0.08) 0%, transparent 70%);
        pointer-events: none;
    }

    /* Animated dots pattern */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        background-image: radial-gradient(circle, rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 32px 32px;
        pointer-events: none;
    }

    /* ===== CONTAINER ===== */
    .container {
        display: flex;
        max-width: 1080px;
        width: 100%;
        border-radius: 28px;
        overflow: hidden;
        box-shadow: 0 40px 80px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.08);
        position: relative;
        z-index: 1;
        animation: fadeUp 0.6s ease both;
    }

    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(30px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ===== LEFT SIDE ===== */
    .design-side {
        flex: 1.1;
        background: linear-gradient(160deg, var(--green-deep) 0%, var(--green-dark) 40%, var(--green-mid) 100%);
        padding: 52px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    /* Arch decorative element */
    .design-side::before {
        content: '';
        position: absolute;
        top: -120px; left: 50%;
        transform: translateX(-50%);
        width: 340px; height: 340px;
        border-radius: 50%;
        border: 60px solid rgba(255,255,255,0.03);
    }

    .design-side::after {
        content: '';
        position: absolute;
        bottom: -100px; right: -80px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(201,168,76,0.06);
    }

    /* Gold top accent bar */
    .design-side .gold-bar {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, transparent, var(--gold), var(--gold-light), var(--gold), transparent);
    }

    .logo {
        width: 100px; height: 100px;
        border-radius: 50%;
        border: 3px solid rgba(201,168,76,0.5);
        object-fit: cover;
        box-shadow: 0 0 0 8px rgba(201,168,76,0.1), 0 12px 30px rgba(0,0,0,0.4);
        margin-bottom: 22px;
        position: relative; z-index: 1;
        animation: fadeUp 0.6s 0.1s ease both;
    }

    .surau-title {
        font-family: 'Playfair Display', serif;
        font-size: 26px;
        color: var(--white);
        margin-bottom: 6px;
        font-weight: 700;
        position: relative; z-index: 1;
        animation: fadeUp 0.6s 0.2s ease both;
        text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }

    .lost-found {
        font-family: 'Playfair Display', serif;
        font-size: 18px;
        color: var(--gold-light);
        margin-bottom: 26px;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        position: relative; z-index: 1;
        animation: fadeUp 0.6s 0.25s ease both;
    }

    .surau-image {
        width: 100%;
        height: 185px;
        object-fit: cover;
        border-radius: 16px;
        margin-bottom: 22px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.4);
        border: 2px solid rgba(201,168,76,0.25);
        position: relative; z-index: 1;
        animation: fadeUp 0.6s 0.3s ease both;
    }

    .design-quote {
        color: rgba(255,255,255,0.6);
        font-size: 13px;
        font-style: italic;
        line-height: 1.8;
        position: relative; z-index: 1;
        animation: fadeUp 0.6s 0.35s ease both;
        border-top: 1px solid rgba(201,168,76,0.2);
        padding-top: 16px;
        max-width: 280px;
    }

    /* ===== RIGHT SIDE ===== */
    .form-side {
        flex: 1;
        padding: 52px 48px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: var(--cream);
        position: relative;
    }

    /* Right side subtle pattern */
    .form-side::before {
        content: '';
        position: absolute;
        top: 0; right: 0;
        width: 180px; height: 180px;
        background: radial-gradient(circle at top right, rgba(46,125,82,0.06), transparent 70%);
        pointer-events: none;
    }

    .form-container {
        max-width: 380px;
        width: 100%;
        margin: 0 auto;
        animation: fadeUp 0.6s 0.15s ease both;
    }

    .form-title {
        font-family: 'Playfair Display', serif;
        font-size: 32px;
        color: var(--text-dark);
        margin-bottom: 6px;
        font-weight: 800;
        text-align: center;
        line-height: 1.2;
    }

    .form-subtitle {
        color: var(--text-light);
        text-align: center;
        margin-bottom: 28px;
        font-size: 14px;
        font-weight: 600;
    }

    /* ===== ROLE SELECTOR ===== */
    .role-selector {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        background: #e8f5ee;
        padding: 5px;
        border-radius: 14px;
    }

    .role-option {
        flex: 1;
        padding: 11px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        text-align: center;
        transition: all 0.25s ease;
        font-weight: 700;
        color: var(--text-mid);
        background: transparent;
        font-size: 14px;
        font-family: 'Nunito', sans-serif;
    }

    .role-option:hover { background: rgba(255,255,255,0.7); }

    .role-option.selected {
        background: var(--white);
        color: var(--green-dark);
        box-shadow: 0 3px 12px rgba(27,77,53,0.18);
    }

    /* ===== FORM ===== */
    .form-group { margin-bottom: 16px; }

    label {
        display: block;
        margin-bottom: 7px;
        font-weight: 700;
        color: var(--text-dark);
        font-size: 14px;
        letter-spacing: 0.3px;
    }

    .required { color: #e74c3c; }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
        width: 100%;
        padding: 14px 18px;
        border: 2px solid #cde8d8;
        border-radius: 12px;
        font-size: 16px;
        background: var(--white);
        transition: all 0.25s;
        color: var(--text-dark);
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
    }

    input:focus {
        outline: none;
        border-color: var(--green-mid);
        box-shadow: 0 0 0 4px rgba(46,125,82,0.10);
        background: #f8fdf9;
    }

    input::placeholder { color: #b0ccbb; font-weight: 500; }

    /* ===== BUTTON ===== */
    .submit-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--green-dark) 0%, var(--green-mid) 100%);
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 800;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.25s;
        font-family: 'Nunito', sans-serif;
        letter-spacing: 0.5px;
        box-shadow: 0 6px 20px rgba(27,77,53,0.35);
        position: relative;
        overflow: hidden;
    }

    .submit-btn::after {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.12), transparent);
        transition: left 0.5s ease;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 28px rgba(27,77,53,0.45);
    }

    .submit-btn:hover::after { left: 100%; }
    .submit-btn:active { transform: translateY(0); }

    /* ===== MESSAGE ===== */
    .message {
        padding: 14px 16px;
        border-radius: 12px;
        margin-bottom: 20px;
        text-align: center;
        border-left: 4px solid;
        font-weight: 700;
        font-size: 14px;
        animation: fadeUp 0.3s ease both;
    }

    .success { background: #eafaf1; color: #1b4d35; border-left-color: #2e8b57; }
    .error   { background: #fdf0f0; color: #922b21; border-left-color: #e74c3c; }

    /* ===== BOTTOM LINK ===== */
    .register-link,
    .login-link {
        text-align: center;
        margin-top: 20px;
        color: var(--text-light);
        font-size: 14px;
        font-weight: 600;
    }

    .register-link a,
    .login-link a {
        color: var(--green-dark);
        text-decoration: none;
        font-weight: 800;
        position: relative;
    }

    .register-link a::after,
    .login-link a::after {
        content: '';
        position: absolute;
        bottom: -2px; left: 0; right: 0;
        height: 2px;
        background: var(--gold);
        border-radius: 2px;
        transform: scaleX(0);
        transition: transform 0.25s ease;
    }

    .register-link a:hover::after,
    .login-link a:hover::after { transform: scaleX(1); }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 900px) {
        .container { flex-direction: column; max-width: 460px; }
        .design-side { padding: 36px 28px; }
        .form-side   { padding: 36px 28px; }
        .surau-title { font-size: 22px; }
        .lost-found  { font-size: 15px; }
        .form-title  { font-size: 26px; }
    }

    @media (max-width: 480px) {
        .role-selector { flex-direction: column; }
        body { padding: 16px; }
    }
</style>
    <script>
        let currentRole = 'user';
        
        function selectRole(role) {
            currentRole = role;
            
            // Remove selected class
            document.querySelectorAll('.role-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked
            event.target.classList.add('selected');
            
            // Update hidden role input
            document.getElementById('roleInput').value = role;
            
            // Update form labels and placeholders
            if(role === 'user') {
                document.getElementById('nameLabel').textContent = 'Name:';
                document.getElementById('nameInput').placeholder = 'Enter your name';
                document.getElementById('formTitle').textContent = 'Login as User';
                document.getElementById('formSubtitle').textContent = 'Access your user account to report or claim items';
            } else {
                document.getElementById('nameLabel').textContent = 'Name/ID:';
                document.getElementById('nameInput').placeholder = 'Enter your name or ID';
                document.getElementById('formTitle').textContent = 'Login as Admin';
                document.getElementById('formSubtitle').textContent = 'Access admin dashboard to manage lost items';
            }
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Select user by default
            document.querySelector('.role-option:first-child').classList.add('selected');
            document.getElementById('roleInput').value = 'user';
            document.getElementById('nameLabel').textContent = 'Name:';
            document.getElementById('nameInput').placeholder = 'Enter your name';
            document.getElementById('formTitle').textContent = 'Login as User';
            document.getElementById('formSubtitle').textContent = 'Access your user account to report or claim items';
        });
    </script>
</head>
<body>

<div class="container">
    <!-- LEFT SIDE - Design/Visual -->
    <div class="design-side">
        <div>
            <img src="Logo.png" alt="logo" class="logo">
        </div>
        
        <h1 class="surau-title">Surau Ismail Kharofa</h1>
        <div class="lost-found">Lost And Found</div>
        
        <img src="surau_pic.png" alt="surau" class="surau-image">
        
        <p class="design-quote">
            "Welcome back to our community lost and found system"
        </p>
    </div>
    
    <!-- RIGHT SIDE - Form -->
    <div class="form-side">
        <div class="form-container">
            <h2 class="form-title" id="formTitle">Welcome Back</h2>
            <p class="form-subtitle" id="formSubtitle">Sign in to your account to continue</p>
            
            <?php if($message != ""): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Role Selection -->
            <div class="role-selector">
                <div class="role-option" onclick="selectRole('user')">
                    <i style="margin-right: 8px;">👤</i> User Login
                </div>
                <div class="role-option" onclick="selectRole('admin')">
                    <i style="margin-right: 8px;">👑</i> Admin Login
                </div>
            </div>
            
            <form method="POST" action="">
                <!-- Hidden role input -->
                <input type="hidden" name="role" id="roleInput" value="user" required>
                
                <!-- LOGIN FIELDS -->
                <div class="form-group">
                    <label id="nameLabel">Name:</label>
                    <input type="text" name="name" id="nameInput" placeholder="Enter your name" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" name="login" class="submit-btn">
                    Sign In
                </button>
                
                <div class="register-link">
                    Don't have an account? <a href="register.php">Create Account (User Only)</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
<?php

session_start();
require_once 'config/email_config.php';

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email_tujuan = $_POST['email'];
    $nama_tujuan = $_POST['nama'] ?: 'Pengguna';
    
    // Generate OTP dummy for test
    $otp_test = rand(100000, 999999);
    
    // send email
    $emailSender = new EmailSender();
    $result = $emailSender->sendOTP($email_tujuan, $nama_tujuan, $otp_test);
    
    if ($result['success']) {
        $message = "✓ EMAIL BERJAYA DIHANTAR! OTP: $otp_test (check email $email_tujuan)";
        $message_class = 'success';
    } else {
        $message = "✗ GAGAL: " . $result['message'];
        $message_class = 'error';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Email 2FA</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; }
        .container { border: 1px solid #ddd; padding: 20px; border-radius: 5px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        .success { color: #155724; background: #d4edda; padding: 10px; border-radius: 4px; }
        .error { color: #721c24; background: #f8d7da; padding: 10px; border-radius: 4px; }
        .info { background: #e7f3ff; padding: 10px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test Email untuk 2FA</h1>
        
        <div class="info">
            <strong>📋 Maklumat SMTP:</strong><br>
            Host: smtp.gmail.com:587 (TLS)<br>
            Username: solivralunovae@gmail.com<br>
            App Password: [sudah diset dalam config/email_config.php]
        </div>
        
        <?php if ($message): ?>
            <div class="<?php echo $message_class; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Tujuan:</label>
                <input type="email" name="email" required placeholder="contoh: penerima@gmail.com">
            </div>
            
            <div class="form-group">
                <label>Nama Penerima (opsional):</label>
                <input type="text" name="nama" placeholder="Nama pengguna">
            </div>
            
            <button type="submit">🚀 Hantar Test Email</button>
        </form>
        
        <p style="margin-top: 20px; font-size: 0.9em; color: #666;">
            * Test ini akan hantar OTP dummy ke email yang anda masukkan.<br>
            * Check folder Inbox dan SPAM.
        </p>
    </div>
</body>
</html>
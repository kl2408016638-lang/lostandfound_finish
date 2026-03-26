<?php


require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';
require_once __DIR__ . '/../PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailSender {
    private $mail;
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_encryption = 'tls';
    private $smtp_username = 'solivralunovae@gmail.com';
    private $smtp_password = 'aiea libt cdiu ieya';  
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host       = $this->smtp_host;
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $this->smtp_username;
        $this->mail->Password   = $this->smtp_password;
        $this->mail->SMTPSecure = $this->smtp_encryption;
        $this->mail->Port       = $this->smtp_port;
        $this->mail->setFrom($this->smtp_username, 'LostAndFound System');
    }
    
    public function sendOTP($recipient_email, $recipient_name, $otp_code) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($recipient_email, $recipient_name);
            $this->mail->isHTML(true);
            $this->mail->Subject = '2FA Verification Code - LostAndFound';
            $this->mail->Body = "Your OTP code: <b>$otp_code</b>. Valid for 5 minutes.";
            $this->mail->AltBody = "Your OTP code: $otp_code. Valid for 5 minutes.";
            $this->mail->send();
            return ['success' => true];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }
}
?>
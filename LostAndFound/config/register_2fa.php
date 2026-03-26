<?php


require_once __DIR__ . '/email_config.php';
require_once __DIR__ . '/../db_connect.php';

class Register2FA {
    private $db;
    private $emailSender;
    
    public function __construct($connection) {
        $this->db = $connection;
        $this->emailSender = new EmailSender();
    }
    
    /**
     * Generate OTP 6 digit
     */
    private function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Store OTP in database
     */
    public function saveOTP($user_id, $otp_code) {
        // Delete old unused OTP 
        $delete = "DELETE FROM two_factor_tokens WHERE user_id = $user_id AND is_used = 0";
        mysqli_query($this->db, $delete);
        
        // Set expiry 5 minit
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Insert new OTP
        $insert = "INSERT INTO two_factor_tokens (user_id, otp_code, expires_at) 
                   VALUES ($user_id, '$otp_code', '$expires_at')";
        
        return mysqli_query($this->db, $insert);
    }
    
    /**
     * Send OTP for registration verification
     */
   public function sendVerificationOTP($user_id, $email, $name) {
    error_log("=== SEND VERIFICATION OTP DEBUG ===");
    error_log("User ID: " . $user_id);
    error_log("Email: " . $email);
    
    $otp = $this->generateOTP();
    error_log("Generated OTP: " . $otp);
    
    $save_result = $this->saveOTP($user_id, $otp);
    error_log("Save OTP result: " . ($save_result ? "SUCCESS" : "FAILED"));
    
    if ($save_result) {
        $send_result = $this->emailSender->sendOTP($email, $name, $otp);
        error_log("Send email result: " . ($send_result['success'] ? "SUCCESS" : "FAILED - " . $send_result['message']));
        return $send_result;
    }
    
    return ['success' => false, 'message' => 'Fail to save the OTP'];
}
    
    /**
     * Verify OTP 
     */
   public function verifyOTP($user_id, $user_otp) {
    error_log("=== VERIFY OTP DEBUG (SIMPLE MODE) ===");
    error_log("User ID: " . $user_id);
    error_log("User OTP: " . $user_otp);
    
    // CHECK ALL OTP for user - ignore expiry
    $query = "SELECT * FROM two_factor_tokens 
              WHERE user_id = $user_id 
              AND is_used = 0 
              ORDER BY id DESC LIMIT 1";  
    
    $result = mysqli_query($this->db, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $token = mysqli_fetch_assoc($result);
        
        error_log("Found OTP in DB: " . $token['otp_code']);
        error_log("Expires at: " . $token['expires_at']);
        error_log("Is used: " . $token['is_used']);
        
        // Compare OTP (exact match)
        if ($token['otp_code'] === $user_otp) {
            error_log("OTP MATCH! Activating user...");
            
            // Mark OTP as used
            $update = "UPDATE two_factor_tokens SET is_used = 1 WHERE id = " . $token['id'];
            $update_result = mysqli_query($this->db, $update);
            error_log("Update is_used: " . ($update_result ? "SUCCESS" : "FAILED"));
            
            // Actively use account
            $activate = "UPDATE accounts SET is_verified = 1 WHERE id = $user_id";
            $activate_result = mysqli_query($this->db, $activate);
            error_log("Activate account: " . ($activate_result ? "SUCCESS" : "FAILED"));
            
            if ($activate_result) {
                return ['success' => true, 'message' => 'Account successfully activated'];
            }
        } else {
            error_log("OTP MISMATCH! DB: '" . $token['otp_code'] . "', Input: '" . $user_otp . "'");
        }
    } else {
        error_log("NO OTP FOUND in database for user $user_id");
        
        // DEBUG: Check if table has any records at all
        $check_table = "SELECT COUNT(*) as total FROM two_factor_tokens";
        $table_result = mysqli_query($this->db, $check_table);
        $table_count = mysqli_fetch_assoc($table_result);
        error_log("Total records in two_factor_tokens: " . $table_count['total']);
        
        // Check all records for this user (including used ones)
        $check_all = "SELECT * FROM two_factor_tokens WHERE user_id = $user_id ORDER BY id DESC";
        $all_result = mysqli_query($this->db, $check_all);
        $num_rows = mysqli_num_rows($all_result);
        error_log("Found $num_rows total records for user $user_id (including used)");
        
        while($row = mysqli_fetch_assoc($all_result)) {
            error_log("Record: ID={$row['id']}, OTP={$row['otp_code']}, Used={$row['is_used']}, Expires={$row['expires_at']}");
        }
    }
    
    return ['success' => false, 'message' => 'OTP is invalid or expired'];
}
}
?>
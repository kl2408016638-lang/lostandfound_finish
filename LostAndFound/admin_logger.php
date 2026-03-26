<?php

function logAdminAction($connect, $admin_id, $admin_name, $action, $target_type = null, $target_id = null, $target_name = null, $description = '') {
    // Get IP address
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Sanitize inputs for avoid NULL issues
    $admin_id = (int)$admin_id;
    $admin_name = $admin_name ?? 'Unknown Admin';
    $target_id = $target_id ? (int)$target_id : null;
    $target_name = $target_name ?? null;
    
    // Prepare the query
    $sql = "INSERT INTO admin_logs (admin_id, admin_name, action, target_type, target_id, target_name, description, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($connect, $sql);
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssiiss", 
            $admin_id, 
            $admin_name, 
            $action, 
            $target_type, 
            $target_id, 
            $target_name, 
            $description, 
            $ip_address
        );
        
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return true;
    }
    
    return false;
}

// Helper function for get human readable action names
function getActionLabel($action) {
    $labels = [
        // Profile actions
        'update_profile' => 'Update Profile',
        'update_profile_pic' => 'Update Profile Picture',
        'update_name' => 'Update Name',
        'update_email' => 'Update Email',
        'update_password' => 'Update Password',
        
        // Found items actions
        'update_found_status' => 'Update Found Item Status',
        'approve_found_item' => 'Approve Found Item',
        'match_found_item' => 'Match Found Item',
        'claim_found_item' => 'Claim Found Item',
        
        // Lost items actions
        'update_lost_status' => 'Update Lost Item Status',
        'match_lost_item' => 'Match Lost Item',
        'claim_lost_item' => 'Claim Lost Item',
        
        // User account actions
        'edit_user' => 'Edit User',
        'delete_user' => 'Delete User',
        'add_user' => 'Add User',
        'view_user' => 'View User',
        
        // Item management
        'add_found_item' => 'Add Found Item',
        'edit_found_item' => 'Edit Found Item',
        'delete_found_item' => 'Delete Found Item',
        'add_lost_item' => 'Add Lost Item',
        'edit_lost_item' => 'Edit Lost Item',
        'delete_lost_item' => 'Delete Lost Item',
        
        // Archive actions
        'archive_item' => 'Archive Item',
        'unarchive_item' => 'Unarchive Item',
        'view_archive' => 'View Archive',
        
        // Legacy/General
        'login' => 'Login',
        'logout' => 'Logout',
        'update_status' => 'Update Item Status'
    ];
    
    return $labels[$action] ?? ucfirst(str_replace('_', ' ', $action));
}

// Helper function untuk get action badge color
function getActionBadgeColor($action) {
    $colors = [
        // Profile actions
        'update_profile' => 'info',
        'update_profile_pic' => 'info',
        'update_name' => 'info',
        'update_email' => 'info',
        'update_password' => 'warning',
        
        // Found items actions
        'update_found_status' => 'primary',
        'approve_found_item' => 'success',
        'match_found_item' => 'info',
        'claim_found_item' => 'success',
        
        // Lost items actions
        'update_lost_status' => 'primary',
        'match_lost_item' => 'info',
        'claim_lost_item' => 'success',
        
        // User account actions
        'edit_user' => 'warning',
        'delete_user' => 'danger',
        'add_user' => 'success',
        'view_user' => 'secondary',
        
        // Item management
        'add_found_item' => 'success',
        'edit_found_item' => 'warning',
        'delete_found_item' => 'danger',
        'add_lost_item' => 'success',
        'edit_lost_item' => 'warning',
        'delete_lost_item' => 'danger',
        
        // Archive actions
        'archive_item' => 'secondary',
        'unarchive_item' => 'secondary',
        'view_archive' => 'secondary',
        
        // Legacy/General
        'login' => 'success',
        'logout' => 'secondary',
        'update_status' => 'primary'
    ];
    
    return $colors[$action] ?? 'secondary';
}
?>
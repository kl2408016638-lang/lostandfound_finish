<?php

session_start();

// Only log if admin is logged in
if(isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'admin') {
    // Check if db_connect exists
    if(file_exists('db_connect.php')) {
        include_once 'db_connect.php';
        
        // Check if admin_logger exists
        if(file_exists('admin_logger.php')) {
            include_once 'admin_logger.php';
            
            // Check if function exists
            if(function_exists('logAdminAction')) {
                // Log the logout action
                logAdminAction($connect, $_SESSION['user_id'], $_SESSION['name'], 'logout', null, null, null, 'Admin logged out of system');
            }
        }
    }
}


$_SESSION = array();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>
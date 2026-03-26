<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Mark all as read
if(isset($_POST['mark_all_read'])) {
    $sql = "UPDATE notifications SET is_read = 1 WHERE user_id = '$user_id' AND is_read = 0";
    mysqli_query($connect, $sql);
}

// Mark single as read 
if(isset($_POST['mark_read']) && isset($_POST['notif_id'])) {
    $notif_id = $_POST['notif_id'];
    $sql = "UPDATE notifications SET is_read = 1 WHERE id = '$notif_id' AND user_id = '$user_id'";
    mysqli_query($connect, $sql);
}


if(isset($_POST['redirect'])) {
    header("Location: " . $_POST['redirect']);
    exit();
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
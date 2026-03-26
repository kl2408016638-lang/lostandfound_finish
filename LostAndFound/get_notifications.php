<?php
session_start();
include 'db_connect.php';
header('Content-Type: application/json');
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}
$user_id = $_SESSION['user_id'];

// Get unread count
$count_sql = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = '$user_id' AND is_read = 0";
$count_result = mysqli_query($connect, $count_sql);
$unread_count = mysqli_fetch_assoc($count_result)['unread'];


$notif_sql = "SELECT * FROM notifications WHERE user_id = '$user_id' AND is_read = 0 ORDER BY created_at DESC LIMIT 10";
$notif_result = mysqli_query($connect, $notif_sql);

$notifications = [];
while($row = mysqli_fetch_assoc($notif_result)) {
    $row['time_ago'] = timeAgo($row['created_at']);
    $notifications[] = $row;
}

echo json_encode([
    'unread' => $unread_count,
    'notifications' => $notifications
]);

function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "yesterday" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>
<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=admin_activity_log_' . date('Y-m-d') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Write CSV header
fputcsv($output, ['Timestamp', 'Admin ID', 'Admin Name', 'Action', 'Target Type', 'Target ID', 'Target Name', 'Description', 'IP Address']);


$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : "";
$action_filter = isset($_GET['action']) ? $_GET['action'] : "all";
$date_filter = isset($_GET['date']) ? $_GET['date'] : "all";

$sql = "SELECT * FROM admin_logs WHERE 1=1";

if($action_filter != "all") {
    $sql .= " AND action = '$action_filter'";
}

if(!empty($search)) {
    $sql .= " AND (admin_name LIKE '%$search%' OR target_name LIKE '%$search%' OR description LIKE '%$search%')";
}

switch($date_filter) {
    case 'today':
        $sql .= " AND DATE(created_at) = CURDATE()";
        break;
    case 'yesterday':
        $sql .= " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $sql .= " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
}

$sql .= " ORDER BY created_at DESC";

$result = mysqli_query($connect, $sql);

// Write data rows
while($log = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $log['created_at'],
        $log['admin_id'],
        $log['admin_name'],
        $log['action'],
        $log['target_type'] ?? '',
        $log['target_id'] ?? '',
        $log['target_name'] ?? '',
        $log['description'],
        $log['ip_address']
    ]);
}

fclose($output);
?>
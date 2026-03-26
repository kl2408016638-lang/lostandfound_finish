<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];

// Include admin logger for helper functions
include 'admin_logger.php';

// Filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : "";
$action_filter = isset($_GET['action']) ? $_GET['action'] : "all";
$date_filter = isset($_GET['date']) ? $_GET['date'] : "all";

// Build date condition
$date_condition = "";
switch($date_filter) {
    case 'today':
        $date_condition = " AND DATE(created_at) = CURDATE()";
        break;
    case 'yesterday':
        $date_condition = " AND DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        break;
    case 'week':
        $date_condition = " AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        break;
    case 'month':
        $date_condition = " AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        break;
    case 'all':
    default:
        $date_condition = "";
        break;
}

// Build query
$sql = "SELECT * FROM admin_logs WHERE 1=1";

// Apply action filter
if($action_filter != "all") {
    $sql .= " AND action = '$action_filter'";
}

// Apply search filter
if(!empty($search)) {
    $sql .= " AND (admin_name LIKE '%$search%' OR target_name LIKE '%$search%' OR description LIKE '%$search%')";
}

// Apply date filter
$sql .= $date_condition;

// Order by latest first
$sql .= " ORDER BY created_at DESC";

// Pagination
$results_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;

// Get total count
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_result = mysqli_query($connect, $count_sql);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $results_per_page);

// Apply pagination to main query
$sql .= " LIMIT $offset, $results_per_page";

$result = mysqli_query($connect, $sql);
$total_logs = mysqli_num_rows($result);

// Get distinct actions for filter dropdown
$actions_sql = "SELECT DISTINCT action FROM admin_logs ORDER BY action";
$actions_result = mysqli_query($connect, $actions_sql);
$available_actions = [];
while($action_row = mysqli_fetch_assoc($actions_result)) {
    $available_actions[] = $action_row['action'];
}

// Include admin sidebar
if(file_exists('admin_sidebar_nav.php')) {
    include 'admin_sidebar_nav.php';
} else {
    echo '<div style="padding:20px;background:#f8d7da;color:#721c24;">Admin sidebar not found.</div>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Activity Log</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .main-content {
            margin-left: 250px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .page-title {
            color: #2c3e50;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .page-title i {
            color: #3498db;
        }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        @media (max-width: 1024px) {
            .stats-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border-left: 4px solid;
        }
        
        .stat-card.total { border-left-color: #3498db; }
        .stat-card.today { border-left-color: #2ecc71; }
        .stat-card.users { border-left-color: #e74c3c; }
        .stat-card.items { border-left-color: #f39c12; }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .filters-container {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-label {
            color: #34495e;
            font-weight: 600;
            font-size: 14px;
        }
        
        .filter-select, .search-input {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .search-input {
            min-width: 250px;
            padding-left: 40px;
        }
        
        .search-container {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-clear {
            background: #95a5a6;
            color: white;
        }
        
        .btn-clear:hover {
            background: #7f8c8d;
        }
        
        .btn-export {
            background: #27ae60;
            color: white;
        }
        
        .btn-export:hover {
            background: #219653;
        }
        
        /* Action Badges */
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-primary { background: #cce5ff; color: #004085; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        .badge-secondary { background: #e2e3e5; color: #383d41; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 700;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #eee;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .log-details {
            font-size: 14px;
            line-height: 1.5;
        }
        
        .timestamp {
            color: #7f8c8d;
            font-size: 13px;
            white-space: nowrap;
        }
        
        .target-info {
            font-size: 13px;
            color: #3498db;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .page-link {
            padding: 8px 15px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: #3498db;
            font-size: 14px;
            min-width: 40px;
            text-align: center;
        }
        
        .page-link.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .page-link:hover:not(.active) {
            background: #f8f9fa;
        }
        
        .page-link.disabled {
            color: #95a5a6;
            cursor: not-allowed;
        }
        
        .export-options {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        @media (max-width: 768px) {
            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .filter-select, .search-input {
                width: 100%;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .timestamp {
                white-space: normal;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content">
            <!-- Dashboard Card -->
            <div class="dashboard-card">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-history"></i>
                        Admin Activity Log
                    </h1>
                    <div>
                        Logged in as: <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                    </div>
                </div>
                
                <!-- Stats Overview -->
                <div class="stats-container">
                    <?php
                    // Get stats
                    $today_sql = "SELECT COUNT(*) as count FROM admin_logs WHERE DATE(created_at) = CURDATE()";
                    $user_actions_sql = "SELECT COUNT(*) as count FROM admin_logs WHERE target_type = 'user'";
                    $item_actions_sql = "SELECT COUNT(*) as count FROM admin_logs WHERE target_type IN ('found_item', 'lost_item')";
                    
                    $today_result = mysqli_query($connect, $today_sql);
                    $user_result = mysqli_query($connect, $user_actions_sql);
                    $item_result = mysqli_query($connect, $item_actions_sql);
                    
                    $today_count = mysqli_fetch_assoc($today_result)['count'];
                    $user_actions_count = mysqli_fetch_assoc($user_result)['count'];
                    $item_actions_count = mysqli_fetch_assoc($item_result)['count'];
                    ?>
                    
                    <div class="stat-card total">
                        <div class="stat-number"><?php echo $total_rows; ?></div>
                        <div class="stat-label">Total Activities</div>
                    </div>
                    
                    <div class="stat-card today">
                        <div class="stat-number"><?php echo $today_count; ?></div>
                        <div class="stat-label">Today's Activities</div>
                    </div>
                    
                    <div class="stat-card users">
                        <div class="stat-number"><?php echo $user_actions_count; ?></div>
                        <div class="stat-label">User Actions</div>
                    </div>
                    
                    <div class="stat-card items">
                        <div class="stat-number"><?php echo $item_actions_count; ?></div>
                        <div class="stat-label">Item Actions</div>
                    </div>
                </div>
                
                <!-- Filters Section -->
                <form method="GET" action="" class="filters-container">
                    <div class="filter-group">
                        <span class="filter-label">Search:</span>
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Search admin, target, or description" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Action Type:</span>
                        <select name="action" class="filter-select">
                            <option value="all" <?php echo $action_filter == 'all' ? 'selected' : ''; ?>>All Actions</option>
                            <?php foreach($available_actions as $action): ?>
                                <option value="<?php echo $action; ?>" <?php echo $action_filter == $action ? 'selected' : ''; ?>>
                                    <?php echo getActionLabel($action); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <span class="filter-label">Date Range:</span>
                        <select name="date" class="filter-select">
                            <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                            <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                            <option value="yesterday" <?php echo $date_filter == 'yesterday' ? 'selected' : ''; ?>>Yesterday</option>
                            <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                            <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                    
                    <?php if(!empty($search) || $action_filter != 'all' || $date_filter != 'all'): ?>
                    <div class="filter-group">
                        <a href="admin_trail.php" class="btn btn-clear">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
                
                <!-- Activity Logs Table -->
                <?php if($total_logs > 0): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($log = mysqli_fetch_assoc($result)): 
                                    $badge_color = getActionBadgeColor($log['action']);
                                ?>
                                <tr>
                                    <td class="timestamp">
                                        <?php echo date('d/m/Y', strtotime($log['created_at'])); ?><br>
                                        <small><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($log['admin_name']); ?></strong><br>
                                        <small style="color: #7f8c8d;">ID: <?php echo $log['admin_id']; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $badge_color; ?>">
                                            <?php echo getActionLabel($log['action']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($log['target_type'] && $log['target_id']): ?>
                                            <div class="target-info">
                                                <?php echo ucfirst($log['target_type']); ?> #<?php echo $log['target_id']; ?><br>
                                                <?php if($log['target_name']): ?>
                                                    <small><?php echo htmlspecialchars(substr($log['target_name'], 0, 30)); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php else: ?>
                                            <span style="color: #95a5a6; font-style: italic;">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="log-details">
                                            <?php echo htmlspecialchars($log['description']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <code style="font-size: 12px; color: #7f8c8d;">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </code>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if($total_pages > 1): ?>
                    <div class="pagination">
                        <!-- Previous Page -->
                        <?php if($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $action_filter; ?>&date=<?php echo $date_filter; ?>"
                               class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if($start_page > 1): ?>
                            <a href="?page=1&search=<?php echo urlencode($search); ?>&action=<?php echo $action_filter; ?>&date=<?php echo $date_filter; ?>"
                               class="page-link">1</a>
                            <?php if($start_page > 2): ?>
                                <span class="page-link disabled">...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $action_filter; ?>&date=<?php echo $date_filter; ?>"
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if($end_page < $total_pages): ?>
                            <?php if($end_page < $total_pages - 1): ?>
                                <span class="page-link disabled">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $action_filter; ?>&date=<?php echo $date_filter; ?>"
                               class="page-link">
                                <?php echo $total_pages; ?>
                            </a>
                        <?php endif; ?>
                        
                        <!-- Next Page -->
                        <?php if($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&action=<?php echo $action_filter; ?>&date=<?php echo $date_filter; ?>"
                               class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="page-link disabled">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Summary -->
                    <div style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 14px; color: #7f8c8d;">
                        <i class="fas fa-info-circle"></i>
                        Showing <?php echo $total_logs; ?> of <?php echo $total_rows; ?> activity log(s)
                        <?php if(!empty($search)): ?> matching "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                        <?php if($action_filter != 'all'): ?> with action "<?php echo getActionLabel($action_filter); ?>"<?php endif; ?>
                        <?php if($date_filter != 'all'): ?> from "<?php echo ucfirst($date_filter); ?>"<?php endif; ?>
                        | Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                    </div>
                    
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <h3>No activity logs found</h3>
                        <p>
                            <?php if(!empty($search) || $action_filter != 'all' || $date_filter != 'all'): ?>
                                Try changing your search or filter criteria.
                            <?php else: ?>
                                No admin activities have been logged yet.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <!-- Export Options -->
                <?php if($total_logs > 0): ?>
                <div class="export-options">
                    <button type="button" class="btn btn-export" onclick="exportToCSV()">
                        <i class="fas fa-file-export"></i> Export to CSV
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-apply filter on select change
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', function() {
                // Only auto-submit if not in a form with search input
                if(this.closest('form').querySelector('.search-input').value === '') {
                    this.closest('form').submit();
                }
            });
        });
        
        // Export to CSV function
        function exportToCSV() {
            // Get current filter parameters
            const params = new URLSearchParams(window.location.search);
            
            // Create export URL
            let exportUrl = 'export_logs.php?';
            params.forEach((value, key) => {
                exportUrl += `${key}=${encodeURIComponent(value)}&`;
            });
            
            // Open in new tab to download
            window.open(exportUrl, '_blank');
        }
        
        // Auto-refresh page every 60 seconds (optional)
        setInterval(function() {
            if(!document.hidden) {
                // Don't refresh if user is interacting with filters
                if(!document.querySelector('.search-input:focus') && 
                   !document.querySelector('.filter-select:focus')) {
                    location.reload();
                }
            }
        }, 60000);
    </script>
</body>
</html>
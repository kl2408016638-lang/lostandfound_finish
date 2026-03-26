<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$message = "";

// Handle filters
$item_type = $_GET['type'] ?? 'all'; // all, lost, found
$search = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query to matched items (status = 'matched' atau 'claimed')
$where_clause = "status IN ('matched', 'claimed')"; // Archive = matched OR claimed

// Filter by item type
if($item_type == 'lost') {
    $where_clause .= " AND item_type = 'lost'";
} elseif($item_type == 'found') {
    $where_clause .= " AND item_type = 'found'";
}

// Search filter
if(!empty($search)) {
    $search = mysqli_real_escape_string($connect, $search);
    $where_clause .= " AND (type_item LIKE '%$search%' OR description LIKE '%$search%' OR location LIKE '%$search%' OR user_name LIKE '%$search%')";
}

// Date filter
if(!empty($date_from)) {
    $where_clause .= " AND date >= '$date_from'";
}
if(!empty($date_to)) {
    $where_clause .= " AND date <= '$date_to'";
}

// Get total counts for statistics
$count_sql = "SELECT 
                item_type,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'matched' THEN 1 ELSE 0 END) as matched_count,
                SUM(CASE WHEN status = 'claimed' THEN 1 ELSE 0 END) as claimed_count
              FROM items 
              WHERE status IN ('matched', 'claimed')
              GROUP BY item_type";
$count_result = mysqli_query($connect, $count_sql);

$stats = [
    'lost' => ['total' => 0, 'matched' => 0, 'claimed' => 0],
    'found' => ['total' => 0, 'matched' => 0, 'claimed' => 0]
];

while($row = mysqli_fetch_assoc($count_result)) {
    $stats[$row['item_type']] = [
        'total' => $row['total'],
        'matched' => $row['matched_count'],
        'claimed' => $row['claimed_count']
    ];
}

// Get items for display
$sql = "SELECT * FROM items WHERE $where_clause ORDER BY updated_at DESC, created_at DESC";
$result = mysqli_query($connect, $sql);
$total_items = mysqli_num_rows($result);


include 'admin_sidebar_nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Matched Items | Surau Ismail Kharofa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        .main-content {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-align: center;
            border-top: 4px solid;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.lost {
            border-top-color: #ff9800;
            background: linear-gradient(135deg, #fff8e1 0%, #fff3e0 100%);
        }
        
        .stat-card.found {
            border-top-color: #27ae60;
            background: linear-gradient(135deg, #e8f8ef 0%, #d5f0e1 100%);
        }
        
        .stat-card.total {
            border-top-color: #3498db;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
        }
        
        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-title {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-label {
            font-size: 14px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .filter-input, .filter-select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
        }
        
        .filter-btn {
            padding: 10px 25px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .filter-btn:hover {
            background: #2980b9;
        }
        
        .clear-btn {
            padding: 10px 25px;
            background: #95a5a6;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .clear-btn:hover {
            background: #7f8c8d;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .items-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 3px solid #1a252f;
        }
        
        .items-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        
        .items-table tr:hover {
            background: #f9f9f9;
        }
        
        .type-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-lost {
            background: #fff3e0;
            color: #ff9800;
            border: 1px solid #ffcc80;
        }
        
        .badge-found {
            background: #e8f8ef;
            color: #27ae60;
            border: 1px solid #a3e4b8;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-matched {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-claimed {
            background: #d4edda;
            color: #155724;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #eee;
            cursor: pointer;
        }
        
        .item-image:hover {
            border-color: #3498db;
        }
        
        .no-items {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .no-items i {
            font-size: 48px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 10px;
        }
        
        .page-btn {
            padding: 8px 16px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            color: #3498db;
        }
        
        .page-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .page-btn:hover:not(.active) {
            background: #f8f9fa;
        }
        
        .action-btns {
            display: flex;
            gap: 5px;
        }
        
        .view-btn {
            padding: 5px 10px;
            background: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .view-btn:hover {
            background: #2980b9;
        }
        
        .print-btn {
            padding: 10px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-left: auto;
        }
        
        .print-btn:hover {
            background: #219653;
        }
        
        @media (max-width: 1200px) {
            .filter-row {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .items-table {
                font-size: 14px;
            }
            
            .items-table th,
            .items-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main-content">
            
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h1 style="color: #2c3e50; margin-bottom: 10px; font-size: 28px;">
                        <i class="fas fa-archive" style="color: #3498db; margin-right: 10px;"></i>
                        Archive - Matched & Claimed Items
                    </h1>
                    <p style="color: #7f8c8d;">
                        View all successfully matched and claimed lost & found items
                    </p>
                </div>
                
                <button class="print-btn" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Archive
                </button>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-cards">
                <div class="stat-card lost">
                    <div class="stat-number"><?php echo $stats['lost']['total']; ?></div>
                    <div class="stat-title">Matched Lost Items</div>
                    <div style="margin-top: 10px; font-size: 13px; color: #7f8c8d;">
                        <div>Matched: <?php echo $stats['lost']['matched']; ?></div>
                        <div>Claimed: <?php echo $stats['lost']['claimed']; ?></div>
                    </div>
                </div>
                
                <div class="stat-card found">
                    <div class="stat-number"><?php echo $stats['found']['total']; ?></div>
                    <div class="stat-title">Matched Found Items</div>
                    <div style="margin-top: 10px; font-size: 13px; color: #7f8c8d;">
                        <div>Matched: <?php echo $stats['found']['matched']; ?></div>
                        <div>Claimed: <?php echo $stats['found']['claimed']; ?></div>
                    </div>
                </div>
                
                <div class="stat-card total">
                    <div class="stat-number"><?php echo $stats['lost']['total'] + $stats['found']['total']; ?></div>
                    <div class="stat-title">Total Archived Items</div>
                    <div style="margin-top: 10px; font-size: 13px; color: #7f8c8d;">
                        Showing: <?php echo $total_items; ?> items
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-label">Item Type:</label>
                            <select name="type" class="filter-select">
                                <option value="all" <?php echo $item_type == 'all' ? 'selected' : ''; ?>>All Items</option>
                                <option value="lost" <?php echo $item_type == 'lost' ? 'selected' : ''; ?>>Lost Items Only</option>
                                <option value="found" <?php echo $item_type == 'found' ? 'selected' : ''; ?>>Found Items Only</option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Search:</label>
                            <input type="text" name="search" class="filter-input" 
                                   placeholder="Search by item, description, location, or user" 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Date From:</label>
                            <input type="date" name="date_from" class="filter-input" value="<?php echo $date_from; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Date To:</label>
                            <input type="date" name="date_to" class="filter-input" value="<?php echo $date_to; ?>">
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="archive_items.php" class="clear-btn">
                            <i class="fas fa-times"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Results Info -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <strong>Results:</strong> <?php echo $total_items; ?> items found
                    <?php if($item_type != 'all'): ?>
                        <span style="margin-left: 10px; padding: 3px 10px; background: #f0f0f0; border-radius: 12px;">
                            Filter: <?php echo ucfirst($item_type); ?> Items
                        </span>
                    <?php endif; ?>
                </div>
                
                
            </div>
            
            <!-- Items Table -->
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div style="overflow-x: auto;">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Item</th>
                                <th>Date</th>
                                <th>Location</th>
                                <th>Picture</th>
                                <th>Reported By</th>
                                <th>Status</th>
                                <th>Details</th>
                                <th>Updated On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = mysqli_fetch_assoc($result)): 
                                $is_lost = ($item['item_type'] == 'lost');
                            ?>
                            <tr>
                                <td>
                                    <span class="type-badge <?php echo $is_lost ? 'badge-lost' : 'badge-found'; ?>">
                                        <i class="fas <?php echo $is_lost ? 'fa-search' : 'fa-hand-holding-heart'; ?>"></i>
                                        <?php echo ucfirst($item['item_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(ucfirst($item['type_item'])); ?></strong><br>
                                    <small style="color: #7f8c8d;">
                                        <?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?>
                                        <?php if(strlen($item['description']) > 60): ?>...<?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['date']); ?><br>
                                    <small style="color: #7f8c8d;"><?php echo htmlspecialchars($item['time']); ?></small>
                                </td>
                                <td>
                                    <?php 
                                    $location = $item['location'];
                                    if($location == 'other' && isset($item['custom_location'])) {
                                        echo htmlspecialchars(ucfirst($item['custom_location']));
                                    } else {
                                        echo htmlspecialchars(ucwords(str_replace('_', ' ', $location)));
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if(!empty($item['picture']) && file_exists('uploads/' . $item['picture'])): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($item['picture']); ?>" 
                                             alt="Item Picture" class="item-image"
                                             onclick="openImage('uploads/<?php echo htmlspecialchars($item['picture']); ?>')">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 6px; 
                                                    display: flex; align-items: center; justify-content: center; 
                                                    color: #6c757d; border: 2px dashed #ddd;">
                                            <i class="fas fa-image" style="font-size: 16px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['user_name']); ?><br>
                                    <small style="color: #7f8c8d;">ID: <?php echo $item['user_id']; ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php if($item['status'] == 'matched'): ?>
                                            <i class="fas fa-link"></i> Matched
                                        <?php else: ?>
                                            <i class="fas fa-handshake"></i> Claimed
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                       <button onclick="showDetails(
                                        '<?php echo htmlspecialchars(addslashes($item['type_item'])); ?>',
                                        '<?php echo htmlspecialchars(addslashes($item['item_type'])); ?>',
                                        '<?php echo htmlspecialchars(addslashes($item['description'])); ?>',
                                        '<?php echo htmlspecialchars($item['date']); ?>',
                                        '<?php echo htmlspecialchars($item['time']); ?>',
                                        '<?php echo htmlspecialchars(addslashes(ucwords(str_replace('_', ' ', $item['location'])))); ?>',
                                        '<?php echo htmlspecialchars(addslashes($item['user_name'])); ?>',
                                        '<?php echo $item['status']; ?>',
                                        '<?php echo !empty($item['picture']) ? "uploads/" . htmlspecialchars($item['picture']) : ""; ?>'
                                    )" class="view-btn">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    </div>
                                </td>
                                <td style="color: #666; font-size: 13px;">
                                    <?php echo date('d/m/Y', strtotime($item['updated_at'])); ?><br>
                                    <small><?php echo date('H:i', strtotime($item['updated_at'])); ?></small>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-archive"></i>
                    <h3 style="color: #7f8c8d; margin-bottom: 10px;">
                        <?php if(!empty($search) || !empty($date_from) || !empty($date_to) || $item_type != 'all'): ?>
                            No archived items match your filters
                        <?php else: ?>
                            No items have been matched or claimed yet
                        <?php endif; ?>
                    </h3>
                    <p style="color: #95a5a6; font-size: 14px;">
                        Archived items appear here once they are marked as "Matched" or "Claimed"
                    </p>
                    <?php if(!empty($search) || !empty($date_from) || !empty($date_to) || $item_type != 'all'): ?>
                    <a href="archive_items.php" style="margin-top: 15px; padding: 8px 20px; background: #3498db; color: white; 
                                                       border-radius: 5px; text-decoration: none; display: inline-flex; 
                                                       align-items: center; gap: 8px;">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Details Modal -->
<div id="detailsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
     background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
    <div style="background:white; border-radius:16px; padding:35px; max-width:520px; width:90%; 
                box-shadow:0 20px 60px rgba(0,0,0,0.2); position:relative;">
        <button onclick="closeDetails()" style="position:absolute; top:15px; right:20px; background:none; 
                border:none; font-size:24px; cursor:pointer; color:#7f8c8d;">&times;</button>
        <h3 id="modal-title" style="margin-bottom:20px; color:#2c3e50; font-size:20px;"></h3>
        <div id="modal-img-wrap" style="text-align:center; margin-bottom:20px;"></div>
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:10px 0; color:#7f8c8d; width:40%;">Type</td>
                <td style="padding:10px 0; font-weight:600;" id="m-type"></td>
            </tr>
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:10px 0; color:#7f8c8d;">Date & Time</td>
                <td style="padding:10px 0; font-weight:600;" id="m-date"></td>
            </tr>
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:10px 0; color:#7f8c8d;">Location</td>
                <td style="padding:10px 0; font-weight:600;" id="m-location"></td>
            </tr>
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:10px 0; color:#7f8c8d;">Reported By</td>
                <td style="padding:10px 0; font-weight:600;" id="m-user"></td>
            </tr>
            <tr style="border-bottom:1px solid #f0f0f0;">
                <td style="padding:10px 0; color:#7f8c8d;">Status</td>
                <td style="padding:10px 0;" id="m-status"></td>
            </tr>
            <tr>
                <td style="padding:10px 0; color:#7f8c8d; vertical-align:top;">Description</td>
                <td style="padding:10px 0;" id="m-desc"></td>
            </tr>
        </table>
    </div>
</div>
            
        </div>
    </div>
    
    <script>
        // Function to open image in new tab
        function openImage(src) {
            window.open(src, '_blank', 'width=800,height=600');
        }
        
        // Set default dates for filter
        document.addEventListener('DOMContentLoaded', function() {
            // Set date_to to today
            const today = new Date().toISOString().split('T')[0];
            if(!document.querySelector('input[name="date_to"]').value) {
                document.querySelector('input[name="date_to"]').value = today;
            }
            
            // Set date_from to 30 days ago
            const thirtyDaysAgo = new Date();
            thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
            const thirtyDaysAgoStr = thirtyDaysAgo.toISOString().split('T')[0];
            
            if(!document.querySelector('input[name="date_from"]').value) {
                document.querySelector('input[name="date_from"]').value = thirtyDaysAgoStr;
            }
        });
        
        // Print function with better formatting
        function printArchive() {
            const printContent = document.querySelector('.main-content').innerHTML;
            const originalContent = document.body.innerHTML;
            
            document.body.innerHTML = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Archive Report - Surau Ismail Kharofa</title>
                    <style>
                        body { font-family: Arial, sans-serif; padding: 20px; }
                        .print-header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
                        .print-stats { display: flex; justify-content: space-around; margin-bottom: 30px; }
                        .print-stat { text-align: center; }
                        .print-table { width: 100%; border-collapse: collapse; }
                        .print-table th, .print-table td { border: 1px solid #ddd; padding: 8px; }
                        .print-table th { background: #f2f2f2; }
                        .print-footer { margin-top: 50px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h1>Archive Report - Matched & Claimed Items</h1>
                        <p>Surau Ismail Kharofa Lost & Found System</p>
                        <p>Generated on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
                    </div>
                    
                    <div class="print-stats">
                        <div class="print-stat">
                            <h3>${<?php echo $stats['lost']['total']; ?>}</h3>
                            <p>Lost Items</p>
                        </div>
                        <div class="print-stat">
                            <h3>${<?php echo $stats['found']['total']; ?>}</h3>
                            <p>Found Items</p>
                        </div>
                        <div class="print-stat">
                            <h3>${<?php echo $stats['lost']['total'] + $stats['found']['total']; ?>}</h3>
                            <p>Total Archived</p>
                        </div>
                    </div>
                    
                    ${document.querySelector('.items-table').outerHTML}
                    
                    <div class="print-footer">
                        <p>This is an auto-generated report from Surau Ismail Kharofa Lost & Found System</p>
                        <p>Confidential - For internal use only</p>
                    </div>
                </body>
                </html>
            `;
            
            window.print();
            document.body.innerHTML = originalContent;
            location.reload();
        }

        function showDetails(typeItem, itemType, desc, date, time, location, user, status, pic) {
    document.getElementById('modal-title').innerHTML = 
        `<i class="fas ${itemType === 'lost' ? 'fa-search' : 'fa-hand-holding-heart'}" 
        style="color:${itemType === 'lost' ? '#ff9800' : '#27ae60'}; margin-right:8px;"></i>
        ${itemType.charAt(0).toUpperCase() + itemType.slice(1)} Item — ${typeItem.charAt(0).toUpperCase() + typeItem.slice(1)}`;
    document.getElementById('m-type').textContent = itemType.charAt(0).toUpperCase() + itemType.slice(1);
    document.getElementById('m-date').textContent = date + ' ' + time;
    document.getElementById('m-location').textContent = location;
    document.getElementById('m-user').textContent = user;
    document.getElementById('m-status').innerHTML = 
        `<span class="status-badge status-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>`;
    document.getElementById('m-desc').textContent = desc;
    
    const imgWrap = document.getElementById('modal-img-wrap');
    imgWrap.innerHTML = pic 
        ? `<img src="${pic}" style="max-width:180px; max-height:180px; border-radius:10px; border:2px solid #eee; cursor:pointer;" 
               onclick="openImage('${pic}')">`
        : `<div style="color:#bdc3c7; font-size:13px;"><i class="fas fa-image" style="font-size:32px; display:block; margin-bottom:8px;"></i>No image</div>`;
    
    const modal = document.getElementById('detailsModal');
modal.style.setProperty('display', 'flex', 'important');
}

function closeDetails() {
    document.getElementById('detailsModal').style.setProperty('display', 'none', 'important');
}

window.addEventListener('click', function(e) {
    if(e.target.id === 'detailsModal') closeDetails();
});
    </script>
</body>
</html>
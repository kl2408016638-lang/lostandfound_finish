<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_name = $_SESSION['name'];
$message = "";

// Handle status update (FOR ADMIN ONLY - FOUND ITEMS)
if($user_role == 'admin' && isset($_POST['update_status'])) {
    $item_id = $_POST['item_id'];
    $new_status = $_POST['status'];
    
    // Get item details for logging
    $get_old_sql = "SELECT status, type_item, item_type, user_id, user_name FROM items WHERE id = '$item_id'";
    $old_result = mysqli_query($connect, $get_old_sql);
    $old_data = mysqli_fetch_assoc($old_result);
    $old_status = $old_data['status'] ?? 'unknown';
    $item_name = $old_data['type_item'] ?? 'Unknown Item';
    $item_type = $old_data['item_type'] ?? 'found';
    $reported_by = $old_data['user_name'] ?? 'Unknown';
    $reporter_id = $old_data['user_id'] ?? 0;
    
    // Validate: Found items can have all statuses
    $allowed_statuses = ['pending', 'approved', 'matched', 'claimed'];
    
    if($new_status == $old_status) {
        $message = "Error: Item already has status '{$old_status}'!";
    }
    elseif(in_array($new_status, $allowed_statuses)) {
        // Update status
        $update_sql = "UPDATE items SET status='$new_status', updated_at=NOW() WHERE id='$item_id'";
        
        if(mysqli_query($connect, $update_sql)) {
            $message = "Item status updated successfully!";
            
            // ===== NOTIFICATION FOR USER =====
            
            $safe_item_name = mysqli_real_escape_string($connect, $item_name);
            $safe_old_status = mysqli_real_escape_string($connect, $old_status);
            $safe_new_status = mysqli_real_escape_string($connect, $new_status);
            
            $notif_title = "Item Status Updated";
            $notif_message = "Your found item '" . $safe_item_name . "' status has been changed from " . $safe_old_status . " to " . $safe_new_status . ".";
            $notif_link = "user_dashboard.php";
            
            $title_esc = mysqli_real_escape_string($connect, $notif_title);
            $msg_esc = mysqli_real_escape_string($connect, $notif_message);
            $link_esc = mysqli_real_escape_string($connect, $notif_link);
            
            $user_notif_sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                               VALUES ('$reporter_id', 'status_update', '$title_esc', '$msg_esc', '$link_esc')";
            mysqli_query($connect, $user_notif_sql);
            
            
            // LOGGING with specific action based on new status
            include_once 'admin_logger.php';
            
            // Determine specific action
            $action_type = 'update_found_status';
            if($new_status == 'approved') {
                $action_type = 'approve_found_item';
            } elseif($new_status == 'matched') {
                $action_type = 'match_found_item';
                
                // Also log archive entry
                $archive_desc = "Found item #{$item_id} ({$item_name}) matched with lost report - added to archive";
                logAdminAction($connect, $user_id, $user_name, 'archive_item', 'found_item', $item_id, $item_name, $archive_desc);
                
            } elseif($new_status == 'claimed') {
                $action_type = 'claim_found_item';
                
                // Also log archive entry
                $archive_desc = "Found item #{$item_id} ({$item_name}) claimed by owner - added to archive";
                logAdminAction($connect, $user_id, $user_name, 'archive_item', 'found_item', $item_id, $item_name, $archive_desc);
            }
            
            // Main status update log
            $log_desc = "Updated found item #{$item_id} ({$item_name}) reported by {$reported_by} from {$old_status} to {$new_status}";
            logAdminAction($connect, $user_id, $user_name, $action_type, 'found_item', $item_id, $item_name, $log_desc);
            
        } else {
            $message = "Error: " . mysqli_error($connect);
        }
    } else {
        $message = "Error: Invalid status for found item!";
    }
}

// Search functionality
$search = "";
if(isset($_GET['search'])) {
    $search = mysqli_real_escape_string($connect, $_GET['search']);
}

// Query to get FOUND items only - EXCLUDE matched & claimed (archive)
$where_clause = "item_type = 'found' AND status NOT IN ('matched', 'claimed')";

if(!empty($search)) {
    $where_clause .= " AND (type_item LIKE '%$search%' OR description LIKE '%$search%' OR location LIKE '%$search%')";
}

$sql = "SELECT * FROM items WHERE $where_clause ORDER BY created_at DESC";
$result = mysqli_query($connect, $sql);
$total_items = mysqli_num_rows($result);

// Include sidebar
if($user_role == 'admin') {
    include 'admin_sidebar_nav.php';
} else {
    include 'sidebar_nav.php';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Found Items - Surau Ismail Kharofa</title>
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
            max-width: 1400px;
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
        }
        
        .stats-badge {
            background: #e8f8ef;
            color: #27ae60;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .search-box {
            flex: 1;
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }
        
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .search-btn {
            background: #27ae60;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            background: #219653;
        }
        
        .clear-btn {
            padding: 12px 20px;
            background: #6c757d;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .clear-btn:hover {
            background: #5a6268;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            color: #2c3e50;
            font-weight: 700;
            border-bottom: 2px solid #ddd;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d1ecf1; color: #0c5460; }
        .status-matched { background: #d1ecf1; color: #0c5460; }
        .status-claimed { background: #d4edda; color: #155724; }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #eee;
            cursor: pointer;
        }
        
        .no-image {
            width: 80px;
            height: 80px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            border: 2px dashed #ddd;
        }
        
        .action-btn {
            padding: 6px 12px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            margin: 2px;
        }
        
        .btn-edit { background: #ffc107; color: black; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-view { background: #17a2b8; color: white; }
        
        .info-box {
            margin-top: 40px;
            padding: 20px;
            background: #e8f8ef;
            border-radius: 10px;
            border-left: 4px solid #27ae60;
        }
        
        .no-items {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        
        .archive-link {
            color: #27ae60;
            font-weight: 600;
            text-decoration: none;
        }
        
        .archive-link:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .main-content {
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
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
                        <i class="fas fa-hand-holding-heart" style="color: #27ae60; margin-right: 10px;"></i>
                        Found Items List
                    </h1>
                    <p style="color: #7f8c8d;">
                        <?php echo $user_role == 'admin' ? 'Manage and update found items status' : 'Browse found items in the surau'; ?>
                    </p>
                </div>
                
                <div class="stats-badge">
                    <i class="fas fa-box"></i>
                    Active Found Items: <?php echo $total_items; ?> (Pending/Approved)
                </div>
            </div>
            
            <?php if($message != ""): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Search Form -->
            <form method="GET" action="" class="search-form">
                <div class="search-box">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" placeholder="Search by item type, description, or location" 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                </div>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search" style="margin-right: 8px;"></i> Search
                </button>
                <?php if(!empty($search)): ?>
                    <a href="list_found.php" class="clear-btn">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
            
            <?php if(!empty($search)): ?>
                <div style="background: #e7f3fe; padding: 15px; border-radius: 8px; margin-bottom: 20px; 
                            border-left: 4px solid #2196F3; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        Search results for: <strong style="color: #2196F3;"><?php echo htmlspecialchars($search); ?></strong>
                        | Found: <strong><?php echo $total_items; ?></strong> items
                    </div>
                    <?php if($user_role == 'admin'): ?>
                        <button onclick="window.print()" style="background: #6c757d; color: white; padding: 8px 15px; 
                                                                border: none; border-radius: 5px; cursor: pointer; font-size: 14px;">
                            <i class="fas fa-print" style="margin-right: 5px;"></i> Print
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Found Items Table -->
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Date Found</th>
                                <th>Location</th>
                                <th>Picture</th>
                                <th>Description</th>
                                <?php if($user_role == 'admin'): ?><th>Found By</th><?php endif; ?>
                                <th>Status</th>
                                <?php if($user_role == 'admin'): ?>
                                    <th>Change Status</th>
                                <?php endif; ?>
                                <th>Reported On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #27ae60;"></span>
                                        <?php 
                                        $item_labels = [
                                            'wallet'           => 'Wallet/Purse',
                                            'phone'            => 'Mobile Phone',
                                            'keys'             => 'Keys',
                                            'documents'        => 'Documents',
                                            'jewelry'          => 'Jewelry',
                                            'sandal'           => 'Sandal',
                                            'clothing'         => 'Clothing',
                                            'umbrella'         => 'Umbrella',
                                            'reading_material' => 'Al-Quran/Books',
                                            'prayer_equipment' => 'Prayer Equipment',
                                            'food_container'   => 'Water Bottle/Food Container',
                                            'other'            => 'Other',
                                        ];
                                        $type = $item['type_item'];
                                        echo htmlspecialchars($item_labels[$type] ?? ucfirst($type));
                                        ?>
                                    </div>
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
                                             onclick="window.open('uploads/<?php echo htmlspecialchars($item['picture']); ?>', '_blank')">
                                    <?php else: ?>
                                        <div class="no-image">
                                            <i class="fas fa-image" style="font-size: 20px;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="max-width: 250px;">
                                    <?php echo htmlspecialchars(substr($item['description'], 0, 100)); ?>
                                    <?php if(strlen($item['description']) > 100): ?>...<?php endif; ?>
                                </td>
                                <?php if($user_role == 'admin'): ?>
                                <td>
                                    <?php echo htmlspecialchars($item['user_name']); ?><br>
                                    <small style="color: #7f8c8d;">ID: <?php echo $item['user_id']; ?></small>
                                </td>
                                <?php endif; ?>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                
                                <?php if($user_role == 'admin'): ?>
                                    <!-- ADMIN: Change Status Form -->
                                    <td>
                                        <form method="POST" action="" style="display: flex; gap: 5px; align-items: center;">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <select name="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px; 
                                                                        font-size: 14px; background: white; min-width: 100px;" required>
                                                <option value="pending" <?php echo $item['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="approved" <?php echo $item['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                <option value="matched" <?php echo $item['status'] == 'matched' ? 'selected' : ''; ?>>Matched</option>
                                                <option value="claimed" <?php echo $item['status'] == 'claimed' ? 'selected' : ''; ?>>Claimed</option>
                                            </select>
                                            <button type="submit" name="update_status" 
                                                    style="background: #28a745; color: white; padding: 8px 12px; border: none; 
                                                            border-radius: 5px; cursor: pointer; font-size: 12px;">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                    </td>
                                <?php endif; ?>
                                
                                <td style="color: #666; font-size: 14px;">
                                    <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-items">
                    <i class="fas fa-hand-holding-heart" style="font-size: 48px; color: #bdc3c7; margin-bottom: 20px;"></i>
                    <h3 style="color: #7f8c8d; margin-bottom: 10px;">
                        <?php if(!empty($search)): ?>
                            No active found items matching "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            No active found items available
                        <?php endif; ?>
                    </h3>
                    <p style="color: #95a5a6; font-size: 14px;">
                        <?php if($user_role == 'admin'): ?>
                            Check back later or <a href="archive_items.php" class="archive-link">view archived items</a>.
                        <?php else: ?>
                            <a href="form_item.php" style="color: #27ae60; text-decoration: none; font-weight: 600;">
                                <i class="fas fa-plus-circle"></i> Report a Found Item
                            </a>
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Information Box -->
            <div class="info-box">
                <h4 style="color: #27ae60; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-info-circle"></i>
                    <?php echo $user_role == 'admin' ? 'Admin Information' : 'User Information'; ?>
                </h4>
                <p style="color: #666; line-height: 1.6; font-size: 14px;">
                    <?php if($user_role == 'admin'): ?>
                        • <strong>Status Flow for Found Items:</strong> Pending → Approved → Matched → Claimed<br>
                        • <strong>Note:</strong> Items with status "Matched" or "Claimed" are automatically moved to Archive<br>
                        • View archived items in <a href="archive_items.php" class="archive-link">Archive Page</a><br>
                        • Use search to quickly find specific items
                    <?php else: ?>
                        • <strong>Status Meanings:</strong><br>
                        &nbsp;&nbsp;- <span class="status-badge status-pending" style="font-size: 10px;">Pending</span>: Item is awaiting admin review<br>
                        &nbsp;&nbsp;- <span class="status-badge status-approved" style="font-size: 10px;">Approved</span>: Item verified and in storage<br>
                        &nbsp;&nbsp;- <span class="status-badge status-matched" style="font-size: 10px;">Matched</span>: Item matched with a lost report<br>
                        &nbsp;&nbsp;- <span class="status-badge status-claimed" style="font-size: 10px;">Claimed</span>: Item has been claimed by owner<br>
                        • Once an item is "Matched" or "Claimed", it will be moved to archive page admin
                    <?php endif; ?>
                </p>
            </div>
            
        </div>
    </div>
    
    <script>
        // Auto-refresh for admin every 30 seconds
        <?php if($user_role == 'admin'): ?>
        setTimeout(function() {
            if(!document.hidden) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
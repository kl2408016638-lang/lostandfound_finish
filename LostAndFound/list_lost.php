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

// Handle status update (FOR LOST ITEMS)
if(isset($_POST['update_status'])) {
    $item_id = $_POST['item_id'];
    $new_status = $_POST['status'];
    
    // Get item details for logging
    $get_old_sql = "SELECT status, type_item, user_id, user_name FROM items WHERE id = '$item_id' AND item_type = 'lost'";
    $old_result = mysqli_query($connect, $get_old_sql);
    $old_data = mysqli_fetch_assoc($old_result);
    $old_status = $old_data['status'] ?? 'unknown';
    $item_name = $old_data['type_item'] ?? 'Unknown Item';
    $reported_by = $old_data['user_name'] ?? 'Unknown';
    $reporter_id = $old_data['user_id'] ?? 0;
    
    // Validate: Lost items can only have matched or claimed status
    $allowed_statuses = ['matched', 'claimed'];
    
    
    if($new_status == $old_status) {
        $message = "Error: Item already has status '{$old_status}'!";
    }
    elseif(in_array($new_status, $allowed_statuses)) {
        // Update status
        $update_sql = "UPDATE items SET status='$new_status', updated_at=NOW() WHERE id='$item_id'";
        
        if(mysqli_query($connect, $update_sql)) {
            $message = "Lost item status updated successfully!";
            
            // ===== NOTIFICATION FOR USER =====
            
            $safe_item_name = mysqli_real_escape_string($connect, $item_name);
            $safe_old_status = mysqli_real_escape_string($connect, $old_status);
            $safe_new_status = mysqli_real_escape_string($connect, $new_status);
            
            $notif_title = "Item Status Updated";
            $notif_message = "Your lost item '" . $safe_item_name . "' status has been changed from " . $safe_old_status . " to " . $safe_new_status . ".";
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
            $action_type = 'update_lost_status';
            if($new_status == 'matched') {
                $action_type = 'match_lost_item';
                
                // Also log archive entry
                $archive_desc = "Lost item #{$item_id} ({$item_name}) matched with found item - added to archive";
                logAdminAction($connect, $user_id, $user_name, 'archive_item', 'lost_item', $item_id, $item_name, $archive_desc);
                
            } elseif($new_status == 'claimed') {
                $action_type = 'claim_lost_item';
                
                // Also log archive entry
                $archive_desc = "Lost item #{$item_id} ({$item_name}) claimed by owner - added to archive";
                logAdminAction($connect, $user_id, $user_name, 'archive_item', 'lost_item', $item_id, $item_name, $archive_desc);
            }
            
            // Main status update log
            $log_desc = "Updated lost item #{$item_id} ({$item_name}) reported by {$reported_by} from {$old_status} to {$new_status}";
            logAdminAction($connect, $user_id, $user_name, $action_type, 'lost_item', $item_id, $item_name, $log_desc);
            
        } else {
            $message = "Error: " . mysqli_error($connect);
        }
    } else {
        $message = "Error: Invalid status for lost item! Only 'matched' or 'claimed' allowed.";
    }
}

// Search functionality
$search = "";
if(isset($_GET['search'])) {
    $search = mysqli_real_escape_string($connect, $_GET['search']);
}

// Query to get LOST items only - EXCLUDE matched & claimed (archive)
$where_clause = "item_type = 'lost' AND status NOT IN ('matched', 'claimed')";

if(!empty($search)) {
    $where_clause .= " AND (type_item LIKE '%$search%' OR description LIKE '%$search%' OR location LIKE '%$search%')";
}

$sql = "SELECT * FROM items WHERE $where_clause ORDER BY created_at DESC";
$result = mysqli_query($connect, $sql);
$total_items = mysqli_num_rows($result);

// Include admin sidebar
include 'admin_sidebar_nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Items (Admin) - Surau Ismail Kharofa</title>
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
            background: #fff3e0;
            color: #ff9800;
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
        
        .search-input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-btn {
            background: #ff9800;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            font-weight: 600;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: 700;
            color: #2c3e50;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        tr:hover {
            background-color: #f9f9f9;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #eee;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .item-image:hover {
            transform: scale(1.1);
            border-color: #ff9800;
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
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-matched { background: #d1ecf1; color: #0c5460; }
        .status-claimed { background: #d4edda; color: #155724; }
        
        .info-box {
            margin-top: 40px;
            padding: 20px;
            background: #fff3e0;
            border-radius: 10px;
            border-left: 4px solid #ff9800;
        }
        
        .archive-link {
            color: #ff9800;
            font-weight: 600;
            text-decoration: none;
        }
        
        .archive-link:hover {
            text-decoration: underline;
        }
        
        .no-items {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-size: 18px;
            background: #f8f9fa;
            border-radius: 10px;
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
                        <i class="fas fa-search" style="color: #ff9800; margin-right: 10px;"></i>
                        Lost Items List (Admin Only)
                    </h1>
                    <p style="color: #7f8c8d;">
                        Manage lost item reports from users
                    </p>
                </div>
                
                <div class="stats-badge">
                    <i class="fas fa-exclamation-triangle"></i>
                    Active Lost Items: <?php echo $total_items; ?> (Pending only)
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
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #7f8c8d;"></i>
                    <input type="text" name="search" placeholder="Search lost items..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                </div>
                <button type="submit" class="search-btn">
                    <i class="fas fa-search" style="margin-right: 8px;"></i> Search
                </button>
                <?php if(!empty($search)): ?>
                    <a href="list_lost.php" class="clear-btn">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </form>
            
            <!-- Lost Items Table -->
            <?php if(mysqli_num_rows($result) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Date Lost</th>
                                <th>Location</th>
                                <th>Picture</th>
                                <th>Description</th>
                                <th>Reported By</th>
                                <th>Status</th>
                                <th>Change Status</th>
                                <th>Reported On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span style="width: 12px; height: 12px; border-radius: 50%; background: #ff9800;"></span>
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
                                <td>
                                    <?php echo htmlspecialchars($item['user_name']); ?><br>
                                    <small style="color: #7f8c8d;">ID: <?php echo $item['user_id']; ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $item['status']; ?>">
                                        <?php echo ucfirst($item['status']); ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <form method="POST" action="" style="display: flex; gap: 5px; align-items: center;">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <select name="status" style="padding: 8px; border: 1px solid #ddd; border-radius: 5px; 
                                                                    font-size: 14px; background: white; min-width: 100px;" required>
                                            <option value="pending" <?php echo $item['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
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
                    <i class="fas fa-search" style="font-size: 48px; color: #bdc3c7; margin-bottom: 20px;"></i>
                    <h3 style="color: #7f8c8d; margin-bottom: 10px;">
                        <?php if(!empty($search)): ?>
                            No active lost items matching "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            No active lost items available
                        <?php endif; ?>
                    </h3>
                    <p style="color: #95a5a6; font-size: 14px;">
                        <a href="archive_items.php" class="archive-link">View archived items</a> to see matched/claimed items.
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Information Box -->
            <div class="info-box">
                <h4 style="color: #ff9800; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-info-circle"></i>
                    Admin Information - Lost Items
                </h4>
                <p style="color: #666; line-height: 1.6; font-size: 14px;">
                    • <strong>Status Flow for Lost Items:</strong> Pending → Matched → Claimed<br>
                    • <strong>Matched:</strong> When a lost item matches a found item report<br>
                    • <strong>Claimed:</strong> When owner has claimed their lost item<br>
                    • <strong>Note:</strong> Items with status "Matched" or "Claimed" are automatically moved to Archive<br>
                    • View archived items in <a href="archive_items.php" class="archive-link">Archive Page</a><br>
                    
                </p>
            </div>
            
        </div>
    </div>
    
    <script>
        // Function to open image in new tab
        function openImage(src) {
            window.open(src, '_blank', 'width=800,height=600');
        }
    </script>
</body>
</html>
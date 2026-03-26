<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] == 'admin') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];
$user_role = $_SESSION['role'];


if(file_exists('sidebar_nav.php')) {
    include 'sidebar_nav.php';
} else {
    echo '<div style="padding:20px;background:#f8d7da;color:#721c24;">Sidebar not found.</div>';
}

// Function to get items by status
function getItemsByStatus($connect, $user_id, $status) {
    $sql = "SELECT * FROM items 
            WHERE user_id = '$user_id' AND status = '$status' 
            ORDER BY created_at DESC";
    return mysqli_query($connect, $sql);
}

// Get counts for each status
$status_counts = [];
$statuses = ['pending', 'approved', 'matched', 'claimed'];

foreach($statuses as $status) {
    $sql = "SELECT COUNT(*) as count FROM items 
            WHERE user_id = '$user_id' AND status = '$status'";
    $result = mysqli_query($connect, $sql);
    $row = mysqli_fetch_assoc($result);
    $status_counts[$status] = $row['count'];
}

// Total items user has reported
$total_sql = "SELECT COUNT(*) as total FROM items WHERE user_id = '$user_id'";
$total_result = mysqli_query($connect, $total_sql);
$total_row = mysqli_fetch_assoc($total_result);
$total_items = $total_row['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - My Found Items</title>
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
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Status colors */
        .status-pending { color: #856404; background: #fff3cd; }
        .status-approved { color: #004085; background: #cce5ff; }
        .status-matched { color: #0c5460; background: #d1ecf1; }
        .status-claimed { color: #155724; background: #d4edda; }
        
        
        .status-card {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .item-card {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        
        .item-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-right: 10px;
        }
        
        .collapse-btn {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .collapse-btn:hover {
            color: #343a40;
        }
        
        .collapse-content {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.5s ease;
        }
        
        .collapse-content.collapsed {
            max-height: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Main Content -->
        <div class="main-content">
            
            <!-- Dashboard Header -->
            <div style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h1 style="color: #2c3e50; margin-bottom: 10px; font-size: 32px;">
                            <i class="fas fa-tachometer-alt" style="color: #27ae60; margin-right: 15px;"></i>
                            User Dashboard
                        </h1>
                        <p style="color: #7f8c8d; font-size: 16px;">
                            Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>! 
                            Here's an overview of all your reported found items.
                        </p>
                    </div>
                    
                    <div style="text-align: center;">
                        <div style="background: #27ae60; color: white; padding: 15px 25px; border-radius: 10px; display: inline-block;">
                            <div style="font-size: 36px; font-weight: 700;"><?php echo $total_items; ?></div>
                            <div style="font-size: 14px;">Total Items Reported</div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-top: 25px;">
                    <?php 
                    $status_info = [
                        'pending' => ['icon' => 'fas fa-clock', 'title' => 'Pending'],
                        'approved' => ['icon' => 'fas fa-check-circle', 'title' => 'Approved'],
                        'matched' => ['icon' => 'fas fa-link', 'title' => 'Matched'],
                        'claimed' => ['icon' => 'fas fa-handshake', 'title' => 'Claimed']
                        
                    ];
                    
                    foreach($statuses as $status): 
                        $count = $status_counts[$status];
                        $info = $status_info[$status];
                    ?>
                    <div class="stats-card">
                        <div style="font-size: 28px; margin-bottom: 10px; color: #2c3e50;">
                            <i class="<?php echo $info['icon']; ?>"></i>
                        </div>
                        <div style="font-size: 32px; font-weight: 700; color: #2c3e50; margin-bottom: 5px;">
                            <?php echo $count; ?>
                        </div>
                        <div style="font-size: 14px; color: #7f8c8d; margin-bottom: 10px;">
                            <?php echo $info['title']; ?> Items
                        </div>
                        <div class="status-badge status-<?php echo $status; ?>" style="font-size: 12px;">
                            <?php echo ucfirst($status); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                
            </div>
            
            <!-- Items by Status Sections -->
            <?php foreach($statuses as $status): 
                $items = getItemsByStatus($connect, $user_id, $status);
                $count = $status_counts[$status];
                $status_title = ucfirst($status);
                $status_icons = [
                    'pending' => 'fas fa-clock',
                    'approved' => 'fas fa-check-circle',
                    'matched' => 'fas fa-link',
                    'claimed' => 'fas fa-handshake'
                    
                ];
                $status_descriptions = [
                    'pending' => 'Items waiting for admin verification',
                    'approved' => 'Items verified by admin',
                    'matched' => 'Items matched with lost reports',
                    'claimed' => 'Items claimed by owners'
                    
                ];
            ?>
            <div class="status-card status-<?php echo $status; ?>">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div style="font-size: 24px; color: inherit;">
                            <i class="<?php echo $status_icons[$status]; ?>"></i>
                        </div>
                        <div>
                            <h2 style="color: inherit; margin: 0; font-size: 24px;">
                                <?php echo $status_title; ?> Items
                                <span style="font-size: 16px; background: rgba(255,255,255,0.5); padding: 2px 10px; border-radius: 20px; margin-left: 10px;">
                                    <?php echo $count; ?> item(s)
                                </span>
                            </h2>
                            <p style="color: inherit; opacity: 0.8; margin: 5px 0 0 0; font-size: 14px;">
                                <?php echo $status_descriptions[$status]; ?>
                            </p>
                        </div>
                    </div>
                    <?php if($count > 0): ?>
                    <button class="collapse-btn" onclick="toggleCollapse('<?php echo $status; ?>')">
                        <i class="fas fa-chevron-down"></i> Show/Hide
                    </button>
                    <?php endif; ?>
                </div>
                
                <div id="collapse-<?php echo $status; ?>" class="collapse-content">
                    <?php if(mysqli_num_rows($items) > 0): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
                            <?php while($item = mysqli_fetch_assoc($items)): ?>
                            <div class="item-card" style="border-left-color: <?php 
                                $border_colors = ['pending' => '#ffc107', 'approved' => '#007bff', 'matched' => '#17a2b8', 'claimed' => '#28a745'];
                                echo $border_colors[$status];
                            ?>;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    
                                    <?php if($item['item_type'] == 'lost'): ?>
                                        <span style="background: #dc3545; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-search"></i> LOST
                                        </span>
                                    <?php else: ?>
                                        <span style="background: #28a745; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-hand-holding-heart"></i> FOUND
                                        </span>
                                    <?php endif; ?>
                                    
                                    <h3 style="color: #2c3e50; margin: 0; font-size: 18px;">
                                        <?php echo htmlspecialchars(ucfirst($item['type_item'])); ?>
                                    </h3>
                                </div>
                                <div style="font-size: 12px; color: #7f8c8d; background: #f8f9fa; padding: 3px 8px; border-radius: 4px;">
                                    ID: <?php echo $item['id']; ?>
                                </div>
                            </div>
                                
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <div style="display: flex; align-items: center; gap: 5px; color: #7f8c8d; font-size: 14px;">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo htmlspecialchars($item['date']); ?>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 5px; color: #7f8c8d; font-size: 14px;">
                                        <i class="fas fa-clock"></i>
                                        <?php echo htmlspecialchars($item['time']); ?>
                                    </div>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <div style="display: flex; align-items: center; gap: 5px; color: #7f8c8d; font-size: 14px;">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $item['location']))); ?>
                                    </div>
                                </div>
                                
                                <p style="color: #666; font-size: 14px; margin-bottom: 15px; line-height: 1.5;">
                                    <?php echo htmlspecialchars(substr($item['description'], 0, 120)); ?>
                                    <?php if(strlen($item['description']) > 120): ?>...<?php endif; ?>
                                </p>
                                
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <?php if(!empty($item['picture']) && file_exists('uploads/' . $item['picture'])): ?>
                                        <a href="uploads/<?php echo htmlspecialchars($item['picture']); ?>" 
                                           target="_blank"
                                           style="color: #3498db; text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-image"></i> View Picture
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                   <div style="display: flex; gap: 10px;">
                                    <?php if($status == 'pending'): ?>
                                        <a href="edit_item.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['item_type']; ?>" 
                                        style="padding: 5px 12px; background: #f39c12; color: white; border-radius: 4px; 
                                                text-decoration: none; font-size: 12px; display: flex; align-items: center; gap: 5px;">
                                            <i class="fas fa-edit"></i> Edit Item
                                        </a>
                                    <?php endif; ?>
                                </div>
                                </div>
                                
                                <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; font-size: 12px; color: #95a5a6;">
                                    Reported on: <?php echo date('d/m/Y H:i', strtotime($item['created_at'])); ?>
                                    <?php if($item['updated_at'] != $item['created_at']): ?>
                                        <br>Last updated: <?php echo date('d/m/Y H:i', strtotime($item['updated_at'])); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #bdc3c7; margin-bottom: 15px;"></i>
                            <h3 style="color: #7f8c8d; margin-bottom: 10px;">No <?php echo $status; ?> items</h3>
                            <p style="color: #95a5a6; font-size: 14px;">
                                <?php if($status == 'pending'): ?>
                                    You haven't reported any items yet, or all your items have been processed.
                                <?php else: ?>
                                    You don't have any items with <?php echo $status; ?> status.
                                <?php endif; ?>
                            </p>
                            <?php if($status == 'pending'): ?>
                            <a href="form_item.php" style="margin-top: 15px; padding: 8px 20px; background: #27ae60; color: white; 
                                                           border-radius: 5px; text-decoration: none; display: inline-flex; 
                                                           align-items: center; gap: 8px;">
                                <i class="fas fa-plus-circle"></i> Report Your First Item
                            </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <!-- Summary Section -->
            <div style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); margin-top: 30px;">
                <h3 style="color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-chart-pie" style="color: #27ae60;"></i>
                    Items Summary
                </h3>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                    <div>
                        <h4 style="color: #34495e; margin-bottom: 15px;">Status Distribution</h4>
                        <div style="background: #f8f9fa; border-radius: 10px; padding: 20px;">
                            <?php 
                            $total = $total_items > 0 ? $total_items : 1; 
                            foreach($statuses as $status):
                                $count = $status_counts[$status];
                                $percentage = $total > 0 ? ($count / $total) * 100 : 0;
                            ?>
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                    <span style="color: #2c3e50; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                                        <span style="width: 12px; height: 12px; border-radius: 50%; 
                                                    background: <?php 
                                                        $colors = ['pending' => '#ffc107', 'approved' => '#007bff', 'matched' => '#17a2b8', 'claimed' => '#28a745'];
                                                        echo $colors[$status];
                                                    ?>;"></span>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                    <span style="color: #7f8c8d;">
                                        <?php echo $count; ?> (<?php echo number_format($percentage, 1); ?>%)
                                    </span>
                                </div>
                                <div style="height: 8px; background: #e9ecef; border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: <?php echo $percentage; ?>%; 
                                                background: <?php echo $colors[$status]; ?>; 
                                                border-radius: 4px;"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="color: #34495e; margin-bottom: 15px;">Quick Tips</h4>
                        <div style="background: #e8f8ef; border-radius: 10px; padding: 20px; border-left: 4px solid #27ae60;">
                            <ul style="color: #666; line-height: 1.8; padding-left: 20px; margin: 0;">
                                <li>You can edit items with <strong>Pending</strong> status</li>
                                <li><strong>Approved</strong> items are verified by admin</li>
                                <li>When item is <strong>Matched</strong>, it means we found the owner</li>
                                <li><strong>Claimed</strong> items have been returned to owners</li>
                                <li>Contact admin if you have any questions about your items</li>
                            </ul>
                        </div>
                        
                       
                    </div>
                </div>
            </div>
            
        </div>
    </div>
    
    <script>
        // Toggle collapse for status sections
        function toggleCollapse(status) {
            const element = document.getElementById('collapse-' + status);
            const button = event.currentTarget;
            const icon = button.querySelector('i');
            
            element.classList.toggle('collapsed');
            
            if(element.classList.contains('collapsed')) {
                icon.className = 'fas fa-chevron-right';
            } else {
                icon.className = 'fas fa-chevron-down';
            }
        }
        
        // Auto-collapse sections that are empty on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php foreach($statuses as $status): ?>
                if(<?php echo $status_counts[$status]; ?> === 0) {
                    const collapseElement = document.getElementById('collapse-<?php echo $status; ?>');
                    const button = document.querySelector('button[onclick="toggleCollapse(\'<?php echo $status; ?>\')"]');
                    
                    if(collapseElement && button) {
                        collapseElement.classList.add('collapsed');
                        const icon = button.querySelector('i');
                        icon.className = 'fas fa-chevron-right';
                    }
                }
            <?php endforeach; ?>
        });
        
        // Print dashboard function
        function printDashboard() {
            window.print();
        }
    </script>
</body>
</html>
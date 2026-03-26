<?php
session_start();
include 'db_connect.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];


$sql = "SELECT * FROM notifications WHERE user_id = '$user_id' ORDER BY created_at DESC LIMIT 7";
$result = mysqli_query($connect, $sql);

// Include appropriate sidebar
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
    <title>Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .notifications-wrapper {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .page-title {
            color: #2c3e50;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .notification-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        
        .notification-card:hover {
            transform: translateX(5px);
        }
        
        
        .notification-card.unread {
            background: #e8f4fc;
            border-left-color: #3498db;
        }
        
        
        .notification-card.read {
            background: #f8f9fa;
            border-left-color: #95a5a6;
            opacity: 0.6;
        }
        
        .notification-title {
            font-size: 16px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .notification-message {
            color: #34495e;
            font-size: 14px;
            margin-bottom: 10px;
            line-height: 1.5;
        }
        
        .notification-time {
            color: #7f8c8d;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .notification-link {
            color: #3498db;
            text-decoration: none;
            font-size: 13px;
            margin-top: 10px;
            display: inline-block;
        }
        
        .notification-link:hover {
            text-decoration: underline;
        }

        
        .notif-badge {
            display: inline-block;
            font-size: 11px;
            padding: 2px 10px;
            border-radius: 10px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .notif-badge.unread {
            background: #3498db;
            color: white;
        }

        .notif-badge.read {
            background: #bdc3c7;
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 48px;
            color: #bdc3c7;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="notifications-wrapper">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-bell"></i>
                    Notifications
                </h1>
                
                <form method="POST" action="mark_notification_read.php">
                    <input type="hidden" name="current_page" value="notifications.php">
                    <button type="submit" name="mark_all_read" class="btn btn-primary">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </button>
                </form>
            </div>
            
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($notif = mysqli_fetch_assoc($result)): ?>
                    <div class="notification-card <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                        
                        
                        <?php if($notif['is_read']): ?>
                            <span class="notif-badge read">Read</span>
                        <?php else: ?>
                            <span class="notif-badge unread">New</span>
                        <?php endif; ?>

                        <div class="notification-title">
                            <?php echo htmlspecialchars($notif['title']); ?>
                        </div>
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notif['message']); ?>
                        </div>
                        <div class="notification-time">
                            <i class="far fa-clock"></i>
                            <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                        </div>
                        <?php if(!empty($notif['link'])): ?>
                            <form method="POST" action="mark_notification_read.php" style="display:inline;">
                                <input type="hidden" name="mark_read" value="1">
                                <input type="hidden" name="notif_id" value="<?php echo $notif['id']; ?>">
                                <input type="hidden" name="redirect" value="<?php echo $notif['link']; ?>">
                                <button type="submit" class="notification-link" style="background:none; border:none; cursor:pointer; padding:0;">
                                    <i class="fas fa-arrow-right"></i> View Details
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Notifications</h3>
                    <p>You don't have any notifications yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
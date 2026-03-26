<?php
// sidebar_nav.php
// Check jika session sudah start, kalau belum start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check jika user logged in
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    // Redirect ke login jika bukan user
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <title>User Navigation</title>
   <style>
    

    :root {
        --green-deep:    #0f3522;
        --green-dark:    #1b4d35;
        --green-mid:     #2e7d52;
        --green-light:   #3aaf6e;
        --gold:          #c9a84c;
        --gold-light:    #f0d080;
        --sidebar-w:     260px;
        --header-h:      66px;
        --white:         #ffffff;
        --text-muted:    #8aab98;
        --bg-page:       #f0f4f2;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Nunito', sans-serif;
        display: flex;
        min-height: 100vh;
        background: var(--bg-page);
    }

    /* ===== SIDEBAR ===== */
    .sidebar {
        width: var(--sidebar-w);
        background: linear-gradient(180deg, var(--green-deep) 0%, var(--green-dark) 55%, var(--green-mid) 100%);
        height: 100vh;
        position: fixed;
        left: 0; top: 0;
        z-index: 1000;
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 24px rgba(0,0,0,0.18);
    }

    /* Gold shimmer line top of sidebar */
    .sidebar::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--gold), var(--gold-light), var(--gold), transparent);
    }

    /* ===== SIDEBAR LOGO ===== */
    .logo {
        padding: 28px 20px 22px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        position: relative;
    }

    .logo::after {
        content: '';
        position: absolute;
        bottom: -1px; left: 20%; right: 20%;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--gold), transparent);
    }

    /* Mosque icon circle */
    .logo-icon {
        width: 58px; height: 58px;
        border-radius: 50%;
        background: rgba(201,168,76,0.12);
        border: 2px solid rgba(201,168,76,0.35);
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 12px;
        font-size: 24px;
        color: var(--gold-light);
        box-shadow: 0 0 20px rgba(201,168,76,0.15);
    }

    .logo h2 {
        font-family: 'Playfair Display', serif;
        color: var(--white);
        font-size: 15px;
        font-weight: 700;
        line-height: 1.4;
        margin-bottom: 4px;
    }

    .logo p {
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    /* ===== NAV MENU ===== */
    .nav-menu {
        padding: 14px 14px;
        flex: 1;
    }

    .nav-title {
        color: rgba(201,168,76,0.6);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 800;
        padding: 16px 8px 8px;
    }

    .nav-item {
        display: flex;
        align-items: center;
        padding: 13px 14px;
        color: rgba(255,255,255,0.72);
        text-decoration: none;
        border-radius: 10px;
        margin-bottom: 4px;
        transition: all 0.25s ease;
        position: relative;
        font-weight: 600;
        font-size: 14.5px;
    }

    .nav-item:hover {
        background: rgba(255,255,255,0.08);
        color: var(--white);
        transform: translateX(4px);
    }

    .nav-item.active {
        background: rgba(201,168,76,0.12);
        color: var(--gold-light);
        border-left: 3px solid var(--gold);
        padding-left: 11px;
    }

    .nav-item.active .nav-icon {
        color: var(--gold-light);
    }

    .nav-icon {
        width: 28px;
        font-size: 16px;
        margin-right: 11px;
        text-align: center;
        color: rgba(255,255,255,0.5);
        transition: color 0.25s;
        flex-shrink: 0;
    }

    .nav-item:hover .nav-icon {
        color: var(--white);
    }

    .nav-text { flex: 1; }

    /* Sidebar footer */
    .sidebar-footer {
        padding: 14px 20px;
        border-top: 1px solid rgba(255,255,255,0.07);
        text-align: center;
        font-size: 11px;
        color: rgba(255,255,255,0.25);
        font-weight: 600;
    }

    /* ===== TOP HEADER ===== */
    .top-header {
        position: fixed;
        top: 0;
        left: var(--sidebar-w);
        right: 0;
        height: var(--header-h);
        background: var(--white);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 28px;
        z-index: 999;
        box-shadow: 0 2px 16px rgba(0,0,0,0.07);
    }

    /* Gold-green accent line bottom of header */
    .top-header::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--green-dark), var(--green-mid), var(--gold), transparent);
    }

    .page-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        color: var(--green-deep);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .page-title::before {
        content: '';
        width: 4px; height: 22px;
        background: linear-gradient(180deg, var(--gold), var(--green-mid));
        border-radius: 4px;
        flex-shrink: 0;
    }

    /* ===== HEADER RIGHT ===== */
    .header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    /* Notification button */
    .notification-btn {
        position: relative;
        background: #f4f7f5;
        border: 1.5px solid #d8ece2;
        color: var(--green-dark);
        font-size: 18px;
        cursor: pointer;
        width: 42px; height: 42px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.25s;
    }

    .notification-btn:hover {
        background: var(--green-dark);
        color: var(--white);
        border-color: var(--green-dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(27,77,53,0.25);
    }

    .notification-badge {
        position: absolute;
        top: -5px; right: -5px;
        background: #e74c3c;
        color: white;
        font-size: 10px;
        font-weight: 800;
        padding: 2px 5px;
        border-radius: 8px;
        min-width: 17px;
        text-align: center;
        border: 2px solid var(--white);
        display: none;
    }

    /* User profile */
    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 6px 12px 6px 6px;
        border-radius: 50px;
        border: 1.5px solid #d8ece2;
        background: #f4f7f5;
        transition: all 0.25s;
    }

    .user-profile:hover {
        border-color: var(--green-mid);
        background: white;
        box-shadow: 0 4px 12px rgba(27,77,53,0.12);
    }

    .user-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--green-dark), var(--green-mid));
        display: flex; align-items: center; justify-content: center;
        color: white;
        font-weight: 800;
        font-size: 15px;
        overflow: hidden;
        flex-shrink: 0;
    }

    .user-info { display: flex; flex-direction: column; }

    .user-name {
        font-weight: 800;
        color: var(--green-deep);
        font-size: 13.5px;
        line-height: 1.2;
    }

    .user-role {
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Dropdown */
    .dropdown { position: relative; }

    .dropdown-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: white;
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.14);
        min-width: 180px;
        display: none;
        z-index: 1001;
        border: 1px solid #e8f0eb;
        overflow: hidden;
    }

    .dropdown-item {
        display: flex;
        align-items: center;
        padding: 13px 16px;
        color: var(--green-deep);
        text-decoration: none;
        transition: all 0.2s;
        border-bottom: 1px solid #f0f6f2;
        font-size: 14px;
        font-weight: 600;
    }

    .dropdown-item:hover {
        background: #f0f6f2;
        color: var(--green-dark);
        padding-left: 20px;
    }

    .dropdown-item:last-child { border-bottom: none; }
    .dropdown-icon { margin-right: 10px; font-size: 14px; }

    /* Notification panel */
    .notification-panel {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: white;
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.14);
        width: 360px;
        max-height: 420px;
        overflow-y: auto;
        display: none;
        z-index: 1001;
        border: 1px solid #e8f0eb;
    }

    .notification-header {
        padding: 14px 16px;
        border-bottom: 1px solid #e8f0eb;
        font-weight: 800;
        color: var(--green-deep);
        font-size: 14px;
        background: #f4f7f5;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .notification-item {
        padding: 14px 16px;
        border-bottom: 1px solid #f0f6f2;
        transition: background 0.2s;
        background: #eaf4ff;
        border-left: 3px solid #3498db;
        width: 100%;
        text-align: left;
        border-top: none;
        border-right: none;
        cursor: pointer;
        font-family: 'Nunito', sans-serif;
        display: block;
    }

    .notification-item:hover { background: #d6ecff; }

    .notification-title {
        font-weight: 700;
        color: var(--green-deep);
        margin-bottom: 4px;
        font-size: 13.5px;
    }

    .notification-message {
        color: #6b8c7a;
        font-size: 12.5px;
        margin-bottom: 4px;
        line-height: 1.5;
    }

    .notification-time {
        color: #a0bfad;
        font-size: 11.5px;
    }

    .mark-all-btn {
        background: none;
        border: none;
        color: var(--green-mid);
        font-size: 12px;
        cursor: pointer;
        font-family: 'Nunito', sans-serif;
        font-weight: 700;
    }

    .mark-all-btn:hover { text-decoration: underline; }

    .font-size-toggle {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f4f7f5;
    border: 1.5px solid var(--border);
    border-radius: 10px;
    padding: 4px 8px;
}

.font-toggle-label {
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 800;
    margin-right: 4px;
    letter-spacing: 1px;
}

.font-btn {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--text-muted);
    border-radius: 6px;
    padding: 3px 7px;
    font-weight: 800;
    transition: all 0.2s;
    line-height: 1;
}

.font-btn:nth-child(2) { font-size: 13px; }
.font-btn:nth-child(3) { font-size: 16px; }
.font-btn:nth-child(4) { font-size: 20px; }

.font-btn:hover { background: #e8f0eb; color: var(--green-dark); }

.font-btn.active {
    background: var(--green-dark);
    color: white;
}

.tour-btn {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: var(--gold);
    color: var(--green-deep);
    border: none;
    font-size: 18px;
    font-weight: 900;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.25s;
    box-shadow: 0 3px 10px rgba(201,168,76,0.3);
    flex-shrink: 0;
}

.tour-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 5px 14px rgba(201,168,76,0.45);
}

/* ===== SHEPHERD TOUR CUSTOM THEME - USER ===== */
.shepherd-modal-overlay-container {
    z-index: 9998 !important;
}

.shepherd-element {
    z-index: 9999 !important;
    max-width: 360px !important;
}

.shepherd-theme-custom .shepherd-content {
    border-radius: 16px !important;
    box-shadow: 0 20px 60px rgba(0,0,0,0.35) !important;
    border: none !important;
    font-family: 'Nunito', sans-serif !important;
    overflow: hidden !important;
}

.shepherd-theme-custom .shepherd-header {
    background: linear-gradient(135deg, var(--green-deep), var(--green-mid)) !important;
    padding: 18px 22px 14px !important;
    border-bottom: 2px solid var(--gold) !important;
}

.shepherd-theme-custom .shepherd-title {
    color: var(--gold-light) !important;
    font-size: 16px !important;
    font-weight: 800 !important;
    font-family: 'Nunito', sans-serif !important;
}

.shepherd-theme-custom .shepherd-cancel-icon {
    color: rgba(255,255,255,0.5) !important;
    font-size: 20px !important;
}

.shepherd-theme-custom .shepherd-cancel-icon:hover {
    color: white !important;
    background: none !important;
}

.shepherd-theme-custom .shepherd-text {
    padding: 18px 22px !important;
    font-size: 14px !important;
    color: #2c3e50 !important;
    line-height: 1.75 !important;
    background: white !important;
}

.shepherd-theme-custom .shepherd-footer {
    padding: 10px 22px 18px !important;
    background: white !important;
    display: flex !important;
    gap: 8px !important;
    justify-content: flex-end !important;
    border-top: 1px solid #f0f0f0 !important;
}

.shepherd-btn-next {
    background: linear-gradient(135deg, var(--green-dark), var(--green-mid)) !important;
    color: white !important;
    border: none !important;
    padding: 10px 20px !important;
    border-radius: 9px !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    cursor: pointer !important;
    font-family: 'Nunito', sans-serif !important;
    box-shadow: 0 4px 12px rgba(27,77,53,0.3) !important;
    transition: all 0.2s !important;
}

.shepherd-btn-next:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 16px rgba(27,77,53,0.4) !important;
}

.shepherd-btn-back {
    background: #f0f4f2 !important;
    color: #555 !important;
    border: none !important;
    padding: 10px 20px !important;
    border-radius: 9px !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    cursor: pointer !important;
    font-family: 'Nunito', sans-serif !important;
}

.shepherd-btn-skip {
    background: none !important;
    color: #aaa !important;
    border: none !important;
    padding: 10px 12px !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    cursor: pointer !important;
    margin-right: auto !important;
    font-family: 'Nunito', sans-serif !important;
}

.shepherd-btn-skip:hover { color: var(--green-mid) !important; }

.shepherd-arrow:before {
    background: var(--green-deep) !important;
}

.shepherd-highlight {
    outline: 3px solid var(--gold) !important;
    outline-offset: 5px !important;
    border-radius: 8px !important;
    box-shadow: 0 0 0 6px rgba(201,168,76,0.15), 0 0 20px rgba(201,168,76,0.3) !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    z-index: 10000 !important;
}
    /* Logout */
    .logout-btn {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        border: none;
        padding: 8px 18px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        font-size: 13.5px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: 'Nunito', sans-serif;
        transition: all 0.25s;
        text-decoration: none;
    }

    .logout-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(231,76,60,0.3);
    }

    /* ===== MAIN CONTENT ===== */
    .main-content {
        flex: 1;
        margin-left: var(--sidebar-w);
        margin-top: var(--header-h);
        padding: 28px;
        min-height: calc(100vh - var(--header-h));
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        :root { --sidebar-w: 68px; }

        .sidebar .nav-text,
        .sidebar .logo h2,
        .sidebar .logo p,
        .sidebar .nav-title,
        .sidebar-footer { display: none; }

        .nav-item {
            justify-content: center;
            padding: 15px;
            border-left: none !important;
        }

        .nav-icon { margin-right: 0; font-size: 20px; width: auto; }
        .logo-icon { width: 40px; height: 40px; font-size: 18px; }
        .user-info { display: none; }
    }
</style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11/dist/css/shepherd.css">
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@11/dist/js/shepherd.min.js"></script>
<script src="tour.js"></script>
</head>
<body>
    <!-- SIDEBAR NAVIGATION -->
    <div class="sidebar">
        <div class="logo">
            <h2>Surau Ismail Kharofa</h2>
            <p>Lost & Found System</p>
        </div>
        
        <div class="nav-menu">
            <div class="nav-title">Main Menu</div>
            
            <a href="user_dashboard.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'my_reports.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-file-alt"></i></div>
                <div class="nav-text">Dashboard</div>
            </a>
            
            <a href="user_profile.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'user_profile.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-user"></i></div>
                <div class="nav-text">Profile</div>
            </a>
            
            <a href="list_found.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'list_found.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-search"></i></div>
                <div class="nav-text">List Found Item</div>
            </a>
            
            <a href="form_item.php" class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'form_item.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-plus-circle"></i></div>
                <div class="nav-text">Report Item</div>
            </a>
        </div>
    </div>
    
    <!-- TOP HEADER BAR -->
    <div class="top-header">
        <div class="page-title">
    <?php 
    // Dynamic page title based on current page
    $page_titles = [
        'user_dashboard.php' => 'Dashboard',
        'user_profile.php' => 'My Profile',
        'list_found.php' => 'Found Items List',
        'form_item.php' => 'Report Found and Lost Item',
        'my_reports.php' => 'My Reports',
        'edit_item.php' => 'Edit Item'
    ];
    $current_page = basename($_SERVER['PHP_SELF']);
    echo isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'User Panel';
    ?>
</div>

 <div class="font-size-toggle">
    <button class="tour-btn" onclick="startTour()" title="Start guided tour">?</button>
        
        <button class="font-btn" onclick="setFontSize('small')" id="fs-small">A</button>
        <button class="font-btn active" onclick="setFontSize('medium')" id="fs-medium">A</button>
        <button class="font-btn" onclick="setFontSize('large')" id="fs-large">A</button>
    </div>
        
        <div class="header-right">
            <!-- Notification Button -->
            <div class="dropdown">
                <button class="notification-btn" id="notifBtn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notifBadge"></span>
                </button>
                
                <div class="notification-panel" id="notifPanel">
                    <div class="notification-header">Notifications</div>
                    
                    <a href="notifications.php" class="dropdown-item" style="text-align: center; color: #3498db;">
                        <i class="fas fa-eye dropdown-icon"></i>
                        View All Notifications
                    </a>
                </div>
            </div>
            
            <!-- User Profile Dropdown -->
            <div class="dropdown">
                <div class="user-profile">
                    <div class="user-avatar" style="background: none; border: none; overflow: hidden;">
                        <?php
                        // Get fresh user data for profile picture
                        $user_id = $_SESSION['user_id'];
                        $user_sql = "SELECT profile_pic, name FROM accounts WHERE id='$user_id'";
                        $user_result = mysqli_query($connect, $user_sql);
                        $user_data = mysqli_fetch_assoc($user_result);
                        
                        if(!empty($user_data['profile_pic']) && file_exists('profile_pics/' . $user_data['profile_pic'])): ?>
                            <img src="profile_pics/<?php echo $user_data['profile_pic']; ?>" 
                                alt="Profile" 
                                style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; 
                                        background: linear-gradient(135deg, #3498db, #2c3e50);
                                        display: flex; align-items: center; justify-content: center;
                                        color: white; font-weight: bold; font-size: 16px;">
                                <?php echo strtoupper(substr($user_data['name'] ?? 'U', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['role'] ?? 'user'); ?></div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: #7f8c8d; font-size: 12px;"></i>
                </div>
                
                <div class="dropdown-menu">
                    
                    <div class="dropdown-item" style="border-top: 1px solid #f8f9fa; margin-top: 5px;">
                        <a href="logout.php" class="logout-btn" style="width: 100%; justify-content: center;">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <!-- MAIN CONTENT AREA -->
    <div class="main-content">
        
        <?php
        
        ?>

        <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // ===== NOTIFICATION SYSTEM FOR USER =====
        function loadNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    
                    const badge = document.getElementById('notifBadge');
                    if (badge) {
                        if (data.unread > 0) {
                            badge.textContent = data.unread;
                            badge.style.display = 'inline';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                    
                    // Update notification panel
                    const panel = document.getElementById('notifPanel');
                    if (panel) {
                        let html = '<div class="notification-header" style="display:flex; justify-content:space-between; align-items:center;">';
                        html += '<span>Notifications</span>';
                        html += '<form method="POST" action="mark_notification_read.php" style="display:inline;">';
                        html += '<input type="hidden" name="current_page" value="' + window.location.pathname.split('/').pop() + '">';
                        html += '<button type="submit" name="mark_all_read" class="mark-all-btn">Mark all as read</button>';
                        html += '</form>';
                        html += '</div>';
                        
                
                        if (data.notifications.length > 0) {
                            data.notifications.forEach(notif => {
                                html += `<form method="POST" action="mark_notification_read.php" style="margin:0; padding:0;">`;
                                html += `<input type="hidden" name="notif_id" value="${notif.id}">`;
                                html += `<input type="hidden" name="mark_read" value="1">`;
                                html += `<input type="hidden" name="redirect" value="${notif.link || ''}">`;
                                html += `<button type="submit" class="notification-item">`;
                                html += `<div class="notification-title">${notif.title}</div>`;
                                html += `<div class="notification-message">${notif.message}</div>`;
                                html += `<div class="notification-time"><i class="far fa-clock"></i> ${notif.time_ago}</div>`;
                                html += `</button>`;
                                html += `</form>`;
                            });
                        } else {
                            
                            html += '<div style="text-align:center; padding:30px; color:#7f8c8d;">';
                            html += '<i class="fas fa-bell-slash" style="font-size:30px; color:#bdc3c7; margin-bottom:10px; display:block;"></i>';
                            html += 'No new notifications';
                            html += '</div>';
                        }
                        
                        html += '<a href="notifications.php" class="dropdown-item" style="text-align: center; color: #3498db;">';
                        html += '<i class="fas fa-eye dropdown-icon"></i> View All Notifications';
                        html += '</a>';
                        
                        panel.innerHTML = html;
                    }
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        
        function updateBadgeOnly() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('notifBadge');
                    if (badge) {
                        if (data.unread > 0) {
                            badge.textContent = data.unread;
                            badge.style.display = 'inline';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Notification panel toggle
        const notificationBtn = document.getElementById('notifBtn');
        const notificationPanel = document.getElementById('notifPanel');

        if (notificationBtn && notificationPanel) {
            notificationBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (notificationPanel.style.display === 'block') {
                    notificationPanel.style.display = 'none';
                } else {
                    notificationPanel.style.display = 'block';
                    loadNotifications(); // Refresh when panel open
                }
            });
            
            document.addEventListener('click', function() {
                notificationPanel.style.display = 'none';
            });
            
            notificationPanel.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }

    
        updateBadgeOnly();

    
        setInterval(updateBadgeOnly, 30000);
        
        // User profile dropdown toggle
        const userProfile = document.querySelector('.user-profile');
        const userDropdown = document.querySelector('.dropdown-menu');
        
        if (userProfile && userDropdown) {
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                if (userDropdown.style.display === 'block') {
                    userDropdown.style.display = 'none';
                } else {
                    userDropdown.style.display = 'block';
                }
            });
            
            document.addEventListener('click', function() {
                userDropdown.style.display = 'none';
            });
            
            userDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    });
</script>

<script>
function setFontSize(size) {
    const zooms = { small: '0.75', medium: '1', large: '1.2' };
    const mainContent = document.querySelector('.main-content');
    if(mainContent) {
        mainContent.style.zoom = zooms[size];
    }
    
    document.querySelectorAll('.font-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('fs-' + size).classList.add('active');
    
    localStorage.setItem('fontSizePreference', size);
}

// Load saved preference on page load
const savedSize = localStorage.getItem('fontSizePreference') || 'medium';
setFontSize(savedSize);
</script>
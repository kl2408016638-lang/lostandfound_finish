<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once 'db_connect.php';

// Check if user log in as admin
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get fresh user data for profile picture
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT profile_pic, name FROM accounts WHERE id='$user_id'";
$user_result = mysqli_query($connect, $user_sql);
$user_data = mysqli_fetch_assoc($user_result);

$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11/dist/css/shepherd.css">
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@11/dist/js/shepherd.min.js"></script>
<script src="admin_tour.js"></script>

<style>
    
    *, *::before, *::after {
    box-sizing: border-box;
}

body, body * {
    font-family: 'Nunito', sans-serif;
}


.fa, .fas, .far, .fab, .fal, [class^="fa-"], [class*=" fa-"] {
    font-family: 'Font Awesome 6 Free', 'Font Awesome 6 Brands' !important;
}

    :root {
        --navy-deep:     #0d1b2a;
        --navy-dark:     #1a2a3d;
        --navy-mid:      #243650;
        --gold:          #c9a84c;
        --gold-light:    #f0d080;
        --red:           #e74c3c;
        --red-dark:      #c0392b;
        --sidebar-w:     280px;
        --header-h:      68px;
        --white:         #ffffff;
        --text-muted:    #6c8bc7;
        --bg-page:       #f0f2f5;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Nunito', sans-serif;
        display: flex;
        min-height: 100vh;
        background: var(--bg-page);
    }

    /* ===== ADMIN SIDEBAR ===== */
    .admin-sidebar {
        width: var(--sidebar-w);
        background: linear-gradient(180deg, var(--navy-deep) 0%, var(--navy-dark) 60%, var(--navy-mid) 100%);
        height: 100vh;
        position: fixed;
        left: 0; top: 0;
        z-index: 1000;
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 28px rgba(0,0,0,0.28);
    }

    /* Red top accent line */
    .admin-sidebar::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--red), #ff6b5b, var(--red), transparent);
    }

    /* ===== ADMIN LOGO ===== */
    .admin-logo {
        padding: 26px 20px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255,255,255,0.07);
        position: relative;
    }

    .admin-logo::after {
        content: '';
        position: absolute;
        bottom: -1px; left: 20%; right: 20%;
        height: 1px;
        background: linear-gradient(90deg, transparent, var(--red), transparent);
    }

    .admin-logo h2 {
        font-family: 'Playfair Display', serif;
        color: var(--white);
        font-size: 20px;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 5px;
    }

    .admin-badge {
        background: var(--red);
        color: white;
        padding: 2px 9px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 1.5px;
        font-family: 'Nunito', sans-serif;
    }

    .admin-logo p {
        color: var(--text-muted);
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    .system-stats {
        display: flex;
        gap: 8px;
        margin-top: 12px;
        justify-content: center;
    }

    .stat-badge {
        background: rgba(255,255,255,0.06);
        border: 1px solid rgba(108,139,199,0.25);
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 10.5px;
        color: var(--text-muted);
        font-weight: 600;
    }

    /* ===== ADMIN NAV MENU ===== */
    .admin-nav-menu { padding: 14px 14px; flex: 1; }

    .admin-nav-title {
        color: rgba(108,139,199,0.6);
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 2px;
        font-weight: 800;
        padding: 16px 8px 8px;
    }

    .admin-nav-item {
        display: flex;
        align-items: center;
        padding: 13px 14px;
        color: rgba(255,255,255,0.65);
        text-decoration: none;
        border-radius: 10px;
        margin-bottom: 4px;
        transition: all 0.25s ease;
        position: relative;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        font-family: 'Nunito', sans-serif;
        font-weight: 600;
        font-size: 14.5px;
    }

    .admin-nav-item:hover {
        background: rgba(255,255,255,0.07);
        color: var(--white);
        transform: translateX(4px);
    }

    .admin-nav-item.active {
        background: rgba(231,76,60,0.15);
        color: #ff8a7e;
        border-left: 3px solid var(--red);
        padding-left: 11px;
    }

    .admin-nav-item.active .admin-nav-icon { color: var(--red); }

    .admin-nav-icon {
        width: 28px;
        font-size: 16px;
        margin-right: 12px;
        text-align: center;
        color: rgba(255,255,255,0.4);
        transition: color 0.25s;
        flex-shrink: 0;
    }

    .admin-nav-item:hover .admin-nav-icon { color: var(--white); }

    .admin-nav-text { flex: 1; }

    .nav-arrow {
        font-size: 11px;
        color: rgba(255,255,255,0.3);
        transition: transform 0.3s;
    }

    .nav-arrow.rotated { transform: rotate(90deg); }

    /* Submenu */
    .submenu {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease;
        background: rgba(0,0,0,0.2);
        border-radius: 8px;
        margin: 3px 0 3px 6px;
    }

    .submenu.open { max-height: 300px; }

    .submenu-item {
        display: flex;
        align-items: center;
        padding: 11px 14px 11px 40px;
        color: rgba(255,255,255,0.55);
        text-decoration: none;
        transition: all 0.2s;
        border-left: 2px solid transparent;
        font-size: 13.5px;
        font-weight: 600;
    }

    .submenu-item:hover {
        background: rgba(255,255,255,0.05);
        color: var(--white);
        border-left-color: var(--text-muted);
    }

    .submenu-item.active {
        color: var(--red);
        border-left-color: var(--red);
        background: rgba(231,76,60,0.08);
    }

    .submenu-icon { margin-right: 10px; font-size: 13px; width: 18px; text-align: center; }

    /* ===== ADMIN TOP HEADER ===== */
    .admin-top-header {
        position: fixed;
        top: 0;
        left: var(--sidebar-w);
        right: 0;
        height: var(--header-h);
        background: var(--white);
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 30px;
        z-index: 999;
        box-shadow: 0 2px 16px rgba(0,0,0,0.08);
    }

    /* Red bottom accent */
    .admin-top-header::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 2px;
        background: linear-gradient(90deg, var(--red), #ff6b5b, var(--gold), transparent);
    }

    .admin-page-title {
        font-family: 'Playfair Display', serif;
        font-size: 20px;
        color: var(--navy-deep);
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .admin-page-title::before {
        content: '';
        width: 4px; height: 22px;
        background: linear-gradient(180deg, var(--red), var(--gold));
        border-radius: 4px;
        flex-shrink: 0;
    }

    .admin-title-icon { color: var(--red); font-size: 18px; }

    /* ===== HEADER RIGHT ===== */
    .admin-header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .admin-notification-btn {
        position: relative;
        background: #f7f1f1;
        border: 1.5px solid #f0dada;
        color: var(--red-dark);
        font-size: 18px;
        cursor: pointer;
        width: 44px; height: 44px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.25s;
    }

    .admin-notification-btn:hover {
        background: var(--red);
        color: var(--white);
        border-color: var(--red);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(231,76,60,0.3);
    }

    .admin-notification-badge {
        position: absolute;
        top: -5px; right: -5px;
        background: var(--red);
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

    .admin-user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        padding: 6px 14px 6px 6px;
        border-radius: 50px;
        border: 1.5px solid #f0dada;
        background: #f7f1f1;
        transition: all 0.25s;
    }

    .admin-user-profile:hover {
        border-color: var(--red);
        background: white;
        box-shadow: 0 4px 12px rgba(231,76,60,0.12);
    }

    .admin-user-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        overflow: hidden;
        flex-shrink: 0;
        border: 2px solid rgba(231,76,60,0.2);
    }

    .admin-user-info { display: flex; flex-direction: column; }

    .admin-user-name {
        font-weight: 800;
        color: var(--navy-deep);
        font-size: 13.5px;
        line-height: 1.2;
    }

    .admin-user-role {
        color: var(--red);
        font-size: 10.5px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* Dropdowns */
    .admin-dropdown { position: relative; }

    .admin-dropdown-menu {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: white;
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.14);
        min-width: 200px;
        display: none;
        z-index: 1001;
        border: 1px solid #f0dada;
        overflow: hidden;
    }

    .admin-dropdown-item {
        display: flex;
        align-items: center;
        padding: 13px 16px;
        color: var(--navy-deep);
        text-decoration: none;
        transition: all 0.2s;
        border-bottom: 1px solid #fdf0f0;
        font-size: 14px;
        font-weight: 600;
    }

    .admin-dropdown-item:hover {
        background: #fdf0f0;
        color: var(--red);
        padding-left: 20px;
    }

    .admin-dropdown-item:last-child { border-bottom: none; }
    .admin-dropdown-icon { margin-right: 12px; font-size: 14px; }

    /* Notification panel */
    .admin-notification-panel {
        position: absolute;
        top: calc(100% + 8px);
        right: 0;
        background: white;
        border-radius: 14px;
        box-shadow: 0 12px 40px rgba(0,0,0,0.14);
        width: 380px;
        max-height: 450px;
        overflow-y: auto;
        display: none;
        z-index: 1001;
        border: 1px solid #f0dada;
    }

    .admin-notification-header {
        padding: 14px 16px;
        border-bottom: 1px solid #f0dada;
        font-weight: 800;
        color: var(--navy-deep);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f7f1f1;
        font-size: 14px;
    }

    .admin-notification-item {
        padding: 14px 16px;
        border-bottom: 1px solid #fdf0f0;
        transition: background 0.2s;
        background: #ffeaea;
        border-left: 3px solid var(--red);
        width: 100%;
        text-align: left;
        border-top: none;
        border-right: none;
        cursor: pointer;
        font-family: 'Nunito', sans-serif;
        display: block;
    }

    .admin-notification-item:hover { background: #ffd9d9; }

    .admin-notification-title {
        font-weight: 700;
        color: var(--navy-deep);
        margin-bottom: 5px;
        font-size: 13.5px;
    }

    .admin-notification-message {
        color: #7f8c8d;
        font-size: 12.5px;
        margin-bottom: 5px;
        line-height: 1.5;
    }

    .admin-notification-time {
        color: #95a5a6;
        font-size: 11.5px;
    }

    .mark-all-btn {
        background: none;
        border: none;
        color: var(--red);
        font-size: 12px;
        cursor: pointer;
        font-family: 'Nunito', sans-serif;
        font-weight: 700;
    }

    .mark-all-btn:hover { text-decoration: underline; }

    /* Logout */
    .admin-logout-btn {
        background: linear-gradient(135deg, var(--red), var(--red-dark));
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
        box-shadow: 0 3px 10px rgba(231,76,60,0.25);
    }

    .admin-logout-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 5px 14px rgba(231,76,60,0.35);
    }

    /* ===== TOUR BUTTON ===== */
.tour-btn {
    background: var(--gold);
    color: var(--navy-deep);
    border: none;
    width: 36px; height: 36px;
    border-radius: 50%;
    font-size: 18px;
    font-weight: 800;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.25s;
    box-shadow: 0 3px 10px rgba(201,168,76,0.35);
    flex-shrink: 0;
}

.tour-btn:hover {
    background: var(--gold-light);
    transform: scale(1.1);
    box-shadow: 0 5px 14px rgba(201,168,76,0.5);
}

/* ===== FONT SIZE TOGGLE ===== */
.font-size-toggle {
    display: flex;
    align-items: center;
    gap: 4px;
    background: #f7f1f1;
    border: 1.5px solid #f0dada;
    border-radius: 10px;
    padding: 4px 8px;
}

.font-toggle-label {
    color: var(--red);
    font-size: 13px;
    font-weight: 800;
    margin-right: 4px;
}

.font-btn {
    background: none;
    border: none;
    color: #7f8c8d;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    padding: 3px 7px;
    border-radius: 6px;
    transition: all 0.2s;
    font-family: 'Nunito', sans-serif;
}

.font-btn:hover { background: #fdf0f0; color: var(--red); }
.font-btn.active { background: var(--red); color: white; }


/* ===== SHEPHERD TOUR CUSTOM THEME - ADMIN ===== */
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
    background: linear-gradient(135deg, var(--navy-deep), var(--navy-mid)) !important;
    padding: 18px 22px 14px !important;
    border-bottom: 2px solid var(--red) !important;
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
    background: linear-gradient(135deg, var(--red), var(--red-dark)) !important;
    color: white !important;
    border: none !important;
    padding: 10px 20px !important;
    border-radius: 9px !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    cursor: pointer !important;
    font-family: 'Nunito', sans-serif !important;
    box-shadow: 0 4px 12px rgba(231,76,60,0.3) !important;
    transition: all 0.2s !important;
}

.shepherd-btn-next:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 6px 16px rgba(231,76,60,0.4) !important;
}

.shepherd-btn-back {
    background: #f0f2f5 !important;
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

.shepherd-btn-skip:hover { color: var(--red) !important; }

/* Arrow pointer */
.shepherd-arrow:before {
    background: var(--navy-deep) !important;
}

/* Highlight border around element */
.shepherd-highlight {
    outline: 3px solid var(--red) !important;
    outline-offset: 5px !important;
    border-radius: 8px !important;
    box-shadow: 0 0 0 6px rgba(231,76,60,0.15), 0 0 20px rgba(231,76,60,0.3) !important;
    transition: all 0.3s ease !important;
    position: relative !important;
    z-index: 10000 !important;
}
    /* ===== MAIN CONTENT ===== */
    .admin-main-content {
        flex: 1;
        margin-left: var(--sidebar-w);
        margin-top: var(--header-h);
        padding: 32px;
        min-height: calc(100vh - var(--header-h));
        background: var(--bg-page);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
        :root { --sidebar-w: 240px; }
    }

    @media (max-width: 768px) {
        :root { --sidebar-w: 68px; }

        .admin-sidebar .admin-nav-text,
        .admin-sidebar .admin-logo h2,
        .admin-sidebar .admin-logo p,
        .admin-sidebar .admin-nav-title,
        .admin-sidebar .system-stats,
        .admin-sidebar .nav-arrow { display: none; }

        .admin-nav-item {
            justify-content: center;
            padding: 16px;
            border-left: none !important;
        }

        .admin-nav-icon { margin-right: 0; width: auto; font-size: 20px; }
        .admin-user-info { display: none; }

        .admin-top-header { padding: 0 18px; }
        .admin-main-content { padding: 20px; }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle submenu function
        function toggleSubmenu(submenuId) {
            const submenu = document.getElementById(submenuId);
            const arrow = document.querySelector(`[data-submenu="${submenuId}"] .nav-arrow`);
            
            if (submenu && arrow) {
                submenu.classList.toggle('open');
                arrow.classList.toggle('rotated');
            }
        }
        
        // Set active menu based on current page
        function setActiveMenu() {
            const currentPage = '<?php echo $current_page; ?>';
            
            document.querySelectorAll('.admin-nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            document.querySelectorAll('.submenu-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const submenuItems = {
                'list_found.php': { submenu: 'listsSubmenu', item: 'founditems' },
                'list_lost.php': { submenu: 'listsSubmenu', item: 'lostitems' },
                'admin_statistics.php': { submenu: 'dashboardSubmenu', item: 'statistics' },
                'admin_trail.php': { submenu: 'dashboardSubmenu', item: 'trail' },
                'admin_users.php': { submenu: 'dashboardSubmenu', item: 'users' },
                'archive_items.php': { submenu: 'dashboardSubmenu', item: 'archive' }
            };
            
            if (submenuItems[currentPage]) {
                const { submenu, item } = submenuItems[currentPage];
                
                const submenuEl = document.getElementById(submenu);
                const arrow = document.querySelector(`[data-submenu="${submenu}"] .nav-arrow`);
                
                if (submenuEl && arrow) {
                    submenuEl.classList.add('open');
                    arrow.classList.add('rotated');
                }
                
                const submenuItem = document.querySelector(`.submenu-item[data-page="${item}"]`);
                if (submenuItem) {
                    submenuItem.classList.add('active');
                }
                
                const parentMenu = document.querySelector(`[data-submenu="${submenu}"]`);
                if (parentMenu) {
                    parentMenu.classList.add('active');
                }
            }
        }
        
        document.querySelectorAll('[data-submenu]').forEach(menuItem => {
            menuItem.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const submenuId = this.getAttribute('data-submenu');
                toggleSubmenu(submenuId);
                
                document.querySelectorAll('.submenu').forEach(sub => {
                    if (sub.id !== submenuId) {
                        sub.classList.remove('open');
                    }
                });
                
                document.querySelectorAll('.nav-arrow').forEach(arrow => {
                    if (!arrow.closest(`[data-submenu="${submenuId}"]`)) {
                        arrow.classList.remove('rotated');
                    }
                });
            });
        });
        
        setActiveMenu();

        // ===== NOTIFICATION SYSTEM FOR ADMIN =====
        function loadNotifications() {
            fetch('get_notifications.php')
                .then(response => response.json())
                .then(data => {
                    
                    const badge = document.querySelector('.admin-notification-badge');
                    if (badge) {
                        if (data.unread > 0) {
                            badge.textContent = data.unread;
                            badge.style.display = 'inline';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                    
                    // Update notification panel
                    const panel = document.querySelector('.admin-notification-panel');
                    if (panel) {
                        let html = '<div class="admin-notification-header">';
                        html += '<span>Notifications</span>';
                        html += '<form method="POST" action="mark_notification_read.php" style="display:inline;">';
                        html += '<input type="hidden" name="current_page" value="' + window.location.pathname.split('/').pop() + '">';
                        html += '<button type="submit" name="mark_all_read" class="mark-all-btn">Mark all as read</button>';
                        html += '</form>';
                        html += '</div>';
                        
                        // All notifications that come out are unread.
                        if (data.notifications.length > 0) {
                            data.notifications.forEach(notif => {
                                html += `<form method="POST" action="mark_notification_read.php" style="margin:0; padding:0;">`;
                                html += `<input type="hidden" name="notif_id" value="${notif.id}">`;
                                html += `<input type="hidden" name="mark_read" value="1">`;
                                html += `<input type="hidden" name="redirect" value="${notif.link || ''}">`;
                                html += `<button type="submit" class="admin-notification-item">`;
                                html += `<div class="admin-notification-title">${notif.title}</div>`;
                                html += `<div class="admin-notification-message">${notif.message}</div>`;
                                html += `<div class="admin-notification-time"><i class="far fa-clock"></i> ${notif.time_ago}</div>`;
                                html += `</button>`;
                                html += `</form>`;
                            });
                        } else {
                            // Empty state when there are no unreads
                            html += '<div style="text-align:center; padding:30px; color:#7f8c8d;">';
                            html += '<i class="fas fa-bell-slash" style="font-size:30px; color:#bdc3c7; margin-bottom:10px; display:block;"></i>';
                            html += 'No new notifications';
                            html += '</div>';
                        }
                        
                        html += '<a href="notifications.php" class="admin-dropdown-item" style="text-align: center; color: #e74c3c; font-weight: 600;">';
                        html += '<i class="fas fa-list admin-dropdown-icon"></i> View All Notifications';
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
                    const badge = document.querySelector('.admin-notification-badge');
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
        const notificationBtn = document.querySelector('.admin-notification-btn');
        const notificationPanel = document.querySelector('.admin-notification-panel');

        if (notificationBtn && notificationPanel) {
            notificationBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (notificationPanel.style.display === 'block') {
                    notificationPanel.style.display = 'none';
                } else {
                    notificationPanel.style.display = 'block';
                    loadNotifications(); // Refresh when opening
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

        // Auto-refresh badge every 30 seconds
        setInterval(updateBadgeOnly, 30000);

        const userProfile = document.querySelector('.admin-user-profile');
        const userDropdown = document.querySelector('.admin-dropdown-menu');
        
        if (userProfile && userDropdown) {
            userProfile.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.style.display = userDropdown.style.display === 'block' ? 'none' : 'block';
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
function setAdminFontSize(size) {
    const zooms = { small: '0.75', medium: '1', large: '1.2', xlarge: '1.4' };
    const mainContent = document.querySelector('.admin-main-content');
    if(mainContent) { mainContent.style.zoom = zooms[size]; }
    document.querySelectorAll('.font-btn').forEach(btn => btn.classList.remove('active'));
    const btn = document.getElementById('afs-' + size);
    if(btn) btn.classList.add('active');
    localStorage.setItem('adminFontSizePreference', size);
}

document.addEventListener('DOMContentLoaded', function() {
    const savedSize = localStorage.getItem('adminFontSizePreference') || 'medium';
    setAdminFontSize(savedSize);
});
</script>


<!-- ADMIN SIDEBAR NAVIGATION -->
<div class="admin-sidebar">
    <div class="admin-logo">
        <h2>
            <i class="fas fa-shield-alt"></i>
            Admin Panel
            <span class="admin-badge">ADMIN</span>
        </h2>
        <p>Surau Ismail Kharofa Lost & Found</p>
        
        <div class="system-stats">
            <span class="stat-badge"><i class="fas fa-users"></i> Online</span>
            <span class="stat-badge"><i class="fas fa-server"></i> Active</span>
        </div>
    </div>
    
    <div class="admin-nav-menu">
        <div class="admin-nav-title">Administration</div>

        <a href="admin_profile.php" class="admin-nav-item <?php echo $current_page == 'admin_profile.php' ? 'active' : ''; ?>">
            <div class="admin-nav-icon"><i class="fas fa-user-cog"></i></div>
            <div class="admin-nav-text">Profile</div>
        </a>
        
        <!-- Lists Menu dengan Submenu -->
        <button class="admin-nav-item" data-submenu="listsSubmenu">
            <div class="admin-nav-icon"><i class="fas fa-list"></i></div>
            <div class="admin-nav-text">Lists</div>
            <div class="nav-arrow"><i class="fas fa-chevron-right"></i></div>
        </button>
        
        <!-- Submenu untuk Lists -->
        <div class="submenu" id="listsSubmenu">
            <a href="list_found.php" class="submenu-item" data-page="founditems">
                <div class="submenu-icon"><i class="fas fa-hand-holding-heart"></i></div>
                <div>Found Items</div>
            </a>
            <a href="list_lost.php" class="submenu-item" data-page="lostitems">
                <div class="submenu-icon"><i class="fas fa-search"></i></div>
                <div>Lost Items</div>
            </a>
        </div>
        
        <!-- Dashboard dengan Submenu -->
        <button class="admin-nav-item" data-submenu="dashboardSubmenu">
            <div class="admin-nav-icon"><i class="fas fa-tachometer-alt"></i></div>
            <div class="admin-nav-text">Dashboard</div>
            <div class="nav-arrow"><i class="fas fa-chevron-right"></i></div>
        </button>
        
        <!-- Submenu untuk Dashboard -->
        <div class="submenu" id="dashboardSubmenu">
            <a href="admin_statistics.php" class="submenu-item" data-page="statistics">
                <div class="submenu-icon"><i class="fas fa-chart-bar"></i></div>
                <div>Statistics</div>
            </a>
            <a href="admin_trail.php" class="submenu-item" data-page="trail">
                <div class="submenu-icon"><i class="fas fa-history"></i></div>
                <div>Admin Trail</div>
            </a>
            <a href="admin_users.php" class="submenu-item" data-page="users">
                <div class="submenu-icon"><i class="fas fa-user-friends"></i></div>
                <div>List User Account</div>
            </a>
            <a href="archive_items.php" class="submenu-item" data-page="archive">
                <div class="submenu-icon"><i class="fas fa-archive"></i></div>
                <div>Archive</div>
            </a>
        </div>
    </div>
</div>

<!-- ADMIN TOP HEADER BAR -->
<div class="admin-top-header">
    <div class="admin-page-title">
        <i class="fas fa-<?php 
            $page_icons = [
                'admin_statistics.php' => 'chart-bar',
                'admin_profile.php' => 'user-cog',
                'list_found.php' => 'hand-holding-heart',
                'list_lost.php' => 'search',
                'admin_trail.php' => 'history',
                'admin_users.php' => 'user-friends',
                'form_item.php' => 'plus-circle',
                'archive_items.php' => 'archive'
            ];
            echo $page_icons[$current_page] ?? 'cog';
        ?> admin-title-icon"></i>
        <?php 
        $page_titles = [
            'admin_statistics.php' => 'Dashboard Statistics',
            'admin_profile.php' => 'Admin Profile',
            'list_found.php' => 'Found Items Management',
            'list_lost.php' => 'Lost Items Management',
            'admin_trail.php' => 'Admin Activity Trail',
            'admin_users.php' => 'User Accounts Management',
            'form_item.php' => 'Report Item',
            'archive_items.php' => 'Archive'
        ];
        echo $page_titles[$current_page] ?? 'Admin Panel';
        ?>
    </div>
    
    <div class="admin-header-right">

<button class="tour-btn" onclick="startAdminTour()" title="Start guided tour">?</button>
<div class="font-size-toggle">
    
   <button class="font-btn" onclick="setAdminFontSize('small')" id="afs-small" style="font-size:12px;">A</button>
<button class="font-btn active" onclick="setAdminFontSize('medium')" id="afs-medium" style="font-size:16px;">A</button>
<button class="font-btn" onclick="setAdminFontSize('large')" id="afs-large" style="font-size:21px;">A</button>
</div>

        <!-- Admin Notification Button -->
        <div class="admin-dropdown">
            <button class="admin-notification-btn">
                <i class="fas fa-bell"></i>
                <span class="admin-notification-badge"></span>
            </button>
            
            <div class="admin-notification-panel">
                <div class="admin-notification-header">
                    <span>Admin Notifications</span>
                </div>
                 
                <a href="notifications.php" class="admin-dropdown-item" style="text-align: center; color: #e74c3c; font-weight: 600;">
                    <i class="fas fa-list admin-dropdown-icon"></i>
                    View All Notifications
                </a>
            </div>
        </div>
        
        <!-- Admin User Profile Dropdown -->
        <div class="admin-dropdown">
            <div class="admin-user-profile">
                <div class="admin-user-avatar">
                    <?php if(!empty($user_data['profile_pic']) && file_exists('profile_pics/' . $user_data['profile_pic'])): ?>
                        <img src="profile_pics/<?php echo $user_data['profile_pic']; ?>" 
                             alt="Profile" 
                             style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; 
                                    background: linear-gradient(135deg, #3498db, #2c3e50);
                                    display: flex; align-items: center; justify-content: center;
                                    color: white; font-weight: bold; font-size: 18px;">
                            <?php echo strtoupper(substr($user_data['name'] ?? 'A', 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="admin-user-info">
                    <div class="admin-user-name"><?php echo htmlspecialchars($user_data['name'] ?? 'Administrator'); ?></div>
                    <div class="admin-user-role">ADMIN</div>
                </div>
                <i class="fas fa-chevron-down" style="color: #7f8c8d; font-size: 12px;"></i>
            </div>
            
            <div class="admin-dropdown-menu">
                <div class="admin-dropdown-item">
                    <a href="logout.php" class="admin-logout-btn" style="width: 100%; justify-content: center;">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout Admin
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ADMIN MAIN CONTENT AREA -->
<div class="admin-main-content">
    <!-- Content will be inserted here by other admin pages -->
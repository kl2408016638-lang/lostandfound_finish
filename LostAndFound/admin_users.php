<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['name'];
$message = "";


include_once 'admin_logger.php';

// Handle user deletion
if(isset($_POST['delete_user'])) {
    $user_id_to_delete = $_POST['user_id'];
    
    // Get user details before deletion for logging
    $get_user_sql = "SELECT name, role FROM accounts WHERE id = '$user_id_to_delete'";
    $user_result = mysqli_query($connect, $get_user_sql);
    $user_data = mysqli_fetch_assoc($user_result);
    $deleted_name = $user_data['name'] ?? 'Unknown';
    $deleted_role = $user_data['role'] ?? 'user';
    
    // Protection 1: Admin cannot delete themselves
    if($user_id_to_delete == $admin_id) {
        $message = "<div class='alert alert-error'>You cannot delete your own account!</div>";
    } 
    // Protection 2: Check if this is the last admin
    else {
        // Count how many admins are left
        $count_admins_sql = "SELECT COUNT(*) as admin_count FROM accounts WHERE role = 'admin'";
        $count_result = mysqli_query($connect, $count_admins_sql);
        $admin_count = mysqli_fetch_assoc($count_result)['admin_count'];
        
        // If this is an admin AND it's the last admin, prevent deletion
        if($deleted_role == 'admin' && $admin_count <= 1) {
            $message = "<div class='alert alert-error'>Cannot delete the last admin account!</div>";
        } else {
            // Proceed with deletion
            $delete_sql = "DELETE FROM accounts WHERE id = '$user_id_to_delete'";
            
            if(mysqli_query($connect, $delete_sql)) {
                $message = "<div class='alert alert-success'>User deleted successfully!</div>";
                
                // LOGGING: Admin delete user
                $log_desc = "Deleted {$deleted_role} account #{$user_id_to_delete} '{$deleted_name}'";
                logAdminAction($connect, $admin_id, $admin_name, 'delete_user', 'user', $user_id_to_delete, $deleted_name, $log_desc);
                
            } else {
                $message = "<div class='alert alert-error'>Error deleting user: " . mysqli_error($connect) . "</div>";
            }
        }
    }
}

// Handle user edit
if(isset($_POST['edit_user'])) {
    $user_id_to_edit = $_POST['user_id'];
    $new_name = mysqli_real_escape_string($connect, $_POST['name']);
    $new_email = mysqli_real_escape_string($connect, $_POST['email']);
    $new_contactnum = mysqli_real_escape_string($connect, $_POST['contactnum']);
    
    // Get old user data for logging
    $get_old_sql = "SELECT name, role FROM accounts WHERE id = '$user_id_to_edit'";
    $old_result = mysqli_query($connect, $get_old_sql);
    $old_data = mysqli_fetch_assoc($old_result);
    $old_name = $old_data['name'] ?? 'Unknown';
    $user_role = $old_data['role'] ?? 'user';
    
    // Check if email already exists (excluding current user)
    if(!empty($new_email)) {
        $email_check_sql = "SELECT id FROM accounts WHERE email = '$new_email' AND id != '$user_id_to_edit'";
        $email_check_result = mysqli_query($connect, $email_check_sql);
        
        if(mysqli_num_rows($email_check_result) > 0) {
            $message = "<div class='alert alert-error'>Email already exists!</div>";
            $email_error = true;
        }
    }
    
    if(!isset($email_error)) {
        $update_sql = "UPDATE accounts SET name = '$new_name', email = '$new_email', contactnum = '$new_contactnum' WHERE id = '$user_id_to_edit'";
        
        if(mysqli_query($connect, $update_sql)) {
            $message = "<div class='alert alert-success'>User updated successfully!</div>";
            
            // LOGGING: Admin edit user
            $log_desc = "Edited {$user_role} account #{$user_id_to_edit} '{$new_name}'";
            logAdminAction($connect, $admin_id, $admin_name, 'edit_user', 'user', $user_id_to_edit, $new_name, $log_desc);
            
        } else {
            $message = "<div class='alert alert-error'>Error updating user: " . mysqli_error($connect) . "</div>";
        }
    }
}

// Search and filter functionality
$search = isset($_GET['search']) ? mysqli_real_escape_string($connect, $_GET['search']) : "";
$role_filter = isset($_GET['role']) ? $_GET['role'] : "all";

// Build the query
$sql = "SELECT * FROM accounts WHERE 1=1";

// Apply search filter
if(!empty($search)) {
    $sql .= " AND (name LIKE '%$search%' OR email LIKE '%$search%' OR contactnum LIKE '%$search%')";
}

// Apply role filter
if($role_filter != "all") {
    $sql .= " AND role = '$role_filter'";
}

// Sort by ID descending (latest first)
$sql .= " ORDER BY id DESC";

$result = mysqli_query($connect, $sql);
$total_users = mysqli_num_rows($result);

// Count users by role for stats
$admin_count_sql = "SELECT COUNT(*) as count FROM accounts WHERE role = 'admin'";
$user_count_sql = "SELECT COUNT(*) as count FROM accounts WHERE role = 'user'";
$admin_count_result = mysqli_query($connect, $admin_count_sql);
$user_count_result = mysqli_query($connect, $user_count_sql);
$admin_count = mysqli_fetch_assoc($admin_count_result)['count'];
$user_count = mysqli_fetch_assoc($user_count_result)['count'];


include 'admin_sidebar_nav.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        
        .users-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
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
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .admin-badge {
            background: #e74c3c;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .user-badge {
            background: #2ecc71;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .filters-container {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
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
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-edit {
            background: #f39c12;
            color: white;
        }
        
        .btn-edit:hover {
            background: #d35400;
        }
        
        .btn-clear {
            background: #95a5a6;
            color: white;
        }
        
        .btn-clear:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
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
        }
        
        tr:hover {
            background: #f9f9f9;
        }
        
        .actions-cell {
            display: flex;
            gap: 8px;
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
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            font-size: 20px;
            color: #2c3e50;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #34495e;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 25px;
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
            
            .filters-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .filter-select, .search-input {
                width: 100%;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .actions-cell {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- SIDEBAR & HEADER DAH AUTO MASUK DARI INCLUDE -->
    
    <div class="users-wrapper">
        <!-- Dashboard Card -->
        <div class="dashboard-card">
            <!-- Page Header -->
            <div class="page-header">
                <h1 class="page-title">
                    <i class="fas fa-users"></i>
                    Manage User Accounts
                </h1>
                <div>
                    Logged in as: <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                    <span class="admin-badge">Admin</span>
                </div>
            </div>
            
            <!-- Stats Overview -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Accounts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $admin_count; ?></div>
                    <div class="stat-label">Admin Accounts</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $user_count; ?></div>
                    <div class="stat-label">User Accounts</div>
                </div>
            </div>
            
            <!-- Messages -->
            <?php if($message != ""): ?>
                <?php echo $message; ?>
            <?php endif; ?>
            
            <!-- Filters Section -->
            <form method="GET" action="" class="filters-container">
                <div class="filter-group">
                    <span class="filter-label">Search:</span>
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by name, email, or phone" 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                
                <div class="filter-group">
                    <span class="filter-label">Role:</span>
                    <select name="role" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $role_filter == 'all' ? 'selected' : ''; ?>>All Roles</option>
                        <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin Only</option>
                        <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>User Only</option>
                    </select>
                </div>
                
                <?php if(!empty($search) || $role_filter != 'all'): ?>
                <div class="filter-group">
                    <a href="admin_users.php" class="btn btn-clear">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                </div>
                <?php endif; ?>
            </form>
            
            <!-- Users Table -->
            <?php if($total_users > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = mysqli_fetch_assoc($result)): 
                                $is_current_user = ($user['id'] == $admin_id);
                            ?>
                            <tr>
                                <td>#<?php echo $user['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($user['name']); ?>
                                    <?php if($is_current_user): ?>
                                        <span style="color: #3498db; font-size: 12px;">(You)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo !empty($user['email']) ? htmlspecialchars($user['email']) : '<span style="color:#95a5a6;font-style:italic;">No email</span>'; ?>
                                </td>
                                <td>
                                    <?php echo !empty($user['contactnum']) ? htmlspecialchars($user['contactnum']) : '<span style="color:#95a5a6;font-style:italic;">No phone</span>'; ?>
                                </td>
                                <td>
                                    <?php if($user['role'] == 'admin'): ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php else: ?>
                                        <span class="user-badge">User</span>
                                    <?php endif; ?>
                                </td>
                                <td class="actions-cell">
                                    <button class="btn btn-edit" onclick="openEditModal(
                                        '<?php echo $user['id']; ?>',
                                        '<?php echo htmlspecialchars(addslashes($user['name'] ?? '')); ?>',
                                        '<?php echo htmlspecialchars(addslashes($user['email'] ?? '')); ?>',
                                        '<?php echo htmlspecialchars(addslashes($user['contactnum'] ?? '')); ?>'
                                    )">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    
                                    <?php if(!$is_current_user): ?>
                                    <form method="POST" action="" style="display:inline;" 
                                          onsubmit="return confirmDelete('<?php echo htmlspecialchars(addslashes($user['name'] ?? 'this user')); ?>')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button class="btn btn-danger" disabled title="Cannot delete your own account">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <h3>No users found</h3>
                    <p>
                        <?php if(!empty($search) || $role_filter != 'all'): ?>
                            Try changing your search or filter criteria.
                        <?php else: ?>
                            There are no user accounts in the system.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Summary -->
            <div style="margin-top: 25px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 14px; color: #7f8c8d;">
                <i class="fas fa-info-circle"></i>
                Showing <?php echo $total_users; ?> user account(s)
                <?php if(!empty($search)): ?> matching "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                <?php if($role_filter != 'all'): ?> with role "<?php echo $role_filter; ?>"<?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Edit User Account</h2>
                <button class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form id="editForm" method="POST" action="">
                <input type="hidden" name="user_id" id="edit_user_id">
                <input type="hidden" name="edit_user" value="1">
                
                <div class="form-group">
                    <label class="form-label" for="edit_name">Name *</label>
                    <input type="text" id="edit_name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_email">Email</label>
                    <input type="email" id="edit_email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit_contactnum">Phone Number</label>
                    <input type="text" id="edit_contactnum" name="contactnum" class="form-input" required>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-clear" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Edit Modal Functions
        function openEditModal(userId, userName, userEmail, userPhone) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_name').value = userName;
            document.getElementById('edit_email').value = userEmail;
            document.getElementById('edit_contactnum').value = userPhone;
            document.getElementById('editModal').style.display = 'flex';
        }
        
        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
        
        // Delete confirmation
        function confirmDelete(userName) {
            return confirm(`Are you sure you want to delete "${userName}"?\n\nThis action cannot be undone.`);
        }
    </script>
</body>
</html>
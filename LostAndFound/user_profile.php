<?php
session_start();
include_once 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$message = "";
$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT * FROM accounts WHERE id='$user_id' AND role='user'";
$result = mysqli_query($connect, $sql);
$user = mysqli_fetch_assoc($result);

// Handle profile update (name, contact number, password)
if(isset($_POST['update'])) {
    $name = mysqli_real_escape_string($connect, $_POST['name'] ?? $user['name']);
    $contactnum = mysqli_real_escape_string($connect, $_POST['contactnum'] ?? $user['contactnum']);
    $password = $_POST['password'] ?? '';
    
    // Determine new password
    if(!empty($password)) {
        $new_password = $password;
    } else {
        $new_password = $user['password'];
    }
    
    $update_sql = "UPDATE accounts SET 
                   name='$name', 
                   contactnum='$contactnum', 
                   password='$new_password' 
                   WHERE id='$user_id' AND role='user'";
    
    if(mysqli_query($connect, $update_sql)) {
        $message = "Profile updated successfully!";
        // Refresh user data
        $result = mysqli_query($connect, $sql);
        $user = mysqli_fetch_assoc($result);
        $_SESSION['name'] = $user['name'];
        $_SESSION['profile_pic'] = $user['profile_pic'];
    } else {
        $message = "Error: " . mysqli_error($connect);
    }
}

// Handle picture upload
if(isset($_POST['upload_picture'])) {
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['profile_image']['type'];
        $file_size = $_FILES['profile_image']['size'] / 1024 / 1024; 
        
        if($file_size > 2) {
            $message = "Error: File size must be less than 2MB!";
        } elseif(in_array($file_type, $allowed_types)) {
            // Create profile_pics directory if not exists
            if(!is_dir('profile_pics')) {
                mkdir('profile_pics', 0777, true);
            }
            
            // Delete old picture if exists
            if(!empty($user['profile_pic']) && file_exists('profile_pics/' . $user['profile_pic'])) {
                unlink('profile_pics/' . $user['profile_pic']);
            }
            
            // Generate unique filename
            $file_ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $profile_pic = 'profile_' . $user_id . '_' . time() . '.' . $file_ext;
            $upload_path = 'profile_pics/' . $profile_pic;
            
            if(move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                // Update database with new picture name
                $update_pic_sql = "UPDATE accounts SET profile_pic='$profile_pic' WHERE id='$user_id'";
                if(mysqli_query($connect, $update_pic_sql)) {
                    $message = "Profile picture uploaded successfully!";
                    // Refresh user data
                    $result = mysqli_query($connect, $sql);
                    $user = mysqli_fetch_assoc($result);
                    $_SESSION['profile_pic'] = $user['profile_pic'];
                } else {
                    $message = "Error: " . mysqli_error($connect);
                }
            } else {
                $message = "Error: Failed to upload picture!";
            }
        } else {
            $message = "Error: Only JPG, PNG, and GIF images are allowed!";
        }
    } else {
        $message = "Error: Please select an image to upload!";
    }
}




include 'sidebar_nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Surau Ismail Kharofa</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== PROFILE CONTAINER ===== */
        .profile-wrapper {
            max-width: 1300px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* ===== PAGE HEADER ===== */
        .profile-header {
            background: white;
            border-radius: 16px;
            padding: 25px 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #f0f0f0;
        }
        
        .header-title h1 {
            color: #1a2639;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-title h1 i {
            color: #3498db;
            font-size: 32px;
        }
        
        .header-title p {
            color: #7f8c8d;
            font-size: 15px;
            margin-left: 44px;
        }
        
        .user-badge-large {
            background: linear-gradient(135deg, #e8f4fc 0%, #d4e6f1 100%);
            color: #3498db;
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            border: 2px solid #3498db;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-badge-large i {
            font-size: 20px;
        }
        
        /* ===== TWO COLUMN LAYOUT ===== */
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 25px;
        }
        
        /* ===== LEFT COLUMN CARDS ===== */
        .profile-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            border: 1px solid #f0f0f0;
            margin-bottom: 25px;
        }
        
        .profile-card:last-child {
            margin-bottom: 0;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            color: #3498db;
            font-size: 20px;
        }
        
        /* Profile Picture Section */
        .profile-pic-container {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .profile-pic-wrapper {
            position: relative;
            display: inline-block;
        }
        
        .profile-pic {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .profile-pic-default {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3498db, #2c3e50);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            border: 5px solid white;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .profile-pic-default span {
            font-size: 60px;
            color: white;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .profile-pic-edit {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: #3498db;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        
        .profile-pic-edit:hover {
            background: #2980b9;
            transform: scale(1.1);
        }
        
        /* Upload Section */
        .upload-area {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin-top: 20px;
            border: 2px dashed #cbd5e0;
        }
        
        .file-input-btn {
            background: white;
            border: 2px solid #3498db;
            color: #3498db;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .file-input-btn:hover {
            background: #3498db;
            color: white;
        }
        
        /* Info Cards */
        .info-item {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid;
            transition: transform 0.3s;
        }
        
        .info-item:hover {
            transform: translateX(5px);
        }
        
        .info-label {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 5px;
            font-weight: 600;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .info-value {
            font-size: 18px;
            color: #2c3e50;
            font-weight: 700;
        }
        
        .info-value.key {
            font-family: 'Courier New', monospace;
            font-size: 24px;
            color: #34495e;
            letter-spacing: 3px;
        }
        
        /* ===== RIGHT COLUMN FORM ===== */
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.03);
            border: 1px solid #f0f0f0;
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f8f9fa;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-title i {
            color: #3498db;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #34495e;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-group label i {
            color: #3498db;
            font-size: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }
        
        .password-field {
            background: #fff5f5;
            border-color: #feb2b2;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            letter-spacing: 1px;
        }
        
        .password-field:focus {
            border-color: #e74c3c;
            box-shadow: 0 0 0 4px rgba(231, 76, 60, 0.1);
        }
        
        .btn-update {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .btn-update:hover {
            background: linear-gradient(135deg, #2980b9, #3498db);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.3);
        }
        
        .btn-upload {
            width: 100%;
            padding: 14px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 15px;
        }
        
        .btn-upload:hover {
            background: #229954;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.3);
        }
        
        /* Message Alert */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
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
        
        /* Preview Image */
        .preview-box {
            margin-top: 20px;
            text-align: center;
            display: none;
        }
        
        .preview-box img {
            max-width: 120px;
            max-height: 120px;
            border-radius: 12px;
            border: 3px solid #3498db;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .profile-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .profile-wrapper {
                padding: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .profile-card, .form-card {
                padding: 20px;
            }
            
            .profile-pic, .profile-pic-default {
                width: 130px;
                height: 130px;
            }
            
            .profile-pic-default span {
                font-size: 48px;
            }
        }
    </style>
</head>
<body>
    
    
    <div class="profile-wrapper">
        <!-- Page Header -->
        <div class="profile-header">
            <div class="header-title">
                <h1>
                    <i class="fas fa-user"></i>
                    User Profile
                </h1>
                <p>Manage your personal account and profile picture</p>
            </div>
            
            <div class="user-badge-large">
                <i class="fas fa-user-circle"></i>
                USER ACCOUNT
            </div>
        </div>
        
        <?php if($message != ""): ?>
            <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-error' : 'alert-success'; ?>">
                <i class="fas <?php echo strpos($message, 'Error') !== false ? 'fa-exclamation-circle' : 'fa-check-circle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Profile Grid - 2 Columns -->
        <div class="profile-grid">
            <!-- LEFT COLUMN -->
            <div class="profile-left">
                <!-- Profile Picture Card -->
                <div class="profile-card">
                    <div class="card-title">
                        <i class="fas fa-camera"></i>
                        Profile Picture
                    </div>
                    
                    <div class="profile-pic-container">
                        <div class="profile-pic-wrapper">
                            <?php if(!empty($user['profile_pic']) && file_exists('profile_pics/' . $user['profile_pic'])): ?>
                                <img src="profile_pics/<?php echo $user['profile_pic']; ?>" 
                                     alt="Profile" 
                                     class="profile-pic"
                                     id="currentProfilePic">
                            <?php else: ?>
                                <div class="profile-pic-default" id="defaultIcon">
                                    <span><?php echo substr($user['name'], 0, 1); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <label for="profileImage" class="profile-pic-edit">
                                <i class="fas fa-camera"></i>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Upload Form -->
                    <div class="upload-area">
                        <form method="POST" action="" enctype="multipart/form-data" id="uploadForm">
                            <input type="file" name="profile_image" id="profileImage" 
                                   accept="image/jpeg,image/png,image/gif,image/jpg" 
                                   style="display: none;" onchange="previewImage(this)">
                            
                            <div style="text-align: center;">
                                <span id="fileName" style="color: #7f8c8d; font-size: 14px; display: block; margin-bottom: 10px;">No file chosen</span>
                                
                                <button type="button" class="file-input-btn" onclick="document.getElementById('profileImage').click()">
                                    <i class="fas fa-folder-open"></i> Choose Image
                                </button>
                            </div>
                            
                            <div class="preview-box" id="previewContainer">
                                <img id="imagePreview" alt="Preview">
                            </div>
                            
                            <p style="color: #95a5a6; font-size: 13px; margin: 15px 0; text-align: center;">
                                <i class="fas fa-info-circle"></i> Max size: 2MB | Format: JPG, PNG, GIF
                            </p>
                            
                            <button type="submit" name="upload_picture" class="btn-upload">
                                <i class="fas fa-upload"></i> Upload Picture
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Current Info Card -->
                <div class="profile-card">
                    <div class="card-title">
                        <i class="fas fa-info-circle"></i>
                        Current Information
                    </div>
                    
                    <div class="info-item" style="border-left-color: #28a745;">
                        <div class="info-label">
                            <i class="fas fa-user"></i> FULL NAME
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    
                    <div class="info-item" style="border-left-color: #17a2b8;">
                        <div class="info-label">
                            <i class="fas fa-phone"></i> CONTACT NUMBER
                        </div>
                        <div class="info-value"><?php echo htmlspecialchars($user['contactnum'] ?? 'Not set'); ?></div>
                    </div>

                    <div class="info-item" style="border-left-color: #9b59b6;">
                    <div class="info-label">
                        <i class="fas fa-envelope"></i> EMAIL
                    </div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'Not set'); ?></div>
                </div>
                    
                    <div class="info-item" style="border-left-color: #e74c3c;">
                        <div class="info-label">
                            <i class="fas fa-key"></i> CURRENT PASSWORD
                        </div>
                        <div class="info-value" style="font-family: 'Courier New', monospace; font-size: 16px; background: #fff5f5; padding: 8px 12px; border-radius: 8px; display: inline-block;">
                            <?php echo htmlspecialchars($user['password']); ?>
                        </div>
                    </div>
                    
                    
                    
                    <div class="info-item" style="border-left-color: #6c757d;">
                        <div class="info-label">
                            <i class="fas fa-id-card"></i> ROLE
                        </div>
                        <div class="info-value">USER</div>
                    </div>
                </div>
            </div>
            
            <!-- RIGHT COLUMN -->
            <div class="profile-right">
                <!-- Edit Profile Form -->
                <div class="form-card">
                    <div class="form-title">
                        <i class="fas fa-edit"></i>
                        Edit Profile Information
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-user"></i> Full Name
                            </label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                                   required class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-phone"></i> Contact Number
                            </label>
                            <input type="text" name="contactnum" value="<?php echo htmlspecialchars($user['contactnum'] ?? ''); ?>"
                                   class="form-control" placeholder="Enter your phone number">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-lock"></i> New Password
                                <span style="font-size: 13px; color: #95a5a6; font-weight: normal; margin-left: auto;">
                                    (leave blank to keep current)
                                </span>
                            </label>
                            <input type="text" name="password" placeholder="Enter new password"
                                   class="form-control password-field" value="">
                            <small style="display: block; margin-top: 8px; color: #7f8c8d; font-size: 13px;">
                                <i class="fas fa-info-circle"></i> Current password is displayed above
                            </small>
                        </div>
                        
                        <button type="submit" name="update" class="btn-update">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </form>
                    
                    
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Preview image before upload
        function previewImage(input) {
            const fileName = document.getElementById('fileName');
            const previewContainer = document.getElementById('previewContainer');
            const preview = document.getElementById('imagePreview');
            const currentPic = document.getElementById('currentProfilePic');
            const defaultIcon = document.getElementById('defaultIcon');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                fileName.textContent = 'Selected: ' + file.name;
                
                
                if (file.size > 2097152) {
                    alert('File size must be less than 2MB!');
                    input.value = '';
                    fileName.textContent = 'No file chosen';
                    previewContainer.style.display = 'none';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Only JPG, PNG, and GIF images are allowed!');
                    input.value = '';
                    fileName.textContent = 'No file chosen';
                    previewContainer.style.display = 'none';
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                    
                    // Update preview
                    if (currentPic) {
                        currentPic.src = e.target.result;
                    }
                    if (defaultIcon) {
                        defaultIcon.style.display = 'none';
                        // Create temporary image
                        const tempImg = document.createElement('img');
                        tempImg.src = e.target.result;
                        tempImg.className = 'profile-pic';
                        tempImg.id = 'currentProfilePic';
                        defaultIcon.parentNode.insertBefore(tempImg, defaultIcon);
                        defaultIcon.style.display = 'none';
                    }
                }
                
                reader.readAsDataURL(file);
            } else {
                fileName.textContent = 'No file chosen';
                previewContainer.style.display = 'none';
            }
        }
        
        // Form validation before submit
        document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
            const fileInput = document.getElementById('profileImage');
            if (!fileInput.files || fileInput.files.length === 0) {
                e.preventDefault();
                alert('Please select a file to upload!');
                return false;
            }
        });
    </script>
</body>
</html>
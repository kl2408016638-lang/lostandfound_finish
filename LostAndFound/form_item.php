<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'user') {
    header("Location: login.php");
    exit();
}

$message = "";
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'];

if(isset($_POST['submit'])) {
    // Get form data
    $item_type = $_POST['item_type'] ?? ''; // lost or found
    $type_item = $_POST['type_item'] ?? '';
    $custom_item = $_POST['custom_item'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = $_POST['location'] ?? '';
    $location_custom = $_POST['location_custom'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // If "other" is choosen, use custom_item
    if($type_item == 'other' && !empty($custom_item)) {
        $type_item = $custom_item;
    }
    
    // If "other" location is choosen, use location_custom
    if($location == 'other' && !empty($location_custom)) {
        $location = $location_custom;
    }
    
    // Handle file upload (picture)
    $picture = '';
    if(isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $file_type = $_FILES['picture']['type'];
        
        if(in_array($file_type, $allowed_types)) {
            // Create uploads directory if not exists
            if(!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            
            // Generate unique filename
            $file_ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
            $picture = 'item_' . time() . '_' . $user_id . '.' . $file_ext;
            $upload_path = 'uploads/' . $picture;
            
            if(!move_uploaded_file($_FILES['picture']['tmp_name'], $upload_path)) {
                $picture = '';
                $message = "Error: Failed to upload picture!";
            }
        } else {
            $message = "Error: Only JPG, PNG, and GIF images are allowed!";
        }
    }

    if(empty($picture) && empty($message)) {
    $message = "Error: Please upload a picture of the item!";
}

    
    if(empty($message)) {
        // Insert into database
        $sql = "INSERT INTO items 
                (user_id, user_name, item_type, type_item, date, time, location, picture, description, status, created_at) 
                VALUES 
                ('$user_id', '$user_name', '$item_type', '$type_item', '$date', '$time', '$location', '$picture', '$description', 'pending', NOW())";
        
                if(mysqli_query($connect, $sql)) {
    $message = "Item reported successfully!";
    
    $item_id = mysqli_insert_id($connect);
    $notif_title = "New " . ucfirst($item_type) . " Item Report";
    
    $safe_user = mysqli_real_escape_string($connect, $user_name);
    $safe_item = mysqli_real_escape_string($connect, $type_item);
    
    $notif_message = "User '" . $safe_user . "' reported a new {$item_type} item: " . $safe_item . ".";
    $notif_link = ($item_type == 'lost') ? "list_lost.php" : "list_found.php";
    
    $admin_sql = "SELECT id FROM accounts WHERE role = 'admin'";
    $admin_result = mysqli_query($connect, $admin_sql);
    
    while($admin = mysqli_fetch_assoc($admin_result)) {
        $admin_id = $admin['id'];
        $title_esc = mysqli_real_escape_string($connect, $notif_title);
        $msg_esc = mysqli_real_escape_string($connect, $notif_message);
        $link_esc = mysqli_real_escape_string($connect, $notif_link);
        
        $notif_sql = "INSERT INTO notifications (user_id, type, title, message, link) 
                      VALUES ('$admin_id', 'new_item', '$title_esc', '$msg_esc', '$link_esc')";
        mysqli_query($connect, $notif_sql);
    }
    
    $_POST = array();
} else {
    $message = "Error: " . mysqli_error($connect);
}
    }
}

// Include sidebar navigation
include 'sidebar_nav.php';

?>

<!DOCTYPE html>
<html>
<head>


    <title>Report Item - Surau Ismail Kharofa</title>
    <style>
    :root {
        --green-deep:  #0f3522;
        --green-dark:  #1b4d35;
        --green-mid:   #2e7d52;
        --gold:        #c9a84c;
        --gold-light:  #f0d080;
        --red:         #e74c3c;
        --orange:      #e67e22;
        --white:       #ffffff;
        --bg-page:     #f5f5f2;
        --border:      #dde8e2;
        --text-dark:   #1a1a1a;
        --text-muted:  #888888;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
       
        background: var(--bg-page);
        padding: 32px 20px;
        min-height: 100vh;
    }

    .container {
        max-width: 680px;
        margin: 0 auto;
        background: var(--white);
        padding: 36px 40px;
        border-radius: 22px;
        box-shadow: 0 8px 32px rgba(15,53,34,0.10);
        border: 1px solid #e8ede8;
        position: relative;
        overflow: hidden;
    }

    /* Gold-green accent top */
    .container::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--green-dark), var(--green-mid), var(--gold), transparent);
    }

    h1 {
        
        text-align: center;
        color: var(--text-dark);
        font-size: 28px;
        font-weight: 800;
        margin-bottom: 6px;
    }

    .subtitle {
        text-align: center;
        color: var(--text-muted);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 30px;
    }

    /* ===== ALERT ===== */
    .message {
        padding: 14px 18px;
        border-radius: 12px;
        margin-bottom: 24px;
        font-weight: 700;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-left: 4px solid;
    }

    .success {
        background: #eafaf1;
        color: var(--green-dark);
        border-left-color: var(--green-mid);
    }

    .error {
        background: #fdf0f0;
        color: #922b21;
        border-left-color: var(--red);
    }

    /* ===== FORM ===== */
    .form-group { margin-bottom: 20px; }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 700;
        color: var(--text-dark);
        font-size: 15px;
    }

    input[type="text"],
    input[type="date"],
    input[type="time"],
    input[type="file"],
    textarea,
    select {
        width: 100%;
        padding: 13px 16px;
        border: 2px solid var(--border);
        border-radius: 12px;
        font-size: 15px;
        font-weight: 600;
        color: var(--text-dark);
        background: #fafafa;
        transition: all 0.25s;
        box-sizing: border-box;
    }

    input:focus, textarea:focus, select:focus {
        outline: none;
        border-color: var(--green-mid);
        background: var(--white);
        box-shadow: 0 0 0 4px rgba(46,125,82,0.10);
    }

    textarea {
        resize: vertical;
        min-height: 110px;
        line-height: 1.6;
    }

    select { cursor: pointer; }

    .form-row {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
    }

    .form-row .form-group {
        flex: 1;
        margin-bottom: 0;
    }

    .file-info {
        font-size: 12.5px;
        color: var(--text-muted);
        font-weight: 600;
        margin-top: 6px;
    }

    .hidden { display: none; }

    /* ===== ITEM TYPE SELECTOR ===== */
    .item-type-selector {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        background: #f0f0ec;
        padding: 5px;
        border-radius: 14px;
    }

    .item-type-btn {
        flex: 1;
        padding: 13px;
        border: none;
        border-radius: 10px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        text-align: center;
        transition: all 0.25s;
        background: transparent;
        color: var(--text-muted);
        
    }

    .item-type-btn:hover { background: rgba(255,255,255,0.7); color: var(--text-dark); }

    .found-btn.selected {
        background: var(--white);
        color: var(--green-dark);
        box-shadow: 0 3px 12px rgba(27,77,53,0.15);
    }

    .lost-btn.selected {
        background: var(--white);
        color: var(--orange);
        box-shadow: 0 3px 12px rgba(230,126,34,0.15);
    }

    /* ===== SUBMIT BUTTON ===== */
    .submit-btn {
        width: 100%;
        padding: 16px;
        background: linear-gradient(135deg, var(--green-deep), var(--green-mid));
        color: white;
        border: none;
        border-radius: 12px;
        font-size: 17px;
        font-weight: 800;
        cursor: pointer;
        margin-top: 10px;
        transition: all 0.25s;
        box-shadow: 0 5px 18px rgba(15,53,34,0.28);
        letter-spacing: 0.3px;
        position: relative;
        overflow: hidden;
    }

    .submit-btn::after {
        content: '';
        position: absolute;
        top: 0; left: -100%; width: 100%; height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 22px rgba(15,53,34,0.38);
    }

    .submit-btn:hover::after { left: 100%; }

    .submit-btn.lost-submit {
        background: linear-gradient(135deg, #c0390b, var(--orange));
        box-shadow: 0 5px 18px rgba(230,126,34,0.3);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 600px) {
        .container { padding: 24px 18px; }
        .form-row { flex-direction: column; gap: 0; }
        .form-row .form-group { margin-bottom: 20px; }
        h1 { font-size: 22px; }
        .item-type-selector { flex-direction: column; }
    }
</style>
</head>
<body>

<div class="container">
    <h1>Report Item</h1>
    <p class="subtitle">Surau Ismail Kharofa - Lost and Found System</p>
    
    <?php if($message != ""): ?>
        <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" enctype="multipart/form-data" id="itemForm">
        <!-- Item Type: Lost or Found -->
        <div class="form-group">
            <label>Report Type:</label>
            <div class="item-type-selector">
                <div class="item-type-btn found-btn selected" onclick="selectItemType('found')">
                    📍 Report Found Item
                </div>
                <div class="item-type-btn lost-btn" onclick="selectItemType('lost')">
                    🔍 Report Lost Item
                </div>
            </div>
            <input type="hidden" name="item_type" id="item_type" value="found" required>
        </div>
        
        <!-- Type Item -->
        <div class="form-group">
            <label for="type_item">Type of Item:</label>
            <select name="type_item" id="type_item" required onchange="toggleCustomItem()">
                <option value="">-- Select Item Type --</option>
                <option value="wallet">Wallet/Purse</option>
                <option value="phone">Mobile Phone</option>
                <option value="keys">Keys</option>
                <option value="documents">Documents</option>
                <option value="jewelry">Jewelry</option>
                <option value="sandal">Sandal</option>
                <option value="clothing">Clothing</option>
                <option value="umbrella">Umbrella</option>
                <option value="reading_material">Al-Quran/Books</option>
                <option value="prayer_equipment">Prayer Equipment</option>
                <option value="food_container">Water Bottle/Food Container</option>
                <option value="other">Other (Please specify)</option>
            </select>
        </div>
        
        <!-- Custom Item Input (show only when "other" selected) -->
        <div class="form-group hidden" id="customItemGroup">
            <label for="custom_item">Please specify the item:</label>
            <input type="text" name="custom_item" id="custom_item" placeholder="e.g., Water bottle, Umbrella, Glasses, etc.">
        </div>
        
        <div class="form-row">
            <!-- Date -->
            <div class="form-group">
                <label for="date">Date:</label>
                <input type="date" name="date" id="date" required>
            </div>
            
            <!-- Time -->
            <div class="form-group">
                <label for="time">Time:</label>
                <input type="time" name="time" id="time" required>
            </div>
        </div>
        
        <!-- Location -->
        <div class="form-group">
            <label for="location">Location:</label>
            <select name="location" id="location" required onchange="toggleCustomLocation()">
                <option value="">-- Select Location --</option>
                <option value="main_hall">Main Prayer Hall</option>
                <option value="female_prayer_area">Female Prayer Area</option>
                <option value="ablution_area">Ablution Area</option>
                <option value="toilet">Toilet</option>
                <option value="cooking_area">Cooking Area</option>
                <option value="surau_qurban_area">Surau Qurban Area</option>
                <option value="main_entrance">Main Entrance</option>
                <option value="back_entrance">Back Entrance</option>
                <option value="other">Other Area (Please specify)</option>
            </select>
        </div>
        
        <!-- Custom Location Input -->
        <div class="form-group hidden" id="customLocationGroup">
            <label for="location_custom">Please specify location:</label>
            <input type="text" name="location_custom" id="location_custom" placeholder="e.g., Near shoe rack, Outside toilet, etc.">
        </div>
        
        <!-- Picture -->
        <div class="form-group">
            <label for="picture">Picture of Item:</label>
            <input type="file" name="picture" id="picture" accept="image/*">
            <div class="file-info">Required: Upload photo of the item (JPG, PNG, GIF)</div>
        </div>
        
        <!-- Description -->
        <div class="form-group">
            <label for="description">Description:</label>
            <textarea name="description" id="description" placeholder="Describe the item in detail (color, brand, size, distinguishing features, etc.)" required></textarea>
        </div>
        
        <button type="submit" name="submit" class="submit-btn" id="submitBtn">
            Submit Found Item Report
        </button>
    </form>
    
    
</div>

<script>
    // Set default date to today
    document.getElementById('date').valueAsDate = new Date();
    
    // Set default time to current time
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    document.getElementById('time').value = `${hours}:${minutes}`;
    
    // Item type selection
    function selectItemType(type) {
    document.getElementById('item_type').value = type;
    
    document.querySelector('.found-btn').classList.remove('selected');
    document.querySelector('.lost-btn').classList.remove('selected');
    
    if(type === 'found') {
        document.querySelector('.found-btn').classList.add('selected');
        document.getElementById('submitBtn').textContent = '📍 Submit Found Item Report';
        document.getElementById('submitBtn').classList.remove('lost-submit');
    } else {
        document.querySelector('.lost-btn').classList.add('selected');
        document.getElementById('submitBtn').textContent = '🔍 Submit Lost Item Report';
        document.getElementById('submitBtn').classList.add('lost-submit');
    }
}
    
    // Show/hide custom item input
    function toggleCustomItem() {
        const itemType = document.getElementById('type_item').value;
        const customItemGroup = document.getElementById('customItemGroup');
        
        if(itemType === 'other') {
            customItemGroup.classList.remove('hidden');
            document.getElementById('custom_item').required = true;
        } else {
            customItemGroup.classList.add('hidden');
            document.getElementById('custom_item').required = false;
        }
    }
    
    // Show/hide custom location input
    function toggleCustomLocation() {
        const location = document.getElementById('location').value;
        const customLocationGroup = document.getElementById('customLocationGroup');
        
        if(location === 'other') {
            customLocationGroup.classList.remove('hidden');
            document.getElementById('location_custom').required = true;
        } else {
            customLocationGroup.classList.add('hidden');
            document.getElementById('location_custom').required = false;
        }
    }
    
    // Form validation
    document.getElementById('itemForm').addEventListener('submit', function(e) {
        const itemType = document.getElementById('type_item').value;
        const customItem = document.getElementById('custom_item').value;
        
        // If "other" selected but no custom item specified
        if(itemType === 'other' && !customItem.trim()) {
            e.preventDefault();
            alert('Please specify the item type in the "Please specify the item" field.');
            document.getElementById('custom_item').focus();
            return false;
        }
        
        const location = document.getElementById('location').value;
        const customLocation = document.getElementById('location_custom').value;
        
        // If "other" location selected but no custom location specified
        if(location === 'other' && !customLocation.trim()) {
            e.preventDefault();
            alert('Please specify the location in the "Please specify location" field.');
            document.getElementById('location_custom').focus();
            return false;
        }
    });
</script>

</body>
</html>
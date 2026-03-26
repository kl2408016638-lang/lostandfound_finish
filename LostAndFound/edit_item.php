<?php
session_start();
include 'db_connect.php';


if(!isset($_SESSION['user_id']) || $_SESSION['role'] == 'admin') {
    header("Location: login.php");
    exit();
}

// Include user sidebar
if(file_exists('sidebar_nav.php')) {
    include 'sidebar_nav.php';
} else {
    echo '<div style="padding:20px;background:#f8d7da;color:#721c24;">Sidebar not found.</div>';
}

$user_id = $_SESSION['user_id'];
$message = "";

// Check if ID and type are provided
if(!isset($_GET['id']) || !isset($_GET['type'])) {
    header("Location: user_dashboard.php");
    exit();
}

$item_id = $_GET['id'];
$item_type = $_GET['type']; // 'lost' or 'found'

// Get item details
$sql = "SELECT * FROM items WHERE id = '$item_id' AND user_id = '$user_id'";
$result = mysqli_query($connect, $sql);

if(mysqli_num_rows($result) == 0) {
    header("Location: user_dashboard.php");
    exit();
}

$item = mysqli_fetch_assoc($result);

// Handle form submission
if(isset($_POST['update'])) {
    $type_item = $_POST['type_item'];
    $custom_item = ($type_item == 'other') ? $_POST['custom_item'] : '';
    $date = $_POST['date'];
    $time = $_POST['time'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    
    // Handle picture upload
    $picture = $item['picture']; // keep old picture by default
    
    if(isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
        $target_dir = "uploads/";
        $picture = time() . "_" . $user_id . "_" . basename($_FILES['picture']['name']);
        $target_file = $target_dir . $picture;
        
        if(move_uploaded_file($_FILES['picture']['tmp_name'], $target_file)) {
            // Delete old picture if exists
            if(!empty($item['picture']) && file_exists($target_dir . $item['picture'])) {
                unlink($target_dir . $item['picture']);
            }
        }
    }
    
    // Update query
    $update_sql = "UPDATE items SET 
                    type_item = '$type_item',
                    custom_item = '$custom_item',
                    date = '$date',
                    time = '$time',
                    location = '$location',
                    description = '$description',
                    picture = '$picture'
                  WHERE id = '$item_id' AND user_id = '$user_id'";
    
    if(mysqli_query($connect, $update_sql)) {
        $message = "<div class='success'>Item updated successfully!</div>";
        // Refresh item data
        $result = mysqli_query($connect, $sql);
        $item = mysqli_fetch_assoc($result);
    } else {
        $message = "<div class='error'>Error: " . mysqli_error($connect) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Item</title>
    <style>
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        h1 { color: #2c5530; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        input, select, textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #2c5530; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #3a7c3e; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
        .back-link { margin-top: 20px; display: block; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit <?php echo ucfirst($item_type); ?> Item</h1>
        
        <?php echo $message; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Item Type:</label>
                <select name="type_item" required>
                    <option value="">Select item type</option>
                    <option value="wallet" <?php echo ($item['type_item'] == 'wallet') ? 'selected' : ''; ?>>Wallet</option>
                    <option value="phone" <?php echo ($item['type_item'] == 'phone') ? 'selected' : ''; ?>>Mobile Phone</option>
                    <option value="keys" <?php echo ($item['type_item'] == 'keys') ? 'selected' : ''; ?>>Keys</option>
                    <option value="documents" <?php echo ($item['type_item'] == 'documents') ? 'selected' : ''; ?>>Documents</option>
                    <option value="jewelry" <?php echo ($item['type_item'] == 'jewelry') ? 'selected' : ''; ?>>Jewelry</option>
                    <option value="sandal" <?php echo ($item['type_item'] == 'sandal') ? 'selected' : ''; ?>>Sandal</option>
                    <option value="clothing" <?php echo ($item['type_item'] == 'clothing') ? 'selected' : ''; ?>>Clothing</option>
                    <option value="umbrella" <?php echo ($item['type_item'] == 'umbrella') ? 'selected' : ''; ?>>Umbrella</option>
                    <option value="reading_material" <?php echo ($item['type_item'] == 'reading_material') ? 'selected' : ''; ?>>Al-Quran/Books</option>
                    <option value="prayer_equipment" <?php echo ($item['type_item'] == 'prayer_equipment') ? 'selected' : ''; ?>>Prayer Equipment</option>
                    <option value="food_container" <?php echo ($item['type_item'] == 'food_container') ? 'selected' : ''; ?>>Water Bottle/Food Container</option>
                    <option value="other"<?php echo ($item['type_item'] == 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>
            
            <div class="form-group" id="custom_item_group" style="display: <?php echo ($item['type_item'] == 'other') ? 'block' : 'none'; ?>;">
                <label>Specify Item:</label>
                <input type="text" name="custom_item" value="<?php echo htmlspecialchars($item['custom_item']); ?>" placeholder="e.g., Water bottle">
            </div>
            
            <div class="form-group">
                <label>Date:</label>
                <input type="date" name="date" value="<?php echo $item['date']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Time:</label>
                <input type="time" name="time" value="<?php echo $item['time']; ?>" required>
            </div>
            
            <div class="form-group">
                <label>Location:</label>
                <select name="location" required>
                    <option value="">Select location</option>
                    <option value="main_hall" <?php echo ($item['location'] == 'main_hall') ? 'selected' : ''; ?>>Main Prayer Hall</option>
                    <option value="female_prayer_area" <?php echo ($item['location'] == 'female_prayer_area') ? 'selected' : ''; ?>>Female Prayer Area</option>
                    <option value="ablution_area" <?php echo ($item['location'] == 'ablution_area') ? 'selected' : ''; ?>>Ablution Area</option>
                    <option value="toilet" <?php echo ($item['location'] == 'toilet') ? 'selected' : ''; ?>>Toilet</option>
                    <option value="cooking_area" <?php echo ($item['location'] == 'cooking_area') ? 'selected' : ''; ?>>Cooking Area</option>
                     <option value="surau_qurban_area" <?php echo ($item['location'] == 'surau_qurban_area') ? 'selected' : ''; ?>>Surau Qurban Area</option>
                      <option value="main_entrance" <?php echo ($item['location'] == 'main_entrance') ? 'selected' : ''; ?>>Main Entrance</option>
                       <option value="back_entrance" <?php echo ($item['location'] == 'back_entrance') ? 'selected' : ''; ?>>Back Entrance</option>
                    <option value="others" <?php echo ($item['location'] == 'others') ? 'selected' : ''; ?>>Others</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Description:</label>
                <textarea name="description" rows="4" required><?php echo htmlspecialchars($item['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Current Picture:</label>
                <?php if(!empty($item['picture']) && file_exists('uploads/' . $item['picture'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="uploads/<?php echo $item['picture']; ?>" style="max-width: 200px; max-height: 200px;">
                    </div>
                <?php endif; ?>
                <label>Upload New Picture (optional):</label>
                <input type="file" name="picture" accept="image/*">
            </div>
            
            <button type="submit" name="update">Update Item</button>
           
        </form>
    </div>
    
    <script>
        // Show/hide custom item field based on selection
        document.querySelector('select[name="type_item"]').addEventListener('change', function() {
            const customGroup = document.getElementById('custom_item_group');
            if(this.value === 'other') {
                customGroup.style.display = 'block';
            } else {
                customGroup.style.display = 'none';
            }
        });
    </script>
</body>
</html>
<?php
session_start();
require 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginForm.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';
$user = null;

// Get user data
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result && mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
} else {
    $error_message = "Error retrieving user information";
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
    $age = is_numeric($_POST['age']) ? intval($_POST['age']) : 0;
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validate input
    if (empty($fullname)) {
        $error_message = "Full name is required";
    } elseif ($age < 0 || $age > 120) {
        $error_message = "Please enter a valid age";
    } else {
        // Update user profile
        $update_query = "UPDATE users SET 
                         user_name = ?,
                         gender = ?,
                         age = ?,
                         phone = ?,
                         address = ?
                         WHERE user_id = ?";
        
        $stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($stmt, "ssissi", $fullname, $gender, $age, $phone, $address, $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_message = "Profile updated successfully!";
            
            // Update session data
            $_SESSION['username'] = $fullname;
            
            // Refresh user data
            $stmt = mysqli_prepare($conn, $user_query);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Error updating profile: " . mysqli_error($conn);
        }
    }
}

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All password fields are required";
    } elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match";
    } elseif (strlen($new_password) < 6) {
        $error_message = "New password must be at least 6 characters";
    } else {
        // Verify current password
        if (password_verify($current_password, $user['password_hash'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password_hash = ? WHERE user_id = ?";
            
            $stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($stmt, "si", $hashed_password, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_message = "Password updated successfully!";
            } else {
                $error_message = "Error updating password: " . mysqli_error($conn);
            }
        } else {
            $error_message = "Current password is incorrect";
        }
    }
}

// Handle avatar update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_avatar'])) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $error_message = "Only JPG, PNG and GIF images are allowed";
        } elseif ($_FILES['avatar']['size'] > $max_size) {
            $error_message = "File size must be less than 5MB";
        } else {
            $upload_dir = 'avatars/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $new_filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_file)) {
                // Update avatar in database
                $update_query = "UPDATE users SET avatar = ? WHERE user_id = ?";
                
                $stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($stmt, "si", $target_file, $user_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    // Update session avatar
                    $_SESSION['avatar'] = $target_file;
                    $success_message = "Avatar updated successfully!";
                    
                    // Refresh user data
                    $stmt = mysqli_prepare($conn, $user_query);
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $user = mysqli_fetch_assoc($result);
                } else {
                    $error_message = "Error updating avatar in database: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Error uploading avatar";
            }
        }
    } else {
        $error_message = "Please select an image to upload";
    }
}

// Get user order history
$orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$orders_result = mysqli_stmt_get_result($stmt);

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - E-Sale</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Profile page styles */
        .profile-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-name {
            margin: 0;
            font-size: 24px;
        }
        
        .profile-role {
            background-color: #f8f9fa;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .admin-role {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .user-role {
            background-color: #e2e3e5;
            color: #383d41;
        }
        
        .profile-tabs {
            display: flex;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        
        .profile-tab {
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
            transition: background-color 0.3s;
        }
        
        .profile-tab.active {
            background-color: #007bff;
            color: white;
            border: 1px solid #007bff;
            border-bottom: none;
        }
        
        .profile-tab:not(.active):hover {
            background-color: #f1f1f1;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .profile-section {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="tel"],
        input[type="number"],
        input[type="password"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .section-title {
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #2ecc71;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .message-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .user-info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        
        .order-item {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        
        .order-id {
            font-weight: bold;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9em;
        }
        
        .order-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            text-transform: uppercase;
            margin-left: 5px;
        }
        
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        
        .status-processing {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background-color: #c3e6cb;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .view-orders-btn {
            display: block;
            text-align: center;
            margin-top: 10px;
        }
        
        /* Updated user avatar styles */
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            max-width: 100%;
            max-height: 100%;
            display: inline-block;
            vertical-align: middle;
        }
        
        .menu .user-avatar {
            width: 24px;
            height: 24px;
            margin-right: 6px;
        }
        
        /* Fix for menu item alignment */
        .menu li > a {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo">
                <a href="ecommerce.php">
                    <img id="logo" src="images/btec.jpg" alt="Logo">
                </a>
            </div>
        </div>
        
        <div class="menu">
            <ul>
                <li>
                    <a href="ecommerce.php">
                        <i class="fas fa-home"></i>
                        Home
                    </a>
                </li>
                <?php if(isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="#">
                        <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="Avatar" class="user-avatar">
                        <?php echo htmlspecialchars($_SESSION['username']); ?>
                        <i class="fas fa-chevron-down" style="font-size: 12px; margin-left: 5px;"></i>
                    </a>
                    <div class="dropdown">
                        <a href="profile.php">View Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </li>
                <?php else: ?>
                <li class="login-item">
                    <a href="loginForm.php">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="profile-container">
            <?php if ($user): ?>
                <div class="profile-header">
                    <img src="<?php echo htmlspecialchars($user['avatar'] ?: 'images/default_avatar.jpg'); ?>" alt="Profile Avatar" class="profile-avatar">
                    <div>
                        <h1 class="profile-name">
                            <?php echo htmlspecialchars($user['user_name']); ?>
                            <span class="profile-role <?php echo $user['role'] == 1 ? 'admin-role' : 'user-role'; ?>">
                                <?php echo $user['role'] == 1 ? 'Admin' : 'User'; ?>
                            </span>
                        </h1>
                        <p>Member since: <?php echo date('F j, Y', strtotime($user['created_at'] ?? 'now')); ?></p>
                    </div>
                </div>
                
                <?php if (!empty($success_message)): ?>
                    <div class="message message-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error_message)): ?>
                    <div class="message message-error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                
                <div class="profile-tabs">
                    <div class="profile-tab active" onclick="switchTab('profile-info')">Profile Information</div>
                    <div class="profile-tab" onclick="switchTab('edit-profile')">Edit Profile</div>
                    <div class="profile-tab" onclick="switchTab('change-password')">Change Password</div>
                    <div class="profile-tab" onclick="switchTab('change-avatar')">Change Avatar</div>
                    <div class="profile-tab" onclick="switchTab('order-history')">Order History</div>
                </div>
                
                <!-- Profile Info Tab -->
                <div id="profile-info" class="tab-content active">
                    <div class="profile-section">
                        <h2 class="section-title">Personal Information</h2>
                        
                        <div class="user-info-item">
                            <span class="info-label">Full Name:</span>
                            <span><?php echo htmlspecialchars($user['user_name']); ?></span>
                        </div>
                        
                        <div class="user-info-item">
                            <span class="info-label">Email:</span>
                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        
                        <div class="user-info-item">
                            <span class="info-label">Gender:</span>
                            <span><?php echo $user['gender'] ? htmlspecialchars($user['gender']) : 'Not specified'; ?></span>
                        </div>
                        
                        <div class="user-info-item">
                            <span class="info-label">Age:</span>
                            <span><?php echo $user['age'] ? htmlspecialchars($user['age']) : 'Not specified'; ?></span>
                        </div>
                        
                        <div class="user-info-item">
                            <span class="info-label">Phone:</span>
                            <span><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not specified'; ?></span>
                        </div>
                        
                        <div class="user-info-item">
                            <span class="info-label">Address:</span>
                            <span><?php echo $user['address'] ? htmlspecialchars($user['address']) : 'Not specified'; ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Edit Profile Tab -->
                <div id="edit-profile" class="tab-content">
                    <div class="profile-section">
                        <h2 class="section-title">Edit Profile Information</h2>
                        
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="fullname">Full Name</label>
                                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['user_name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email (cannot be changed)</label>
                                <input type="text" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label for="gender">Gender</label>
                                <select id="gender" name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="Male" <?php echo $user['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo $user['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo $user['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="age">Age</label>
                                <input type="number" id="age" name="age" min="0" max="120" value="<?php echo htmlspecialchars($user['age'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-success">Update Profile</button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Password Tab -->
                <div id="change-password" class="tab-content">
                    <div class="profile-section">
                        <h2 class="section-title">Change Password</h2>
                        
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="update_password" class="btn btn-success">Change Password</button>
                        </form>
                    </div>
                </div>
                
                <!-- Change Avatar Tab -->
                <div id="change-avatar" class="tab-content">
                    <div class="profile-section">
                        <h2 class="section-title">Change Profile Picture</h2>
                        
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="avatar">Select Profile Picture</label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" required onchange="previewImage(event)">
                            </div>
                            
                            <div class="form-group">
                                <label>Preview</label>
                                <div>
                                    <img id="preview" src="#" alt="Preview" style="max-width: 200px; max-height: 200px; display: none;">
                                </div>
                            </div>
                            
                            <button type="submit" name="update_avatar" class="btn btn-success">Update Avatar</button>
                        </form>
                    </div>
                </div>
                
                <!-- Order History Tab -->
                <div id="order-history" class="tab-content">
                    <div class="profile-section">
                        <h2 class="section-title">Recent Orders</h2>
                        
                        <?php if (!$table_exists): ?>
                            <p>Order tracking is not available yet.</p>
                            <a href="ecommerce.php" class="btn">Continue Shopping</a>
                        <?php elseif ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                            <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                                <div class="order-item">
                                    <div>
                                        <span class="order-id">Order #<?php echo $order['order_id']; ?></span>
                                        <span class="order-date"><?php echo date("M d, Y", strtotime($order['created_at'])); ?></span>
                                        <span class="order-status status-<?php echo strtolower($order['status']); ?>"><?php echo ucfirst($order['status']); ?></span>
                                    </div>
                                    <div>Total: <?php echo number_format($order['total_amount']); ?> VND</div>
                                </div>
                            <?php endwhile; ?>
                            
                            <a href="orders.php" class="btn view-orders-btn">View All Orders</a>
                        <?php else: ?>
                            <p>You haven't placed any orders yet.</p>
                            <a href="ecommerce.php" class="btn">Start Shopping</a>
                        <?php endif; ?>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="message message-error">Error loading user profile. Please try again later.</div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 E-Sale Electronic Store. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        function switchTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.profile-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked tab button
            event.currentTarget.classList.add('active');
        }
        
        function previewImage(event) {
            const preview = document.getElementById('preview');
            preview.src = URL.createObjectURL(event.target.files[0]);
            preview.style.display = 'block';
        }
    </script>
</body>
</html>
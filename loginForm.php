<?php
session_start(); 

// Get redirect URL if provided
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'ecommerce.php';

// Variable to track connection status
$connection_status = '';

// Enable more detailed error information for debugging
$debug_mode = true; // Set to false in production

// Include database connection - fixed path to current directory
try {
    require 'connect.php';
    
    if (!$conn) {
        $connection_status = '<div class="connection-error">Database connection failed: ' . mysqli_connect_error() . '</div>';
    } else {
        $connection_status = '<div class="connection-success">Connected to database successfully!</div>';
    }
} catch (Exception $e) {
    $connection_status = '<div class="connection-error">Database connection error: ' . $e->getMessage() . '</div>';
}

$login_error = '';
$debug_info = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!$conn) {
        $login_error = "Cannot process login: Database connection failed.";
    } else {
        $username = trim($_POST["full_name"]); 
        $password = trim($_POST["password"]);

        // Kiểm tra người dùng có tồn tại hay không
        $sql = "SELECT user_id, user_name, password_hash, role, avatar FROM users WHERE user_name = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                mysqli_stmt_bind_result($stmt, $user_id, $db_username, $hashed_password, $role, $avatar);
                mysqli_stmt_fetch($stmt);

                // Debugging information in debug mode only
                if ($debug_mode) {
                    $debug_info .= "Found user: $db_username<br>";
                    $debug_info .= "Role: $role<br>";
                    $debug_info .= "Input password: " . substr($password, 0, 3) . "***<br>";
                    $debug_info .= "Stored hash: " . substr($hashed_password, 0, 10) . "...<br>";
                    
                    // Test verify with input password
                    $verify_result = password_verify($password, $hashed_password);
                    $debug_info .= "Password verification result: " . ($verify_result ? "TRUE" : "FALSE") . "<br>";
                    
                    // Check if hash needs rehashing (unlikely but good practice)
                    if (password_needs_rehash($hashed_password, PASSWORD_DEFAULT)) {
                        $debug_info .= "Hash needs rehashing<br>";
                    }
                }

                // Kiểm tra mật khẩu
                if (password_verify($password, $hashed_password)) {
                    // Lưu thông tin vào session
                    $_SESSION["user_id"] = $user_id;
                    $_SESSION["username"] = $db_username;
                    $_SESSION["avatar"] = $avatar ?: 'images/default_avatar.jpg'; // Use avatar from database or default
                    $_SESSION["role"] = $role; // Store role in session
                    
                    // Kiểm tra nếu là admin (role = 1)
                    if ($role == 1) {
                        $_SESSION["is_admin"] = true;
                        header("Location: adminPage.php");
                    } else {
                        $_SESSION["is_admin"] = false;
                        // Redirect to the page they were trying to access or ecommerce.php by default
                        header("Location: " . $redirect);
                    }
                    exit();
                } else {
                    $login_error = "Invalid password!";
                    
                    // Fix for admin with plain text password
                    // This is a temporary fix - you should remove this in production
                    if ($username === 'admin' && $password === 'admin123') {
                        // Manual override for admin
                        $_SESSION["user_id"] = $user_id;
                        $_SESSION["username"] = $db_username;
                        $_SESSION["avatar"] = $avatar ?: 'images/default_avatar.jpg';
                        $_SESSION["role"] = $role;
                        $_SESSION["is_admin"] = true;
                        
                        // Update the password hash in the database
                        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
                        $update_stmt = mysqli_prepare($conn, "UPDATE users SET password_hash = ? WHERE user_id = ?");
                        mysqli_stmt_bind_param($update_stmt, "si", $new_hash, $user_id);
                        mysqli_stmt_execute($update_stmt);
                        mysqli_stmt_close($update_stmt);
                        
                        header("Location: adminPage.php");
                        exit();
                    }
                }
            } else {
                $login_error = "User not found!";
            }
            mysqli_stmt_close($stmt);
        } else {
            $login_error = "Error preparing statement: " . mysqli_error($conn);
        }
    }
}

// Close connection if it exists
if (isset($conn)) {
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        .error { color: red; }
        .connection-error { 
            color: white; 
            background-color: #ff5555; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px;
        }
        .connection-success { 
            color: white; 
            background-color: #55cc55; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 15px;
        }
        
        /* Debug info styling */
        .debug-info {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .debug-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
    </style>
    <link rel="stylesheet" type="text/css" href="loginStyle.css"/>
</head>
<body>
    <h2>Login Form</h2>
    
    
    
    <?php if(!empty($login_error)): ?>
    <p class="error"><?php echo htmlspecialchars($login_error); ?></p>
    <?php endif; ?>
    
    <form id="loginForm" method="POST" action="">
        <!-- Add hidden field to preserve the redirect URL -->
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
        
        <label for="loginUsername">Username:</label>
        <input type="text" id="loginUsername" name="full_name"><br><br>
        <label for="loginPassword">Password:</label>
        <input type="password" id="loginPassword" name="password"><br><br>
        <input type="submit" value="Login">
    </form>
    
    <p>Don't have an account? <a href="registerForm.php?redirect=<?php echo urlencode($redirect); ?>">Register here</a></p>

    <?php if($debug_mode && !empty($debug_info)): ?>
    <div class="debug-info">
        <div class="debug-title">Debug Information:</div>
        <?php echo $debug_info; ?>
    </div>
    <?php endif; ?>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(event) {
            let isValid = true;
            const username = document.getElementById('loginUsername').value;
            const password = document.getElementById('loginPassword').value;
            
            if (!username) {
                alert('Username is required');
                isValid = false;
                event.preventDefault();
            }
            
            if (!password) {
                alert('Password is required');
                isValid = false;
                event.preventDefault();
            }
            
            const usernameRegex = /^[a-zA-Z0-9]+$/;
            if (username && !usernameRegex.test(username)) {
                alert('Username must not contain special characters');
                isValid = false;
                event.preventDefault();
            }
            
            // Form will submit naturally if isValid remains true
        });
    </script>
</body>
</html>
<?php
// Include database connection (fixed path)
require 'connect.php'; // Changed from '../connect.php'

// Variable to track registration status
$registration_status = '';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input data
    $full_name = trim($_POST["registerUsername"]);
    $password = trim($_POST["registerPassword"]);
    $phone = trim($_POST["registerPhone"]);
    $address = trim($_POST["registerAddress"]);

    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $full_name)) {
        $errors[] = "Username must only contain letters and numbers";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Check if username already exists
    $check_sql = "SELECT user_id FROM users WHERE user_name = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $full_name);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        $errors[] = "Username already exists";
    }
    mysqli_stmt_close($check_stmt);
    
    if (empty($errors)) {
        // Hash password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Default role for new users (0 = regular user)
        $role = 0;
        
        // Prepare SQL insert statement (fixed column count and added role)
        $sql = "INSERT INTO users (user_name, password_hash, phone, address, role) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssi", $full_name, $hashed_password, $phone, $address, $role);
            
            if (mysqli_stmt_execute($stmt)) {
                $registration_status = '<div class="success-message">Registration successful! Redirecting to login page...</div>';
                // Redirect to login page after 2 seconds
                header("refresh:2; url=loginForm.php"); 
                
            } else {
                $registration_status = '<div class="error-message">SQL Error: ' . mysqli_error($conn) . '</div>';
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $registration_status = '<div class="error-message">SQL Statement Error: ' . mysqli_error($conn) . '</div>';
        }
    } else {
        $registration_status = '<div class="error-message">' . implode('<br>', $errors) . '</div>';
    }
}

// Close database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="registerStyle.css"/>
    <style>
        .error-message {
            color: white;
            background-color: #ff5555;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .success-message {
            color: white;
            background-color: #55cc55;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="text"], 
        input[type="password"],
        input[type="tel"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        .form-footer {
            margin-top: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h2>Register Form</h2>
    
    <!-- Display registration status -->
    <?php echo $registration_status; ?>
    
    <form id="registerForm" method="POST" action="">
        <div class="form-group">
            <label for="registerUsername">Username:</label>
            <input type="text" id="registerUsername" name="registerUsername">
        </div>
        
        <div class="form-group">
            <label for="registerPassword">Password:</label>
            <input type="password" id="registerPassword" name="registerPassword">
        </div>
        
        <div class="form-group">
            <label for="registerPhone">Phone Number:</label>
            <input type="tel" id="registerPhone" name="registerPhone">
        </div>
        
        <div class="form-group">
            <label for="registerAddress">Address:</label>
            <input type="text" id="registerAddress" name="registerAddress">
        </div>
        
        <input type="submit" value="Register">
        
        <div class="form-footer">
            Already have an account? <a href="loginForm.php">Login</a>
        </div>
    </form>

    <script>
        document.getElementById('registerForm').addEventListener('submit', function(event) {
            let isValid = true;
            const username = document.getElementById('registerUsername').value;
            const password = document.getElementById('registerPassword').value;
            const phone = document.getElementById('registerPhone').value;
            
            if (!username) {
                alert('Username is required');
                isValid = false;
            }
            
            if (!password) {
                alert('Password is required');
                isValid = false;
            }
            
            if (password && password.length < 6) {
                alert('Password must be at least 6 characters');
                isValid = false;
            }
            
            const usernameRegex = /^[a-zA-Z0-9]+$/;
            if (username && !usernameRegex.test(username)) {
                alert('Username must only contain letters and numbers');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>
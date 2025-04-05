<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    // Not logged in or not admin, redirect to login page
    header("Location: loginForm.php");
    exit();
}

$product_added = false;
$error_message = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    require 'connect.php';
    
    // Get and sanitize input
    $product_name = trim(mysqli_real_escape_string($conn, $_POST['product_name']));
    $product_price = floatval($_POST['product_price']);
    $product_description = trim(mysqli_real_escape_string($conn, $_POST['product_description']));
    $product_category = trim(mysqli_real_escape_string($conn, $_POST['product_category']));
    $product_brand = trim(mysqli_real_escape_string($conn, $_POST['product_brand']));
    $product_stock = intval($_POST['product_stock']);
    
    // Handle image upload
    $target_dir = "images/";
    $product_image = "images/default_product.jpg"; // Default image
    
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $file_name = basename($_FILES["product_image"]["name"]);
        $target_file = $target_dir . time() . '_' . $file_name; // Add timestamp to prevent duplicate names
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if file is an actual image
        $check = getimagesize($_FILES["product_image"]["tmp_name"]);
        if ($check !== false) {
            // Check file size (max 5MB)
            if ($_FILES["product_image"]["size"] <= 5000000) {
                // Allow certain file formats
                if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg" || $imageFileType == "gif") {
                    if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
                        $product_image = $target_file;
                    } else {
                        $error_message = "Sorry, there was an error uploading your file.";
                    }
                } else {
                    $error_message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
                }
            } else {
                $error_message = "Sorry, your file is too large.";
            }
        } else {
            $error_message = "File is not an image.";
        }
    }
    
    // If no errors, insert product into database
    if (empty($error_message)) {
        $sql = "INSERT INTO products (product_name, product_price, product_description, product_category, product_brand, product_image, product_stock) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $sql);
        
        // FIX: Correct the parameter binding - 7 parameters with types "sdssssi"
        mysqli_stmt_bind_param($stmt, "sdssssi", 
            $product_name,    // s - string
            $product_price,   // d - double
            $product_description, // s - string
            $product_category,    // s - string
            $product_brand,       // s - string
            $product_image,       // s - string
            $product_stock        // i - integer
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $product_added = true;
            // Redirect to avoid form resubmission on refresh
            header("Location: adminPage.php?section=products&message=" . urlencode("Product added successfully!"));
            exit();
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Product</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Reuse styles from adminPage.php */
        .admin-panel {
            padding: 20px;
        }
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .admin-section {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        .message {
            padding: 10px;
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
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 100px;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            display: none;
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
            <div class="user-info">
                <img src="<?php echo htmlspecialchars($_SESSION['avatar']); ?>" alt="Avatar" class="user-avatar">
                <div>
                    <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></p>
                    <a href="logout.php" class="btn">Logout</a>
                </div>
            </div>
        </div>
        
        <div class="admin-panel">
            <div class="admin-header">
                <h1>Add New Product</h1>
                <a href="adminPage.php?section=products" class="btn">Back to Products</a>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="message message-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-section">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="product_name">Product Name:</label>
                        <input type="text" id="product_name" name="product_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_price">Price:</label>
                        <input type="number" id="product_price" name="product_price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_description">Description:</label>
                        <textarea id="product_description" name="product_description"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_category">Category:</label>
                        <select id="product_category" name="product_category" required>
                            <option value="">Select Category</option>
                            <option value="fridge">Fridge</option>
                            <option value="washing machine">Washing Machine</option>
                            <option value="air condition">Air Condition</option>
                            <option value="tv">TV</option>
                            <option value="microwave">Microwave</option>
                            <option value="air fryer">Air Fryer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_brand">Brand:</label>
                        <select id="product_brand" name="product_brand" required>
                            <option value="">Select Brand</option>
                            <option value="toshiba">Toshiba</option>
                            <option value="casper">Casper</option>
                            <option value="panasonic">Panasonic</option>
                            <option value="lg">LG</option>
                            <option value="sharp">Sharp</option>
                            <option value="sunhouse">Sunhouse</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_stock">Stock Quantity:</label>
                        <input type="number" id="product_stock" name="product_stock" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image">Product Image:</label>
                        <input type="file" id="product_image" name="product_image" accept="image/*" onchange="previewImage(event)">
                        <img id="preview" class="preview-image" src="#" alt="Preview">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Add Product</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(event) {
            var preview = document.getElementById('preview');
            preview.style.display = 'block';
            preview.src = URL.createObjectURL(event.target.files[0]);
        }
    </script>
</body>
</html>
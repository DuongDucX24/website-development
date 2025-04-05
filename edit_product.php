<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    // Not logged in or not admin, redirect to login page
    header("Location: loginForm.php");
    exit();
}

$error_message = '';
$product = null;

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: adminPage.php?section=products&message=" . urlencode("Invalid product ID"));
    exit();
}

$product_id = $_GET['id'];
require 'connect.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $product_name = trim(mysqli_real_escape_string($conn, $_POST['product_name']));
    $product_price = floatval($_POST['product_price']);
    $product_description = trim(mysqli_real_escape_string($conn, $_POST['product_description']));
    $product_category = trim(mysqli_real_escape_string($conn, $_POST['product_category']));
    $product_brand = trim(mysqli_real_escape_string($conn, $_POST['product_brand']));
    $product_stock = intval($_POST['product_stock']);
    $current_image = $_POST['current_image'];
    
    // Handle image upload if a new image is provided
    $product_image = $current_image; // Default to current image
    
    if (isset($_FILES["product_image"]) && $_FILES["product_image"]["error"] == 0) {
        $target_dir = "images/";
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
    
    // If no errors, update product in database
    if (empty($error_message)) {
        $sql = "UPDATE products SET 
                product_name = ?, 
                product_price = ?, 
                product_description = ?, 
                product_category = ?, 
                product_brand = ?, 
                product_image = ?,
                product_stock = ?
                WHERE product_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sdssssis", $product_name, $product_price, $product_description, $product_category, $product_brand, $product_image, $product_stock, $product_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Redirect to avoid form resubmission on refresh
            header("Location: adminPage.php?section=products&message=" . urlencode("Product updated successfully!"));
            exit();
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    // Fetch product data
    $sql = "SELECT * FROM products WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $product = mysqli_fetch_assoc($result);
    } else {
        header("Location: adminPage.php?section=products&message=" . urlencode("Product not found"));
        exit();
    }
    
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
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
        .current-image {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
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
                <h1>Edit Product</h1>
                <a href="adminPage.php?section=products" class="btn">Back to Products</a>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="message message-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="admin-section">
                <?php if ($product): ?>
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="product_name">Product Name:</label>
                        <input type="text" id="product_name" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_price">Price:</label>
                        <input type="number" id="product_price" name="product_price" step="0.01" min="0" value="<?php echo htmlspecialchars($product['product_price']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_description">Description:</label>
                        <textarea id="product_description" name="product_description"><?php echo htmlspecialchars($product['product_description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_category">Category:</label>
                        <select id="product_category" name="product_category" required>
                            <option value="">Select Category</option>
                            <option value="fridge" <?php if ($product['product_category'] == 'fridge') echo 'selected'; ?>>Fridge</option>
                            <option value="washing machine" <?php if ($product['product_category'] == 'washing machine') echo 'selected'; ?>>Washing Machine</option>
                            <option value="air condition" <?php if ($product['product_category'] == 'air condition') echo 'selected'; ?>>Air Condition</option>
                            <option value="tv" <?php if ($product['product_category'] == 'tv') echo 'selected'; ?>>TV</option>
                            <option value="microwave" <?php if ($product['product_category'] == 'microwave') echo 'selected'; ?>>Microwave</option>
                            <option value="air fryer" <?php if ($product['product_category'] == 'air fryer') echo 'selected'; ?>>Air Fryer</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_brand">Brand:</label>
                        <select id="product_brand" name="product_brand" required>
                            <option value="">Select Brand</option>
                            <option value="toshiba" <?php if ($product['product_brand'] == 'toshiba') echo 'selected'; ?>>Toshiba</option>
                            <option value="casper" <?php if ($product['product_brand'] == 'casper') echo 'selected'; ?>>Casper</option>
                            <option value="panasonic" <?php if ($product['product_brand'] == 'panasonic') echo 'selected'; ?>>Panasonic</option>
                            <option value="lg" <?php if ($product['product_brand'] == 'lg') echo 'selected'; ?>>LG</option>
                            <option value="sharp" <?php if ($product['product_brand'] == 'sharp') echo 'selected'; ?>>Sharp</option>
                            <option value="sunhouse" <?php if ($product['product_brand'] == 'sunhouse') echo 'selected'; ?>>Sunhouse</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_stock">Stock Quantity:</label>
                        <input type="number" id="product_stock" name="product_stock" min="0" value="<?php echo htmlspecialchars($product['product_stock']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Image:</label>
                        <div>
                            <img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="Product Image" class="current-image">
                            <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['product_image']); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_image">Change Image (optional):</label>
                        <input type="file" id="product_image" name="product_image" accept="image/*" onchange="previewImage(event)">
                        <img id="preview" class="preview-image" src="#" alt="Preview">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn">Update Product</button>
                    </div>
                </form>
                <?php else: ?>
                <p>Product not found.</p>
                <?php endif; ?>
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
<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    // Not logged in or not admin, redirect to login page
    header("Location: loginForm.php");
    exit();
}

// Handle product deletion if requested
if (isset($_GET['delete_product']) && is_numeric($_GET['delete_product'])) {
    require 'connect.php';
    $product_id = $_GET['delete_product'];
    
    $delete_sql = "DELETE FROM products WHERE product_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_sql);
    mysqli_stmt_bind_param($delete_stmt, "i", $product_id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        $delete_message = "Product deleted successfully!";
    } else {
        $delete_error = "Error deleting product: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($delete_stmt);
    mysqli_close($conn);
    
    // Redirect to avoid form resubmission on refresh
    header("Location: adminPage.php?section=products&message=" . urlencode($delete_message ?? $delete_error));
    exit();
}

// Determine which section to display
$section = $_GET['section'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Admin panel styles */
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
        .admin-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .admin-tabs a {
            padding: 10px 15px;
            text-decoration: none;
            color: #333;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        .admin-tabs a.active {
            background-color: #007bff;
            color: white;
        }
        .admin-tabs a:hover:not(.active) {
            background-color: #f0f0f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .user-info {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #007bff;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            margin-right: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #bd2130;
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
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
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
        select,
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .stat-card {
            flex: 1;
            min-width: 200px;
            margin-right: 10px;
            margin-bottom: 10px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card:last-child {
            margin-right: 0;
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
                <h1>Admin Dashboard</h1>
                <a href="ecommerce.php" class="btn">Back to Store</a>
            </div>
            
            <!-- Admin navigation tabs -->
            <div class="admin-tabs">
                <a href="adminPage.php?section=dashboard" class="<?php echo $section === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
                <a href="adminPage.php?section=users" class="<?php echo $section === 'users' ? 'active' : ''; ?>">Users</a>
                <a href="adminPage.php?section=products" class="<?php echo $section === 'products' ? 'active' : ''; ?>">Products</a>
                <a href="adminPage.php?section=orders" class="<?php echo $section === 'orders' ? 'active' : ''; ?>">Orders</a>
            </div>
            
            <?php if (isset($_GET['message'])): ?>
                <div class="message <?php echo strpos($_GET['message'], 'Error') !== false ? 'message-error' : 'message-success'; ?>">
                    <?php echo htmlspecialchars($_GET['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($section === 'dashboard'): ?>
                <!-- Dashboard Section -->
                <div class="admin-section">
                    <h2>System Overview</h2>
                    <div class="stats-container">
                        <div class="stat-card">
                            <h3>Total Users</h3>
                            <p>
                                <?php
                                require 'connect.php';
                                $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
                                $data = mysqli_fetch_assoc($result);
                                echo $data['total'];
                                mysqli_close($conn);
                                ?>
                            </p>
                        </div>
                        <div class="stat-card">
                            <h3>Admins</h3>
                            <p>
                                <?php
                                require 'connect.php';
                                $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 1");
                                $data = mysqli_fetch_assoc($result);
                                echo $data['total'];
                                mysqli_close($conn);
                                ?>
                            </p>
                        </div>
                        <div class="stat-card">
                            <h3>Regular Users</h3>
                            <p>
                                <?php
                                require 'connect.php';
                                $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role = 0");
                                $data = mysqli_fetch_assoc($result);
                                echo $data['total'];
                                mysqli_close($conn);
                                ?>
                            </p>
                        </div>
                        <div class="stat-card">
                            <h3>Total Products</h3>
                            <p>
                                <?php
                                require 'connect.php';
                                $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM products");
                                $data = mysqli_fetch_assoc($result);
                                echo $data['total'] ?? 0;
                            ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($section === 'users'): ?>
                <!-- User Management Section -->
                <div class="admin-section">
                    <h2>User Management</h2>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                        
                        <?php
                        // Connect to database
                        require 'connect.php';
                        
                        // Get all users
                        $sql = "SELECT user_id, user_name, role FROM users";
                        $result = mysqli_query($conn, $sql);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row["user_id"] . "</td>";
                                echo "<td>" . htmlspecialchars($row["user_name"]) . "</td>";
                                echo "<td>" . ($row["role"] == 1 ? "Admin" : "User") . "</td>";
                                echo "<td>
                                    <a href='edit_user.php?id=" . $row["user_id"] . "'>Edit</a> | 
                                    <a href='delete_user.php?id=" . $row["user_id"] . "' onclick='return confirm(\"Are you sure?\")'>Delete</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No users found</td></tr>";
                        }
                        
                        mysqli_close($conn);
                        ?>
                    </table>
                    <p><a href="add_user.php" class="btn">Add New User</a></p>
                </div>
            <?php elseif ($section === 'products'): ?>
                <!-- Product Management Section -->
                <div class="admin-section">
                    <h2>Product Management</h2>
                    <p><a href="add_product.php" class="btn">Add New Product</a></p>
                    
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Category</th>
                            <th>Brand</th>
                            <th>Stock</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                        
                        <?php
                        // Connect to database
                        require 'connect.php';
                        
                        // Get all products
                        $sql = "SELECT product_id, product_name, product_price, product_category, product_brand, product_stock, product_image FROM products ORDER BY product_id DESC";
                        $result = mysqli_query($conn, $sql);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row["product_id"] . "</td>";
                                echo "<td>" . htmlspecialchars($row["product_name"]) . "</td>";
                                echo "<td>$" . number_format($row["product_price"], 0) . "</td>";
                                echo "<td>" . htmlspecialchars($row["product_category"]) . "</td>";
                                echo "<td>" . htmlspecialchars($row["product_brand"]) . "</td>";
                                echo "<td>" . $row["product_stock"] . "</td>";
                                echo "<td><img src='" . htmlspecialchars($row["product_image"]) . "' alt='Product Image' class='product-image'></td>";
                                echo "<td>
                                    <a href='edit_product.php?id=" . $row["product_id"] . "' class='btn'>Edit</a> 
                                    <a href='adminPage.php?delete_product=" . $row["product_id"] . "' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete this product?\")'>Delete</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='8'>No products found</td></tr>";
                        }
                        
                        mysqli_close($conn);
                        ?>
                    </table>
                </div>
            <?php elseif ($section === 'orders'): ?>
                <!-- Order Management Section -->
                <div class="admin-section">
                    <h2>Order Management</h2>
                    <table>
                        <tr>
                            <th>Order ID</th>
                            <th>User</th>
                            <th>Total Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        
                        <?php
                        // Connect to database
                        require 'connect.php';
                        
                        // Get all orders with user information
                        $sql = "SELECT o.order_id, o.user_id, o.total_amount, o.status, o.created_at, u.user_name 
                               FROM orders o
                               JOIN users u ON o.user_id = u.user_id
                               ORDER BY o.created_at DESC";
                        $result = mysqli_query($conn, $sql);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $row["order_id"] . "</td>";
                                echo "<td>" . htmlspecialchars($row["user_name"]) . "</td>";
                                echo "<td>" . number_format($row["total_amount"]) . " VND</td>";
                                echo "<td>" . date("Y-m-d H:i", strtotime($row["created_at"])) . "</td>";
                                echo "<td>" . htmlspecialchars($row["status"]) . "</td>";
                                echo "<td>
                                    <a href='view_order.php?id=" . $row["order_id"] . "' class='btn'>View</a> 
                                    <a href='update_order.php?id=" . $row["order_id"] . "' class='btn'>Update</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6'>No orders found</td></tr>";
                        }
                        
                        mysqli_close($conn);
                        ?>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
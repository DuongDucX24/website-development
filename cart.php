<?php
session_start();
require 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: loginForm.php?redirect=cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle add to cart via GET (AJAX calls)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['add']) && is_numeric($_GET['add'])) {
    $product_id = intval($_GET['add']);
    $quantity = isset($_GET['qty']) ? max(1, intval($_GET['qty'])) : 1;
    
    // Check if product exists and has enough stock
    $product_query = "SELECT product_stock FROM products WHERE product_id = $product_id";
    $product_result = mysqli_query($conn, $product_query);
    
    if ($product = mysqli_fetch_assoc($product_result)) {
        if ($product['product_stock'] >= $quantity) {
            // Check if product already in cart
            $cart_check = mysqli_query($conn, "SELECT cart_id, quantity FROM cart WHERE user_id = $user_id AND product_id = $product_id");
            
            if (mysqli_num_rows($cart_check) > 0) {
                // Update quantity
                $cart_item = mysqli_fetch_assoc($cart_check);
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                // Make sure we don't exceed stock
                if ($new_quantity > $product['product_stock']) {
                    $new_quantity = $product['product_stock'];
                }
                
                mysqli_query($conn, "UPDATE cart SET quantity = $new_quantity WHERE cart_id = {$cart_item['cart_id']}");
            } else {
                // Add new item to cart
                mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity) VALUES ($user_id, $product_id, $quantity)");
            }
            
            echo "Product added to cart";
        } else {
            echo "Not enough stock available";
        }
    } else {
        echo "Product not found";
    }
    
    // If it's an AJAX request, stop here
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        exit;
    }
}

// Handle cart updates (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update quantities
    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $cart_id => $quantity) {
            $cart_id = intval($cart_id);
            $quantity = max(1, intval($quantity));
            
            // Check stock limit
            $stock_check = mysqli_query($conn, "SELECT p.product_stock FROM cart c 
                                               JOIN products p ON c.product_id = p.product_id 
                                               WHERE c.cart_id = $cart_id AND c.user_id = $user_id");
            
            if ($product = mysqli_fetch_assoc($stock_check)) {
                if ($quantity > $product['product_stock']) {
                    $quantity = $product['product_stock'];
                }
                
                mysqli_query($conn, "UPDATE cart SET quantity = $quantity WHERE cart_id = $cart_id AND user_id = $user_id");
            }
        }
        
        $message = "Cart updated successfully";
    }
    
    // Remove item
    if (isset($_POST['remove_item'])) {
        $cart_id = intval($_POST['remove_item']);
        mysqli_query($conn, "DELETE FROM cart WHERE cart_id = $cart_id AND user_id = $user_id");
        $message = "Item removed from cart";
    }
    
    // Clear cart
    if (isset($_POST['clear_cart'])) {
        mysqli_query($conn, "DELETE FROM cart WHERE user_id = $user_id");
        $message = "Cart has been emptied";
    }
}

// Get cart items
$cart_sql = "SELECT c.cart_id, c.quantity, p.product_id, p.product_name, p.product_price, 
            p.product_image, p.product_stock, p.product_brand
            FROM cart c 
            JOIN products p ON c.product_id = p.product_id 
            WHERE c.user_id = $user_id";
$cart_result = mysqli_query($conn, $cart_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - E-Sale</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Cart page styles */
        .cart-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .cart-table th {
            text-align: left;
            padding: 12px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
            vertical-align: middle;
        }
        
        .cart-product {
            display: flex;
            align-items: center;
        }
        
        .cart-product img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 4px;
        }
        
        .product-details h4 {
            margin: 0 0 5px 0;
        }
        
        .product-details .brand {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .quantity-input {
            width: 60px;
            padding: 8px;
            text-align: center;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .cart-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .summary-total {
            border-top: 1px solid #dee2e6;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
            font-size: 1.1rem;
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
            font-size: 0.9rem;
        }
        
        .btn-primary {
            background: #3498db;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #2ecc71;
        }
        
        .btn-success:hover {
            background: #27ae60;
        }
        
        .btn-danger {
            background: #e74c3c;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-icon {
            display: flex;
            align-items: center;
        }
        
        .btn-icon i {
            margin-right: 8px;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .empty-cart {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-cart i {
            font-size: 50px;
            color: #6c757d;
            margin-bottom: 15px;
        }
        
        .stock-warning {
            display: block;
            color: #e74c3c;
            font-size: 0.8rem;
            margin-top: 5px;
        }
        
        .remove-btn {
            color: #e74c3c;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .cart-product {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .cart-product img {
                margin-bottom: 10px;
                margin-right: 0;
            }
            
            .cart-table td, .cart-table th {
                padding: 8px;
            }
        }
        
        /* User avatar styles */
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
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                        <a href="adminPage.php" class="admin-link">Admin Dashboard</a>
                        <?php endif; ?>
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
                <li>
                    <a href="cart.php">
                        <i class="fas fa-shopping-cart"></i>
                        Cart
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="cart-container">
            <div class="cart-header">
                <h1>Your Shopping Cart</h1>
                <a href="ecommerce.php" class="btn btn-primary btn-icon">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
            
            <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if (mysqli_num_rows($cart_result) > 0): ?>
                <form method="post" action="cart.php">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subtotal = 0;
                            while ($item = mysqli_fetch_assoc($cart_result)): 
                                $item_total = $item['product_price'] * $item['quantity'];
                                $subtotal += $item_total;
                            ?>
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        <img src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                        <div class="product-details">
                                            <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                            <span class="brand"><?php echo htmlspecialchars($item['product_brand']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['product_price']); ?> VND</td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['product_stock']; ?>" class="quantity-input">
                                    <?php if ($item['quantity'] > $item['product_stock']): ?>
                                        <span class="stock-warning">Only <?php echo $item['product_stock']; ?> items available</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($item_total); ?> VND</td>
                                <td>
                                    <button type="submit" name="remove_item" value="<?php echo $item['cart_id']; ?>" class="remove-btn" title="Remove item">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div class="cart-summary">
                        <div class="summary-row">
                            <span>Subtotal:</span>
                            <span><?php echo number_format($subtotal); ?> VND</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span>Total:</span>
                            <span><?php echo number_format($subtotal); ?> VND</span>
                        </div>
                    </div>
                    
                    <div class="cart-actions">
                        <div>
                            <button type="submit" name="update_cart" class="btn btn-primary btn-icon">
                                <i class="fas fa-sync-alt"></i> Update Cart
                            </button>
                            <button type="submit" name="clear_cart" class="btn btn-danger btn-icon" onclick="return confirm('Are you sure you want to empty your cart?')">
                                <i class="fas fa-trash"></i> Empty Cart
                            </button>
                        </div>
                        <a href="checkout.php" class="btn btn-success btn-icon">
                            <i class="fas fa-shopping-bag"></i> Proceed to Checkout
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any products to your cart yet.</p>
                    <a href="ecommerce.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 E-Sale Electronic Store. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
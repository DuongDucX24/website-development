<?php
session_start();
require 'connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: loginForm.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = $_GET['id'];

// Get order details
$order_query = "SELECT * FROM orders WHERE order_id = $order_id AND user_id = $user_id";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: orders.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, p.product_name, p.product_image 
                FROM order_items oi JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - E-Sale</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .order-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .order-details {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .order-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-left: 10px;
        }
        .status-pending { background: #ffeeba; color: #856404; }
        .status-processing { background: #b8daff; color: #004085; }
        .status-shipped { background: #c3e6cb; color: #155724; }
        .status-delivered { background: #d1ecf1; color: #0c5460; }
        .status-cancelled { background: #f5c6cb; color: #721c24; }
        .order-items {
            margin-top: 20px;
        }
        .item {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            margin-right: 10px;
        }
        .btn {
            display: inline-block;
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo">
                <a href="ecommerce.php"><img id="logo" src="images/btec.jpg" alt="Logo"></a>
            </div>
        </div>
        
        <div class="menu">
            <ul>
                <li><a href="ecommerce.php"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="orders.php"><i class="fas fa-list"></i> My Orders</a></li>
            </ul>
        </div>
        
        <div class="order-container">
            <h1>
                Order #<?php echo $order_id; ?>
                <span class="order-status status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
            </h1>
            
            <div class="order-details">
                <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                <p><strong>Payment Method:</strong> <?php echo $order['payment_method'] == 'cod' ? 'Cash on Delivery' : 'Bank Transfer'; ?></p>
                <p><strong>Shipping Address:</strong> <?php echo $order['shipping_address']; ?></p>
                
                <div class="order-items">
                    <h2>Items</h2>
                    <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                        <div class="item">
                            <img src="<?php echo $item['product_image']; ?>" alt="<?php echo $item['product_name']; ?>">
                            <div>
                                <div><?php echo $item['product_name']; ?> x <?php echo $item['quantity']; ?></div>
                                <div><?php echo number_format($item['price_per_unit'] * $item['quantity']); ?> VND</div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div style="margin-top: 15px; text-align: right; font-weight: bold;">
                        Total: <?php echo number_format($order['total_amount']); ?> VND
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="orders.php" class="btn">Back to My Orders</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
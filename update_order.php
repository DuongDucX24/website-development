<?php
session_start();
require 'connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: loginForm.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: adminPage.php?section=orders");
    exit();
}

$order_id = $_GET['id'];
$message = '';

// Get order details
$order_query = "SELECT o.*, u.user_name 
                FROM orders o JOIN users u ON o.user_id = u.user_id 
                WHERE o.order_id = $order_id";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: adminPage.php?section=orders");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    $update_query = "UPDATE orders SET status = '$status', admin_notes = '$notes' WHERE order_id = $order_id";
    
    if (mysqli_query($conn, $update_query)) {
        $message = "Order status updated successfully";
    } else {
        $message = "Error updating order: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Order #<?php echo $order_id; ?> - Admin</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .admin-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .form-section {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo">
                <a href="adminPage.php"><img id="logo" src="images/btec.jpg" alt="Logo"></a>
            </div>
        </div>
        
        <div class="admin-container">
            <h1>Update Order #<?php echo $order_id; ?></h1>
            
            <?php if (!empty($message)): ?>
                <div class="message"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="form-section">
                <p><strong>Customer:</strong> <?php echo $order['user_name']; ?></p>
                <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></p>
                <p><strong>Total Amount:</strong> <?php echo number_format($order['total_amount']); ?> VND</p>
                
                <form method="post">
                    <div class="form-group">
                        <label for="status">Order Status:</label>
                        <select id="status" name="status">
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Admin Notes:</label>
                        <textarea id="notes" name="notes" rows="3"><?php echo $order['admin_notes'] ?? ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn">Update Order</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
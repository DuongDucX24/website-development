<?php
session_start();
require 'connect.php';

// Get search query
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - E-Sale</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Search results specific styles */
        .search-header {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-header a {
            color: #4285F4;
            text-decoration: none;
        }
        
        .search-header a:hover {
            text-decoration: underline;
        }
        
        .no-results {
            padding: 50px;
            text-align: center;
            width: 100%;
            color: #777;
        }
        
        /* These styles will be used until the page loads the global CSS */
        /* They will be overridden by the global CSS but ensure consistent appearance during loading */
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
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
            <div class="form-search">
                <form id="searchForm" action="search-results.php" method="get">
                    <input type="text" name="query" id="searchInput" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>" required>
                    <input type="submit" value="Search">
                </form>
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
                        <a href="profile.php">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <a href="orders.php">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                        <a href="adminPage.php" class="admin-link">
                            <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                        </a>
                        <?php endif; ?>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
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
                        <?php
                        // Display cart count if user is logged in
                        if(isset($_SESSION['user_id'])) {
                            require 'connect.php';
                            $user_id = $_SESSION['user_id'];
                            $cart_count_query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
                            $count_result = mysqli_query($conn, $cart_count_query);
                            $count_data = mysqli_fetch_assoc($count_result);
                            if($count_data['total'] > 0) {
                                echo '<span class="cart-count">' . $count_data['total'] . '</span>';
                            }
                        }
                        ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="content">
            <div class="search-header">
                <h2>Search Results for: "<?php echo htmlspecialchars($search_query); ?>"</h2>
                <a href="ecommerce.php"><i class="fas fa-arrow-left"></i> Back to all products</a>
            </div>
            
            <div class="right">
                <div class="product">
                    <?php
                    if (!empty($search_query)) {
                        // Search in product name, description, category and brand
                        $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
                        $sql = "SELECT * FROM products 
                                WHERE product_name LIKE ? 
                                OR product_description LIKE ? 
                                OR product_category LIKE ? 
                                OR product_brand LIKE ?";
                        
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "ssss", $search_term, $search_term, $search_term, $search_term);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        
                        if (mysqli_num_rows($result) > 0) {
                            while($row = mysqli_fetch_assoc($result)) {
                                ?>
                                <div class="single-product">
                                    <img src="<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                        <div class="price"><?php echo number_format($row['product_price']); ?> VND</div>
                                        <button class="add-to-cart-btn" onclick="addToCart(<?php echo $row['product_id']; ?>)">
                                            <i class="fas fa-shopping-cart"></i>
                                            Add to Cart
                                        </button>
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="no-results">
                                    <h3>No products found matching your search.</h3>
                                    <p>Try different keywords or browse our categories.</p>
                                  </div>';
                        }
                    } else {
                        echo '<div class="no-results">
                                <h3>Please enter a search term.</h3>
                              </div>';
                    }
                    
                    // Close the connection
                    mysqli_close($conn);
                    ?>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; 2025 E-Sale Electronic Store. All rights reserved.</p>
        </div>
    </div>
    
    <script>
        // Function to add product to cart (MATCHING ecommerce.php)
        function addToCart(productId) {
            // Check if user is logged in by checking for session
            <?php if(!isset($_SESSION['user_id'])): ?>
                window.location.href = 'loginForm.php?redirect=' + encodeURIComponent(window.location.href);
                return;
            <?php endif; ?>
            
            // Visual feedback - button animation
            const button = event.currentTarget;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            button.disabled = true;
            
            // Send AJAX request to add item to cart
            fetch('cart.php?add=' + productId + '&qty=1')
                .then(response => response.text())
                .then(data => {
                    // Update button appearance after successful addition
                    setTimeout(() => {
                        button.innerHTML = '<i class="fas fa-check"></i> Added to Cart';
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                            button.disabled = false;
                        }, 2000);
                    }, 500);
                    
                    // Show notification
                    showNotification('Product added to cart successfully!');
                })
                .catch(error => {
                    console.error('Error adding product to cart:', error);
                    button.innerHTML = '<i class="fas fa-exclamation-circle"></i> Error';
                    
                    setTimeout(() => {
                        button.innerHTML = '<i class="fas fa-shopping-cart"></i> Add to Cart';
                        button.disabled = false;
                    }, 2000);
                });
        }
        
        function showNotification(message) {
            // Create notification element if it doesn't exist
            let notification = document.getElementById('cart-notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'cart-notification';
                notification.style.position = 'fixed';
                notification.style.top = '20px';
                notification.style.right = '20px';
                notification.style.backgroundColor = '#2ecc71';
                notification.style.color = 'white';
                notification.style.padding = '12px 20px';
                notification.style.borderRadius = '4px';
                notification.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
                notification.style.zIndex = '1000';
                notification.style.transition = 'all 0.3s ease';
                notification.style.transform = 'translateY(-20px)';
                notification.style.opacity = '0';
                document.body.appendChild(notification);
            }
            
            // Set message and show notification
            notification.textContent = message;
            notification.style.transform = 'translateY(0)';
            notification.style.opacity = '1';
            
            // Hide notification after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateY(-20px)';
                notification.style.opacity = '0';
            }, 3000);
        }
    </script>
</body>
</html>
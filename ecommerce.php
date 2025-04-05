<?php
session_start(); // Add this at the top to access session data
require 'connect.php'; // Include database connection
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Sale - Electronic Store</title>
    <link rel="stylesheet" type="text/css" href="style.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 5px;
            vertical-align: middle;
        }
        
        .menu .dropdown {
            display: none;
            position: absolute;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
        }
        
        .menu li:hover .dropdown {
            display: block;
        }
        
        .menu .dropdown a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }
        
        .menu .dropdown a:hover {
            background-color: #f1f1f1;
        }
        
        /* Product styles */
        .product {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        
        .single-product {
            width: 30%;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        
        .single-product:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .single-product img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            margin-bottom: 10px;
        }
        
        .single-product h3 {
            margin: 10px 0;
            font-size: 16px;
            text-transform: capitalize;
        }
        
        .single-product p {
            color: #e74c3c;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .single-product button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .single-product button:hover {
            background: #2980b9;
        }
        
        /* Category links */
        .category a, .brand a {
            text-decoration: none;
            color: #333;
            display: block;
            padding: 5px;
            transition: background 0.3s;
        }
        
        .category a:hover, .brand a:hover {
            background: #f1f1f1;
            padding-left: 10px;
        }
        
        /* Filter active states */
        .active-filter {
            background: #e5f7ff;
            font-weight: bold;
        }
        
        /* No products message */
        .no-products {
            padding: 20px;
            text-align: center;
            width: 100%;
            color: #777;
        }
        
        /* Cart Count Badge */
        .cart-count {
            display: inline-block;
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 11px;
            line-height: 18px;
            text-align: center;
            margin-left: 5px;
        }
        
        /* Login/Admin button styles */
        .login-item a {
            color: #fff;
            background-color: #4285F4;
            padding: 8px 15px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .login-item a:hover {
            background-color: #2d6fd2;
        }
        
        .admin-link {
            color: #fff !important;
            background-color: #e74c3c !important;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .admin-link:hover {
            background-color: #c0392b !important;
        }
        
        /* User dropdown enhancements */
        .menu .dropdown {
            min-width: 180px;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            z-index: 100;
        }
        
        .menu .dropdown a {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .menu .dropdown a i {
            margin-right: 10px;
            width: 16px;
            text-align: center;
        }
        
        .menu .dropdown a:hover {
            background-color: #f5f5f5;
            transform: translateX(3px);
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo">
                <img id="logo" src="images/btec.jpg" alt="">
            </div>
            <div class="form-search">
                <form id="searchForm" action="search-results.php" method="get">
                    <input type="text" name="query" id="searchInput" placeholder="Search products..." required>
                    <input type="submit" value="Search">
                </form>
                
                <script>
                    document.getElementById('searchForm').addEventListener('submit', function(event) {
                        event.preventDefault();
                        const searchQuery = document.getElementById('searchInput').value.trim().toLowerCase();
                        
                        if (searchQuery === '') {
                            alert('Please enter a search term');
                            return;
                        }
                        
                        // Add form action to submit to search page
                        this.submit();
                    });
                </script>
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
                    <!-- User is logged in -->
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
                    <!-- User is not logged in -->
                    <li class="login-item">
                        <a href="loginForm.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">
                            <i class="fas fa-sign-in-alt"></i>
                            Login / Register
                        </a>
                    </li>
                <?php endif; ?>
                <!-- Add Cart Link Here -->
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
            <div class="left">
                <?php
                // Get the selected category and brand (if any)
                $selectedCategory = isset($_GET['category']) ? $_GET['category'] : '';
                $selectedBrand = isset($_GET['brand']) ? $_GET['brand'] : '';
                
                // Get all unique categories from database
                $categoryQuery = "SELECT DISTINCT product_category FROM products ORDER BY product_category";
                $categoryResult = mysqli_query($conn, $categoryQuery);
                
                // Get all unique brands from database
                $brandQuery = "SELECT DISTINCT product_brand FROM products ORDER BY product_brand";
                $brandResult = mysqli_query($conn, $brandQuery);
                ?>
                
                <div class="category">
                    <ul type="none">
                        <li>Category</li>
                        <li><a href="ecommerce.php" <?php echo empty($selectedCategory) && empty($selectedBrand) ? 'class="active-filter"' : ''; ?>>All products</a></li>
                        <?php while ($category = mysqli_fetch_assoc($categoryResult)): ?>
                            <li>
                                <a href="ecommerce.php?category=<?php echo urlencode($category['product_category']); ?>" 
                                   <?php echo $category['product_category'] == $selectedCategory ? 'class="active-filter"' : ''; ?>>
                                    <?php echo htmlspecialchars($category['product_category']); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                <div class="brand">
                    <ul type="none">
                        <li>Brand</li>
                        <?php while ($brand = mysqli_fetch_assoc($brandResult)): ?>
                            <li>
                                <a href="ecommerce.php?brand=<?php echo urlencode($brand['product_brand']); ?>"
                                   <?php echo $brand['product_brand'] == $selectedBrand ? 'class="active-filter"' : ''; ?>>
                                    <?php echo htmlspecialchars($brand['product_brand']); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            <div class="right">
                <div class="product">
                    <?php
                    // Build the SQL query based on filters
                    $sql = "SELECT * FROM products WHERE 1=1";
                    
                    if (!empty($selectedCategory)) {
                        $sql .= " AND product_category = '" . mysqli_real_escape_string($conn, $selectedCategory) . "'";
                    }
                    
                    if (!empty($selectedBrand)) {
                        $sql .= " AND product_brand = '" . mysqli_real_escape_string($conn, $selectedBrand) . "'";
                    }
                    
                    $result = mysqli_query($conn, $sql);
                    
                    if (mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                            ?>
                            <div class="single-product">
                                <img src="<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">
                                <h3><?php echo htmlspecialchars($row['product_name']); ?></h3>
                                <p>price: <?php echo number_format($row['product_price']); ?> VND</p>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $row['product_id']; ?>)">
                                    <i class="fas fa-shopping-cart"></i>
                                    Add to Cart
                                </button>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<div class="no-products">No products found matching your criteria.</div>';
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
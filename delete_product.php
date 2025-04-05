<?php
session_start();

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    // Not logged in or not admin, redirect to login page
    header("Location: loginForm.php");
    exit();
}

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: adminPage.php?section=products&message=" . urlencode("Invalid product ID"));
    exit();
}

$product_id = $_GET['id'];
require 'connect.php';

// Fetch the product image path before deletion (to delete the file later if needed)
$img_query = "SELECT product_image FROM products WHERE product_id = ?";
$img_stmt = mysqli_prepare($conn, $img_query);
mysqli_stmt_bind_param($img_stmt, "i", $product_id);
mysqli_stmt_execute($img_stmt);
mysqli_stmt_bind_result($img_stmt, $image_path);
mysqli_stmt_fetch($img_stmt);
mysqli_stmt_close($img_stmt);

// Delete the product
$delete_sql = "DELETE FROM products WHERE product_id = ?";
$delete_stmt = mysqli_prepare($conn, $delete_sql);
mysqli_stmt_bind_param($delete_stmt, "i", $product_id);

if (mysqli_stmt_execute($delete_stmt)) {
    // Delete the image file if it's not a default image and exists
    if ($image_path && $image_path != 'images/default_product.jpg' && file_exists($image_path)) {
        unlink($image_path);
    }
    $message = "Product deleted successfully!";
} else {
    $message = "Error deleting product: " . mysqli_error($conn);
}

mysqli_stmt_close($delete_stmt);
mysqli_close($conn);

// Redirect back to products page
header("Location: adminPage.php?section=products&message=" . urlencode($message));
exit();
?>
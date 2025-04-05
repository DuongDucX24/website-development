<?php
$servername = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty
$database = "e-sale"; // Your database name

// Create connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
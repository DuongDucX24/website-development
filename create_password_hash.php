<?php
// This is a utility script to generate password hashes
// Run this file directly to generate a hash for a new password

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = trim($_POST["password"]);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    echo "Password: " . htmlspecialchars($password) . "<br>";
    echo "Hash: " . $hash;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Password Hash</title>
</head>
<body>
    <h2>Generate Password Hash</h2>
    <form method="post">
        <label for="password">Password:</label>
        <input type="text" name="password" id="password">
        <button type="submit">Generate Hash</button>
    </form>
</body>
</html>
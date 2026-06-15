<?php
require_once 'config.php';

// Connect to database
$conn = new mysqli(
    $_ENV['MYSQL_HOST'] ?? 'localhost',
    $_ENV['MYSQL_USER'] ?? 'root',
    $_ENV['MYSQL_PASSWORD'] ?? '',
    $_ENV['MYSQL_DATABASE'] ?? 'murna_foundation'
);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Update password - hash it properly
$new_password = 'Murna_foundation@555';
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

UPDATE admin_users SET password = ? WHERE username = 'admin' AND password = 'password';
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $hashed_password);

if ($stmt->execute()) {
    echo "Password updated successfully. Rows affected: " . $stmt->affected_rows;
} else {
    echo "Error updating password: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

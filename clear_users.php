<?php
require_once 'config.php';

// Database connection
$conn = getDB();

if (!$conn) {
    die('Database connection failed');
}

// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Clear all records from users table
$sql = "TRUNCATE TABLE users";

if ($conn->query($sql) === TRUE) {
    echo "✓ All user records have been cleared successfully.<br>";
    echo "The users table has been truncated.<br>";
} else {
    echo "✗ Error clearing users: " . $conn->error;
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

$conn->close();
?>

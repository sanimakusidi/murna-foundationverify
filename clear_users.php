<?php
require_once 'config.php';

// Database connection
$conn = getDB();

if (!$conn) {
    die('Database connection failed');
}

// Clear all records from users table
$sql = "TRUNCATE TABLE users";

if ($conn->query($sql) === TRUE) {
    echo "✓ All user records have been cleared successfully.<br>";
    echo "The users table has been truncated.<br>";
} else {
    echo "✗ Error clearing users: " . $conn->error;
}

$conn->close();
?>

<?php
require_once 'config.php';

// Database connection
$conn = getDB();

if (!$conn) {
    die('Database connection failed');
}

// Disable foreign key checks temporarily (in case of constraints)
$conn->query("SET FOREIGN_KEY_CHECKS=0");

// Clear all records from verification_logs table
$sql = "TRUNCATE TABLE verification_logs";

if ($conn->query($sql) === TRUE) {
    echo "✓ All verification log records have been cleared successfully.<br>";
    echo "The verification_logs table has been truncated.<br>";
} else {
    echo "✗ Error clearing verification_logs: " . $conn->error;
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS=1");

$conn->close();
?>

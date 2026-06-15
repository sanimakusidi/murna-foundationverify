<?php
require_once 'config.php';

// Database connection
$conn = getDB();

if (!$conn) {
    die('Database connection failed');
}

// Clear all records from transactions table
$sql = "TRUNCATE TABLE transactions";

if ($conn->query($sql) === TRUE) {
    echo "✓ All transaction records have been cleared successfully.<br>";
    echo "The transactions table has been truncated.<br>";
} else {
    echo "✗ Error clearing transactions: " . $conn->error;
}

$conn->close();
?>

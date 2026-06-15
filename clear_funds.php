<?php
require_once 'config.php';

// Database connection
$conn = getDB();

if (!$conn) {
    die('Database connection failed');
}

// Reset all account balances to 0
$sql = "UPDATE accounts SET balance = 0";

if ($conn->query($sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "✓ All account balances have been cleared successfully.<br>";
    echo "Accounts updated: " . $affected . "<br>";
    echo "Total funds on dashboard will now show 0.<br>";
} else {
    echo "✗ Error clearing funds: " . $conn->error;
}

$conn->close();
?>

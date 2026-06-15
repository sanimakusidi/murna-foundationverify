<?php
require_once 'config.php';

// Database connection via shared helper
$conn = getDB();

// New password to set
$new_password = 'Murna_foundation@555';

// Hash the password using bcrypt
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

// Update password for admin user
$sql = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Prepare failed: ' . $conn->error);
}

$stmt->bind_param('s', $hashed_password);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    if ($affected > 0) {
        echo "&#10003; Password updated successfully for admin user.<br>";
        echo "Rows affected: " . $affected . "<br>";
        echo "New password hash: " . substr($hashed_password, 0, 20) . "...<br>";
    } else {
        echo "&#9888; No admin user found or password already set to this value.";
    }
} else {
    echo "&#10007; Error updating password: " . $stmt->error;
}

$stmt->close();
?>

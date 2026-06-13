<?php
session_start();
define('ADMIN_PATH', dirname(__DIR__));
define('ADMIN_URL', 'http://localhost/murna-foundation/admin');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'murna_foundation');

// Admin session timeout (30 minutes)
define('ADMIN_TIMEOUT', 1800);

// Function to check admin login
function isAdminLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_last_activity'])) {
        return false;
    }
    
    // Check session timeout
    if (time() - $_SESSION['admin_last_activity'] > ADMIN_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    $_SESSION['admin_last_activity'] = time();
    return true;
}

// Function to redirect if not logged in
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Function to log admin activity
function logAdminActivity($admin_id, $action, $description = null) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $conn->prepare("INSERT INTO admin_activity_log (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $admin_id, $action, $description, $ip);
        $stmt->execute();
        $stmt->close();
        $conn->close();
    } catch (Exception $e) {
        // Silent fail for logging
    }
}

// Database connection function
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    return $conn;
}
?>
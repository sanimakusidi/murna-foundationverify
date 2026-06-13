<?php
require_once 'config.php';

// Destroy the session and logout user
if (session_status() !== PHP_SESSION_NONE) {
    session_unset(); // Free all session variables
    session_destroy(); // Destroy the session
}

// Redirect to login page with a success message
header('Location: index.php?page=login&success=logged_out');
exit();
?>

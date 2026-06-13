<?php
require_once 'config/admin_config.php';

if (isset($_SESSION['admin_id'])) {
    logAdminActivity($_SESSION['admin_id'], 'LOGOUT', 'Admin logged out');
}

session_destroy();
header('Location: login.php');
exit();
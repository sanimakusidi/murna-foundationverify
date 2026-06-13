<?php
require_once 'config/admin_config.php';
requireAdminLogin();

if (!isset($_GET['id'])) {
    header('Location: users.php');
    exit();
}

$user_id = (int)$_GET['id'];
$conn = getDBConnection();

// Get user details
$stmt = $conn->prepare("
    SELECT u.*, a.account_number, a.balance 
    FROM users u 
    LEFT JOIN accounts a ON u.id = a.user_id 
    WHERE u.id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: users.php');
    exit();
}

// Get user transactions
$transactions = $conn->prepare("
    SELECT * FROM transactions 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$transactions->bind_param("i", $user_id);
$transactions->execute();
$transactions_result = $transactions->get_result();

// Get verification logs
$verifications = $conn->prepare("
    SELECT * FROM verification_logs 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 20
");
$verifications->bind_param("i", $user_id);
$verifications->execute();
$verifications_result = $verifications->get_result();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Murna Foundation Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
              background: linear-gradient(135deg, #002D62 0%, #007A3D 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .navbar-top {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 p-0 sidebar">
                <div class="text-center py-4">
                     <div class="logo-icon">
              <img 
                src="../murna.jpg" 
                alt="Murna Logo"
                style="
                    width:150px;
                    height:150px;
                    object-fit:cover;
                    border-radius:20px;
                    padding:9px;
                    background:#fff;
                    box-shadow:0 2px 8px rgba(0,0,0,0.15);
                    border:2px solid #00A651;
                "
                >
        
        
        </div>
          <h5 class="text-white mt-2">Murna Foundation</h5>
                    <small class="text-white-50">Admin Panel</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    <a class="nav-link active" href="users.php"><i class="fas fa-users"></i> Users</a>
                    <a class="nav-link" href="verifications.php"><i class="fas fa-check-circle"></i> Verifications</a>
                    <a class="nav-link" href="transactions.php"><i class="fas fa-money-bill-wave"></i> Transactions</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-top navbar-expand-lg px-4">
                    <div class="container-fluid">
                        <span class="navbar-brand">User Details</span>
                        <div class="ms-auto">
                            <a href="users.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Users
                            </a>
                        </div>
                    </div>
                </nav>
                
                <div class="p-4">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-card">
                                <h6><i class="fas fa-user"></i> Personal Information</h6>
                                <hr>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($user['full_name'] ?? $user['org_name']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                               
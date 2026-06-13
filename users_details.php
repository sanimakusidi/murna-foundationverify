<?php
require_once 'admin_config.php';
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
                    <i class="fas fa-hand-holding-heart" style="font-size: 40px; color: white;"></i>
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
                                <p><strong>Account Type:</strong> <?php echo ucfirst($user['account_type']); ?></p>
                                <?php if($user['account_type'] == 'corporate'): ?>
                                    <p><strong>RC Number:</strong> <?php echo htmlspecialchars($user['rc_number']); ?></p>
                                    <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($user['contact_person']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-card">
                                <h6><i class="fas fa-chart-line"></i> Verification Statistics</h6>
                                <hr>
                                <p><strong>NIN Verifications:</strong> <?php echo $user['nin_verifications'] ?? 0; ?></p>
                                <p><strong>Phone Verifications:</strong> <?php echo $user['phone_verifications'] ?? 0; ?></p>
                                <p><strong>Demographic Verifications:</strong> <?php echo $user['demographic_verifications'] ?? 0; ?></p>
                                <p><strong>Total Verifications:</strong> <?php echo ($user['nin_verifications'] ?? 0) + ($user['phone_verifications'] ?? 0) + ($user['demographic_verifications'] ?? 0); ?></p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="info-card">
                                <h6><i class="fas fa-money-bill"></i> Account Information</h6>
                                <hr>
                                <p><strong>Account Number:</strong> <?php echo $user['account_number']; ?></p>
                                <p><strong>Current Balance:</strong> ₦<?php echo number_format($user['balance'] ?? 0, 2); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo $user['status']; ?>
                                    </span>
                                </p>
                                <p><strong>Registered:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="fas fa-history"></i> Recent Transactions</h6>
                                <hr>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr><th>Date</th><th>Type</th><th>Amount</th><th>Status</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php while($trans = $transactions_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, H:i', strtotime($trans['created_at'])); ?></td>
                                                <td><?php echo ucfirst($trans['type']); ?></td>
                                                <td>₦<?php echo number_format($trans['amount'], 2); ?></td>
                                                <td><span class="badge bg-<?php echo $trans['status'] == 'success' ? 'success' : 'warning'; ?>"><?php echo $trans['status']; ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="info-card">
                                <h6><i class="fas fa-check-circle"></i> Verification History</h6>
                                <hr>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr><th>Date</th><th>Type</th><th>Cost</th><th>Status</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php while($verif = $verifications_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('M d, H:i', strtotime($verif['created_at'])); ?></td>
                                                <td><?php echo strtoupper($verif['verification_type']); ?></td>
                                                <td>₦<?php echo number_format($verif['cost'], 2); ?></td>
                                                <td><span class="badge bg-<?php echo $verif['status'] == 'success' ? 'success' : 'danger'; ?>"><?php echo $verif['status']; ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
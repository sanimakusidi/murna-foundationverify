<?php
require_once 'config/admin_config.php';
requireAdminLogin();

$conn = getDBConnection();

// Get verification statistics
$stats = [];
$result = $conn->query("
    SELECT 
        verification_type,
        COUNT(*) as total,
        SUM(cost) as total_cost,
        COUNT(DISTINCT user_id) as unique_users
    FROM verification_logs 
    GROUP BY verification_type
");

while ($row = $result->fetch_assoc()) {
    $stats[$row['verification_type']] = $row;
}

// Get recent verifications
$recent = $conn->query("
    SELECT v.*, u.email, u.full_name, u.org_name 
    FROM verification_logs v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.created_at DESC 
    LIMIT 50
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifications - Murna Foundation Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
        .stat-card {
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
                    <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                    <a class="nav-link active" href="verifications.php"><i class="fas fa-check-circle"></i> Verifications</a>
                    <a class="nav-link" href="transactions.php"><i class="fas fa-money-bill-wave"></i> Transactions</a>
                    <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-0">
                <nav class="navbar navbar-top navbar-expand-lg px-4">
                    <div class="container-fluid">
                        <span class="navbar-brand">Verifications Management</span>
                        <div class="ms-auto">
                            <span class="badge bg-info"><?php echo $_SESSION['admin_role']; ?></span>
                        </div>
                    </div>
                </nav>
                
                <div class="p-4">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h6>NIN Verifications</h6>
                                <h3><?php echo number_format($stats['nin']['total'] ?? 0); ?></h3>
                                <small>Total Cost: ₦<?php echo number_format($stats['nin']['total_cost'] ?? 0, 2); ?></small>
                                <br>
                                <small>Unique Users: <?php echo $stats['nin']['unique_users'] ?? 0; ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h6>Phone Verifications</h6>
                                <h3><?php echo number_format($stats['phone']['total'] ?? 0); ?></h3>
                                <small>Total Cost: ₦<?php echo number_format($stats['phone']['total_cost'] ?? 0, 2); ?></small>
                                <br>
                                <small>Unique Users: <?php echo $stats['phone']['unique_users'] ?? 0; ?></small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <h6>Demographic Verifications</h6>
                                <h3><?php echo number_format($stats['demographic']['total'] ?? 0); ?></h3>
                                <small>Total Cost: ₦<?php echo number_format($stats['demographic']['total_cost'] ?? 0, 2); ?></small>
                                <br>
                                <small>Unique Users: <?php echo $stats['demographic']['unique_users'] ?? 0; ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Verification Logs</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="verificationsTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Query Input</th>
                                            <th>Cost</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($log = $recent->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($log['full_name'] ?? $log['org_name']); ?></td>
                                            <td><span class="badge bg-primary"><?php echo strtoupper($log['verification_type']); ?></span></td>
                                            <td><?php echo htmlspecialchars(substr($log['query_input'], 0, 50)); ?></td>
                                            <td>₦<?php echo number_format($log['cost'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $log['status'] == 'success' ? 'success' : ($log['status'] == 'failed' ? 'danger' : 'warning'); ?>">
                                                    <?php echo $log['status']; ?>
                                                </span>
                                            </td>
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
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#verificationsTable').DataTable({
                "order": [[0, "desc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>
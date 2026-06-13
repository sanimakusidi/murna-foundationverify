<?php
require_once 'config/admin_config.php';
requireAdminLogin();

$conn = getDBConnection();
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Get total users count
$total_result = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_users / $limit);

// Fetch users with their accounts and verification stats
$users_query = $conn->prepare("
    SELECT u.*, a.balance, a.account_number 
    FROM users u 
    LEFT JOIN accounts a ON u.id = a.user_id 
    ORDER BY u.created_at DESC 
    LIMIT ? OFFSET ?
");
$users_query->bind_param("ii", $limit, $offset);
$users_query->execute();
$users = $users_query->get_result();

// Handle user status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    if ($_POST['action'] === 'toggle_status') {
        $new_status = $_POST['status'] === 'active' ? 'suspended' : 'active';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $user_id);
        $stmt->execute();
        logAdminActivity($_SESSION['admin_id'], 'USER_STATUS', "Changed user $user_id status to $new_status");
        header('Location: users.php');
        exit();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Murna Foundation Admin</title>
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (same as dashboard) -->
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
                        <span class="navbar-brand">Users Management</span>
                        <div class="ms-auto">
                            <span class="badge bg-info"><?php echo $_SESSION['admin_role']; ?></span>
                        </div>
                    </div>
                </nav>
                
                <div class="p-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>All Registered Users</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="usersTable" class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name/Organization</th>
                                            <th>Email</th>
                                             <th>NIN</th>
                                            <th>Phone</th>
                                            <th>Type</th>
                                            <th>Account Balance</th>
                                            <th>Verifications (N/P/D)</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($user = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['full_name'] ?? $user['org_name']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                             <td><?php echo htmlspecialchars($user['nin_input']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                            <td><span class="badge bg-info"><?php echo $user['account_type']; ?></span></td>
                                            <td>₦<?php echo number_format($user['balance'] ?? 0, 2); ?></td>
                                            <td><?php echo ($user['nin_verifications'] ?? 0) . '/' . ($user['phone_verifications'] ?? 0) . '/' . ($user['demographic_verifications'] ?? 0); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                    <?php echo $user['status']; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                            <td>
                                               
                                                <form method="POST" style="display: inline-block;">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <button type="submit" class="btn btn-sm btn-<?php echo $user['status'] == 'active' ? 'warning' : 'success'; ?>" onclick="return confirm('Are you sure?')">
                                                        <?php echo $user['status'] == 'active' ? 'Suspend' : 'Activate'; ?>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
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
            $('#usersTable').DataTable({
                "paging": false,
                "searching": true,
                "ordering": true
            });
        });
    </script>
</body>
</html>
<?php
require_once 'config/admin_config.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter username and password';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT id, username, email, password, full_name, role, status FROM admin_users WHERE (username = ? OR email = ?) AND status = 'active'");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['admin_id'] = $row['id'];
                $_SESSION['admin_username'] = $row['username'];
                $_SESSION['admin_email'] = $row['email'];
                $_SESSION['admin_fullname'] = $row['full_name'];
                $_SESSION['admin_role'] = $row['role'];
                $_SESSION['admin_last_activity'] = time();
                
                
                
                
                // Update last login
                $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $row['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                logAdminActivity($row['id'], 'LOGIN', 'Admin logged in successfully');
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password';
            }
        } else {
            $error = 'Invalid username or password';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Murna Foundation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>

           :root {
            --green: #00A651;
            --green-light: #00C960;
            --green-dark: #007A3D;
            --blue: #002D62;
            --blue-mid: #003f8a;
            --black: #080f1a;
            --black-soft: #0d1625;
            --card: #0f1e38;
            --card-light: #162840;
            --border: rgba(255,255,255,0.07);
            --text: #c8d8f0;
            --text-muted: #6e88ab;
            --white: #ffffff;
        }
        
        body {
            background: linear-gradient(135deg, #002D62 0%, #007A3D 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h3 {
            color: #333;
            font-weight: 600;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .btn-login {
            background: linear-gradient(135deg, #002D62 0%, #007A3D 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            width: 100%;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-header">
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
             <h3>Murna Foundation Admin</h3>
             <span>NIN Verification Portal, Partner to Randa Frames, Licensed by NDPC</span>
            <p>Please login to access the dashboard</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" class="form-control" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
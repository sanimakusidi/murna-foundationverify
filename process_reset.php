<?php
require 'config.php'; // Database connection – expects $conn (MySQLi) or getDB() function

// Enable error logging (remove in production)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/process_reset_errors.log');
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation: check if passwords match
    if (empty($new_password) || empty($confirm_password)) {
        showError("Please fill in both password fields.");
        exit;
    }

    if ($new_password !== $confirm_password) {
        showError("Passwords do not match.");
        exit;
    }

    if (strlen($new_password) < 8) {
        showError("Password must be at least 8 characters long.");
        exit;
    }

    // Get database connection (assuming your config.php provides a function or variable)
    $conn = getDB(); // or use $conn = $conn; depending on your config

    // Fetch all valid (unused & not expired) reset entries
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE used = FALSE AND expires_at > NOW()");
    $stmt->execute();
    $result = $stmt->get_result();
    $valid = false;
    $resetData = null;

    while ($row = $result->fetch_assoc()) {
        if (password_verify($token, $row['token'])) {
            $valid = true;
            $resetData = $row;
            break;
        }
    }
    $stmt->close();

    if ($valid && $resetData) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update user's password
        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $updateStmt->bind_param("si", $hashed_password, $resetData['user_id']);
        $updateSuccess = $updateStmt->execute();
        $updateStmt->close();

        if ($updateSuccess) {
            // Mark the token as used
            $usedStmt = $conn->prepare("UPDATE password_resets SET used = TRUE WHERE id = ?");
            $usedStmt->bind_param("i", $resetData['id']);
            $usedStmt->execute();
            $usedStmt->close();

            // Show success GUI
            showSuccess("Your password has been successfully reset. You can now log in with your new password.");
        } else {
            showError("Something went wrong. Please try again later.");
        }
    } else {
        showError("Invalid or expired reset link. Please request a new one.");
    }
} else {
    // Not a POST request
    showError("Invalid request method.");
}

// Helper function to display a styled error page
function showError($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Reset Failed | Murna Foundation</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #e9f2f5 0%, #d4e2e8 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 1.5rem;
            }
            .message-card {
                background: white;
                max-width: 480px;
                width: 100%;
                border-radius: 28px;
                padding: 2rem;
                text-align: center;
                box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1);
            }
            .icon {
                font-size: 3.5rem;
                margin-bottom: 1rem;
            }
            h2 {
                color: #1c3a2f;
                margin-bottom: 0.75rem;
                font-size: 1.6rem;
            }
            p {
                color: #4a5b66;
                margin-bottom: 1.8rem;
                line-height: 1.5;
            }
            .btn {
                display: inline-block;
                background: #1b6b4c;
                color: white;
                padding: 0.75rem 1.8rem;
                border-radius: 40px;
                text-decoration: none;
                font-weight: 500;
                transition: background 0.2s;
            }
            .btn:hover {
                background: #13563e;
            }
        </style>
    </head>
    <body>
        <div class="message-card">
            <div class="icon">⚠️</div>
            <h2>Password Reset Failed</h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="forgot_password.php" class="btn">Request New Link</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Helper function to display a styled success page
function showSuccess($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Password Reset | Murna Foundation</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
                background: linear-gradient(135deg, #e9f2f5 0%, #d4e2e8 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 1.5rem;
            }
            .message-card {
                background: white;
                max-width: 480px;
                width: 100%;
                border-radius: 28px;
                padding: 2rem;
                text-align: center;
                box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1);
            }
            .icon {
                font-size: 3.5rem;
                margin-bottom: 1rem;
            }
            h2 {
                color: #1b6b4c;
                margin-bottom: 0.75rem;
                font-size: 1.6rem;
            }
            p {
                color: #4a5b66;
                margin-bottom: 1.8rem;
                line-height: 1.5;
            }
            .btn {
                display: inline-block;
                background: #1b6b4c;
                color: white;
                padding: 0.75rem 1.8rem;
                border-radius: 40px;
                text-decoration: none;
                font-weight: 500;
                transition: background 0.2s;
            }
            .btn:hover {
                background: #13563e;
            }
        </style>
    </head>
    <body>
        <div class="message-card">
            <div class="icon">✅</div>
            <h2>Password Reset Successful</h2>
            <p><?php echo htmlspecialchars($message); ?></p>
            <a href="index.php" class="btn">Go to Login</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
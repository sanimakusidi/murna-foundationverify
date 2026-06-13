<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'config.php'; // Assume $conn is a MySQLi connection

set_time_limit(120); // Increase execution time limit

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // ---------- SELECT query ----------
     $conn = getDB();
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result(); // get result set
    $user = $result->fetch_assoc();
    $stmt->close();                // ✅ FREE the result – this avoids "out of sync"
    // Alternatively: $result->free();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);

        // ---------- INSERT query (now safe) ----------
        $stmt2 = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt2->bind_param("iss", $user['id'], $hashed_token, $expires_at);
        $stmt2->execute();
        $stmt2->close();

        // Send email (same as before, but ensure environment variables are loaded)
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'murnafoundationverify@gmail.com';  // <-- hardcode for test
            $mail->Password = 'iasrutzcabijytmb';     // <-- hardcode for test
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
            $mail->Timeout = 10; // Fail fast if SMTP is blocked (e.g., on Railway)
            $mail->setFrom('murnafoundationverify@gmail.com', 'Murna Foundation');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            
            // Generate the dynamic reset link using the current host
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];
            $resetLink = $protocol . $host . '/reset_password.php?token=' . $token;
            
            $mail->Body = 'Click this link to reset your password: <a href="' . $resetLink . '">Reset Password</a>';
            $mail->send();
            echo 'Reset link has been sent to your email.';
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            // Fallback for Railway where outbound SMTP is blocked
            echo '<div style="padding: 15px; border: 1px solid #ffcc00; background-color: #ffffe6; border-radius: 5px; margin-top: 20px;">';
            echo '<strong>Development Notice:</strong> It appears your host (Railway) is blocking outgoing SMTP connections, which is common on free/hobby plans.<br><br>';
            echo 'For testing purposes, here is your password reset link: <br><br>';
            echo '<a href="' . $resetLink . '" style="background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; display: inline-block;">Reset Password Now</a>';
            echo '</div>';
        }
    } else {
        echo 'If an account exists with that email, you will receive a reset link.';
    }
}
?>
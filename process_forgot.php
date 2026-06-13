<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'config.php'; // Assume $conn is a MySQLi connection

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
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
           $mail->setFrom('murnafoundationverify@gmail.com', 'Test');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = 'Click this link to reset your password: <a href="https://877e-102-91-103-174.ngrok-free.app/murna-foundation/reset_password.php?token=' . $token . '">Reset Password</a>';
            $mail->send();
            echo 'Reset link has been sent to your email.';
        } catch (Exception $e) {
            error_log("Mail error: " . $mail->ErrorInfo);
            echo 'There was an error sending the email.';
              
        }
    } else {
        echo 'If an account exists with that email, you will receive a reset link.';
    }
}
?>
<?php
require 'config.php';

// Enable error logging (remove in production)
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/reset_errors.log');
error_reporting(E_ALL);

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Fetch all valid (unused & not expired) reset entries
    $conn = getDB();
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
        // Token valid – show styled form with confirm password
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
            <title>Reset Password | Murna Foundation</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

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
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
           background: linear-gradient(135deg, #002D62 0%, #007A3D 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            line-height: 1.5;
            color: #1e2a32;
        }

                .reset-card {
                    background: #ffffff;
                    max-width: 500px;
                    width: 100%;
                    border-radius: 28px;
                    box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.1), 0 1px 2px rgba(0, 0, 0, 0.02);
                    padding: 2rem 2rem 2.5rem;
                    transition: transform 0.2s ease, box-shadow 0.2s ease;
                }

                .reset-card:hover {
                    box-shadow: 0 25px 40px -14px rgba(0, 0, 0, 0.15);
                }

                .brand {
                    text-align: center;
                    margin-bottom: 1.8rem;
                }

                .brand-icon {
                    font-size: 2.8rem;
                    margin-bottom: 0.5rem;
                    display: inline-block;
                }

                .brand h1 {
                    font-size: 1.85rem;
                    font-weight: 600;
                    letter-spacing: -0.3px;
                    background: linear-gradient(125deg, #1b6b4c, #2c8f6b);
                    background-clip: text;
                    -webkit-background-clip: text;
                    color: transparent;
                }

                .brand .sub {
                    font-size: 0.9rem;
                    color: #5a6e7a;
                    margin-top: 0.25rem;
                }

                .form-title {
                    font-size: 1.65rem;
                    font-weight: 600;
                    margin-bottom: 0.5rem;
                    color: #1c3a2f;
                }

                .form-description {
                    font-size: 0.95rem;
                    color: #4a5b66;
                    margin-bottom: 1.8rem;
                    border-left: 3px solid #2c8f6b;
                    padding-left: 1rem;
                    background: #f9fbfc;
                    border-radius: 0 12px 12px 0;
                }

                .input-group {
                    margin-bottom: 1.5rem;
                }

                label {
                    display: block;
                    font-weight: 500;
                    margin-bottom: 0.5rem;
                    font-size: 0.9rem;
                    color: #1e2a32;
                }

                .input-wrapper {
                    display: flex;
                    align-items: center;
                    background: #f5f9fc;
                    border: 1px solid #cbdbe0;
                    border-radius: 18px;
                    transition: all 0.2s;
                    padding: 0.5rem 1rem;
                }

                .input-wrapper:focus-within {
                    border-color: #2c8f6b;
                    box-shadow: 0 0 0 3px rgba(44, 143, 107, 0.2);
                    background: #ffffff;
                }

                .input-icon {
                    margin-right: 0.75rem;
                    font-size: 1.25rem;
                    color: #6c8b7a;
                }

                input[type="password"] {
                    width: 100%;
                    border: none;
                    background: transparent;
                    font-size: 1rem;
                    padding: 0.6rem 0;
                    outline: none;
                    font-family: inherit;
                    color: #1e2a32;
                }

                input[type="password"]::placeholder {
                    color: #9aaeb9;
                    font-weight: 400;
                }

                .error-message {
                    color: #d9534f;
                    font-size: 0.8rem;
                    margin-top: 0.5rem;
                    display: none;
                }

                .btn-submit {
                    background: #1b6b4c;
                    color: white;
                    border: none;
                    width: 100%;
                    padding: 0.9rem 1rem;
                    font-size: 1rem;
                    font-weight: 600;
                    border-radius: 40px;
                    cursor: pointer;
                    transition: all 0.2s;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 0.5rem;
                    margin-top: 0.5rem;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
                }

                .btn-submit:hover {
                    background: #13563e;
                    transform: scale(1.01);
                    box-shadow: 0 8px 18px rgba(27, 107, 76, 0.25);
                }

                .btn-submit:active {
                    transform: scale(0.98);
                }

                .back-link {
                    text-align: center;
                    margin-top: 1.8rem;
                    font-size: 0.9rem;
                }

                .back-link a {
                    color: #1b6b4c;
                    text-decoration: none;
                    font-weight: 500;
                    border-bottom: 1px dotted #9bc9b6;
                }

                .back-link a:hover {
                    color: #0e4a35;
                    border-bottom-color: #0e4a35;
                }

                .footer-note {
                    text-align: center;
                    margin-top: 2rem;
                    font-size: 0.75rem;
                    color: #5a6c6e;
                    max-width: 460px;
                }

                @media (max-width: 550px) {
                    .reset-card {
                        padding: 1.6rem;
                    }
                    .form-title {
                        font-size: 1.45rem;
                    }
                    .brand h1 {
                        font-size: 1.5rem;
                    }
                }
            </style>
        </head>
        <body>
            <div class="reset-card">
                <div class="brand">
                    <div class="brand-icon">  <img 
                src="murna.jpg" 
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
                ></div>
                    <h1>Murna Foundation</h1>
                    <div class="sub">restoring hope · empowering communities</div>
                </div>

                <div class="form-title">Create new password</div>
                <div class="form-description">
                    Your new password must be at least 8 characters long. Choose something strong and unique.
                </div>

                <form action="process_reset.php" method="POST" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="input-group">
                        <label for="password">New Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">🔒</span>
                            <input type="password" name="password" id="password" placeholder="••••••••" required minlength="8">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">✓</span>
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required minlength="8">
                        </div>
                        <div class="error-message" id="matchError">Passwords do not match.</div>
                    </div>

                    <button type="submit" class="btn-submit">
                        <span>🔄</span> Reset password
                    </button>

                    <div class="back-link">
                        <a href="index.php">← Back to sign in</a>
                    </div>
                </form>
            </div>
            <div class="footer-note">
                Murna Foundation — secure password reset
            </div>

            <script>
                const form = document.getElementById('resetForm');
                const password = document.getElementById('password');
                const confirmPassword = document.getElementById('confirm_password');
                const matchError = document.getElementById('matchError');

                form.addEventListener('submit', function(event) {
                    if (password.value !== confirmPassword.value) {
                        event.preventDefault();  // stop submission
                        matchError.style.display = 'block';
                        confirmPassword.focus();
                    } else {
                        matchError.style.display = 'none';
                    }
                });

                // Optional: hide error when user starts typing again
                confirmPassword.addEventListener('input', function() {
                    if (password.value === confirmPassword.value) {
                        matchError.style.display = 'none';
                    }
                });
                password.addEventListener('input', function() {
                    if (password.value === confirmPassword.value) {
                        matchError.style.display = 'none';
                    }
                });
            </script>
        </body>
        </html>
        <?php
    } else {
        // Token invalid or expired – show error page
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Invalid Link | Murna Foundation</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body {
                    font-family: 'Inter', sans-serif;
                    background: linear-gradient(135deg, #e9f2f5 0%, #d4e2e8 100%);
                    min-height: 100vh;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    padding: 1.5rem;
                }
                .error-card {
                    background: white;
                    max-width: 450px;
                    width: 100%;
                    border-radius: 28px;
                    padding: 2rem;
                    text-align: center;
                    box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1);
                }
                .error-icon { font-size: 3rem; margin-bottom: 1rem; }
                h2 { color: #1c3a2f; margin-bottom: 0.5rem; }
                p { color: #4a5b66; margin-bottom: 1.5rem; }
                .btn {
                    display: inline-block;
                    background: #1b6b4c;
                    color: white;
                    padding: 0.75rem 1.5rem;
                    border-radius: 40px;
                    text-decoration: none;
                    font-weight: 500;
                }
                .btn:hover { background: #13563e; }
            </style>
        </head>
        <body>
            <div class="error-card">
                <div class="error-icon">⏰</div>
                <h2>Invalid or expired link</h2>
                <p>The password reset link you used is either invalid, already used, or has expired (30 minutes).</p>
                <a href="forgot_password.php" class="btn">Request new link</a>
            </div>
        </body>
        </html>
        <?php
    }
} else {
    // No token provided
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Missing Token | Murna Foundation</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                font-family: 'Inter', sans-serif;
                background: linear-gradient(135deg, #e9f2f5 0%, #d4e2e8 100%);
                min-height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                padding: 1.5rem;
            }
            .error-card {
                background: white;
                max-width: 450px;
                width: 100%;
                border-radius: 28px;
                padding: 2rem;
                text-align: center;
                box-shadow: 0 20px 35px -12px rgba(0,0,0,0.1);
            }
            .error-icon { font-size: 3rem; margin-bottom: 1rem; }
            h2 { color: #1c3a2f; margin-bottom: 0.5rem; }
            p { color: #4a5b66; margin-bottom: 1.5rem; }
            .btn {
                display: inline-block;
                background: #1b6b4c;
                color: white;
                padding: 0.75rem 1.5rem;
                border-radius: 40px;
                text-decoration: none;
                font-weight: 500;
            }
            .btn:hover { background: #13563e; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <div class="error-icon">⚠️</div>
            <h2>No reset token provided</h2>
            <p>Please use the link from your email to reset your password.</p>
            <a href="forgot_password.php" class="btn">Request new link</a>
        </div>
    </body>
    </html>
    <?php
}
?>
<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$page = $_GET['page'] ?? 'login';
$error = '';
$success = '';

// ─── Handle Login ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['account_type'] === 'individual' ? $user['full_name'] : $user['org_name'];
            $_SESSION['account_type'] = $user['account_type'];
            $_SESSION['email'] = $user['email'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

// ─── Handle Individual Registration ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_individual') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $nin_input = $_POST['nin_input'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email) || empty($password)|| empty($nin_input)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (account_type, full_name, email, phone, password, nin_input) VALUES ('individual', ?, ?, ?, ?,?)");
                $stmt->bind_param('sssss', $full_name, $email, $phone, $hashed, $nin_input);
                $stmt->execute();
                $user_id = $db->insert_id;

                $acc_num = generateAccountNumber();
                $stmt2 = $db->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, 0.00)");
                $stmt2->bind_param('is', $user_id, $acc_num);
                $stmt2->execute();

                $db->commit();
                $success = 'Account created successfully! You can now log in.';
                $page = 'login';
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

// ─── Handle Corporate Registration ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register_corporate') {
    $org_name = trim($_POST['org_name'] ?? '');
    $rc_number = trim($_POST['rc_number'] ?? '');
    $org_address = trim($_POST['org_address'] ?? '');
    $org_state = trim($_POST['org_state'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($org_name) || empty($email) || empty($password) || empty($contact_person)) {
        $error = 'Please fill all required fields.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } else {
        $db = getDB();
        $check = $db->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $db->begin_transaction();
            try {
                $stmt = $db->prepare("INSERT INTO users (account_type, org_name, rc_number, org_address, org_state, contact_person, contact_phone, email, password) VALUES ('corporate', ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssssss', $org_name, $rc_number, $org_address, $org_state, $contact_person, $contact_phone, $email, $hashed);
                $stmt->execute();
                $user_id = $db->insert_id;

                $acc_num = generateAccountNumber();
                $stmt2 = $db->prepare("INSERT INTO accounts (user_id, account_number, balance) VALUES (?, ?, 0.00)");
                $stmt2->bind_param('is', $user_id, $acc_num);
                $stmt2->execute();

                $db->commit();
                $success = 'Corporate account created! You can now log in.';
                $page = 'login';
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$nigerian_states = [
    'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
    'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo',
    'Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos',
    'Nasarawa','Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers',
    'Sokoto','Taraba','Yobe','Zamfara'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Murna Foundation - NIN Verification Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
             --banner-height: auto;
            --green: #00A651;
            --green-light: #00C960;
            --green-dark: #007A3D;
            --blue: #002D62;
            --blue-mid: #003f8a;
            --blue-light: #0056b8;
            --black: #0a0a0a;
            --black-soft: #111827;
            --card: #0f1f3d;
            --card-light: #152a50;
            --border: rgba(0, 166, 81, 0.2);
            --text: #e8f0fe;
            --text-muted: #8aa0c4;
            --white: #ffffff;
            --input-bg: rgba(255,255,255,0.05);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--black);
    margin: 0;
    padding: 0;
    overflow-x: hidden;
    color: var(--text);
}

        /* ── Left Panel ── */
        .auth-left {
            width: 42%;
            background: linear-gradient(145deg, var(--blue) 0%, #001a3d 60%, #000c1f 100%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 50px 48px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .auth-left::before {
            content: '';
            position: absolute;
            top: -100px; right: -100px;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(0,166,81,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .auth-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(0,166,81,0.1) 0%, transparent 70%);
            border-radius: 50%;
        }

        .brand {
            position: relative; z-index: 2;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 50px;
        }

        .logo-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-size: 22px; font-weight: 800;
            color: white;
            box-shadow: 0 8px 24px rgba(0,166,81,0.3);
            flex-shrink: 0;
        }

        .brand-name {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.2;
            color: var(--white);
        }

        .brand-name span {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: var(--green-light);
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .brand-hero h1 {
            font-family: 'Syne', sans-serif;
            font-size: 42px;
            font-weight: 800;
            line-height: 1.15;
            color: var(--white);
            margin-bottom: 20px;
        }

        .brand-hero h1 em {
            font-style: normal;
            color: var(--green-light);
        }

        .brand-hero p {
            font-size: 16px;
            line-height: 1.7;
            color: var(--text-muted);
            max-width: 340px;
        }

        .features {
            position: relative; z-index: 2;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(0,166,81,0.15);
            border-radius: 12px;
            padding: 14px 18px;
        }

        .feature-icon {
            width: 36px; height: 36px;
            background: rgba(0,166,81,0.15);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon svg {
            width: 18px; height: 18px;
            fill: var(--green-light);
        }

        .feature-text {
            font-size: 14px;
            color: var(--text);
            font-weight: 500;
        }

        /* ── Right Panel ── */
        .auth-right {
            flex: 1;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 40px 32px;
            overflow-y: auto;
            background: var(--black-soft);
        }

        .auth-box {
            width: 100%;
            max-width: 520px;
        }

        .auth-tabs {
            display: flex;
            gap: 4px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 6px;
            margin-bottom: 32px;
        }

        .auth-tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            font-size: 14px;
            font-weight: 600;
            font-family: 'Syne', sans-serif;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.25s;
            color: var(--text-muted);
            text-decoration: none;
        }

        .auth-tab.active {
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            color: white;
            box-shadow: 0 4px 16px rgba(0,166,81,0.25);
        }

        .auth-heading {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 6px;
        }

        .auth-subheading {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        /* Register Type Toggle */
        .reg-type-tabs {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
        }

        .reg-type-btn {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            border: 2px solid rgba(255,255,255,0.08);
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.25s;
            text-align: center;
        }

        .reg-type-btn.active {
            border-color: var(--green);
            color: var(--green-light);
            background: rgba(0,166,81,0.08);
        }

        .reg-type-btn .btn-label {
            display: block;
            font-size: 12px;
            font-weight: 400;
            color: inherit;
            opacity: 0.7;
            margin-top: 3px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        input, select, textarea {
            width: 100%;
            padding: 14px 16px;
            background: var(--input-bg);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 3px rgba(0,166,81,0.12);
        }

        select option {
            background: var(--card);
            color: var(--text);
        }

        textarea { resize: vertical; min-height: 80px; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .btn-primary {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 8px;
            transition: all 0.25s;
            letter-spacing: 0.5px;
            box-shadow: 0 6px 20px rgba(0,166,81,0.25);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(0,166,81,0.35);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(220,53,69,0.12);
            border: 1px solid rgba(220,53,69,0.3);
            color: #ff6b6b;
        }

        .alert-success {
            background: rgba(0,166,81,0.12);
            border: 1px solid rgba(0,166,81,0.3);
            color: var(--green-light);
        }

        .divider {
            display: flex;
            align-items: center;
            gap: 14px;
            margin: 24px 0;
            color: var(--text-muted);
            font-size: 13px;
        }

        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255,255,255,0.08);
        }

        .hidden { display: none !important; }

        @media (max-width: 900px) {
            .auth-left { display: none; }
            .auth-right { padding: 28px 20px; }
            .form-row { grid-template-columns: 1fr; }
        }
        /* ── Page Wrapper ── */
/* Page wrapper – vertical stack */
#page-wrapper {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    width: 100%;
}

/* Banner – takes most of the viewport */
#banner {
    position: relative;
    width: 100%;
     height: var(--banner-height);         /* adjust height as desired */
    overflow: hidden;
    flex-shrink: 0;
}

#banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

#banner .banner-overlay {
    position: absolute;
    bottom: 10%;
    left: 5%;
    color: #fff;
    text-shadow: 0 2px 12px rgba(0,0,0,0.7);
    background: rgba(0,0,0,0.4);
    padding: 20px 30px;
    border-radius: 12px;
    backdrop-filter: blur(4px);
    max-width: 600px;
}

#banner .banner-overlay h2 {
    font-family: 'Syne', sans-serif;
    font-size: 2.8rem;
    font-weight: 800;
    margin: 0 0 6px 0;
}

#banner .banner-overlay p {
    font-size: 1.2rem;
    margin: 0;
    opacity: 0.9;
}

/* Container for the two panels – horizontal flex */
#auth-container {
    display: flex;
    flex-direction: row;
    flex: 1 1 auto;        /* takes remaining height */
    min-height: 0;         /* prevent overflow */
    width: 100%;
}

/* Left panel – keep original width 42% */
.auth-left {
    width: 42%;
    background: linear-gradient(145deg, var(--blue) 0%, #001a3d 60%, #000c1f 100%);
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 50px 48px;
    overflow: hidden;
    flex-shrink: 0;
}

/* Right panel – takes remaining space */
.auth-right {
    flex: 1;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 40px 32px;
    overflow-y: auto;
    background: var(--black-soft);
    min-height: 0; /* allows scrolling if content overflows */
}

    </style>
</head>
<body>

  <div id="page-wrapper">
        <div id="banner">
            <img src="ndpact.jpg" alt="NDP Act 2023 Audit" />
            <div class="banner-overlay">
                <h2>NDP Act 2023 Compliant</h2>
                <p>Official NIN Verification Portal</p>
            </div>
        </div>

        <div id="auth-container">
        <div class="auth-left">
            <div class="brand">
                <div class="brand-logo">
                                    <img 
                        src="murna.jpg" 
                        alt="Murna Logo"
                        style="
                            width:120px;
                            height:120px;
                            object-fit:cover;
                            border-radius:20px;
                            padding:9px;
                            background:#fff;
                            box-shadow:0 4px 12px rgba(0,0,0,0.15);
                            border:8px solid #00A651;
                        "
                        >
                    
                    <div class="brand-name">
                        Murna Foundation
                        <span>NIN Verification Portal, Partner to Randa Frames, Licensed by NDPC</span>
                    </div>
                </div>
                <div class="brand-hero">
                    <h1>Verify Identity with <em>Confidence</em></h1>
                    <p>Access and verify persons with National Identity Management Commission securely. Verify individuals by NIN, phone number, or demographic data.</p>
                </div>
            </div>
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    </div>
                    <div class="feature-text">Secure &amp; Encrypted Verification</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                    <div class="feature-text">Real-time NIN Database Access</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <svg viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
                    </div>
                    <div class="feature-text">Individual &amp; Corporate Accounts</div>
                </div>
            </div>
        </div>

  

        <!-- RIGHT PANEL -->
        <div class="auth-right">
            <div class="auth-box">

                <!-- Tabs: Login / Register -->
                <div class="auth-tabs">
                    <a href="?page=login" class="auth-tab <?= $page === 'login' ? 'active' : '' ?>">Sign In</a>
                    <a href="?page=register" class="auth-tab <?= $page === 'register' ? 'active' : '' ?>">Create Account</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <!-- ─── LOGIN FORM ──────────────────────────────────────── -->
                <?php if ($page === 'login'): ?>
                <div class="auth-heading">Welcome Back</div>
                <div class="auth-subheading">Sign in to your Murna Foundation account</div>

                <form method="POST">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" placeholder="you@example.com" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn-primary">Sign In to Portal</button>

                </form>
                <div class="divider">Don't have an account?</div>
                <a href="?page=register" style="display:block; text-align:center; color:var(--green-light); font-weight:600; font-size:15px; text-decoration:none;">Create Account &rarr;</a>

        <div class="divider">Can't access your account?</div>
        <a href="forgot_password.php" style="display:block; text-align:center; color:var(--green-light); font-weight:600; font-size:15px; text-decoration:none;">Forgot Password? &rarr;</a>
            
                <!-- ─── REGISTER FORM ───────────────────────────────────── -->
                <?php elseif ($page === 'register'): ?>
                <div class="auth-heading">Create Account</div>
                <div class="auth-subheading">Select your account type to get started</div>

                <div class="reg-type-tabs">
                    <button class="reg-type-btn active" id="btn-individual" onclick="switchRegType('individual')">
                        Individual
                        <span class="btn-label">Personal account</span>
                    </button>
                    <button class="reg-type-btn" id="btn-corporate" onclick="switchRegType('corporate')">
                        Corporate Body
                        <span class="btn-label">Organisation account</span>
                    </button>
                </div>

                <!-- Individual Form -->
                <div id="form-individual">
                    <form method="POST">
                        <input type="hidden" name="action" value="register_individual">
                        <div class="form-group">
                            <label>Full Name <span style="color:var(--green)">*</span></label>
                            <input type="text" name="full_name" placeholder="e.g. Amina Mohammed" required>
                        </div>
                        <div class="form-group">
                                <label>Email Address <span style="color:var(--green)">*</span></label>
                                <input type="email" name="email" placeholder="you@example.com" required>
                            </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                            <label>NIN <span style="color:var(--green)">*</span></label>
                            <input type="text" name="nin_input" placeholder="e.g. 11 digit NIN" required minlength="11" maxlength="11">
                        </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" placeholder="080XXXXXXXX">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Password <span style="color:var(--green)">*</span></label>
                                <input type="password" name="password" placeholder="Min. 8 characters" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password <span style="color:var(--green)">*</span></label>
                                <input type="password" name="confirm_password" placeholder="Repeat password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">Create Individual Account</button>
                    </form>
                </div>

                <!-- Corporate Form -->
                <div id="form-corporate" class="hidden">
                    <form method="POST">
                        <input type="hidden" name="action" value="register_corporate">
                        <div class="form-group">
                            <label>Organisation Name <span style="color:var(--green)">*</span></label>
                            <input type="text" name="org_name" placeholder="e.g. Murna Foundation Ltd" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>RC Number</label>
                                <input type="text" name="rc_number" placeholder="CAC RC Number">
                            </div>
                            <div class="form-group">
                                <label>State of Registration</label>
                                <select name="org_state">
                                    <option value="">-- Select State --</option>
                                    <?php foreach ($nigerian_states as $state): ?>
                                        <option value="<?= $state ?>"><?= $state ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Organisation Address</label>
                            <textarea name="org_address" placeholder="Full registered address"></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Contact Person <span style="color:var(--green)">*</span></label>
                                <input type="text" name="contact_person" placeholder="Authorised signatory" required>
                            </div>
                            <div class="form-group">
                                <label>Contact Phone</label>
                                <input type="tel" name="contact_phone" placeholder="080XXXXXXXX">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Email Address <span style="color:var(--green)">*</span></label>
                                <input type="email" name="email" placeholder="org@example.com" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Password <span style="color:var(--green)">*</span></label>
                                <input type="password" name="password" placeholder="Min. 8 characters" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm Password <span style="color:var(--green)">*</span></label>
                                <input type="password" name="confirm_password" placeholder="Repeat password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">Create Corporate Account</button>
                    </form>
                </div>

                <div class="divider">Already have an account?</div>
                <a href="?page=login" style="display:block; text-align:center; color:var(--green-light); font-weight:600; font-size:15px; text-decoration:none;">Sign In &rarr;</a>
                <?php endif; ?>
</div>
            </div>
        </div>
</div>
<script>
function switchRegType(type) {
    document.getElementById('form-individual').classList.toggle('hidden', type !== 'individual');
    document.getElementById('form-corporate').classList.toggle('hidden', type !== 'corporate');
    document.getElementById('btn-individual').classList.toggle('active', type === 'individual');
    document.getElementById('btn-corporate').classList.toggle('active', type === 'corporate');
}
</script>
</body>
</html>

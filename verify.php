<?php
require_once 'config.php';
requireLogin();
$db = getDB();
$user_id  = $_SESSION['user_id'];
$balance  = getUserBalance($user_id);


// Get verification type from URL
$type         = $_GET['type'] ?? '';
$valid_types  = ['nin', 'phone', 'demographic'];
$error        = '';
$success      = '';
$result_data  = null;

// ─── Handle password change (first login) ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $tempToken = $_POST['temp_token'] ?? '';
    
    $error = '';
    if (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $changeResult = changeRandaVerifyPassword(RANDAVERIFY_ADMIN_PASS, $newPassword, $tempToken);
        if ($changeResult['status'] === 'success') {
            // Store the new password in session for future use
            $_SESSION['randa_new_password'] = $newPassword;
            // Clear any old token
            unset($_SESSION['randa_token'], $_SESSION['randa_token_expiry']);
            // Redirect to same page to retry verification (clean URL)
            $redirectType = $_GET['type'] ?? '';
            header('Location: verify.php' . ($redirectType ? '?type=' . urlencode($redirectType) : ''));
            exit();
        } else {
            $error = $changeResult['message'];
        }
    }
    // If there's an error, we'll display the form again below – we set a flag
    $show_password_change_form = true;
}



// Fetch current verification costs from settings
$cost_stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('nin_verification_cost', 'phone_verification_cost', 'demographic_verification_cost')");
$cost_stmt->execute();
$cost_result = $cost_stmt->get_result();
$costs = ['nin' => 100, 'phone' => 100, 'demographic' => 100]; // fallback defaults
while ($row = $cost_result->fetch_assoc()) {
    switch ($row['setting_key']) {
        case 'nin_verification_cost': $costs['nin'] = (float)$row['setting_value']; break;
        case 'phone_verification_cost': $costs['phone'] = (float)$row['setting_value']; break;
        case 'demographic_verification_cost': $costs['demographic'] = (float)$row['setting_value']; break;
    }
}





// ─── Handle Form Submission ──────────────────────────────────────────────────

// ─── Handle Form Submission ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    $ver_type        = $_POST['ver_type'] ?? '';
    $query_input     = '';
    $error           = '';
    $result_data     = null;
    $api_response_raw = '';
    $endpoint        = '';
    $api_payload     = [];

    if (!in_array($ver_type, $valid_types)) {
        $error = 'Invalid verification type.';
    } elseif ($balance < $costs[$ver_type]) {
        $error = 'Insufficient wallet balance. Please fund your wallet to continue.';
    } else {

        $default_reason = 'corporate';

        switch ($ver_type) {

            // ── NIN ──────────────────────────────────────────────
            case 'nin':
                $nin = trim($_POST['nin_number'] ?? '');
                if (!preg_match('/^\d{11}$/', $nin)) {
                    $error = 'NIN must be exactly 11 digits.';
                } else {
                    $endpoint    = RANDAVERIFY_ENDPOINT_NIN;
                    $api_payload = ['nin' => $nin, 'reason' => $default_reason];
                    $query_input = $nin;
                }
                break;

            // ── Phone ─────────────────────────────────────────────
            case 'phone':
                $phone       = trim($_POST['phone_number'] ?? '');
                $clean_phone = preg_replace('/^\+234/', '0', $phone);
                if (!preg_match('/^0[7-9][0-1]\d{8}$/', $clean_phone)) {
                    $error = 'Enter a valid Nigerian phone number (e.g. 08012345678).';
                } else {
                    $endpoint    = RANDAVERIFY_ENDPOINT_PHONE;
                    // API accepts the number in local format (0XXXXXXXXXX)
                    $api_payload = ['phone' => $clean_phone, 'reason' => $default_reason];
                    $query_input = $clean_phone;
                }
                break;

            // ── Demographic ───────────────────────────────────────
            case 'demographic':
                $first_name = trim($_POST['first_name'] ?? '');
                $surname    = trim($_POST['surname']    ?? '');
                $dob        = trim($_POST['dob']        ?? '');
                $gender     = trim($_POST['gender']     ?? '');
                if (empty($first_name) || empty($surname) || empty($dob) || empty($gender)) {
                    $error = 'Please fill in all required fields.';
                } else {
                    $endpoint    = RANDAVERIFY_ENDPOINT_DEMO;
                    $formatted_dob = date('d-m-Y', strtotime($dob));
                    $api_payload = [
                        'firstname' => $first_name,
                        'lastname'  => $surname,
                        'dob'       => $formatted_dob,
                        'gender'    => $gender,
                        'reason'    => 'nyscCheck',
                    ];
                    $query_input = json_encode(['name' => "$first_name $surname", 'dob' => $dob]);
                }
                break;
        }

        // ── Call API ────────────────────────────────────────────────────────
        if (empty($error) && $endpoint !== '') {
            $api_response     = callRandaVerifyAPI($endpoint, $api_payload);
            
            $api_response_raw = json_encode($api_response);

            // Password change gate
            if (isset($api_response['error']) && $api_response['error'] === 'require_password_change') {
                $_SESSION['temp_token']      = $api_response['temp_token'];
                $show_password_change_form   = true;
                $skip_verification           = true;
            } else {
                $skip_verification = false;
            }

            if (!$skip_verification) {

                // ── Determine success ─────────────────────────────────────
                // RandaVerify wraps data under "data" key with status true/false
                $is_success = false;

                if (
                    !isset($api_response['error']) &&
                    isset($api_response['data']) &&
                    !empty($api_response['data'])
                ) {
                    $is_success  = true;
                    $result_data = $api_response['data'];
                       error_log('Full API Response: ' . print_r($api_response, true));
                     $_SESSION['last_verification_data'] = $result_data;
                    $transaction_id = $result_data['transaction_id'] ?? $result_data['reference_id'] ?? null;
                    if ($transaction_id) {
                        $_SESSION['last_transaction_id'] = $transaction_id;
                    }
                  
                    
                } elseif (
                    !isset($api_response['error']) &&
                    isset($api_response['status']) &&
                    $api_response['status'] === true &&
                    isset($api_response['data'])
                ) {
                    $is_success  = true;
                    $result_data = $api_response['data'];
                       error_log('Full API Response: ' . print_r($api_response, true));
                     $_SESSION['last_verification_data'] = $result_data; 
                    $transaction_id = $result_data['transaction_id'] ?? $result_data['reference_id'] ?? null;
                    if ($transaction_id) {
                        $_SESSION['last_transaction_id'] = $transaction_id;
                    }
                    
                }

                // ── Deduct on success only ────────────────────────────────
                if ($is_success) {
                    $desc     = 'RandaVerify ' . strtoupper($ver_type) . ' verification';
                    $debit_ok = debitAccount($user_id, $costs[$ver_type], $desc);
                    if ($debit_ok) {
                        $balance = getUserBalance($user_id);
                    } else {
                        $error       = 'Wallet debit failed after successful verification. Please contact support.';
                        $is_success  = false;
                        $result_data = null;
                    }
                } else {
                    // Surface the API's own error message
                    $error = $api_response['detail'] ?? 'Verification failed — no matching record found.';
                }

                // ── Log every attempt ─────────────────────────────────────
                $status_flag = $is_success ? 'success' : 'failed';
                $db_log      = getDB();
                $log_stmt    = $db_log->prepare(
                    "INSERT INTO verification_logs
                     (user_id, verification_type, query_input, response_data, cost, status)
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $log_stmt->bind_param('issdss', $user_id, $ver_type, $query_input, $api_response_raw, $costs[$ver_type], $status_flag);
                $log_stmt->execute();
                $log_stmt->close();
            }
        }
    }
}

 

// ── Labels & meta for each type ─────────────────────────────────────────────
$type_meta = [
    'nin'         => ['label' => 'NIN Number',    'icon_path' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0'],
    'phone'       => ['label' => 'Phone Number',  'icon_path' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z'],
    'demographic' => ['label' => 'Demographics',  'icon_path' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NIN Verification - Murna Foundation</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --green:       #00A651;
            --green-light: #00C960;
            --green-dark:  #007A3D;
            --blue:        #002D62;
            --blue-mid:    #003f8a;
            --blue-light:  #0056b8;
            --black:       #080f1a;
            --black-soft:  #0d1625;
            --card:        #0f1e38;
            --card-light:  #162840;
            --border:      rgba(255,255,255,0.07);
            --border-g:    rgba(0,166,81,0.25);
            --text:        #c8d8f0;
            --text-muted:  #6e88ab;
            --white:       #ffffff;
            --red:         #ff4d4d;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--black);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ════════════════════════════════════════
           SIDEBAR
        ════════════════════════════════════════ */
        .sidebar {
            width: 260px;
            background: var(--blue);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            z-index: 100;
            border-right: 1px solid rgba(0,166,81,0.15);
        }

        .sidebar-brand {
            padding: 28px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 800;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(0,166,81,0.3);
        }

        .brand-text {
            font-family: 'Syne', sans-serif;
            font-size: 14px; font-weight: 700;
            color: var(--white);
            line-height: 1.3;
        }

        .brand-text small {
            display: block;
            font-size: 11px; font-weight: 400;
            color: var(--green-light);
            opacity: 0.85;
        }

        .sidebar-nav { flex: 1; padding: 20px 0; overflow-y: auto; }

        .nav-section { padding: 0 16px; margin-bottom: 8px; }

        .nav-label {
            font-size: 10px; font-weight: 700;
            color: var(--text-muted);
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 8px 10px 6px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 10px;
            color: #7a97be;
            text-decoration: none;
            font-size: 14px; font-weight: 500;
            margin-bottom: 2px;
            transition: all 0.2s;
        }

        .nav-link svg {
            width: 18px; height: 18px;
            fill: currentColor;
            flex-shrink: 0;
        }

        .nav-link:hover, .nav-link.active {
            background: rgba(0,166,81,0.12);
            color: var(--green-light);
        }

        /* ════════════════════════════════════════
           MAIN LAYOUT
        ════════════════════════════════════════ */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 40px 40px 60px;
            min-height: 100vh;
        }

        /* ── Page Header ── */
        .page-header { margin-bottom: 36px; }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 12px;
        }

        .breadcrumb a {
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .breadcrumb a:hover { color: var(--green-light); }

        .breadcrumb-sep { opacity: 0.4; }

        .breadcrumb-current { color: var(--green-light); font-weight: 600; }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 30px; font-weight: 800;
            color: var(--white);
            margin-bottom: 6px;
        }

        .page-subtitle {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* ── Balance Strip ── */
        .balance-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px 24px;
            margin-bottom: 32px;
            flex-wrap: wrap;
            gap: 14px;
        }

        .balance-strip-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .balance-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: var(--green-light);
            box-shadow: 0 0 8px var(--green-light);
            flex-shrink: 0;
        }

        .balance-strip-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 2px;
        }

        .balance-strip-value {
            font-family: 'Syne', sans-serif;
            font-size: 22px; font-weight: 800;
            color: var(--green-light);
        }

        .balance-strip-cost {
            font-size: 13px;
            color: var(--text-muted);
        }

        .balance-strip-cost strong {
            color: var(--text);
            font-weight: 600;
        }

        .btn-fund {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 13px; font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .btn-fund:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(0,166,81,0.3);
        }

        /* ════════════════════════════════════════
           VERIFICATION TYPE SELECTOR (No type chosen)
        ════════════════════════════════════════ */
        .selector-heading {
            font-family: 'Syne', sans-serif;
            font-size: 20px; font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
        }

        .selector-sub {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 28px;
        }

        .type-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            max-width: 860px;
        }

        .type-card {
            display: flex;
            flex-direction: column;
            gap: 16px;
            padding: 28px 24px;
            background: var(--card);
            border: 2px solid var(--border);
            border-radius: 20px;
            text-decoration: none;
            color: var(--text);
            transition: all 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .type-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,166,81,0.06), transparent);
            opacity: 0;
            transition: opacity 0.25s;
        }

        .type-card:hover {
            border-color: var(--green);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(0,166,81,0.15);
        }

        .type-card:hover::before { opacity: 1; }

        .type-card-icon {
            width: 52px; height: 52px;
            background: rgba(0,166,81,0.1);
            border: 1px solid rgba(0,166,81,0.2);
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.25s;
        }

        .type-card:hover .type-card-icon {
            background: rgba(0,166,81,0.2);
        }

        .type-card-icon svg {
            width: 26px; height: 26px;
            fill: none;
            stroke: var(--green-light);
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .type-card-title {
            font-family: 'Syne', sans-serif;
            font-size: 17px; font-weight: 700;
            color: var(--white);
        }

        .type-card-desc {
            font-size: 13px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        .type-card-cost {
            display: inline-block;
            padding: 5px 12px;
            background: rgba(0,166,81,0.1);
            border: 1px solid rgba(0,166,81,0.2);
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            color: var(--green-light);
            align-self: flex-start;
        }

        .type-card-arrow {
            position: absolute;
            bottom: 20px; right: 20px;
            width: 28px; height: 28px;
            background: rgba(0,166,81,0.1);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0;
            transition: opacity 0.25s, transform 0.25s;
        }

        .type-card-arrow svg {
            width: 14px; height: 14px;
            fill: none;
            stroke: var(--green-light);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .type-card:hover .type-card-arrow {
            opacity: 1;
            transform: translateX(3px);
        }

        /* ── Insufficient balance overlay on card ── */
        .type-card.disabled {
            opacity: 0.6;
            pointer-events: none;
            cursor: not-allowed;
        }

        /* ════════════════════════════════════════
           INSUFFICIENT FUNDS BANNER
        ════════════════════════════════════════ */
        .insufficient-banner {
            display: flex;
            align-items: flex-start;
            gap: 18px;
            background: rgba(255, 77, 77, 0.07);
            border: 1px solid rgba(255, 77, 77, 0.25);
            border-radius: 16px;
            padding: 24px 28px;
            max-width: 620px;
            margin-bottom: 32px;
        }

        .insufficient-icon {
            width: 46px; height: 46px;
            background: rgba(255, 77, 77, 0.15);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .insufficient-icon svg {
            width: 24px; height: 24px;
            fill: none;
            stroke: var(--red);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .insufficient-title {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 700;
            color: var(--red);
            margin-bottom: 6px;
        }

        .insufficient-msg {
            font-size: 14px;
            color: rgba(255,255,255,0.6);
            line-height: 1.65;
            margin-bottom: 18px;
        }

        .insufficient-msg strong { color: var(--text); }

        .insufficient-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-back-link {
            font-size: 13px;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .btn-back-link:hover { color: var(--text); }

        /* ════════════════════════════════════════
           VERIFICATION FORM CARD
        ════════════════════════════════════════ */
        .verify-form-wrap {
            max-width: 620px;
        }

        .verify-form-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .verify-form-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 24px 28px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(90deg, rgba(0,45,98,0.5), transparent);
        }

        .verify-form-header-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(0,166,81,0.3);
            flex-shrink: 0;
        }

        .verify-form-header-icon svg {
            width: 24px; height: 24px;
            fill: none;
            stroke: white;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .verify-form-title {
            font-family: 'Syne', sans-serif;
            font-size: 20px; font-weight: 800;
            color: var(--white);
        }

        .verify-form-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .verify-form-body {
            padding: 32px 28px;
        }

        /* Form elements */
        .form-group { margin-bottom: 22px; }
        .form-group:last-of-type { margin-bottom: 28px; }

        .form-group label {
            display: block;
            font-size: 12px; font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 10px;
        }

        .form-group label .req {
            color: var(--green-light);
            margin-left: 3px;
        }

        .input-wrap { position: relative; }

        .input-wrap svg.input-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            width: 18px; height: 18px;
            fill: none;
            stroke: var(--text-muted);
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
            pointer-events: none;
        }

        .input-wrap input, .input-wrap select {
            width: 100%;
            padding: 14px 16px 14px 44px;
            background: rgba(255,255,255,0.04);
            border: 1.5px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            color: var(--white);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
            outline: none;
            transition: border-color 0.25s, box-shadow 0.25s, background 0.25s;
            letter-spacing: 0.3px;
        }

        .input-wrap select {
            appearance: none;
            -webkit-appearance: none;
        }

        .input-wrap select option {
            background: #002d62;
            color: var(--white);
        }

        .input-wrap input::placeholder {
            color: rgba(255,255,255,0.2);
        }

        .input-wrap input:focus, .input-wrap select:focus {
            border-color: var(--green);
            background: rgba(0,166,81,0.04);
            box-shadow: 0 0 0 4px rgba(0,166,81,0.1);
        }

        .input-wrap input:focus + svg.input-icon,
        .input-wrap svg.input-icon:has(+ input:focus),
        .input-wrap svg.input-icon:has(+ select:focus) {
            stroke: var(--green-light);
        }

        /* Focused icon color via JS class */
        .input-wrap.focused svg.input-icon {
            stroke: var(--green-light);
        }

        .input-hint {
            margin-top: 7px;
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .input-hint svg {
            width: 13px; height: 13px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
        }

        /* Date input specific */
        input[type="date"] {
            color-scheme: dark;
        }

        /* Row layout for demographics */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        /* Submit button */
        .btn-verify {
            width: 100%;
            padding: 16px 24px;
            background: linear-gradient(135deg, var(--green-dark) 0%, var(--green) 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: 'Syne', sans-serif;
            font-size: 16px; font-weight: 700;
            cursor: pointer;
            transition: all 0.25s;
            box-shadow: 0 6px 20px rgba(0,166,81,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.3px;
        }

        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(0,166,81,0.35);
        }

        .btn-verify:active { transform: translateY(0); }

        .btn-verify svg {
            width: 20px; height: 20px;
            fill: none;
            stroke: white;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        /* Cost notice */
        .cost-notice {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 14px;
        }

        .cost-notice svg {
            width: 14px; height: 14px;
            fill: none;
            stroke: var(--text-muted);
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .cost-notice strong { color: var(--green-light); }

        /* ── Alerts ── */
        .alert {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            padding: 16px 18px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .alert svg {
            width: 20px; height: 20px;
            fill: none;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .alert-error {
            background: rgba(255,77,77,0.08);
            border: 1px solid rgba(255,77,77,0.25);
            color: #ff8080;
        }

        .alert-error svg { stroke: #ff6b6b; }

        /* ════════════════════════════════════════
           RESULT CARD
        ════════════════════════════════════════ */
        .result-card {
            background: var(--card);
            border: 1px solid var(--border-g);
            border-radius: 20px;
            overflow: hidden;
            max-width: 680px;
            
        }

        .result-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 28px;
            background: linear-gradient(90deg, rgba(0,166,81,0.1), rgba(0,166,81,0.02));
            border-bottom: 1px solid rgba(0,166,81,0.15);
        }

        .result-success-icon {
            width: 42px; height: 42px;
            background: rgba(0,166,81,0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        .result-success-icon svg {
            width: 22px; height: 22px;
            fill: none;
            stroke: var(--green-light);
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .result-header-title {
            font-family: 'Syne', sans-serif;
            font-size: 18px; font-weight: 700;
            color: var(--green-light);
        }

        .result-header-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .result-body {
            padding: 28px;
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0;
        }

        /* Photo on the left */
        .result-photo-col {
            padding-right: 28px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .result-photo {
            width: 110px; height: 130px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid rgba(0,166,81,0.3);
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }

        .result-photo-placeholder {
            width: 110px; height: 130px;
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            border: 2px dashed rgba(255,255,255,0.1);
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            gap: 6px;
            color: var(--text-muted);
            font-size: 11px;
        }

        .result-photo-placeholder svg {
            width: 28px; height: 28px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.5;
        }

        /* Fields grid */
        .result-fields { display: flex; flex-direction: column; gap: 0; }

        .result-field {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .result-field:last-child { border-bottom: none; }

        .result-field-key {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.7px;
            white-space: nowrap;
            flex-shrink: 0;
            min-width: 130px;
        }

        .result-field-val {
            font-size: 15px;
            font-weight: 500;
            color: var(--white);
            text-align: right;
        }

        .result-field-val.mono {
            font-family: monospace;
            font-size: 14px;
            letter-spacing: 1px;
        }

        .result-footer {
            padding: 16px 28px;
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .result-footer-note {
            font-size: 12px;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .result-footer-note svg {
            width: 14px; height: 14px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            flex-shrink: 0;
        }

        .btn-new-verify {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 9px 18px;
            background: rgba(0,166,81,0.12);
            border: 1px solid rgba(0,166,81,0.25);
            color: var(--green-light);
            border-radius: 8px;
            font-size: 13px; font-weight: 700;
            text-decoration: none;
            font-family: 'Syne', sans-serif;
            transition: all 0.2s;
        }

        .btn-new-verify:hover {
            background: rgba(0,166,81,0.2);
        }

        /* ════════════════════════════════════════
           RESPONSIVE
        ════════════════════════════════════════ */
        @media (max-width: 900px) {
            .sidebar { display: none; }
            .main { margin-left: 0; padding: 24px 20px 60px; }
            .type-cards { grid-template-columns: 1fr; }
            .form-row { grid-template-columns: 1fr; }
            .result-body { grid-template-columns: 1fr; }
            .result-photo-col { flex-direction: row; padding: 0 0 20px; border-bottom: 1px solid var(--border); margin-bottom: 4px; }
            .result-field-val { text-align: left; }
        }

        @media (max-width: 640px) {
            .balance-strip { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════════════════════════════════
     SIDEBAR
═══════════════════════════════════════════════ -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <div class="logo-icon">
             <img 
                src="murna.jpg" 
                alt="Murna Logo"
                style="
                    width:70px;
                    height:70px;
                    object-fit:cover;
                    border-radius:20px;
                    padding:9px;
                    background:#fff;
                    box-shadow:0 2px 8px rgba(0,0,0,0.15);
                    border:2px solid #00A651;
                "
                >
        </div>
        <div class="brand-text">
            Murna Foundation
            <small>NIN Portal</small>
        </div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="verify.php" class="nav-link active">
                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                NIN Verification
            </a>
            <a href="payment.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Fund Wallet
            </a>
        </div>
        <div class="nav-section">
            <div class="nav-label">Account</div>
            <a href="dashboard.php#history" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Transaction History
            </a>
            <a href="logout.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Log Out
            </a>
        </div>
    </div>
</nav>

<!-- ═══════════════════════════════════════════════
     MAIN CONTENT
═══════════════════════════════════════════════ -->
<main class="main">

    <!-- Page Header -->
    <div class="page-header">
        <div class="breadcrumb">
            <a href="dashboard.php">Dashboard</a>
            <span class="breadcrumb-sep">/</span>
            <a href="verify.php">Verification</a>
            <?php if (in_array($type, $valid_types)): ?>
                <span class="breadcrumb-sep">/</span>
                <span class="breadcrumb-current"><?= htmlspecialchars($type_meta[$type]['label']) ?></span>
            <?php endif; ?>
        </div>
        <div class="page-title">NIN Verification</div>
        <div class="page-subtitle">
            Verify individuals using Nigeria's National Identity Number database.
        </div>
    </div>

    <!-- Balance Strip -->
    <div class="balance-strip">
        <div class="balance-strip-left">
            <div class="balance-dot"></div>
            <div>
                <div class="balance-strip-label">Wallet Balance</div>
                <div class="balance-strip-value">NGN <?= number_format($balance, 2) ?></div>
            </div>
        </div>
        <div class="balance-strip-cost">
    <?php if (in_array($type, $valid_types)): ?>
        Cost for <?= htmlspecialchars($type_meta[$type]['label']) ?>: <strong>NGN <?= number_format($costs[$type], 2) ?></strong>
    <?php else: ?>
        Costs: NIN <?= number_format($costs['nin'], 2) ?> &nbsp;|&nbsp; Phone <?= number_format($costs['phone'], 2) ?> &nbsp;|&nbsp; Demo <?= number_format($costs['demographic'], 2) ?>
    <?php endif; ?>
</div>
        <a href="payment.php" class="btn-fund">
            <svg style="width:15px;height:15px;fill:none;stroke:white;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 24 24">
                <path d="M12 5v14m-7-7h14"/>
            </svg>
            Fund Wallet
        </a>
    </div>


    <?php
    // ═══════════════════════════════════════════════════════════════════
    // CASE 1: No type selected — show the type selector cards
    // ═══════════════════════════════════════════════════════════════════
    if (!in_array($type, $valid_types)):
    ?>

    <div class="selector-heading">Choose Verification Method</div>
    <div class="selector-sub">
        Select how you want to look up an identity record. Each query costs
        <strong style="color:var(--white);">NGN <?= number_format($costs['nin'], 2) ?></strong>.
    </div>

    <div class="type-cards">

        <!-- NIN Card -->
        <a href="verify.php?type=nin" class="type-card <?= $balance < $costs['nin'] ? 'disabled' : '' ?>">
            <div class="type-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                </svg>
            </div>
            <div class="type-card-title">By NIN Number</div>
            <div class="type-card-desc">
                Verify a person directly by their 11-digit National Identification Number. Returns full name, date of birth, address, and photo.
            </div>
           <div class="type-card-cost">NGN <?= number_format($costs['nin'], 2) ?> per query</div>
            <div class="type-card-arrow">
                <svg viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>
            </div>
        </a>

        <!-- Phone Card -->
        <a href="verify.php?type=phone" class="type-card <?= $balance < $costs['phone'] ? 'disabled' : '' ?>">
            <div class="type-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </div>
            <div class="type-card-title">By Phone Number</div>
            <div class="type-card-desc">
                Retrieve NIN-linked identity records using a Nigerian phone number. Useful when the NIN is not readily available.
            </div>
           <div class="type-card-cost">NGN <?= number_format($costs['phone'], 2) ?> per query</div>
            <div class="type-card-arrow">
                <svg viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>
            </div>
        </a>

        <!-- Demographic Card -->
        <a href="verify.php?type=demographic" class="type-card <?= $balance < $costs['demographic'] ? 'disabled' : '' ?>">
            <div class="type-card-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="type-card-title">By Demographics</div>
            <div class="type-card-desc">
                Look up identity records using a person's first name, surname, and date of birth. Ideal for cross-referencing applicant data.
            </div>
            <div class="type-card-cost">NGN <?= number_format($costs['demographic'], 2) ?> per query</div>
            <div class="type-card-arrow">
                <svg viewBox="0 0 24 24"><path d="M5 12h14m-7-7l7 7-7 7"/></svg>
            </div>
        </a>

    </div>

    <?php if ($balance < $costs['nin']): ?>
        <div style="margin-top:24px; font-size:14px; color:var(--red); display:flex; align-items:center; gap:8px;">
            <svg style="width:16px;height:16px;fill:none;stroke:var(--red);stroke-width:2;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/>
            </svg>
            Verification options are disabled. Please fund your wallet with at least
            <strong style="color:var(--white);">NGN <?= number_format($costs['nin'], 2) ?></strong> to proceed.
            &nbsp;<a href="payment.php" style="color:var(--green-light); font-weight:700; text-decoration:none;">Fund Now</a>
        </div>
    <?php endif; ?>


    <?php
    // ═══════════════════════════════════════════════════════════════════
    // CASE 2: Type selected but insufficient balance
    // ═══════════════════════════════════════════════════════════════════
    elseif (in_array($type, $valid_types) && $balance < $costs[$type] && $result_data === null):
    ?>

    <div class="insufficient-banner">
        <div class="insufficient-icon">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"/>
                <path d="M12 8v4m0 4h.01"/>
            </svg>
        </div>
        <div>
            <div class="insufficient-title">Insufficient Wallet Balance</div>
            <div class="insufficient-msg">
              You need at least <strong>NGN <?= number_format($costs[$type], 2) ?></strong> in your wallet
                to perform a <strong><?= htmlspecialchars($type_meta[$type]['label']) ?></strong> verification.<br>
                Your current balance is <strong>NGN <?= number_format($balance, 2) ?></strong>.
                Please fund your wallet to continue.
            </div>
            <div class="insufficient-actions">
                <a href="payment.php" class="btn-fund">Fund My Wallet</a>
                <a href="verify.php" class="btn-back-link">Back to Verification</a>
            </div>
        </div>
    </div>


    <?php
    // ═══════════════════════════════════════════════════════════════════
    // CASE 3: Type selected, balance sufficient — show form (and results)
    // ═══════════════════════════════════════════════════════════════════
    else:
    ?>

    <?php if ($result_data === null): ?>
    <!-- ── Verification Form ── -->
    <div class="verify-form-wrap">

        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
            <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <div class="verify-form-card">
            <!-- Form Header -->
            <div class="verify-form-header">
                <div class="verify-form-header-icon">
                    <?php if ($type === 'nin'): ?>
                    <svg viewBox="0 0 24 24">
                        <path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/>
                    </svg>
                    <?php elseif ($type === 'phone'): ?>
                    <svg viewBox="0 0 24 24">
                        <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    <?php else: ?>
                    <svg viewBox="0 0 24 24">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="verify-form-title">
                        Verify by <?= htmlspecialchars($type_meta[$type]['label']) ?>
                    </div>
                    <div class="verify-form-subtitle">
                        Fill in the required details to look up the identity record.
                    </div>
                </div>
            </div>

            <!-- Form Body -->
            <div class="verify-form-body">
                <form method="POST" action="verify.php?type=<?= htmlspecialchars($type) ?>" autocomplete="off">
                    <input type="hidden" name="ver_type" value="<?= htmlspecialchars($type) ?>">

                    <!-- ── NIN Form ────────────────────────────── -->
                    <?php if ($type === 'nin'): ?>
                    <div class="form-group">
                        <label>NIN Number <span class="req">*</span></label>
                        <div class="input-wrap" id="wrap-nin">
                            <svg class="input-icon" viewBox="0 0 24 24">
                                <rect x="2" y="5" width="20" height="14" rx="2"/>
                                <path d="M2 10h20"/>
                            </svg>
                            <input
                                type="text"
                                name="nin_number"
                                id="nin_number"
                                maxlength="11"
                                placeholder="Enter 11-digit NIN"
                                inputmode="numeric"
                                pattern="[0-9]{11}"
                                value="<?= htmlspecialchars($_POST['nin_number'] ?? '') ?>"
                                required
                            >
                        </div>
                        <div class="input-hint">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                            Your NIN is an 11-digit number found on your NIMC slip or national ID card.
                        </div>
                    </div>

                    <!-- ── Phone Form ──────────────────────────── -->
                    <?php elseif ($type === 'phone'): ?>
                    <div class="form-group">
                        <label>Phone Number <span class="req">*</span></label>
                        <div class="input-wrap" id="wrap-phone">
                            <svg class="input-icon" viewBox="0 0 24 24">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            <input
                                type="tel"
                                name="phone_number"
                                id="phone_number"
                                maxlength="14"
                                placeholder="e.g. 08012345678"
                                value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>"
                                required
                            >
                        </div>
                        <div class="input-hint">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                            Enter the Nigerian phone number linked to the NIN (e.g. 0801 234 5678).
                        </div>
                    </div>

                    <!-- ── Demographic Form ────────────────────── -->
                    <?php elseif ($type === 'demographic'): ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name <span class="req">*</span></label>
                            <div class="input-wrap" id="wrap-firstname">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <input
                                    type="text"
                                    name="first_name"
                                    id="first_name"
                                    placeholder="e.g. Amina"
                                    value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                    required
                                >
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Surname <span class="req">*</span></label>
                            <div class="input-wrap" id="wrap-surname">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                                <input
                                    type="text"
                                    name="surname"
                                    id="surname"
                                    placeholder="e.g. Mohammed"
                                    value="<?= htmlspecialchars($_POST['surname'] ?? '') ?>"
                                    required
                                >
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date of Birth <span class="req">*</span></label>
                            <div class="input-wrap" id="wrap-dob">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/>
                                </svg>
                                <input
                                    type="date"
                                    name="dob"
                                    id="dob"
                                    max="<?= date('Y-m-d') ?>"
                                    value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>"
                                    required
                                >
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Gender <span class="req">*</span></label>
                            <div class="input-wrap" id="wrap-gender">
                                <svg class="input-icon" viewBox="0 0 24 24">
                                    <path d="M12 12a5 5 0 100-10 5 5 0 000 10zM12 14c-5.33 0-8 2.67-8 8h16c0-5.33-2.67-8-8-8z"/>
                                </svg>
                                <select name="gender" id="gender" required>
                                    <option value="">Select Gender</option>
                                    <option value="m" <?= (isset($_POST['gender']) && $_POST['gender'] === 'm') ? 'selected' : '' ?>>Male</option>
                                    <option value="f" <?= (isset($_POST['gender']) && $_POST['gender'] === 'f') ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="input-hint" style="margin-bottom: 22px; margin-top: -10px;">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                        Enter the subject's date of birth and gender exactly as registered with NIMC.
                    </div>
                    <?php endif; ?>

                    <button type="submit" class="btn-verify">
                        <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Verify Now
                    </button>

                    <div class="cost-notice">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                        NGN <strong><?= number_format($costs[$type], 2) ?></strong> will be deducted from your wallet upon submission.
                    </div>
                </form>
            </div>
        </div>

        <a href="verify.php" style="display:inline-flex; align-items:center; gap:7px; font-size:13px; color:var(--text-muted); text-decoration:none; transition:color 0.2s;" onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--text-muted)'">
            <svg style="width:15px;height:15px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 24 24">
                <path d="M19 12H5m7-7l-7 7 7 7"/>
            </svg>
            Back to Verification Methods
        </a>

    </div><!-- end .verify-form-wrap -->

    <?php else: ?>
    <!-- ── Verification Result ──────────────────────────────────── -->
    <div class="result-card">
        <div class="result-header">
            <div class="result-success-icon">
                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <div class="result-header-title">Identity Record Found</div>
                <div class="result-header-sub">
                    Verified via <?= htmlspecialchars($type_meta[$type]['label']) ?> &bull;
                    <?= date('d M Y, H:i') ?>
                </div>
            </div>
        </div>

        <div class="result-body">
            <!-- Photo Column -->
            <div class="result-photo-col">
                <?php
               $photo_b64 = $result_data['image'] ?? ($result_data['photo'] ?? null);
                  
                if ($photo_b64):
                   
                ?>
                    <img
                        src="data:image/jpeg;base64,<?= htmlspecialchars($photo_b64) ?>"
                        alt="NIN Photo"
                        class="result-photo"
                    >
                <?php else: ?>
                    <div class="result-photo-placeholder">
                        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z"/></svg>
                        No Photo
                    </div>
                <?php endif; ?>
                <div style="font-size:11px; color:var(--text-muted); text-align:center; max-width:110px; line-height:1.4;">
                    Official NIMC Photo
                </div>
            </div>

            <!-- Fields -->
            <div class="result-fields">
               <?php
// FIX #6: Complete field map covering all known RandaVerify response keys
$fields = [
    'NIN'                => $result_data['nin']               ?? null,
    'First Name'         => $result_data['fname']             ?? $result_data['firstname']  ?? null,
    'Middle Name'        => $result_data['mname']             ?? $result_data['middlename'] ?? null,
    'Last Name'          => $result_data['lname']             ?? $result_data['lastname']   ?? null,
    'Full Name'          => trim(
                              ($result_data['fname']  ?? $result_data['firstname']  ?? '') . ' ' .
                              ($result_data['mname']  ?? $result_data['middlename'] ?? '') . ' ' .
                              ($result_data['lname']  ?? $result_data['lastname']   ?? '')
                           ),
    'Gender'             => $result_data['gender']            ?? null,
    'Date of Birth'      => $result_data['dob']               ?? null,
    'Birth Country'      => $result_data['birthCountry']      ?? null,
    'Nationality'        => $result_data['nationality']       ?? null,
    'Phone'              => $result_data['phone']             ?? null,
    'Email'              => $result_data['email']             ?? null,
    'Residence Address'  => $result_data['residenceAdress']   ?? $result_data['residenceAddress'] ?? null,
    'Residence Town'     => $result_data['residenceTown']     ?? null,
    'Residence LGA'      => $result_data['residenceLga']      ?? null,
    'Residence State'    => $result_data['residenceState']    ?? null,
    'State of Origin'    => $result_data['stateOfOrigin']     ?? null,
    'LGA of Origin'      => $result_data['lgaOfOrigin']       ?? null,
    'Marital Status'     => $result_data['maritalStatus']     ?? null,
    'Height'             => $result_data['height']            ?? null,
    'Employment Status'  => $result_data['employmentStatus']  ?? null,
    'Education Level'    => $result_data['educationLevel']    ?? null,
    'NOK Full Name'      => $result_data['nokFullName']       ?? null,
    'NOK Address'        => $result_data['nokAddress']        ?? null,
    'NOK State'          => $result_data['nokState']          ?? null,
    'Reference ID'       => $result_data['reference_id']      ?? $result_data['transactionRef'] ?? null,
];
 foreach ($fields as $key => $val):
        if (!empty($val)):
    ?>
            <div class="result-field">
                <div class="result-field-key"><?= htmlspecialchars($key) ?></div>
                <div class="result-field-val <?= $key === 'NIN' ? 'mono' : '' ?>">
                    <?= htmlspecialchars(ucwords(strtolower($val))) ?>
                </div>
            </div>
    <?php
        endif;
    endforeach;
    ?>
            </div>
        </div>

        <div class="result-footer">
            <div class="result-footer-note">
                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Data sourced from NIMC via secure API. Confidential.
            </div>

            
<?php if ($result_data): ?>
    <!-- ... your result display code ... -->
    <div class="download-buttons">
        <a href="download_personal_slip.php" class="btn-new-verify">📄 Personal Info Slip (PDF)</a>
        <a href="download_premium_slip.php" class="btn-new-verify">⭐ Premium NIN Slip (PDF)</a>
        <!-- "New Verification" button as before -->
      
    </div>
 <?php endif; ?>



                        <div class="result-footer" style="flex-direction: column; gap: 12px;">
                <div style="display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;">
                    <a href="download_nin_pdf.php?format=verification" class="btn-new-verify">💳 Improved NIN Slip</a>
                    <a href="download_nin_pdf.php?format=premium" class="btn-new-verify">📄 Verification Page</a>
                    <a href="download_nin_pdf.php?format=card" class="btn-new-verify"> Regular NIN slip</a>
                   
                    <a href="verify.php" class="btn-new-verify">🔄 New Verification</a>
                </div>
            </div>
        </div>

    </div>
    <?php endif; ?>

    <?php endif; ?>

<?php
// Show password change form if required
if (isset($show_password_change_form) && $show_password_change_form === true):
?>
<div class="verify-form-wrap">
    <div class="verify-form-card">
        <div class="verify-form-header">
            <div class="verify-form-header-icon">
                <svg viewBox="0 0 24 24"><path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            </div>
            <div>
                <div class="verify-form-title">Password Change Required</div>
                <div class="verify-form-subtitle">This is your first login. Please set a new password.</div>
            </div>
        </div>
        <div class="verify-form-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="">
                <input type="hidden" name="change_password" value="1">
                <input type="hidden" name="temp_token" value="<?= htmlspecialchars($_SESSION['temp_token'] ?? '') ?>">
                <div class="form-group">
                    <label>New Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <input type="password" name="new_password" required minlength="8">
                    </div>
                    <div class="input-hint">Minimum 8 characters</div>
                </div>
                <div class="form-group">
                    <label>Confirm Password <span class="req">*</span></label>
                    <div class="input-wrap">
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="btn-verify">Change Password & Continue</button>
            </form>
        </div>
    </div>
</div>
<?php
    // Stop further output (no verification form or results)
    exit();
endif;
?>


</main>

<script>
    // Highlight SVG icon when input is focused
    document.querySelectorAll('.input-wrap input').forEach(function(input) {
        var wrap = input.closest('.input-wrap');
        input.addEventListener('focus', function() {
            if (wrap) wrap.classList.add('focused');
        });
        input.addEventListener('blur', function() {
            if (wrap) wrap.classList.remove('focused');
        });
    });

    // NIN: allow only digits
    var ninInput = document.getElementById('nin_number');
    if (ninInput) {
        ninInput.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '').slice(0, 11);
        });
    }
</script>

</body>
</html>

<?php
require_once 'config.php';
requireLogin();

$db = getDB();
$user_id = $_SESSION['user_id'];

// Fetch user
$stmt = $db->prepare("SELECT u.*, a.balance, a.account_number FROM users u JOIN accounts a ON u.id = a.user_id WHERE u.id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Recent transactions
$txn_stmt = $db->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 8");
$txn_stmt->bind_param('i', $user_id);
$txn_stmt->execute();
$transactions = $txn_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent verifications
$ver_stmt = $db->prepare("SELECT * FROM verification_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$ver_stmt->bind_param('i', $user_id);
$ver_stmt->execute();
$verifications = $ver_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Stats
$total_verifications = count($verifications);
$ver_count_stmt = $db->prepare("SELECT COUNT(*) as cnt FROM verification_logs WHERE user_id = ?");
$ver_count_stmt->bind_param('i', $user_id);
$ver_count_stmt->execute();
$ver_count = $ver_count_stmt->get_result()->fetch_assoc()['cnt'];


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
$min_required = min($costs); // smallest cost among the three


$display_name = $user['account_type'] === 'individual' ? $user['full_name'] : $user['org_name'];
$balance = floatval($user['balance']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Murna Foundation</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
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

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--black);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
            background: var(--blue);
            display: flex;
            flex-direction: column;
            padding: 0;
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
            font-size: 14px;
            font-weight: 700;
            color: var(--white);
            line-height: 1.3;
        }

        .brand-text small {
            display: block;
            font-size: 11px;
            font-weight: 400;
            color: var(--green-light);
            opacity: 0.8;
        }

        .sidebar-nav {
            flex: 1;
            padding: 20px 0;
            overflow-y: auto;
        }

        .nav-section {
            padding: 0 16px;
            margin-bottom: 8px;
        }

        .nav-label {
            font-size: 10px;
            font-weight: 700;
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
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 2px;
            transition: all 0.2s;
        }

        .nav-link svg { width: 18px; height: 18px; fill: currentColor; flex-shrink: 0; }

        .nav-link:hover, .nav-link.active {
            background: rgba(0,166,81,0.12);
            color: var(--green-light);
        }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid rgba(255,255,255,0.07);
        }

        .user-badge {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
        }

        .user-avatar {
            width: 38px; height: 38px;
            background: linear-gradient(135deg, var(--green-dark), var(--blue-mid));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-family: 'Syne', sans-serif;
            font-weight: 700;
            font-size: 15px;
            color: white;
            flex-shrink: 0;
        }

        .user-info { flex: 1; min-width: 0; }
        .user-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .user-type {
            font-size: 11px;
            color: var(--green-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── Main Content ── */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 32px;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 32px;
        }

        .page-title {
            font-family: 'Syne', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 4px;
        }

        .page-subtitle {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* ── Balance Card ── */
        .balance-card {
            background: linear-gradient(135deg, #003f8a 0%, #00270f 50%, #007A3D 100%);
            border-radius: 20px;
            padding: 36px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,166,81,0.2);
        }

        .balance-card::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 240px; height: 240px;
            background: rgba(0,166,81,0.15);
            border-radius: 50%;
        }

        .balance-card::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 40%;
            width: 180px; height: 180px;
            background: rgba(0,45,98,0.3);
            border-radius: 50%;
        }

        .balance-content { position: relative; z-index: 2; }

        .balance-label {
            font-size: 13px;
            font-weight: 600;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 12px;
        }

        .balance-amount {
            font-family: 'Syne', sans-serif;
            font-size: 52px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 24px;
            line-height: 1;
        }

        .balance-amount span {
            font-size: 24px;
            font-weight: 400;
            opacity: 0.7;
        }

        .balance-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-credit {
            padding: 12px 24px;
            background: var(--green);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-credit:hover {
            background: var(--green-light);
            transform: translateY(-1px);
        }

        .btn-outline-white {
            padding: 12px 24px;
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.25);
            border-radius: 10px;
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-outline-white:hover { background: rgba(255,255,255,0.15); }

        .balance-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-top: 16px;
        }

        /* ── Stats Grid ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }

        .stat-card {
            background: var(--card);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border);
        }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 4px;
        }

        .stat-note { font-size: 12px; color: var(--text-muted); }

        /* ── Verify CTA ── */
        .verify-cta {
            background: var(--card);
            border-radius: 20px;
            padding: 28px;
            margin-bottom: 28px;
            border: 1px solid var(--border);
        }

        .verify-cta h3 {
            font-family: 'Syne', sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 8px;
        }

        .verify-cta p {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 20px;
        }

        .verify-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .verify-option {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 20px 14px;
            background: var(--black-soft);
            border: 2px solid var(--border);
            border-radius: 14px;
            text-decoration: none;
            color: var(--text);
            transition: all 0.25s;
            text-align: center;
        }

        .verify-option:hover {
            border-color: var(--green);
            background: rgba(0,166,81,0.06);
            color: var(--green-light);
            transform: translateY(-2px);
        }

        .verify-option-icon {
            width: 44px; height: 44px;
            background: rgba(0,166,81,0.12);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }

        .verify-option-icon svg {
            width: 22px; height: 22px;
            fill: var(--green-light);
        }

        .verify-option-label {
            font-family: 'Syne', sans-serif;
            font-size: 14px;
            font-weight: 700;
        }

        .verify-option-cost {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* ── Tables ── */
        .section-title {
            font-family: 'Syne', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 16px;
        }

        .table-card {
            background: var(--card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 28px;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: rgba(255,255,255,0.03);
            padding: 12px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tbody td {
            padding: 14px 20px;
            font-size: 14px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
            color: var(--text);
        }

        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: rgba(255,255,255,0.02); }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-success { background: rgba(0,166,81,0.15); color: var(--green-light); }
        .badge-pending { background: rgba(255,193,7,0.15); color: #ffc107; }
        .badge-failed  { background: rgba(220,53,69,0.15); color: #ff6b6b; }
        .badge-credit  { background: rgba(0,166,81,0.15); color: var(--green-light); }
        .badge-debit   { background: rgba(220,53,69,0.15); color: #ff6b6b; }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
            font-size: 14px;
        }

        @media (max-width: 1024px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            .verify-options { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main { margin-left: 0; padding: 20px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
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
            <a href="dashboard.php" class="nav-link active">
                <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="verify.php" class="nav-link">
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
                History
            </a>
            <a href="logout.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Log Out
            </a>
        </div>
    </div>
    <div class="sidebar-footer">
        <div class="user-badge">
            <div class="user-avatar"><?= strtoupper(substr($display_name, 0, 1)) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars(substr($display_name, 0, 22)) ?></div>
                <div class="user-type"><?= $user['account_type'] ?></div>
            </div>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<main class="main">
    <div class="page-header">
        <div class="page-title">Good <?= date('H') < 12 ? 'Morning' : (date('H') < 17 ? 'Afternoon' : 'Evening') ?>, <?= htmlspecialchars(explode(' ', $display_name)[0]) ?></div>
        <div class="page-subtitle">Welcome to your Murna Foundation dashboard &mdash; <?= date('l, d F Y') ?></div>
    </div>

    <!-- BALANCE CARD -->
    <div class="balance-card">
        <div class="balance-content">
            <div class="balance-label">Wallet Balance</div>
            <div class="balance-amount">
                <span>NGN</span> <?= number_format($balance, 2) ?>
            </div>
            <div class="balance-actions">
                <a href="payment.php" class="btn-credit">
                    + Fund Wallet
                </a>
               <?php if ($balance >= $min_required): ?>
                    <a href="verify.php" class="btn-outline-white">Start Verification</a>
                <?php else: ?>
                    <span style="font-size:13px; color:rgba(255,255,255,0.5); line-height:1.4; max-width:260px;">
                        Fund your wallet with at least NGN <?= number_format($costs['nin'], 2) ?> to start verifying NINs.
                    </span>
                <?php endif; ?>
            </div>
            <div class="balance-meta">
                Account No: <?= htmlspecialchars($user['account_number']) ?> &bull; <?= strtoupper($user['account_type']) ?> Account
            </div>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Verifications</div>
            <div class="stat-value"><?= $ver_count ?></div>
            <div class="stat-note">All time</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Cost Per Verification</div>
            <div class="stat-value"><?= number_format($costs['nin']) ?></div>
<div class="stat-note">NGN per NIN query</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Account Status</div>
            <div class="stat-value" style="font-size:20px; color:var(--green-light); margin-top:8px;">Active</div>
            <div class="stat-note"><?= ucfirst($user['account_type']) ?> Account</div>
        </div>
    </div>

    <!-- VERIFY CTA -->
    <div class="verify-cta">
        <h3>Perform NIN Verification</h3>
       <p>Verification costs vary by method (NIN, Phone, Demographic) and are deducted from your wallet balance.</p>
        <div class="verify-options">
            <a href="verify.php?type=nin" class="verify-option">
                <div class="verify-option-icon">
                    <svg viewBox="0 0 24 24"><path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                </div>
                <div class="verify-option-label">By NIN Number</div>
                <div class="verify-option-cost"><div class="verify-option-cost">NGN <?= number_format($costs['nin']) ?> per query</div>
</div>
            </a>
            <a href="verify.php?type=phone" class="verify-option">
                <div class="verify-option-icon">
                    <svg viewBox="0 0 24 24"><path d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                </div>
                <div class="verify-option-label">By Phone Number</div>
                <div class="verify-option-cost"><div class="verify-option-cost">NGN <?= number_format($costs['phone']) ?> per query</div></div>
            </a>
            <a href="verify.php?type=demographic" class="verify-option">
                <div class="verify-option-icon">
                    <svg viewBox="0 0 24 24"><path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="verify-option-label">By Demographics</div>
                <div class="verify-option-cost"><div class="verify-option-cost">NGN <?= number_format($costs['demographic']) ?> per query</div></div>
            </a>
        </div>
    </div>

    <!-- TRANSACTIONS -->
    <div class="table-card" id="history">
        <div class="table-header">
            <div class="section-title" style="margin:0;">Recent Transactions</div>
        </div>
        <?php if (empty($transactions)): ?>
            <div class="empty-state">No transactions yet. Fund your wallet to get started.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $txn): ?>
                <tr>
                    <td style="font-family:monospace; font-size:12px;"><?= htmlspecialchars($txn['reference']) ?></td>
                    <td><span class="badge badge-<?= $txn['type'] ?>"><?= strtoupper($txn['type']) ?></span></td>
                    <td>NGN <?= number_format($txn['amount'], 2) ?></td>
                    <td><?= ucfirst($txn['method']) ?></td>
                    <td><span class="badge badge-<?= $txn['status'] ?>"><?= strtoupper($txn['status']) ?></span></td>
                    <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y H:i', strtotime($txn['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- RECENT VERIFICATIONS -->
    <div class="table-card">
        <div class="table-header">
            <div class="section-title" style="margin:0;">Recent Verifications</div>
        </div>
        <?php if (empty($verifications)): ?>
            <div class="empty-state">No verifications performed yet.</div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Query</th>
                    <th>Cost</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($verifications as $v): ?>
                <tr>
                    <td><?= ucfirst($v['verification_type']) ?></td>
                    <td style="font-family:monospace; font-size:13px;"><?= htmlspecialchars(substr($v['query_input'], 0, 30)) ?>...</td>
                    <td>NGN <?= number_format($v['cost'], 2) ?></td>
                    <td><span class="badge badge-<?= $v['status'] === 'success' ? 'success' : 'failed' ?>"><?= strtoupper($v['status']) ?></span></td>
                    <td style="font-size:12px; color:var(--text-muted);"><?= date('d M Y H:i', strtotime($v['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</main>
</body>
</html>

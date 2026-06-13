<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$balance = getUserBalance($user_id);
$bank_name = getSetting('bank_name');
$bank_acc_num = getSetting('bank_account_number');
$bank_acc_name = getSetting('bank_account_name');
$paystack_pub = PAYSTACK_PUBLIC_KEY;

$db = getDB();
$user_stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fund Wallet - Murna Foundation</title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://js.paystack.co/v1/inline.js"></script>
    <style>
        :root {
            --green: #00A651; --green-light: #00C960; --green-dark: #007A3D;
            --blue: #002D62; --black: #080f1a; --black-soft: #0d1625;
            --card: #0f1e38; --border: rgba(255,255,255,0.07);
            --text: #c8d8f0; --text-muted: #6e88ab; --white: #ffffff;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', sans-serif; background: var(--black); color: var(--text); min-height: 100vh; display: flex; }

        .sidebar {
            width: 260px; background: var(--blue); display: flex; flex-direction: column;
            padding: 0; position: fixed; top: 0; left: 0; bottom: 0; z-index: 100;
            border-right: 1px solid rgba(0,166,81,0.15);
        }
        .sidebar-brand { padding: 28px 24px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; gap: 12px; }
        .logo-icon { width: 44px; height: 44px; background: linear-gradient(135deg, var(--green), var(--green-dark)); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 800; color: white; }
        .brand-text { font-family: 'Syne', sans-serif; font-size: 14px; font-weight: 700; color: var(--white); line-height: 1.3; }
        .brand-text small { display: block; font-size: 11px; font-weight: 400; color: var(--green-light); }
        .sidebar-nav { flex: 1; padding: 20px 0; }
        .nav-section { padding: 0 16px; margin-bottom: 8px; }
        .nav-label { font-size: 10px; font-weight: 700; color: var(--text-muted); letter-spacing: 2px; text-transform: uppercase; padding: 8px 10px 6px; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 14px; border-radius: 10px; color: #7a97be; text-decoration: none; font-size: 14px; font-weight: 500; margin-bottom: 2px; transition: all 0.2s; }
        .nav-link svg { width: 18px; height: 18px; fill: currentColor; }
        .nav-link:hover, .nav-link.active { background: rgba(0,166,81,0.12); color: var(--green-light); }

        .main { margin-left: 260px; flex: 1; padding: 40px; }

        .page-title { font-family: 'Syne', sans-serif; font-size: 28px; font-weight: 800; color: var(--white); margin-bottom: 6px; }
        .page-subtitle { font-size: 14px; color: var(--text-muted); margin-bottom: 36px; }

        .balance-mini {
            display: inline-flex; align-items: center; gap: 10px;
            background: var(--card); border: 1px solid var(--border);
            border-radius: 12px; padding: 14px 20px; margin-bottom: 32px;
        }
        .balance-mini-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .balance-mini-value { font-family: 'Syne', sans-serif; font-size: 22px; font-weight: 800; color: var(--green-light); }

        .payment-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; max-width: 900px; }

        .payment-card {
            background: var(--card); border-radius: 20px; border: 1px solid var(--border); overflow: hidden;
        }

        .payment-card-header {
            padding: 24px 28px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 14px;
        }

        .payment-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
        }

        .payment-icon svg { width: 24px; height: 24px; fill: white; }
        .payment-icon.green { background: linear-gradient(135deg, var(--green-dark), var(--green)); }
        .payment-icon.blue { background: linear-gradient(135deg, var(--blue), #0056b8); }

        .payment-card-title { font-family: 'Syne', sans-serif; font-size: 18px; font-weight: 700; color: var(--white); }
        .payment-card-sub { font-size: 12px; color: var(--text-muted); margin-top: 2px; }

        .payment-card-body { padding: 28px; }

        label { display: block; font-size: 12px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }

        input[type="number"], input[type="text"] {
            width: 100%; padding: 14px 16px;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; color: var(--white); font-size: 16px;
            font-family: 'DM Sans', sans-serif; outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(0,166,81,0.12); }

        .form-group { margin-bottom: 20px; }

        .preset-amounts { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 20px; }
        .preset-btn {
            padding: 8px 16px; background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 8px;
            color: var(--text); font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.2s; font-family: 'DM Sans', sans-serif;
        }
        .preset-btn:hover { border-color: var(--green); color: var(--green-light); }

        .btn-pay {
            width: 100%; padding: 15px; border: none; border-radius: 12px;
            font-family: 'Syne', sans-serif; font-size: 16px; font-weight: 700;
            cursor: pointer; transition: all 0.25s;
        }
        .btn-pay.green {
            background: linear-gradient(135deg, var(--green-dark), var(--green));
            color: white; box-shadow: 0 6px 20px rgba(0,166,81,0.25);
        }
        .btn-pay.green:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(0,166,81,0.35); }

        /* Bank Transfer Info */
        .bank-info-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 14px 0; border-bottom: 1px solid rgba(255,255,255,0.06);
        }
        .bank-info-item:last-child { border-bottom: none; }
        .bank-info-key { font-size: 13px; color: var(--text-muted); }
        .bank-info-val { font-size: 15px; font-weight: 600; color: var(--white); }
        .copy-btn {
            background: rgba(0,166,81,0.15); border: none; color: var(--green-light);
            font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 6px;
            cursor: pointer; margin-left: 10px; font-family: 'DM Sans', sans-serif;
        }
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
.payment-card {
            background: var(--card); border-radius: 20px; border: 1px solid var(--border); overflow: hidden;
        }

        .notice {
            background: rgba(255,193,7,0.08); border: 1px solid rgba(255,193,7,0.2);
            border-radius: 10px; padding: 14px 16px; margin-top: 20px;
            font-size: 13px; color: #ffd60a; line-height: 1.6;
        }

        .bank-transfer-form { margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px; }

        @media (max-width: 900px) {
            .payment-grid { grid-template-columns: 1fr; }
            .main { margin-left: 0; padding: 20px; }
            .sidebar { display: none; }
        }
    </style>
</head>
<body>

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
        <div class="brand-text">Murna Foundation<small>NIN Portal</small></div>
    </div>
    <div class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="verify.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                NIN Verification
            </a>
            <a href="payment.php" class="nav-link active">
                <svg viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Fund Wallet
            </a>
            <a href="logout.php" class="nav-link">
                <svg viewBox="0 0 24 24"><path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                Log Out
            </a>
        </div>
    </div>
</nav>

<main class="main">
    <div class="page-title">Fund Your Wallet</div>
    <div class="page-subtitle">Top up your account balance to perform NIN verifications</div>

    <div class="balance-strip">
        <div>
            <div class="balance-mini-label">Current Balance</div>
            <div class="balance-mini-value">NGN <?= number_format($balance, 2) ?></div>
        </div>
    </div>

    <div class="balance-strip">

        <!-- PAYSTACK CARD -->
        <div class="payment-card">
            <div class="payment-card-header">
                <div class="payment-icon green">
                    <svg viewBox="0 0 24 24"><path d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <div>
                    <div class="payment-card-title">Pay with Paystack</div>
                    <div class="payment-card-sub">Card, Bank Transfer, USSD, QR Code</div>
                </div>
            </div>
            <div class="payment-card-body">
                <div class="form-group">
                    <label>Select or Enter Amount (NGN)</label>
                    <div class="preset-amounts">
                        <button class="preset-btn" onclick="setAmount(500)">500</button>
                        <button class="preset-btn" onclick="setAmount(1000)">1,000</button>
                        <button class="preset-btn" onclick="setAmount(2500)">2,500</button>
                        <button class="preset-btn" onclick="setAmount(5000)">5,000</button>
                        <button class="preset-btn" onclick="setAmount(10000)">10,000</button>
                    </div>
                    <input type="number" id="paystack_amount" min="100" step="50" placeholder="Enter amount (min NGN 100)" value="1000">
                </div>
                <button class="btn-pay green" onclick="payWithPaystack()">Pay Now via Paystack</button>
            </div>
        </div>

       
    </div>
</main>

<script>
function setAmount(val) {
    document.getElementById('paystack_amount').value = val;
}

function copyText(text) {
    navigator.clipboard.writeText(text).then(() => alert('Account number copied!'));
}

function payWithPaystack() {
    const amountInput = document.getElementById('paystack_amount');
    const amount = parseFloat(amountInput.value);

    if (!amount || amount < 100) {
        alert('Minimum amount is NGN 100.');
        return;
    }

    const ref = 'MF_' + Date.now() + '_' + Math.floor(Math.random() * 9999);

    const handler = PaystackPop.setup({
        key: '<?= $paystack_pub ?>',
        email: '<?= htmlspecialchars($user['email']) ?>',
        amount: Math.round(amount * 100), // Paystack uses kobo
        currency: 'NGN',
        ref: ref,
        metadata: {
            user_id: <?= $user_id ?>,
            custom_fields: [
                { display_name: "Payment For", variable_name: "payment_for", value: "Wallet Top-up" }
            ]
        },
        callback: function(response) {
            // Verify on backend
            window.location.href = 'payment_callback.php?reference=' + response.reference;
        },
        onClose: function() {
            alert('Payment window closed. Complete payment to fund your wallet.');
        }
    });
    handler.openIframe();
}
</script>
</body>
</html>

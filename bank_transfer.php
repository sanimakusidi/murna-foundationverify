<?php
require_once 'config.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: payment.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$amount = floatval($_POST['amount'] ?? 0);
$bank_ref = trim($_POST['bank_ref'] ?? '');
$sender_name = trim($_POST['sender_name'] ?? '');

if ($amount < 100 || empty($bank_ref) || empty($sender_name)) {
    header('Location: payment.php?error=invalid_data');
    exit();
}

$db = getDB();

// Check duplicate reference
$check = $db->prepare("SELECT id FROM transactions WHERE reference = ?");
$check->bind_param('s', $bank_ref);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    header('Location: payment.php?error=duplicate_ref');
    exit();
}

// Log as pending (admin will approve manually)
$ref = generateReference('BNK');
$desc = "Bank transfer by {$sender_name}. Bank Ref: {$bank_ref}";
$stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, reference, method, status, description) VALUES (?, 'credit', ?, ?, 'bank_transfer', 'pending', ?)");
$stmt->bind_param('idss', $user_id, $amount, $ref, $desc);
$stmt->execute();

header('Location: dashboard.php?success=transfer_submitted');
exit();

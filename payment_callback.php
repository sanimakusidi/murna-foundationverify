<?php
require_once 'config.php';
requireLogin();

$reference = $_GET['reference'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($reference)) {
    header('Location: payment.php?error=invalid_reference');
    exit();
}

// Verify with Paystack API
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/verify/" . rawurlencode($reference),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer " . PAYSTACK_SECRET_KEY,
        "Cache-Control: no-cache",
    ],
]);
$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    header('Location: payment.php?error=curl_error');
    exit();
}

$result = json_decode($response, true);

if ($result && $result['status'] === true && $result['data']['status'] === 'success') {
    $amount = $result['data']['amount'] / 100; // Convert from kobo
    $ref = $result['data']['reference'];

    $db = getDB();

    // Check if already processed
    $check = $db->prepare("SELECT id FROM transactions WHERE reference = ?");
    $check->bind_param('s', $ref);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $db->begin_transaction();
        try {
            // Record transaction
            $stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, reference, method, status, description) VALUES (?, 'credit', ?, ?, 'paystack', 'success', 'Wallet top-up via Paystack')");
            $stmt->bind_param('ids', $user_id, $amount, $ref);
            $stmt->execute();

            // Credit wallet
            $stmt2 = $db->prepare("UPDATE accounts SET balance = balance + ? WHERE user_id = ?");
            $stmt2->bind_param('di', $amount, $user_id);
            $stmt2->execute();

            $db->commit();
            header('Location: dashboard.php?success=funded&amount=' . $amount);
            exit();
        } catch (Exception $e) {
            $db->rollback();
            header('Location: payment.php?error=db_error');
            exit();
        }
    } else {
        header('Location: dashboard.php?notice=already_processed');
        exit();
    }
} else {
    header('Location: payment.php?error=payment_failed');
    exit();
}

<?php
require_once 'config.php';
requireLogin();

// Use Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;
require_once 'dompdf/vendor/autoload.php';

$options = new Options();
$options->set('defaultFont', 'Helvetica');
$dompdf = new Dompdf($options);




// At the top of your config.php or directly in the download scripts
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// 1. Retrieve the stored transaction ID
$transaction_id = $_SESSION['last_transaction_id'] ?? null;
if (!$transaction_id) {
     error_log("No transaction_id in session. Session data: " . print_r($_SESSION, true));
    die('No verification data found. Please verify a NIN first.');
}

// 2. Obtain a valid API token
$tokenResult = getRandaVerifyToken();
if ($tokenResult['status'] !== 'success') {
    die('Authentication failed: ' . $tokenResult['message']);
}
$token = $tokenResult['token'];

// 3. Build the PDF endpoint URL
// This example is for the personal info slip.
$url = RANDAVERIFY_BASE_URL . "/verify-nin/slip/" . urlencode($transaction_id);
// 4. Initialize cURL to download the PDF
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/pdf, application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Adjust for production
    CURLOPT_SSL_VERIFYHOST => false, // Adjust for production
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

if (curl_errno($ch)) {
    error_log('cURL Error: ' . curl_error($ch));
    curl_close($ch);
    die('Failed to download PDF due to network error.');
}
curl_close($ch);

if ($http_code === 200 && strpos($content_type, 'application/pdf') !== false) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="Personal_Slip_' . $transaction_id . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    echo $response;
} else {
    $error_data = json_decode($response, true);
    $msg = $error_data['message'] ?? $error_data['detail'] ?? 'Failed to download PDF. HTTP Code: ' . $http_code;
    die('Error downloading slip: ' . htmlspecialchars($msg));
}
exit;
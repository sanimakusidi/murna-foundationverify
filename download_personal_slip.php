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
$url = "https://api.randaverify.com/api/v1/verify/slip/" . urlencode($transaction_id);
// 4. Initialize cURL to download the PDF
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => false,  // Output directly
    CURLOPT_HEADER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $token,
        'Accept: application/pdf'
    ],
    CURLOPT_SSL_VERIFYPEER => false, // Adjust for production
    CURLOPT_SSL_VERIFYHOST => false, // Adjust for production
]);

// 5. Set headers to force download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="Personal_Slip_' . $transaction_id . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// 6. Execute cURL and stream the PDF
curl_exec($ch);

// 7. Check for errors
if (curl_errno($ch)) {
    error_log('cURL Error: ' . curl_error($ch));
    // Optionally output a user-friendly message
    echo 'Failed to download PDF. Please try again.';
}

curl_close($ch);
exit;
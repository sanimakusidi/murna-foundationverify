<?php
// config.php

// ─── Database Config ───────────────────────────────────────────────
define('DB_HOST', getenv('MYSQL_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQL_USER') ?: 'root');
define('DB_PASS', getenv('MYSQL_PASSWORD') ?: '');
define('DB_NAME', getenv('MYSQL_DATABASE') ?: 'murna_foundation');
define('DB_PORT', getenv('MYSQL_PORT') ?: 3306);

// ─── App Config ────────────────────────────────────────────────────
define('APP_NAME', 'Murna Foundation NIN Portal');
define('APP_URL', 'http://localhost/murna-foundation');

// ─── Paystack Config ───────────────────────────────────────────────
define('PAYSTACK_PUBLIC_KEY', 'pk_test_3df7192d5ed9da1bda6185029d1daed8210d68c3');
define('PAYSTACK_SECRET_KEY', 'sk_test_266c40da7ed089920b366aab58f1ef8ec0d7e53f');
define('PAYSTACK_CALLBACK_URL', APP_URL . '/payment_callback.php');

// ─── RandaVerify API Config ────────────────────────────────────────
// FIX #1: Removed the leading space that broke every cURL call
define('RANDAVERIFY_BASE_URL', 'https://api.randaverify.com/v1');
define('RANDAVERIFY_ADMIN_USER', 'aishaahmed');
define('RANDAVERIFY_ADMIN_PASS', 'KSHgy_Uy0O-Gneqk');

// ─── API Endpoints ─────────────────────────────────────────────────
define('RANDAVERIFY_ENDPOINT_NIN',   '/verify-nin');
define('RANDAVERIFY_ENDPOINT_PHONE', '/verify-nin/phone');
define('RANDAVERIFY_ENDPOINT_DEMO',  '/verify-nin/demography');
define('RANDAVERIFY_ENDPOINT_LOGIN', '/login');
define('RANDAVERIFY_ENDPOINT_CHPWD', '/change-password');

// ─── Verification Costs ────────────────────────────────────────────
define('NIN_COST',         100.00);
define('PHONE_COST',       100.00);
define('DEMOGRAPHIC_COST', 100.00);

// ─── DB Connection ─────────────────────────────────────────────────
function getDB() {
    static $conn = null;
    // Reuse connection within the same request (prevents "too many connections")
    if ($conn === null || $conn->connect_error) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Database connection failed: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ─── Session Start ─────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Helper: Generate Account Number ───────────────────────────────
function generateAccountNumber() {
    return 'MF' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

// ─── Helper: Generate Reference ────────────────────────────────────
function generateReference($prefix = 'TXN') {
    return $prefix . '_' . time() . '_' . rand(1000, 9999);
}

// ─── Helper: Format Currency ───────────────────────────────────────
function formatCurrency($amount) {
    return 'NGN ' . number_format($amount, 2);
}

// ─── Helper: Auth Guard ────────────────────────────────────────────
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . APP_URL . '/index.php?page=login');
        exit();
    }
}

// ─── Helper: Get Setting ───────────────────────────────────────────
function getSetting($key) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result ? $result['setting_value'] : null;
}

// ─── Helper: Save Setting ──────────────────────────────────────────
function saveSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO settings (setting_key, setting_value)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->bind_param('ss', $key, $value);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

// ─── Helper: Get User Balance ──────────────────────────────────────
function getUserBalance($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT balance FROM accounts WHERE user_id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result ? floatval($result['balance']) : 0.00;
}

// ─── Helper: Debit Account ─────────────────────────────────────────
function debitAccount($user_id, $amount, $description) {
    $db = getDB();
    $balance = getUserBalance($user_id);
    if ($balance < $amount) return false;

    $ref = generateReference('VRF');
    $db->begin_transaction();
    try {
        $stmt = $db->prepare("UPDATE accounts SET balance = balance - ? WHERE user_id = ?");
        $stmt->bind_param('di', $amount, $user_id);
        $stmt->execute();
        $stmt->close();

        $stmt2 = $db->prepare("INSERT INTO transactions (user_id, type, amount, reference, method, status, description) VALUES (?, 'debit', ?, ?, 'wallet', 'success', ?)");
        $stmt2->bind_param('idss', $user_id, $amount, $ref, $description);
        $stmt2->execute();
        $stmt2->close();

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        error_log('[Murna] debitAccount failed: ' . $e->getMessage());
        return false;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// RANDAVERIFY INTEGRATION
// ═══════════════════════════════════════════════════════════════════════════

/**
 * FIX #3: Returns the active RandaVerify password.
 * Priority: DB-stored (post-change) > constant (initial credential).
 */
function getRandaVerifyPassword() {
    $stored = getSetting('randaverify_password');
    return ($stored && $stored !== '') ? $stored : RANDAVERIFY_ADMIN_PASS;
}

/**
 * Shared cURL options for all RandaVerify calls.
 * FIX #4: Added SSL options required for staging environments.
 */
function _randaCurlBase($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
        // Staging cert may be self-signed — set to true in production
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    return $ch;
}

/**
 * FIX #2 + #3: Get or refresh the RandaVerify access token.
 * Uses the persisted (post-change) password when available.
 */
function getRandaVerifyToken($forceRefresh = false) {
    // Serve cached token if still valid
    if (
        !$forceRefresh &&
        isset($_SESSION['randa_token'], $_SESSION['randa_token_expiry']) &&
        time() < $_SESSION['randa_token_expiry']
    ) {
        return ['status' => 'success', 'token' => $_SESSION['randa_token']];
    }

    // Discard stale token
    unset($_SESSION['randa_token'], $_SESSION['randa_token_expiry']);

    $password = getRandaVerifyPassword(); // FIX #3

    $ch = _randaCurlBase(RANDAVERIFY_BASE_URL . RANDAVERIFY_ENDPOINT_LOGIN);
    curl_setopt_array($ch, [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'username' => RANDAVERIFY_ADMIN_USER,
            'password' => $password,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        error_log('[RandaVerify] Login cURL error: ' . $curlErr);
        return ['status' => 'error', 'message' => 'Network error during authentication: ' . $curlErr];
    }

    if ($httpCode === 0) {
        return ['status' => 'error', 'message' => 'No response from RandaVerify server.'];
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('[RandaVerify] Login non-JSON response (HTTP ' . $httpCode . '): ' . $response);
        return ['status' => 'error', 'message' => 'Invalid response from authentication server.'];
    }

    // First-login: password change required
    if (
        isset($data['require_password_change']) &&
        $data['require_password_change'] === true
    ) {
        return [
            'status'     => 'require_password_change',
            'message'    => 'Password change required before proceeding.',
            'temp_token' => $data['access_token'] ?? null,
        ];
    }

    // Success
    if (isset($data['access_token'])) {
        // Token TTL: use expires_in from response, fall back to 55 minutes
        $ttl = isset($data['expires_in']) ? (int)$data['expires_in'] - 60 : 3300;
        $_SESSION['randa_token']        = $data['access_token'];
        $_SESSION['randa_token_expiry'] = time() + $ttl;
        return ['status' => 'success', 'token' => $data['access_token']];
    }

    error_log('[RandaVerify] Login failed (HTTP ' . $httpCode . '): ' . $response);
    return [
        'status'  => 'error',
        'message' => $data['detail'] ?? $data['message'] ?? 'Authentication failed (HTTP ' . $httpCode . ').',
    ];
}

/**
 * Change the RandaVerify password on first login.
 * FIX #2: Persists new password to DB so subsequent logins succeed.
 */
function changeRandaVerifyPassword($oldPassword, $newPassword, $tempToken) {
    $ch = _randaCurlBase(RANDAVERIFY_BASE_URL . RANDAVERIFY_ENDPOINT_CHPWD);
    curl_setopt_array($ch, [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => json_encode([
            'current_password' => $oldPassword,
            'new_password'     => $newPassword,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $tempToken,
        ],
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        return ['status' => 'error', 'message' => 'Network error: ' . $curlErr];
    }

    $decoded = json_decode($response, true);

    if ($httpCode === 200) {
        // FIX #2: Persist the new password to DB settings table
        saveSetting('randaverify_password', $newPassword);

        // Clear cached token so next call re-authenticates with new password
        unset($_SESSION['randa_token'], $_SESSION['randa_token_expiry']);

        return ['status' => 'success', 'message' => 'Password changed successfully.'];
    }

    return [
        'status'  => 'error',
        'message' => $decoded['detail'] ?? $decoded['message'] ?? 'Password change failed (HTTP ' . $httpCode . ').',
    ];
}

/**
 * Call a RandaVerify verification endpoint.
 * FIX #5: Auto-refreshes token on 401 Unauthorized (expired mid-session).
 */
function callRandaVerifyAPI($endpoint, $payload) {
    $tokenResult = getRandaVerifyToken();

    if ($tokenResult['status'] === 'require_password_change') {
        return [
            'error'      => 'require_password_change',
            'detail'     => 'Password change required. Please update your password.',
            'temp_token' => $tokenResult['temp_token'],
        ];
    }

    if ($tokenResult['status'] !== 'success') {
        return ['error' => 'authentication_failed', 'detail' => $tokenResult['message']];
    }

    $response = _doRandaApiCall($endpoint, $payload, $tokenResult['token']);

    // FIX #5: Token expired mid-session — force refresh and retry once
    if (
        isset($response['http_code']) &&
        $response['http_code'] === 401
    ) {
        $refreshResult = getRandaVerifyToken(true);
        if ($refreshResult['status'] === 'success') {
            $response = _doRandaApiCall($endpoint, $payload, $refreshResult['token']);
        } else {
            return ['error' => 'token_refresh_failed', 'detail' => $refreshResult['message']];
        }
    }

    return $response;
}

/**
 * Internal: Execute a single authenticated POST to the RandaVerify API.
 */
function _doRandaApiCall($endpoint, $payload, $token) {
    $ch = _randaCurlBase(RANDAVERIFY_BASE_URL . $endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
    ]);

    $response = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlErr) {
        error_log('[RandaVerify] API cURL error on ' . $endpoint . ': ' . $curlErr);
        return [
            'error'     => 'curl_failed',
            'http_code' => 0,
            'detail'    => 'Network error: ' . $curlErr,
        ];
    }

    $decoded = json_decode($response, true);

    if ($httpCode === 200) {
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['error' => 'invalid_json', 'http_code' => 200, 'detail' => 'Non-JSON success response.'];
        }
        return $decoded;
    }

    error_log('[RandaVerify] API error on ' . $endpoint . ' (HTTP ' . $httpCode . '): ' . $response);
    return [
        'error'     => 'api_error',
        'http_code' => $httpCode,
        'detail'    => $decoded['detail'] ?? $decoded['message'] ?? ('HTTP ' . $httpCode . ' from API.'),
    ];
}

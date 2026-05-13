<?php
/* ═══════════════════════════════════════════════════════════════
   DATABASE CONFIGURATION
   • Local  (XAMPP)     → set environment vars OR use the local block
   • Live   (InfinityFree) → update the 4 constants below
   ═══════════════════════════════════════════════════════════════ */

// ── Local XAMPP Credentials ──
define("DB_HOST", getenv('DB_HOST') ?: "localhost");
define("DB_USER", getenv('DB_USER') ?: "root");
define("DB_PASS", getenv('DB_PASS') ?: "");
define("DB_NAME", getenv('DB_NAME') ?: "homeease_db");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");

function respond($success, $message = "", $data = [])
{
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array_merge(["success" => $success, "message" => $message], $data));
    exit;
}

/**
 * Ensure the payments table exists with proper structure
 * @param mysqli $conn Database connection
 * @return bool True if table exists or was created
 */
function ensurePaymentsTable($conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        user_id INT NOT NULL,
        payment_method ENUM('cash', 'gcash', 'bank') NOT NULL DEFAULT 'cash',
        payment_status ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
        payment_reference VARCHAR(255) NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_id VARCHAR(100) NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_booking_id (booking_id),
        KEY idx_user_id (user_id),
        KEY idx_payment_status (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    return $conn->query($sql) === TRUE || $conn->errno == 1050;
}

/**
 * Validate payment method and reference data
 * @param string $method Payment method (cash, gcash, bank)
 * @param string|null $reference Payment reference (phone/account number)
 * @return array Validation result with 'valid' bool and 'message'
 */
function validatePaymentData($method, $reference = null)
{
    $method = strtolower($method);

    if (!in_array($method, ['cash', 'gcash', 'bank'])) {
        return ['valid' => false, 'message' => 'Invalid payment method'];
    }

    if ($method === 'cash') {
        return ['valid' => true, 'message' => 'Cash payment validated'];
    }

    if ($method === 'gcash') {
        if (empty($reference)) {
            return ['valid' => false, 'message' => 'GCash number is required'];
        }
        // Validate GCash number format (11 digits for PH numbers)
        if (!preg_match('/^09\d{9}$/', $reference)) {
            return ['valid' => false, 'message' => 'Invalid GCash number format (must be 09XXXXXXXXX)'];
        }
        return ['valid' => true, 'message' => 'GCash number validated'];
    }

    if ($method === 'bank') {
        if (empty($reference)) {
            return ['valid' => false, 'message' => 'Account number is required'];
        }
        // Validate account number (numeric, 8-20 digits)
        if (!preg_match('/^\d{8,20}$/', $reference)) {
            return ['valid' => false, 'message' => 'Invalid account number format'];
        }
        return ['valid' => true, 'message' => 'Account number validated'];
    }

    return ['valid' => false, 'message' => 'Payment validation error'];
}

/**
 * Save payment information to the payments table
 * @param mysqli $conn Database connection
 * @param int $bookingId Booking ID
 * @param int $userId User ID
 * @param string $method Payment method
 * @param string|null $reference Payment reference
 * @param float $amount Payment amount
 * @param string $status Payment status
 * @return array Result with 'success' bool and 'payment_id' or 'message'
 */
function savePayment($conn, $bookingId, $userId, $method, $reference, $amount, $status = 'pending')
{
    ensurePaymentsTable($conn);

    // Validate payment data
    $validation = validatePaymentData($method, $reference);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }

    // Generate unique transaction ID
    $transactionId = 'TXN-' . date('YmdHis') . '-' . $bookingId . '-' . mt_rand(1000, 9999);

    $stmt = $conn->prepare(
        "INSERT INTO payments 
        (booking_id, user_id, payment_method, payment_reference, amount, payment_status, transaction_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }

    $stmt->bind_param(
        'iissdss',
        $bookingId,
        $userId,
        $method,
        $reference,
        $amount,
        $status,
        $transactionId
    );

    if ($stmt->execute()) {
        $paymentId = $conn->insert_id;
        $stmt->close();
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'message' => 'Payment information saved successfully'
        ];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Failed to save payment: ' . $error];
    }
}

/**
 * Get payment information for a booking (user-scoped)
 * @param mysqli $conn Database connection
 * @param int $userId User ID
 * @param int $bookingId Booking ID
 * @return array Payment data or null if not found/not authorized
 */
function getPaymentByBooking($conn, $userId, $bookingId)
{
    ensurePaymentsTable($conn);

    $stmt = $conn->prepare(
        "SELECT id, booking_id, user_id, payment_method, payment_status, payment_reference, 
                amount, transaction_id, created_at, updated_at
         FROM payments 
         WHERE booking_id = ? AND user_id = ? 
         LIMIT 1"
    );

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('ii', $bookingId, $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $result;
}

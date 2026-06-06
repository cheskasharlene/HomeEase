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
define("DB_NAME", getenv('DB_NAME') ?: "homease_db");

// Prevent uncaught mysqli_sql_exception from breaking JSON API responses.
mysqli_report(MYSQLI_REPORT_OFF);

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
        payment_method_id INT NULL,
        payment_method ENUM('cash', 'gcash', 'bank') NOT NULL DEFAULT 'cash',
        payment_status ENUM('pending', 'completed', 'failed', 'cancelled', 'submitted') NOT NULL DEFAULT 'pending',
        payment_reference VARCHAR(255) NULL,
        amount DECIMAL(10, 2) NOT NULL,
        transaction_id VARCHAR(100) NULL,
        payment_proof_path VARCHAR(512) NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_booking_id (booking_id),
        receiver_provider_id INT NULL,
        expected_until DATETIME NULL,
        UNIQUE KEY idx_payment_reference (payment_reference(190)),
        KEY idx_payment_method_id (payment_method_id),
        KEY idx_user_id (user_id),
        KEY idx_payment_status (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $created = ($conn->query($sql) === TRUE || $conn->errno == 1050);

    // Ensure payment_status supports 'submitted'
    @$conn->query("ALTER TABLE `payments` MODIFY COLUMN `payment_status` ENUM('pending', 'completed', 'failed', 'cancelled', 'submitted') NOT NULL DEFAULT 'pending'");

    // Safely add proof column to pre-existing tables
    $check = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'payment_proof_path'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `payment_proof_path` VARCHAR(512) NULL AFTER `transaction_id`");
    }

    // Ensure receiver_provider_id exists
    $chk = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'receiver_provider_id'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `receiver_provider_id` INT NULL AFTER `transaction_id`");
    }

    // Ensure expected_until exists
    $chk2 = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'expected_until'");
    if ($chk2 && $chk2->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `expected_until` DATETIME NULL AFTER `receiver_provider_id`");
    }

    // Ensure payment_method_id exists for normalized method relation
    $chk3 = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'payment_method_id'");
    if ($chk3 && $chk3->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `payment_method_id` INT NULL AFTER `user_id`");
    }

    // Ensure payment_method_id index exists
    $idx2 = $conn->query("SHOW INDEX FROM `payments` WHERE Key_name = 'idx_payment_method_id'");
    if ($idx2 && $idx2->num_rows === 0) {
        @$conn->query("ALTER TABLE `payments` ADD INDEX `idx_payment_method_id` (`payment_method_id`)");
    }

    // Ensure payment_reference unique index exists (prefixed for older MySQL utf8 issues)
    $idx = $conn->query("SHOW INDEX FROM `payments` WHERE Key_name = 'idx_payment_reference'");
    if ($idx && $idx->num_rows === 0) {
        @ $conn->query("ALTER TABLE `payments` ADD UNIQUE INDEX `idx_payment_reference` (`payment_reference`(190))");
    }

    return $created;
}

/**
 * Ensure bookings.status supports awaiting_payment for online payment flow
 */
function ensureBookingStatusEnum($conn)
{
    $res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status'");
    if (!$res || !($col = $res->fetch_assoc())) {
        return;
    }
    $type = (string) ($col['Type'] ?? '');
    if (stripos($type, 'awaiting_payment') === false) {
        @$conn->query("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','awaiting_payment','progress','done','cancelled') NOT NULL DEFAULT 'pending'");
    }
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

    // Allow empty reference during initial booking creation
    if (empty($reference)) {
        return ['valid' => true, 'message' => 'Pending payment reference'];
    }

    if ($method === 'gcash') {
        // Validate GCash number format (11 digits for PH numbers)
        if (!preg_match('/^09\d{9}$/', $reference)) {
            return ['valid' => false, 'message' => 'Invalid GCash number format (must be 09XXXXXXXXX)'];
        }
        return ['valid' => true, 'message' => 'GCash number validated'];
    }

    if ($method === 'bank') {
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
 * @param string|null $proofPath Path to the uploaded proof of payment image
 * @return array Result with 'success' bool and 'payment_id' or 'message'
 */
function savePayment($conn, $bookingId, $userId, $method, $reference, $amount, $status = 'pending', $proofPath = null)
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
        (booking_id, user_id, payment_method, payment_reference, amount, payment_status, transaction_id, payment_proof_path, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );

    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }

    $stmt->bind_param(
        'iissdsss',
        $bookingId,
        $userId,
        $method,
        $reference,
        $amount,
        $status,
        $transactionId,
        $proofPath
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
 * @return array|null Payment data or null if not found/not authorized
 */
function getPaymentByBooking($conn, $userId, $bookingId)
{
    ensurePaymentsTable($conn);

    $stmt = $conn->prepare(
        "SELECT id, booking_id, user_id, payment_method, payment_status, payment_reference,
                amount, transaction_id, payment_proof_path, receiver_provider_id, expected_until,
                notes, created_at, updated_at
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

/**
 * Ensure normalization-related tables and columns exist.
 */
function ensureNormalizationSchema($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(32) NOT NULL UNIQUE,
        name VARCHAR(64) NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("INSERT INTO payment_methods (code, name, is_active)
        VALUES
            ('cash', 'Cash', 1),
            ('gcash', 'GCash', 1),
            ('bank', 'Bank Transfer', 1)
        ON DUPLICATE KEY UPDATE name = VALUES(name), is_active = VALUES(is_active)");

    $conn->query("CREATE TABLE IF NOT EXISTS booking_details (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        field_name VARCHAR(120) NOT NULL,
        field_value TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_booking_field (booking_id, field_name),
        INDEX idx_booking_details_booking (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS service_provider_services (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        service_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_provider_service (provider_id, service_id),
        INDEX idx_sps_provider (provider_id),
        INDEX idx_sps_service (service_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS provider_documents (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        document_type VARCHAR(50) NOT NULL,
        file_path VARCHAR(512) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        verified_status ENUM('submitted','approved','rejected') NOT NULL DEFAULT 'submitted',
        verified_at TIMESTAMP NULL,
        verification_notes TEXT NULL,
        UNIQUE KEY uq_provider_doc_type (provider_id, document_type),
        INDEX idx_provider_docs_provider (provider_id),
        INDEX idx_provider_docs_status (verified_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    ensureBookingStatusLogsTable($conn);

    @$conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS service_id INT NULL AFTER user_id");
    @$conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS provider_id INT NULL AFTER service_id");
    @$conn->query("ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_service_id (service_id)");
    @$conn->query("ALTER TABLE bookings ADD INDEX IF NOT EXISTS idx_bookings_provider_id (provider_id)");

    // Ensure customer_lng and provider_lng are DECIMAL(11,8) to prevent clamping of longitude (e.g. 121.xx -> 99.99)
    $res = $conn->query("SHOW COLUMNS FROM bookings LIKE 'customer_lng'");
    if ($res && ($col = $res->fetch_assoc())) {
        if (strpos(strtolower($col['Type']), 'decimal(11,8)') === false) {
            $conn->query("ALTER TABLE bookings MODIFY COLUMN customer_lng DECIMAL(11,8) NULL");
        }
    }
    $res2 = $conn->query("SHOW COLUMNS FROM bookings LIKE 'provider_lng'");
    if ($res2 && ($col = $res2->fetch_assoc())) {
        if (strpos(strtolower($col['Type']), 'decimal(11,8)') === false) {
            $conn->query("ALTER TABLE bookings MODIFY COLUMN provider_lng DECIMAL(11,8) NULL");
        }
    }

    // Backfill service_id from existing text labels.
    @$conn->query("UPDATE bookings b
        JOIN services s ON LOWER(TRIM(s.name)) = LOWER(TRIM(COALESCE(b.service, '')))
        SET b.service_id = s.id
        WHERE b.service_id IS NULL");

    // Backfill payment method relation.
    @$conn->query("UPDATE payments p
        JOIN payment_methods pm ON pm.code = LOWER(COALESCE(p.payment_method, 'cash'))
        SET p.payment_method_id = pm.id
        WHERE p.payment_method_id IS NULL");

    // Seed pivot from existing single-service column.
    @$conn->query("INSERT IGNORE INTO service_provider_services (provider_id, service_id)
        SELECT sp.provider_id, s.id
        FROM service_providers sp
        JOIN services s ON LOWER(TRIM(s.name)) = LOWER(TRIM(COALESCE(sp.service_category, '')))");
}

function ensureBookingStatusLogsTable($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS booking_status_logs (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        old_status VARCHAR(40) NULL,
        new_status VARCHAR(40) NOT NULL,
        changed_by_role ENUM('user','provider','admin','system') NOT NULL DEFAULT 'system',
        changed_by_id INT NULL,
        notes VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_booking_status_logs_booking (booking_id),
        INDEX idx_booking_status_logs_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function logBookingStatusChange($conn, $bookingId, $oldStatus, $newStatus, $changedByRole = 'system', $changedById = null, $notes = null)
{
    ensureBookingStatusLogsTable($conn);
    $stmt = $conn->prepare("INSERT INTO booking_status_logs (booking_id, old_status, new_status, changed_by_role, changed_by_id, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('isssis', $bookingId, $oldStatus, $newStatus, $changedByRole, $changedById, $notes);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function resolveServiceIdByName($conn, $serviceName)
{
    $name = trim((string) $serviceName);
    if ($name === '') {
        return null;
    }

    $stmt = $conn->prepare("SELECT id FROM services WHERE LOWER(name) = LOWER(?) LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return null;
    }
    return (int) $row['id'];
}

function upsertBookingDetail($conn, $bookingId, $fieldName, $fieldValue)
{
    ensureNormalizationSchema($conn);
    $fieldName = trim((string) $fieldName);
    if ($bookingId <= 0 || $fieldName === '') {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO booking_details (booking_id, field_name, field_value, created_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('iss', $bookingId, $fieldName, $fieldValue);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

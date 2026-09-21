<?php
date_default_timezone_set('Asia/Manila');

if (!function_exists('phNow')) {
    function phNow($offsetSeconds = 0)
    {
        return date('Y-m-d H:i:s', time() + (int)$offsetSeconds);
    }
}

if (!defined("DB_HOST")) {
    define("DB_HOST", getenv('DB_HOST') ?: "localhost");
    define("DB_USER", getenv('DB_USER') ?: "root");
    define("DB_PASS", getenv('DB_PASS') ?: "");
    define("DB_NAME", getenv('DB_NAME') ?: "homease_db");
}

mysqli_report(MYSQLI_REPORT_OFF);

if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        header("Content-Type: application/json; charset=utf-8");
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]);
        exit;
    }
    $conn->set_charset("utf8mb4");
    @$conn->query("SET time_zone = '+08:00'");
}

if (!function_exists('respond')) {
    function respond($success, $message = "", $data = [])
    {
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(array_merge(["success" => $success, "message" => $message], $data));
        exit;
    }
}

function ensurePolicyAcceptedColumns($conn)
{
    if (!$conn || !($conn instanceof mysqli)) return;
    $chk = $conn->query("SHOW COLUMNS FROM users LIKE 'policy_accepted'");
    if ($chk && $chk->num_rows === 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN policy_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER status");
        @$conn->query("UPDATE users SET policy_accepted = 1 WHERE policy_accepted_at IS NOT NULL");
    }
    $chk2 = $conn->query("SHOW COLUMNS FROM users LIKE 'policy_accepted_at'");
    if ($chk2 && $chk2->num_rows === 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN policy_accepted_at TIMESTAMP NULL DEFAULT NULL AFTER policy_accepted");
    }
}
ensurePolicyAcceptedColumns($conn);






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

    
    @$conn->query("ALTER TABLE `payments` MODIFY COLUMN `payment_status` ENUM('pending', 'completed', 'failed', 'cancelled', 'submitted') NOT NULL DEFAULT 'pending'");

    
    $check = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'payment_proof_path'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `payment_proof_path` VARCHAR(512) NULL AFTER `transaction_id`");
    }

    
    $chk = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'receiver_provider_id'");
    if ($chk && $chk->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `receiver_provider_id` INT NULL AFTER `transaction_id`");
    }

    
    $chk2 = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'expected_until'");
    if ($chk2 && $chk2->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `expected_until` DATETIME NULL AFTER `receiver_provider_id`");
    }

    
    $chk3 = $conn->query("SHOW COLUMNS FROM `payments` LIKE 'payment_method_id'");
    if ($chk3 && $chk3->num_rows === 0) {
        $conn->query("ALTER TABLE `payments` ADD COLUMN `payment_method_id` INT NULL AFTER `user_id`");
    }

    
    $idx2 = $conn->query("SHOW INDEX FROM `payments` WHERE Key_name = 'idx_payment_method_id'");
    if ($idx2 && $idx2->num_rows === 0) {
        @$conn->query("ALTER TABLE `payments` ADD INDEX `idx_payment_method_id` (`payment_method_id`)");
    }

    
    $idx = $conn->query("SHOW INDEX FROM `payments` WHERE Key_name = 'idx_payment_reference'");
    if ($idx && $idx->num_rows === 0) {
        @ $conn->query("ALTER TABLE `payments` ADD UNIQUE INDEX `idx_payment_reference` (`payment_reference`(190))");
    }

    return $created;
}




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




if (!function_exists('ensureBookingRequestsTable')) {
    function ensureBookingRequestsTable($conn)
    {
        $sql = "CREATE TABLE IF NOT EXISTS booking_requests (
          id INT AUTO_INCREMENT PRIMARY KEY,
          booking_id INT NOT NULL,
          provider_id INT NOT NULL,
          service VARCHAR(120) NOT NULL,
          fixed_price DECIMAL(10,2) NOT NULL DEFAULT 0,
          date DATE NULL,
          time_slot VARCHAR(32) NULL,
          address VARCHAR(255) NULL,
          details TEXT NULL,
          customer_name VARCHAR(120) NULL,
          customer_phone VARCHAR(40) NULL,
          customer_address VARCHAR(255) NULL,
          status ENUM('pending','accepted','declined','closed') NOT NULL DEFAULT 'pending',
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          expires_at DATETIME NULL,
          responded_at DATETIME NULL,
          INDEX idx_provider_status (provider_id, status),
          INDEX idx_booking (booking_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @$conn->query($sql);
    }
}







function validatePaymentData($method, $reference = null)
{
    $method = strtolower($method);

    if (!in_array($method, ['cash', 'gcash', 'bank'])) {
        return ['valid' => false, 'message' => 'Invalid payment method'];
    }

    if ($method === 'cash') {
        return ['valid' => true, 'message' => 'Cash payment validated'];
    }

    
    if (empty($reference)) {
        return ['valid' => true, 'message' => 'Pending payment reference'];
    }

    if ($method === 'gcash') {
        
        if (!preg_match('/^09\d{9}$/', $reference)) {
            return ['valid' => false, 'message' => 'Invalid GCash number format (must be 09XXXXXXXXX)'];
        }
        return ['valid' => true, 'message' => 'GCash number validated'];
    }

    if ($method === 'bank') {
        
        if (!preg_match('/^\d{8,20}$/', $reference)) {
            return ['valid' => false, 'message' => 'Invalid account number format'];
        }
        return ['valid' => true, 'message' => 'Account number validated'];
    }

    return ['valid' => false, 'message' => 'Payment validation error'];
}













function savePayment($conn, $bookingId, $userId, $method, $reference, $amount, $status = 'pending', $proofPath = null)
{
    ensurePaymentsTable($conn);

    
    $validation = validatePaymentData($method, $reference);
    if (!$validation['valid']) {
        return ['success' => false, 'message' => $validation['message']];
    }

    
    $transactionId = 'TXN-' . date('YmdHis') . '-' . $bookingId . '-' . mt_rand(1000, 9999);

    $nowStr = phNow();
    $stmt = $conn->prepare(
        "INSERT INTO payments 
        (booking_id, user_id, payment_method, payment_reference, amount, payment_status, transaction_id, payment_proof_path, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }

    $stmt->bind_param(
        'iissdssss',
        $bookingId,
        $userId,
        $method,
        $reference,
        $amount,
        $status,
        $transactionId,
        $proofPath,
        $nowStr
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

    
    @$conn->query("UPDATE bookings b
        JOIN services s ON LOWER(TRIM(s.name)) = LOWER(TRIM(COALESCE(b.service, '')))
        SET b.service_id = s.id
        WHERE b.service_id IS NULL");

    
    @$conn->query("UPDATE payments p
        JOIN payment_methods pm ON pm.code = LOWER(COALESCE(p.payment_method, 'cash'))
        SET p.payment_method_id = pm.id
        WHERE p.payment_method_id IS NULL");

    
    $resSp = $conn->query("SHOW COLUMNS FROM service_providers LIKE 'service_id'");
    if ($resSp && $resSp->num_rows === 0) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN service_id INT NULL AFTER contact_number");
        $conn->query("ALTER TABLE service_providers ADD INDEX idx_sp_service_id (service_id)");
        
        $conn->query("ALTER TABLE service_providers ADD CONSTRAINT fk_sp_service_id FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL");
    }

    
    $resRej = $conn->query("SHOW COLUMNS FROM service_providers LIKE 'rejection_reason'");
    if ($resRej && $resRej->num_rows === 0) {
        @$conn->query("ALTER TABLE service_providers ADD COLUMN rejection_reason TEXT NULL");
    }

    $resExp = $conn->query("SHOW COLUMNS FROM service_providers LIKE 'work_experience'");
    if ($resExp && $resExp->num_rows === 0) {
        @$conn->query("ALTER TABLE service_providers ADD COLUMN work_experience TEXT NULL");
    }

    
    $resCat = $conn->query("SHOW COLUMNS FROM service_providers LIKE 'service_category'");
    if ($resCat && $resCat->num_rows > 0) {
        
        @$conn->query("UPDATE service_providers SET service_category = 'Plumber' WHERE LOWER(TRIM(service_category)) = 'plumbing'");
        @$conn->query("UPDATE service_providers SET service_category = 'House Cleaner' WHERE LOWER(TRIM(service_category)) IN ('cleaner', 'cleaning')");
        
        
        @$conn->query("UPDATE service_providers sp
            JOIN services s ON LOWER(TRIM(s.name)) = LOWER(TRIM(COALESCE(sp.service_category, '')))
            SET sp.service_id = s.id
            WHERE sp.service_id IS NULL");
            
        
        @$conn->query("ALTER TABLE service_providers DROP COLUMN service_category");
    }

    
    @$conn->query("DROP TABLE IF EXISTS service_provider_services");

    
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL DEFAULT 'general',
        title VARCHAR(200) NOT NULL,
        message TEXT,
        reference_id INT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM admin_notifications");
    if ($result) {
        while ($col = $result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
    }

    if (!in_array('provider_id', $columns)) {
        @$conn->query("ALTER TABLE admin_notifications ADD COLUMN provider_id INT NULL AFTER reference_id");
        @$conn->query("ALTER TABLE admin_notifications ADD INDEX idx_admin_notif_provider (provider_id)");
        @$conn->query("ALTER TABLE admin_notifications ADD CONSTRAINT fk_admin_notif_provider FOREIGN KEY (provider_id) REFERENCES service_providers(provider_id) ON DELETE CASCADE");
    }
    if (!in_array('report_id', $columns)) {
        @$conn->query("ALTER TABLE admin_notifications ADD COLUMN report_id VARCHAR(20) NULL AFTER provider_id");
        @$conn->query("ALTER TABLE admin_notifications ADD INDEX idx_admin_notif_report (report_id)");
        @$conn->query("ALTER TABLE admin_notifications ADD CONSTRAINT fk_admin_notif_report FOREIGN KEY (report_id) REFERENCES incident_reports(report_id) ON DELETE CASCADE");
    }
    if (!in_array('qr_change_request_id', $columns)) {
        @$conn->query("ALTER TABLE admin_notifications ADD COLUMN qr_change_request_id INT NULL AFTER report_id");
        @$conn->query("ALTER TABLE admin_notifications ADD INDEX idx_admin_notif_qr (qr_change_request_id)");
        @$conn->query("ALTER TABLE admin_notifications ADD CONSTRAINT fk_admin_notif_qr FOREIGN KEY (qr_change_request_id) REFERENCES qr_change_requests(id) ON DELETE CASCADE");
    }

    
    $conn->query("CREATE TABLE IF NOT EXISTS remittances (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        reference_no VARCHAR(50) NOT NULL UNIQUE,
        amount_due DECIMAL(10, 2) NOT NULL,
        amount_paid DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
        status ENUM('pending', 'submitted', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
        due_date DATE NOT NULL,
        date_remitted DATETIME DEFAULT NULL,
        submitted_at DATETIME DEFAULT NULL,
        payment_method VARCHAR(50) DEFAULT NULL,
        receipt_path VARCHAR(512) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_remittances_provider (provider_id),
        INDEX idx_remittances_status (status),
        INDEX idx_remittances_due (due_date),
        INDEX idx_remittances_prov_due (provider_id, due_date),
        CONSTRAINT fk_remittances_provider FOREIGN KEY (provider_id) REFERENCES service_providers(provider_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM admin_notifications");
    if ($result) {
        while ($col = $result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
    }
    if (!in_array('remittance_id', $columns)) {
        @$conn->query("ALTER TABLE admin_notifications ADD COLUMN remittance_id INT NULL AFTER qr_change_request_id");
        @$conn->query("ALTER TABLE admin_notifications ADD INDEX idx_admin_notif_remittance (remittance_id)");
        @$conn->query("ALTER TABLE admin_notifications ADD CONSTRAINT fk_admin_notif_remittance FOREIGN KEY (remittance_id) REFERENCES remittances(id) ON DELETE CASCADE");
    }

    
    $conn->query("CREATE TABLE IF NOT EXISTS provider_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        title VARCHAR(120) NOT NULL,
        message TEXT,
        icon VARCHAR(32) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_provider_read (provider_id, is_read),
        INDEX idx_provider_created (provider_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pcolumns = [];
    $presult = $conn->query("SHOW COLUMNS FROM provider_notifications");
    if ($presult) {
        while ($col = $presult->fetch_assoc()) {
            $pcolumns[] = $col['Field'];
        }
    }
    if (!in_array('type', $pcolumns)) {
        @$conn->query("ALTER TABLE provider_notifications ADD COLUMN type VARCHAR(50) NOT NULL DEFAULT 'general' AFTER provider_id");
        @$conn->query("ALTER TABLE provider_notifications ADD INDEX idx_provider_notif_type (type)");
    }
    if (!in_array('reference_id', $pcolumns)) {
        @$conn->query("ALTER TABLE provider_notifications ADD COLUMN reference_id INT NULL AFTER type");
        @$conn->query("ALTER TABLE provider_notifications ADD INDEX idx_provider_notif_ref (reference_id)");
    }

    
    @$conn->query("UPDATE admin_notifications SET provider_id = reference_id WHERE type = 'verification' AND provider_id IS NULL");
    @$conn->query("UPDATE admin_notifications SET qr_change_request_id = reference_id WHERE type = 'qr_change' AND qr_change_request_id IS NULL");

    
    syncProviderJobsDone($conn);
    syncProviderOnlineStatuses($conn);
    syncUserOnlineStatuses($conn);
}




function syncProviderJobsDone($conn, $providerId = null)
{
    if (!$conn instanceof mysqli) return;
    $where = "";
    if ($providerId > 0) {
        $where = "WHERE sp.provider_id = " . intval($providerId);
    }
    $sql = "UPDATE service_providers sp SET
        jobs_done = (
            SELECT COUNT(DISTINCT b.id)
            FROM bookings b
            LEFT JOIN booking_requests br ON br.booking_id = b.id AND br.provider_id = sp.provider_id
            WHERE (b.provider_id = sp.provider_id OR (br.provider_id = sp.provider_id AND br.status IN ('accepted','completed','done')))
              AND LOWER(b.status) IN ('done', 'completed')
        ),
        rating = COALESCE(
            (SELECT ROUND(AVG(pr.rating), 1) FROM provider_reviews pr WHERE pr.provider_id = sp.provider_id),
            0.0
        )
        {$where}";
    @$conn->query($sql);
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
    $nowStr = phNow();
    $stmt = $conn->prepare("INSERT INTO booking_status_logs (booking_id, old_status, new_status, changed_by_role, changed_by_id, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('isssiss', $bookingId, $oldStatus, $newStatus, $changedByRole, $changedById, $notes, $nowStr);
    $ok = $stmt->execute();
    $stmt->close();
    if ($ok && in_array(strtolower((string)$newStatus), ['done', 'completed', 'cancelled'])) {
        syncProviderJobsDone($conn);
    }
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

    $nowStr = phNow();
    $stmt = $conn->prepare("INSERT INTO booking_details (booking_id, field_name, field_value, created_at)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE field_value = VALUES(field_value)");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('isss', $bookingId, $fieldName, $fieldValue, $nowStr);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function ensureRemittancesForProvider($conn, $providerId)
{
    ensureNormalizationSchema($conn);

    
    $query = "SELECT 
                DATE(COALESCE(STR_TO_DATE(date, '%Y-%m-%d'), STR_TO_DATE(date, '%b %d, %Y'), STR_TO_DATE(date, '%M %d, %Y'), created_at)) AS DayDate,
                SUM(price) AS daily_earnings
              FROM bookings
              WHERE provider_id = ? AND status IN ('completed', 'done')
              GROUP BY DayDate";
              
    $stmt = $conn->prepare($query);
    if (!$stmt) return;
    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $res = $stmt->get_result();
    $days = [];
    while ($row = $res->fetch_assoc()) {
        if (!empty($row['DayDate'])) {
            $days[$row['DayDate']] = (float)$row['daily_earnings'];
        }
    }
    $stmt->close();

    $today = date('Y-m-d');

    foreach ($days as $day => $earnings) {
        
        $amountDue = round($earnings * 0.04, 2);
        if ($amountDue <= 0) continue;

        $checkQuery = "SELECT id, status, amount_due FROM remittances WHERE provider_id = ? AND due_date = ?";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bind_param("is", $providerId, $day);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$existing) {
            $refNo = "";
            while (true) {
                $refNo = "REF-" . date('Y', strtotime($day)) . "-" . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $dupCheck = $conn->query("SELECT id FROM remittances WHERE reference_no = '" . $conn->real_escape_string($refNo) . "'");
                if ($dupCheck && $dupCheck->num_rows === 0) {
                    break;
                }
            }

            $status = 'pending';
            if ($day < $today) {
                $status = 'overdue';
            }

            $insertQuery = "INSERT INTO remittances (provider_id, reference_no, amount_due, status, due_date) VALUES (?, ?, ?, ?, ?)";
            $insStmt = $conn->prepare($insertQuery);
            $insStmt->bind_param("isdss", $providerId, $refNo, $amountDue, $status, $day);
            $insStmt->execute();
            $insStmt->close();
        } else {
            $remitId = $existing['id'];
            $status = $existing['status'];
            
            if ($status === 'pending' || $status === 'overdue') {
                $newStatus = $status;
                if ($day < $today && $status === 'pending') {
                    $newStatus = 'overdue';
                }
                
                $updateQuery = "UPDATE remittances SET amount_due = ?, status = ? WHERE id = ?";
                $upStmt = $conn->prepare($updateQuery);
                $upStmt->bind_param("dsi", $amountDue, $newStatus, $remitId);
                $upStmt->execute();
                $upStmt->close();
            }
        }
    }
}

function sendProviderNotification($conn, $providerId, $type, $title, $message, $icon = null, $referenceId = null)
{
    ensureNormalizationSchema($conn);
    
    $providerId = (int)$providerId;
    if ($providerId <= 0 || !($conn instanceof mysqli)) return false;
    $type = !empty($type) ? trim((string)$type) : 'general';
    
    $nowStr = phNow();
    $stmt = $conn->prepare("INSERT INTO provider_notifications (provider_id, type, reference_id, title, message, icon, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
    if (!$stmt) return false;
    
    $stmt->bind_param("isissss", $providerId, $type, $referenceId, $title, $message, $icon, $nowStr);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

function sendUserNotification($conn, $userId, $title, $message, $icon = 'bell')
{
    if (!$conn instanceof mysqli || (int)$userId <= 0) return false;
    $nowStr = phNow();
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, ?, ?, ?, 0, ?)");
    if (!$stmt) return false;
    $stmt->bind_param("issss", $userId, $title, $message, $icon, $nowStr);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}







function cancelExpiredMatchingBookings($conn, $specificBookingId = 0)
{
    if (!$conn instanceof mysqli) return 0;

    $threeMinsAgo = phNow(-180);
    $where = "WHERE status = 'pending' AND created_at <= ?";
    if ($specificBookingId > 0) {
        $where .= " AND id = " . (int)$specificBookingId;
    }

    $stmt = $conn->prepare("SELECT id, user_id, created_at FROM bookings {$where}");
    if (!$stmt) return 0;
    $stmt->bind_param('s', $threeMinsAgo);
    $stmt->execute();
    $res = $stmt->get_result();

    $cancelledCount = 0;
    $nowStr = phNow();
    while ($row = $res->fetch_assoc()) {
        $bid = (int)$row['id'];
        $uid = (int)$row['user_id'];

        $conn->begin_transaction();
        try {
            $upStmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND status = 'pending'");
            if ($upStmt) {
                $upStmt->bind_param('i', $bid);
                $upStmt->execute();
                $affected = $upStmt->affected_rows;
                $upStmt->close();

                if ($affected > 0) {
                    $reqUp = $conn->prepare("UPDATE booking_requests SET status = 'closed', responded_at = ? WHERE booking_id = ? AND status = 'pending'");
                    if ($reqUp) {
                        $reqUp->bind_param('si', $nowStr, $bid);
                        $reqUp->execute();
                        $reqUp->close();
                    }
                    
                    logBookingStatusChange($conn, $bid, 'pending', 'cancelled', 'system', 0, 'Matching timeout - No available workers');

                    sendUserNotification($conn, $uid, 'Booking Cancelled', 'No available workers. Please try again.', 'x-circle');

                    $cancelledCount++;
                }
            }
            $conn->commit();
        } catch (Throwable $e) {
            $conn->rollback();
        }
    }
    $stmt->close();
    return $cancelledCount;
}




function syncProviderOnlineStatuses($conn)
{
    if (!($conn instanceof mysqli)) return;

    $resCol = @$conn->query("SHOW COLUMNS FROM service_providers LIKE 'last_active'");
    if ($resCol && $resCol->num_rows === 0) {
        @$conn->query("ALTER TABLE service_providers ADD COLUMN last_active DATETIME NULL AFTER availability_status");
    }

    $nowStr = phNow();
    $tenMinsAgo = phNow(-600);

    $stmt1 = $conn->prepare("UPDATE service_providers SET last_active = ? WHERE (availability_status = 'online' OR availability_status = 'available') AND last_active IS NULL");
    if ($stmt1) {
        $stmt1->bind_param('s', $nowStr);
        $stmt1->execute();
        $stmt1->close();
    }

    $stmt2 = $conn->prepare("UPDATE service_providers SET availability_status = 'offline' WHERE (availability_status = 'online' OR availability_status = 'available') AND last_active IS NOT NULL AND last_active < ?");
    if ($stmt2) {
        $stmt2->bind_param('s', $tenMinsAgo);
        $stmt2->execute();
        $stmt2->close();
    }
}




function updateProviderActivity($conn, $providerId)
{
    $providerId = (int)$providerId;
    if ($providerId <= 0 || !($conn instanceof mysqli)) return;

    $nowStr = phNow();
    $stmt = $conn->prepare("UPDATE service_providers SET last_active = ? WHERE provider_id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $nowStr, $providerId);
        $stmt->execute();
        $stmt->close();
    }

    syncProviderOnlineStatuses($conn);
}




function syncUserOnlineStatuses($conn)
{
    if (!($conn instanceof mysqli)) return;

    $resIsOnline = @$conn->query("SHOW COLUMNS FROM users LIKE 'is_online'");
    if ($resIsOnline && $resIsOnline->num_rows === 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN is_online TINYINT(1) NOT NULL DEFAULT 0");
    }

    $resLastActive = @$conn->query("SHOW COLUMNS FROM users LIKE 'last_active'");
    if ($resLastActive && $resLastActive->num_rows === 0) {
        @$conn->query("ALTER TABLE users ADD COLUMN last_active DATETIME NULL");
    }

    $twoMinsAgo = phNow(-120);
    $stmt = $conn->prepare("UPDATE users SET is_online = 0 WHERE is_online = 1 AND (last_active IS NULL OR last_active < ?)");
    if ($stmt) {
        $stmt->bind_param('s', $twoMinsAgo);
        $stmt->execute();
        $stmt->close();
    }
}




function updateUserActivity($conn, $userId)
{
    $userId = (int)$userId;
    if ($userId <= 0 || !($conn instanceof mysqli)) return;

    syncUserOnlineStatuses($conn);

    $nowStr = phNow();
    $stmt = $conn->prepare("UPDATE users SET is_online = 1, last_active = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $nowStr, $userId);
        $stmt->execute();
        $stmt->close();
    }
}





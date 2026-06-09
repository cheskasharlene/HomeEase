<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['provider_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../providers/provider_access.php';
ensureNormalizationSchema($conn);

providerRequireVerifiedApi($conn);

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

ensureBookingRequestsTable($conn);

if ($method === 'GET' && $action === 'live_feed') {
    // Return ALL live pending bookings matching provider's service category
    $providerStmt = $conn->prepare("SELECT s.name AS service_category, LOWER(COALESCE(sp.availability_status, 'offline')) AS availability_status FROM service_providers sp LEFT JOIN services s ON s.id = sp.service_id WHERE sp.provider_id = ? LIMIT 1");
    if (!$providerStmt) {
        echo json_encode(['success' => false, 'message' => 'DB error.']);
        exit;
    }
    $providerStmt->bind_param('i', $providerId);
    $providerStmt->execute();
    $providerRow = $providerStmt->get_result()->fetch_assoc();
    $providerStmt->close();

    if (!$providerRow) {
        echo json_encode(['success' => false, 'message' => 'Provider not found.']);
        exit;
    }

    $provService = strtolower(trim((string)($providerRow['service_category'] ?? '')));
    $provAvailability = strtolower(trim((string)($providerRow['availability_status'] ?? 'offline')));
    
    // Enforce online status to receive live booking requests.
    $isOnline = ($provAvailability === 'online');
    if (!$isOnline) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'live_bookings' => [],
            'is_online' => false,
            'provider_service' => $provService,
            'has_active_job' => false,
            'count' => 0
        ]);
        exit;
    }

    // Check if provider has a truly active booking (not done/completed/cancelled)
    $hasActive = false;
    $activeStmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt
         FROM booking_requests br
         JOIN bookings b ON b.id = br.booking_id
         WHERE br.provider_id = ?
           AND br.status = 'accepted'
           AND b.status NOT IN ('done','completed','cancelled')"
    );
    if ($activeStmt) {
        $activeStmt->bind_param('i', $providerId);
        $activeStmt->execute();
        $activeRow = $activeStmt->get_result()->fetch_assoc();
        $activeStmt->close();
        $hasActive = (int)($activeRow['cnt'] ?? 0) > 0;
    }

    // Get all pending bookings of matching service type (last 2 hours)
    // Build SELECT dynamically based on whether GPS columns exist in bookings table
    $bCols = [];
    $bColRes = $conn->query("SHOW COLUMNS FROM bookings");
    if ($bColRes) { while ($bc = $bColRes->fetch_assoc()) $bCols[] = $bc['Field']; }
    $hasCustomerLat = in_array('customer_lat', $bCols);
    $hasCustomerLng = in_array('customer_lng', $bCols);

    $latSelect = $hasCustomerLat ? 'b.customer_lat' : 'NULL AS customer_lat';
    $lngSelect = $hasCustomerLng ? 'b.customer_lng' : 'NULL AS customer_lng';

        $sql = "SELECT b.id AS booking_id, COALESCE(sv.name, b.service) AS service, b.address, b.price, b.created_at,
                 b.date, b.time_slot, b.notes, {$latSelect}, {$lngSelect},
                 COALESCE(br.fixed_price, b.price) AS fixed_price,
                 COALESCE(br.address, b.address) AS request_address,
                 COALESCE(br.customer_address, b.address) AS customer_address,
                 COALESCE(br.details, b.notes) AS details,
                 COALESCE(br.customer_name, u.name) AS customer_name,
                 COALESCE(br.customer_phone, u.phone) AS customer_phone,
                 COALESCE(p.payment_method, 'cash') AS payment_method,
                 br.id AS request_id, br.status AS request_status
            FROM bookings b
            LEFT JOIN services sv ON sv.id = b.service_id
            LEFT JOIN users u ON u.id = b.user_id
            LEFT JOIN booking_requests br ON br.booking_id = b.id AND br.provider_id = ?
             LEFT JOIN payments p ON p.booking_id = b.id
            WHERE b.status = 'pending'
              AND LOWER(b.service) LIKE ?
              AND b.created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
              AND NOT EXISTS (
                  SELECT 1 FROM booking_requests br2
                  WHERE br2.booking_id = b.id AND br2.status = 'accepted'
              )
            ORDER BY b.created_at DESC
            LIMIT 30";

    $like = '%' . $provService . '%';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('is', $providerId, $like);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Also filter by service key matching
    if ($provService !== '') {
        $rows = array_values(array_filter($rows, function($row) use ($provService) {
            return serviceMatches($provService, (string)($row['service'] ?? ''));
        }));
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'live_bookings' => $rows,
        'is_online' => true,
        'provider_service' => $provService,
        'has_active_job' => $hasActive,
        'count' => count($rows)
    ]);
    exit;
}

// Provider: get payment info for a booking
if ($method === 'GET' && $action === 'payment') {
    $bookingId = (int)($_GET['booking_id'] ?? 0);
    if ($bookingId <= 0) { echo json_encode(['success' => false, 'message' => 'Invalid booking id']); exit; }

    $stmt = $conn->prepare("SELECT p.* FROM payments p WHERE p.booking_id = ? AND p.receiver_provider_id = ? LIMIT 1");
    if (!$stmt) { echo json_encode(['success' => false, 'message' => 'DB error']); exit; }
    $stmt->bind_param('ii', $bookingId, $providerId);
    $stmt->execute();
    $pay = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$pay) { echo json_encode(['success' => false, 'message' => 'No payment record found']); exit; }

    echo json_encode(['success' => true, 'payment' => $pay]);
    exit;
}

if ($method === 'GET') {

    $filter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));

    // First, get the provider's service category
    $providerStmt = $conn->prepare("SELECT s.name AS service_category, LOWER(COALESCE(sp.availability_status, 'offline')) AS availability_status FROM service_providers sp LEFT JOIN services s ON s.id = sp.service_id WHERE sp.provider_id = ? LIMIT 1");
    if (!$providerStmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $providerStmt->bind_param('i', $providerId);
    $providerStmt->execute();
    $providerRow = $providerStmt->get_result()->fetch_assoc();
    $providerStmt->close();

    if (!$providerRow) {
        echo json_encode(['success' => false, 'message' => 'Provider not found.']);
        exit;
    }

    $providerService = (string) ($providerRow['service_category'] ?? '');
    $providerAvailability = strtolower(trim((string) ($providerRow['availability_status'] ?? 'offline')));
    // Do not prevent offline providers from viewing their requests. Availability remains a display-only indicator.

    $where = 'br.provider_id = ?';
    $types = 'i';
    $params = [$providerId];

    if ($filter === 'new') {
        $where .= " AND br.status = 'pending'";
    } elseif ($filter === 'accepted') {
        $where .= " AND br.status = 'accepted'";
    } elseif ($filter === 'completed') {
        $where .= " AND LOWER(COALESCE(b.status, '')) IN ('done','completed')";
    } elseif ($filter === 'rejected') {
        $where .= " AND br.status = 'declined'";
    }

    $sql = "SELECT br.id, br.booking_id, br.service, br.fixed_price, br.date, br.time_slot, br.address,
                   br.details, br.customer_name, br.customer_phone, br.customer_address, br.status,
                   br.created_at, br.expires_at,
                   COALESCE(b.status, 'pending') AS booking_status
            FROM booking_requests br
            LEFT JOIN bookings b ON b.id = br.booking_id
            WHERE $where
            ORDER BY br.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
        exit;
    }
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if ($providerService !== '') {
        $rows = array_values(array_filter($rows, function ($row) use ($providerService) {
            return serviceMatches($providerService, (string) ($row['service'] ?? ''));
        }));
    } else {
        $rows = [];
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'requests' => $rows, 'provider_service' => $providerService]);
    exit;
}

// Accept directly from live feed by booking_id
if ($method === 'POST' && $action === 'accept_booking') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if ($bookingId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Lock the booking row
        $stmt = $conn->prepare("SELECT id, status, service, user_id FROM bookings WHERE id = ? FOR UPDATE");
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) throw new RuntimeException('Booking not found.');
        if (strtolower($booking['status']) !== 'pending') {
            throw new RuntimeException('This booking has already been accepted by another provider.');
        }

        // Get provider's service category
        $provStmt = $conn->prepare("SELECT s.name AS service_category FROM service_providers sp LEFT JOIN services s ON s.id = sp.service_id WHERE sp.provider_id = ? LIMIT 1");
        $provStmt->bind_param('i', $providerId);
        $provStmt->execute();
        $provRow = $provStmt->get_result()->fetch_assoc();
        $provStmt->close();
        if (!$provRow) throw new RuntimeException('Provider not found.');
        $provService = (string)($provRow['service_category'] ?? '');
        if (!serviceMatches($provService, (string)($booking['service'] ?? ''))) {
            throw new RuntimeException('This service does not match your specialty.');
        }

        // Ensure a booking_request row exists for this provider (upsert)
        $conn->query("INSERT IGNORE INTO booking_requests
            (booking_id, provider_id, service, fixed_price, date, time_slot, address, details, status, created_at)
            SELECT id, {$providerId}, service, price, date, time_slot, address, notes, 'pending', NOW()
            FROM bookings WHERE id = {$bookingId}");

        $payMethod = 'cash';
        $pmStmt = $conn->prepare("SELECT payment_method FROM payments WHERE booking_id = ? LIMIT 1");
        if ($pmStmt) {
            $pmStmt->bind_param('i', $bookingId);
            $pmStmt->execute();
            $pmRow = $pmStmt->get_result()->fetch_assoc();
            $pmStmt->close();
            if ($pmRow) {
                $payMethod = strtolower((string) ($pmRow['payment_method'] ?? 'cash'));
            }
        }
        

        $nextStatus = in_array($payMethod, ['gcash', 'bank'], true) ? 'awaiting_payment' : 'progress';
        ensureBookingStatusEnum($conn);

        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('si', $nextStatus, $bookingId);
        $stmt->execute();
        if ($stmt->affected_rows <= 0) {
            $stmt->close();
            throw new RuntimeException('Another provider already accepted this booking.');
        }
        $stmt->close();

        logBookingStatusChange($conn, $bookingId, (string) ($booking['status'] ?? 'pending'), $nextStatus, 'provider', $providerId, 'Accepted by provider');

        updateAssignedProvider($conn, $bookingId, $providerId);

        // Mark this provider's request as accepted
        $conn->query("UPDATE booking_requests SET status='accepted', responded_at=NOW() WHERE booking_id={$bookingId} AND provider_id={$providerId}");
        // Close all other providers' requests
        $conn->query("UPDATE booking_requests SET status='closed', responded_at=NOW() WHERE booking_id={$bookingId} AND provider_id<>{$providerId} AND status='pending'");

        notifyHomeownerAccepted($conn, $bookingId, $providerId);
        // Create expected payment record for this booking (unique fractional amount)
        try {
            createExpectedPayment($conn, $bookingId, $providerId);
        } catch (Throwable $ee) {
            // non-fatal: log silently
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Booking accepted!', 'booking_id' => $bookingId]);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'accept') {

    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request id.']);
        exit;
    }

    $conn->begin_transaction();
    try {
        // Get provider's service category
        $provStmt = $conn->prepare("SELECT s.name AS service_category FROM service_providers sp LEFT JOIN services s ON s.id = sp.service_id WHERE sp.provider_id = ? LIMIT 1");
        $provStmt->bind_param('i', $providerId);
        $provStmt->execute();
        $provRow = $provStmt->get_result()->fetch_assoc();
        $provStmt->close();

        if (!$provRow) {
            throw new RuntimeException('Provider not found.');
        }
        $providerService = (string) ($provRow['service_category'] ?? '');

        // Get request with booking details
        $stmt = $conn->prepare("SELECT booking_id, status, service FROM booking_requests WHERE id = ? AND provider_id = ? FOR UPDATE");
        $stmt->bind_param('ii', $requestId, $providerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new RuntimeException('Request not found.');
        }

        if ($row['status'] !== 'pending') {
            throw new RuntimeException('Request is already closed.');
        }

        // Validate service type matches
        $requestService = (string) ($row['service'] ?? '');
        if (!serviceMatches($providerService, $requestService)) {
            throw new RuntimeException('This request service does not match your specialty.');
        }

        $bookingId = (int) $row['booking_id'];

        $payMethod = 'cash';
        $pmStmt = $conn->prepare("SELECT payment_method FROM payments WHERE booking_id = ? LIMIT 1");
        if ($pmStmt) {
            $pmStmt->bind_param('i', $bookingId);
            $pmStmt->execute();
            $pmRow = $pmStmt->get_result()->fetch_assoc();
            $pmStmt->close();
            if ($pmRow) {
                $payMethod = strtolower((string) ($pmRow['payment_method'] ?? 'cash'));
            }
        }
        $nextStatus = in_array($payMethod, ['gcash', 'bank'], true) ? 'awaiting_payment' : 'progress';
        ensureBookingStatusEnum($conn);

        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND LOWER(status) = 'pending'");
        $stmt->bind_param('si', $nextStatus, $bookingId);
        $stmt->execute();
        $bookingUpdated = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$bookingUpdated) {
            $stmt = $conn->prepare("UPDATE booking_requests SET status = 'closed', responded_at = NOW() WHERE booking_id = ? AND provider_id = ? AND status = 'pending'");
            $stmt->bind_param('ii', $bookingId, $providerId);
            $stmt->execute();
            $stmt->close();
            $conn->commit();
            echo json_encode(['success' => false, 'message' => 'Another provider already accepted this booking.']);
            exit;
        }

        logBookingStatusChange($conn, $bookingId, 'pending', $nextStatus, 'provider', $providerId, 'Accepted by provider');

        updateAssignedProvider($conn, $bookingId, $providerId);

        $stmt = $conn->prepare("UPDATE booking_requests SET status = 'accepted', responded_at = NOW() WHERE booking_id = ? AND provider_id = ? AND status = 'pending'");
        $stmt->bind_param('ii', $bookingId, $providerId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE booking_requests SET status = 'closed', responded_at = NOW() WHERE booking_id = ? AND provider_id <> ? AND status = 'pending'");
        $stmt->bind_param('ii', $bookingId, $providerId);
        $stmt->execute();
        $stmt->close();

        notifyHomeownerAccepted($conn, $bookingId, $providerId);
        try {
            createExpectedPayment($conn, $bookingId, $providerId);
        } catch (Throwable $ee) {
            // ignore
        }
        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Booking accepted successfully.', 'booking_id' => $bookingId]);
    } catch (Throwable $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'decline') {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request id.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE booking_requests SET status = 'declined', responded_at = NOW() WHERE id = ? AND provider_id = ? AND status = 'pending'");
    $stmt->bind_param('ii', $requestId, $providerId);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    echo json_encode(['success' => $ok, 'message' => $ok ? 'Request declined.' : 'Request already closed.']);
    exit;
}

if ($method === 'POST' && $action === 'complete') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    if ($bookingId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
        exit;
    }

    // Verify this provider owns the accepted request
    $chk = $conn->prepare("SELECT id FROM booking_requests WHERE booking_id = ? AND provider_id = ? AND status = 'accepted' LIMIT 1");
    $chk->bind_param('ii', $bookingId, $providerId);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'No accepted booking found.']);
        exit;
    }
    $chk->close();

    // Block completion until online payment is confirmed
    $payStmt = $conn->prepare("SELECT payment_method, payment_status FROM payments WHERE booking_id = ? LIMIT 1");
    if ($payStmt) {
        $payStmt->bind_param('i', $bookingId);
        $payStmt->execute();
        $payRow = $payStmt->get_result()->fetch_assoc();
        $payStmt->close();
        if ($payRow) {
            $pm = strtolower((string)($payRow['payment_method'] ?? ''));
            $ps = strtolower((string)($payRow['payment_status'] ?? ''));
            if (in_array($pm, ['gcash', 'bank'], true) && $ps !== 'completed') {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Cannot complete job until client payment is confirmed.']);
                exit;
            }
        }
    }

    $conn->begin_transaction();
    try {
        $oldStatus = null;
        $stOld = $conn->prepare("SELECT status FROM bookings WHERE id = ? LIMIT 1");
        if ($stOld) {
            $stOld->bind_param('i', $bookingId);
            $stOld->execute();
            $oldRow = $stOld->get_result()->fetch_assoc();
            $stOld->close();
            $oldStatus = $oldRow['status'] ?? null;
        }

        $upd = $conn->prepare("UPDATE bookings SET status = 'done' WHERE id = ?");
        $upd->bind_param('i', $bookingId);
        $upd->execute();
        $upd->close();

        if ($oldStatus !== null && $oldStatus !== 'done') {
            logBookingStatusChange($conn, $bookingId, $oldStatus, 'done', 'provider', $providerId, 'Marked complete by provider');
        }

        $updR = $conn->prepare("UPDATE booking_requests SET status = 'closed', responded_at = NOW() WHERE booking_id = ? AND provider_id = ? AND status = 'accepted'");
        $updR->bind_param('ii', $bookingId, $providerId);
        $updR->execute();
        $updR->close();

        // Notify the client
        $bkRow = $conn->query("SELECT user_id, service FROM bookings WHERE id = {$bookingId} LIMIT 1")->fetch_assoc();
        if ($bkRow) {
            $uid = (int)$bkRow['user_id'];
            $svc = $conn->real_escape_string((string)$bkRow['service']);
            $conn->query("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at)
                VALUES ({$uid}, 'Service Complete', 'Your {$svc} service has been completed. Please leave a review!', 'house_cleaner', 0, NOW())");
        }

        $conn->commit();
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Booking marked as complete.']);
    } catch (Throwable $e) {
        $conn->rollback();
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST' && $action === 'update_location') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $lat = (float)($_POST['lat'] ?? 0);
    $lng = (float)($_POST['lng'] ?? 0);

    // Safely ensure columns exist (no crash if already present)
    _safeAddColumn($conn, 'bookings', 'provider_lat', 'DECIMAL(10,8) NULL');
    _safeAddColumn($conn, 'bookings', 'provider_lng', 'DECIMAL(11,8) NULL');

    if ($bookingId > 0 && $lat && $lng) {
        $stmt = $conn->prepare("UPDATE bookings SET provider_lat = ?, provider_lng = ? WHERE id = ?");
        $stmt->bind_param('ddi', $lat, $lng, $bookingId);
        $stmt->execute();
    }
    ob_end_clean();
    echo json_encode(['success' => true]);
    exit;
}

// Fallthrough - unknown request
ob_end_clean();
echo json_encode(['success' => false, 'message' => 'Unknown request.']);

function ensureBookingRequestsTable(mysqli $conn): void
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
    $conn->query($sql);
}

/**
 * Safely add a column only if it doesn't already exist.
 * Prevents fatal mysqli_sql_exception: Duplicate column name.
 */
function _safeAddColumn(mysqli $conn, string $table, string $column, string $definition): void
{
    $res = $conn->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if ($res && $res->num_rows === 0) {
        $conn->query("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
    }
}

function normalizeServiceKey(string $value): string
{
    $v = strtolower(trim($value));
    $v = preg_replace('/[^a-z0-9\s]/', ' ', $v);
    if ($v === '') {
        return '';
    }

    if (strpos($v, 'clean') !== false) return 'clean';
    if (strpos($v, 'plumb') !== false) return 'plumb';
    if (strpos($v, 'electric') !== false) return 'electric';
    if (strpos($v, 'paint') !== false) return 'paint';
    if (strpos($v, 'laundry') !== false) return 'laundry';
    if (strpos($v, 'carpenter') !== false) return 'carpenter';
    if (strpos($v, 'helper') !== false) return 'helper';
    if (strpos($v, 'appliance') !== false) return 'appliance';
    if (strpos($v, 'garden') !== false) return 'garden';

    return $v;
}

function serviceMatches(string $providerService, string $requestService): bool
{
    $p = normalizeServiceKey($providerService);
    $r = normalizeServiceKey($requestService);
    if ($p === '' || $r === '') {
        return false;
    }
    if ($p === $r) {
        return true;
    }
    return stripos($r, $p) !== false || stripos($p, $r) !== false;
}

function updateAssignedProvider(mysqli $conn, int $bookingId, int $providerId): void
{
    $cols = [];
    $res = $conn->query('SHOW COLUMNS FROM bookings');
    if ($res) {
        while ($c = $res->fetch_assoc()) {
            $cols[] = $c['Field'];
        }
    }

    if (in_array('provider_id', $cols, true)) {
        $stmt = $conn->prepare('UPDATE bookings SET provider_id = ? WHERE id = ?');
        $stmt->bind_param('ii', $providerId, $bookingId);
        $stmt->execute();
        $stmt->close();
    }
}

function notifyHomeownerAccepted(mysqli $conn, int $bookingId, int $providerId): void
{
    $bookingStmt = $conn->prepare('SELECT user_id, service, date, time_slot, price FROM bookings WHERE id = ? LIMIT 1');
    if (!$bookingStmt) {
        return;
    }
    $bookingStmt->bind_param('i', $bookingId);
    $bookingStmt->execute();
    $booking = $bookingStmt->get_result()->fetch_assoc();
    $bookingStmt->close();
    if (!$booking) {
        return;
    }

    $providerName = 'A provider';
    $provStmt = $conn->prepare('SELECT full_name AS name FROM service_providers WHERE provider_id = ? LIMIT 1');
    if ($provStmt) {
        $provStmt->bind_param('i', $providerId);
        $provStmt->execute();
        $prow = $provStmt->get_result()->fetch_assoc();
        $provStmt->close();
        if (!empty($prow['name'])) {
            $providerName = (string) $prow['name'];
        }
    }

    $payMethod = 'cash';
    $pmStmt = $conn->prepare("SELECT payment_method FROM payments WHERE booking_id = ? LIMIT 1");
    if ($pmStmt) {
        $pmStmt->bind_param('i', $bookingId);
        $pmStmt->execute();
        $pmRow = $pmStmt->get_result()->fetch_assoc();
        $pmStmt->close();
        if ($pmRow) {
            $payMethod = strtolower((string) ($pmRow['payment_method'] ?? 'cash'));
        }
    }

    $msg = sprintf(
        '%s accepted your booking for %s on %s %s. Fixed price: ₱%s',
        $providerName,
        (string) ($booking['service'] ?? 'service'),
        (string) ($booking['date'] ?? ''),
        (string) ($booking['time_slot'] ?? ''),
        number_format((float) ($booking['price'] ?? 0), 2)
    );
    if (in_array($payMethod, ['gcash', 'bank'], true)) {
        $label = $payMethod === 'gcash' ? 'GCash' : 'Bank Transfer';
        $msg .= " Please complete your {$label} payment and upload your receipt.";
    }

    $uid = (int) ($booking['user_id'] ?? 0);
    if ($uid <= 0) {
        return;
    }

    $ins = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Confirmed', ?, 'house_cleaner', 0, NOW())");
    if ($ins) {
        $ins->bind_param('is', $uid, $msg);
        $ins->execute();
        $ins->close();
    }
}

/**
 * Create an expected payment row with a unique fractional amount and expiry
 */
function createExpectedPayment(mysqli $conn, int $bookingId, int $providerId): void
{
    require_once __DIR__ . '/db.php';
    ensurePaymentsTable($conn);

    // Check if payment row already exists
    $chk = $conn->prepare("SELECT id, payment_method FROM payments WHERE booking_id = ? LIMIT 1");
    $existing = null;
    if ($chk) {
        $chk->bind_param('i', $bookingId);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();
        $chk->close();
    }

    // Get base price and user_id
    $pstmt = $conn->prepare("SELECT price, user_id FROM bookings WHERE id = ? LIMIT 1");
    $pstmt->bind_param('i', $bookingId);
    $pstmt->execute();
    $prow = $pstmt->get_result()->fetch_assoc();
    $pstmt->close();
    $base = (float)($prow['price'] ?? 0);
    $userId = (int)($prow['user_id'] ?? 0);

    // Generate unique fractional cents between 1 and 99
    $fraction = mt_rand(1, 99) / 100.0;
    $expected = round($base + $fraction, 2);

    $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    if ($existing) {
        // If payment method is not 'cash', update the existing payment record with provider_id, fractional amount and expiry!
        if (strtolower($existing['payment_method']) !== 'cash') {
            $upd = $conn->prepare("UPDATE payments SET amount = ?, receiver_provider_id = ?, expected_until = ?, updated_at = NOW() WHERE id = ?");
            if ($upd) {
                $pid = (int)$existing['id'];
                $upd->bind_param('disi', $expected, $providerId, $expires, $pid);
                $upd->execute();
                $upd->close();
            }
        }
    } else {
        // Create new
        $ins = $conn->prepare("INSERT INTO payments (booking_id, user_id, amount, payment_status, receiver_provider_id, expected_until, created_at) VALUES (?, ?, ?, 'pending', ?, ?, NOW())");
        if ($ins) {
            $ins->bind_param('iidis', $bookingId, $userId, $expected, $providerId, $expires);
            $ins->execute();
            $ins->close();
        }
    }
}

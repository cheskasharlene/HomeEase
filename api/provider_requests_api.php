<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['provider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

require_once __DIR__ . '/db.php';

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

ensureBookingRequestsTable($conn);

if ($method === 'GET') {
    $filter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));

    $where = 'br.provider_id = ?';
    $types = 'i';
    $params = [$providerId];

    if ($filter === 'new') {
        $where .= " AND br.status = 'pending'";
    } elseif ($filter === 'accepted') {
        $where .= " AND br.status = 'accepted'";
    } elseif ($filter === 'completed') {
        $where .= " AND LOWER(COALESCE(b.status, '')) IN ('done','completed')";
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
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'requests' => $rows]);
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
        $stmt = $conn->prepare("SELECT booking_id, status FROM booking_requests WHERE id = ? AND provider_id = ? FOR UPDATE");
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

        $bookingId = (int) $row['booking_id'];

        $stmt = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ? AND LOWER(status) = 'pending'");
        $stmt->bind_param('i', $bookingId);
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

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Booking accepted successfully.']);
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

    if (in_array('technician_id', $cols, true)) {
        $stmt = $conn->prepare('UPDATE bookings SET technician_id = ? WHERE id = ?');
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

    $msg = sprintf(
        '%s accepted your booking for %s on %s %s. Fixed price: ₱%s',
        $providerName,
        (string) ($booking['service'] ?? 'service'),
        (string) ($booking['date'] ?? ''),
        (string) ($booking['time_slot'] ?? ''),
        number_format((float) ($booking['price'] ?? 0), 2)
    );

    $uid = (int) ($booking['user_id'] ?? 0);
    if ($uid <= 0) {
        return;
    }

    $ins = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Confirmed', ?, 'cleaning', 0, NOW())");
    if ($ins) {
        $ins->bind_param('is', $uid, $msg);
        $ins->execute();
        $ins->close();
    }
}

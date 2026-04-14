<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['provider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../providers/provider_access.php';

providerRequireVerifiedApi($conn);

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$providerSpecialty = trim((string) ($_SESSION['provider_specialty'] ?? ''));
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim((string) ($_GET['action'] ?? $_POST['action'] ?? ''));

$colRes = $conn->query("SHOW COLUMNS FROM bookings");
if (!$colRes) {
    echo json_encode(['success' => true, 'bookings' => []]);
    exit;
}

$bcols = [];
while ($c = $colRes->fetch_assoc()) {
    $bcols[] = $c['Field'];
}

$has = function (string $col) use ($bcols): bool {
    return in_array($col, $bcols, true);
};

if ($method === 'POST' && $action === 'update_status') {
    $bookingId = (int) ($_POST['id'] ?? 0);
    $statusIn = strtolower(trim((string) ($_POST['status'] ?? '')));

    $statusMap = [
        'pending' => 'pending',
        'confirmed' => 'progress',
        'completed' => 'done',
        'cancelled' => 'cancelled',
        'progress' => 'progress',
        'done' => 'done',
    ];

    if (!$bookingId || !isset($statusMap[$statusIn])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status update request.']);
        exit;
    }

    $dbStatus = $statusMap[$statusIn];
    $ownerClause = "1 = 0";
    $types = 'si';
    $params = [$dbStatus, $bookingId];

    if ($has('provider_id')) {
        $ownerClause = "provider_id = ?";
        $types .= 'i';
        $params[] = $providerId;
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking system not properly configured.']);
        exit;
    }

    $sql = "UPDATE bookings SET status = ? WHERE id = ? AND $ownerClause";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    echo json_encode(['success' => $ok, 'message' => $ok ? 'Booking updated.' : 'No changes made.']);
    exit;
}

$statusFilter = trim((string) ($_GET['status'] ?? ''));
$search = trim((string) ($_GET['search'] ?? ''));

$select = "b.id, b.service, b.status, b.date, b.address, b.price, b.created_at, COALESCE(u.name, 'Client') AS client_name";
if ($has('time_slot')) {
    $select .= ", b.time_slot";
}

$where = [];
$params = [];
$types = '';

if ($has('provider_id')) {
    $where[] = "b.provider_id = ?";
    $types .= 'i';
    $params[] = $providerId;
} else {
    echo json_encode(['success' => false, 'message' => 'Booking system not properly configured.']);
    exit;
}

if ($statusFilter !== '' && strtolower($statusFilter) !== 'all') {
    $statusRaw = strtolower($statusFilter);
    if ($statusRaw === 'confirmed') {
        $where[] = "LOWER(b.status) IN ('confirmed','progress','active')";
    } elseif ($statusRaw === 'completed') {
        $where[] = "LOWER(b.status) IN ('done','completed')";
    } elseif ($statusRaw === 'pending') {
        $where[] = "LOWER(b.status) = 'pending'";
    } else {
        $where[] = "LOWER(b.status) = ?";
        $types .= 's';
        $params[] = $statusRaw;
    }
}

if ($search !== '') {
    $where[] = "(LOWER(b.service) LIKE ? OR LOWER(COALESCE(u.name, '')) LIKE ? OR LOWER(COALESCE(b.address, '')) LIKE ?)";
    $like = '%' . strtolower($search) . '%';
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
$orderSql = $has('time_slot')
    ? "ORDER BY b.date ASC, b.time_slot ASC, b.created_at ASC"
    : "ORDER BY b.date ASC, b.created_at ASC";

$sql = "SELECT $select
        FROM bookings b
        LEFT JOIN users u ON b.user_id = u.id
        $whereSql
        $orderSql";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
    exit;
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$bookings = array_map(function (array $r): array {
    $dateRaw = (string) ($r['date'] ?? '');
    $timeRaw = trim((string) ($r['time_slot'] ?? ''));

    $ts = strtotime($dateRaw);
    $date = $ts ? date('Y-m-d', $ts) : '';

    if ($timeRaw === '' && $ts) {
        $timePart = date('H:i:s', $ts);
        if ($timePart !== '00:00:00') {
            $timeRaw = date('g:i A', $ts);
        }
    }

    $statusRaw = strtolower((string) ($r['status'] ?? 'pending'));
    $status = 'Pending';
    if (in_array($statusRaw, ['done', 'completed'], true)) {
        $status = 'Completed';
    } elseif (in_array($statusRaw, ['progress', 'confirmed', 'active'], true)) {
        $status = 'Confirmed';
    } elseif ($statusRaw === 'cancelled') {
        $status = 'Cancelled';
    }

    return [
        'id' => (int) ($r['id'] ?? 0),
        'date' => $date,
        'time' => $timeRaw !== '' ? $timeRaw : 'All day',
        'service' => (string) ($r['service'] ?? 'Service'),
        'client_name' => (string) ($r['client_name'] ?? 'Client'),
        'price' => (float) ($r['price'] ?? 0),
        'status' => $status,
        'status_raw' => $statusRaw,
        'address' => (string) ($r['address'] ?? ''),
    ];
}, $rows);

echo json_encode(['success' => true, 'bookings' => $bookings]);

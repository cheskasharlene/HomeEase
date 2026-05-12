<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$providerId = (int) ($_SESSION['provider_id'] ?? 0);

if ($userId <= 0 && $providerId <= 0) {
    respond(false, 'Not logged in.');
}

$bookingId = (int) ($_GET['booking_id'] ?? 0);

$cols = [];
$colRes = $conn->query('SHOW COLUMNS FROM bookings');
if ($colRes) {
    while ($c = $colRes->fetch_assoc()) {
        $cols[] = (string) ($c['Field'] ?? '');
    }
}

$hasProviderId = in_array('provider_id', $cols, true);
$hasTimeSlot = in_array('time_slot', $cols, true);
$hasNotes = in_array('notes', $cols, true);
$hasPrice = in_array('price', $cols, true);

ensureBookingRequestsTable($conn);
ensureProviderReviewsTable($conn);

$acceptedStatuses = "('confirmed','progress','active')";

if ($providerId > 0) {
    $select = 'b.id, b.service, b.date, b.address, b.status, b.created_at';
    if ($hasTimeSlot) {
        $select .= ', b.time_slot';
    }
    if ($hasNotes) {
        $select .= ', b.notes';
    }
    if ($hasPrice) {
        $select .= ', b.price';
    }

    $select .= ', br.fixed_price, br.details, br.customer_name, br.customer_phone, br.customer_address';
    $select .= ', u.name AS user_name, u.phone AS user_phone, u.address AS user_address';
    $select .= ', b.customer_lat, b.customer_lng';

    $join = "LEFT JOIN booking_requests br ON br.booking_id = b.id AND br.provider_id = ? AND br.status = 'accepted'";
    $join .= ' LEFT JOIN users u ON b.user_id = u.id';

    $where = "br.id IS NOT NULL";
    $types = 'i';
    $params = [$providerId];

    if ($hasProviderId) {
        $where .= ' AND b.provider_id = ?';
        $types .= 'i';
        $params[] = $providerId;
    }

    if ($bookingId > 0) {
        $where .= ' AND b.id = ?';
        $types .= 'i';
        $params[] = $bookingId;
    }

    $order = $bookingId > 0 ? '' : ' ORDER BY b.created_at DESC LIMIT 1';
    $sql = "SELECT $select FROM bookings b $join WHERE $where$order";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        respond(false, 'DB error: ' . $conn->error);
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        respond(false, 'No accepted booking found.');
    }

    $clientName = trim((string) ($row['customer_name'] ?? ''));
    if ($clientName === '') {
        $clientName = (string) ($row['user_name'] ?? 'Client');
    }

    $clientPhone = trim((string) ($row['customer_phone'] ?? ''));
    if ($clientPhone === '') {
        $clientPhone = (string) ($row['user_phone'] ?? '');
    }

    $clientAddress = trim((string) ($row['customer_address'] ?? ''));
    if ($clientAddress === '') {
        $clientAddress = (string) ($row['address'] ?? '');
    }
    if ($clientAddress === '') {
        $clientAddress = (string) ($row['user_address'] ?? '');
    }

    $price = $row['fixed_price'] ?? null;
    if ($price === null && $hasPrice) {
        $price = $row['price'] ?? 0;
    }

    respond(true, '', [
        'role' => 'provider',
        'booking' => [
            'id' => (int) ($row['id'] ?? 0),
            'service' => (string) ($row['service'] ?? ''),
            'date' => (string) ($row['date'] ?? ''),
            'time_slot' => (string) ($row['time_slot'] ?? ''),
            'address' => (string) ($row['address'] ?? ''),
            'notes' => (string) ($row['notes'] ?? ''),
            'details' => (string) ($row['details'] ?? ''),
            'price' => (float) ($price ?? 0),
            'status' => (string) ($row['status'] ?? ''),
            'client_name'    => $clientName,
            'client_phone'   => $clientPhone,
            'client_address' => $clientAddress,
            'customer_lat'   => isset($row['customer_lat']) && $row['customer_lat'] !== null ? (float)$row['customer_lat'] : null,
            'customer_lng'   => isset($row['customer_lng']) && $row['customer_lng'] !== null ? (float)$row['customer_lng'] : null,
        ],
    ]);
}

$select = 'b.id, b.service, b.date, b.address, b.status, b.created_at';
if ($hasTimeSlot) {
    $select .= ', b.time_slot';
}
if ($hasNotes) {
    $select .= ', b.notes';
}
if ($hasPrice) {
    $select .= ', b.price';
}

$select .= ', br.details, br.fixed_price, br.provider_id AS request_provider_id';
$select .= ', sp.provider_id AS provider_id, sp.full_name AS provider_name, sp.contact_number AS provider_phone,';
$select .= ' sp.rating AS provider_rating, sp.jobs_done AS provider_jobs, sp.service_category AS provider_service';

$join = "LEFT JOIN booking_requests br ON br.booking_id = b.id AND br.status = 'accepted'";
if ($hasProviderId) {
    $join .= ' LEFT JOIN service_providers sp ON sp.provider_id = COALESCE(b.provider_id, br.provider_id)';
} else {
    $join .= ' LEFT JOIN service_providers sp ON sp.provider_id = br.provider_id';
}

$where = "b.user_id = ? AND (LOWER(b.status) IN $acceptedStatuses OR br.id IS NOT NULL)";
$types = 'i';
$params = [$userId];

if ($bookingId > 0) {
    $where .= ' AND b.id = ?';
    $types .= 'i';
    $params[] = $bookingId;
}

if (!$hasProviderId) {
    $where .= ' AND br.id IS NOT NULL';
}

$order = $bookingId > 0 ? '' : ' ORDER BY b.created_at DESC LIMIT 1';
$sql = "SELECT $select FROM bookings b $join WHERE $where$order";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    respond(false, 'DB error: ' . $conn->error);
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    respond(false, 'No accepted booking found.');
}

$providerIdResolved = (int) ($row['provider_id'] ?? 0);
if ($providerIdResolved <= 0) {
    $providerIdResolved = (int) ($row['request_provider_id'] ?? 0);
}

$reviewCount = 0;
$reviewAvg = null;
if ($providerIdResolved > 0) {
    $revStmt = $conn->prepare('SELECT COUNT(*) AS review_count, AVG(rating) AS avg_rating FROM provider_reviews WHERE provider_id = ?');
    if ($revStmt) {
        $revStmt->bind_param('i', $providerIdResolved);
        $revStmt->execute();
        $revRow = $revStmt->get_result()->fetch_assoc();
        $revStmt->close();
        $reviewCount = (int) ($revRow['review_count'] ?? 0);
        if ($revRow && $revRow['avg_rating'] !== null) {
            $reviewAvg = (float) $revRow['avg_rating'];
        }
    }
}

$rating = $row['provider_rating'] ?? null;
if ($rating === null || (float) $rating <= 0) {
    $rating = $reviewAvg ?? 0;
}

$price = $row['fixed_price'] ?? null;
if ($price === null && $hasPrice) {
    $price = $row['price'] ?? 0;
}

respond(true, '', [
    'role' => 'client',
    'booking' => [
        'id' => (int) ($row['id'] ?? 0),
        'service' => (string) ($row['service'] ?? ''),
        'date' => (string) ($row['date'] ?? ''),
        'time_slot' => (string) ($row['time_slot'] ?? ''),
        'address' => (string) ($row['address'] ?? ''),
        'notes' => (string) ($row['notes'] ?? ''),
        'details' => (string) ($row['details'] ?? ''),
        'price' => (float) ($price ?? 0),
        'status' => (string) ($row['status'] ?? ''),
        'provider_id' => $providerIdResolved,
        'provider_name' => (string) ($row['provider_name'] ?? 'Service Provider'),
        'provider_phone' => (string) ($row['provider_phone'] ?? ''),
        'provider_service' => (string) ($row['provider_service'] ?? ''),
        'provider_rating' => (float) ($rating ?? 0),
        'provider_review_count' => $reviewCount,
        'provider_jobs' => (int) ($row['provider_jobs'] ?? 0),
    ],
]);

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

function ensureProviderReviewsTable(mysqli $conn): void
{
    $conn->query("CREATE TABLE IF NOT EXISTS provider_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        provider_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_unique_booking_review (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

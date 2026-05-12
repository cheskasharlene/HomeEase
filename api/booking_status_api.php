<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$uid = (int) $_SESSION['user_id'];
$bookingId = (int) ($_GET['booking_id'] ?? 0);

if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking ID.']);
    exit;
}

// Fetch booking + provider info
$sql = "SELECT b.id, b.service, b.date, b.time_slot, b.address, b.price, b.status, b.created_at,
               b.provider_lat, b.provider_lng, b.customer_lat, b.customer_lng,
               br.provider_id AS req_provider_id, br.status AS req_status, br.responded_at,
               sp.full_name AS provider_name, sp.contact_number AS provider_phone,
               sp.service_category AS provider_service, sp.rating AS provider_rating,
               sp.address AS provider_address, sp.jobs_done
        FROM bookings b
        LEFT JOIN booking_requests br ON br.booking_id = b.id AND br.status = 'accepted'
        LEFT JOIN service_providers sp ON sp.provider_id = br.provider_id
        WHERE b.id = ? AND b.user_id = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error.']);
    exit;
}
$stmt->bind_param('ii', $bookingId, $uid);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Booking not found.']);
    exit;
}

$status = $row['status'];
$hasProvider = !empty($row['provider_name']);

// Count pending requests (how many providers were notified)
$pendingCount = 0;
$pendingStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM booking_requests WHERE booking_id = ? AND status = 'pending'");
if ($pendingStmt) {
    $pendingStmt->bind_param('i', $bookingId);
    $pendingStmt->execute();
    $pr = $pendingStmt->get_result()->fetch_assoc();
    $pendingStmt->close();
    $pendingCount = (int)($pr['cnt'] ?? 0);
}

// Use real customer GPS if stored, otherwise default to Sto. Tomas, Batangas
$customerLat = isset($row['customer_lat']) && $row['customer_lat'] !== null
    ? (float)$row['customer_lat'] : 14.1053;
$customerLng = isset($row['customer_lng']) && $row['customer_lng'] !== null
    ? (float)$row['customer_lng'] : 121.1390;

// Batangas Province — service area bounds (excludes Cavite)
$STO_TOMAS_MIN_LAT = 13.30; $STO_TOMAS_MAX_LAT = 14.20;
$STO_TOMAS_MIN_LNG = 120.55; $STO_TOMAS_MAX_LNG = 121.55;

function isInStoTomasServer(float $lat, float $lng): bool {
    global $STO_TOMAS_MIN_LAT, $STO_TOMAS_MAX_LAT, $STO_TOMAS_MIN_LNG, $STO_TOMAS_MAX_LNG;
    return $lat >= $STO_TOMAS_MIN_LAT && $lat <= $STO_TOMAS_MAX_LAT
        && $lng >= $STO_TOMAS_MIN_LNG && $lng <= $STO_TOMAS_MAX_LNG;
}

$providerLat = $row['provider_lat'] !== null ? (float)$row['provider_lat'] : null;
$providerLng = $row['provider_lng'] !== null ? (float)$row['provider_lng'] : null;

/* Simulation fallback — only when provider hasn't sent any GPS yet */
if ($hasProvider && $providerLat === null) {
    // Simulate provider approaching — coordinates drift toward customer over time as fallback
    $acceptedAt = strtotime($row['responded_at'] ?? 'now');
    $elapsed = max(0, time() - $acceptedAt);
    $startOffset = 0.018;
    $progress = min(1.0, $elapsed / 600);
    $providerLat = $customerLat + ($startOffset * (1 - $progress)) + (sin($elapsed * 0.3) * 0.0005);
    $providerLng = $customerLng - ($startOffset * (1 - $progress)) + (cos($elapsed * 0.2) * 0.0005);
} elseif ($status === 'pending' && $providerLat === null) {
    // Show "searching" animation coords — provider icon orbits
    $t = time() % 30;
    $angle = ($t / 30) * 2 * M_PI;
    $providerLat = $customerLat + cos($angle) * 0.012;
    $providerLng = $customerLng + sin($angle) * 0.012;
}

$response = [
    'success'         => true,
    'booking_id'      => (int)$row['id'],
    'status'          => $status,
    'service'         => (string)($row['service'] ?? ''),
    'date'            => (string)($row['date'] ?? ''),
    'time_slot'       => (string)($row['time_slot'] ?? ''),
    'price'           => (float)($row['price'] ?? 0),
    'has_provider'    => $hasProvider,
    'pending_requests'=> $pendingCount,
    'customer_lat'    => $customerLat,
    'customer_lng'    => $customerLng,
    'provider_lat'    => $providerLat,
    'provider_lng'    => $providerLng,
];

if ($hasProvider) {
    $response['provider'] = [
        'name'    => (string)($row['provider_name'] ?? ''),
        'phone'   => (string)($row['provider_phone'] ?? ''),
        'service' => (string)($row['provider_service'] ?? $row['service'] ?? ''),
        'rating'  => (float)($row['provider_rating'] ?? 0),
        'jobs'    => (int)($row['jobs_done'] ?? 0),
        'address' => (string)($row['provider_address'] ?? ''),
        'initials'=> strtoupper(substr($row['provider_name'] ?? 'P', 0, 2)),
    ];
}

echo json_encode($response);

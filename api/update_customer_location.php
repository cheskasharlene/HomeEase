<?php
/**
 * update_customer_location.php
 * Saves the client's real-time GPS to bookings.customer_lat / customer_lng
 * so the provider sees the accurate pin on their map.
 */
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$uid        = (int) $_SESSION['user_id'];
$bookingId  = (int) ($_POST['booking_id'] ?? 0);
$lat        = (float)($_POST['lat'] ?? 0);
$lng        = (float)($_POST['lng'] ?? 0);

if ($bookingId <= 0 || !$lat || !$lng) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid params.']);
    exit;
}

/* Ensure columns exist (safe no-op if already present) */
$cols = [];
$r = $conn->query("SHOW COLUMNS FROM bookings LIKE 'customer_lat'");
if ($r && $r->num_rows === 0) {
    $conn->query("ALTER TABLE bookings ADD COLUMN customer_lat DECIMAL(10,8) NULL");
    $conn->query("ALTER TABLE bookings ADD COLUMN customer_lng DECIMAL(10,8) NULL");
}

/* Update only if this booking belongs to the logged-in user */
$stmt = $conn->prepare(
    "UPDATE bookings SET customer_lat = ?, customer_lng = ? WHERE id = ? AND user_id = ?"
);
if (!$stmt) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'DB error.']);
    exit;
}
$stmt->bind_param('ddii', $lat, $lng, $bookingId, $uid);
$stmt->execute();
$ok = $stmt->affected_rows >= 0; // 0 rows affected = coords unchanged but query is fine
$stmt->close();

ob_end_clean();
echo json_encode(['success' => $ok]);

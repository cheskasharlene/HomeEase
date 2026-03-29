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
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Ensure provider_reviews table exists
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

if ($method === 'POST' && $action === 'add_review') {
    $booking_id = (int)($_POST['booking_id'] ?? 0);
    $provider_id = (int)($_POST['provider_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($booking_id <= 0 || $provider_id <= 0 || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }

    // Verify booking belongs to user and is completed
    $chk = $conn->prepare("SELECT status, technician_id FROM bookings WHERE id = ? AND user_id = ?");
    if (!$chk) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $chk->bind_param("ii", $booking_id, $uid);
    $chk->execute();
    $bRow = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$bRow) {
        echo json_encode(['success' => false, 'message' => 'Booking not found.']);
        exit;
    }

    $bStatus = strtolower($bRow['status'] ?? '');
    if ($bStatus !== 'completed' && $bStatus !== 'done') {
        echo json_encode(['success' => false, 'message' => 'You can only review completed bookings.']);
        exit;
    }
    
    if (empty($bRow['technician_id']) || $bRow['technician_id'] != $provider_id) {
        echo json_encode(['success' => false, 'message' => 'Provider mismatch.']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO provider_reviews (booking_id, provider_id, user_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB configure error.']);
        exit;
    }
    $stmt->bind_param("iiiis", $booking_id, $provider_id, $uid, $rating, $comment);
    if (!$stmt->execute()) {
        if ($stmt->errno === 1062) {
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this booking.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not save review: ' . $conn->error]);
        }
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Update service_providers cached rating
    $upStmt = $conn->prepare("
        UPDATE service_providers sp 
        LEFT JOIN (
            SELECT provider_id, ROUND(AVG(rating), 1) as avg_rating 
            FROM provider_reviews 
            WHERE provider_id = ?
        ) AS r ON r.provider_id = sp.provider_id 
        SET sp.rating = r.avg_rating 
        WHERE sp.provider_id = ?
    ");
    if ($upStmt) {
        $upStmt->bind_param("ii", $provider_id, $provider_id);
        $upStmt->execute();
        $upStmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Review submitted successfully!']);
    exit;
}

if ($method === 'GET' && $action === 'get_reviews') {
    $provider_id = (int)($_GET['provider_id'] ?? 0);
    if ($provider_id <= 0) {
        echo json_encode(['success' => false, 'reviews' => []]);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT r.rating, r.comment, r.created_at, u.name as user_name 
        FROM provider_reviews r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.provider_id = ? 
        ORDER BY r.created_at DESC 
        LIMIT 10
    ");
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'reviews' => $rows]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);

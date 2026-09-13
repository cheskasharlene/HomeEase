<?php







session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);



if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
    exit;
}


$userId     = (int) $_SESSION['user_id'];
$bookingId  = (int) ($_POST['booking_id']  ?? 0);
$providerId = (int) ($_POST['provider_id'] ?? 0);
$rating     = (int) ($_POST['rating']      ?? 0);
$comment    = trim($_POST['comment'] ?? '');


if ($bookingId <= 0 || $providerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking or provider ID.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5 stars.']);
    exit;
}


$comment = $comment !== '' ? htmlspecialchars_decode(strip_tags($comment)) : null;




$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'homease_db';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, 
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}






try {
    $stmt = $pdo->prepare("
        SELECT id, status, provider_id
        FROM   bookings
        WHERE  id      = :booking_id
          AND  user_id = :user_id
        LIMIT 1
    ");
    $stmt->execute([':booking_id' => $bookingId, ':user_id' => $userId]);
    $booking = $stmt->fetch();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error verifying booking.']);
    exit;
}

if (!$booking) {
    
    echo json_encode(['success' => false, 'message' => 'Booking not found or access denied.']);
    exit;
}


if ((int) $booking['provider_id'] !== $providerId) {
    echo json_encode(['success' => false, 'message' => 'Provider mismatch for this booking.']);
    exit;
}


$status = strtolower(trim($booking['status']));
if (!in_array($status, ['completed', 'done'], true)) {
    echo json_encode(['success' => false, 'message' => 'You can only review a completed booking.']);
    exit;
}



try {
    $insertStmt = $pdo->prepare("
        INSERT INTO provider_reviews
            (booking_id, provider_id, user_id, rating, comment, created_at)
        VALUES
            (:booking_id, :provider_id, :user_id, :rating, :comment, NOW())
        ON DUPLICATE KEY UPDATE
            rating = VALUES(rating),
            comment = VALUES(comment),
            created_at = NOW()
    ");
    $insertStmt->execute([
        ':booking_id'  => $bookingId,
        ':provider_id' => $providerId,
        ':user_id'     => $userId,
        ':rating'      => $rating,
        ':comment'     => $comment,
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Could not save your review. Please try again.']);
    exit;
}




try {
    $ratingStmt = $pdo->prepare("
        UPDATE service_providers
        SET    rating = (
            SELECT ROUND(AVG(rating), 1)
            FROM   provider_reviews
            WHERE  provider_id = :provider_id
        )
        WHERE  provider_id = :provider_id2
    ");
    $ratingStmt->execute([
        ':provider_id'  => $providerId,
        ':provider_id2' => $providerId,
    ]);
} catch (PDOException $e) {
    
    error_log('submit_review.php: Failed to update cached rating for provider ' . $providerId);
}


echo json_encode([
    'success' => true,
    'message' => 'Review submitted successfully! Thank you for your feedback.',
]);

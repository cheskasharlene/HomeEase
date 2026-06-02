<?php
/**
 * submit_review.php — Client Review & Rating Submission
 * -------------------------------------------------------
 * Accepts POST with: booking_id, provider_id, rating (1–5), comment (optional)
 * Returns JSON { "success": true/false, "message": "..." }
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

// ── 1. AUTHENTICATION CHECK ──────────────────────────────────────────────────
// Only logged-in clients may submit reviews.
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorised. Please log in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required.']);
    exit;
}

// ── 2. INPUT SANITISATION ────────────────────────────────────────────────────
$userId     = (int) $_SESSION['user_id'];
$bookingId  = (int) ($_POST['booking_id']  ?? 0);
$providerId = (int) ($_POST['provider_id'] ?? 0);
$rating     = (int) ($_POST['rating']      ?? 0);
$comment    = trim($_POST['comment'] ?? '');

// Basic format validation — DB constraints will catch the rest.
if ($bookingId <= 0 || $providerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid booking or provider ID.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5 stars.']);
    exit;
}

// Sanitise comment: allow empty (nullable), strip harmful tags.
$comment = $comment !== '' ? htmlspecialchars_decode(strip_tags($comment)) : null;

// ── 3. PDO DATABASE CONNECTION ───────────────────────────────────────────────
// Re-use the same credentials already defined in db.php without
// importing the whole file (which creates a MySQLi $conn we don't need).
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
            PDO::ATTR_EMULATE_PREPARES   => false, // Force native prepared statements
        ]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// ── 4. BOOKING OWNERSHIP & COMPLETION VERIFICATION ──────────────────────────
// Confirm that:
//   (a) The booking exists and belongs to THIS client (user_id = $userId).
//   (b) The booking's provider matches the submitted provider_id (prevents spoofing).
//   (c) The booking has a completed/done status — you can't review a pending job.
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
    // Either the booking doesn't exist or it belongs to another user.
    echo json_encode(['success' => false, 'message' => 'Booking not found or access denied.']);
    exit;
}

// Confirm the provider_id the client submitted matches what's on the booking.
if ((int) $booking['provider_id'] !== $providerId) {
    echo json_encode(['success' => false, 'message' => 'Provider mismatch for this booking.']);
    exit;
}

// Only allow reviews for completed bookings.
$status = strtolower(trim($booking['status']));
if (!in_array($status, ['completed', 'done'], true)) {
    echo json_encode(['success' => false, 'message' => 'You can only review a completed booking.']);
    exit;
}

// ── 5. DUPLICATE REVIEW GUARD ────────────────────────────────────────────────
// The table has a UNIQUE KEY on booking_id, but checking here gives a
// friendlier error message and avoids an unnecessary INSERT attempt.
try {
    $dupStmt = $pdo->prepare("
        SELECT id FROM provider_reviews
        WHERE  booking_id = :booking_id
        LIMIT 1
    ");
    $dupStmt->execute([':booking_id' => $bookingId]);
    if ($dupStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this booking.']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error checking existing review.']);
    exit;
}

// ── 6. INSERT REVIEW ─────────────────────────────────────────────────────────
// All values are bound via named parameters — no raw interpolation.
try {
    $insertStmt = $pdo->prepare("
        INSERT INTO provider_reviews
            (booking_id, provider_id, user_id, rating, comment, created_at)
        VALUES
            (:booking_id, :provider_id, :user_id, :rating, :comment, NOW())
    ");
    $insertStmt->execute([
        ':booking_id'  => $bookingId,
        ':provider_id' => $providerId,
        ':user_id'     => $userId,
        ':rating'      => $rating,
        ':comment'     => $comment,
    ]);
} catch (PDOException $e) {
    // Catch the unique constraint violation (SQLSTATE 23000 / error code 1062)
    // in case two requests slip through the duplicate check above.
    if ($e->getCode() === '23000') {
        echo json_encode(['success' => false, 'message' => 'You have already reviewed this booking.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Could not save your review. Please try again.']);
    }
    exit;
}

// ── 7. UPDATE CACHED PROVIDER RATING ─────────────────────────────────────────
// Recalculate the provider's average rating from all their reviews and store
// it back on service_providers so listing pages don't need an expensive JOIN.
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
    // Non-critical: the review is already saved; just log silently.
    error_log('submit_review.php: Failed to update cached rating for provider ' . $providerId);
}

// ── 8. SUCCESS RESPONSE ──────────────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'message' => 'Review submitted successfully! Thank you for your feedback.',
]);

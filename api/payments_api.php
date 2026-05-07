<?php
/**
 * Payment API
 * Handles payment-related operations: fetch payment details, update payment status
 * All operations are user-scoped for security
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/db.php';

// Check authentication
if (empty($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$uid = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Ensure payments table exists
ensurePaymentsTable($conn);

/**
 * GET /api/payments_api.php?action=detail&booking_id=123
 * Get payment details for a specific booking
 */
if ($method === 'GET' && $action === 'detail') {
    $bookingId = (int) ($_GET['booking_id'] ?? 0);
    
    if ($bookingId <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid booking ID']);
        exit;
    }
    
    // Verify booking belongs to user
    $verifyStmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
    if (!$verifyStmt) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $verifyStmt->bind_param('ii', $bookingId, $uid);
    $verifyStmt->execute();
    $bookingExists = $verifyStmt->get_result()->fetch_assoc();
    $verifyStmt->close();
    
    if (!$bookingExists) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Booking not found or not authorized']);
        exit;
    }
    
    // Get payment details
    $payment = getPaymentByBooking($conn, $uid, $bookingId);
    
    if ($payment) {
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'payment' => $payment
        ]);
    } else {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Payment record not found',
            'payment' => null
        ]);
    }
    exit;
}

/**
 * GET /api/payments_api.php?action=list
 * Get all payments for the logged-in user
 */
if ($method === 'GET' && $action === 'list') {
    $limit = (int) ($_GET['limit'] ?? 50);
    $offset = (int) ($_GET['offset'] ?? 0);
    
    if ($limit > 500) $limit = 500; // Cap the limit
    if ($limit < 1) $limit = 50;
    if ($offset < 0) $offset = 0;
    
    $stmt = $conn->prepare(
        "SELECT p.*, b.service, b.date, b.address, b.status as booking_status
         FROM payments p
         INNER JOIN bookings b ON p.booking_id = b.id
         WHERE p.user_id = ?
         ORDER BY p.created_at DESC
         LIMIT ? OFFSET ?"
    );
    
    if (!$stmt) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param('iii', $uid, $limit, $offset);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get total count
    $countStmt = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE user_id = ?");
    if ($countStmt) {
        $countStmt->bind_param('i', $uid);
        $countStmt->execute();
        $countResult = $countStmt->get_result()->fetch_assoc();
        $total = (int) $countResult['count'];
        $countStmt->close();
    } else {
        $total = count($payments);
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'payments' => $payments,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
    exit;
}

/**
 * GET /api/payments_api.php?action=stats
 * Get payment statistics for the user
 */
if ($method === 'GET' && $action === 'stats') {
    $stmt = $conn->prepare(
        "SELECT 
            payment_method,
            payment_status,
            COUNT(*) as count,
            SUM(amount) as total_amount
         FROM payments
         WHERE user_id = ?
         GROUP BY payment_method, payment_status
         ORDER BY payment_method, payment_status"
    );
    
    if (!$stmt) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
    
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Calculate summary
    $summary = [
        'total_payments' => 0,
        'total_amount' => 0,
        'by_method' => [],
        'by_status' => []
    ];
    
    foreach ($stats as $row) {
        $summary['total_payments'] += (int) $row['count'];
        $summary['total_amount'] += (float) $row['total_amount'];
        
        $method = $row['payment_method'];
        $status = $row['payment_status'];
        
        if (!isset($summary['by_method'][$method])) {
            $summary['by_method'][$method] = ['count' => 0, 'amount' => 0];
        }
        $summary['by_method'][$method]['count'] += (int) $row['count'];
        $summary['by_method'][$method]['amount'] += (float) $row['total_amount'];
        
        if (!isset($summary['by_status'][$status])) {
            $summary['by_status'][$status] = ['count' => 0, 'amount' => 0];
        }
        $summary['by_status'][$status]['count'] += (int) $row['count'];
        $summary['by_status'][$status]['amount'] += (float) $row['total_amount'];
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'statistics' => $summary
    ]);
    exit;
}

// Invalid action
ob_end_clean();
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>

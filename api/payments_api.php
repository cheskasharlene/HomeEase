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

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? ''));

// Ensure payments table exists
ensurePaymentsTable($conn);
ensureBookingStatusEnum($conn);

// Provider actions: confirm or reject payment (provider session, not client user_id)
if ($method === 'POST' && in_array($action, ['provider_confirm', 'provider_reject'], true)) {
    if (empty($_SESSION['provider_id'])) {
        ob_end_clean();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not logged in as provider']);
        exit;
    }
    $providerId = (int) $_SESSION['provider_id'];
    $paymentId = (int) ($_POST['payment_id'] ?? 0);
    if ($paymentId <= 0) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid payment id']);
        exit;
    }

    $pst = $conn->prepare("SELECT * FROM payments WHERE id = ? LIMIT 1");
    $pst->bind_param('i', $paymentId);
    $pst->execute();
    $prow = $pst->get_result()->fetch_assoc();
    $pst->close();
    if (!$prow) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Payment not found']);
        exit;
    }

    if ((int) ($prow['receiver_provider_id'] ?? 0) !== $providerId) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'You are not the assigned receiver']);
        exit;
    }

    if ($action === 'provider_confirm') {
        ensureBookingStatusEnum($conn);
        $conn->begin_transaction();
        try {
            $u1 = $conn->prepare("UPDATE payments SET payment_status='completed', updated_at=NOW() WHERE id = ?");
            $u1->bind_param('i', $paymentId);
            $u1->execute();
            $u1->close();
            $conn->query("UPDATE bookings SET status='progress' WHERE id = " . intval($prow['booking_id']));
            $uid2 = (int) $prow['user_id'];
            $conn->query("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES ({$uid2}, 'Payment Verified', 'Your payment has been confirmed by the worker.', 'wallet', 0, NOW())");
            $conn->commit();
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Payment confirmed']);
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    if ($action === 'provider_reject') {
        $reason = trim($_POST['reason'] ?? 'Rejected by worker');
        ensureDisputesTable($conn);
        $bkid = (int) $prow['booking_id'];
        $paymentRef = $prow['payment_reference'] ?? '';
        $matches = $paymentRef !== '';
        $ins = $conn->prepare("INSERT INTO disputes (booking_id, payment_id, provider_id, reason, matches_system, status, created_at) VALUES (?, ?, ?, ?, ?, 'open', NOW())");
        $ms = $matches ? 1 : 0;
        $ins->bind_param('iiisi', $bkid, $paymentId, $providerId, $reason, $ms);
        $ins->execute();
        $ins->close();

        $uid2 = (int) $prow['user_id'];
        $conn->query("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES ({$uid2}, 'Payment Problem Reported', 'The worker reported a problem with your payment. Admin review has been requested.', 'exclamation-triangle', 0, NOW())");
        @$conn->query("UPDATE service_providers SET warnings = COALESCE(warnings,0) + 1 WHERE provider_id = " . $providerId);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Rejection recorded. Admin will review the dispute.']);
        exit;
    }
}

// Client actions — require user session
if (empty($_SESSION['user_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$uid = (int) $_SESSION['user_id'];

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
        // Also include assigned provider payment channels (only after provider accepted)
        $provId = null;
        $stmtp = $conn->prepare("SELECT receiver_provider_id FROM payments WHERE booking_id = ? LIMIT 1");
        if ($stmtp) {
            $stmtp->bind_param('i', $bookingId);
            $stmtp->execute();
            $prow = $stmtp->get_result()->fetch_assoc();
            $stmtp->close();
            $provId = (int)($prow['receiver_provider_id'] ?? 0);
        }

        $provider = null;
        if ($provId > 0) {
            $pstmt = $conn->prepare("SELECT provider_id, full_name, contact_number, COALESCE(qr_gcash, gcash_qr) AS gcash_qr, COALESCE(qr_bank, bank_qr) AS bank_qr FROM service_providers WHERE provider_id = ? LIMIT 1");
            if ($pstmt) {
                $pstmt->bind_param('i', $provId);
                $pstmt->execute();
                $provider = $pstmt->get_result()->fetch_assoc();
                $pstmt->close();
            }
        }

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'payment' => $payment,
            'provider_payment' => $provider
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

// POST /api/payments_api.php?action=submit
if ($method === 'POST' && $action === 'submit') {
    $bookingId = (int)($_POST['booking_id'] ?? 0);
    $paymentReference = trim($_POST['payment_reference'] ?? '');
    $senderName = trim($_POST['sender_name'] ?? '');

    if ($bookingId <= 0) {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Missing required fields']); exit;
    }

    // Verify booking belongs to user
    $bchk = $conn->prepare("SELECT id, user_id, status FROM bookings WHERE id = ? LIMIT 1");
    $bchk->bind_param('i', $bookingId);
    $bchk->execute();
    $brow = $bchk->get_result()->fetch_assoc();
    $bchk->close();
    if (!$brow || (int)$brow['user_id'] !== $uid) { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Booking not found or not authorized']); exit; }

    // Get payment expectation
    $pstmt = $conn->prepare("SELECT * FROM payments WHERE booking_id = ? LIMIT 1");
    $pstmt->bind_param('i', $bookingId);
    $pstmt->execute();
    $pay = $pstmt->get_result()->fetch_assoc();
    $pstmt->close();

    if (!$pay) { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'No payment expected for this booking']); exit; }

    $paymentMethod = strtolower(trim((string)($pay['payment_method'] ?? '')));
    if (!in_array($paymentMethod, ['gcash', 'bank'], true)) {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'This booking does not require online payment']); exit;
    }

    if (!in_array($pay['payment_status'], ['pending'], true)) {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Payment has already been submitted or completed']); exit;
    }

    // Check time window
    $now = date('Y-m-d H:i:s');
    if (!empty($pay['expected_until']) && $now > $pay['expected_until']) {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Payment time window expired']); exit;
    }

    if ($paymentReference === '') {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Transaction/reference number is required']); exit;
    }
    if ($senderName === '') {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Sender name is required']); exit;
    }

    // Use expected amount directly from DB
    $amount = (float)$pay['amount'];

    // Ensure payment_reference uniqueness
    if ($paymentReference !== '') {
        $rchk = $conn->prepare("SELECT id FROM payments WHERE payment_reference = ? AND booking_id <> ? LIMIT 1");
        $rchk->bind_param('si', $paymentReference, $bookingId);
        $rchk->execute();
        $rrow = $rchk->get_result()->fetch_assoc();
        $rchk->close();
        if ($rrow) { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Transaction reference already used']); exit; }
    }

    // Verify assigned provider exists
    $providerId = (int)($pay['receiver_provider_id'] ?? 0);
    if ($providerId <= 0) { ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Assigned provider not found']); exit; }

    // Handle proof upload (required for online payments)
    $proofPath = null;
    if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Payment receipt image is required']); exit;
    }
    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/../uploads/payments/'; if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fileTmp = $_FILES['payment_proof']['tmp_name']; $fileName = basename($_FILES['payment_proof']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed)) {
            $new = 'proof_' . $bookingId . '_' . time() . '.' . $ext;
            if (move_uploaded_file($fileTmp, $uploadDir . $new)) {
                $proofPath = 'uploads/payments/' . $new;
            }
        }
    }
    if (!$proofPath) {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Invalid receipt image. Use JPG, PNG, or WEBP.']); exit;
    }

    ensureBookingStatusEnum($conn);

    // Update payments row
    $upd = $conn->prepare("UPDATE payments SET payment_reference=?, amount=?, payment_proof_path=?, notes=?, payment_status='submitted', updated_at=NOW() WHERE id = ?");
    $notes = 'Sender: ' . $senderName;
    $pid = (int)$pay['id'];
    $upd->bind_param('sdssi', $paymentReference, $amount, $proofPath, $notes, $pid);
    if ($upd->execute()) {
        $upd->close();
        // Keep booking awaiting provider confirmation
        $conn->query("UPDATE bookings SET status = 'awaiting_payment' WHERE id = " . intval($bookingId));

        // Notify assigned provider that payment was submitted
        if ($providerId > 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS provider_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                provider_id INT NOT NULL,
                title VARCHAR(120) NOT NULL,
                message TEXT,
                icon VARCHAR(32) DEFAULT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_provider_read (provider_id, is_read),
                INDEX idx_provider_created (provider_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $msg = 'Client submitted payment for booking #' . $bookingId . '. Review and confirm it.';
            $conn->query("INSERT INTO provider_notifications (provider_id, title, message, icon, is_read, created_at) VALUES ({$providerId}, 'Payment Submitted', '" . $conn->real_escape_string($msg) . "', 'wallet', 0, NOW())");
        }

        // Notify client about next step for visibility in notifications page
        $conn->query("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES ({$uid}, 'Payment Submitted', 'Your payment proof has been sent to the worker for confirmation.', 'wallet', 0, NOW())");
        ob_end_clean(); echo json_encode(['success' => true, 'message' => 'Payment submitted. Awaiting worker confirmation.']); exit;
    } else {
        ob_end_clean(); echo json_encode(['success' => false, 'message' => 'Failed to record payment']); exit;
    }
}

// Ensure disputes table exists
function ensureDisputesTable($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS disputes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        payment_id INT NOT NULL,
        provider_id INT NOT NULL,
        reason TEXT NULL,
        matches_system TINYINT(1) NOT NULL DEFAULT 0,
        status ENUM('open','resolved','dismissed') NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
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

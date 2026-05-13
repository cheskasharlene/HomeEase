<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/db.php';

$userId     = (int)($_SESSION['user_id']     ?? 0);
$providerId = (int)($_SESSION['provider_id'] ?? 0);

if ($userId <= 0 && $providerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

// Ensure chat_messages table exists
$conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    booking_id   INT NOT NULL,
    sender_role  ENUM('client','provider') NOT NULL,
    sender_id    INT NOT NULL,
    message      TEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read      TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_booking_chat (booking_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method   = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action   = trim((string)($_GET['action'] ?? $_POST['action'] ?? ''));
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing booking_id.']);
    exit;
}

// ── Verify access: client must own booking, provider must have accepted it ──
if ($userId > 0) {
    $chk = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ? LIMIT 1");
    $chk->bind_param('ii', $bookingId, $userId);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }
    $chk->close();
    $senderRole = 'client';
    $senderId   = $userId;
} else {
    $chk = $conn->prepare("SELECT id FROM booking_requests WHERE booking_id = ? AND provider_id = ? AND status = 'accepted' LIMIT 1");
    $chk->bind_param('ii', $bookingId, $providerId);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }
    $chk->close();
    $senderRole = 'provider';
    $senderId   = $providerId;
}

// ── GET: fetch messages (optionally after a given id) ──
if ($method === 'GET') {
    $afterId = (int)($_GET['after_id'] ?? 0);

    // Mark unread messages from the other side as read
    $otherRole = ($senderRole === 'client') ? 'provider' : 'client';
    $markRead = $conn->prepare(
        "UPDATE chat_messages SET is_read = 1
         WHERE booking_id = ? AND sender_role = ? AND is_read = 0"
    );
    $markRead->bind_param('is', $bookingId, $otherRole);
    $markRead->execute();
    $markRead->close();

    $stmt = $conn->prepare(
        "SELECT id, sender_role, sender_id, message, created_at, is_read
         FROM chat_messages
         WHERE booking_id = ? AND id > ?
         ORDER BY created_at ASC
         LIMIT 100"
    );
    $stmt->bind_param('ii', $bookingId, $afterId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Unread count for the requester (messages from the other side not yet read)
    $unreadStmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM chat_messages
         WHERE booking_id = ? AND sender_role = ? AND is_read = 0"
    );
    $unreadStmt->bind_param('is', $bookingId, $otherRole);
    $unreadStmt->execute();
    $unreadRow = $unreadStmt->get_result()->fetch_assoc();
    $unreadStmt->close();

    echo json_encode([
        'success'  => true,
        'messages' => $rows,
        'unread'   => (int)($unreadRow['cnt'] ?? 0),
        'my_role'  => $senderRole,
    ]);
    exit;
}

// ── POST: send a message ──
if ($method === 'POST' && $action === 'send') {
    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '') {
        echo json_encode(['success' => false, 'message' => 'Empty message.']);
        exit;
    }
    if (mb_strlen($message) > 1000) {
        $message = mb_substr($message, 0, 1000);
    }

    $ins = $conn->prepare(
        "INSERT INTO chat_messages (booking_id, sender_role, sender_id, message)
         VALUES (?, ?, ?, ?)"
    );
    $ins->bind_param('isis', $bookingId, $senderRole, $senderId, $message);
    $ins->execute();
    $newId = (int)$conn->insert_id;
    $ins->close();

    echo json_encode(['success' => true, 'id' => $newId, 'my_role' => $senderRole]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown request.']);

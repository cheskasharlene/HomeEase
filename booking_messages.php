<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(200);
    exit;
}

ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/api/db.php';

$userId     = (int)($_SESSION['user_id']     ?? 0);
$providerId = (int)($_SESSION['provider_id'] ?? 0);

if ($userId <= 0 && $providerId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

// Auto-create chat_messages table if it does not exist on the database
@$conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    booking_id   INT NOT NULL,
    sender_role  ENUM('client','provider') NOT NULL,
    sender_id    INT NOT NULL,
    message      TEXT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read      TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_booking_chat (booking_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$method    = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action    = trim((string)($_GET['action'] ?? $_POST['action'] ?? ''));
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);
$debug     = isset($_GET['debug']) || isset($_POST['debug']);

if ($bookingId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing booking_id.']);
    exit;
}

// ── Role & Access Resolution ──
$requestedRole = strtolower(trim((string)($_GET['role'] ?? $_POST['role'] ?? '')));

if ($requestedRole === 'provider' && $providerId > 0) {
    $senderRole = 'provider';
    $senderId   = $providerId;
} elseif ($requestedRole === 'client' && $userId > 0) {
    $senderRole = 'client';
    $senderId   = $userId;
} elseif ($providerId > 0 && $userId <= 0) {
    $senderRole = 'provider';
    $senderId   = $providerId;
} elseif ($userId > 0 && $providerId <= 0) {
    $senderRole = 'client';
    $senderId   = $userId;
} elseif ($providerId > 0) {
    $senderRole = 'provider';
    $senderId   = $providerId;
} elseif ($userId > 0) {
    $senderRole = 'client';
    $senderId   = $userId;
} else {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

if ($debug) {
    echo json_encode([
        'debug' => true,
        'user_id' => $userId,
        'provider_id' => $providerId,
        'requested_role' => $requestedRole,
        'resolved_role' => $senderRole,
        'sender_id' => $senderId,
        'booking_id' => $bookingId
    ]);
    exit;
}

function prepareStmt($conn, $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database query failed: ' . $conn->error]);
        exit;
    }
    return $stmt;
}

if ($method === 'GET') {
    $afterId = (int)($_GET['after_id'] ?? 0);
    $otherRole = ($senderRole === 'client') ? 'provider' : 'client';

    $markRead = prepareStmt($conn, 
        "UPDATE chat_messages SET is_read = 1
         WHERE booking_id = ? AND sender_role = ? AND is_read = 0"
    );
    $markRead->bind_param('is', $bookingId, $otherRole);
    $markRead->execute();
    $markRead->close();

    $stmt = prepareStmt($conn, 
        "SELECT id, sender_role, sender_id, message, created_at, is_read
         FROM chat_messages
         WHERE booking_id = ? AND id > ?
         ORDER BY id ASC
         LIMIT 100"
    );
    $stmt->bind_param('ii', $bookingId, $afterId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $unreadStmt = prepareStmt($conn, 
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

if ($method === 'POST' && $action === 'send') {
    $message = trim((string)($_POST['message'] ?? ''));
    if ($message === '') {
        echo json_encode(['success' => false, 'message' => 'Empty message.']);
        exit;
    }
    if (mb_strlen($message) > 1000) {
        $message = mb_substr($message, 0, 1000);
    }

    $ins = prepareStmt($conn, 
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
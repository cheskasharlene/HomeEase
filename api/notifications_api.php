<?php
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    respond(false, 'Not logged in.');
}

$user_id = (int)$_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];
$action  = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'GET') {
    if ($action === 'count') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $unread = (int)($res['cnt'] ?? 0);
        respond(true, '', ['unread_count' => $unread]);
    }

    $stmt = $conn->prepare(
        "SELECT id, title, message, icon, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $unreadCount = 0;
    foreach ($rows as &$row) {
        $row['id'] = (int)$row['id'];
        $row['is_read'] = (int)$row['is_read'];
        if ($row['is_read'] === 0) {
            $unreadCount++;
        }
        $diff = time() - strtotime($row['created_at']);
        if ($diff < 60)       $row['time'] = 'Just now';
        elseif ($diff < 3600) $row['time'] = floor($diff / 60) . 'm ago';
        elseif ($diff < 86400)$row['time'] = floor($diff / 3600) . 'h ago';
        else                  $row['time'] = floor($diff / 86400) . 'd ago';
    }
    unset($row);

    respond(true, '', [
        'notifications' => $rows,
        'unread_count'  => $unreadCount
    ]);
}

if ($method === 'POST') {
    $rawInput  = file_get_contents('php://input');
    $jsonInput = !empty($rawInput) ? json_decode($rawInput, true) : [];
    if (!is_array($jsonInput)) {
        $jsonInput = [];
    }

    $data = array_merge($_POST, $jsonInput);

    if (!empty($data['mark_all']) || !empty($_GET['mark_all'])) {
        $stmt = $conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        respond(true, 'All marked as read.', ['unread_count' => 0]);
    }

    $notif_id = intval($data['id'] ?? $_GET['id'] ?? 0);
    if ($notif_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        $stmt->close();

        $cntStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
        $cntStmt->bind_param("i", $user_id);
        $cntStmt->execute();
        $cntRes = $cntStmt->get_result()->fetch_assoc();
        $cntStmt->close();
        $unread = (int)($cntRes['cnt'] ?? 0);

        respond(true, 'Marked as read.', ['unread_count' => $unread, 'id' => $notif_id]);
    }

    respond(false, 'Invalid request.');
}
?>
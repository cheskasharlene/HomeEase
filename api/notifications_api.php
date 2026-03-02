<?php
session_start();
require __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    respond(false, 'Not logged in.');
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $conn->prepare(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"
    );
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    respond(true, '', ['notifications' => $rows]);
}

if ($method === 'POST') {
    // Mark ALL as read
    if (!empty($_POST['mark_all'])) {
        $stmt = $conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE user_id = ?"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
        respond(true, 'All marked as read.');
    }

    $notif_id = intval($_POST['id'] ?? 0);
    if ($notif_id > 0) {
        $stmt = $conn->prepare(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?"
        );
        $stmt->bind_param("ii", $notif_id, $user_id);
        $stmt->execute();
        $stmt->close();
        respond(true, 'Marked as read.');
    }

    respond(false, 'Invalid request.');
}
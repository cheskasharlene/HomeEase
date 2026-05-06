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

if (empty($_SESSION['provider_id'])) {
    respond(false, 'Not logged in.');
}

$providerId = (int) $_SESSION['provider_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

ensureProviderNotificationsTable($conn);

if ($method === 'GET') {
    if ($action === 'count') {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM provider_notifications WHERE provider_id = ? AND is_read = 0");
        $stmt->bind_param('i', $providerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $count = (int) ($row['cnt'] ?? 0);
        respond(true, '', ['unread_count' => $count]);
    }

    $stmt = $conn->prepare("SELECT id, title, message, icon, is_read, created_at FROM provider_notifications WHERE provider_id = ? ORDER BY created_at DESC LIMIT 50");
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    respond(true, '', ['notifications' => $rows]);
}

if ($method === 'POST') {
    if (!empty($_POST['mark_all'])) {
        $stmt = $conn->prepare("UPDATE provider_notifications SET is_read = 1 WHERE provider_id = ?");
        $stmt->bind_param('i', $providerId);
        $stmt->execute();
        $stmt->close();
        respond(true, 'All marked as read.');
    }

    $notifId = (int) ($_POST['id'] ?? 0);
    if ($notifId > 0) {
        $stmt = $conn->prepare("UPDATE provider_notifications SET is_read = 1 WHERE id = ? AND provider_id = ?");
        $stmt->bind_param('ii', $notifId, $providerId);
        $stmt->execute();
        $stmt->close();
        respond(true, 'Marked as read.');
    }

    respond(false, 'Invalid request.');
}

respond(false, 'Unsupported request method.');

function ensureProviderNotificationsTable(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS provider_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        title VARCHAR(120) NOT NULL,
        message TEXT,
        icon VARCHAR(32) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_provider_read (provider_id, is_read),
        INDEX idx_provider_created (provider_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}
?>
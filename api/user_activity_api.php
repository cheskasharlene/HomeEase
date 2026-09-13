<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/db.php';

$userId = (int)$_SESSION['user_id'];
updateUserActivity($conn, $userId);

echo json_encode(['success' => true]);
exit;

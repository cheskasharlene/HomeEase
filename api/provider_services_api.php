<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['provider_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

require_once __DIR__ . '/db.php';

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

if ($method === 'POST' && $action === 'toggle_service') {
    echo json_encode(['success' => false, 'message' => 'Under HomeEase policy, each provider is registered for exactly one service specialty. Please contact support to update your registered service category.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown request.']);

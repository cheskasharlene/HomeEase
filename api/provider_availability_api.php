<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['provider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

require_once __DIR__ . '/db.php';

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$colRes = $conn->query('SHOW COLUMNS FROM service_providers');
if (!$colRes) {
    echo json_encode(['success' => false, 'message' => 'Could not read provider schema.']);
    exit;
}

$cols = [];
while ($col = $colRes->fetch_assoc()) {
    $cols[] = $col['Field'];
}

if (!in_array('availability_status', $cols, true)) {
    echo json_encode(['success' => false, 'message' => 'Availability column is missing.']);
    exit;
}

if ($method === 'GET') {
    $stmt = $conn->prepare('SELECT availability_status, COALESCE(is_verified, 0) AS is_verified FROM service_providers WHERE provider_id = ? LIMIT 1');
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'Provider not found.']);
        exit;
    }

    $isVerified = ((int) ($row['is_verified'] ?? 0)) === 1;
    $rawAvailability = strtolower(trim((string) ($row['availability_status'] ?? 'offline')));
    $normalized = in_array($rawAvailability, ['available', 'online'], true) ? 'online' : 'offline';

    echo json_encode([
        'success' => true,
        'availability' => $normalized,
        'availability_raw' => $rawAvailability,
        'is_verified' => $isVerified,
    ]);
    exit;
}

if ($method === 'POST') {
    $checkStmt = $conn->prepare('SELECT COALESCE(is_verified, 0) AS is_verified FROM service_providers WHERE provider_id = ? LIMIT 1');
    if (!$checkStmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $checkStmt->bind_param('i', $providerId);
    $checkStmt->execute();
    $checkRow = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$checkRow) {
        echo json_encode(['success' => false, 'message' => 'Provider not found.']);
        exit;
    }

    $isVerified = ((int) ($checkRow['is_verified'] ?? 0)) === 1;
    $requested = strtolower(trim((string) ($_POST['availability'] ?? 'offline')));
    if (!in_array($requested, ['online', 'offline'], true)) {
        $requested = 'offline';
    }

    if (!$isVerified) {
        $requested = 'offline';
    }

    $dbValue = $requested === 'online' ? 'available' : 'offline';
    $updateStmt = $conn->prepare('UPDATE service_providers SET availability_status = ? WHERE provider_id = ?');
    if (!$updateStmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }
    $updateStmt->bind_param('si', $dbValue, $providerId);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        'success' => true,
        'availability' => $requested,
        'availability_raw' => $dbValue,
        'is_verified' => $isVerified,
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Method not allowed.']);

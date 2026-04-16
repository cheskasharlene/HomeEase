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
    $serviceName = trim($_POST['service_name'] ?? '');
    $active = intval($_POST['active'] ?? 0);

    if (!$serviceName) {
        echo json_encode(['success' => false, 'message' => 'Service name required.']);
        exit;
    }

    // Get current services
    $stmt = $conn->prepare("SELECT service_category FROM service_providers WHERE provider_id = ?");
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $provider = $result->fetch_assoc();
    $stmt->close();

    if (!$provider) {
        echo json_encode(['success' => false, 'message' => 'Provider not found.']);
        exit;
    }

    $currentServices = array_filter(array_map('trim', explode(',', $provider['service_category'] ?? '')));

    // Toggle service
    if ($active) {
        // Add service if not already present
        if (!in_array($serviceName, $currentServices, true)) {
            $currentServices[] = $serviceName;
        }
    } else {
        // Remove service
        $currentServices = array_filter($currentServices, function($s) use ($serviceName) {
            return $s !== $serviceName;
        });
    }

    // Update database
    $newServices = implode(', ', $currentServices);
    $stmt = $conn->prepare("UPDATE service_providers SET service_category = ? WHERE provider_id = ?");
    $stmt->bind_param('si', $newServices, $providerId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        echo json_encode(['success' => true, 'message' => $active ? 'Service added.' : 'Service removed.', 'services' => $currentServices]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown request.']);

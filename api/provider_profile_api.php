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

// Handle photo upload
if ($method === 'POST' && $action === 'upload_photo') {
    $conn->query("ALTER TABLE service_providers ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");
    
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No image file provided or upload failed.']);
        exit;
    }

    $file = $_FILES['photo'];
    if (!preg_match('/^image\/(jpeg|png|gif|webp)$/', $file['type'])) {
        echo json_encode(['success' => false, 'message' => 'Only image files (JPG, PNG, GIF, WebP) are allowed.']);
        exit;
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image size must not exceed 5MB.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../assets/uploads/profile_photos/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'provider_' . $providerId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filePath = $uploadDir . $fileName;
    $dbPath = 'assets/uploads/profile_photos/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save image. Please try again.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE service_providers SET profile_photo=? WHERE provider_id=?");
    $stmt->bind_param("si", $dbPath, $providerId);
    if ($stmt->execute()) {
        $_SESSION['provider_photo'] = $dbPath;
        echo json_encode([
            'success' => true,
            'message' => 'Profile photo updated successfully!',
            'photo_url' => $dbPath . '?t=' . time()
        ]);
        exit;
    }
    
    @unlink($filePath);
    echo json_encode(['success' => false, 'message' => 'Could not update profile photo.']);
    exit;
}

// Handle get photo URL
if ($method === 'GET' && $action === 'get_photo') {
    $conn->query("ALTER TABLE service_providers ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");
    
    $stmt = $conn->prepare("SELECT profile_photo FROM service_providers WHERE provider_id=?");
    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $result = $stmt->get_result();
    $provider = $result->fetch_assoc();
    $stmt->close();

    $photoUrl = '';
    if ($provider && $provider['profile_photo']) {
        $photoUrl = $provider['profile_photo'] . '?t=' . time();
    }

    echo json_encode([
        'success' => true,
        'photo_url' => $photoUrl
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;

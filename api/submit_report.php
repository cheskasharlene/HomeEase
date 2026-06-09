<?php
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php';

if (empty($_SESSION['user_id']) && empty($_SESSION['provider_id'])) {
    respond(false, 'Not logged in.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// Identify the reporter role and ID
if (!empty($_SESSION['user_id'])) {
    $reporter_id = (int)$_SESSION['user_id'];
    $reporter_role = 'client';
} else {
    $reporter_id = (int)$_SESSION['provider_id'];
    $reporter_role = 'provider';
}

$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$booking_id = trim($_POST['booking_id'] ?? '');

if (!$category || !$description) {
    respond(false, 'Category and description are required.');
}

// Normalize categories to match database enum values
$category_mapping = [
    'Property Damage' => 'Damage to Property',
    'No Show' => 'Late / No Show'
];
if (isset($category_mapping[$category])) {
    $category = $category_mapping[$category];
}

// Resolve reported user if booking_id is provided
$reported_user_id = null;
$reported_user_role = null;

if ($booking_id !== '') {
    $booking_stmt = $conn->prepare("SELECT user_id, provider_id FROM bookings WHERE id = ?");
    if ($booking_stmt) {
        $booking_stmt->bind_param("s", $booking_id);
        $booking_stmt->execute();
        $booking = $booking_stmt->get_result()->fetch_assoc();
        $booking_stmt->close();
        
        if ($booking) {
            if ($reporter_role === 'client') {
                // Client reports provider
                $reported_user_id = $booking['provider_id'] ? (int)$booking['provider_id'] : null;
                $reported_user_role = $reported_user_id ? 'provider' : null;
            } else {
                // Provider reports client
                $reported_user_id = $booking['user_id'] ? (int)$booking['user_id'] : null;
                $reported_user_role = $reported_user_id ? 'client' : null;
            }
        }
    }
}

// Handle evidence file upload
$evidence_path = null;
if (!empty($_FILES['evidence']) && $_FILES['evidence']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['evidence'];
    
    // Validate file extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf'];
    if (!in_array($ext, $allowed_exts)) {
        respond(false, 'Only image (JPG, PNG, WEBP, GIF) or PDF files are allowed.');
    }
    
    // Validate mime-type
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];
    if (!in_array($file['type'], $allowed_types)) {
        respond(false, 'Invalid file format.');
    }
    
    // Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        respond(false, 'Evidence file size must not exceed 5MB.');
    }
    
    // Ensure uploads directory exists
    $uploadDir = __DIR__ . '/../uploads/reports/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique name
    $fileName = 'report_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        $evidence_path = 'uploads/reports/' . $fileName;
    } else {
        respond(false, 'Failed to save evidence upload.');
    }
}

// Generate unique report ID
$report_id = 'REP' . date('ymd') . mt_rand(1000, 9999);

// Insert the report
$stmt = $conn->prepare("INSERT INTO incident_reports 
    (report_id, booking_id, reporter_id, reporter_role, reported_user_id, reported_user_role, category, description, evidence_path, status, created_at, updated_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())");

if (!$stmt) {
    respond(false, 'Database error: ' . $conn->error);
}

$db_booking_id = ($booking_id !== '') ? $booking_id : null;

$stmt->bind_param("ssisissss", 
    $report_id, 
    $db_booking_id, 
    $reporter_id, 
    $reporter_role, 
    $reported_user_id, 
    $reported_user_role, 
    $category, 
    $description, 
    $evidence_path
);

if ($stmt->execute()) {
    respond(true, 'Report submitted successfully!', ['report_id' => $report_id]);
} else {
    respond(false, 'Failed to submit report. Please try again.');
}
?>

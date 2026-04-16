<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$filePath = trim($_GET['path'] ?? $_POST['path'] ?? '');

if (!$filePath) {
    echo json_encode(['success' => false, 'message' => 'No path provided']);
    exit;
}

// Remove any ../ from the path to prevent directory traversal
$filePath = str_replace('..\\', '', $filePath);
$filePath = str_replace('../', '', $filePath);

// Construct the full filesystem path
$fullPath = __DIR__ . '/../' . $filePath;
$fullPath = realpath($fullPath);

// Verify the path is still within the project
$projectRoot = realpath(__DIR__ . '/..');
if ($fullPath === false || strpos($fullPath, $projectRoot) !== 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid path']);
    exit;
}

// Check if file exists
if (!file_exists($fullPath)) {
    echo json_encode([
        'success' => false, 
        'message' => 'File not found',
        'path' => $filePath,
        'full_path' => $fullPath,
        'exists' => false
    ]);
    exit;
}

// Check if it's readable
if (!is_readable($fullPath)) {
    echo json_encode([
        'success' => false,
        'message' => 'File is not readable',
        'path' => $filePath,
        'exists' => true
    ]);
    exit;
}

// Get file info
$fileSize = filesize($fullPath);
$mimeType = mime_content_type($fullPath);

echo json_encode([
    'success' => true,
    'message' => 'File exists and is accessible',
    'path' => $filePath,
    'exists' => true,
    'readable' => true,
    'size' => $fileSize,
    'mime_type' => $mimeType
]);

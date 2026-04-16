<?php
session_start();

// Only allow authenticated users to view images
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    die('Unauthorized');
}

$filePath = isset($_GET['path']) ? trim($_GET['path']) : '';

if (!$filePath) {
    http_response_code(400);
    die('Missing path');
}

// Prevent directory traversal
$filePath = str_replace('..\\', '', $filePath);
$filePath = str_replace('../', '', $filePath);

// Build full path from project root
$baseProjectRoot = __DIR__ . '/../';
$fullPath = $baseProjectRoot . $filePath;

// Normalize path slashes for Windows compatibility
$fullPath = str_replace('/', DIRECTORY_SEPARATOR, $fullPath);
$fullPath = realpath($fullPath);

// If realpath returns false, file doesn't exist or path is invalid
if ($fullPath === false) {
    http_response_code(404);
    die('File not found');
}

// Verify it's within allowed directories
$allowedBase1 = realpath($baseProjectRoot . 'assets' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'registration');
$allowedBase2 = realpath($baseProjectRoot . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documents');

$isAllowed = false;
if ($allowedBase1 && strpos($fullPath, $allowedBase1) === 0) {
    $isAllowed = true;
}
if ($allowedBase2 && strpos($fullPath, $allowedBase2) === 0) {
    $isAllowed = true;
}

if (!$isAllowed) {
    http_response_code(403);
    die('Access denied');
}

// Final check - file must exist and be readable
if (!is_file($fullPath) || !is_readable($fullPath)) {
    http_response_code(404);
    die('File not readable');
}

// Detect MIME type
$mimeType = mime_content_type($fullPath);
if (!$mimeType) {
    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    $mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';
}

// Set headers for caching and content type
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($fullPath));
header('Cache-Control: max-age=2592000'); // 30 days
header('Pragma: public');
header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time() + 2592000));

// Read and output the file
readfile($fullPath);
exit;

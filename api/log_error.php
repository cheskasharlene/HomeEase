<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if ($data) {
    $logFile = __DIR__ . '/js_errors.log';
    $message = "[" . date('Y-m-d H:i:s') . "] " . json_encode($data) . "\n";
    file_put_contents($logFile, $message, FILE_APPEND);
}

echo json_encode(['success' => true]);
?>

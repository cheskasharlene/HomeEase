<?php
// Simulate a POST login request and capture raw output
// DELETE THIS FILE AFTER DEBUGGING

$url = 'http://localhost/homeease/api/login.php';
$data = json_encode(['email' => 'test@gmail.com', 'password' => '12345678']);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true); // include response headers

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $headerSize);
$body = substr($response, $headerSize);
curl_close($ch);

header('Content-Type: text/plain');
echo "HTTP Status: $httpCode\n\n";
echo "=== RESPONSE HEADERS ===\n$headers\n";
echo "=== RAW BODY (hex for first 200 bytes) ===\n";
echo bin2hex(substr($body, 0, 200)) . "\n\n";
echo "=== RAW BODY (text) ===\n";
echo htmlspecialchars($body) . "\n";

// Check if valid JSON
$decoded = json_decode($body, true);
echo "\n=== JSON DECODE ===\n";
if ($decoded === null) {
    echo "JSON ERROR: " . json_last_error_msg() . "\n";
    echo "This means PHP is outputting non-JSON content before the JSON response!\n";
} else {
    echo "Valid JSON: " . print_r($decoded, true) . "\n";
}

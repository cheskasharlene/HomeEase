<?php
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

// ensure separate table for service providers
$conn->query(
    "CREATE TABLE IF NOT EXISTS service_providers (
        provider_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        contact_number VARCHAR(30) DEFAULT NULL,
        service_category VARCHAR(100) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        availability_status ENUM('online','offline') DEFAULT 'offline',
        pin_code VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$input   = json_decode(file_get_contents('php://input'), true);
$first   = trim($input['first']    ?? '');
$last    = trim($input['last']     ?? '');
$name    = trim("$first $last");
$email   = trim($input['email']    ?? '');
$phone   = trim($input['phone']    ?? '');
$address = trim($input['address']  ?? '');
$pass    = trim($input['password'] ?? '');
$pin     = trim($input['pin']      ?? '');

if (!$first || !$last || !$email || !$pass) {
    respond(false, 'Please fill in all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
if (strlen($pass) < 8) {
    respond(false, 'Password must be at least 8 characters.');
}
if (!$pin || !preg_match('/^\d{4}$/', $pin)) {
    respond(false, 'A valid 4-digit PIN is required.');
}

$stmt = $conn->prepare("SELECT provider_id FROM service_providers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    respond(false, 'An account with this email already exists.');
}
$stmt->close();

$hashed_pass = password_hash($pass, PASSWORD_BCRYPT);
$hashed_pin  = password_hash($pin,  PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO service_providers (
        full_name, email, contact_number, address, password, pin_code
    ) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    respond(false, 'DB error: ' . $conn->error);
}
$stmt->bind_param("ssssss", $name, $email, $phone, $address, $hashed_pass, $hashed_pin);

if ($stmt->execute()) {
    $prov_id = $conn->insert_id;

    $_SESSION['provider_id']      = $prov_id;
    $_SESSION['provider_name']    = $name;
    $_SESSION['provider_email']   = $email;
    $_SESSION['provider_phone']   = $phone;
    $_SESSION['provider_address'] = $address;

    respond(true, 'Account created successfully!', [
        'redirect' => 'provider_home.php',
        'user' => ['id' => $prov_id, 'name' => $name, 'email' => $email, 'role' => 'provider']
    ]);
} else {
    respond(false, 'Registration failed: ' . $stmt->error);
}
?>
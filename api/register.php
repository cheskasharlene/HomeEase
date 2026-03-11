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

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    respond(false, 'An account with this email already exists.');
}
$stmt->close();

$hashed_pass = password_hash($pass, PASSWORD_BCRYPT);
$hashed_pin  = password_hash($pin,  PASSWORD_BCRYPT);

$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS pin_code VARCHAR(255) DEFAULT NULL");

$stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password, pin_code) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    respond(false, 'DB error: ' . $conn->error);
}
$stmt->bind_param("ssssss", $name, $email, $phone, $address, $hashed_pass, $hashed_pin);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    $_SESSION['user_id']      = $user_id;
    $_SESSION['user_name']    = $name;
    $_SESSION['user_email']   = $email;
    $_SESSION['user_phone']   = $phone;
    $_SESSION['user_address'] = $address;
    $_SESSION['user_role']    = 'user';

    respond(true, 'Account created successfully!', [
        'redirect' => 'home.php',
        'user' => ['id' => $user_id, 'name' => $name, 'email' => $email, 'role' => 'user']
    ]);
} else {
    respond(false, 'Registration failed: ' . $stmt->error);
}

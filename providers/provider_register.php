<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require __DIR__ . '/../api/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$first = trim($input['first'] ?? '');
$last = trim($input['last'] ?? '');
$name = trim("$first $last");
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? '');
$address = trim($input['address'] ?? '');
$specialty = trim($input['specialty'] ?? '');
$pass = trim($input['password'] ?? '');

if (!$first || !$last || !$email || !$pass) {
    respond(false, 'Please fill in all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
if (strlen($pass) < 8) {
    respond(false, 'Password must be at least 8 characters.');
}

$chk = $conn->prepare("SELECT provider_id FROM service_providers WHERE email = ?");
$chk->bind_param("s", $email);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    respond(false, 'An account with this email already exists.');
}
$chk->close();

$chk2 = $conn->prepare("SELECT id FROM users WHERE email = ?");
$chk2->bind_param("s", $email);
$chk2->execute();
$chk2->store_result();
if ($chk2->num_rows > 0) {
    respond(false, 'This email is already registered as a homeowner account.');
}
$chk2->close();

$hashed = password_hash($pass, PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    "INSERT INTO service_providers (full_name, email, contact_number, service_category, address, password, availability_status)
     VALUES (?, ?, ?, ?, ?, ?, 'offline')"
);
if (!$stmt) {
    respond(false, 'DB error: ' . $conn->error);
}
$stmt->bind_param("ssssss", $name, $email, $phone, $specialty, $address, $hashed);

if ($stmt->execute()) {
    $pid = $conn->insert_id;
    $_SESSION['provider_id'] = $pid;
    $_SESSION['provider_name'] = $name;
    $_SESSION['provider_email'] = $email;
    $_SESSION['provider_phone'] = $phone;
    $_SESSION['provider_address'] = $address;
    $_SESSION['provider_specialty'] = $specialty;

    respond(true, 'Account created successfully!', [
        'redirect' => 'providers/provider_home.php',
        'user' => ['id' => $pid, 'name' => $name, 'email' => $email, 'role' => 'provider']
    ]);
} else {
    respond(false, 'Registration failed: ' . $conn->error);
}

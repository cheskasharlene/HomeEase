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

$rawInput = file_get_contents('php://input');
$input    = json_decode($rawInput, true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

$acctType = strtolower(trim($input['account_type'] ?? $input['accountType'] ?? $input['role'] ?? $input['user_type'] ?? $input['type'] ?? 'user'));
if (in_array($acctType, ['provider', 'service_provider', 'service provider'], true)) {
    require_once __DIR__ . '/../providers/provider_register.php';
    exit;
}

$first   = trim($input['first']    ?? '');
$last    = trim($input['last']     ?? '');
if (!$first && !$last && !empty($input['name'])) {
    $parts = explode(' ', trim($input['name']), 2);
    $first = $parts[0] ?? '';
    $last  = $parts[1] ?? '';
}
$name    = trim("$first $last");
$email   = trim($input['email']    ?? '');
$phone   = trim($input['phone']    ?? $input['contact_number'] ?? $input['contact'] ?? '');
$address = trim($input['address']  ?? '');
$pass    = trim($input['password'] ?? '');

if (!$first || !$last || !$email || !$address || !$pass) {
    respond(false, 'Please fill in all required fields.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
if (strlen($pass) < 8) {
    respond(false, 'Password must be at least 8 characters long.');
}
if (!preg_match('/[A-Z]/', $pass)) {
    respond(false, 'Password must contain at least one uppercase letter.');
}
if (!preg_match('/[a-z]/', $pass)) {
    respond(false, 'Password must contain at least one lowercase letter.');
}
if (!preg_match('/[0-9]/', $pass)) {
    respond(false, 'Password must contain at least one number.');
}

$stmt = $conn->prepare("SELECT id, name, email, phone, address, password, policy_accepted, policy_accepted_at FROM users WHERE LOWER(email) = LOWER(?)");
$stmt->bind_param("s", $email);
$stmt->execute();
$uRes = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($uRes) {
    if (password_verify($pass, $uRes['password']) || $pass === $uRes['password']) {
        $_SESSION['user_id']            = $uRes['id'];
        $_SESSION['user_name']          = $uRes['name'];
        $_SESSION['user_email']         = $uRes['email'];
        $_SESSION['user_phone']         = $uRes['phone'] ?? '';
        $_SESSION['user_address']       = $uRes['address'] ?? '';
        $_SESSION['user_role']          = 'user';
        $_SESSION['policy_accepted']    = (!empty($uRes['policy_accepted']) || !empty($uRes['policy_accepted_at'])) ? 1 : 0;
        $_SESSION['policy_accepted_at'] = $uRes['policy_accepted_at'] ?? null;

        respond(true, 'Account created successfully!', [
            'redirect' => 'home.php',
            'user' => [
                'id' => $uRes['id'],
                'name' => $uRes['name'],
                'email' => $uRes['email'],
                'role' => 'user',
                'policy_accepted' => $_SESSION['policy_accepted']
            ]
        ]);
    } else {
        respond(false, 'An account with this email already exists.');
    }
}

$spChk = $conn->prepare("SELECT provider_id FROM service_providers WHERE LOWER(email) = LOWER(?)");
$spChk->bind_param("s", $email);
$spChk->execute();
$spChk->store_result();
if ($spChk->num_rows > 0) {
    $spChk->close();
    respond(false, 'This email is already registered as a service provider account.');
}
$spChk->close();

$hashed_pass = password_hash($pass, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password, policy_accepted, policy_accepted_at) VALUES (?, ?, ?, ?, ?, 0, NULL)");
if (!$stmt) {
    $stmt = $conn->prepare("INSERT INTO users (name, email, phone, address, password) VALUES (?, ?, ?, ?, ?)");
}
if (!$stmt) {
    respond(false, 'DB error: ' . $conn->error);
}
$stmt->bind_param("sssss", $name, $email, $phone, $address, $hashed_pass);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    $_SESSION['user_id']            = $user_id;
    $_SESSION['user_name']          = $name;
    $_SESSION['user_email']         = $email;
    $_SESSION['user_phone']         = $phone;
    $_SESSION['user_address']       = $address;
    $_SESSION['user_role']          = 'user';
    $_SESSION['policy_accepted']    = 0;
    $_SESSION['policy_accepted_at'] = null;

    respond(true, 'Account created successfully!', [
        'redirect' => 'home.php',
        'user' => [
            'id' => $user_id,
            'name' => $name,
            'email' => $email,
            'role' => 'user',
            'policy_accepted' => 0
        ]
    ]);
} else {
    respond(false, 'Registration failed: ' . $stmt->error);
}

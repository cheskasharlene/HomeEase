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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$input = json_decode(file_get_contents('php://input'), true);

if (isset($input['email']) && isset($input['password']) && !isset($input['pin'])) {
    $email = trim($input['email'] ?? '');
    $pass  = trim($input['password'] ?? '');

    if (!$email || !$pass) {
        respond(false, 'Please fill in all fields.');
    }

    $stmt = $conn->prepare("SELECT id, name, email, password, role, pin_code FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($pass, $user['password'])) {
        respond(false, 'Invalid email or password.');
    }

    if ($user['role'] === 'admin') {
        respond(false, 'Admin access is not allowed here. Please use the admin login page.');
    }

    $_SESSION['temp_user_id']    = $user['id'];
    $_SESSION['temp_user_name']  = $user['name'];
    $_SESSION['temp_user_email'] = $user['email'];
    $_SESSION['temp_user_role']  = $user['role'];
    $_SESSION['temp_login_time'] = time();

    respond(true, 'Password verified. PIN required.', [
        'step' => 'pin_required',
        'user' => [
            'name'  => $user['name'],
            'email' => $user['email']
        ]
    ]);
}

else if (isset($input['pin']) && !isset($input['email'])) {
    $pin = trim($input['pin'] ?? '');

    if (empty($_SESSION['temp_user_id'])) {
        respond(false, 'Session expired. Please login again.');
    }


    if (time() - $_SESSION['temp_login_time'] > 300) {
        session_unset();
        session_destroy();
        respond(false, 'Session expired. Please login again.');
    }

    if (!$pin) {
        respond(false, 'PIN is required.');
    }

    $user_id = $_SESSION['temp_user_id'];

    $stmt = $conn->prepare("SELECT pin_code FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['pin_code'])) {
        respond(false, 'PIN not set for this account.');
    }

    if (!password_verify($pin, $user['pin_code'])) {
        respond(false, 'Invalid PIN.');
    }

    $_SESSION['user_id']    = $_SESSION['temp_user_id'];
    $_SESSION['user_name']  = $_SESSION['temp_user_name'];
    $_SESSION['user_email'] = $_SESSION['temp_user_email'];
    $_SESSION['user_role']  = $_SESSION['temp_user_role'];

    unset($_SESSION['temp_user_id']);
    unset($_SESSION['temp_user_name']);
    unset($_SESSION['temp_user_email']);
    unset($_SESSION['temp_user_role']);
    unset($_SESSION['temp_login_time']);

    respond(true, 'Login successful!', [
        'user' => [
            'id'    => $_SESSION['user_id'],
            'name'  => $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role'  => $_SESSION['user_role']
        ],
        'redirect' => 'home.php'
    ]);
}

else {
    respond(false, 'Invalid request. Provide email/password or PIN.');
}
?>

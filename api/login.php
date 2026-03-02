<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$email = trim($_POST['email'] ?? '');
$pass = trim($_POST['password'] ?? '');

if (!$email || !$pass) {
    respond(false, 'Please fill in all fields.');
}

define('ADMIN_EMAIL', 'cheska@admin.com');
define('ADMIN_PASSWORD', 'admin1234');

if ($email === ADMIN_EMAIL && $pass === ADMIN_PASSWORD) {
    $_SESSION['user_id'] = 0;          // 0 = super admin (not in users table)
    $_SESSION['user_name'] = 'Admin';
    $_SESSION['user_role'] = 'admin';
    respond(true, 'Admin login successful!', [
        'user' => ['id' => 0, 'name' => 'Admin', 'email' => $email, 'role' => 'admin'],
        'redirect' => 'admindashboard.php'
    ]);
}

$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($pass, $user['password'])) {
    respond(false, 'Invalid email or password.');
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['name'];
$_SESSION['user_role'] = $user['role'];

$redirect = ($user['role'] === 'admin') ? 'admindashboard.php' : 'index.php';

respond(true, 'Login successful!', [
    'user' => [
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role']
    ],
    'redirect' => $redirect
]);
?>
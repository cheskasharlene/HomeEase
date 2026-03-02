<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$pass = trim($_POST['password'] ?? '');


if (!$name || !$email || !$pass) {
    respond(false, 'Name, email, and password are required.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
if (strlen($pass) < 6) {
    respond(false, 'Password must be at least 6 characters.');
}

$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    respond(false, 'An account with this email already exists.');
}
$stmt->close();

$hashed = password_hash($pass, PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $phone, $hashed);

if ($stmt->execute()) {
    $user_id = $conn->insert_id;

    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_name'] = $name;
    $_SESSION['user_role'] = 'user';

    respond(true, 'Account created successfully!', [
        'user' => ['id' => $user_id, 'name' => $name, 'email' => $email, 'role' => 'user']
    ]);
} else {
    respond(false, 'Registration failed. Please try again.');
}
?>
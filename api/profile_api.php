<?php
session_start();
require 'db.php';

if (empty($_SESSION['user_id'])) {
    respond(false, 'Not logged in.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request.');
}

$uid = $_SESSION['user_id'];
$section = $_POST['section'] ?? 'profile';

if ($section === 'profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (!$name || !$email) {
        respond(false, 'Name and email are required.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email address.');
    }

    $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $chk->bind_param("si", $email, $uid);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        respond(false, 'That email is already taken.');
    }
    $chk->close();

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=? WHERE id=?");
    $stmt->bind_param("sssi", $name, $email, $phone, $uid);
    if ($stmt->execute()) {
        // Update session
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_phone'] = $phone;
        respond(true, 'Profile updated successfully!');
    }
    respond(false, 'Could not update profile.');
}

if ($section === 'security') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) {
        respond(false, 'All password fields are required.');
    }
    if ($new !== $confirm) {
        respond(false, 'New passwords do not match.');
    }
    if (strlen($new) < 6) {
        respond(false, 'New password must be at least 6 characters.');
    }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current, $row['password'])) {
        respond(false, 'Current password is incorrect.');
    }

    $hashed = password_hash($new, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $hashed, $uid);
    if ($stmt->execute()) {
        respond(true, 'Password changed successfully!');
    }
    respond(false, 'Could not update password.');
}

if ($section === 'address') {
    $address = trim($_POST['address'] ?? '');
    $_SESSION['user_address'] = $address;
    respond(true, 'Address saved!');
}

respond(false, 'Unknown section.');
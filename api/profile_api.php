<?php
session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

require 'db.php';

if (empty($_SESSION['user_id'])) { respond(false, 'Not logged in.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { respond(false, 'Invalid request.'); }

$uid     = $_SESSION['user_id'];
$section = $_POST['section'] ?? 'profile';


if ($section === 'profile') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $phone   = trim($_POST['phone']   ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$name || !$email) { respond(false, 'Name and email are required.'); }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { respond(false, 'Invalid email address.'); }

    $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $chk->bind_param("si", $email, $uid);
    $chk->execute(); $chk->store_result();
    if ($chk->num_rows > 0) { respond(false, 'That email is already taken.'); }
    $chk->close();

    $stmt = $conn->prepare("UPDATE users SET name=?, email=?, phone=?, address=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone, $address, $uid);
    if ($stmt->execute()) {
        $_SESSION['user_name']    = $name;
        $_SESSION['user_email']   = $email;
        $_SESSION['user_phone']   = $phone;
        $_SESSION['user_address'] = $address;
        respond(true, 'Profile updated successfully!');
    }
    respond(false, 'Could not update profile.');
}

if ($section === 'security') {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$current || !$new || !$confirm) { respond(false, 'All password fields are required.'); }
    if ($new !== $confirm) { respond(false, 'New passwords do not match.'); }
    if (strlen($new) < 6)  { respond(false, 'New password must be at least 6 characters.'); }

    $stmt = $conn->prepare("SELECT password FROM users WHERE id=?");
    $stmt->bind_param("i", $uid); $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc(); $stmt->close();

    if (!$row || !password_verify($current, $row['password'])) {
        respond(false, 'Current password is incorrect.');
    }

    $hashed = password_hash($new, PASSWORD_BCRYPT);
    $stmt   = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->bind_param("si", $hashed, $uid);
    if ($stmt->execute()) { respond(true, 'Password changed successfully!'); }
    respond(false, 'Could not update password.');
}


if ($section === 'address') {
    $address = trim($_POST['address'] ?? '');
    $stmt = $conn->prepare("UPDATE users SET address=? WHERE id=?");
    $stmt->bind_param("si", $address, $uid);
    if ($stmt->execute()) {
        $_SESSION['user_address'] = $address;
        respond(true, 'Address saved!');
    }
    respond(false, 'Could not save address.');
}


if ($section === 'pin') {
    $pin = trim($_POST['pin'] ?? '');
    if (!$pin || !preg_match('/^\d{4}$/', $pin)) {
        respond(false, 'A valid 4-digit PIN is required.');
    }

    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS pin_code VARCHAR(255) DEFAULT NULL");

    $hashed = password_hash($pin, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE users SET pin_code=? WHERE id=?");
    $stmt->bind_param("si", $hashed, $uid);
    if ($stmt->execute()) {
        respond(true, 'PIN updated successfully!');
    }
    respond(false, 'Could not update PIN.');
}

if ($section === 'photo') {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");
    
    if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'No image file provided or upload failed.');
    }

    $file = $_FILES['photo'];
    if (!preg_match('/^image\/(jpeg|png|gif|webp)$/', $file['type'])) {
        respond(false, 'Only image files (JPG, PNG, GIF, WebP) are allowed.');
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        respond(false, 'Image size must not exceed 5MB.');
    }

    $uploadDir = __DIR__ . '/../assets/uploads/profile_photos/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'user_' . $uid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $filePath = $uploadDir . $fileName;
    $dbPath = 'assets/uploads/profile_photos/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        respond(false, 'Failed to save image. Please try again.');
    }

    $stmt = $conn->prepare("UPDATE users SET profile_photo=? WHERE id=?");
    $stmt->bind_param("si", $dbPath, $uid);
    if ($stmt->execute()) {
        respond(true, 'Profile photo updated successfully!', ['photo_url' => $dbPath . '?t=' . time()]);
    }
    
    @unlink($filePath);
    respond(false, 'Could not update profile photo.');
}

respond(false, 'Unknown section.');

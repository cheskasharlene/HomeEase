<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['provider_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/db.php';

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? ''));

function providerRespond(bool $success, string $message, array $extra = []): void {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

function ensureWorkingHoursColumn(mysqli $conn): bool {
    $check = $conn->query("SHOW COLUMNS FROM service_providers LIKE 'working_hours'");
    if ($check && $check->num_rows > 0) {
        return true;
    }
    return (bool) $conn->query("ALTER TABLE service_providers ADD COLUMN working_hours VARCHAR(120) NULL");
}

if ($action === 'update_profile') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));

    if ($name === '') {
        providerRespond(false, 'Name is required.');
    }

    $stmt = $conn->prepare('UPDATE service_providers SET full_name = ?, contact_number = ?, address = ? WHERE provider_id = ?');
    if (!$stmt) {
        providerRespond(false, 'DB error: ' . $conn->error);
    }
    $stmt->bind_param('sssi', $name, $phone, $address, $providerId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        providerRespond(false, 'Could not update profile.');
    }

    $_SESSION['provider_name'] = $name;
    $_SESSION['provider_phone'] = $phone;
    $_SESSION['provider_address'] = $address;

    providerRespond(true, 'Profile updated.', [
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
    ]);
}

if ($action === 'update_phone') {
    $phone = trim((string) ($_POST['phone'] ?? ''));

    $stmt = $conn->prepare('UPDATE service_providers SET contact_number = ? WHERE provider_id = ?');
    if (!$stmt) {
        providerRespond(false, 'DB error: ' . $conn->error);
    }
    $stmt->bind_param('si', $phone, $providerId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        providerRespond(false, 'Could not update phone.');
    }

    $_SESSION['provider_phone'] = $phone;
    providerRespond(true, 'Phone updated.', ['phone' => $phone]);
}

if ($action === 'update_service_area') {
    $address = trim((string) ($_POST['address'] ?? ''));

    $stmt = $conn->prepare('UPDATE service_providers SET address = ? WHERE provider_id = ?');
    if (!$stmt) {
        providerRespond(false, 'DB error: ' . $conn->error);
    }
    $stmt->bind_param('si', $address, $providerId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        providerRespond(false, 'Could not update service area.');
    }

    $_SESSION['provider_address'] = $address;
    providerRespond(true, 'Service area updated.', ['address' => $address]);
}

if ($action === 'update_working_hours') {
    $workingHours = trim((string) ($_POST['working_hours'] ?? ''));
    if (!ensureWorkingHoursColumn($conn)) {
        providerRespond(false, 'Could not prepare working hours storage.');
    }

    $stmt = $conn->prepare('UPDATE service_providers SET working_hours = ? WHERE provider_id = ?');
    if (!$stmt) {
        providerRespond(false, 'DB error: ' . $conn->error);
    }
    $stmt->bind_param('si', $workingHours, $providerId);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        providerRespond(false, 'Could not update working hours.');
    }

    providerRespond(true, 'Working hours updated.', ['working_hours' => $workingHours]);
}

if ($action === 'change_password') {
    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    if ($current === '' || $new === '' || $confirm === '') {
        providerRespond(false, 'All password fields are required.');
    }
    if ($new !== $confirm) {
        providerRespond(false, 'New passwords do not match.');
    }
    if (strlen($new) < 6) {
        providerRespond(false, 'New password must be at least 6 characters.');
    }

    $stmt = $conn->prepare('SELECT password FROM service_providers WHERE provider_id = ? LIMIT 1');
    if (!$stmt) {
        providerRespond(false, 'DB error: ' . $conn->error);
    }
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stored = (string) ($row['password'] ?? '');
    $okCurrent = $stored !== '' && (password_verify($current, $stored) || $current === $stored);
    if (!$okCurrent) {
        providerRespond(false, 'Current password is incorrect.');
    }

    $hashed = password_hash($new, PASSWORD_BCRYPT);
    $up = $conn->prepare('UPDATE service_providers SET password = ? WHERE provider_id = ?');
    if (!$up) {
        providerRespond(false, 'DB error: ' . $conn->error);
    }
    $up->bind_param('si', $hashed, $providerId);
    $ok = $up->execute();
    $up->close();

    if (!$ok) {
        providerRespond(false, 'Could not update password.');
    }

    providerRespond(true, 'Password updated successfully.');
}

providerRespond(false, 'Unknown action.');

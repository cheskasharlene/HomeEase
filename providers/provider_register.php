<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

require_once __DIR__ . '/../api/db.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}

$first = trim($input['first'] ?? '');
$last = trim($input['last'] ?? '');
if (!$first && !$last && !empty($input['name'])) {
    $parts = explode(' ', trim($input['name']), 2);
    $first = $parts[0] ?? '';
    $last = $parts[1] ?? '';
}
$name = trim("$first $last");
$email = trim($input['email'] ?? '');
$phone = trim($input['phone'] ?? $input['contact_number'] ?? $input['contact'] ?? '');
$address = trim($input['address'] ?? '');
$specialty = trim($input['specialty'] ?? $input['service'] ?? $input['service_category'] ?? $input['service_name'] ?? '');
$pass = trim($input['password'] ?? '');

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
$uRes = $chk2->get_result()->fetch_assoc();
$chk2->close();
if ($uRes) {
    $existingUserId = (int)$uRes['id'];
    $bChk = $conn->prepare("SELECT COUNT(*) AS cnt FROM bookings WHERE user_id = ?");
    $bChk->bind_param("i", $existingUserId);
    $bChk->execute();
    $bRow = $bChk->get_result()->fetch_assoc();
    $bChk->close();
    $bookingCount = (int)($bRow['cnt'] ?? 0);
    if ($bookingCount === 0) {
        $delUser = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delUser->bind_param("i", $existingUserId);
        $delUser->execute();
        $delUser->close();
    } else {
        respond(false, 'This email is already registered as a homeowner account with active bookings.');
    }
}

$hashed = password_hash($pass, PASSWORD_BCRYPT);

$row = null;
if (is_numeric($specialty) && (int)$specialty > 0) {
    $stmt = $conn->prepare("SELECT id, name FROM services WHERE id = ? LIMIT 1");
    if ($stmt) {
        $sid = (int)$specialty;
        $stmt->bind_param("i", $sid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$row && !empty($specialty)) {
    $specMap = [
        'plumbing' => 'Plumber',
        'cleaner' => 'House Cleaner',
        'cleaning' => 'House Cleaner',
        'house cleaning' => 'House Cleaner',
        'house cleaner' => 'House Cleaner',
        'helper' => 'Helper',
        'laundry' => 'Laundry Worker',
        'laundry worker' => 'Laundry Worker',
        'carpentry' => 'Carpenter',
        'carpenter' => 'Carpenter',
        'appliance repair' => 'Appliance Technician',
        'appliance technician' => 'Appliance Technician'
    ];
    $lookupName = $specMap[strtolower($specialty)] ?? $specialty;

    $stmt = $conn->prepare("SELECT id, name FROM services WHERE LOWER(name) = LOWER(?) LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $lookupName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }
}

if (!$row) {
    $resFirst = $conn->query("SELECT id, name FROM services ORDER BY id ASC LIMIT 1");
    if ($resFirst && ($rowFirst = $resFirst->fetch_assoc())) {
        $row = $rowFirst;
    }
}

if (!$row) {
    respond(false, 'No valid service category found.');
}
$service_id = (int) $row['id'];
$standard_specialty = $row['name'];

if ($service_id > 0) {
    $stmt = $conn->prepare(
        "INSERT INTO service_providers (full_name, email, contact_number, service_id, address, password, availability_status)
         VALUES (?, ?, ?, ?, ?, ?, 'offline')"
    );
    if (!$stmt) {
        respond(false, 'DB prepare error: ' . $conn->error);
    }
    $stmt->bind_param("sssiss", $name, $email, $phone, $service_id, $address, $hashed);
} else {
    $stmt = $conn->prepare(
        "INSERT INTO service_providers (full_name, email, contact_number, service_id, address, password, availability_status)
         VALUES (?, ?, ?, NULL, ?, ?, 'offline')"
    );
    if (!$stmt) {
        respond(false, 'DB prepare error: ' . $conn->error);
    }
    $stmt->bind_param("sssss", $name, $email, $phone, $address, $hashed);
}

if ($stmt->execute()) {
    $pid = $conn->insert_id;
    $_SESSION['provider_id'] = $pid;
    $_SESSION['provider_name'] = $name;
    $_SESSION['provider_email'] = $email;
    $_SESSION['provider_phone'] = $phone;
    $_SESSION['provider_address'] = $address;
    $_SESSION['provider_specialty'] = $standard_specialty;

    respond(true, 'Account created successfully!', [
        'redirect' => 'providers/provider_home.php',
        'user' => ['id' => $pid, 'name' => $name, 'email' => $email, 'role' => 'provider']
    ]);
} else {
    respond(false, 'Registration failed: ' . $stmt->error);
}

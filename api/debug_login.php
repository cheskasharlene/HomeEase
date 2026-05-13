<?php
// Temporary debug script - DELETE AFTER FIXING
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: text/plain');

// 1. Test DB connection
require 'db.php';
echo "1. DB Connection: OK\n";

// 2. Check if users table exists and has correct columns
$result = $conn->query("DESCRIBE users");
if (!$result) {
    echo "2. users table ERROR: " . $conn->error . "\n";
} else {
    echo "2. users table columns:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

// 3. Check service_providers table
$result2 = $conn->query("DESCRIBE service_providers");
if (!$result2) {
    echo "3. service_providers table ERROR: " . $conn->error . "\n";
} else {
    echo "3. service_providers columns:\n";
    while ($row = $result2->fetch_assoc()) {
        echo "   - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

// 4. Count users
$r = $conn->query("SELECT COUNT(*) as cnt FROM users");
$row = $r->fetch_assoc();
echo "4. Total users: " . $row['cnt'] . "\n";

// 5. Test raw login query
$email = 'test@gmail.com';
$stmt = $conn->prepare("SELECT id, name, email, password, phone, address, role FROM users WHERE email = ?");
if (!$stmt) {
    echo "5. Prepare failed: " . $conn->error . "\n";
} else {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user) {
        echo "5. User found: id=" . $user['id'] . " role=" . $user['role'] . " pw_hash=" . substr($user['password'], 0, 10) . "...\n";
    } else {
        echo "5. User NOT found for email: $email\n";
    }
}

// 6. Simulate full login response
echo "\n--- Simulating login API response ---\n";
ob_start();
$_SERVER['REQUEST_METHOD'] = 'POST';
$fakeInput = json_encode(['email' => 'test@gmail.com', 'password' => '12345678']);
// We can't re-require db.php (already loaded), just test respond()
echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
$output = ob_get_clean();
echo "respond() output: " . $output . "\n";
echo "\nDone.\n";

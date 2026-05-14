<?php
// Test login with real credentials - DELETE AFTER DEBUGGING
$url = 'http://localhost/homeease/api/login.php';

// Test 1: Check what the actual password hash looks like for test@gmail.com
require 'db.php';

header('Content-Type: text/plain');

$stmt = $conn->prepare("SELECT id, email, password, role FROM users WHERE email = ?");
$email = 'test@gmail.com';
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    echo "User found: id=" . $user['id'] . " role=" . $user['role'] . "\n";
    echo "Password hash: " . $user['password'] . "\n";
    echo "Is bcrypt: " . (strpos($user['password'], '$2y$') === 0 ? 'YES' : 'NO') . "\n\n";
    
    // Check common passwords
    $testPasswords = ['12345678', 'password', 'test1234', 'Test@123', '123456789', 'password123'];
    foreach ($testPasswords as $pw) {
        $ok = password_verify($pw, $user['password']) || $pw === $user['password'];
        echo "Password '$pw': " . ($ok ? 'MATCH' : 'no match') . "\n";
    }
} else {
    echo "User test@gmail.com NOT found\n";
    
    // List all users
    $r = $conn->query("SELECT id, email, role FROM users LIMIT 10");
    echo "\nAll users:\n";
    while ($row = $r->fetch_assoc()) {
        echo "  id=" . $row['id'] . " email=" . $row['email'] . " role=" . $row['role'] . "\n";
    }
}

// Test 2: Also check php.ini for display_errors that could pollute output
echo "\n=== PHP Config ===\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "output_buffering: " . ini_get('output_buffering') . "\n";

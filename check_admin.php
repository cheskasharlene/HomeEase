<?php
require_once __DIR__ . '/api/db.php';

// Check admin accounts
$result = $conn->query("SELECT * FROM users WHERE role='admin' LIMIT 1");
if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "Admin found:\n";
    echo "Email: {$admin['email']}\n";
    echo "Name: {$admin['first_name']} {$admin['last_name']}\n";
    echo "Role: {$admin['role']}\n";
}

// Also check admins table
$result2 = $conn->query("SELECT * FROM admins LIMIT 1");
if ($result2 && $result2->num_rows > 0) {
    $admin2 = $result2->fetch_assoc();
    echo "\nAdmin in admins table:\n";
    print_r($admin2);
}
?>

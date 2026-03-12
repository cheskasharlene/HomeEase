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

// make sure providers table exists (same schema as register script)
$conn->query(
    "CREATE TABLE IF NOT EXISTS service_providers (
        provider_id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        contact_number VARCHAR(30) DEFAULT NULL,
        service_category VARCHAR(100) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        password VARCHAR(255) NOT NULL,
        profile_image VARCHAR(255) DEFAULT NULL,
        availability_status ENUM('online','offline') DEFAULT 'offline',
        pin_code VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$input = json_decode(file_get_contents('php://input'), true);

// email/password login for service providers
if (isset($input['email']) && isset($input['password'])) {
    $email = trim($input['email'] ?? '');
    $pass  = trim($input['password'] ?? '');

    if (!$email || !$pass) {
        respond(false, 'Please fill in all fields.');
    }

    // fetch from providers table instead of users
    $stmt = $conn->prepare("SELECT provider_id, full_name, email, password, pin_code FROM service_providers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($pass, $user['password'])) {
        respond(false, 'Invalid email or password. Please try again.');
    }

    // successful auth: start provider session (separate from homeowner session)
    $_SESSION['provider_id']    = $user['provider_id'];
    $_SESSION['provider_name']  = $user['full_name'];
    $_SESSION['provider_email'] = $user['email'];

    respond(true, 'Login successful!', [
        'user' => [
            'id'    => $_SESSION['provider_id'],
            'name'  => $_SESSION['provider_name'],
            'email' => $_SESSION['provider_email'],
            'role'  => 'provider'
        ],
        'redirect' => 'provider_home.php'
    ]);
}

else {
    respond(false, 'Invalid email or password. Please try again.');
}
?>
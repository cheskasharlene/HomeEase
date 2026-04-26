<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Invalid request.']); exit;
}

require 'db.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$pass  = trim($input['password'] ?? '');

if (!$email || !$pass) {
    respond(false, 'Please fill in all fields.');
}


$stmt = $conn->prepare("SELECT id, name, email, password, phone, address, role FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    $pwOk = password_verify($pass, $user['password']) || $pass === $user['password'];
    if ($pwOk) {
       
        if ($pass === $user['password'] && strpos($user['password'], '$2y$') !== 0) {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param("si", $hashed, $user['id']);
            $upd->execute(); $upd->close();
        }
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_phone'] = $user['phone'] ?? '';
        $_SESSION['user_address'] = $user['address'] ?? '';
        $_SESSION['user_role']  = $user['role'];

        if ($user['role'] === 'admin') {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            respond(true, 'Login successful!', [
                'redirect' => 'admin/admindashboard.php',
                'role'     => 'admin',
                'user'     => ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>'admin']
            ]);
        }

        respond(true, 'Login successful!', [
            'redirect' => 'home.php',
            'role'     => 'user',
            'user'     => ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'role'=>'user']
        ]);
    }
}

$stmt2 = $conn->prepare("SELECT provider_id, full_name, email, password, service_category, contact_number, address FROM service_providers WHERE email = ?");
$stmt2->bind_param("s", $email);
$stmt2->execute();
$provider = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

if ($provider) {
    $pwOk = password_verify($pass, $provider['password']) || $pass === $provider['password'];
    if ($pwOk) {
     
        if ($pass === $provider['password'] && strpos($provider['password'], '$2y$') !== 0) {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE service_providers SET password=? WHERE provider_id=?");
            $upd->bind_param("si", $hashed, $provider['provider_id']);
            $upd->execute(); $upd->close();
        }
        $_SESSION['provider_id']       = $provider['provider_id'];
        $_SESSION['provider_name']     = $provider['full_name'];
        $_SESSION['provider_email']    = $provider['email'];
        $_SESSION['provider_phone']    = $provider['contact_number'];
        $_SESSION['provider_address']  = $provider['address'];
        $_SESSION['provider_specialty']= $provider['service_category'];

        respond(true, 'Login successful!', [
            'redirect' => 'providers/provider_home.php',
            'role'     => 'provider',
            'user'     => ['id'=>$provider['provider_id'],'name'=>$provider['full_name'],'email'=>$provider['email'],'role'=>'provider']
        ]);
    }
}

respond(false, 'Invalid email or password.');

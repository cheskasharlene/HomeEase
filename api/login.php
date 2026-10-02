<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_set_cookie_params(['path' => '/']);
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'Invalid request.']); exit;
}

require 'db.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);
if (!is_array($input) || empty($input)) {
    $input = $_POST;
}
$email = trim($input['email'] ?? '');
$pass  = trim($input['password'] ?? '');

if (!$email || !$pass) {
    respond(false, 'Please fill in all fields.');
}


$colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'disabled'");
$hasDisabled = $colRes && $colRes->num_rows > 0;
$disabledCol = $hasDisabled ? ", disabled" : "";

$stmt = $conn->prepare("SELECT id, name, email, password, phone, address, role, policy_accepted, policy_accepted_at $disabledCol FROM users WHERE LOWER(email) = LOWER(?)");
$stmt->bind_param("s", $email);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($user) {
    $pwOk = password_verify($pass, $user['password']) || $pass === $user['password'];
    if ($pwOk) {
        if (!empty($user['disabled'])) {
            respond(false, 'Your account has been suspended by the administrator. Please contact support.');
        }
        if ($pass === $user['password'] && strpos($user['password'], '$2y$') !== 0) {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $upd->bind_param("si", $hashed, $user['id']);
            $upd->execute(); $upd->close();
        }
        // Clear provider session if present
        unset($_SESSION['provider_id'], $_SESSION['provider_name'], $_SESSION['provider_email'], $_SESSION['provider_phone'], $_SESSION['provider_address'], $_SESSION['provider_specialty']);

        $_SESSION['user_id']            = $user['id'];
        $_SESSION['user_name']          = $user['name'];
        $_SESSION['user_email']         = $user['email'];
        $_SESSION['user_phone']         = $user['phone'] ?? '';
        $_SESSION['user_address']       = $user['address'] ?? '';
        $_SESSION['user_role']          = $user['role'];
        $_SESSION['policy_accepted']    = (!empty($user['policy_accepted']) || !empty($user['policy_accepted_at'])) ? 1 : 0;
        $_SESSION['policy_accepted_at'] = $user['policy_accepted_at'] ?? null;

        if ($user['role'] !== 'admin') {
            updateUserActivity($conn, $user['id']);
        }

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
            'user'     => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => 'user',
                'policy_accepted' => (!empty($user['policy_accepted']) || !empty($user['policy_accepted_at'])) ? 1 : 0
            ]
        ]);
    }
}

$stmt2 = $conn->prepare("SELECT sp.provider_id, sp.full_name, sp.email, sp.password, s.name AS service_category, sp.contact_number, sp.address, sp.status FROM service_providers sp LEFT JOIN services s ON s.id = sp.service_id WHERE LOWER(sp.email) = LOWER(?)");
$stmt2->bind_param("s", $email);
$stmt2->execute();
$provider = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

if ($provider) {
    $pwOk = password_verify($pass, $provider['password']) || $pass === $provider['password'];
    if ($pwOk) {
        $pid = (int) ($provider['provider_id'] ?? 0);
        if ($pid <= 0) {
            $mRes = $conn->query("SELECT MAX(provider_id) AS max_id FROM service_providers WHERE provider_id > 0");
            $mRow = $mRes ? $mRes->fetch_assoc() : null;
            $newId = (int)($mRow['max_id'] ?? 0) + 1;
            if ($newId < 1) $newId = 1;
            $safeEmail = $conn->real_escape_string($provider['email']);
            @$conn->query("UPDATE service_providers SET provider_id = $newId WHERE LOWER(email) = LOWER('$safeEmail') LIMIT 1");
            $provider['provider_id'] = $newId;
            $pid = $newId;
        }

        $pStatus = strtolower(trim((string)($provider['status'] ?? 'active')));
        if ($pStatus === 'suspended') {
            respond(false, 'Your worker account has been suspended by the administrator. Please contact support.');
        }
        if ($pStatus === 'inactive' || $pStatus === '') {
            @$conn->query("UPDATE service_providers SET status='active' WHERE provider_id=" . $pid);
        }

        if ($pass === $provider['password'] && strpos($provider['password'], '$2y$') !== 0) {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $upd = $conn->prepare("UPDATE service_providers SET password=? WHERE provider_id=?");
            $upd->bind_param("si", $hashed, $pid);
            $upd->execute(); $upd->close();
        }
        // Clear customer/user session if present
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_phone'], $_SESSION['user_address'], $_SESSION['user_role']);

        $_SESSION['provider_id']       = $pid;
        $_SESSION['provider_name']     = $provider['full_name'];
        $_SESSION['provider_email']    = $provider['email'];
        $_SESSION['provider_phone']    = $provider['contact_number'];
        $_SESSION['provider_address']  = $provider['address'];
        $_SESSION['provider_specialty']= $provider['service_category'];

        updateProviderActivity($conn, $provider['provider_id']);

        respond(true, 'Login successful!', [
            'redirect' => 'providers/provider_home.php',
            'role'     => 'provider',
            'user'     => ['id'=>$provider['provider_id'],'name'=>$provider['full_name'],'email'=>$provider['email'],'role'=>'provider']
        ]);
    }
}

respond(false, 'Invalid email or password.');

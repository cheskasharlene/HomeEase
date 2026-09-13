<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/api/db.php';

if (!empty($_SESSION['provider_id'])) {
  if (isset($conn) && $conn instanceof mysqli) {
    $pid = (int) $_SESSION['provider_id'];
    $stmt = $conn->prepare("UPDATE service_providers SET availability_status = 'offline' WHERE provider_id = ?");
    if ($stmt) {
      $stmt->bind_param("i", $pid);
      $stmt->execute();
      $stmt->close();
    }
  }
}

if (!empty($_SESSION['user_id'])) {
  if (isset($conn) && $conn instanceof mysqli) {
    $uid = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET is_online = 0 WHERE id = ?");
    if ($stmt) {
      $stmt->bind_param("i", $uid);
      $stmt->execute();
      $stmt->close();
    }
  }
}

session_unset();
session_destroy();

if (ini_get('session.use_cookies')) {
  $p = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $p['path'],
    $p['domain'],
    $p['secure'],
    $p['httponly']
  );
}

header('Location: index.php');
exit;
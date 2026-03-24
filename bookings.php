<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$target = 'booking_history.php';
$wantsNew = isset($_GET['newbooking']) || isset($_GET['svc']);
if ($wantsNew) {
  $target = 'booking_form.php';
}

$query = $_SERVER['QUERY_STRING'] ?? '';
if ($query !== '') {
  $target .= '?' . $query;
}

header('Location: ' . $target);
exit;

<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// redirect to provider login since placeholder deprecated
if (empty($_SESSION['user_id'])) {
  header('Location: provider_index.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Service Provider</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--teal:#147d8c;--dark-teal:#0f6b74;--bg-soft:#DEF6F3;--txt-dark:#1a1a2e;--txt-muted:#6b7280}
    body{margin:0;padding:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:var(--bg-soft);font-family:'Poppins',sans-serif;}
    .card{background:#fff;border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,.05);padding:40px;max-width:360px;text-align:center;}
    .card svg{width:48px;height:48px;margin-bottom:16px;fill:var(--teal);}
    .card h1{font-size:24px;color:var(--dark-teal);margin-bottom:8px;}
    .card p{font-size:16px;color:var(--txt-muted);}
  </style>
</head>
<body>
  <div class="card">
    <!-- simple tools icon -->
    <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg"><path d="M44.5 11.5l8 8-15 15-8-8 15-15zM20.5 35.5l1.5 1.5-5.5 5.5-1.5-1.5 5.5-5.5z"/><path fill="none" stroke="var(--teal)" stroke-width="4" d="M2 62l20-20 22 22-20 20z"/></svg>
    <h1>Service Provider Dashboard</h1>
    <p>Coming Soon</p>
  </div>
</body>
</html>
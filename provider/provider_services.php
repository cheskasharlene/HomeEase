<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Provider Services</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link href="../assets/css/home.css" rel="stylesheet">
  <style>
    body { background: #e0f7f4; }
    .provider-screen { min-height: 100%; background: #f0fdfa; display: flex; flex-direction: column; }
    .provider-scroll { flex: 1; overflow-y: auto; padding: 28px 16px 120px; }
    .page-kicker { font: 800 11px 'Nunito', sans-serif; color: #0D9488; text-transform: uppercase; letter-spacing: .9px; }
    .page-title { margin-top: 6px; font: 800 24px 'Poppins', sans-serif; color: #1f2937; }
    .page-subtitle { margin-top: 4px; font: 600 14px 'Nunito', sans-serif; color: #6b7280; }
    .service-list { display: grid; gap: 14px; margin-top: 18px; }
    .service-card { background: #fff; border: 1px solid rgba(13, 148, 136, .08); border-radius: 20px; box-shadow: 0 16px 32px rgba(13, 148, 136, .10); padding: 16px; }
    .service-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    .service-head strong { font: 800 16px 'Poppins', sans-serif; color: #1f2937; }
    .service-head span { font: 800 15px 'Poppins', sans-serif; color: #0D9488; }
    .service-card p { margin-top: 8px; font: 600 14px 'Nunito', sans-serif; color: #6b7280; line-height: 1.5; }
    .service-actions { margin-top: 14px; display: flex; gap: 10px; }
    .service-actions button { flex: 1; min-height: 42px; border: none; border-radius: 12px; background: #ccfbf1; color: #0f766e; font: 800 13px 'Nunito', sans-serif; cursor: pointer; }
    .bnav { position: fixed; bottom: 0; left: 50%; transform: translateX(-50%); width: 100%; max-width: 420px; background: #fff; border-top: 1px solid rgba(13, 148, 136, .10); display: flex; align-items: center; justify-content: space-around; padding: 8px 0 16px; z-index: 100; box-shadow: 0 -10px 24px rgba(13, 148, 136, .10); }
    .ni { display: flex; flex-direction: column; align-items: center; gap: 4px; min-width: 52px; color: #6b7280; cursor: pointer; position: relative; font-family: 'Nunito', sans-serif; font-weight: 800; }
    .ni i { font-size: 22px; }
    .ni.on, .ni.on i, .ni.on .nl { color: #0D9488; }
    .nl { font-size: 10px; }
    .nb-c { width: 56px; height: 56px; border-radius: 50%; background: #0D9488; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 22px rgba(13, 148, 136, .26); margin-top: -24px; color: #fff; }
  </style>
</head>
<body>
  <div class="shell">
    <div class="provider-screen">
      <div class="provider-scroll">
        <div class="page-kicker">Provider Space</div>
        <div class="page-title">Services</div>
        <div class="page-subtitle">Manage offered services while keeping the app navigation pinned.</div>

        <div class="service-list">
          <div class="service-card">
            <div class="service-head"><strong>Plumbing</strong><span>$40</span></div>
            <p>Leak repair, fixture replacement, and preventive maintenance.</p>
            <div class="service-actions"><button type="button">Edit</button><button type="button">Pause</button></div>
          </div>
          <div class="service-card">
            <div class="service-head"><strong>Electrical Repair</strong><span>$60</span></div>
            <p>Outlet diagnostics, minor rewiring, and lighting installation.</p>
            <div class="service-actions"><button type="button">Edit</button><button type="button">Pause</button></div>
          </div>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-calendar-check"></i><span class="nl">Requests</span></div>
        <div class="ni on" onclick="goPage('provider_services.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>
    </div>
  </div>
  <script src="../assets/js/app.js"></script>
</body>
</html>
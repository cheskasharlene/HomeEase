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
  <title>HomeEase – Provider Schedule</title>
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
    .schedule-list { display: grid; gap: 14px; margin-top: 18px; }
    .schedule-card { background: #fff; border: 1px solid rgba(13, 148, 136, .08); border-radius: 20px; box-shadow: 0 16px 32px rgba(13, 148, 136, .10); padding: 16px; }
    .schedule-time { font: 800 13px 'Nunito', sans-serif; color: #0D9488; text-transform: uppercase; letter-spacing: .7px; }
    .schedule-title { margin-top: 8px; font: 800 16px 'Poppins', sans-serif; color: #1f2937; }
    .schedule-card p { margin-top: 6px; font: 600 14px 'Nunito', sans-serif; color: #6b7280; }
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
        <div class="page-title">Schedule</div>
        <div class="page-subtitle">Upcoming jobs stay accessible without losing navigation.</div>

        <div class="schedule-list">
          <div class="schedule-card">
            <div class="schedule-time">Apr 1, 10:00 AM</div>
            <div class="schedule-title">Plumbing with Jane Smith</div>
            <p>Bring leak-seal kit and replacement faucet parts.</p>
          </div>
          <div class="schedule-card">
            <div class="schedule-time">Apr 2, 2:00 PM</div>
            <div class="schedule-title">Home Cleaning with Bob Lee</div>
            <p>Two-bedroom deep clean, estimated duration: 3 hours.</p>
          </div>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-calendar-check"></i><span class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_services.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>
    </div>
  </div>
  <script src="../assets/js/app.js"></script>
</body>
</html>
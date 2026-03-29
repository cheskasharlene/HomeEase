<?php /* provider_services.php */
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
  <title>HomeEase – My Services</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_services.css">
</head>

<body>
  <div class="shell" id="app">
    <div id="ml">
      <div class="ml-wrap">
        <div class="ml-box"><svg viewBox="0 0 54 54" fill="none">
            <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white" />
            <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" />
          </svg></div>
        <div class="ml-name">Home<span>Ease</span></div>
        <div class="ml-dots">
          <div class="ml-dot"></div>
          <div class="ml-dot"></div>
          <div class="ml-dot"></div>
        </div>
      </div>
    </div>

    <div class="screen">
      <div class="p-scroll">
        <div class="p-hdr">
          <div style="position:relative;z-index:1;">
            <div class="p-hdr-ttl">My Services</div>
            <div class="p-hdr-sub">Manage what you offer</div>
          </div>
        </div>

        <button class="btn-add"><i class="bi bi-plus-lg"></i> Add New Service</button>

        <div class="svc-list">
          <div class="svc-card">
            <div class="svc-top">
              <div class="svc-ic">🔧</div>
              <div>
                <div class="svc-nm">Plumbing</div>
                <div class="svc-desc">Leak repair, fixtures & maintenance</div>
              </div>
              <div class="svc-price">₱2,000</div>
            </div>
            <div class="svc-footer">
              <button class="btn-edit"><i class="bi bi-pencil-fill" style="margin-right:5px;"></i>Edit</button>
              <button class="btn-pause"><i class="bi bi-pause-fill" style="margin-right:5px;"></i>Pause</button>
            </div>
          </div>
          <div class="svc-card">
            <div class="svc-top">
              <div class="svc-ic">⚡</div>
              <div>
                <div class="svc-nm">Electrical Repair</div>
                <div class="svc-desc">Wiring, outlets & diagnostics</div>
              </div>
              <div class="svc-price">₱3,000</div>
            </div>
            <div class="svc-footer">
              <button class="btn-edit"><i class="bi bi-pencil-fill" style="margin-right:5px;"></i>Edit</button>
              <button class="btn-pause"><i class="bi bi-pause-fill" style="margin-right:5px;"></i>Pause</button>
            </div>
          </div>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span
          class="nl">Calendar</span></div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span
            class="nl">Notifications</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
      </div>
    </div>
  </div>
  <script src="../assets/js/app.js"></script>
  <script>initTheme();</script>
</body>

</html>
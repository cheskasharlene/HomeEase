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
  <style>
    body {
      background: #fff;
    }

    .screen {
      background: #F9F5EF;
      justify-content: flex-start;
    }

    .p-scroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 90px;
      scrollbar-width: none;
    }

    .p-scroll::-webkit-scrollbar {
      display: none;
    }

    .p-hdr {
      width: 100%;
      padding: 48px 22px 24px;
      background: radial-gradient(ellipse at 80% 0%, rgba(255, 200, 80, .5) 0%, transparent 50%), radial-gradient(ellipse at 5% 90%, rgba(200, 90, 0, .12) 0%, transparent 45%), linear-gradient(160deg, rgba(216, 100, 8, .88) 0%, rgba(232, 130, 12, .70) 35%, rgba(245, 166, 35, .45) 65%, rgba(255, 183, 107, .15) 85%, transparent 100%);
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .p-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .06) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }

    .p-hdr-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #1A1A2E;
      position: relative;
      z-index: 1;
    }

    .p-hdr-sub {
      font-size: 12px;
      color: #6B7280;
      font-weight: 600;
      margin-top: 2px;
      position: relative;
      z-index: 1;
    }

    .svc-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 18px 18px 0;
    }

    .svc-card {
      background: #fff;
      border-radius: 18px;
      padding: 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .07);
      border: 1.5px solid #EDE8E0;
    }

    .svc-top {
      display: flex;
      align-items: center;
      gap: 13px;
    }

    .svc-ic {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      background: linear-gradient(135deg, #FFE5B4, #FFF8F0);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }

    .svc-nm {
      font-family: 'Poppins', sans-serif;
      font-size: 15px;
      font-weight: 800;
      color: #1A1A2E;
    }

    .svc-desc {
      font-size: 12px;
      color: #8E8E93;
      margin-top: 2px;
    }

    .svc-price {
      font-family: 'Poppins', sans-serif;
      font-size: 17px;
      font-weight: 800;
      color: #F5A623;
      margin-left: auto;
      flex-shrink: 0;
    }

    .svc-footer {
      display: flex;
      gap: 8px;
      margin-top: 12px;
      padding-top: 10px;
      border-top: 1px solid #EDE8E0;
    }

    .btn-edit {
      flex: 1;
      padding: 9px;
      background: #FFF8F0;
      color: #D4790A;
      border: 1.5px solid #FFE5B4;
      border-radius: 12px;
      font-family: 'Poppins', sans-serif;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s;
    }

    .btn-pause {
      flex: 1;
      padding: 9px;
      background: #f3f4f6;
      color: #6b7280;
      border: 1.5px solid #e5e7eb;
      border-radius: 12px;
      font-family: 'Poppins', sans-serif;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }

    .btn-add {
      width: calc(100% - 36px);
      margin: 16px 18px 0;
      padding: 14px;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff;
      border: none;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 8px 24px rgba(232, 130, 12, .3);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all .2s;
    }

    .btn-add:hover {
      transform: translateY(-2px);
      box-shadow: 0 14px 32px rgba(232, 130, 12, .38);
    }

    .bnav {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff !important;
      border-top: 1px solid #EDE8E0 !important;
      display: flex;
      padding: 9px 0 calc(12px + env(safe-area-inset-bottom));
      box-shadow: 0 -4px 20px rgba(232, 130, 12, .07);
      z-index: 50;
    }

    .ni {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      cursor: pointer;
      color: #C5BEB3;
      font-family: "Nunito", sans-serif;
      padding: 2px 0;
    }

    .ni i {
      font-size: 22px;
    }

    .ni.on,
    .ni.on i,
    .ni.on .nl {
      color: #F5A623;
    }

    .nl {
      font-size: 10px;
      font-weight: 700;
    }

    .nb-c {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 22px;
      margin-top: -22px;
      box-shadow: 0 6px 20px rgba(232, 130, 12, .45);
    }
  </style>
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
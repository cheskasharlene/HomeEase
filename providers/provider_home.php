<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Provider');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Provider Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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

    .ph-hdr {
      width: 100%;
      padding: 48px 22px 24px;
      background:
        radial-gradient(ellipse at 80% 0%, rgba(255, 200, 80, .50) 0%, transparent 50%),
        radial-gradient(ellipse at 5% 90%, rgba(200, 90, 0, .12) 0%, transparent 45%),
        linear-gradient(145deg, #C86500 0%, #D97108 20%, #E8820C 42%, #F5A623 65%, #FFB347 85%, #FFC96B 100%);
      border-radius: 0 0 30px 30px;
      box-shadow: 0 8px 32px rgba(232, 130, 12, .28);
      position: relative;
      overflow: hidden;
    }

    .ph-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .07) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }

    .ph-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      position: relative;
      z-index: 1;
    }

    .ph-info .ph-greet {
      font-size: 13px;
      color: rgba(255, 255, 255, .78);
      font-weight: 500;
    }

    .ph-info .ph-name {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, .1);
      letter-spacing: -.3px;
    }

    .ph-badge {
      background: rgba(255, 255, 255, .2);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255, 255, 255, .3);
      border-radius: 20px;
      padding: 3px 12px;
      font-size: 11px;
      font-weight: 700;
      color: #fff;
      display: inline-block;
      margin-top: 5px;
    }

    .ph-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .ph-btn {
      width: 42px;
      height: 42px;
      background: rgba(255, 255, 255, .2);
      backdrop-filter: blur(8px);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 18px;
      cursor: pointer;
      border: 1.5px solid rgba(255, 255, 255, .28);
      transition: background .2s;
      position: relative;
    }

    .ph-btn:hover {
      background: rgba(255, 255, 255, .3);
    }

    .ph-bell-dot {
      position: absolute;
      top: 7px;
      right: 7px;
      width: 8px;
      height: 8px;
      background: #fff;
      border-radius: 50%;
      border: 2px solid #F5A623;
    }

    .avail-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      position: relative;
      z-index: 1;
      margin-bottom: 14px;
    }

    .avail-lbl {
      font-size: 12px;
      font-weight: 700;
      color: rgba(255, 255, 255, .85);
    }

    .avail-sw {
      position: relative;
      width: 48px;
      height: 26px;
    }

    .avail-sw input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .avail-slider {
      position: absolute;
      inset: 0;
      background: rgba(255, 255, 255, .25);
      border-radius: 13px;
      cursor: pointer;
      transition: .3s;
      border: 1.5px solid rgba(255, 255, 255, .3);
    }

    .avail-slider::after {
      content: '';
      position: absolute;
      left: 3px;
      top: 3px;
      width: 18px;
      height: 18px;
      background: #fff;
      border-radius: 50%;
      transition: .3s;
      box-shadow: 0 1px 4px rgba(0, 0, 0, .2);
    }

    .avail-sw input:checked+.avail-slider {
      background: rgba(16, 185, 129, .7);
    }

    .avail-sw input:checked+.avail-slider::after {
      transform: translateX(22px);
    }

    .avail-status {
      font-size: 12px;
      font-weight: 800;
      color: #fff;
    }

    .p-stats-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
      padding: 18px 18px 0;
    }

    .p-stat-chip {
      background: #fff;
      border-radius: 16px;
      padding: 14px 8px;
      text-align: center;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .07);
      border: 1.5px solid #EDE8E0;
    }

    .p-stat-chip .val {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      font-weight: 800;
      color: #F5A623;
    }

    .p-stat-chip .lbl {
      font-size: 10px;
      color: #8E8E93;
      font-weight: 600;
      margin-top: 2px;
    }

    .sec-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 22px 18px 10px;
    }

    .sec-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 16px;
      font-weight: 800;
      color: #1A1A2E;
    }

    .sec-lnk {
      font-size: 13px;
      color: #F5A623;
      font-weight: 700;
      cursor: pointer;
    }

    /* Request cards */
    .req-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 0 18px;
    }

    .req-card {
      background: #fff;
      border-radius: 18px;
      padding: 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .07);
      border: 1.5px solid #EDE8E0;
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }

    .req-ic {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      background: linear-gradient(135deg, #FFE5B4, #FFF8F0);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
    }

    .req-body {
      flex: 1;
      min-width: 0;
    }

    .req-type {
      font-size: 11px;
      font-weight: 800;
      color: #F5A623;
      text-transform: uppercase;
      letter-spacing: .7px;
    }

    .req-name {
      font-family: 'Poppins', sans-serif;
      font-size: 15px;
      font-weight: 800;
      color: #1A1A2E;
      margin-top: 2px;
    }

    .req-meta {
      font-size: 12px;
      color: #8E8E93;
      margin-top: 4px;
      line-height: 1.6;
    }

    .req-price {
      font-size: 15px;
      font-weight: 800;
      color: #F5A623;
      font-family: 'Poppins', sans-serif;
    }

    .req-btns {
      display: flex;
      flex-direction: column;
      gap: 7px;
      flex-shrink: 0;
    }

    .btn-accept {
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 8px 16px;
      font-size: 12px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(232, 130, 12, .3);
      transition: all .2s;
    }

    .btn-accept:hover {
      transform: translateY(-1px);
    }

    .btn-decline {
      background: #FFF8F0;
      color: #F5A623;
      border: 1.5px solid #FFE5B4;
      border-radius: 12px;
      padding: 8px 16px;
      font-size: 12px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      cursor: pointer;
      transition: all .2s;
    }

    /* Schedule */
    .sched-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 0 18px;
    }

    .sched-card {
      background: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .06);
      border: 1.5px solid #EDE8E0;
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .sched-dot {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      flex-shrink: 0;
      box-shadow: 0 2px 6px rgba(232, 130, 12, .4);
    }

    .sched-time {
      font-size: 11px;
      font-weight: 800;
      color: #F5A623;
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .sched-title {
      font-size: 14px;
      font-weight: 700;
      color: #1A1A2E;
      font-family: 'Poppins', sans-serif;
    }

    .rev-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 0 18px;
    }

    .rev-card {
      background: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .06);
      border: 1.5px solid #EDE8E0;
    }

    .rev-top {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .rev-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 14px;
      font-weight: 800;
      flex-shrink: 0;
    }

    .rev-name {
      font-size: 13px;
      font-weight: 700;
      color: #1A1A2E;
    }

    .rev-stars {
      font-size: 13px;
      color: #f59e0b;
      margin-top: 1px;
    }

    .rev-text {
      font-size: 13px;
      color: #8E8E93;
      line-height: 1.5;
    }

    /* Earnings */
    .earn-card {
      margin: 0 18px;
      background: #fff;
      border-radius: 18px;
      padding: 18px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .07);
      border: 1.5px solid #EDE8E0;
    }

    .earn-total {
      font-family: 'Poppins', sans-serif;
      font-size: 28px;
      font-weight: 800;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .earn-lbl {
      font-size: 12px;
      color: #8E8E93;
      font-weight: 600;
      margin-top: 2px;
    }

    .earn-bar-track {
      height: 8px;
      background: #EDE8E0;
      border-radius: 4px;
      overflow: hidden;
      margin-top: 12px;
    }

    .earn-bar-fill {
      height: 100%;
      border-radius: 4px;
      background: linear-gradient(to right, #E8820C, #F5A623, #FFB347);
      width: 75%;
    }

    .h-pb {
      height: 90px;
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

    <div class="screen" id="home">
      <div style="flex:1;overflow-y:auto;overflow-x:hidden;scrollbar-width:none;padding-bottom:90px;" id="phScroll">

        <!-- Header -->
        <div class="ph-hdr">
          <div class="ph-top">
            <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:1;">
              <div
                style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;border:2px solid rgba(255,255,255,.4);">
                <?= strtoupper(substr($providerName, 0, 1)) ?>
              </div>
              <div class="ph-info">
                <div class="ph-greet"><?= $greeting ?> 👷</div>
                <div class="ph-name"><?= $providerName ?></div>
                <div class="ph-badge"><i class="bi bi-tools"
                    style="font-size:9px;margin-right:4px;"></i><?= htmlspecialchars($_SESSION['provider_specialty'] ?? 'Service Provider') ?>
                </div>
              </div>
            </div>
            <div class="ph-right">
              <button class="ph-btn" onclick="toggleDark()"><i class="bi bi-moon-fill" id="dmIcon"></i></button>
              <div class="ph-btn" onclick="goPage('provider_notifications.php')">
                <i class="bi bi-bell-fill"></i>
                <div class="ph-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
            </div>
          </div>
          <div class="avail-wrap">
            <div class="avail-lbl">Status:</div>
            <label class="avail-sw">
              <input type="checkbox" id="availToggle">
              <span class="avail-slider"></span>
            </label>
            <div class="avail-status" id="availLabel">Offline</div>
          </div>
        </div>

     
        <div class="p-stats-row">
          <div class="p-stat-chip">
            <div class="val">5</div>
            <div class="lbl">Pending</div>
          </div>
          <div class="p-stat-chip">
            <div class="val">2</div>
            <div class="lbl">Active</div>
          </div>
          <div class="p-stat-chip">
            <div class="val">4.8</div>
            <div class="lbl">Rating</div>
          </div>
          <div class="p-stat-chip">
            <div class="val">24</div>
            <div class="lbl">Done</div>
          </div>
        </div>

   
        <div class="sec-row">
          <div class="sec-ttl">Incoming Requests</div>
          <span class="sec-lnk" onclick="goPage('provider_requests.php')">See all →</span>
        </div>
        <div class="req-list">
          <div class="req-card">
            <div class="req-ic">🔧</div>
            <div class="req-body">
              <div class="req-type">Plumbing</div>
              <div class="req-name">John Doe</div>
              <div class="req-meta">📍 123 Main St<br>🕐 Apr 1, 10:00 AM</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
              <div class="req-price">₱2,500</div>
              <div class="req-btns">
                <button class="btn-accept">Accept</button>
                <button class="btn-decline">Decline</button>
              </div>
            </div>
          </div>
          <div class="req-card">
            <div class="req-ic">⚡</div>
            <div class="req-body">
              <div class="req-type">Electrical</div>
              <div class="req-name">Maria Santos</div>
              <div class="req-meta">📍 Rizal Avenue<br>🕐 Apr 2, 2:00 PM</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
              <div class="req-price">₱3,750</div>
              <div class="req-btns">
                <button class="btn-accept">Accept</button>
                <button class="btn-decline">Decline</button>
              </div>
            </div>
          </div>
        </div>

   
        <div class="sec-row">
          <div class="sec-ttl">Today's Schedule</div>
          <span class="sec-lnk" onclick="goPage('provider_schedule.php')">Full calendar →</span>
        </div>
        <div class="sched-list">
          <div class="sched-card">
            <div class="sched-dot"></div>
            <div>
              <div class="sched-time">Apr 1 · 10:00 AM</div>
              <div class="sched-title">Plumbing with Jane Smith</div>
            </div>
          </div>
          <div class="sched-card">
            <div class="sched-dot"
              style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 2px 6px rgba(16,185,129,.4);">
            </div>
            <div>
              <div class="sched-time" style="color:#10b981;">Apr 2 · 2:00 PM</div>
              <div class="sched-title">Home Cleaning with Bob Lee</div>
            </div>
          </div>
        </div>

   
        <div class="sec-row">
          <div class="sec-ttl">Earnings This Month</div>
        </div>
        <div class="earn-card">
          <div class="earn-total">₱12,400</div>
          <div class="earn-lbl">24 jobs completed · Goal: ₱16,500</div>
          <div class="earn-bar-track">
            <div class="earn-bar-fill"></div>
          </div>
        </div>

      
        <div class="sec-row">
          <div class="sec-ttl">Recent Reviews</div>
          <span class="sec-lnk">See all →</span>
        </div>
        <div class="rev-list">
          <div class="rev-card">
            <div class="rev-top">
              <div class="rev-avatar">A</div>
              <div>
                <div class="rev-name">Anna K.</div>
                <div class="rev-stars">★★★★★</div>
              </div>
            </div>
            <div class="rev-text">Great work, very professional and on time!</div>
          </div>
        </div>

        <div class="h-pb"></div>
      </div>

      <div class="bnav">
        <div class="ni on" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_services.php')">
          <div class="nb-c"><i class="bi bi-plus-lg"></i></div>
        </div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span
            class="nl">Notifications</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();
    (function () { const ic = document.getElementById('dmIcon'); if (ic && document.body.classList.contains('dark')) ic.className = 'bi bi-sun-fill'; })();
    document.getElementById('bellDot').style.display = 'block';
    const toggle = document.getElementById('availToggle');
    const lbl = document.getElementById('availLabel');
    toggle.addEventListener('change', function () { lbl.textContent = this.checked ? 'Online' : 'Offline'; });
  </script>
</body>

</html>
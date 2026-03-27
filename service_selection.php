<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Select Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    :root {
      --teal: #F5A623;
      --teal2: #E8960F;
      --teal-lt: #FDECC8;
      --teal-xlt: #FFF3E0;
      --gold: #f59e0b;
      --red: #ef4444;
      --td: #1A1A2E;
      --tm: #8E8E93;
      --tbg: #F8F8F8;
      --card: #ffffff;
      --border: #e5e7eb;
      --radius: 18px;
      --shadow: 0 2px 16px rgba(245, 166, 35, .09);
    }

    body.dark {
      --td: #FFF3DC;
      --tm: #B8A882;
      --tbg: #2A2216;
      --card: #2A2216;
      --border: #4A3E28;
      --teal-xlt: #2A2216;
      --teal-lt: #4A3E28;
      background: #18140C;
    }

    body.dark .shell {
      background: #201A10;
    }

    body.dark #svcGrid {
      background: #18140C;
    }

    body.dark .svc-card {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
    }

    body.dark .svc-name {
      color: #FFF3DC !important;
    }

    body.dark .svc-icon-badge {
      background: #332A1C !important;
      border-color: #4A3E28 !important;
    }

    body.dark .svc-icon-badge i,
    body.dark .svc-icon-badge span {
      color: #F5A623 !important;
    }

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: #FDF9F4;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      overflow-x: hidden;
    }

    .shell {
      max-width: 420px;
      width: 100%;
      height: 100dvh;
      background: var(--card);
      position: relative;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    @media (min-width: 600px) {
      body {
        padding: 20px;
      }

      .shell {
        height: min(860px, 100dvh);
        border-radius: 44px;
        box-shadow:
          0 40px 80px rgba(232, 130, 12, .12),
          0 8px 24px rgba(232, 130, 12, 0.08),
          0 0 0 1px rgba(245, 166, 35, 0.08);
      }
    }

    .hdr {
      padding: 48px 20px 20px;
      background:
        radial-gradient(ellipse at 80% 0%, rgba(255, 200, 80, 0.50) 0%, transparent 50%),
        radial-gradient(ellipse at 5% 90%, rgba(200, 90, 0, 0.12) 0%, transparent 45%),
        linear-gradient(160deg,
          rgba(216, 100, 8, 0.88) 0%,
          rgba(232, 130, 12, 0.70) 35%,
          rgba(245, 166, 35, 0.45) 65%,
          rgba(255, 183, 107, 0.15) 85%,
          rgba(255, 255, 255, 0) 100%);
      flex-shrink: 0;
      position: relative;
      overflow: hidden;
    }

    .hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.06) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }

    .hdr-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    .hdr-title {
      font-family: 'Poppins', sans-serif;
      font-size: 26px;
      font-weight: 800;
      color: #1A1A2E;
      margin: 0 0 4px;
    }

    .hdr-sub {
      color: rgba(26, 26, 46, 0.6);
      font-size: 13px;
      font-weight: 500;
    }

    .hdr-btn {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(8px);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1A1A2E;
      font-size: 18px;
      cursor: pointer;
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      text-decoration: none;
      transition: all 0.2s;
    }

    .hdr-btn:hover {
      background: rgba(255, 255, 255, 0.32);
      transform: scale(1.05);
    }

    .hdr-btn:active {
      transform: scale(0.95);
    }

    .svc-grid {
      flex: 1;
      overflow-y: auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      padding: 20px;
      background: var(--tbg);
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    .svc-grid::-webkit-scrollbar {
      display: none;
    }

    .svc-card {
      background: var(--card);
      border: 2px solid var(--border);
      border-radius: 20px;
      padding: 18px 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
      text-align: center;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .svc-card:hover {
      border-color: var(--teal);
      background: var(--teal-xlt);
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(245, 166, 35, 0.15);
    }

    .svc-card:active {
      transform: scale(0.97);
    }

    .svc-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 56px;
      width: 100%;
    }

    .svc-icon-badge {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      border: 1px solid #FFE5B4;
      background: #FFF8F0;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 8px rgba(232, 130, 12, 0.08);
    }

    .svc-icon-badge i,
    .svc-icon-badge span {
      color: #E8960F;
      font-size: 24px;
      line-height: 1;
    }

    .svc-name {
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 700;
      color: var(--td);
      line-height: 1.3;
    }

    .svc-price {
      font-family: 'Nunito', sans-serif;
      font-size: 12px;
      color: var(--tm);
      font-weight: 600;
    }

    .svc-price .price-val {
      color: var(--teal);
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
    }

    .svc-loading {
      grid-column: 1 / -1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      color: var(--tm);
      min-height: 240px;
      font-size: 14px;
      font-weight: 500;
    }

    .svc-loading i {
      animation: spin 1s linear infinite;
      font-size: 18px;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>
</head>

<body>
  <div class="shell">
    <div class="hdr">
      <div class="hdr-top">
        <div>
          <div class="hdr-sub">What do you need?</div>
          <div class="hdr-title">Select a Service</div>
        </div>
        <a href="home.php" class="hdr-btn"><i class="bi bi-arrow-left"></i></a>
      </div>
    </div>

    <div class="svc-grid" id="svcGrid">
      <div class="svc-loading"><i class="bi bi-arrow-clockwise"></i> Loading services...</div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    initTheme();
  </script>
  <script>
    let services = [];

    const serviceIconClass = {
      'Cleaner': 'bi-brush',
      'Plumber': 'bi-wrench-adjustable-circle',
      'Laundry Worker': 'bi-basket3',
      'Helper': 'bi-house-heart',
      'Carpenter': 'bi-hammer',
      'Appliance Technician': 'bi-lightning-charge'
    };

    function brandIcon(name, fallback) {
      const iconClass = serviceIconClass[name] || '';
      if (iconClass) {
        return `<div class="svc-icon-badge"><i class="bi ${iconClass}"></i></div>`;
      }
      return `<div class="svc-icon-badge"><span>${fallback || '🛠️'}</span></div>`;
    }

    async function loadServices() {
      try {
        const res = await fetch('api/bookings_api.php?action=services');
        const data = await res.json();
        if (!data.success || !data.services.length) {
          document.getElementById('svcGrid').innerHTML =
            '<div class="svc-loading" style="color:#ef4444; grid-column: 1 / -1;">No services available yet.</div>';
          return;
        }
        services = data.services;
        renderServices();
      } catch (e) {
        document.getElementById('svcGrid').innerHTML =
          '<div class="svc-loading" style="color:#ef4444; grid-column: 1 / -1;"><i class="bi bi-wifi-off"></i> Failed to load services.</div>';
        console.error(e);
      }
    }

    function renderServices() {
      document.getElementById('svcGrid').innerHTML = services.map((s, i) => `
    <div class="svc-card" id="svcCard${i}" onclick="selectService(${i})">
      <div class="svc-icon">${brandIcon(s.name, s.icon)}</div>
      <div class="svc-name">${s.name}</div>
      <div class="svc-price">Starting <span class="price-val">₱${parseFloat(s.flat_rate || 0).toLocaleString()}</span></div>
    </div>
  `).join('');
    }

    function selectService(i) {
      const svc = services[i];
      window.location.href = 'booking_form.php?svc=' + encodeURIComponent(svc.name);
    }

    loadServices();
  </script>
</body>

</html>

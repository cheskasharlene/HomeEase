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
  <link rel="stylesheet" href="assets/css/service_selection.css">
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

    const serviceConfig = {
      'Cleaner': {
        icon: 'bi-stars',
        bg: 'linear-gradient(135deg, #E0F2FE, #BAE6FD)',
        color: '#0284C7',
        accent: '#0EA5E9',
        shadow: 'rgba(14,165,233,.18)',
        desc: 'Home & office cleaning'
      },
      'Plumber': {
        icon: 'bi-wrench-adjustable-circle',
        bg: 'linear-gradient(135deg, #EEF2FF, #C7D2FE)',
        color: '#4F46E5',
        accent: '#6366F1',
        shadow: 'rgba(99,102,241,.18)',
        desc: 'Pipes, leaks & fixtures'
      },
      'Carpenter': {
        icon: 'bi-hammer',
        bg: 'linear-gradient(135deg, #FEF9C3, #FDE68A)',
        color: '#D97706',
        accent: '#F59E0B',
        shadow: 'rgba(245,158,11,.18)',
        desc: 'Furniture & woodwork'
      },
      'Laundry Worker': {
        icon: 'bi-basket2-fill',
        bg: 'linear-gradient(135deg, #FCE7F3, #FBCFE8)',
        color: '#BE185D',
        accent: '#EC4899',
        shadow: 'rgba(236,72,153,.18)',
        desc: 'Washing & ironing'
      },
      'Appliance Technician': {
        icon: 'bi-tools',
        bg: 'linear-gradient(135deg, #FFF7ED, #FED7AA)',
        color: '#C2410C',
        accent: '#F97316',
        shadow: 'rgba(249,115,22,.18)',
        desc: 'Fix home appliances'
      },
      'Helper': {
        icon: 'bi-person-arms-up',
        bg: 'linear-gradient(135deg, #DCFCE7, #BBF7D0)',
        color: '#15803D',
        accent: '#22C55E',
        shadow: 'rgba(34,197,94,.18)',
        desc: 'All-around household help'
      }
    };

    function brandIcon(name, fallback) {
      const cfg = serviceConfig[name];
      if (cfg) {
        return `<div class="svc-icon-badge" style="background:${cfg.bg};--svc-shadow:${cfg.shadow};">
          <i class="bi ${cfg.icon}" style="color:${cfg.color};"></i>
        </div>`;
      }
      return `<div class="svc-icon-badge" style="background:#FFF8F0;"><span style="font-size:26px;">${fallback || '🛠️'}</span></div>`;
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
      document.getElementById('svcGrid').innerHTML = services.map((s, i) => {
        const cfg = serviceConfig[s.name] || {};
        const accent = cfg.accent || '#F5A623';
        const desc = cfg.desc || s.description || '';
        return `
    <div class="svc-card" id="svcCard${i}" onclick="selectService(${i})" style="--svc-accent:${accent};">
      <div class="svc-icon">${brandIcon(s.name, s.icon)}</div>
      <div class="svc-name">${s.name}</div>
      ${desc ? `<div class="svc-desc">${desc}</div>` : ''}
      <div class="svc-price">from <span class="price-val">₱${parseFloat(s.flat_rate || 0).toLocaleString()}</span></div>
    </div>
  `}).join('');
    }

    function selectService(i) {
      const svc = services[i];
      window.location.href = 'booking_form.php?svc=' + encodeURIComponent(svc.name);
    }

    loadServices();
  </script>
</body>

</html>

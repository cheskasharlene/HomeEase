<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Booking History</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/bookings.css">
  <style>
    .tabs-row {
      display: flex;
      gap: 8px;
      overflow-x: auto;
      padding: 0 18px 10px;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    .tabs-row::-webkit-scrollbar {
      display: none;
    }

    .tab-chip {
      border: 1.5px solid var(--border-col);
      background: var(--bg-card);
      color: var(--tm);
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      white-space: nowrap;
      cursor: pointer;
      transition: all .18s;
      font-family: 'Nunito', sans-serif;
    }

    .tab-chip.on {
      background: linear-gradient(135deg, var(--g-start), var(--g-mid));
      border-color: var(--g-mid);
      color: #fff;
    }

    .sec-head {
      padding: 18px 18px 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .sec-count {
      font-size: 12px;
      color: var(--tm);
      font-weight: 700;
    }

    .scroll {
      padding-bottom: 96px;
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
  <div id="toastBox"></div>
  <div class="shell">

    <div class="hdr">
      <div class="hdr-top">
        <div>
          <div class="hdr-sub">Hi, <?= $userName ?> 👋</div>
          <div class="hdr-title">Booking History</div>
        </div>
        <a href="home.php" class="hdr-btn"><i class="bi bi-arrow-left"></i></a>
      </div>
    </div>

    <div class="scroll">
      <div class="sec-head">
        <div class="sec-title" style="margin:0;">My Bookings</div>
        <div class="sec-count" id="bkCount">0</div>
      </div>

      <div class="tabs-row">
        <button class="tab-chip on" data-filter="all">All Bookings</button>
        <button class="tab-chip" data-filter="pending">Pending</button>
        <button class="tab-chip" data-filter="completed">Completed</button>
        <button class="tab-chip" data-filter="canceled">Canceled</button>
      </div>

      <div class="sec" style="padding-top:0;">
        <div id="myBookings">
          <div class="loader-txt"><i class="bi bi-arrow-clockwise"></i> Loading...</div>
        </div>
      </div>
    </div>

    <div class="bnav">
      <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
      <div class="ni on" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
      <div class="ni" onclick="goPage('booking_form.php?newbooking=1')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
      <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
      <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    initTheme();

    function goPage(page) {
      window.location.href = page;
    }

    let allBookings = [];
    let activeFilter = 'all';

    function normalizeStatus(raw) {
      const s = String(raw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'completed';
      if (s === 'cancelled' || s === 'canceled') return 'canceled';
      return 'pending';
    }

    function filterBookings() {
      if (activeFilter === 'all') return allBookings;
      return allBookings.filter(b => normalizeStatus(b.status) === activeFilter);
    }

    async function loadMyBookings() {
      const el = document.getElementById('myBookings');
      try {
        const res = await fetch('api/bookings_api.php');
        const data = await res.json();

        allBookings = data.success && Array.isArray(data.bookings) ? data.bookings : [];
        renderBookings();
      } catch (e) {
        el.innerHTML = `<div class="empty"><i class="bi bi-wifi-off"></i><p>Could not load bookings.</p></div>`;
      }
    }

    function renderBookings() {
      const el = document.getElementById('myBookings');
      const rows = filterBookings();
      document.getElementById('bkCount').textContent = `${rows.length} ${rows.length === 1 ? 'booking' : 'bookings'}`;

      if (!rows.length) {
        el.innerHTML = `<div class="empty"><i class="bi bi-calendar-x"></i><p>No bookings for this tab.</p></div>`;
        return;
      }

      const svcIcon = {
        'Cleaning': '🧹', 'Plumbing': '🔧', 'Electrical': '⚡',
        'Painting': '🖌️', 'Appliance Repair': '🔩', 'Gardening': '🌿'
      };

      el.innerHTML = rows.map(b => {
        const icon = svcIcon[b.service] || '🏠';
        const rawStatus = normalizeStatus(b.status);
        const pillClass = rawStatus === 'canceled' ? 'cancelled' : rawStatus === 'completed' ? 'done' : 'pending';
        const statusText = rawStatus === 'canceled' ? 'Canceled' : rawStatus === 'completed' ? 'Completed' : 'Pending';
        const providerName = b.technician_name ? b.technician_name : 'Provider not assigned';
        const dateTime = `${b.date || '—'}${b.time_slot ? ' · ' + b.time_slot : ''}`;

        return `
        <div class="bk-card">
          <div class="bk-top">
            <div class="bk-ic">${icon}</div>
            <div style="flex:1;min-width:0;">
              <div class="bk-svc">${b.service || 'Service'}</div>
              <div class="bk-meta">
                <i class="bi bi-person-badge" style="font-size:9px;"></i> ${providerName}<br>
                <i class="bi bi-calendar3" style="font-size:9px;"></i> ${dateTime}
              </div>
            </div>
            <div class="bk-right">
              <span class="pill ${pillClass}">${statusText}</span>
            </div>
          </div>
        </div>`;
      }).join('');
    }

    document.querySelectorAll('.tab-chip').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-chip').forEach(b => b.classList.remove('on'));
        this.classList.add('on');
        activeFilter = this.dataset.filter;
        renderBookings();
      });
    });

    loadMyBookings();
  </script>
</body>

</html>

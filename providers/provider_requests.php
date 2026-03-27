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
  <title>HomeEase – Requests</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
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
      background:
        radial-gradient(ellipse at 80% 0%, rgba(255, 200, 80, .5) 0%, transparent 50%),
        radial-gradient(ellipse at 5% 90%, rgba(200, 90, 0, .12) 0%, transparent 45%),
        linear-gradient(160deg, rgba(216, 100, 8, .88) 0%, rgba(232, 130, 12, .70) 35%, rgba(245, 166, 35, .45) 65%, rgba(255, 183, 107, .15) 85%, transparent 100%);
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


    .filter-row {
      display: flex;
      gap: 8px;
      padding: 16px 18px 4px;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .filter-row::-webkit-scrollbar {
      display: none;
    }

    .fpill {
      background: #fff;
      border: 2px solid #EDE8E0;
      border-radius: 30px;
      padding: 7px 16px;
      font-size: 12px;
      font-weight: 700;
      color: #8E8E93;
      cursor: pointer;
      white-space: nowrap;
      transition: all .2s;
      flex-shrink: 0;
    }

    .fpill.active {
      background: linear-gradient(135deg, #E8820C, #F5A623);
      border-color: transparent;
      color: #fff;
      box-shadow: 0 4px 14px rgba(232, 130, 12, .3);
    }

    .req-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
      padding: 14px 18px 0;
    }

    .req-card {
      background: #fff;
      border-radius: 20px;
      padding: 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .07);
      border: 1.5px solid #EDE8E0;
    }

    .req-top {
      display: flex;
      align-items: flex-start;
      gap: 13px;
    }

    .req-ic {
      width: 50px;
      height: 50px;
      border-radius: 14px;
      background: linear-gradient(135deg, #FFE5B4, #FFF8F0);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }

    .req-info {
      flex: 1;
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
      font-size: 16px;
      font-weight: 800;
      color: #1A1A2E;
      margin-top: 2px;
    }

    .req-meta {
      font-size: 12px;
      color: #8E8E93;
      margin-top: 5px;
      line-height: 1.7;
    }

    .req-price-tag {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      font-weight: 800;
      color: #F5A623;
      flex-shrink: 0;
    }

    .req-footer {
      display: flex;
      gap: 10px;
      margin-top: 14px;
      padding-top: 12px;
      border-top: 1px solid #EDE8E0;
    }

    .btn-accept {
      flex: 1;
      padding: 11px;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff;
      border: none;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(232, 130, 12, .3);
      transition: all .2s;
    }

    .btn-accept:hover {
      transform: translateY(-1px);
    }

    .btn-decline {
      flex: 1;
      padding: 11px;
      background: #FFF8F0;
      color: #D4790A;
      border: 2px solid #FFE5B4;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: all .2s;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      padding: 3px 10px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
    }

    .status-pill.new {
      background: linear-gradient(135deg, #fef9e7, #fef3c7);
      color: #d97706;
      border: 1px solid #fde68a;
    }

    .status-pill.accepted {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #059669;
    }

    .status-pill.declined {
      background: #f3f4f6;
      color: #6b7280;
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

    <div class="screen" id="requests-page">
      <div class="p-scroll">
        <div class="p-hdr">
          <div style="position:relative;z-index:1;">
            <div class="p-hdr-ttl">Service Requests</div>
            <div class="p-hdr-sub">2 new requests waiting</div>
          </div>
          <div
            style="background:rgba(255,255,255,.2);backdrop-filter:blur(8px);border:1.5px solid rgba(255,255,255,.3);border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;z-index:1;"
            onclick="goPage('provider_home.php')">
            <i class="bi bi-arrow-left" style="color:#1A1A2E;font-size:18px;"></i>
          </div>
        </div>

        <div class="filter-row" id="filterRow">
          <div class="fpill active" data-filter="all">All</div>
          <div class="fpill" data-filter="new">New</div>
          <div class="fpill" data-filter="accepted">Accepted</div>
          <div class="fpill" data-filter="completed">Completed</div>
        </div>

        <div class="req-list" id="reqList">
          <div class="req-card" style="text-align:center;color:#8E8E93;">Loading requests...</div>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
        <div class="ni on" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
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
  <script>
    initTheme();

    const reqList = document.getElementById('reqList');
    let currentFilter = 'all';

    const svcIcon = {
      'cleaning': '🧹',
      'plumbing': '🔧',
      'electrical': '⚡',
      'painting': '🖌️',
      'appliance repair': '🔩',
      'gardening': '🌿'
    };

    function iconFor(service) {
      const key = String(service || '').toLowerCase();
      return svcIcon[key] || '🏠';
    }

    function statusClass(status) {
      const s = String(status || '').toLowerCase();
      if (s === 'accepted') return 'accepted';
      if (s === 'declined' || s === 'closed') return 'declined';
      return 'new';
    }

    function statusLabel(status) {
      const s = String(status || '').toLowerCase();
      if (s === 'accepted') return 'Accepted';
      if (s === 'declined') return 'Declined';
      if (s === 'closed') return 'Request Closed';
      return 'New';
    }

    async function loadRequests() {
      try {
        const res = await fetch(`../api/provider_requests_api.php?filter=${encodeURIComponent(currentFilter)}`, { cache: 'no-store' });
        const data = await res.json();
        if (!data.success) {
          reqList.innerHTML = `<div class="req-card" style="text-align:center;color:#ef4444;">${data.message || 'Failed to load requests.'}</div>`;
          return;
        }

        const items = Array.isArray(data.requests) ? data.requests : [];
        const pendingCount = items.filter(i => String(i.status).toLowerCase() === 'pending').length;
        document.querySelector('.p-hdr-sub').textContent = pendingCount > 0
          ? `${pendingCount} new request${pendingCount > 1 ? 's' : ''} waiting`
          : 'No new requests';

        if (!items.length) {
          reqList.innerHTML = '<div class="req-card" style="text-align:center;color:#8E8E93;">No requests in this tab.</div>';
          return;
        }

        reqList.innerHTML = items.map(item => {
          const sClass = statusClass(item.status);
          const sLabel = statusLabel(item.status);
          const canAction = String(item.status).toLowerCase() === 'pending';
          const dateText = `${item.date || '—'}${item.time_slot ? ' · ' + item.time_slot : ''}`;
          const details = item.details ? String(item.details) : 'No additional details';

          return `
            <div class="req-card">
              <div class="req-top">
                <div class="req-ic">${iconFor(item.service)}</div>
                <div class="req-info">
                  <div class="req-type">${item.service} · <span class="status-pill ${sClass}">${sLabel}</span></div>
                  <div class="req-name">${item.customer_name || 'Homeowner'}</div>
                  <div class="req-meta">📍 ${item.address || 'No address'}<br>🕐 ${dateText}<br>📝 ${details}<br>📞 ${item.customer_phone || 'No phone'}</div>
                </div>
                <div class="req-price-tag">₱${Number(item.fixed_price || 0).toLocaleString('en-PH')}</div>
              </div>
              ${canAction ? `<div class="req-footer">
                <button class="btn-accept" onclick="respondRequest(${item.id},'accept')"><i class="bi bi-check2" style="margin-right:5px;"></i>Accept</button>
                <button class="btn-decline" onclick="respondRequest(${item.id},'decline')"><i class="bi bi-x-lg" style="margin-right:5px;"></i>Decline</button>
              </div>` : ''}
            </div>`;
        }).join('');
      } catch (e) {
        reqList.innerHTML = '<div class="req-card" style="text-align:center;color:#ef4444;">Could not load requests.</div>';
      }
    }

    async function respondRequest(id, action) {
      const fd = new FormData();
      fd.append('action', action);
      fd.append('request_id', id);
      const res = await fetch('../api/provider_requests_api.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) {
        alert(data.message || 'Action failed.');
      }
      loadRequests();
    }

    document.getElementById('filterRow').addEventListener('click', function (e) {
      const pill = e.target.closest('.fpill');
      if (!pill) return;
      document.querySelectorAll('.fpill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      currentFilter = pill.dataset.filter || 'all';
      loadRequests();
    });

    loadRequests();
  </script>
</body>

</html>
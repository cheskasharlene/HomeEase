<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
enforceProviderSectionAccess('requests', $conn);
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
  <link rel="stylesheet" href="../assets/css/provider_requests.css">
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
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
      </div>
    </div>
  </div>
  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') {
      initTheme();
    }

    const reqList = document.getElementById('reqList');
    let currentFilter = 'all';

    const svcIcon = {
      'cleaning': '🧹',
      'plumbing': '🔧',
      'electrical': '⚡',
      'painting': '🖌️',
      'appliance repair': '🔩'
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
          const statusRaw = String(item.status).toLowerCase();
          const canAction = statusRaw === 'pending';
          const isAccepted = statusRaw === 'accepted';
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
              </div>` : (isAccepted ? `<div class="req-footer">
                <button class="btn-view" onclick="openAccepted(${item.booking_id || 0})"><i class="bi bi-eye" style="margin-right:5px;"></i>View details</button>
              </div>` : '')}
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
        return;
      }
      if (action === 'accept' && data.booking_id) {
        window.location.href = `provider_accepted_booking.php?booking_id=${encodeURIComponent(data.booking_id)}`;
        return;
      }
      loadRequests();
    }

    function openAccepted(bookingId) {
      if (!bookingId) return;
      window.location.href = `provider_accepted_booking.php?booking_id=${encodeURIComponent(bookingId)}`;
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
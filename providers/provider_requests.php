<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
enforceProviderSectionAccess('requests', $conn);
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Provider');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Live Requests</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_requests.css">
  <style>
    /* ===== LIVE FEED OVERRIDES ===== */
    .live-badge {
      display: inline-flex; align-items: center; gap: 5px;
      background: #EF4444; color: #fff;
      font-size: 10px; font-weight: 800; font-family: 'Poppins',sans-serif;
      padding: 3px 10px; border-radius: 99px;
      animation: livePulse 1.5s ease-in-out infinite;
    }
    .live-badge::before {
      content: ''; width: 6px; height: 6px; border-radius: 50%; background: #fff;
      animation: livePulse 1.5s ease-in-out infinite;
    }
    @keyframes livePulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }

    .feed-tabs {
      display: flex; gap: 8px; padding: 0 16px 14px; overflow-x: auto;
      scrollbar-width: none;
    }
    .feed-tabs::-webkit-scrollbar { display: none; }

    .feed-tab {
      flex-shrink: 0; padding: 7px 16px; border-radius: 99px;
      border: 1.5px solid #E8E0D5; background: #fff;
      font-size: 12px; font-weight: 700; color: #7A7064;
      cursor: pointer; transition: all 0.2s; font-family: 'Poppins',sans-serif;
    }
    .feed-tab.on {
      background: linear-gradient(135deg,#E8820C,#F5A623);
      color: #fff; border-color: transparent;
      box-shadow: 0 3px 10px rgba(232,130,12,0.3);
    }

    .live-card {
      background: #fff;
      border-radius: 18px;
      border: 1.5px solid #F0EAE0;
      padding: 16px;
      margin: 0 16px 12px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
      transition: transform 0.15s, box-shadow 0.15s;
      position: relative;
      overflow: hidden;
    }
    .live-card:active { transform: scale(0.98); }

    .live-card.new-flash {
      animation: cardFlash 0.6s ease;
    }
    @keyframes cardFlash {
      0% { background: #FEF3C7; }
      100% { background: #fff; }
    }

    .live-card-top {
      display: flex; align-items: flex-start; gap: 12px;
    }

    .live-card-icon {
      width: 48px; height: 48px; border-radius: 14px;
      background: linear-gradient(135deg,#FEF3C7,#FDE68A);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; flex-shrink: 0;
    }

    .live-card-info { flex: 1; min-width: 0; }

    .live-card-svc {
      font-size: 15px; font-weight: 800; color: #1A1A2E;
      font-family: 'Poppins',sans-serif;
    }

    .live-card-customer {
      font-size: 12px; color: #7A7064; font-weight: 600; margin-top: 2px;
    }

    .live-card-addr {
      font-size: 11px; color: #9E9690; margin-top: 4px;
      display: flex; align-items: flex-start; gap: 4px;
    }

    .live-price {
      font-size: 18px; font-weight: 900; color: #E8820C;
      font-family: 'Poppins',sans-serif; flex-shrink: 0;
    }

    .live-card-meta {
      display: flex; align-items: center; gap: 8px; margin-top: 10px;
      flex-wrap: wrap;
    }

    .live-tag {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 4px 10px; border-radius: 99px;
      font-size: 10px; font-weight: 800; font-family: 'Poppins',sans-serif;
      letter-spacing: 0.2px;
    }
    .live-tag.time { background: #FEF3C7; color: #92400E; }
    .live-tag.notes { background: #F0F9FF; color: #0369A1; }
    .live-tag.new-req { background: #FEE2E2; color: #991B1B; }
    .live-tag.declined { background: #F3F4F6; color: #6B7280; }

    .live-card-actions {
      display: flex; gap: 10px; margin-top: 12px;
    }

    .btn-live-accept {
      flex: 1; height: 44px; border-radius: 12px;
      background: linear-gradient(135deg,#E8820C,#F5A623);
      color: #fff; border: none; cursor: pointer;
      font-size: 14px; font-weight: 800; font-family: 'Poppins',sans-serif;
      display: flex; align-items: center; justify-content: center; gap: 6px;
      box-shadow: 0 4px 14px rgba(232,130,12,0.35);
      transition: transform 0.15s;
    }
    .btn-live-accept:active { transform: scale(0.96); }
    .btn-live-accept:disabled { opacity: 0.6; cursor: not-allowed; }

    .btn-live-pass {
      width: 44px; height: 44px; border-radius: 12px;
      border: 1.5px solid #E8E0D5; background: #fff;
      color: #7A7064; cursor: pointer; font-size: 18px;
      display: flex; align-items: center; justify-content: center;
      transition: background 0.15s;
    }
    .btn-live-pass:active { background: #F5F5F5; }

    .empty-feed {
      text-align: center; padding: 48px 24px; color: #9E9690;
    }
    .empty-feed-icon { font-size: 48px; margin-bottom: 12px; }
    .empty-feed-title {
      font-size: 16px; font-weight: 800; color: #1A1A2E;
      font-family: 'Poppins',sans-serif; margin-bottom: 6px;
    }
    .empty-feed-sub { font-size: 13px; line-height: 1.5; }

    .active-job-banner {
      margin: 0 16px 12px;
      padding: 14px 16px;
      background: linear-gradient(135deg,#059669,#10b981);
      border-radius: 16px;
      display: flex; align-items: center; gap: 12px;
      cursor: pointer;
    }
    .active-job-banner i { font-size: 24px; color: #fff; flex-shrink: 0; }
    .active-job-text { flex: 1; }
    .active-job-text strong { display: block; font-size: 14px; font-weight: 800; color: #fff; font-family:'Poppins',sans-serif; }
    .active-job-text span { font-size: 12px; color: rgba(255,255,255,0.85); font-weight: 600; }
    .active-job-banner .go-arrow { color: #fff; font-size: 18px; }

    .poll-bar {
      height: 2px; background: #F0EAE0; margin: 0 16px 14px; border-radius: 2px; overflow: hidden;
    }
    .poll-bar-fill {
      height: 100%; background: linear-gradient(90deg,#E8820C,#F5A623);
      border-radius: 2px;
      animation: pollSweep 5s linear infinite;
    }
    @keyframes pollSweep {
      0% { width: 0%; }
      100% { width: 100%; }
    }

    .hdr-live-row {
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 16px 12px;
    }
    .hdr-count { font-size: 12px; color: #7A7064; font-weight: 600; }
    /* ── Map Preview Modal ── */
    .map-modal-overlay{position:absolute;inset:0;z-index:900;background:rgba(0,0,0,.5);display:flex;align-items:flex-end;opacity:0;pointer-events:none;transition:opacity .25s}
    .map-modal-overlay.open{opacity:1;pointer-events:all}
    .map-modal-card{width:100%;background:#fff;border-radius:24px 24px 0 0;padding-bottom:32px;transform:translateY(100%);transition:transform .32s cubic-bezier(.32,.72,0,1)}
    .map-modal-overlay.open .map-modal-card{transform:translateY(0)}
    .map-modal-handle{width:40px;height:4px;background:#E0D8D0;border-radius:2px;margin:12px auto 0}
    .map-modal-hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 16px 8px}
    .map-modal-title{font-size:15px;font-weight:800;color:#1A1A2E;font-family:'Poppins',sans-serif}
    .map-modal-close{width:32px;height:32px;border-radius:50%;background:#F5F0EA;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;color:#7A7064}
    #mapPreview{height:220px;width:100%;display:block}
    .btn-map-accept:disabled{opacity:.6;cursor:not-allowed}
    .map-modal-info{padding:12px 16px 14px;display:flex;align-items:center;gap:10px}
    .map-modal-addr{flex:1;font-size:12px;color:#5E564D;font-weight:600;line-height:1.4}
    .map-modal-actions{display:flex;gap:10px;padding:0 16px}
    .btn-map-accept{flex:1;height:46px;border-radius:13px;background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;border:none;cursor:pointer;font-size:14px;font-weight:800;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 4px 14px rgba(232,130,12,.3)}
    .btn-map-pass{width:46px;height:46px;border-radius:13px;border:1.5px solid #E8E0D5;background:#fff;color:#7A7064;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center}
    .map-modal-chip{display:inline-flex;align-items:center;gap:4px;background:#FEF3C7;color:#92400E;padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;font-family:'Poppins',sans-serif}
  </style>
</head>
<body>
  <div class="shell" id="app">
  <!-- Map Preview Modal (inside shell to stay in mobile frame) -->
  <div class="map-modal-overlay" id="mapModal" onclick="closeMapModal(event)">
    <div class="map-modal-card" onclick="event.stopPropagation()">
      <div class="map-modal-handle"></div>
      <div class="map-modal-hdr">
        <div class="map-modal-title">Booking Location</div>
        <button class="map-modal-close" onclick="dismissMapModal()"><i class="bi bi-x-lg"></i></button>
      </div>
      <div id="mapPreview"></div>
      <div class="map-modal-info">
        <i class="bi bi-geo-alt-fill" style="color:#E8820C;font-size:18px;flex-shrink:0"></i>
        <div class="map-modal-addr" id="modalAddr">Loading…</div>
        <span class="map-modal-chip" id="modalDist"></span>
      </div>
      <div class="map-modal-actions">
        <button class="btn-map-accept" id="btnModalAccept" onclick="acceptFromModal()"><i class="bi bi-check2-circle"></i> Accept Job</button>
        <button class="btn-map-pass" onclick="dismissMapModal()"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
  </div>
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
        <!-- Header -->
        <div class="p-hdr">
          <div style="position:relative;z-index:1;">
            <div style="display:flex;align-items:center;gap:10px;">
              <div class="p-hdr-ttl">Live Requests</div>
              <div class="live-badge">LIVE</div>
            </div>
            <div class="p-hdr-sub" id="feedSubtitle">Looking for bookings…</div>
          </div>
          <div style="background:rgba(255,255,255,.2);backdrop-filter:blur(8px);border:1.5px solid rgba(255,255,255,.3);border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;z-index:1;" onclick="goPage('provider_home.php')">
            <i class="bi bi-arrow-left" style="color:#1A1A2E;font-size:18px;"></i>
          </div>
        </div>

        <!-- Active job banner -->
        <div class="active-job-banner" id="activeJobBanner" style="display:none;" onclick="goPage('provider_accepted_booking.php')">
          <i class="bi bi-tools"></i>
          <div class="active-job-text">
            <strong>You have an active job</strong>
            <span>Tap to view job details →</span>
          </div>
        </div>

        <!-- Feed tabs -->
        <div class="feed-tabs">
          <div class="feed-tab on" data-tab="live" onclick="switchTab('live',this)">🔴 Live Feed</div>
          <div class="feed-tab" data-tab="mine" onclick="switchTab('mine',this)">My Requests</div>
          <div class="feed-tab" data-tab="accepted" onclick="switchTab('accepted',this)">Accepted</div>
          <div class="feed-tab" data-tab="completed" onclick="switchTab('completed',this)">Completed</div>
        </div>

        <!-- Poll progress bar -->
        <div class="poll-bar" id="pollBar" style="display:none;">
          <div class="poll-bar-fill" id="pollFill"></div>
        </div>

        <div class="hdr-live-row">
          <div class="hdr-count" id="feedCount"></div>
          <div style="font-size:11px;color:#9E9690;" id="lastUpdated"></div>
        </div>

        <!-- Feed list -->
        <div id="feedList">
          <div class="empty-feed">
            <div class="empty-feed-icon">⏳</div>
            <div class="empty-feed-title">Loading…</div>
          </div>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni on" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span class="nl">Calendar</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') initTheme();

    const SVC_ICONS = {
      'cleaner': '🧹', 'helper': '🧑‍🤝‍🧑', 'laundry': '🧺',
      'plumber': '🔧', 'carpenter': '🔨', 'appliance': '🔩'
    };

    function svcIcon(name) {
      const k = String(name || '').toLowerCase();
      for (const [key, icon] of Object.entries(SVC_ICONS)) {
        if (k.includes(key)) return icon;
      }
      return '🏠';
    }

    function timeAgo(dateStr) {
      if (!dateStr) return '';
      const d = new Date(String(dateStr).replace(' ', 'T'));
      const s = Math.round((Date.now() - d.getTime()) / 1000);
      if (s < 60) return 'Just now';
      if (s < 3600) return Math.floor(s / 60) + ' min ago';
      return Math.floor(s / 3600) + 'h ago';
    }

    let currentTab = 'live';
    let pollTimer = null;
    let knownIds = new Set();
    let isAccepting = false;

    function goPage(p) { window.location.href = p; }

    function switchTab(tab, el) {
      currentTab = tab;
      document.querySelectorAll('.feed-tab').forEach(t => t.classList.remove('on'));
      el.classList.add('on');
      knownIds.clear();
      loadFeed(true);
    }

    /* ===== LIVE FEED ===== */
    async function loadFeed(forceReset = false) {
      try {
        let url, data;

        if (currentTab === 'live') {
          const res = await fetch('../api/provider_requests_api.php?action=live_feed&_t=' + Date.now(), { cache: 'no-store' });
          data = await res.json();
        } else {
          const filterMap = { mine: 'all', accepted: 'accepted', completed: 'completed' };
          const res = await fetch('../api/provider_requests_api.php?filter=' + filterMap[currentTab] + '&_t=' + Date.now(), { cache: 'no-store' });
          data = await res.json();
        }

        if (!data.success) return;

        // Show active job banner
        const banner = document.getElementById('activeJobBanner');
        banner.style.display = data.has_active_job ? 'flex' : 'none';

        // Show/hide poll bar
        document.getElementById('pollBar').style.display = currentTab === 'live' ? 'block' : 'none';

        if (currentTab === 'live') {
          renderLiveFeed(data.live_bookings || [], forceReset);
          const count = (data.live_bookings || []).length;
          document.getElementById('feedSubtitle').textContent =
            count > 0 ? `${count} booking${count > 1 ? 's' : ''} waiting for a provider` : 'No live bookings right now';
          document.getElementById('feedCount').textContent = count > 0 ? count + ' live' : '';
        } else {
          renderMyRequests(data.requests || []);
        }

        document.getElementById('lastUpdated').textContent = 'Updated ' + new Date().toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit'});
      } catch (e) {
        console.warn('Feed load error:', e);
      }
    }

    function renderLiveFeed(bookings, forceReset) {
      const el = document.getElementById('feedList');

      if (!bookings.length) {
        el.innerHTML = `
          <div class="empty-feed">
            <div class="empty-feed-icon">📭</div>
            <div class="empty-feed-title">No live bookings</div>
            <div class="empty-feed-sub">New customer requests will appear here instantly.<br>Stay on this screen to grab them first!</div>
          </div>`;
        knownIds.clear();
        return;
      }

      // Detect new cards
      const newIds = new Set(bookings.map(b => b.booking_id));
      const addedIds = new Set([...newIds].filter(id => !knownIds.has(id)));

      if (forceReset || addedIds.size === bookings.length) {
        // Full re-render
        el.innerHTML = bookings.map(b => buildLiveCard(b, false)).join('');
      } else if (addedIds.size > 0) {
        // Prepend new cards only
        const newHtml = [...addedIds].map(id => {
          const b = bookings.find(x => x.booking_id === id);
          return b ? buildLiveCard(b, true) : '';
        }).join('');
        el.insertAdjacentHTML('afterbegin', newHtml);

        // Remove cards no longer in feed
        document.querySelectorAll('.live-card[data-booking-id]').forEach(card => {
          const id = parseInt(card.dataset.bookingId);
          if (!newIds.has(id)) card.remove();
        });
      }

      knownIds = newIds;
    }

    function buildLiveCard(b, isNew) {
      const bid = b.booking_id;
      const reqStatus = String(b.request_status || '').toLowerCase();
      const hasDeclined = reqStatus === 'declined';
      const hasPending = reqStatus === 'pending';
      const icon = svcIcon(b.service);
      const price = '₱' + Number(b.price || 0).toLocaleString('en-PH');
      const ago = timeAgo(b.created_at);
      const addr = (b.address || 'Address not set').substring(0, 50);
      const customer = b.customer_name || 'Homeowner';
      const notes = b.notes ? b.notes.substring(0, 60) : '';

      return `
        <div class="live-card ${isNew ? 'new-flash' : ''}" data-booking-id="${bid}" id="liveCard${bid}">
          <div class="live-card-top">
            <div class="live-card-icon">${icon}</div>
            <div class="live-card-info">
              <div class="live-card-svc">${b.service || 'Service'}</div>
              <div class="live-card-customer"><i class="bi bi-person-fill"></i> ${customer}</div>
              <div class="live-card-addr"><i class="bi bi-geo-alt-fill" style="flex-shrink:0;margin-top:1px;"></i>${addr}</div>
            </div>
            <div class="live-price">${price}</div>
          </div>
          <div class="live-card-meta">
            <span class="live-tag time"><i class="bi bi-clock"></i> ${ago}</span>
            ${notes ? `<span class="live-tag notes"><i class="bi bi-chat-text"></i> ${notes}${notes.length >= 60 ? '…' : ''}</span>` : ''}
            ${hasDeclined ? `<span class="live-tag declined"><i class="bi bi-x-circle"></i> You passed</span>` : ''}
            ${isNew ? `<span class="live-tag new-req">🔥 New</span>` : ''}
          </div>
          ${!hasDeclined ? `
          <div class="live-card-actions">
            <button class="btn-live-accept" id="btnAccept${bid}" onclick="openMapModal(${bid}, '${addr.replace(/'/g,"\\'")}', ${b.customer_lat || 'null'}, ${b.customer_lng || 'null'})">
              <i class="bi bi-map"></i> View Map &amp; Accept
            </button>
            <button class="btn-live-pass" onclick="passBooking(${bid}, this)" title="Pass">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>` : ''}
        </div>`;
    }

    function renderMyRequests(requests) {
      const el = document.getElementById('feedList');
      if (!requests.length) {
        el.innerHTML = `
          <div class="empty-feed">
            <div class="empty-feed-icon">📋</div>
            <div class="empty-feed-title">No requests yet</div>
            <div class="empty-feed-sub">Switch to Live Feed to grab new bookings.</div>
          </div>`;
        return;
      }

      el.innerHTML = requests.map(r => {
        const sClass = String(r.status).toLowerCase() === 'accepted' ? 'accepted' : String(r.status).toLowerCase() === 'declined' ? 'declined' : 'new';
        const isAccepted = String(r.status).toLowerCase() === 'accepted';
        return `
          <div class="req-card">
            <div class="req-top">
              <div class="req-ic">${svcIcon(r.service)}</div>
              <div class="req-info">
                <div class="req-type">${r.service} · <span class="status-pill ${sClass}">${r.status}</span></div>
                <div class="req-name">${r.customer_name || 'Homeowner'}</div>
                <div class="req-meta">📍 ${r.address || '—'}<br>📝 ${r.details || '—'}</div>
              </div>
              <div class="req-price-tag">₱${Number(r.fixed_price || 0).toLocaleString('en-PH')}</div>
            </div>
            ${isAccepted ? `<div class="req-footer"><button class="btn-view" onclick="goPage('provider_accepted_booking.php?booking_id=${r.booking_id}')"><i class="bi bi-eye" style="margin-right:5px;"></i>View details</button></div>` : ''}
          </div>`;
      }).join('');
    }

    /* ===== MAP PREVIEW MODAL ===== */
    let previewMap = null, previewMarker = null, modalBookingId = null;
    let providerGpsLat = null, providerGpsLng = null;

    // Sto. Tomas, Batangas — service area center & bounds
    const ST_CENTER = [14.1053, 121.1390];
    const ST_BOUNDS = L.latLngBounds(L.latLng(13.9800, 120.9800), L.latLng(14.2500, 121.3200));

    function isValidCoord(lat, lng) {
      if (!lat || !lng || isNaN(lat) || isNaN(lng)) return false;
      return ST_BOUNDS.contains(L.latLng(lat, lng));
    }

    async function geocodeAddress(address) {
      // Search across all of Batangas province
      const q = encodeURIComponent((address || '') + ', Batangas, Philippines');
      const res = await fetch(
        `https://nominatim.openstreetmap.org/search?q=${q}&format=json&limit=1` +
        `&viewbox=120.65,13.65,121.55,14.35&bounded=1`,
        { headers: { 'Accept-Language': 'en' } }
      );
      const data = await res.json();
      if (!data || !data[0]) throw new Error('No geocode result for: ' + address);
      return { lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) };
    }

    async function openMapModal(bookingId, address, custLat, custLng) {
      modalBookingId = bookingId;
      document.getElementById('modalAddr').textContent = address || 'Address not available';
      document.getElementById('modalDist').textContent = '⏳';
      document.getElementById('btnModalAccept').disabled = false;
      document.getElementById('btnModalAccept').innerHTML = '<i class="bi bi-check2-circle"></i> Accept Job';
      document.getElementById('mapModal').classList.add('open');

      await new Promise(r => setTimeout(r, 80)); // let modal animate in

      if (!previewMap) {
        previewMap = L.map('mapPreview', {
          zoomControl: false, tap: false,
          minZoom: 10, maxZoom: 19,
          maxBounds: ST_BOUNDS,
          maxBoundsViscosity: 0.85
        }).setView(ST_CENTER, 13);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
          attribution: '© CARTO', maxZoom: 19, subdomains: 'abcd'
        }).addTo(previewMap);
      }
      setTimeout(() => previewMap.invalidateSize(), 350);

      try {
        let lat = parseFloat(custLat);
        let lng = parseFloat(custLng);

        // Validate stored GPS — reject if outside Batangas Province
        if (!isValidCoord(lat, lng)) {
          console.warn(`Stored GPS (${lat},${lng}) invalid — geocoding address instead.`);
          document.getElementById('modalDist').textContent = '📍 Locating…';
          const geo = await geocodeAddress(address);
          lat = geo.lat;
          lng = geo.lng;
        }

        previewMap.setView([lat, lng], 16);
        if (previewMarker) previewMap.removeLayer(previewMarker);
        const icon = L.divIcon({
          className: '',
          html: `<div style="
            width:44px;height:44px;border-radius:50%;
            background:linear-gradient(135deg,#1A1A2E,#2D2D4E);
            border:3px solid #fff;
            box-shadow:0 3px 14px rgba(0,0,0,0.35);
            display:flex;align-items:center;justify-content:center;
            font-size:22px;
          ">🏠</div>`,
          iconSize: [44, 44], iconAnchor: [22, 22]
        });
        previewMarker = L.marker([lat, lng], { icon }).addTo(previewMap);
        previewMarker.bindPopup('<b>🏠 Client Location</b>').openPopup();

        // Distance from provider's real GPS — use Sto. Tomas center if GPS is unavailable or invalid
        const fromLat = (providerGpsLat && isValidCoord(providerGpsLat, providerGpsLng))
                        ? providerGpsLat : ST_CENTER[0];
        const fromLng = (providerGpsLng && isValidCoord(providerGpsLat, providerGpsLng))
                        ? providerGpsLng : ST_CENTER[1];
        const R = 6371;
        const dLat = (lat - fromLat) * Math.PI / 180;
        const dLng = (lng - fromLng) * Math.PI / 180;
        const a = Math.sin(dLat/2)**2 + Math.cos(fromLat*Math.PI/180) * Math.cos(lat*Math.PI/180) * Math.sin(dLng/2)**2;
        const dist = (R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))).toFixed(1);
        document.getElementById('modalDist').textContent = dist + ' km away';

      } catch(e) {
        console.warn('Map locate failed:', e);
        // Fall back to Sto. Tomas center
        previewMap.setView(ST_CENTER, 13);
        document.getElementById('modalDist').textContent = '📍 Address not found';
      }
    }

    function closeMapModal(e) {
      // Allow: direct call (no event), or backdrop click
      if (e && e.target !== document.getElementById('mapModal')) return;
      document.getElementById('mapModal').classList.remove('open');
      modalBookingId = null;
    }
    function dismissMapModal() {
      document.getElementById('mapModal').classList.remove('open');
      modalBookingId = null;
    }

    async function acceptFromModal() {
      if (!modalBookingId) return;
      const btn = document.getElementById('btnModalAccept');
      await acceptBooking(modalBookingId, btn);
    }

    /* ===== ACCEPT / PASS ===== */
    async function acceptBooking(bookingId, btn) {
      if (isAccepting) return;
      isAccepting = true;
      const origHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Accepting…';

      try {
        const fd = new FormData();
        fd.append('action', 'accept_booking');
        fd.append('booking_id', bookingId);
        const res = await fetch('../api/provider_requests_api.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          const card = document.getElementById('liveCard' + bookingId);
          if (card) {
            card.style.transition = 'opacity 0.4s';
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 400);
          }
          setTimeout(() => {
            goPage('provider_accepted_booking.php?booking_id=' + data.booking_id);
          }, 500);
        } else {
          alert(data.message || 'Could not accept. Someone else may have taken it.');
          btn.disabled = false;
          btn.innerHTML = origHtml;
          loadFeed();
        }
      } catch (e) {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = origHtml;
      }
      isAccepting = false;
    }

    async function passBooking(bookingId, btn) {
      // Locally hide card — don't send a decline to server (customer may still get accepted by others)
      const card = document.getElementById('liveCard' + bookingId);
      if (card) {
        card.style.transition = 'opacity 0.3s, transform 0.3s';
        card.style.opacity = '0';
        card.style.transform = 'translateX(60px)';
        setTimeout(() => {
          card.remove();
          knownIds.delete(bookingId);
        }, 300);
      }
    }

    /* ===== PROVIDER GPS TRACKING ===== */
    function startProviderTracking() {
      if (!navigator.geolocation) return;
      navigator.geolocation.watchPosition(
        (pos) => {
          const lat = pos.coords.latitude;
          const lng = pos.coords.longitude;
          const acc = pos.coords.accuracy;
          // Only store if accurate enough and within Batangas Province
          if (acc < 500 && ST_BOUNDS.contains(L.latLng(lat, lng))) {
            providerGpsLat = lat;
            providerGpsLng = lng;
          }
        },
        () => {},
        { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 }
      );
    }

    /* ===== POLLING ===== */
    function startPolling() {
      loadFeed(true);
      pollTimer = setInterval(() => loadFeed(false), 5000);
    }

    document.addEventListener('DOMContentLoaded', () => {
      startProviderTracking();
      startPolling();
    });
  </script>
</body>
</html>
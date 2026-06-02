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
  <title>HomeEase – Incoming Requests</title>
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
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px;
      padding: 10px 16px 14px;
      margin-top: 2px;
    }

    .feed-tab {
      width: 100%;
      padding: 7px 10px;
      border-radius: 99px;
      border: 1.5px solid #E8E0D5; background: #fff;
      font-size: 11px; font-weight: 700; color: #7A7064;
      cursor: pointer; transition: all 0.2s; font-family: 'Poppins',sans-serif;
      text-align: center;
      white-space: nowrap;
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
      margin: 0 16px;
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

    #feedList {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }


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
    .map-modal-card{width:100%;max-height:92%;background:#fff;border-radius:24px 24px 0 0;transform:translateY(100%);transition:transform .32s cubic-bezier(.32,.72,0,1);display:flex;flex-direction:column;overflow:hidden}
    .map-modal-overlay.open .map-modal-card{transform:translateY(0)}
    .map-modal-handle{width:40px;height:4px;background:#E0D8D0;border-radius:2px;margin:12px auto 0}
    .map-modal-hdr{display:flex;align-items:center;justify-content:space-between;padding:12px 16px 8px}
    .map-modal-title{font-size:15px;font-weight:800;color:#1A1A2E;font-family:'Poppins',sans-serif}
    .map-modal-close{width:32px;height:32px;border-radius:50%;background:#F5F0EA;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:15px;color:#7A7064}
    .map-modal-content{padding:0 0 10px;overflow-y:auto;flex:1}
    #mapPreview{height:200px;width:100%;display:block}
    .btn-map-accept:disabled{opacity:.6;cursor:not-allowed}
    .map-modal-info{padding:10px 16px 12px;display:flex;align-items:flex-start;gap:10px}
    .map-modal-addr{flex:1;font-size:12px;color:#5E564D;font-weight:600;line-height:1.4}
    .map-modal-details{margin:0 16px 10px;background:#FFFDF9;border:1.5px solid #EFE3D4;border-radius:16px;padding:12px 12px 4px}
    .map-modal-details-title{font-size:12px;font-weight:800;color:#1A1A2E;font-family:'Poppins',sans-serif;margin-bottom:10px;display:flex;align-items:center;gap:6px}
    .map-modal-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 10px}
    .map-modal-item{background:#fff;border:1px solid #EFE3D4;border-radius:12px;padding:8px 10px}
    .map-modal-item.full{grid-column:1/-1}
    .map-item-label{font-size:10px;font-weight:800;color:#9A8F83;text-transform:uppercase;letter-spacing:.35px;margin-bottom:4px;font-family:'Poppins',sans-serif}
    .map-item-value{font-size:12px;font-weight:700;color:#2F2A24;line-height:1.35;word-break:break-word}
    .map-item-value.price{font-size:15px;color:#C2410C;font-family:'Poppins',sans-serif;font-weight:800}
    .map-item-value.chip{display:inline-flex;align-items:center;padding:4px 10px;border-radius:99px;background:#FFF7E8;color:#B45309;font-family:'Poppins',sans-serif;font-size:11px;font-weight:800}
    .map-modal-actions{display:flex;gap:10px;padding:10px 16px 16px;border-top:1px solid #F0E8DC;background:#fff;position:sticky;bottom:0;z-index:2}
    .btn-map-accept{flex:1;height:46px;border-radius:13px;background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;border:none;cursor:pointer;font-size:14px;font-weight:800;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:6px;box-shadow:0 4px 14px rgba(232,130,12,.3)}
    .btn-map-pass{width:46px;height:46px;border-radius:13px;border:1.5px solid #E8E0D5;background:#fff;color:#7A7064;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center}
    .map-modal-chip{display:inline-flex;align-items:center;gap:4px;background:#FEF3C7;color:#92400E;padding:4px 10px;border-radius:99px;font-size:10px;font-weight:800;font-family:'Poppins',sans-serif}

    @media (max-width: 420px) {
      .map-modal-grid{grid-template-columns:1fr}
    }
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
      <div class="map-modal-content">
        <div id="mapPreview"></div>
        <div class="map-modal-info">
          <i class="bi bi-geo-alt-fill" style="color:#E8820C;font-size:18px;flex-shrink:0"></i>
          <div class="map-modal-addr" id="modalAddr">Loading…</div>
          <span class="map-modal-chip" id="modalDist"></span>
        </div>

        <div class="map-modal-details">
          <div class="map-modal-details-title"><i class="bi bi-card-checklist"></i> Booking Details</div>
          <div class="map-modal-grid">
            <div class="map-modal-item">
              <div class="map-item-label">Client/Homeowner</div>
              <div class="map-item-value" id="modalClientName">—</div>
            </div>
            <div class="map-modal-item">
              <div class="map-item-label">Contact Number</div>
              <div class="map-item-value" id="modalClientPhone">—</div>
            </div>
            <div class="map-modal-item full">
              <div class="map-item-label">Full Address/Location</div>
              <div class="map-item-value" id="modalFullAddress">—</div>
            </div>
            <div class="map-modal-item">
              <div class="map-item-label">Service Type</div>
              <div class="map-item-value" id="modalServiceType">—</div>
            </div>
            <div class="map-modal-item">
              <div class="map-item-label">Date & Time</div>
              <div class="map-item-value" id="modalDateTime">—</div>
            </div>
            <div class="map-modal-item">
              <div class="map-item-label">Payment Method</div>
              <div class="map-item-value chip" id="modalPaymentMethod">Cash</div>
            </div>
            <div class="map-modal-item">
              <div class="map-item-label">Total Price</div>
              <div class="map-item-value price" id="modalTotalPrice">₱0</div>
            </div>
            <div class="map-modal-item full">
              <div class="map-item-label">Selected Options/Tasks</div>
              <div class="map-item-value" id="modalOptions">Not specified.</div>
            </div>
            <div class="map-modal-item full">
              <div class="map-item-label">Additional Notes</div>
              <div class="map-item-value" id="modalNotes">No additional notes.</div>
            </div>
          </div>
        </div>
      </div>
      <div class="map-modal-actions">
        <button class="btn-map-accept" id="btnModalAccept" onclick="acceptFromModal()"><i class="bi bi-check2-circle"></i> Accept Job</button>
        <button class="btn-map-pass" onclick="dismissMapModal()"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
  </div>

  <!-- QR Upload Modal -->
  <div class="map-modal-overlay" id="qrUploadModal" onclick="closeQrModal(event)">
    <div class="map-modal-card" onclick="event.stopPropagation()">
      <div class="map-modal-handle"></div>
      <div class="map-modal-hdr">
        <div class="map-modal-title">Upload QR Code</div>
        <button class="map-modal-close" onclick="closeQrModal()"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="map-modal-content" style="padding: 16px;">
        <p style="font-size: 13px; color: #5E564D; margin-bottom: 12px;" id="qrUploadMsg">This booking requires online payment. Please upload your QR code so the client can pay you.</p>
        <form id="qrUploadForm" onsubmit="submitQrAndAccept(event)">
          <div style="background: #FAFAF8; border: 1.5px dashed #E8E0D5; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 16px;">
            <label for="qrFileInput" style="display: block; cursor: pointer;">
              <i class="bi bi-cloud-arrow-up" style="font-size: 32px; color: #E8820C;"></i>
              <div style="font-size: 14px; font-weight: 700; color: #1A1A2E; margin-top: 8px;">Tap to select QR image</div>
              <div id="qrFileName" style="font-size: 11px; color: #7A7064; margin-top: 4px;">No file selected</div>
            </label>
            <input type="file" id="qrFileInput" name="qr_file" accept="image/*" style="display: none;" required onchange="document.getElementById('qrFileName').textContent = this.files[0] ? this.files[0].name : 'No file selected';">
          </div>
          <button type="submit" class="btn-map-accept" style="width: 100%;">Upload & Accept Job</button>
        </form>
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
              <div class="p-hdr-ttl">Incoming Requests</div>
              <div class="live-badge">LIVE</div>
            </div>
            <div class="p-hdr-sub" id="feedSubtitle">Looking for bookings…</div>
          </div>
          <div style="background:rgba(255,255,255,.2);backdrop-filter:blur(8px);border:1.5px solid rgba(255,255,255,.3);border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;z-index:1;" onclick="goPage('provider_home.php')">
            <i class="bi bi-arrow-left" style="color:#1A1A2E;font-size:18px;"></i>
          </div>
        </div>


        <!-- Feed tabs -->
        <div class="feed-tabs">
          <div class="feed-tab on" data-tab="live" onclick="switchTab('live',this)">🔴 Live Feed</div>
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
        <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span class="nl">Earnings</span></div>
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
    let liveBookingLookup = new Map();
    let requestedBookingId = null; // booking_id from URL to auto-open

    function tryOpenRequestedBooking() {
      if (!requestedBookingId) return;
      const id = parseInt(requestedBookingId, 10);
      if (!id) return;

      // First try: find live accept button (opens map modal)
      const btn = document.getElementById('btnAccept' + id);
      if (btn) {
        btn.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Click after small delay so rendering/scroll settles
        setTimeout(() => btn.click(), 220);
        requestedBookingId = null;
        return;
      }

      // Second try: find live card and highlight it
      const card = document.getElementById('liveCard' + id);
      if (card) {
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // transient highlight
        const orig = card.style.boxShadow;
        card.style.boxShadow = '0 6px 30px rgba(232,130,12,0.25)';
        setTimeout(() => card.style.boxShadow = orig, 1800);
        requestedBookingId = null;
        return;
      }

      // For requests in "my requests" view, attempt to find req-card by data attribute
      const reqCard = document.querySelector(`.req-card[data-booking-id="${id}"]`);
      if (reqCard) {
        reqCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
        requestedBookingId = null;
        return;
      }
    }

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
          const filterMap = { completed: 'completed' };
          const res = await fetch('../api/provider_requests_api.php?filter=' + filterMap[currentTab] + '&_t=' + Date.now(), { cache: 'no-store' });
          data = await res.json();
        }

        if (!data.success) return;


        // Show/hide poll bar
        document.getElementById('pollBar').style.display = currentTab === 'live' ? 'block' : 'none';

        if (currentTab === 'live') {
          if (data.is_online === false) {
            document.getElementById('feedSubtitle').textContent = 'You are Offline';
            document.getElementById('feedCount').textContent = '';
            document.getElementById('feedList').innerHTML = `
              <div class="empty-feed">
                <div class="empty-feed-icon" style="color: #6b7280;">💤</div>
                <div class="empty-feed-title">You are Offline</div>
                <div class="empty-feed-sub">Switch your status to <strong>Online</strong> on the <a href="provider_home.php" style="color: #E8820C; text-decoration: underline; font-weight: 700;">Home page</a> to start receiving and accepting live booking requests.</div>
              </div>`;
            knownIds.clear();
            return;
          }

          renderLiveFeed(data.live_bookings || [], forceReset);
          const count = (data.live_bookings || []).length;
          document.getElementById('feedSubtitle').textContent =
            count > 0 ? `${count} booking${count > 1 ? 's' : ''} waiting for a provider` : 'No live bookings right now';
          document.getElementById('feedCount').textContent = count > 0 ? count + ' live' : '';

          // If a booking_id was provided in the URL, try to open it now
          tryOpenRequestedBooking();
        } else {
          renderMyRequests(data.requests || []);

          // Also attempt in case the booking is in "my requests"
          tryOpenRequestedBooking();
        }

        document.getElementById('lastUpdated').textContent = 'Updated ' + new Date().toLocaleTimeString('en-US', {hour:'numeric', minute:'2-digit'});
      } catch (e) {
        console.warn('Feed load error:', e);
      }
    }

    function renderLiveFeed(bookings, forceReset) {
      const el = document.getElementById('feedList');
      liveBookingLookup = new Map(bookings.map(b => [Number(b.booking_id), b]));

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
            <button class="btn-live-accept" id="btnAccept${bid}" onclick="openMapModal(${bid})">
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
            <div class="empty-feed-icon">✅</div>
            <div class="empty-feed-title">No completed requests yet</div>
            <div class="empty-feed-sub">Completed bookings will appear here once jobs are finished.</div>
          </div>`;
        return;
      }

      el.innerHTML = requests.map(r => {
        const rawStatus = currentTab === 'completed'
          ? String(r.booking_status || r.status || 'completed').toLowerCase()
          : String(r.status || '').toLowerCase();
        const sClass = rawStatus === 'accepted' ? 'accepted' : rawStatus === 'declined' ? 'declined' : 'completed';
        const isCompleted = currentTab === 'completed';
        return `
          <div class="req-card">
            <div class="req-top">
              <div class="req-ic">${svcIcon(r.service)}</div>
              <div class="req-info">
                <div class="req-type">${r.service} · <span class="status-pill ${sClass}">Completed</span></div>
                <div class="req-name">${r.customer_name || 'Homeowner'}</div>
                <div class="req-meta">📍 ${r.address || '—'}<br>📝 ${r.details || '—'}</div>
              </div>
              <div class="req-price-tag">₱${Number(r.fixed_price || 0).toLocaleString('en-PH')}</div>
            </div>
            ${isCompleted ? `<div class="req-footer"><button class="btn-view" onclick="goPage('provider_accepted_booking.php?booking_id=${r.booking_id}')"><i class="bi bi-eye" style="margin-right:5px;"></i>View details</button></div>` : ''}
          </div>`;
      }).join('');
    }

    /* ===== MAP PREVIEW MODAL ===== */
    let previewMap = null, previewMarker = null, modalBookingId = null;
    let providerGpsLat = null, providerGpsLng = null;

    // Batangas Province — service area center & bounds (excludes Cavite)
    const ST_CENTER = [13.7565, 121.0583];
    const ST_BOUNDS = L.latLngBounds(L.latLng(13.30, 120.55), L.latLng(14.20, 121.55));

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

    function setText(id, value, fallback = '—') {
      const el = document.getElementById(id);
      if (!el) return;
      const txt = String(value || '').trim();
      el.textContent = txt !== '' ? txt : fallback;
    }

    function formatPaymentMethod(method) {
      const m = String(method || 'cash').toLowerCase();
      if (m === 'gcash') return 'GCash';
      if (m === 'bank') return 'Bank Transfer';
      return 'Cash';
    }

    function formatDateTime(date, timeSlot) {
      const d = String(date || '').trim();
      const t = String(timeSlot || '').trim();
      if (d && t) return `${d} • ${t}`;
      if (d) return d;
      if (t) return t;
      return 'Not specified';
    }

    function parseDetailText(rawText) {
      const raw = String(rawText || '').replace(/\r/g, '').trim();
      if (!raw) {
        return {
          selectedOptions: 'Not specified.',
          notes: 'No additional notes.'
        };
      }

      const markerMatch = raw.match(/Selected Options:\s*/i);
      if (!markerMatch) {
        return {
          selectedOptions: 'Not specified.',
          notes: raw
        };
      }

      const markerIndex = markerMatch.index || 0;
      const before = raw.slice(0, markerIndex).trim();
      const after = raw.slice(markerIndex + markerMatch[0].length).trim();

      const options = after
        .split('\n')
        .map(line => line.replace(/^[-•]\s*/, '').trim())
        .filter(Boolean)
        .join(' • ');

      return {
        selectedOptions: options || 'Not specified.',
        notes: before || 'No additional notes.'
      };
    }

    function fillMapModalDetails(booking) {
      const fullAddress = booking.customer_address || booking.request_address || booking.address || '';
      const detailsText = booking.details || booking.notes || '';
      const parsed = parseDetailText(detailsText);

      setText('modalClientName', booking.customer_name, 'Homeowner');
      setText('modalClientPhone', booking.customer_phone, 'Not provided');
      setText('modalFullAddress', fullAddress, 'Address not available');
      setText('modalServiceType', booking.service, 'Service');
      setText('modalDateTime', formatDateTime(booking.date, booking.time_slot), 'Not specified');
      setText('modalPaymentMethod', formatPaymentMethod(booking.payment_method), 'Cash');
      setText('modalTotalPrice', '₱' + Number(booking.fixed_price || booking.price || 0).toLocaleString('en-PH'), '₱0');
      setText('modalOptions', parsed.selectedOptions, 'Not specified.');
      setText('modalNotes', parsed.notes, 'No additional notes.');
      setText('modalAddr', fullAddress, 'Address not available');
    }

    async function openMapModal(bookingId) {
      const booking = liveBookingLookup.get(Number(bookingId));
      if (!booking) {
        alert('Booking details not available. Please refresh live feed.');
        return;
      }

      modalBookingId = bookingId;
      fillMapModalDetails(booking);
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
          maxBoundsViscosity: 1.0
        });
        previewMap.fitBounds(ST_BOUNDS);
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
          attribution: '© CARTO', maxZoom: 19, subdomains: 'abcd'
        }).addTo(previewMap);
      }
      setTimeout(() => previewMap.invalidateSize(), 350);

      try {
        const fullAddress = booking.customer_address || booking.request_address || booking.address || '';
        let lat = parseFloat(booking.customer_lat);
        let lng = parseFloat(booking.customer_lng);

        // Validate stored GPS — reject if outside Batangas Province
        if (!isValidCoord(lat, lng)) {
          console.warn(`Stored GPS (${lat},${lng}) invalid — geocoding address instead.`);
          document.getElementById('modalDist').textContent = '📍 Locating…';
          const geo = await geocodeAddress(fullAddress);
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
        previewMap.fitBounds(ST_BOUNDS);
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

    function closeQrModal(e) {
      if (e && e.target !== document.getElementById('qrUploadModal')) return;
      document.getElementById('qrUploadModal').classList.remove('open');
    }

    async function submitQrAndAccept(e) {
      e.preventDefault();
      const fileInput = document.getElementById('qrFileInput');
      if (!fileInput.files.length) return alert('Please select a QR image.');
      
      const btn = document.querySelector('#qrUploadForm button');
      btn.disabled = true;
      btn.innerHTML = 'Accepting...';
      
      await acceptBooking(modalBookingId, document.getElementById('btnModalAccept'), fileInput.files[0]);
      
      btn.disabled = false;
      btn.innerHTML = 'Upload & Accept Job';
      closeQrModal();
    }

    async function acceptFromModal() {
      if (!modalBookingId) return;
      const booking = liveBookingLookup.get(Number(modalBookingId));
      const method = String(booking.payment_method || 'cash').toLowerCase();

      if (method === 'gcash' || method === 'bank') {
          const mText = method === 'gcash' ? 'GCash' : 'Bank Transfer';
          document.getElementById('qrUploadMsg').textContent = `This booking requires ${mText} payment. Please upload your QR code before accepting.`;
          document.getElementById('qrUploadModal').classList.add('open');
          return;
      }
      
      const btn = document.getElementById('btnModalAccept');
      await acceptBooking(modalBookingId, btn);
    }

    /* ===== ACCEPT / PASS ===== */
    async function acceptBooking(bookingId, btn, qrFile = null) {
      if (isAccepting) return;
      isAccepting = true;
      const origHtml = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Accepting…';

      try {
        const fd = new FormData();
        fd.append('action', 'accept_booking');
        fd.append('booking_id', bookingId);
        if (qrFile) fd.append('qr_file', qrFile);

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

      // Parse booking_id from URL so we can auto-open details after feed loads
      const params = new URLSearchParams(window.location.search);
      const bid = params.get('booking_id');
      if (bid && /^\d+$/.test(bid)) requestedBookingId = bid;

      startPolling();
    });
  </script>
</body>
</html>
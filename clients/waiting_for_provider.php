<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}

$bookingId = (int)($_GET['booking_id'] ?? 0);
if ($bookingId <= 0) {
  header('Location: booking_history.php');
  exit;
}

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Finding Your Provider</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Leaflet Map -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/waiting_for_provider.css">
  <style>
    /* ===== MAP STYLE PICKER ===== */
    .wfp-layers-btn {
      position: absolute;
      bottom: calc(var(--sheet-h, 260px) + 70px);
      right: 16px;
      z-index: 400;
      width: 44px; height: 44px;
      border-radius: 50%;
      background: rgba(255,255,255,0.97);
      border: none;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; color: #E8820C;
      box-shadow: 0 2px 14px rgba(0,0,0,0.18);
      cursor: pointer;
      transition: transform 0.15s;
    }
    .wfp-layers-btn:active { transform: scale(0.9); }

    .wfp-style-overlay {
      position: absolute;
      inset: 0; z-index: 700;
      background: rgba(0,0,0,0.45);
      backdrop-filter: blur(4px);
      display: flex; align-items: flex-end;
      opacity: 0; pointer-events: none;
      transition: opacity 0.28s;
    }
    .wfp-style-overlay.open {
      opacity: 1; pointer-events: all;
    }

    .wfp-style-drawer {
      width: 100%;
      background: #fff;
      border-radius: 24px 24px 0 0;
      padding: 18px 18px 36px;
      transform: translateY(100%);
      transition: transform 0.32s cubic-bezier(0.32,0.72,0,1);
    }
    .wfp-style-overlay.open .wfp-style-drawer {
      transform: translateY(0);
    }

    .wfp-style-handle {
      width: 40px; height: 4px;
      background: #E0E0E0; border-radius: 2px;
      margin: 0 auto 16px;
    }

    .wfp-style-title {
      font-size: 16px; font-weight: 800;
      color: #1A1A2E;
      font-family: 'Poppins', sans-serif;
      margin-bottom: 4px;
    }
    .wfp-style-sub {
      font-size: 12px; color: #7A7064;
      font-weight: 600; margin-bottom: 18px;
    }

    .wfp-style-grid {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 10px;
    }

    .wfp-style-opt {
      display: flex; flex-direction: column;
      align-items: center; gap: 6px;
      cursor: pointer;
    }

    .wfp-style-thumb {
      width: 100%; aspect-ratio: 1;
      border-radius: 14px;
      border: 2.5px solid transparent;
      overflow: hidden;
      position: relative;
      transition: border-color 0.2s, transform 0.15s;
      box-shadow: 0 2px 8px rgba(0,0,0,0.12);
    }
    .wfp-style-opt.active .wfp-style-thumb {
      border-color: #E8820C;
      box-shadow: 0 0 0 3px rgba(232,130,12,0.25);
    }
    .wfp-style-opt:active .wfp-style-thumb { transform: scale(0.94); }

    .wfp-style-thumb-img {
      width: 100%; height: 100%;
      object-fit: cover;
      display: block;
    }

    /* Fallback color blocks for thumbnails */
    .thumb-standard { background: linear-gradient(135deg,#e8f4e8,#b8d4b8,#88c088,#98b8e0,#d4e8f0); }
    .thumb-google   { background: linear-gradient(135deg,#f5f0eb,#e8e0d5,#d4c8b8,#c8d8e8,#a8c4d8); }
    .thumb-dark     { background: linear-gradient(135deg,#1a1a2e,#16213e,#0f3460,#222244,#1a1a2e); }
    .thumb-minimal  { background: linear-gradient(135deg,#fafafa,#f0f0f0,#e8e8e8,#f5f5f5,#ffffff); }

    /* Pseudo-map lines on thumbs */
    .wfp-style-thumb::after {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.3) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.3) 1px, transparent 1px);
      background-size: 14px 14px;
      pointer-events: none;
    }
    .thumb-dark::after {
      background-image:
        linear-gradient(rgba(255,255,255,0.08) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.08) 1px, transparent 1px);
    }

    .wfp-style-label {
      font-size: 10px; font-weight: 800;
      color: #5E564D; text-align: center;
      font-family: 'Poppins', sans-serif;
      line-height: 1.2;
    }
    .wfp-style-opt.active .wfp-style-label { color: #E8820C; }

    .wfp-style-check {
      position: absolute;
      top: 5px; right: 5px;
      width: 18px; height: 18px;
      border-radius: 50%;
      background: #E8820C;
      display: none;
      align-items: center; justify-content: center;
      font-size: 10px; color: #fff;
    }
    .wfp-style-opt.active .wfp-style-check { display: flex; }
  </style>
</head>
<body>
  <div class="wfp-shell">

    <!-- Top Bar -->
    <div class="wfp-topbar">
      <button class="wfp-topbar-btn" onclick="goBack()" aria-label="Back">
        <i class="bi bi-arrow-left"></i>
      </button>
      <div class="wfp-topbar-title" id="topBarTitle">HomeEase</div>
      <button class="wfp-topbar-btn" onclick="shareBooking()" aria-label="Share" style="font-size:16px;">
        <i class="bi bi-three-dots-vertical"></i>
      </button>
    </div>

    <!-- Map -->
    <div id="wfpMap"></div>

    <!-- Recenter button -->
    <button class="wfp-recenter" id="btnRecenter" onclick="recenterMap()" aria-label="Recenter">
      <i class="bi bi-crosshair2"></i>
    </button>

    <!-- Map Layers button -->
    <button class="wfp-layers-btn" id="btnLayers" onclick="openStylePicker()" aria-label="Map Style">
      <i class="bi bi-layers-fill"></i>
    </button>

    <!-- Style Picker Overlay -->
    <div class="wfp-style-overlay" id="styleOverlay" onclick="closeStylePicker(event)">
      <div class="wfp-style-drawer" onclick="event.stopPropagation()">
        <div class="wfp-style-handle"></div>
        <div class="wfp-style-title">Map Style</div>
        <div class="wfp-style-sub">Choose your preferred map look</div>
        <div class="wfp-style-grid" id="styleGrid">

          <div class="wfp-style-opt active" id="styleOpt-standard" onclick="setMapStyle('standard')">
            <div class="wfp-style-thumb thumb-standard">
              <div class="wfp-style-check"><i class="bi bi-check"></i></div>
            </div>
            <div class="wfp-style-label">Standard</div>
          </div>

          <div class="wfp-style-opt" id="styleOpt-google" onclick="setMapStyle('google')">
            <div class="wfp-style-thumb thumb-google">
              <div class="wfp-style-check"><i class="bi bi-check"></i></div>
            </div>
            <div class="wfp-style-label">Google</div>
          </div>

          <div class="wfp-style-opt" id="styleOpt-dark" onclick="setMapStyle('dark')">
            <div class="wfp-style-thumb thumb-dark">
              <div class="wfp-style-check"><i class="bi bi-check"></i></div>
            </div>
            <div class="wfp-style-label">Dark</div>
          </div>

          <div class="wfp-style-opt" id="styleOpt-minimal" onclick="setMapStyle('minimal')">
            <div class="wfp-style-thumb thumb-minimal">
              <div class="wfp-style-check"><i class="bi bi-check"></i></div>
            </div>
            <div class="wfp-style-label">Minimal</div>
          </div>

        </div>
      </div>
    </div>

    <!-- Bottom Sheet -->
    <div class="wfp-sheet" id="wfpSheet">
      <div class="wfp-sheet-handle"></div>
      <div class="wfp-sheet-body" id="sheetBody">

        <!-- Status Banner -->
        <div class="wfp-status-banner" id="statusBanner">
          <div class="wfp-search-ring" id="statusSpinner"></div>
          <div class="wfp-status-text" id="statusText">
            Looking for a provider<span class="wfp-dots"><span>.</span><span>.</span><span>.</span></span>
          </div>
        </div>

        <!-- Provider Card (hidden until accepted) -->
        <div class="wfp-provider-card" id="providerCard" style="display:none;">
          <div class="wfp-prov-av" id="provAvatar">?</div>
          <div class="wfp-prov-info">
            <div class="wfp-prov-name" id="provName">–</div>
            <div class="wfp-prov-meta" id="provMeta">–</div>
            <div class="wfp-prov-rating" id="provRating"></div>
          </div>
          <div class="wfp-prov-actions">
            <a class="wfp-prov-btn outline" id="btnChat" href="#" onclick="contactProvider(event,'chat')">
              <i class="bi bi-chat-fill"></i>
            </a>
            <a class="wfp-prov-btn" id="btnCall" href="#" onclick="contactProvider(event,'call')">
              <i class="bi bi-telephone-fill"></i>
            </a>
          </div>
        </div>

        <!-- Tips while waiting -->
        <div class="wfp-tips" id="tipsSection">
          <div class="wfp-tips-title">While you wait 👀</div>
          <div class="wfp-tips-scroll">
            <div class="wfp-tip-card">
              <div class="wfp-tip-icon">🏠</div>
              <div class="wfp-tip-text">Prepare your space before the provider arrives</div>
            </div>
            <div class="wfp-tip-card">
              <div class="wfp-tip-icon">🔒</div>
              <div class="wfp-tip-text">All providers are verified &amp; background-checked</div>
            </div>
            <div class="wfp-tip-card">
              <div class="wfp-tip-icon">💳</div>
              <div class="wfp-tip-text">Payment is system-fixed — no surprises</div>
            </div>
            <div class="wfp-tip-card">
              <div class="wfp-tip-icon">⭐</div>
              <div class="wfp-tip-text">After service, rate your provider to help the community</div>
            </div>
          </div>
        </div>

        <!-- Cancel button (only when pending) -->
        <div class="wfp-cancel-wrap" id="cancelWrap">
          <button class="wfp-cancel-btn" id="btnCancel" onclick="cancelBooking()">
            <i class="bi bi-x-circle-fill"></i> Cancel Booking
          </button>
        </div>

      </div>

      <!-- Price Bar -->
      <div class="wfp-price-bar">
        <div class="wfp-price-svc">
          <div class="wfp-price-svc-icon" id="svcIcon">🏠</div>
          <div>
            <div class="wfp-price-svc-label" id="svcLabel">Service</div>
            <div class="wfp-price-svc-sub" id="svcSub">Fixed price</div>
          </div>
        </div>
        <div class="wfp-price-amount" id="priceAmount">₱–</div>
      </div>
    </div>

  </div><!-- /.wfp-shell -->

  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') initTheme();

    /* =============================================
       CONFIG
    ============================================= */
    const BOOKING_ID = <?= $bookingId ?>;
    const POLL_INTERVAL_MS = 5000; // 5 seconds
    const STATUS_API = '../api/booking_status_api.php?booking_id=' + BOOKING_ID;
    const CANCEL_API = '../api/bookings_api.php';

    /* Service emoji map */
    const SVC_ICONS = {
      'Cleaner': '🧹', 'Helper': '🧑‍🤝‍🧑', 'Laundry Worker': '🧺',
      'Plumber': '🔧', 'Carpenter': '🔨', 'Appliance Technician': '🔩'
    };

    /* =============================================
       STATE
    ============================================= */
    /* ===== MAP STYLES ===== */
    const MAP_STYLES = {
      standard: {
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
        subdomains: 'abc'
      },
      google: {
        // CartoDB Voyager — closest free alternative to Google Maps vector style
        url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19,
        subdomains: 'abcd'
      },
      dark: {
        url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19,
        subdomains: 'abcd'
      },
      minimal: {
        url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
        attribution: '&copy; OpenStreetMap &copy; CARTO',
        maxZoom: 19,
        subdomains: 'abcd'
      }
    };

    let currentStyleKey = localStorage.getItem('wfpMapStyle') || 'standard';
    let tileLayer = null;

    let map, customerMarker, providerMarker, routeLine;
    let pollTimer = null;
    let currentStatus = 'pending';
    let currentData = null;
    let providerPhone = '';
    let animFrame = null;

    // Map center — Philippines default, updated on first load
    let customerLat = 14.5995;
    let customerLng = 120.9842;

    /* =============================================
       MAP INIT
    ============================================= */
    function initMap() {
      map = L.map('wfpMap', {
        zoomControl: false,
        attributionControl: true,
        tap: false
      }).setView([customerLat, customerLng], 15);

      applyMapStyle(currentStyleKey, false);
      highlightStyleOpt(currentStyleKey);

      // Customer marker (orange house)
      const custIcon = L.divIcon({
        className: '',
        html: `<div class="wfp-marker-customer">🏠</div>`,
        iconSize: [30, 30],
        iconAnchor: [15, 15]
      });
      customerMarker = L.marker([customerLat, customerLng], { icon: custIcon }).addTo(map);
      customerMarker.bindPopup('<b>Your Location</b>').openPopup();

      // Sync sheet height for recenter button positioning
      syncSheetHeight();
    }

    function syncSheetHeight() {
      const sheet = document.getElementById('wfpSheet');
      if (sheet) {
        const h = sheet.getBoundingClientRect().height;
        document.documentElement.style.setProperty('--sheet-h', h + 'px');
      }
    }

    /* Provider marker */
    function ensureProviderMarker(lat, lng, isAccepted) {
      const emoji = isAccepted ? '🔧' : '🔍';
      const provIcon = L.divIcon({
        className: '',
        html: `<div class="wfp-marker-provider">${emoji}</div>`,
        iconSize: [34, 34],
        iconAnchor: [17, 17]
      });

      if (!providerMarker) {
        providerMarker = L.marker([lat, lng], { icon: provIcon }).addTo(map);
      } else {
        animateMarker(providerMarker.getLatLng(), { lat, lng });
        providerMarker.setIcon(provIcon);
      }
    }

    /* Smooth marker movement */
    function animateMarker(from, to, duration = 1500) {
      if (animFrame) cancelAnimationFrame(animFrame);
      const start = performance.now();
      function step(ts) {
        const t = Math.min((ts - start) / duration, 1);
        const ease = t < 0.5 ? 2*t*t : -1+(4-2*t)*t; // easeInOut
        const lat = from.lat + (to.lat - from.lat) * ease;
        const lng = from.lng + (to.lng - from.lng) * ease;
        providerMarker.setLatLng([lat, lng]);
        updateRoute([lat, lng]);
        if (t < 1) animFrame = requestAnimationFrame(step);
      }
      animFrame = requestAnimationFrame(step);
    }

    /* Draw/update route line */
    function updateRoute(provLatLng) {
      const points = [provLatLng, [customerLat, customerLng]];
      if (!routeLine) {
        routeLine = L.polyline(points, {
          color: '#E8820C',
          weight: 4,
          opacity: 0.7,
          dashArray: '10, 8',
          lineCap: 'round'
        }).addTo(map);
      } else {
        routeLine.setLatLngs(points);
      }
    }

    /* Fit map to show both markers */
    function fitMap(provLat, provLng) {
      const bounds = L.latLngBounds(
        [customerLat, customerLng],
        [provLat, provLng]
      );
      map.fitBounds(bounds, { padding: [80, 80] });
    }

    function recenterMap() {
      if (providerMarker) {
        fitMap(providerMarker.getLatLng().lat, providerMarker.getLatLng().lng);
      } else {
        map.setView([customerLat, customerLng], 15, { animate: true });
      }
    }

    /* =============================================
       POLL & UI UPDATE
    ============================================= */
    async function pollStatus() {
      try {
        const res = await fetch(STATUS_API + '&_t=' + Date.now(), { cache: 'no-store' });
        const data = await res.json();

        if (!data.success) {
          console.warn('Status API returned error:', data.message);
          return;
        }

        currentData = data;
        renderUI(data);

        // Update map
        customerLat = parseFloat(data.customer_lat) || customerLat;
        customerLng = parseFloat(data.customer_lng) || customerLng;
        customerMarker.setLatLng([customerLat, customerLng]);

        if (data.provider_lat && data.provider_lng) {
          const pLat = parseFloat(data.provider_lat);
          const pLng = parseFloat(data.provider_lng);
          ensureProviderMarker(pLat, pLng, data.status === 'accepted');

          // Only auto-fit once when provider first appears
          if (data.status !== currentStatus || !routeLine) {
            fitMap(pLat, pLng);
          }
          updateRoute([pLat, pLng]);
        }

        currentStatus = data.status;

        // Stop polling if terminal state
        if (['completed', 'cancelled'].includes(data.status)) {
          stopPolling();
        }
      } catch (e) {
        console.warn('Poll failed:', e);
      }
    }

    function renderUI(data) {
      // Service info
      const svcIcon = SVC_ICONS[data.service] || '🏠';
      document.getElementById('svcIcon').textContent = svcIcon;
      document.getElementById('svcLabel').textContent = data.service || 'Service';
      document.getElementById('svcSub').textContent = data.time_slot
        ? (formatDate(data.date) + ' · ' + data.time_slot)
        : formatDate(data.date);
      document.getElementById('priceAmount').textContent =
        '₱' + Number(data.price || 0).toLocaleString('en-PH');

      // Status states
      const banner  = document.getElementById('statusBanner');
      const spinner = document.getElementById('statusSpinner');
      const statusTxt = document.getElementById('statusText');
      const provCard = document.getElementById('providerCard');
      const cancelWrap = document.getElementById('cancelWrap');
      const tipsSection = document.getElementById('tipsSection');
      const topBarTitle = document.getElementById('topBarTitle');

      if (data.status === 'pending') {
        topBarTitle.textContent = 'Finding Provider';
        banner.className = 'wfp-status-banner';
        spinner.style.display = 'block';
        statusTxt.innerHTML =
          `Looking for a provider<span class="wfp-dots"><span>.</span><span>.</span><span>.</span></span>`;
        provCard.style.display = 'none';
        cancelWrap.style.display = 'block';
        tipsSection.style.display = 'block';

      } else if (data.status === 'accepted' || data.has_provider) {
        topBarTitle.textContent = 'Provider En Route';
        banner.className = 'wfp-status-banner accepted';
        spinner.style.display = 'none';
        statusTxt.innerHTML = `Your provider is on the way! <span>🏃</span>`;
        cancelWrap.style.display = 'none';
        tipsSection.style.display = 'none';

        if (data.provider) {
          const p = data.provider;
          document.getElementById('provAvatar').textContent = p.initials || p.name.substring(0,2).toUpperCase();
          document.getElementById('provName').textContent = p.name;
          document.getElementById('provMeta').textContent = p.service + ' · ' + p.jobs + ' jobs done';
          const ratingVal = parseFloat(p.rating || 0);
          document.getElementById('provRating').textContent =
            ratingVal > 0 ? '⭐ ' + ratingVal.toFixed(1) + ' rating' : 'New Provider';
          providerPhone = p.phone || '';
          provCard.style.display = 'flex';
        }

      } else if (data.status === 'completed') {
        topBarTitle.textContent = 'Service Complete';
        banner.className = 'wfp-status-banner completed';
        spinner.style.display = 'none';
        statusTxt.innerHTML = `Service completed! <span>✅</span> Please leave a review.`;
        cancelWrap.style.display = 'none';
        tipsSection.style.display = 'none';

        // Auto-redirect to booking detail after 3s
        setTimeout(() => {
          window.location.href = 'booking_detail.php?booking_id=' + BOOKING_ID;
        }, 3000);

      } else if (data.status === 'cancelled') {
        topBarTitle.textContent = 'Booking Cancelled';
        banner.className = 'wfp-status-banner';
        banner.style.background = 'linear-gradient(135deg, #EF4444, #F87171)';
        spinner.style.display = 'none';
        statusTxt.innerHTML = `Booking was cancelled.`;
        provCard.style.display = 'none';
        cancelWrap.style.display = 'none';
        tipsSection.style.display = 'none';
      }

      syncSheetHeight();
    }

    function startPolling() {
      pollStatus(); // immediate first poll
      pollTimer = setInterval(pollStatus, POLL_INTERVAL_MS);
    }

    function stopPolling() {
      if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
      }
    }

    /* =============================================
       MAP STYLE PICKER
    ============================================= */
    function applyMapStyle(key, animate = true) {
      const style = MAP_STYLES[key] || MAP_STYLES.standard;
      if (tileLayer) map.removeLayer(tileLayer);

      const opts = { attribution: style.attribution, maxZoom: style.maxZoom };
      if (style.subdomains) opts.subdomains = style.subdomains;

      tileLayer = L.tileLayer(style.url, opts).addTo(map);
      currentStyleKey = key;
      localStorage.setItem('wfpMapStyle', key);

      // Update route line colour for dark/satellite
      if (routeLine) {
        const lineColor = key === 'dark' ? '#F5A623' : '#E8820C';
        routeLine.setStyle({ color: lineColor });
      }
    }

    function setMapStyle(key) {
      applyMapStyle(key);
      highlightStyleOpt(key);
      setTimeout(closeStylePicker, 320);
    }

    function highlightStyleOpt(key) {
      document.querySelectorAll('.wfp-style-opt').forEach(el => el.classList.remove('active'));
      const el = document.getElementById('styleOpt-' + key);
      if (el) el.classList.add('active');
    }

    function openStylePicker() {
      document.getElementById('styleOverlay').classList.add('open');
    }

    function closeStylePicker(e) {
      if (!e || e.target === document.getElementById('styleOverlay')) {
        document.getElementById('styleOverlay').classList.remove('open');
      }
    }

    /* =============================================
       ACTIONS
    ============================================= */
    function goBack() {
      window.location.href = 'booking_history.php';
    }

    function shareBooking() {
      openStylePicker();
    }

    async function cancelBooking() {
      if (!confirm('Are you sure you want to cancel this booking?')) return;
      const btn = document.getElementById('btnCancel');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Cancelling…';

      try {
        const fd = new FormData();
        fd.append('action', 'cancel');
        fd.append('id', BOOKING_ID);
        const res = await fetch(CANCEL_API, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          stopPolling();
          renderUI({ ...currentData, status: 'cancelled' });
          setTimeout(() => goBack(), 2000);
        } else {
          alert(data.message || 'Could not cancel booking.');
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-x-circle-fill"></i> Cancel Booking';
        }
      } catch (e) {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-x-circle-fill"></i> Cancel Booking';
      }
    }

    function contactProvider(event, type) {
      event.preventDefault();
      if (!providerPhone) {
        alert('Provider contact not yet available.');
        return;
      }
      if (type === 'call') {
        window.location.href = 'tel:' + providerPhone;
      } else {
        // SMS/chat
        window.location.href = 'sms:' + providerPhone;
      }
    }

    /* =============================================
       HELPERS
    ============================================= */
    function formatDate(dateStr) {
      if (!dateStr) return '–';
      const d = new Date(dateStr + 'T00:00:00');
      return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    /* =============================================
       BOOT
    ============================================= */
    document.addEventListener('DOMContentLoaded', () => {
      initMap();
      startPolling();

      // Sync sheet height on resize
      window.addEventListener('resize', syncSheetHeight);

      // Handle back button (Android)
      window.addEventListener('popstate', goBack);
    });
  </script>
</body>
</html>

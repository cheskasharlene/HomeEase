<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
enforceProviderSectionAccess('requests', $conn);
$bookingId = (int) ($_GET['booking_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>HomeEase – Active Job</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/waiting_for_provider.css">
  <style>
    /* Add any provider-specific overrides here */
    .wfp-status-banner.accepted {
      background: linear-gradient(135deg, #1A1A2E, #2D2D4E);
      /* Dark theme for provider en route */
    }

    .mark-done-btn {
      width: 100%;
      height: 46px;
      border-radius: 13px;
      background: linear-gradient(135deg, #059669, #10B981);
      color: #fff;
      border: none;
      cursor: pointer;
      font-size: 14px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 10px;
      box-shadow: 0 4px 14px rgba(5, 150, 105, .3);
      transition: transform 0.15s;
    }

    .mark-done-btn:active {
      transform: scale(0.97);
    }

    .eta-badge {
      background: rgba(255, 255, 255, 0.2);
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      margin-left: auto;
    }
  </style>
</head>

<body>
  <div class="wfp-shell">
    <!-- Top Bar -->
    <div class="wfp-topbar">
      <button class="wfp-topbar-btn" onclick="goPage('provider_requests.php')" aria-label="Back">
        <i class="bi bi-arrow-left"></i>
      </button>
      <div class="wfp-topbar-title" id="topBarTitle">Active Job</div>
      <button class="wfp-topbar-btn" onclick="openStylePicker()" aria-label="Map Style" style="font-size:16px;">
        <i class="bi bi-layers-fill"></i>
      </button>
    </div>

    <!-- Map -->
    <div id="wfpMap"></div>

    <!-- Recenter button -->
    <button class="wfp-recenter" id="btnRecenter" onclick="recenterMap()" aria-label="Recenter">
      <i class="bi bi-crosshair2"></i>
    </button>

    <!-- Style Picker Overlay (Reused from client side) -->
    <div class="wfp-style-overlay" id="styleOverlay"
      style="position:absolute;inset:0;z-index:700;background:rgba(0,0,0,.45);backdrop-filter:blur(4px);display:flex;align-items:flex-end;opacity:0;pointer-events:none;transition:opacity .28s"
      onclick="closeStylePicker(event)">
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
    <div class="wfp-sheet expanded" id="wfpSheet">
      <!-- Draggable handle — tap to collapse/expand -->
      <div class="wfp-sheet-handle" id="sheetHandle" onclick="toggleSheet()">
        <div class="wfp-sheet-handle-pill"></div>
      </div>
      <div class="wfp-sheet-body" id="sheetBody">

        <!-- Status Banner -->
        <div class="wfp-status-banner accepted" id="statusBanner">
          <div class="wfp-status-text" id="statusText">
            Head to the client's location <span>🚗</span>
          </div>
          <div class="eta-badge" id="etaText">Calc ETA...</div>
        </div>

        <!-- Client Card -->
        <div class="wfp-provider-card" id="clientCard">
          <div class="wfp-prov-av" id="clientAvatar">?</div>
          <div class="wfp-prov-info">
            <div class="wfp-prov-name" id="clientName">–</div>
            <div class="wfp-prov-meta" id="clientAddress"
              style="overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">
              –</div>
            <div class="wfp-prov-rating" id="clientNotes"
              style="color:#7A7064;font-weight:600;white-space:normal;word-break:break-word;">–</div>
          </div>
          <div class="wfp-prov-actions">
            <a class="wfp-prov-btn outline" id="btnChat" href="#" onclick="contactClient(event,'chat')">
              <i class="bi bi-chat-fill"></i>
            </a>
            <a class="wfp-prov-btn" id="btnCall" href="#" onclick="contactClient(event,'call')">
              <i class="bi bi-telephone-fill"></i>
            </a>
          </div>
        </div>

        <div style="padding: 0 14px 14px;">
          <button class="mark-done-btn" onclick="markDone()">
            <i class="bi bi-check2-circle"></i> Mark Job as Done
          </button>
        </div>

      </div>

      <!-- Price Bar -->
      <div class="wfp-price-bar">
        <div class="wfp-price-svc">
          <div class="wfp-price-svc-icon" id="svcIcon">🛠</div>
          <div>
            <div class="wfp-price-svc-label" id="svcLabel">Service</div>
            <div class="wfp-price-svc-sub" id="svcSub">Fixed price</div>
          </div>
        </div>
        <div class="wfp-price-amount" id="priceAmount">₱–</div>
      </div>
    </div><!-- /.wfp-price-bar -->
  </div><!-- /.wfp-sheet -->
  <!-- Chat Drawer (inside shell to stay in mobile frame) -->
  <div id="chatOverlay"
    style="position:absolute;inset:0;z-index:800;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);display:flex;align-items:flex-end;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s"
    onclick="closeChat(event)">
    <div id="chatDrawer"
      style="width:100%;max-width:480px;background:#fff;border-radius:24px 24px 0 0;padding-bottom:32px;display:flex;flex-direction:column;max-height:72dvh;transform:translateY(100%);transition:transform .32s cubic-bezier(.32,.72,0,1)"
      onclick="event.stopPropagation()">
      <div style="width:40px;height:4px;background:#E0D8D0;border-radius:2px;margin:12px auto 0"></div>
      <div
        style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px 10px;border-bottom:1.5px solid #F0EAE0">
        <div style="font-size:15px;font-weight:800;color:#1A1A2E;font-family:'Poppins',sans-serif">💬 Chat with Client
        </div>
        <button onclick="closeChat()"
          style="width:32px;height:32px;border-radius:50%;background:#F5F0EA;border:none;cursor:pointer;font-size:15px;color:#7A7064;display:flex;align-items:center;justify-content:center"><i
            class="bi bi-x-lg"></i></button>
      </div>
      <div id="chatMsgs" style="flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:8px">
        <div id="chatEmpty" style="text-align:center;color:#9E9690;font-size:13px;padding:24px 0">No messages yet. Say
          hello!</div>
      </div>
      <div style="display:flex;gap:8px;padding:10px 16px 4px;border-top:1.5px solid #F0EAE0">
        <textarea id="chatInput" rows="1" placeholder="Type a message…"
          style="flex:1;border:1.5px solid #E8E0D5;border-radius:22px;padding:10px 14px;font-size:13px;font-family:'Nunito',sans-serif;outline:none;resize:none;background:#FAFAF8"
          onkeydown="handleChatKey(event)"></textarea>
        <button onclick="sendMessage()"
          style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#E8820C,#F5A623);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;flex-shrink:0"><i
            class="bi bi-send-fill"></i></button>
      </div>
    </div>
  </div>
  </div><!-- /.wfp-shell -->

  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') initTheme();

    /* ══════════════════════════════════════════════
       DRAGGABLE BOTTOM SHEET
       — follows finger, snaps to expanded / collapsed
    ═══════════════════════════════════════════════ */
    const PEEK_PX = 72;   // px visible when collapsed (handle + top of banner)
    let _sheetExpanded = true;
    let _sheetH = 0;      // measured full height of the sheet

    function _sheetSnap(expanded, animate = true) {
      const sheet = document.getElementById('wfpSheet');
      if (!sheet) return;
      _sheetExpanded = expanded;
      sheet.style.transition = animate
        ? 'transform 0.38s cubic-bezier(0.32,0.72,0,1)'
        : 'none';
      sheet.style.transform = expanded ? 'translateY(0)' : `translateY(${_sheetH - PEEK_PX}px)`;
      sheet.classList.toggle('expanded', expanded);
      sheet.classList.toggle('collapsed', !expanded);
      /* Update --sheet-h so floating buttons reposition */
      setTimeout(() => {
        const vis = expanded ? _sheetH : PEEK_PX;
        document.documentElement.style.setProperty('--sheet-h', vis + 'px');
      }, animate ? 380 : 0);
    }

    function toggleSheet() {
      /* called by handle onclick (tap) */
      if (!_sheetH) _sheetH = document.getElementById('wfpSheet').getBoundingClientRect().height;
      _sheetSnap(!_sheetExpanded);
    }

    /* Drag logic — finger tracked at document level so it never loses contact */
    (function () {
      const handle = document.getElementById('sheetHandle');
      if (!handle) return;
      let startY = 0, startTransY = 0, active = false;
      const sheet = () => document.getElementById('wfpSheet');

      handle.addEventListener('touchstart', e => {
        const s = sheet();
        _sheetH = s.getBoundingClientRect().height;
        startY = e.touches[0].clientY;
        startTransY = _sheetExpanded ? 0 : (_sheetH - PEEK_PX);
        active = true;
        s.style.transition = 'none';
      }, { passive: true });

      /* Attached to document — works even if finger leaves the handle */
      document.addEventListener('touchmove', e => {
        if (!active) return;
        const dy = e.touches[0].clientY - startY;
        const newY = Math.max(0, Math.min(_sheetH - PEEK_PX, startTransY + dy));
        sheet().style.transform = `translateY(${newY}px)`;
      }, { passive: true });

      document.addEventListener('touchend', e => {
        if (!active) return;
        active = false;
        const dy = e.changedTouches[0].clientY - startY;
        const moved = Math.abs(dy) > 12;
        if (!moved) return; // tiny movement = tap, handled by onclick
        if (dy > 60 && _sheetExpanded)        _sheetSnap(false);
        else if (dy < -60 && !_sheetExpanded) _sheetSnap(true);
        else _sheetSnap(_sheetExpanded); // snap back
      }, { passive: true });
    })();

    /* ── User interaction guard — pause auto-fit while user explores map ── */
    let _userInteracted = false;
    let _interactTimer = null;

    let BID = <?= $bookingId ?: 0 ?>;
    const API = '../api/';

    /* ===== GEO CONSTANTS — Batangas Province ===== */
    const STO_TOMAS = {
      lat: 13.7565, lng: 121.0583,  // Batangas province center
      bounds: L.latLngBounds(L.latLng(13.30, 120.55), L.latLng(14.20, 121.55))
    };
    function isInStoTomas(lat, lng) {
      return STO_TOMAS.bounds.contains(L.latLng(lat, lng));
    }

    const MAP_STYLES = {
      standard: { url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', attribution: '&copy; OpenStreetMap contributors', maxZoom: 19, subdomains: 'abc' },
      google: { url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', attribution: '&copy; CARTO', maxZoom: 19, subdomains: 'abcd' },
      dark: { url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', attribution: '&copy; CARTO', maxZoom: 19, subdomains: 'abcd' },
      minimal: { url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', attribution: '&copy; CARTO', maxZoom: 19, subdomains: 'abcd' }
    };

    let currentStyleKey = localStorage.getItem('wfpMapStyle') || 'standard';
    let tileLayer = null;

    let map, custM, provM, routeLine;
    let custLat = 13.7565, custLng = 121.0583;  // Batangas province center
    let provLat = 13.7565, provLng = 121.0583;
    let clientPhone = '', myRole = 'provider';
    let chatLast = 0, chatTimer = null, chatOpen = false;

    function goPage(p) { window.location.href = p; }

    function syncSheetHeight() {
      const sheet = document.getElementById('wfpSheet');
      if (sheet) {
        const h = sheet.getBoundingClientRect().height;
        document.documentElement.style.setProperty('--sheet-h', h + 'px');
      }
    }

    function initMap() {
      map = L.map('wfpMap', {
        zoomControl: false, tap: false,
        minZoom: 9,
        maxBounds: STO_TOMAS.bounds,
        maxBoundsViscosity: 1.0
      });
      map.fitBounds(STO_TOMAS.bounds);
      applyMapStyle(currentStyleKey, false);
      highlightStyleOpt(currentStyleKey);

      /* Detect manual interaction — stop auto-fit for 30s */
      map.on('movestart', (e) => {
        if (e.originalEvent) {
          _userInteracted = true;
          clearTimeout(_interactTimer);
          _interactTimer = setTimeout(() => { _userInteracted = false; }, 30000);
        }
      });

      // ── Provider GPS Precision Engine ──
      if (navigator.geolocation) {
        const _GPS = {
          SMOOTH_ALPHA: 0.25,
          MIN_MOVE_M: 5,
          SEND_MIN_M: 5,
          ACCEPT_FIRST: 200,   // reject first fix worse than 200m
          ACCEPT_STEADY: 100,
        };
        let _smLat = null, _smLng = null;
        let _bestAcc = Infinity, _hasFirst = false;
        let _lastSentLat = null, _lastSentLng = null;
        let _accCircle = null;

        function _dist(la1, lo1, la2, lo2) {
          const R = 6371000, r = Math.PI / 180;
          const a = Math.sin((la2 - la1) * r / 2) ** 2
            + Math.cos(la1 * r) * Math.cos(la2 * r) * Math.sin((lo2 - lo1) * r / 2) ** 2;
          return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function _smooth(lat, lng) {
          if (_smLat === null) { _smLat = lat; _smLng = lng; }
          else {
            _smLat = _GPS.SMOOTH_ALPHA * lat + (1 - _GPS.SMOOTH_ALPHA) * _smLat;
            _smLng = _GPS.SMOOTH_ALPHA * lng + (1 - _GPS.SMOOTH_ALPHA) * _smLng;
          }
          return { lat: _smLat, lng: _smLng };
        }

        function _onFix(pos) {
          const rawLat = pos.coords.latitude;
          const rawLng = pos.coords.longitude;
          const acc = pos.coords.accuracy;

          /* Accept ALL fixes — even coarse WiFi/IP ones.
             Zoom level adapts to accuracy. */
          if (acc < _bestAcc) {
            _bestAcc = acc;
          }

          const s = _smooth(rawLat, rawLng);
          const moved = (_lastSentLat === null) ||
            _dist(_lastSentLat, _lastSentLng, s.lat, s.lng) >= _GPS.MIN_MOVE_M;
          const betterAcc = acc < _bestAcc * 0.85;

          _hasFirst = true;
          provLat = s.lat;
          provLng = s.lng;
          updateMarkers();

          // Accuracy circle on provider map
          if (_accCircle) map.removeLayer(_accCircle);
          if (acc > 5) {
            _accCircle = L.circle([s.lat, s.lng], {
              radius: acc,
              color: '#059669', fillColor: '#059669',
              fillOpacity: 0.06, weight: 1, opacity: 0.35
            }).addTo(map);
          }

          // Send to server only when moved enough (saves bandwidth)
          const shouldSend = BID && (
            _lastSentLat === null ||
            _dist(_lastSentLat, _lastSentLng, s.lat, s.lng) >= _GPS.SEND_MIN_M ||
            betterAcc
          );
          if (shouldSend) {
            _lastSentLat = s.lat; _lastSentLng = s.lng;
            const fd = new FormData();
            fd.append('action', 'update_location');
            fd.append('booking_id', BID);
            fd.append('lat', s.lat.toFixed(7));
            fd.append('lng', s.lng.toFixed(7));
            fetch(API + 'provider_requests_api.php', { method: 'POST', body: fd }).catch(() => { });
          }

          const label = acc < 10 ? `✅ ±${Math.round(acc)}m — Excellent`
            : acc < 50 ? `✅ ±${Math.round(acc)}m — High`
              : acc < 200 ? `📍 ±${Math.round(acc)}m — Good`
                : acc < 2000 ? `⚠️ ±${Math.round(acc)}m — Low`
                  : `⚠️ ±${(acc / 1000).toFixed(1)}km — Very low`;
          showGpsStatus(acc < 200 ? 'success' : 'loading', label);
          if (acc < 200) setTimeout(() => hideGpsStatus(), 5000);
        }

        function _onErr(err) {
          const msgs = {
            1: '🔒 Location denied — enable GPS.',
            2: '📡 GPS unavailable.',
            3: '⏱ GPS timed out — retrying…'
          };
          showGpsStatus('error', msgs[err.code] || 'GPS error.');
        }

        const _opts = { enableHighAccuracy: true, maximumAge: 0, timeout: 30000 };
        showGpsStatus('loading', '📡 Acquiring GPS signal…');

        /* Fast coarse fix first (accepts cached/WiFi), then continuous high-accuracy watch */
        navigator.geolocation.getCurrentPosition(_onFix, _onErr, {
          enableHighAccuracy: false, maximumAge: 60000, timeout: 5000
        });
        navigator.geolocation.watchPosition(_onFix, _onErr, _opts);
      }
      syncSheetHeight();
    }

    function showGpsStatus(type, msg) {
      let el = document.getElementById('provGpsBanner');
      if (!el) {
        el = document.createElement('div');
        el.id = 'provGpsBanner';
        el.style.cssText = 'position:absolute;top:90px;left:50%;transform:translateX(-50%);z-index:600;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:700;white-space:nowrap;max-width:90%;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,0.18);pointer-events:none;font-family:"Poppins",sans-serif;';
        document.getElementById('wfpMap').parentElement.appendChild(el);
      }
      const s = { loading: 'background:#FFF8F0;color:#E8820C;border:1.5px solid #FFE5B4;', error: 'background:#FFF5F5;color:#EF4444;border:1.5px solid #FCA5A5;', success: 'background:#ECFDF5;color:#059669;border:1.5px solid #6EE7B7;' };
      el.style.cssText += (s[type] || s.loading);
      el.textContent = msg;
      el.style.display = 'block';
    }
    function hideGpsStatus() {
      const el = document.getElementById('provGpsBanner');
      if (el) el.style.display = 'none';
    }

    function applyMapStyle(key, animate = true) {
      const style = MAP_STYLES[key] || MAP_STYLES.standard;
      if (tileLayer) map.removeLayer(tileLayer);
      const opts = { attribution: style.attribution, maxZoom: style.maxZoom };
      if (style.subdomains) opts.subdomains = style.subdomains;
      tileLayer = L.tileLayer(style.url, opts).addTo(map);
      currentStyleKey = key;
      localStorage.setItem('wfpMapStyle', key);

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

    function openStylePicker() { document.getElementById('styleOverlay').classList.add('open'); }
    function closeStylePicker(e) {
      if (!e || e.target === document.getElementById('styleOverlay')) {
        document.getElementById('styleOverlay').classList.remove('open');
      }
    }

    function pin(type) {
      if (type === 'client') {
        return L.divIcon({ className: '', html: `<div class="wfp-marker-customer" style="width:34px;height:34px;font-size:16px;">🏠</div>`, iconSize: [34, 34], iconAnchor: [17, 17] });
      } else {
        return L.divIcon({ className: '', html: `<div class="wfp-marker-provider" style="width:38px;height:38px;font-size:18px;">🚗</div>`, iconSize: [38, 38], iconAnchor: [19, 19] });
      }
    }

    function updateMarkers() {
      if (custM) map.removeLayer(custM);
      custM = L.marker([custLat, custLng], { icon: pin('client') }).addTo(map).bindPopup('<b>Client</b>');

      if (provM) map.removeLayer(provM);
      provM = L.marker([provLat, provLng], { icon: pin('provider') }).addTo(map).bindPopup('<b>You</b>');

      fetchRouteAndDraw(provLat, provLng, custLat, custLng);

      /* Only auto-fit when user hasn't interacted */
      if (!_userInteracted) {
        const bounds = L.latLngBounds([[custLat, custLng], [provLat, provLng]]);
        map.fitBounds(bounds, { padding: [50, 80] });
      }
    }

    async function fetchRouteAndDraw(fLat, fLng, tLat, tLng) {
      try {
        const url = `https://router.project-osrm.org/route/v1/driving/${fLng},${fLat};${tLng},${tLat}?overview=full&geometries=geojson`;
        const res = await fetch(url);
        const data = await res.json();

        if (routeLine) map.removeLayer(routeLine);

        if (data.routes && data.routes[0]) {
          const coords = data.routes[0].geometry.coordinates.map(c => [c[1], c[0]]);
          const lineColor = currentStyleKey === 'dark' ? '#F5A623' : '#E8820C';
          routeLine = L.polyline(coords, {
            color: lineColor, weight: 5, opacity: 0.9, lineCap: 'round', lineJoin: 'round'
          }).addTo(map);

          const distKm = (data.routes[0].distance / 1000).toFixed(1);
          const timeMin = Math.ceil(data.routes[0].duration / 60);
          document.getElementById('etaText').textContent = `${distKm} km • ${timeMin} min`;
          map.fitBounds(routeLine.getBounds(), { padding: [50, 80] });
        } else {
          drawStraightLine(fLat, fLng, tLat, tLng);
        }
      } catch (e) {
        drawStraightLine(fLat, fLng, tLat, tLng);
      }
    }

    function drawStraightLine(fLat, fLng, tLat, tLng) {
      if (routeLine) map.removeLayer(routeLine);
      const lineColor = currentStyleKey === 'dark' ? '#F5A623' : '#E8820C';
      routeLine = L.polyline([[fLat, fLng], [tLat, tLng]], {
        color: lineColor, weight: 4, dashArray: '10,8', opacity: 0.8
      }).addTo(map);
      map.fitBounds(routeLine.getBounds(), { padding: [50, 80] });
      document.getElementById('etaText').textContent = `Calculating...`;
    }

    async function geocode(address) {
      try {
        const q = encodeURIComponent(address + ', Batangas, Philippines');
        const res = await fetch(
          `https://nominatim.openstreetmap.org/search?q=${q}&format=json&limit=1&viewbox=120.55,14.20,121.55,13.30&bounded=1`,
          { headers: { 'Accept-Language': 'en' } }
        );
        const d = await res.json();
        if (d && d[0]) return { lat: parseFloat(d[0].lat), lng: parseFloat(d[0].lon) };
      } catch (e) { }
      return null;
    }

    function recenterMap() {
      _userInteracted = false;
      clearTimeout(_interactTimer);

      /* ── Animate button into loading state ── */
      const btn = document.getElementById('btnRecenter');
      if (btn) {
        btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 0.8s linear infinite;display:inline-block"></i>';
        btn.style.opacity = '0.75';
        btn.style.pointerEvents = 'none';
      }
      const _resetBtn = () => {
        if (btn) {
          btn.innerHTML = '<i class="bi bi-crosshair2"></i>';
          btn.style.opacity = '';
          btn.style.pointerEvents = '';
        }
      };

      if (!navigator.geolocation) {
        _resetBtn();
        if (custM && provM) {
          const bounds = L.latLngBounds([custLat, custLng], [provLat, provLng]);
          map.fitBounds(bounds, { padding: [80, 80] });
        }
        return;
      }

      showGpsStatus('loading', '📡 Getting precise location…');

      /* High-accuracy one-shot fix */
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          _resetBtn();
          provLat = pos.coords.latitude;
          provLng = pos.coords.longitude;
          const acc = pos.coords.accuracy;

          /* Update provider marker with fresh GPS */
          updateMarkers();

          const label = acc < 10 ? `✅ ±${Math.round(acc)}m — Excellent`
            : acc < 50  ? `✅ ±${Math.round(acc)}m — High`
            : acc < 200 ? `📍 ±${Math.round(acc)}m — Good`
            : `⚠️ ±${(acc/1000).toFixed(1)}km — Low`;
          showGpsStatus('success', label);
          setTimeout(hideGpsStatus, 4000);
        },
        (err) => {
          _resetBtn();
          const msgs = { 1:'🔒 Location permission denied.', 2:'📡 GPS unavailable.', 3:'⏱ GPS timed out — retrying…' };
          showGpsStatus('error', msgs[err.code] || 'GPS error.');
          /* Fall back to fitting existing markers */
          if (custM && provM) {
            const bounds = L.latLngBounds([custLat, custLng], [provLat, provLng]);
            map.fitBounds(bounds, { padding: [80, 80] });
          }
        },
        { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
      );
    }

    /* ── BOOKING DATA ── */
    async function loadBooking() {
      try {
        const url = API + 'accepted_booking_api.php' + (BID ? '?booking_id=' + BID : '');
        const data = await (await fetch(url, { cache: 'no-store' })).json();
        if (!data.success || !data.booking) return;
        const b = data.booking;

        if (!BID && b.id) BID = b.id;

        document.getElementById('clientName').textContent = b.client_name || 'Client';
        const addr = b.client_address || b.address || '–';
        document.getElementById('clientAddress').textContent = addr;
        document.getElementById('clientNotes').textContent = b.details || b.notes ? `Notes: ${b.details || b.notes}` : 'No additional notes';

        document.getElementById('clientAvatar').textContent = b.client_name ? b.client_name.substring(0, 2).toUpperCase() : 'CL';

        document.getElementById('svcLabel').textContent = b.service || 'Service';
        document.getElementById('priceAmount').textContent = '₱' + Number(b.price || 0).toLocaleString('en-PH');
        document.getElementById('topBarTitle').textContent = b.service || 'Active Job';

        clientPhone = b.client_phone || '';

        /* ── Use stored GPS coords from booking (set at booking time by location_picker) ── */
        if (b.customer_lat && b.customer_lng) {
          custLat = parseFloat(b.customer_lat);
          custLng = parseFloat(b.customer_lng);
        } else {
          /* Fallback: geocode address within Batangas province */
          const geo = addr && addr !== '–' ? await geocode(addr) : null;
          if (geo) { custLat = geo.lat; custLng = geo.lng; }
        }

        /* If provider GPS hasn't fixed yet, position provider near customer */
        if (!_hasFirst || (provLat === 13.7565 && provLng === 121.0583)) {
          provLat = custLat - 0.008;
          provLng = custLng - 0.006;
        }
        updateMarkers();

      } catch (e) { console.warn(e); }
    }

    /* ── ACTIONS ── */
    async function markDone() {
      if (!confirm('Mark this job as completed?')) return;
      const fd = new FormData();
      fd.append('action', 'complete'); fd.append('booking_id', BID);
      try {
        const d = await (await fetch(API + 'provider_requests_api.php', { method: 'POST', body: fd })).json();
        if (d.success) { alert('Job marked complete!'); goPage('provider_requests.php'); }
        else alert(d.message || 'Error.');
      } catch (e) { alert('Network error.'); }
    }

    function contactClient(event, type) {
      event.preventDefault();
      if (type === 'call') {
        if (!clientPhone) { alert('No phone number available.'); return; }
        window.location.href = 'tel:' + clientPhone;
      } else {
        openChat();
      }
    }

    /* ── CHAT ── */
    function openChat() {
      chatOpen = true;
      const ov = document.getElementById('chatOverlay');
      const dr = document.getElementById('chatDrawer');
      ov.style.opacity = '1'; ov.style.pointerEvents = 'all';
      dr.style.transform = 'translateY(0)';
      scrollChat();
    }

    function closeChat(e) {
      if (e && e.target !== document.getElementById('chatOverlay')) return;
      chatOpen = false;
      const ov = document.getElementById('chatOverlay');
      const dr = document.getElementById('chatDrawer');
      ov.style.opacity = '0'; ov.style.pointerEvents = 'none';
      dr.style.transform = 'translateY(100%)';
    }

    function startChat() { fetchChat(); chatTimer = setInterval(fetchChat, 3000); }

    async function fetchChat() {
      if (!BID) return;
      try {
        const data = await (await fetch(API + 'chat_api.php?booking_id=' + BID + '&after_id=' + chatLast + '&_t=' + Date.now(), { cache: 'no-store' })).json();
        if (!data.success) return;
        myRole = data.my_role || 'provider';
        if (data.messages?.length) {
          appendMsgs(data.messages);
          chatLast = data.messages[data.messages.length - 1].id;
        }
      } catch (e) { }
    }

    function appendMsgs(msgs) {
      const box = document.getElementById('chatMsgs');
      document.getElementById('chatEmpty')?.remove();
      msgs.forEach(m => {
        const mine = m.sender_role === myRole;
        const div = document.createElement('div');
        div.style.cssText = `max-width:78%;padding:9px 13px;border-radius:18px;font-size:13px;line-height:1.45;word-break:break-word;${mine ? 'background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;align-self:flex-end;border-bottom-right-radius:4px;' : 'background:#F5F0EA;color:#1A1A2E;align-self:flex-start;border-bottom-left-radius:4px;'}`;
        const t = new Date(m.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        div.innerHTML = esc(m.message) + `<div style="font-size:10px;opacity:.6;margin-top:3px;text-align:right">${t}</div>`;
        box.appendChild(div);
      });
      scrollChat();
    }

    function scrollChat() { const b = document.getElementById('chatMsgs'); b.scrollTop = b.scrollHeight; }

    async function sendMessage() {
      const inp = document.getElementById('chatInput');
      const msg = inp.value.trim();
      if (!msg || !BID) return;
      inp.value = '';
      const fd = new FormData();
      fd.append('action', 'send'); fd.append('booking_id', BID); fd.append('message', msg);
      try { const d = await (await fetch(API + 'chat_api.php', { method: 'POST', body: fd })).json(); if (d.success) fetchChat(); } catch (e) { }
    }

    function handleChatKey(e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } }
    function esc(s) { return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    document.addEventListener('DOMContentLoaded', () => {
      initMap();
      setTimeout(() => { map.invalidateSize(); }, 200);
      loadBooking().then(() => {
        startChat();
      });
      window.addEventListener('resize', syncSheetHeight);
    });
  </script>
</body>

</html>
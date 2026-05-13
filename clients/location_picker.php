<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: ../index.php');
  exit;
}
$serviceName = isset($_GET['svc']) ? trim($_GET['svc']) : '';
if (!$serviceName) {
  header('Location: service_selection.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Confirm Location</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="../assets/css/main.css">
  <style>
    * { box-sizing: border-box; }

    body, html { margin: 0; padding: 0; height: 100%; background: #F7F3EE; }

    .lp-shell {
      position: relative;
      width: 100%; max-width: 480px;
      margin: 0 auto;
      height: 100dvh;
      display: flex;
      flex-direction: column;
      background: #F7F3EE;
      overflow: hidden;
    }

    /* ── Top Bar ── */
    .lp-topbar {
      display: flex; align-items: center; gap: 12px;
      padding: 14px 16px 10px;
      background: #fff;
      border-bottom: 1.5px solid #F0EAE0;
      flex-shrink: 0;
      z-index: 10;
    }
    .lp-back-btn {
      width: 36px; height: 36px; border-radius: 50%;
      background: #F5F0EA; border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; color: #1A1A2E; flex-shrink: 0;
    }
    .lp-topbar-info { flex: 1; min-width: 0; }
    .lp-topbar-label {
      font-size: 11px; font-weight: 700; color: #9E9690;
      text-transform: uppercase; letter-spacing: .5px; margin-bottom: 1px;
    }
    .lp-topbar-svc {
      font-size: 14px; font-weight: 800; color: #1A1A2E;
      font-family: 'Poppins', sans-serif;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    /* ── Map ── */
    #lpMap {
      flex: 1;
      width: 100%;
      height: 100%;
      min-height: 0;
      z-index: 1;
    }

    /* draggable center pin overlay */
    .map-pin-overlay {
      position: absolute;
      top: 50%; left: 50%;
      transform: translate(-50%, -100%);
      z-index: 400;
      pointer-events: none;
      display: flex; flex-direction: column; align-items: center;
    }
    .map-pin-dot {
      width: 44px; height: 44px; border-radius: 50%;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      border: 3px solid #fff;
      box-shadow: 0 4px 18px rgba(232,130,12,0.45);
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
    }
    .map-pin-tail {
      width: 3px; height: 14px;
      background: linear-gradient(#F5A623, transparent);
      border-radius: 2px;
    }
    .map-pin-shadow {
      width: 14px; height: 5px;
      background: rgba(0,0,0,0.15);
      border-radius: 50%;
    }

    /* GPS accuracy circle badge */
    #accBadge {
      position: absolute;
      top: 12px; left: 50%; transform: translateX(-50%);
      z-index: 450;
      padding: 6px 14px; border-radius: 20px;
      font-size: 11px; font-weight: 700;
      font-family: 'Poppins', sans-serif;
      white-space: nowrap;
      box-shadow: 0 2px 10px rgba(0,0,0,0.15);
      pointer-events: none;
      transition: opacity .3s;
    }

    /* "Use my location" recenter button */
    #btnRecenter {
      position: absolute;
      right: 14px; top: 14px;
      z-index: 450;
      width: 42px; height: 42px;
      border-radius: 50%;
      background: #fff;
      border: 1.5px solid #E8E0D5;
      cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; color: #E8820C;
      box-shadow: 0 2px 10px rgba(0,0,0,0.12);
    }

    /* ── Bottom Sheet ── */
    .lp-sheet {
      background: #fff;
      border-radius: 24px 24px 0 0;
      padding: 20px 20px 28px;
      flex-shrink: 0;
      box-shadow: 0 -4px 24px rgba(0,0,0,0.08);
      z-index: 10;
    }
    .lp-sheet-handle {
      width: 40px; height: 4px;
      background: #E0D8D0; border-radius: 2px;
      margin: 0 auto 16px;
    }
    .lp-sheet-title {
      font-size: 16px; font-weight: 800; color: #1A1A2E;
      font-family: 'Poppins', sans-serif; margin-bottom: 14px;
    }

    /* ── Search input row ── */
    .lp-search-wrap {
      position: relative;
      margin-bottom: 10px;
    }
    .lp-search-row {
      display: flex; align-items: center; gap: 12px;
      background: #FAFAF8; border: 1.5px solid #EDE8E0;
      border-radius: 14px; padding: 10px 14px;
      transition: border-color .2s, background .2s;
    }
    .lp-search-row:focus-within {
      border-color: #E8820C;
      background: #fff;
      box-shadow: 0 0 0 3px rgba(232,130,12,0.10);
    }
    .lp-addr-icon {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; color: #fff; flex-shrink: 0;
    }
    .lp-search-input {
      flex: 1; border: none; outline: none; background: transparent;
      font-size: 13px; font-weight: 700; color: #1A1A2E;
      font-family: 'Nunito', sans-serif;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      min-width: 0;
    }
    .lp-search-input::placeholder { color: #C5BEB3; font-weight: 600; }
    .lp-search-clear {
      width: 24px; height: 24px; border-radius: 50%;
      background: #EDE8E0; border: none; cursor: pointer;
      display: none; align-items: center; justify-content: center;
      font-size: 12px; color: #7A7064; flex-shrink: 0;
    }
    .lp-search-clear.show { display: flex; }

    /* Suggestions dropdown */
    .lp-suggestions {
      position: absolute; left: 0; right: 0; top: calc(100% + 6px);
      background: #fff;
      border: 1.5px solid #EDE8E0;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 8px 28px rgba(0,0,0,0.12);
      z-index: 500;
      display: none;
    }
    .lp-suggestions.open { display: block; }
    .lp-sugg-item {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 14px; cursor: pointer;
      transition: background .15s;
      border-bottom: 1px solid #F5F0EA;
    }
    .lp-sugg-item:last-child { border-bottom: none; }
    .lp-sugg-item:hover, .lp-sugg-item:active { background: #FFF8F0; }
    .lp-sugg-icon {
      width: 32px; height: 32px; border-radius: 50%;
      background: #F5F0EA;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; flex-shrink: 0; color: #E8820C;
    }
    .lp-sugg-text { flex: 1; min-width: 0; }
    .lp-sugg-main {
      font-size: 13px; font-weight: 700; color: #1A1A2E;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .lp-sugg-sub {
      font-size: 11px; color: #9E9690; font-weight: 600;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .lp-sugg-loading {
      padding: 14px 16px; text-align: center;
      font-size: 12px; color: #9E9690; font-weight: 600;
    }

    /* Use my location row */
    .lp-use-location {
      display: flex; align-items: center; gap: 12px;
      padding: 12px 14px; cursor: pointer;
      border-radius: 14px; margin-bottom: 16px;
      transition: background .2s;
    }
    .lp-use-location:active { background: #F5F0EA; }
    .lp-use-location i { font-size: 20px; color: #E8820C; flex-shrink: 0; }
    .lp-use-location-lbl {
      font-size: 14px; font-weight: 700; color: #1A1A2E;
    }

    /* Confirm button */
    .lp-confirm-btn {
      width: 100%; height: 52px; border-radius: 16px;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff; border: none; cursor: pointer;
      font-size: 15px; font-weight: 800;
      font-family: 'Poppins', sans-serif;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      box-shadow: 0 6px 20px rgba(232,130,12,0.38);
      transition: transform .15s, box-shadow .15s;
    }
    .lp-confirm-btn:active { transform: scale(0.97); }
    .lp-confirm-btn:disabled { opacity: .6; cursor: not-allowed; }

    @keyframes spin { to { transform: rotate(360deg); } }
    .spinning { animation: spin .7s linear infinite; }
  </style>
</head>
<body>
  <div class="lp-shell">

    <!-- Top Bar -->
    <div class="lp-topbar">
      <button class="lp-back-btn" onclick="history.back()"><i class="bi bi-arrow-left"></i></button>
      <div class="lp-topbar-info">
        <div class="lp-topbar-label">Booking for</div>
        <div class="lp-topbar-svc" id="svcNameLabel"><?= htmlspecialchars($serviceName) ?></div>
      </div>
    </div>

    <!-- Map (fills available space) -->
    <div style="position:relative; flex:1; min-height:0;">
      <div id="lpMap"></div>

      <!-- Centre pin (visual only — map moves under it) -->
      <div class="map-pin-overlay" id="mapPin">
        <div class="map-pin-dot">🏠</div>
        <div class="map-pin-tail"></div>
        <div class="map-pin-shadow"></div>
      </div>

      <!-- GPS accuracy badge -->
      <div id="accBadge" style="display:none;"></div>

      <!-- Recenter button -->
      <button id="btnRecenter" onclick="recenterToGps()" title="Use my current location">
        <i class="bi bi-crosshair2"></i>
      </button>
    </div>

    <!-- Bottom Sheet -->
    <div class="lp-sheet">
      <div class="lp-sheet-handle"></div>
      <div class="lp-sheet-title">Where should we send your provider?</div>

      <!-- Search / address input with autocomplete -->
      <div class="lp-search-wrap" id="searchWrap">
        <div class="lp-search-row">
          <div class="lp-addr-icon"><i class="bi bi-geo-alt-fill"></i></div>
          <input
            class="lp-search-input"
            id="addrSearch"
            type="text"
            placeholder="Detecting your location…"
            autocomplete="off"
            spellcheck="false"
            oninput="onSearchInput()"
            onfocus="onSearchFocus()"
            onblur="onSearchBlur()"
            onkeydown="onSearchKeydown(event)"
          >
          <button class="lp-search-clear" id="btnClear" onclick="clearSearch()">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <!-- Suggestions dropdown -->
        <div class="lp-suggestions" id="suggBox"></div>
      </div>

      <!-- Use my current location -->
      <div class="lp-use-location" onclick="recenterToGps()">
        <i class="bi bi-send-fill"></i>
        <span class="lp-use-location-lbl">Use my current location</span>
      </div>

      <!-- Confirm -->
      <button class="lp-confirm-btn" id="btnConfirm" onclick="confirmLocation()" disabled>
        <i class="bi bi-check-circle-fill"></i> Confirm Location
      </button>
    </div>

    <!-- Hidden fields -->
    <input type="hidden" id="finalLat" value="">
    <input type="hidden" id="finalLng" value="">
    <input type="hidden" id="finalAddr" value="">
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', initMap);

    if (typeof initTheme === 'function') initTheme();

    const SVC = <?= json_encode($serviceName) ?>;

    /* ── Batangas Province bounds ── */
    const BATANGAS_BOUNDS = L.latLngBounds(L.latLng(13.30, 120.55), L.latLng(14.20, 121.55));
    const BATANGAS_CENTER = [13.7565, 121.0583];

    let map, accuracyCircle;
    let gpsLat = null, gpsLng = null;
    let gpsWatchId = null;
    let _bestAcc = Infinity;

    /* ── Init Map ── */
    function initMap() {
      map = L.map('lpMap', {
        zoomControl: false,
        attributionControl: false,
        tap: false,
        minZoom: 9,
        maxZoom: 19,
        maxBounds: BATANGAS_BOUNDS,
        maxBoundsViscosity: 1.0
      });
      map.fitBounds(BATANGAS_BOUNDS);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        subdomains: 'abc', maxZoom: 19
      }).addTo(map);

      /* Geocode on every map stop */
      map.on('moveend', onMapMoveEnd);

      /* Kick off initial geocode right away with the center of Batangas
         so the Confirm button is enabled even before GPS locks */
      onMapMoveEnd();

      /* Start GPS */
      startGps();
    }

    /* ── GPS — accept everything, refine over time ── */
    function startGps() {
      if (!navigator.geolocation) {
        showBadge('error', '📡 GPS not supported by this browser');
        return;
      }
      showBadge('loading', '📡 Acquiring GPS signal…');

      const opts = { enableHighAccuracy: true, maximumAge: 30000, timeout: 30000 };

      /* First shot — accept cached position (up to 30 s old) for speed */
      navigator.geolocation.getCurrentPosition(onFix, onErr, {
        enableHighAccuracy: false,   // fast coarse fix first
        maximumAge: 60000,
        timeout: 5000
      });

      /* Then watch for the best possible fix */
      gpsWatchId = navigator.geolocation.watchPosition(onFix, onErr, opts);
    }

    function onFix(pos) {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      const acc = pos.coords.accuracy;   // metres

      /* Always store best coordinates */
      if (acc < _bestAcc) {
        _bestAcc = acc;
        gpsLat = lat;
        gpsLng = lng;
      }

      /* Accuracy circle */
      if (accuracyCircle) map.removeLayer(accuracyCircle);
      if (acc > 5) {
        accuracyCircle = L.circle([lat, lng], {
          radius: acc,
          color: '#E8820C', fillColor: '#F5A623',
          fillOpacity: 0.07, weight: 1.5, opacity: 0.35
        }).addTo(map);
      }

      /* Pick zoom based on accuracy:
         < 50m  → street level 17
         < 500m → neighbourhood 15
         < 5km  → city 13
         else   → regional 11           */
      const zoom = acc < 50 ? 17 : acc < 500 ? 15 : acc < 5000 ? 13 : 11;

      /* Pan to GPS position */
      map.setView([lat, lng], zoom, { animate: true });

      /* Badge */
      const label = acc < 10   ? `✅ ±${Math.round(acc)}m — Excellent`
                  : acc < 50   ? `✅ ±${Math.round(acc)}m — High`
                  : acc < 200  ? `📍 ±${Math.round(acc)}m — Good`
                  : acc < 2000 ? `⚠️ ±${Math.round(acc)}m — Low`
                  :              `⚠️ ±${(acc/1000).toFixed(1)}km — Very low`;
      showBadge(acc < 200 ? 'success' : 'loading', label);
      if (acc < 200) setTimeout(hideBadge, 5000);
    }

    function onErr(err) {
      const msgs = {
        1: '🔒 Location denied — drag the pin to your address',
        2: '📡 GPS signal unavailable — drag the pin manually',
        3: '⏱ GPS timed out — drag the pin to your address'
      };
      showBadge('error', msgs[err.code] || 'GPS error');
    }

    /* ── Geocode the current map centre → update search input ── */
    let geocodeTimer = null;
    let _skipNextMoveEnd = false;   // set to true when WE pan (search selection)
    function onMapMoveEnd() {
      if (_skipNextMoveEnd) { _skipNextMoveEnd = false; return; }
      const c = map.getCenter();
      setSearchValue('Getting address…', true);
      /* Always enable Confirm with current coords */
      document.getElementById('finalLat').value = c.lat;
      document.getElementById('finalLng').value = c.lng;
      document.getElementById('btnConfirm').disabled = false;
      clearTimeout(geocodeTimer);
      geocodeTimer = setTimeout(() => reverseGeocode(c.lat, c.lng), 700);
    }

    async function reverseGeocode(lat, lng) {
      try {
        const res  = await fetch(
          `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`,
          { headers: { 'Accept-Language': 'en' } }
        );
        const data = await res.json();
        if (data && data.display_name) {
          const a = data.address || {};
          const parts = [
            a.house_number,
            a.road || a.pedestrian || a.path,
            a.neighbourhood || a.suburb || a.village || a.hamlet || a.quarter,
            a.city || a.town || a.municipality || a.county
          ].filter(Boolean);
          const main = parts.length ? parts.join(', ') : data.display_name.split(',').slice(0, 3).join(',').trim();
          const sub  = [a.city || a.town || a.municipality, a.province || a.state].filter(Boolean).join(', ') || 'Batangas';
          const full = main + ', ' + sub;
          setSearchValue(full);
          document.getElementById('finalAddr').value = full;
          document.getElementById('finalLat').value  = lat;
          document.getElementById('finalLng').value  = lng;
          document.getElementById('btnConfirm').disabled = false;
          return;
        }
      } catch(e) {}
      const coord = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
      setSearchValue(`GPS Coordinates (${coord})`);
      document.getElementById('finalAddr').value = `GPS Coordinates (${coord})`;
      document.getElementById('btnConfirm').disabled = false;
    }

    /* ── Helper: set/clear the search input value ── */
    function setSearchValue(text, isPlaceholder = false) {
      const inp = document.getElementById('addrSearch');
      if (isPlaceholder) {
        inp.value = '';
        inp.placeholder = text;
      } else {
        inp.value = text;
        inp.placeholder = 'Search a location in Batangas…';
      }
      const clr = document.getElementById('btnClear');
      if (clr) clr.classList.toggle('show', !!inp.value);
    }

    /* ── Search autocomplete ── */
    let searchTimer = null;
    let _blurTimer  = null;

    function onSearchFocus() {
      clearTimeout(_blurTimer);
      const inp = document.getElementById('addrSearch');
      /* Select all so the user can immediately type a new query */
      inp.select();
      if (inp.value.trim().length >= 2) showSuggestions(inp.value.trim());
    }

    function onSearchBlur() {
      /* Delay closing so clicks on suggestions register first */
      _blurTimer = setTimeout(closeSuggestions, 200);
    }

    function onSearchInput() {
      const inp = document.getElementById('addrSearch');
      const q   = inp.value.trim();
      document.getElementById('btnClear').classList.toggle('show', q.length > 0);
      clearTimeout(searchTimer);
      if (q.length < 2) { closeSuggestions(); return; }
      searchTimer = setTimeout(() => showSuggestions(q), 380);
    }

    function onSearchKeydown(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        const first = document.querySelector('#suggBox .lp-sugg-item');
        if (first) first.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
      }
    }

    function clearSearch() {
      const inp = document.getElementById('addrSearch');
      inp.value = '';
      inp.placeholder = 'Search a location in Batangas…';
      document.getElementById('btnClear').classList.remove('show');
      closeSuggestions();
      inp.focus();
    }

    async function showSuggestions(q) {
      const box = document.getElementById('suggBox');
      box.innerHTML = '<div class="lp-sugg-loading"><i class="bi bi-arrow-clockwise" style="animation:spin .7s linear infinite;margin-right:6px;"></i>Searching…</div>';
      box.classList.add('open');
      try {
        /* Search within Batangas province using viewbox + countrycodes only.
           Do NOT append address text — let Nominatim match the raw query. */
        const url = 'https://nominatim.openstreetmap.org/search'
          + `?q=${encodeURIComponent(q)}`
          + '&format=json&limit=6&addressdetails=1&namedetails=1'
          + '&countrycodes=ph'
          + '&viewbox=120.55,14.20,121.55,13.30&bounded=1';
        const res  = await fetch(url, { headers: { 'Accept-Language': 'en' } });
        const data = await res.json();

        if (!data || !data.length) {
          /* Widen search to all Philippines if nothing found in Batangas */
          const url2 = 'https://nominatim.openstreetmap.org/search'
            + `?q=${encodeURIComponent(q + ' Batangas')}`
            + '&format=json&limit=5&addressdetails=1&namedetails=1'
            + '&countrycodes=ph';
          const res2  = await fetch(url2, { headers: { 'Accept-Language': 'en' } });
          const data2 = await res2.json();
          if (!data2 || !data2.length) {
            box.innerHTML = '<div class="lp-sugg-loading">No results found — try a different name</div>';
            return;
          }
          renderSuggestions(data2, box);
          return;
        }
        renderSuggestions(data, box);
      } catch(e) {
        box.innerHTML = '<div class="lp-sugg-loading">Search unavailable — check connection</div>';
      }
    }

    function renderSuggestions(data, box) {
      box.innerHTML = data.map(item => {
        const a    = item.address || {};
        const nd   = item.namedetails || {};
        const main = (nd.name || nd['name:en'] || item.name ||
                      a.road || a.neighbourhood || a.suburb || a.village ||
                      item.display_name.split(',')[0]).trim();
        const sub  = [a.municipality || a.city || a.town || a.village,
                      a.province || a.state]
                     .filter(Boolean).join(', ') || 'Batangas';
        const lat  = parseFloat(item.lat);
        const lng  = parseFloat(item.lon);
        return `
          <div class="lp-sugg-item" onmousedown="selectSuggestion(${lat},${lng},${JSON.stringify(main + ', ' + sub)})">
            <div class="lp-sugg-icon"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="lp-sugg-text">
              <div class="lp-sugg-main">${esc(main)}</div>
              <div class="lp-sugg-sub">${esc(sub)}</div>
            </div>
          </div>`;
      }).join('');
    }

    function selectSuggestion(lat, lng, label) {
      closeSuggestions();
      setSearchValue(label);
      document.getElementById('finalLat').value  = lat;
      document.getElementById('finalLng').value  = lng;
      document.getElementById('finalAddr').value = label;
      document.getElementById('btnConfirm').disabled = false;
      /* Pan map without triggering reverse geocode */
      _skipNextMoveEnd = true;
      map.setView([lat, lng], 17, { animate: true });
    }

    function closeSuggestions() {
      const box = document.getElementById('suggBox');
      box.classList.remove('open');
    }

    function esc(s) {
      return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    /* ── Recenter to best GPS fix ── */
    function recenterToGps() {
      if (gpsLat !== null && gpsLng !== null) {
        const zoom = _bestAcc < 100 ? 17 : _bestAcc < 1000 ? 15 : 13;
        map.setView([gpsLat, gpsLng], zoom, { animate: true });
      } else {
        showBadge('loading', '📡 Re-acquiring GPS…');
        startGps();
      }
    }

    /* ── Confirm → booking form ── */
    function confirmLocation() {
      const lat  = document.getElementById('finalLat').value;
      const lng  = document.getElementById('finalLng').value;
      const addr = document.getElementById('finalAddr').value || 'GPS Location';
      if (!lat || !lng) { showBadge('error', 'Still detecting location — please wait'); return; }
      const url = 'booking_form.php'
        + '?svc='  + encodeURIComponent(SVC)
        + '&lat='  + encodeURIComponent(lat)
        + '&lng='  + encodeURIComponent(lng)
        + '&addr=' + encodeURIComponent(addr);
      window.location.href = url;
    }

    /* ── Badge helpers ── */
    function showBadge(type, msg) {
      const el = document.getElementById('accBadge');
      const styles = {
        loading : 'background:#FFF8F0;color:#E8820C;border:1.5px solid #FFE5B4;',
        success : 'background:#ECFDF5;color:#059669;border:1.5px solid #6EE7B7;',
        error   : 'background:#FFF5F5;color:#EF4444;border:1.5px solid #FCA5A5;'
      };
      el.style.cssText = 'position:absolute;top:12px;left:50%;transform:translateX(-50%);z-index:450;padding:6px 14px;border-radius:20px;font-size:11px;font-weight:700;font-family:"Poppins",sans-serif;white-space:nowrap;max-width:88%;overflow:hidden;text-overflow:ellipsis;box-shadow:0 2px 10px rgba(0,0,0,.15);pointer-events:none;' + (styles[type] || styles.loading);
      el.textContent = msg;
      el.style.display = 'block';
    }
    function hideBadge() {
      document.getElementById('accBadge').style.display = 'none';
    }
  </script>
</body>
</html>

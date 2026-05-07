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

    /* ===== PROVIDER MARKER PULSE (Grab/Angkas style) ===== */
    @keyframes provPulse {
      0%   { transform: scale(0.8); opacity: 0.6; }
      70%  { transform: scale(1.6); opacity: 0;   }
      100% { transform: scale(0.8); opacity: 0;   }
    }

    /* Customer marker — home icon with subtle glow */
    .wfp-marker-customer-home {
      width: 40px; height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1A1A2E, #2D2D4E);
      border: 3px solid #fff;
      box-shadow: 0 3px 14px rgba(0,0,0,0.35);
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
    }

    /* Customer marker — pulsing home circle */
    .wfp-marker-customer-home {
      width: 44px; height: 44px;
      border-radius: 50%;
      background: linear-gradient(135deg, #1A1A2E, #2D2D4E);
      border: 3px solid #fff;
      box-shadow: 0 3px 14px rgba(0,0,0,0.4);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
      animation: custPulse 2.4s ease-in-out infinite;
    }
    @keyframes custPulse {
      0%, 100% { box-shadow: 0 3px 14px rgba(0,0,0,0.4), 0 0 0 0 rgba(26,26,46,0.4); }
      50%       { box-shadow: 0 3px 14px rgba(0,0,0,0.4), 0 0 0 10px rgba(26,26,46,0); }
    }

    /* GPS Permission Modal */
    #gpsModal {
      position: absolute; inset: 0; z-index: 900;
      background: rgba(0,0,0,0.55);
      backdrop-filter: blur(6px);
      display: flex; align-items: flex-end; justify-content: center;
      transition: opacity .3s;
    }
    #gpsModal.hidden { opacity: 0; pointer-events: none; }
    #gpsModalSheet {
      width: 100%; max-width: 480px;
      background: #fff;
      border-radius: 28px 28px 0 0;
      padding: 28px 24px 40px;
      font-family: 'Poppins', sans-serif;
      transform: translateY(0);
      transition: transform .35s cubic-bezier(.32,.72,0,1);
    }
    #gpsModal.hidden #gpsModalSheet { transform: translateY(100%); }
    .gps-modal-handle {
      width: 40px; height: 4px;
      background: #E0D8D0; border-radius: 2px;
      margin: 0 auto 20px;
    }
    .gps-modal-icon {
      width: 72px; height: 72px;
      border-radius: 50%;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      display: flex; align-items: center; justify-content: center;
      font-size: 34px;
      margin: 0 auto 16px;
      box-shadow: 0 6px 24px rgba(232,130,12,0.35);
    }
    .gps-modal-title {
      font-size: 20px; font-weight: 800;
      color: #1A1A2E; text-align: center; margin-bottom: 8px;
    }
    .gps-modal-sub {
      font-size: 13px; color: #7A7064;
      text-align: center; line-height: 1.6; margin-bottom: 24px;
    }
    .gps-modal-items {
      display: flex; flex-direction: column; gap: 10px;
      margin-bottom: 24px;
    }
    .gps-modal-item {
      display: flex; align-items: center; gap: 12px;
      background: #FAF8F5; border-radius: 14px;
      padding: 12px 14px;
    }
    .gps-modal-item-icon {
      width: 36px; height: 36px; border-radius: 50%;
      background: linear-gradient(135deg,#E8820C,#F5A623);
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; flex-shrink: 0;
    }
    .gps-modal-item-text { font-size: 13px; color: #1A1A2E; font-weight: 600; }
    .gps-modal-item-sub  { font-size: 11px; color: #9E9690; margin-top: 1px; }
    .gps-btn-allow {
      width: 100%; height: 52px; border-radius: 16px;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff; border: none; cursor: pointer;
      font-size: 15px; font-weight: 800;
      font-family: 'Poppins', sans-serif;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      box-shadow: 0 6px 20px rgba(232,130,12,0.38);
      margin-bottom: 10px;
      transition: transform .15s, box-shadow .15s;
    }
    .gps-btn-allow:active { transform: scale(0.97); }
    .gps-btn-skip {
      width: 100%; height: 44px; border-radius: 14px;
      background: transparent; color: #9E9690;
      border: none; cursor: pointer;
      font-size: 13px; font-weight: 600;
      font-family: 'Poppins', sans-serif;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
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

    <!-- GPS Permission Modal (hidden until boot() decides) -->
    <div id="gpsModal" class="hidden" style="display:none">
      <div id="gpsModalSheet">
        <div class="gps-modal-handle"></div>
        <div class="gps-modal-icon">📍</div>
        <div class="gps-modal-title">Enable Location Access</div>
        <div class="gps-modal-sub">
          HomeEase needs your location to show you on the map<br>and let your provider navigate to you.
        </div>
        <div class="gps-modal-items">
          <div class="gps-modal-item">
            <div class="gps-modal-item-icon">🚗</div>
            <div>
              <div class="gps-modal-item-text">Track your provider in real-time</div>
              <div class="gps-modal-item-sub">See exactly where they are on the map</div>
            </div>
          </div>
          <div class="gps-modal-item">
            <div class="gps-modal-item-icon">🏠</div>
            <div>
              <div class="gps-modal-item-text">Show your location to provider</div>
              <div class="gps-modal-item-sub">So they can navigate directly to you</div>
            </div>
          </div>
          <div class="gps-modal-item">
            <div class="gps-modal-item-icon">🔒</div>
            <div>
              <div class="gps-modal-item-text">Your location stays private</div>
              <div class="gps-modal-item-sub">Only shared with your assigned provider</div>
            </div>
          </div>
        </div>
        <button class="gps-btn-allow" id="btnAllowGps" onclick="requestGpsPermission()">
          <i class="bi bi-geo-alt-fill"></i> Enable GPS Location
        </button>
        <button class="gps-btn-skip" onclick="skipGps()">Use default location instead</button>
      </div>
    </div>

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

        <!-- Chat Button (shown when accepted) -->
        <div id="chatBtnWrap" style="display:none;padding:0 16px 8px;">
          <button onclick="openChat()" style="width:100%;height:46px;border-radius:13px;background:linear-gradient(135deg,#1A1A2E,#2D2D4E);color:#fff;border:none;cursor:pointer;font-size:13px;font-weight:800;font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="bi bi-chat-fill"></i> Message Provider <span id="chatUnreadBubble" style="background:#EF4444;border-radius:99px;padding:1px 7px;font-size:11px;display:none;"></span>
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

  <!-- Chat Drawer (inside shell to stay in mobile frame) -->
  <div id="chatOverlay" style="position:absolute;inset:0;z-index:800;background:rgba(0,0,0,.45);backdrop-filter:blur(3px);display:flex;align-items:flex-end;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s" onclick="closeChat(event)">
    <div id="chatDrawer" style="width:100%;max-width:480px;background:#fff;border-radius:24px 24px 0 0;padding-bottom:32px;display:flex;flex-direction:column;max-height:72dvh;transform:translateY(100%);transition:transform .32s cubic-bezier(.32,.72,0,1)" onclick="event.stopPropagation()">
      <div style="width:40px;height:4px;background:#E0D8D0;border-radius:2px;margin:12px auto 0"></div>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px 10px;border-bottom:1.5px solid #F0EAE0">
        <div style="font-size:15px;font-weight:800;color:#1A1A2E;font-family:'Poppins',sans-serif">💬 Chat with Provider</div>
        <button onclick="closeChat()" style="width:32px;height:32px;border-radius:50%;background:#F5F0EA;border:none;cursor:pointer;font-size:15px;color:#7A7064;display:flex;align-items:center;justify-content:center"><i class="bi bi-x-lg"></i></button>
      </div>
      <div id="chatMsgs" style="flex:1;overflow-y:auto;padding:14px 16px;display:flex;flex-direction:column;gap:8px">
        <div id="chatEmpty" style="text-align:center;color:#9E9690;font-size:13px;padding:24px 0">No messages yet. Say hello!</div>
      </div>
      <div style="display:flex;gap:8px;padding:10px 16px 4px;border-top:1.5px solid #F0EAE0">
        <textarea id="chatInput" rows="1" placeholder="Type a message…" style="flex:1;border:1.5px solid #E8E0D5;border-radius:22px;padding:10px 14px;font-size:13px;font-family:'Nunito',sans-serif;outline:none;resize:none;background:#FAFAF8" onkeydown="handleChatKey(event)"></textarea>
        <button onclick="sendMessage()" style="width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#E8820C,#F5A623);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;flex-shrink:0"><i class="bi bi-send-fill"></i></button>
      </div>
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

    /* ===== MAP STYLES ===== */
    const MAP_STYLES = {
      standard: {
        url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 19,
        subdomains: 'abc'
      },
      google: {
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

    /* =============================================
       GEO CONSTANTS — Sto. Tomas, Batangas
    ============================================= */
    const STO_TOMAS = {
      lat:  14.1053,
      lng:  121.1390,
      // Bounding box locked to Sto. Tomas municipality
      bounds: L.latLngBounds(
        L.latLng(13.9800, 120.9800),   // SW corner
        L.latLng(14.2500, 121.3200)    // NE corner
      ),
      zoom: 15,
      minZoom: 12,
      maxZoom: 19
    };

    /** Return true if a coordinate is within Sto. Tomas bounds */
    function isInStoTomas(lat, lng) {
      return STO_TOMAS.bounds.contains(L.latLng(lat, lng));
    }

    /** Clamp to Sto. Tomas center if coord is outside bounds */
    function clampToStoTomas(lat, lng) {
      if (isInStoTomas(lat, lng)) return { lat, lng };
      console.warn(`GPS (${lat},${lng}) outside Sto. Tomas — using default center.`);
      return { lat: STO_TOMAS.lat, lng: STO_TOMAS.lng };
    }

    /* =============================================
       STATE
    ============================================= */
    let currentStyleKey = localStorage.getItem('wfpMapStyle') || 'standard';
    let tileLayer = null;

    let map, customerMarker, providerMarker, routeLine;
    let pollTimer = null;
    let currentStatus = 'pending';
    let currentData = null;
    let providerPhone = '';
    let animFrame = null;

    // Map center — Sto. Tomas, Batangas default, updated by GPS
    let customerLat = STO_TOMAS.lat;
    let customerLng = STO_TOMAS.lng;
    let clientGpsLat = null, clientGpsLng = null;
    let accuracyCircle = null;
    let gpsWatchId = null;

    /* =============================================
       MAP INIT
    ============================================= */
    function initMap() {
      map = L.map('wfpMap', {
        zoomControl: false,
        attributionControl: true,
        tap: false,
        minZoom: STO_TOMAS.minZoom,
        maxZoom: STO_TOMAS.maxZoom,
        maxBounds: STO_TOMAS.bounds,      // lock pan to Sto. Tomas
        maxBoundsViscosity: 1.0           // hard boundary — no rubber-banding outside
      }).setView([STO_TOMAS.lat, STO_TOMAS.lng], STO_TOMAS.zoom);

      applyMapStyle(currentStyleKey, false);
      highlightStyleOpt(currentStyleKey);

      // Customer marker — will be moved to real GPS
      const custIcon = L.divIcon({
        className: '',
        html: `<div class="wfp-marker-customer-home">🏠</div>`,
        iconSize: [40, 40],
        iconAnchor: [20, 20]
      });
      customerMarker = L.marker([STO_TOMAS.lat, STO_TOMAS.lng], { icon: custIcon, zIndexOffset: 100 }).addTo(map);
      customerMarker.bindPopup('<b>📍 Your Location</b>');

      // Sync sheet height for recenter button positioning
      syncSheetHeight();
    }

    /* ── GPS Precision Engine ── */
    const GPS = {
      SMOOTH_ALPHA    : 0.25,   // EMA smoothing — lower = smoother
      MIN_MOVE_M      : 2,      // minimum movement to update marker (metres)
      REJECT_IP_BASED : 1000,   // anything ≥ 1km accuracy = IP-based, completely ignored
      ACCEPT_FIRST    : 200,    // first real GPS fix: accept if ≤ 200m
      ACCEPT_STEADY   : 80,     // once tracking: only accept ≤ 80m
    };
    let _gpsSmLat = null, _gpsSmLng = null;
    let _gpsBestAcc = Infinity;
    let _gpsHasFirst = false;    // true once a real (accurate) fix is applied

    function _gpsDist(lat1, lng1, lat2, lng2) {
      const R = 6371000, r = Math.PI / 180;
      const a = Math.sin((lat2-lat1)*r/2)**2
              + Math.cos(lat1*r) * Math.cos(lat2*r) * Math.sin((lng2-lng1)*r/2)**2;
      return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function _gpsSmooth(lat, lng) {
      if (_gpsSmLat === null) { _gpsSmLat = lat; _gpsSmLng = lng; }
      else {
        _gpsSmLat = GPS.SMOOTH_ALPHA * lat + (1 - GPS.SMOOTH_ALPHA) * _gpsSmLat;
        _gpsSmLng = GPS.SMOOTH_ALPHA * lng + (1 - GPS.SMOOTH_ALPHA) * _gpsSmLng;
      }
      return { lat: _gpsSmLat, lng: _gpsSmLng };
    }

    function _gpsOnFix(pos) {
      const rawLat = pos.coords.latitude;
      const rawLng = pos.coords.longitude;
      const acc    = pos.coords.accuracy;

      // ── STEP 1: Reject IP-based (wildly inaccurate) positions completely ──
      // These are cell-tower or IP lookups — never accurate enough to use
      if (acc >= GPS.REJECT_IP_BASED) {
        const km = (acc / 1000).toFixed(0);
        showGpsBanner('loading', `📡 Waiting for GPS signal… (accuracy: ±${km}km — too low)`);
        // Customer marker stays at Sto. Tomas default — do NOT move it
        return;
      }

      // ── STEP 2: Reject if outside Sto. Tomas, Batangas ──
      if (!isInStoTomas(rawLat, rawLng)) {
        showGpsBanner('error', '📍 GPS outside Sto. Tomas service area — using default.');
        return;
      }

      // ── STEP 3: Accuracy gate (tightens after first fix) ──
      const threshold = _gpsHasFirst ? GPS.ACCEPT_STEADY : GPS.ACCEPT_FIRST;
      if (acc > threshold) {
        showGpsBanner('loading', `⏳ Improving GPS accuracy… ±${Math.round(acc)}m`);
        return; // Don't update position — wait for better signal
      }

      // ── STEP 4: Smooth the position ──
      const s = _gpsSmooth(rawLat, rawLng);

      // ── STEP 5: Distance & accuracy filter ──
      const moved     = !_gpsHasFirst || _gpsDist(clientGpsLat, clientGpsLng, s.lat, s.lng) >= GPS.MIN_MOVE_M;
      const betterAcc = acc < _gpsBestAcc * 0.85;
      if (!moved && !betterAcc) return;

      // ── STEP 6: Accept this fix ──
      _gpsBestAcc  = Math.min(_gpsBestAcc, acc);
      _gpsHasFirst = true;
      clientGpsLat = s.lat;
      clientGpsLng = s.lng;
      customerLat  = s.lat;
      customerLng  = s.lng;

      customerMarker.setLatLng([s.lat, s.lng]);

      // Accuracy circle
      if (accuracyCircle) map.removeLayer(accuracyCircle);
      if (acc > 5) {
        accuracyCircle = L.circle([s.lat, s.lng], {
          radius: acc,
          color: '#1A1A2E', fillColor: '#1A1A2E',
          fillOpacity: 0.05, weight: 1, opacity: 0.3
        }).addTo(map);
      }

      // Re-centre map only on the very first real fix, and only if we haven't moved
      if (clientGpsLat === s.lat && !providerMarker) {
        map.setView([s.lat, s.lng], 17, { animate: true });
      } else if (providerMarker) {
        fitMap(providerMarker.getLatLng().lat, providerMarker.getLatLng().lng);
      }

      const label = acc < 5  ? `✅ ${Math.round(acc)}m — Excellent`
                  : acc < 15 ? `✅ ±${Math.round(acc)}m — High`
                  : acc < 80 ? `📍 ±${Math.round(acc)}m — Good`
                  :            `⚠️ ±${Math.round(acc)}m — Low`;
      showGpsBanner('success', label);
      setTimeout(hideGpsBanner, 5000);
    }

    function _gpsOnError(err) {
      const msgs = {
        1: '🔒 Location permission denied — showing Batangas default.',
        2: '📡 GPS unavailable — showing Batangas default.',
        3: '⏱ GPS signal timeout — retrying…'
      };
      showGpsBanner('error', msgs[err.code] || 'GPS error.');
    }

    function startClientGps() {
      if (!navigator.geolocation) {
        showGpsBanner('loading', '📡 No GPS on this device — using map default.');
        return;
      }

      showGpsBanner('loading', '📡 Acquiring GPS signal…');

      const opts = {
        enableHighAccuracy : true,
        maximumAge         : 0,
        timeout            : 25000
      };

      // Fast initial fix attempt, then continuous watch
      navigator.geolocation.getCurrentPosition(_gpsOnFix, _gpsOnError, { ...opts, timeout: 8000 });
      gpsWatchId = navigator.geolocation.watchPosition(_gpsOnFix, _gpsOnError, opts);
    }


    function showGpsBanner(type, msg) {
      let el = document.getElementById('gpsBanner');
      if (!el) {
        el = document.createElement('div');
        el.id = 'gpsBanner';
        el.style.cssText = 'position:absolute;top:90px;left:50%;transform:translateX(-50%);z-index:600;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:700;white-space:nowrap;max-width:90%;text-align:center;box-shadow:0 2px 12px rgba(0,0,0,0.18);pointer-events:none;font-family:"Poppins",sans-serif;';
        document.getElementById('wfpMap').parentElement.appendChild(el);
      }
      const styles = {
        loading : 'background:#FFF8F0;color:#E8820C;border:1.5px solid #FFE5B4;',
        error   : 'background:#FFF5F5;color:#EF4444;border:1.5px solid #FCA5A5;',
        success : 'background:#ECFDF5;color:#059669;border:1.5px solid #6EE7B7;'
      };
      el.style.cssText += styles[type] || styles.loading;
      el.textContent = msg;
      el.style.display = 'block';
    }

    function hideGpsBanner() {
      const el = document.getElementById('gpsBanner');
      if (el) el.style.display = 'none';
    }

    function syncSheetHeight() {
      const sheet = document.getElementById('wfpSheet');
      if (sheet) {
        const h = sheet.getBoundingClientRect().height;
        document.documentElement.style.setProperty('--sheet-h', h + 'px');
      }
    }

    /* Provider marker — animated Grab-style pulsing dot */
    function ensureProviderMarker(lat, lng, isAccepted) {
      const color = isAccepted ? '#E8820C' : '#6366F1';
      const emoji = isAccepted ? '🚗' : '🔍';
      const label = isAccepted ? 'On the Way' : 'Searching…';

      const provIcon = L.divIcon({
        className: '',
        html: `
          <div style="position:relative;width:44px;height:44px;">
            <!-- Outer pulse ring -->
            <div style="
              position:absolute;inset:-8px;
              border-radius:50%;
              background:${color};
              opacity:0.18;
              animation:provPulse 1.6s ease-out infinite;
            "></div>
            <!-- Mid ring -->
            <div style="
              position:absolute;inset:-2px;
              border-radius:50%;
              border:2px solid ${color};
              opacity:0.5;
              animation:provPulse 1.6s ease-out infinite 0.3s;
            "></div>
            <!-- Core dot -->
            <div style="
              width:44px;height:44px;
              border-radius:50%;
              background:linear-gradient(135deg,${color},#F5A623);
              border:3px solid #fff;
              box-shadow:0 3px 14px rgba(0,0,0,0.35);
              display:flex;align-items:center;justify-content:center;
              font-size:20px;
            ">${emoji}</div>
            <!-- Label -->
            <div style="
              position:absolute;top:48px;left:50%;transform:translateX(-50%);
              background:${color};color:#fff;
              font-size:9px;font-weight:800;
              padding:2px 7px;border-radius:10px;
              white-space:nowrap;
              font-family:'Poppins',sans-serif;
              box-shadow:0 2px 6px rgba(0,0,0,0.2);
            ">${label}</div>
          </div>`,
        iconSize: [44, 44],
        iconAnchor: [22, 22]
      });

      if (!providerMarker) {
        providerMarker = L.marker([lat, lng], { icon: provIcon, zIndexOffset: 200 }).addTo(map);
        providerMarker.bindPopup(`<b>${emoji} Provider</b><br>${label}`);
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
        if (t < 1) animFrame = requestAnimationFrame(step);
      }
      animFrame = requestAnimationFrame(step);
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

        // Update map — clamp coordinates to Sto. Tomas bounds
        const clampedCustomer = clampToStoTomas(
          parseFloat(data.customer_lat) || customerLat,
          parseFloat(data.customer_lng) || customerLng
        );
        customerLat = clampedCustomer.lat;
        customerLng = clampedCustomer.lng;
        customerMarker.setLatLng([customerLat, customerLng]);

        if (data.provider_lat && data.provider_lng) {
          const rawPLat = parseFloat(data.provider_lat);
          const rawPLng = parseFloat(data.provider_lng);
          const clampedProvider = clampToStoTomas(rawPLat, rawPLng);
          const pLat = clampedProvider.lat;
          const pLng = clampedProvider.lng;
          const wasVisible = !!providerMarker;
          ensureProviderMarker(pLat, pLng, data.status === 'progress' || data.has_provider);

          // Auto-fit to show both markers when provider first appears
          if (!wasVisible) {
            fitMap(pLat, pLng);
          }
          fetchRouteAndDraw(pLat, pLng, customerLat, customerLng);
        }

        currentStatus = data.status;

        // Stop polling if terminal state
        if (['done', 'cancelled'].includes(data.status)) {
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
        topBarTitle.textContent = 'Provider On the Way';
        banner.className = 'wfp-status-banner accepted';
        spinner.style.display = 'none';
        statusTxt.innerHTML = `Your provider is on the way! <span>🏃</span>`;
        cancelWrap.style.display = 'none';
        tipsSection.style.display = 'none';
        document.getElementById('chatBtnWrap').style.display = 'block';

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

      } else if (data.status === 'done') {
        topBarTitle.textContent = 'Service Complete';
        banner.className = 'wfp-status-banner done';
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
        openChat();
      }
    }

    /* ===== CHAT ===== */
    let chatLastId = 0, chatTimer = null, chatOpen = false, myRole = 'client';

    function openChat() {
      chatOpen = true;
      const ov = document.getElementById('chatOverlay');
      const dr = document.getElementById('chatDrawer');
      ov.style.opacity = '1'; ov.style.pointerEvents = 'all';
      dr.style.transform = 'translateY(0)';
      document.getElementById('chatUnreadBubble').style.display = 'none';
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

    function startChat() {
      fetchChatMessages();
      chatTimer = setInterval(fetchChatMessages, 3000);
    }

    async function fetchChatMessages() {
      try {
        const res  = await fetch('../api/chat_api.php?booking_id=' + BOOKING_ID + '&after_id=' + chatLastId + '&_t=' + Date.now(), { cache:'no-store' });
        const data = await res.json();
        if (!data.success) return;
        myRole = data.my_role || 'client';
        if (data.messages && data.messages.length) {
          appendChatMessages(data.messages);
          chatLastId = data.messages[data.messages.length-1].id;
        }
        if (data.unread > 0 && !chatOpen) {
          const b = document.getElementById('chatUnreadBubble');
          b.textContent = data.unread;
          b.style.display = 'inline';
        }
      } catch(e) {}
    }

    function appendChatMessages(msgs) {
      const box = document.getElementById('chatMsgs');
      const empty = document.getElementById('chatEmpty');
      if (empty) empty.remove();
      msgs.forEach(m => {
        const mine = m.sender_role === myRole;
        const div  = document.createElement('div');
        div.style.cssText = `max-width:78%;padding:9px 13px;border-radius:18px;font-size:13px;line-height:1.45;word-break:break-word;${mine ? 'background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;align-self:flex-end;border-bottom-right-radius:4px;' : 'background:#F5F0EA;color:#1A1A2E;align-self:flex-start;border-bottom-left-radius:4px;'}`;
        const t = new Date(m.created_at).toLocaleTimeString('en-US',{hour:'numeric',minute:'2-digit'});
        div.innerHTML = escHtml(m.message) + `<div style="font-size:10px;opacity:.6;margin-top:3px;text-align:right">${t}</div>`;
        box.appendChild(div);
      });
      scrollChat();
    }

    function scrollChat() {
      const box = document.getElementById('chatMsgs');
      box.scrollTop = box.scrollHeight;
    }

    async function sendMessage() {
      const inp = document.getElementById('chatInput');
      const msg = inp.value.trim();
      if (!msg) return;
      inp.value = '';
      const fd = new FormData();
      fd.append('action','send'); fd.append('booking_id',BOOKING_ID); fd.append('message',msg);
      try {
        const res  = await fetch('../api/chat_api.php', { method:'POST', body:fd });
        const data = await res.json();
        if (data.success) fetchChatMessages();
      } catch(e) {}
    }

    function handleChatKey(e) { if (e.key==='Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); } }
    function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

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
    async function boot() {
      initMap();
      startPolling();
      startChat();
      window.addEventListener('resize', syncSheetHeight);
      window.addEventListener('popstate', goBack);

      // Check if we already have GPS permission
      if (navigator.permissions) {
        try {
          const perm = await navigator.permissions.query({ name: 'geolocation' });
          if (perm.state === 'granted') {
            // Already allowed — skip modal, start GPS directly
            hideGpsModal();
            startClientGps();
            return;
          } else if (perm.state === 'denied') {
            // Already denied — hide modal, show banner
            hideGpsModal();
            showGpsBanner('error', '🔒 Location blocked — enable it in browser settings.');
            return;
          }
        } catch(e) { /* permissions API not fully supported */ }
      }

      // Show modal to ask for GPS
      const m = document.getElementById('gpsModal');
      m.style.display = 'flex';
      requestAnimationFrame(() => m.classList.remove('hidden'));
    }

    function requestGpsPermission() {
      const btn = document.getElementById('btnAllowGps');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat" style="animation:spin 1s linear infinite"></i> Requesting…';

      // This call triggers the browser’s native permission prompt
      navigator.geolocation.getCurrentPosition(
        (pos) => {
          hideGpsModal();
          _gpsOnFix(pos);        // process this first fix immediately
          startClientGps();       // then start continuous watch
        },
        (err) => {
          hideGpsModal();
          const msgs = {
            1: '🔒 Location denied. Enable GPS in your browser settings, then refresh.',
            2: '📡 GPS unavailable. Using default Batangas location.',
            3: '⏱ GPS timed out. Using default location.'
          };
          showGpsBanner('error', msgs[err.code] || 'Could not get location.');
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
      );
    }

    function skipGps() {
      hideGpsModal();
      showGpsBanner('loading', '📍 Using Sto. Tomas, Batangas as your location.');
      setTimeout(hideGpsBanner, 4000);
      // Start watch anyway in the background — if GPS improves, it'll update
      startClientGps();
    }

    function hideGpsModal() {
      const m = document.getElementById('gpsModal');
      m.classList.add('hidden');
      setTimeout(() => { if (m.classList.contains('hidden')) m.style.display = 'none'; }, 400);
    }

    document.addEventListener('DOMContentLoaded', boot);
  </script>
</body>
</html>

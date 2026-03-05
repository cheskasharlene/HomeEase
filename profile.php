<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once 'api/db.php';
// ✅ Fetch address too
$stmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row) {
  $_SESSION['user_name'] = $row['name'];
  $_SESSION['user_email'] = $row['email'];
  $_SESSION['user_phone'] = $row['phone'];
  $_SESSION['user_address'] = $row['address'] ?? '';
}

$bstmt = $conn->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$bstmt->bind_param("i", $_SESSION['user_id']);
$bstmt->execute();
$bstmt->bind_result($bookingCount);
$bstmt->fetch();
$bstmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/profile.css" rel="stylesheet">
  <style>
    /* ── PIN change overlay ── */
    .pcd {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, .4);
      background: transparent;
      transition: background .18s, border-color .18s, transform .15s;
    }

    .pcd.filled {
      background: #fff;
      border-color: #fff;
      transform: scale(1.15);
    }

    .pcd.shake {
      animation: pcshake .42s ease;
      background: #ff6b6b;
      border-color: #ff6b6b;
    }

    @keyframes pcshake {

      0%,
      100% {
        transform: translateX(0)
      }

      25% {
        transform: translateX(-7px)
      }

      75% {
        transform: translateX(7px)
      }
    }

    .pck {
      aspect-ratio: 1;
      border-radius: 50%;
      border: 1.5px solid rgba(255, 255, 255, .22);
      background: rgba(255, 255, 255, .08);
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 400;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .12s, transform .1s;
    }

    .pck:active {
      background: rgba(255, 255, 255, .25);
      transform: scale(.9);
    }

    #pinChangeOverlay.visible {
      display: flex !important;
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

    <div class="screen" id="profile">
      <div class="p-scroll">
        <div class="p-hdr">
          <div class="p-hdr-back" onclick="goPage('home.php')"><i class="bi bi-arrow-left"></i></div>
          <img class="p-avatar" id="profileAvatar"
            src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=200&q=80" alt="Avatar">
          <div class="p-name" id="profileName">Loading…</div>
          <div class="p-email" id="profileEmail"></div>
          <div class="p-badges">
            <div class="p-badge">
              <svg viewBox="0 0 16 16" fill="none" width="12" height="12">
                <path d="M8 1l1.8 4H14l-3.3 2.4 1.2 4L8 9 4.1 11.4l1.2-4L2 5h4.2L8 1z" fill="#fff" />
              </svg>
              Verified Member
            </div>
            <div class="p-badge" id="profileAddressBadge">
              <svg viewBox="0 0 16 16" fill="none" width="12" height="12">
                <path d="M8 2a6 6 0 100 12A6 6 0 008 2zm0 10a4 4 0 110-8 4 4 0 010 8z" fill="#fff" />
                <circle cx="8" cy="8" r="2" fill="#fff" />
              </svg>
              <span id="profileAddressShort">No address set</span>
            </div>
          </div>
        </div>

        <div class="p-stats">
          <div class="p-stat">
            <div class="p-stat-val" id="statBookings">0</div>
            <div class="p-stat-lbl">Bookings</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val">4.9</div>
            <div class="p-stat-lbl">Rating</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val">2</div>
            <div class="p-stat-lbl">Saved</div>
          </div>
        </div>

        <div class="p-body">
          <div class="p-sec">
            <div class="p-sec-ttl">Account</div>
            <div class="p-row" onclick="openSettings('profile')">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 12a5 5 0 100-10 5 5 0 000 10z" stroke="#0D9488" stroke-width="2" />
                  <path d="M3 21c0-4.4 4-8 9-8s9 3.6 9 8" stroke="#0D9488" stroke-width="2" stroke-linecap="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Edit Profile</div>
                <div class="p-row-sub">Update name, email, phone & address</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row" onclick="openSettings('security')">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <rect x="5" y="11" width="14" height="10" rx="2" stroke="#0D9488" stroke-width="2" />
                  <path d="M8 11V7a4 4 0 118 0v4" stroke="#0D9488" stroke-width="2" stroke-linecap="round" />
                  <circle cx="12" cy="16" r="1.5" fill="#0D9488" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Security & PIN</div>
                <div class="p-row-sub">Change password & PIN</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row" onclick="openSettings('address')">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 2a8 8 0 00-8 8c0 5.5 8 13 8 13s8-7.5 8-13a8 8 0 00-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z"
                    stroke="#0D9488" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Saved Addresses</div>
                <div class="p-row-sub" id="addressRowSub">Manage delivery addresses</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
          </div>

          <div class="p-sec">
            <div class="p-sec-ttl">Preferences</div>
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M18 8a6 6 0 00-12 0v4l-2 4h16l-2-4V8z" stroke="#0D9488" stroke-width="2"
                    stroke-linejoin="round" />
                  <path d="M10 18a2 2 0 004 0" stroke="#0D9488" stroke-width="2" stroke-linecap="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Push Notifications</div>
                <div class="p-row-sub">Booking alerts & updates</div>
              </div>
              <div class="toggle-wrap">
                <div class="toggle on" onclick="this.classList.toggle('on')"></div>
              </div>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M3 8l9-5 9 5v10l-9 5-9-5V8z" stroke="#0D9488" stroke-width="2" stroke-linejoin="round" />
                  <path d="M3 8l9 5 9-5M12 13v9" stroke="#0D9488" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Email Updates</div>
                <div class="p-row-sub">Receive booking confirmations</div>
              </div>
              <div class="toggle-wrap">
                <div class="toggle on" onclick="this.classList.toggle('on')"></div>
              </div>
            </div>
          </div>

          <div class="p-sec">
            <div class="p-sec-ttl">Support</div>
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="9" stroke="#0D9488" stroke-width="2" />
                  <path d="M9 9a3 3 0 015.8 1c0 2-3 3-3 3" stroke="#0D9488" stroke-width="2" stroke-linecap="round" />
                  <circle cx="12" cy="17" r="1" fill="#0D9488" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Help Center</div>
                <div class="p-row-sub">FAQs & support articles</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2v10z" stroke="#0D9488"
                    stroke-width="2" stroke-linejoin="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Contact Us</div>
                <div class="p-row-sub">Chat or call support</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
          </div>

          <button class="logout-btn" onclick="doLogout()">
            <i class="bi bi-box-arrow-right" style="margin-right:8px;"></i>Log Out
          </button>
        </div>
      </div>
      <div id="navContainer"></div>
    </div>

    <!-- SETTINGS MODAL -->
    <div id="settingsModal" onclick="settingsBg(event)">
      <div class="s-sheet">
        <div class="s-hand"></div>
        <div style="margin-bottom:4px;">
          <button onclick="closeSettings()"
            style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:6px;color:var(--teal,#0D9488);font-size:14px;font-weight:600;padding:4px 0;font-family:inherit;">
            <i class="bi bi-arrow-left" style="font-size:16px;"></i> Back
          </button>
        </div>
        <div class="s-ttl" id="settingsTitle">Edit Profile</div>

        <!-- Alert inside modal -->
        <div id="modalAlert"
          style="display:none;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:600;margin-bottom:12px;">
        </div>

        <div class="s-avatar-row">
          <div class="s-avatar-wrap">
            <img id="settingsAvatar" src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=200&q=80"
              alt="Avatar">
            <div class="s-avatar-edit"><i class="bi bi-camera-fill"></i></div>
          </div>
          <div class="s-avatar-lbl">Change photo</div>
        </div>

        <!-- PROFILE SECTION -->
        <div id="profileSection">
          <div class="s-sec-ttl">Personal Information</div>
          <div class="s-fg"><label class="s-lbl">Full Name</label>
            <div class="s-iw"><i class="bi bi-person-fill s-ico"></i><input type="text" class="s-fi" id="s_name"
                placeholder="Full name"></div>
          </div>
          <div class="s-fg"><label class="s-lbl">Email Address</label>
            <div class="s-iw"><i class="bi bi-envelope-fill s-ico"></i><input type="email" class="s-fi" id="s_email"
                placeholder="your@email.com"></div>
          </div>
          <div class="s-fg"><label class="s-lbl">Phone Number</label>
            <div class="s-iw"><i class="bi bi-phone-fill s-ico"></i><input type="tel" class="s-fi" id="s_phone"
                placeholder="09xx xxx xxxx"></div>
          </div>
          <div class="s-fg"><label class="s-lbl">Home Address</label>
            <div class="s-iw"><i class="bi bi-house-fill s-ico"></i><input type="text" class="s-fi" id="s_addr"
                placeholder="e.g. 123 Mauban, Quezon"></div>
          </div>
        </div>

        <!-- SECURITY SECTION -->
        <div id="securitySection" style="display:none;">

          <!-- CHANGE PASSWORD -->
          <div class="s-sec-ttl">Change Password</div>
          <div class="s-fg"><label class="s-lbl">Current Password</label>
            <div class="s-iw"><i class="bi bi-lock-fill s-ico"></i><input type="password" class="s-fi" id="s_cpwd"
                placeholder="Current password"><i class="bi bi-eye-fill s-eye" onclick="tPwd('s_cpwd',this)"></i></div>
          </div>
          <div class="s-fg"><label class="s-lbl">New Password</label>
            <div class="s-iw"><i class="bi bi-lock-fill s-ico"></i><input type="password" class="s-fi" id="s_npwd"
                placeholder="New password"><i class="bi bi-eye-fill s-eye" onclick="tPwd('s_npwd',this)"></i></div>
          </div>
          <div class="s-fg"><label class="s-lbl">Confirm New Password</label>
            <div class="s-iw"><i class="bi bi-lock-fill s-ico"></i><input type="password" class="s-fi" id="s_cpwd2"
                placeholder="Confirm new password"><i class="bi bi-eye-fill s-eye" onclick="tPwd('s_cpwd2',this)"></i>
            </div>
          </div>

          <!-- CHANGE PIN -->
          <div style="height:1px;background:var(--border);margin:18px 0;"></div>
          <div class="s-sec-ttl">Change PIN Code</div>
          <div
            style="background:var(--tbg);border-radius:14px;padding:12px 14px;margin-bottom:14px;font-size:12px;color:var(--tm);display:flex;align-items:center;gap:8px;">
            <i class="bi bi-shield-lock-fill" style="color:var(--teal,#0D9488);font-size:16px;"></i>
            Your 4-digit PIN is used to verify your identity
          </div>
          <button type="button" onclick="openPinChange()"
            style="width:100%;padding:13px;border-radius:14px;border:2px dashed var(--teal,#0D9488);background:var(--teal-xlt,#f0fdf9);color:var(--teal,#0D9488);font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
            <i class="bi bi-shield-lock-fill"></i> Change PIN Code
          </button>
          <div id="pinChangeStatus"
            style="display:none;margin-top:10px;padding:10px 14px;border-radius:10px;font-size:12px;font-weight:700;background:#d1fae5;color:#065f46;text-align:center;">
            ✓ PIN updated successfully!
          </div>
        </div>

        <!-- PIN CHANGE OVERLAY -->
        <div id="pinChangeOverlay"
          style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(6,30,27,.7);backdrop-filter:blur(6px);align-items:center;justify-content:center;">
          <div
            style="background:#0b3d38;background-image:radial-gradient(circle at 20% 30%,rgba(13,200,180,.2) 0%,transparent 55%);width:100%;max-width:360px;border-radius:28px;overflow:hidden;padding-bottom:32px;box-shadow:0 40px 80px rgba(0,0,0,.5);">
            <!-- Top bar -->
            <div style="display:flex;align-items:center;padding:18px 20px 0;">
              <button onclick="closePinChange()"
                style="background:none;border:none;color:rgba(255,255,255,.6);font-size:22px;cursor:pointer;line-height:1;"><i
                  class="bi bi-arrow-left"></i></button>
            </div>
            <!-- Header -->
            <div style="text-align:center;padding:16px 24px 8px;">
              <div
                style="width:60px;height:60px;border-radius:16px;background:rgba(255,255,255,.12);border:1.5px solid rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                <i class="bi bi-shield-lock-fill" style="font-size:24px;color:#fff;"></i>
              </div>
              <div id="pinChgTitle"
                style="font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;letter-spacing:.18em;text-transform:uppercase;color:#fff;margin-bottom:6px;">
                Set New PIN</div>
              <div id="pinChgSub"
                style="font-family:'Poppins',sans-serif;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.5);">
                Enter your new 4-digit PIN</div>
            </div>
            <!-- Dots -->
            <div style="display:flex;gap:18px;justify-content:center;padding:24px 0 20px;">
              <div class="pcd" id="pcd0"></div>
              <div class="pcd" id="pcd1"></div>
              <div class="pcd" id="pcd2"></div>
              <div class="pcd" id="pcd3"></div>
            </div>
            <!-- Keypad -->
            <div style="padding:0 32px;">
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;max-width:260px;margin:0 auto;">
                <?php foreach (['1', '2', '3', '4', '5', '6', '7', '8', '9'] as $k): ?>
                  <button class="pck" onclick="pcKey('<?= $k ?>')"><?= $k ?></button>
                <?php endforeach; ?>
                <div></div>
                <button class="pck" onclick="pcKey('0')">0</button>
                <button class="pck" onclick="pcDel()"><i class="bi bi-delete-left-fill"></i></button>
              </div>
            </div>
          </div>
        </div>

        <!-- ADDRESS SECTION -->
        <div id="addressSection" style="display:none;">
          <div class="s-sec-ttl">Saved Addresses</div>
          <div
            style="background:var(--tbg);border-radius:14px;padding:14px 16px;margin-bottom:14px;display:flex;align-items:center;gap:12px;">
            <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
              <path d="M12 2a8 8 0 00-8 8c0 5.5 8 13 8 13s8-7.5 8-13a8 8 0 00-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z"
                fill="#0D9488" />
            </svg>
            <div>
              <div style="font-size:13px;font-weight:700;color:var(--td);">Home</div>
              <div style="font-size:12px;color:var(--tm);" id="savedAddrDisplay">No address saved yet</div>
            </div>
          </div>
          <div class="s-fg"><label class="s-lbl">Update Home Address</label>
            <div class="s-iw"><i class="bi bi-geo-alt-fill s-ico"></i><input type="text" class="s-fi" id="s_newaddr"
                placeholder="Enter new address"></div>
          </div>
        </div>

        <button class="btn-p save-btn" id="saveBtn" onclick="saveSettings()">Save Changes</button>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    initTheme();

    window.HE = window.HE || {};
    window.HE.user = {
      name: <?= json_encode($_SESSION['user_name'] ?? '') ?>,
      email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
      phone: <?= json_encode($_SESSION['user_phone'] ?? '') ?>,
      address: <?= json_encode($_SESSION['user_address'] ?? '') ?>
    };

    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('bookings.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('bookings.php?newbooking=1')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
        <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;

    function loadProfile() {
      const u = window.HE.user;
      document.getElementById('profileName').textContent = u.name || 'User';
      document.getElementById('profileEmail').textContent = u.email || '';
      document.getElementById('statBookings').textContent = <?= (int) $bookingCount ?>;

      // Address badge
      const shortAddr = u.address ? (u.address.length > 22 ? u.address.slice(0, 22) + '…' : u.address) : 'No address set';
      document.getElementById('profileAddressShort').textContent = shortAddr;
      document.getElementById('addressRowSub').textContent = u.address || 'Manage delivery addresses';
      document.getElementById('savedAddrDisplay').textContent = u.address || 'No address saved yet';

      // Pre-fill profile fields
      document.getElementById('s_name').value = u.name;
      document.getElementById('s_email').value = u.email;
      document.getElementById('s_phone').value = u.phone;
      document.getElementById('s_addr').value = u.address;
      document.getElementById('s_newaddr').value = u.address;
    }

    let settingsSection = 'profile';

    /* ── PIN Change Pad ── */
    let _pcStep = 'set', _pcFirst = '', _pcBuf = '';

    function openPinChange() {
      _pcStep = 'set'; _pcFirst = ''; _pcBuf = '';
      document.getElementById('pinChgTitle').textContent = 'Set New PIN';
      document.getElementById('pinChgSub').textContent = 'Enter your new 4-digit PIN';
      syncPcd();
      document.getElementById('pinChangeOverlay').classList.add('visible');
    }
    function closePinChange() {
      document.getElementById('pinChangeOverlay').classList.remove('visible');
      _pcBuf = ''; _pcFirst = ''; _pcStep = 'set';
    }
    function syncPcd() {
      for (let i = 0; i < 4; i++) {
        const d = document.getElementById('pcd' + i);
        d.classList.toggle('filled', i < _pcBuf.length);
        d.classList.remove('shake');
      }
    }
    function pcShakeErr() {
      for (let i = 0; i < 4; i++) document.getElementById('pcd' + i).classList.add('shake');
      setTimeout(() => { _pcBuf = ''; syncPcd(); }, 500);
    }
    function pcKey(d) {
      if (_pcBuf.length >= 4) return;
      _pcBuf += d; syncPcd();
      if (_pcBuf.length === 4) setTimeout(onPcFull, 220);
    }
    function pcDel() {
      if (_pcBuf.length) { _pcBuf = _pcBuf.slice(0, -1); syncPcd(); }
    }
    function onPcFull() {
      if (_pcStep === 'set') {
        _pcFirst = _pcBuf; _pcBuf = ''; _pcStep = 'confirm';
        document.getElementById('pinChgTitle').textContent = 'Confirm PIN';
        document.getElementById('pinChgSub').textContent = 'Re-enter your new PIN to confirm';
        syncPcd();
      } else {
        if (_pcBuf === _pcFirst) {
          savePinChange(_pcBuf);
        } else {
          pcShakeErr();
          setTimeout(() => {
            _pcStep = 'set'; _pcFirst = '';
            document.getElementById('pinChgTitle').textContent = 'Set New PIN';
            document.getElementById('pinChgSub').textContent = "PINs didn't match — try again";
          }, 560);
        }
      }
    }
    async function savePinChange(pin) {
      closePinChange();
      const fd = new FormData();
      fd.append('section', 'pin');
      fd.append('pin', pin);
      try {
        const res = await fetch('api/profile_api.php', { method: 'POST', body: fd });
        const data = await res.json();
        const el = document.getElementById('pinChangeStatus');
        el.style.display = 'block';
        if (data.success) {
          el.style.background = '#d1fae5'; el.style.color = '#065f46';
          el.textContent = '✓ PIN updated successfully!';
        } else {
          el.style.background = '#fee2e2'; el.style.color = '#991b1b';
          el.textContent = '✗ ' + (data.message || 'Failed to update PIN.');
        }
        setTimeout(() => { el.style.display = 'none'; }, 3000);
      } catch (e) {
        alert('Network error saving PIN.');
      }
    }

    function openSettings(section) {
      section = section || 'profile';
      settingsSection = section;
      hideModalAlert();
      document.getElementById('settingsModal').classList.add('on');
      document.getElementById('profileSection').style.display = section === 'profile' ? 'block' : 'none';
      document.getElementById('securitySection').style.display = section === 'security' ? 'block' : 'none';
      document.getElementById('addressSection').style.display = section === 'address' ? 'block' : 'none';
      const titles = { profile: 'Edit Profile', security: 'Security & PIN', address: 'Saved Addresses' };
      document.getElementById('settingsTitle').textContent = titles[section] || 'Settings';
    }

    function settingsBg(e) {
      if (e.target === document.getElementById('settingsModal')) closeSettings();
    }
    function closeSettings() {
      document.getElementById('settingsModal').classList.remove('on');
    }

    function showModalAlert(msg, type) {
      const el = document.getElementById('modalAlert');
      el.style.display = 'block';
      el.style.background = type === 'success' ? '#d1fae5' : '#fee2e2';
      el.style.color = type === 'success' ? '#065f46' : '#991b1b';
      el.textContent = msg;
    }
    function hideModalAlert() {
      document.getElementById('modalAlert').style.display = 'none';
    }

    function tPwd(id, btn) {
      const inp = document.getElementById(id);
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.className = show ? 'bi bi-eye-slash-fill s-eye' : 'bi bi-eye-fill s-eye';
    }

    async function saveSettings() {
      const btn = document.getElementById('saveBtn');
      btn.disabled = true; btn.textContent = 'Saving…';
      hideModalAlert();

      const fd = new FormData();

      if (settingsSection === 'profile') {
        fd.append('section', 'profile');
        fd.append('name', document.getElementById('s_name').value.trim());
        fd.append('email', document.getElementById('s_email').value.trim());
        fd.append('phone', document.getElementById('s_phone').value.trim());
        fd.append('address', document.getElementById('s_addr').value.trim());

      } else if (settingsSection === 'security') {
        fd.append('section', 'security');
        fd.append('current_password', document.getElementById('s_cpwd').value);
        fd.append('new_password', document.getElementById('s_npwd').value);
        fd.append('confirm_password', document.getElementById('s_cpwd2').value);

      } else if (settingsSection === 'address') {
        fd.append('section', 'address');
        fd.append('address', document.getElementById('s_newaddr').value.trim());
      }

      try {
        const res = await fetch('api/profile_api.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          // Update local state
          if (settingsSection === 'profile') {
            window.HE.user.name = document.getElementById('s_name').value.trim();
            window.HE.user.email = document.getElementById('s_email').value.trim();
            window.HE.user.phone = document.getElementById('s_phone').value.trim();
            window.HE.user.address = document.getElementById('s_addr').value.trim();
          } else if (settingsSection === 'address') {
            window.HE.user.address = document.getElementById('s_newaddr').value.trim();
          }
          loadProfile();
          showModalAlert('✓ ' + data.message, 'success');
          setTimeout(() => closeSettings(), 1200);
        } else {
          showModalAlert(data.message || 'Something went wrong.', 'error');
        }
      } catch (e) {
        showModalAlert('Network error. Please try again.', 'error');
      }

      btn.disabled = false; btn.textContent = 'Save Changes';
    }

    loadProfile();

    function doLogout() { window.location.href = 'logout.php'; }
  </script>
</body>

</html>
<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once '../api/db.php';
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

$scriptDir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF'] ?? ''));
$scriptDir = rtrim($scriptDir, '/');
if ($scriptDir === '' || $scriptDir === '.') {
  $scriptDir = '';
}
if (substr($scriptDir, -8) === '/clients') {
  $appBase = substr($scriptDir, 0, -8);
} else {
  $appBase = $scriptDir;
}
if ($appBase === '') {
  $appBase = '/';
}

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
  <link href="<?= htmlspecialchars(rtrim($appBase, '/')) ?>/assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
  <link href="<?= htmlspecialchars(rtrim($appBase, '/')) ?>/assets/css/profile.css?v=<?= time() ?>" rel="stylesheet">
  <style>
    /* ── Profile Page Critical CSS ── */

    /* Screen fix - hide all screens but show the profile screen (only one on this page) */
    .screen {
      display: none !important;
      position: absolute;
      inset: 0;
      overflow: hidden;
      background: var(--bg-screen);
      flex-direction: column;
      align-items: stretch !important;
      justify-content: flex-start !important;
    }

    /* Profile screen layout */
    #profile {
      display: flex !important;
      flex-direction: column;
      align-items: stretch;
      justify-content: flex-start;
      position: absolute !important;
      inset: 0;
    }

    /* Profile main scroll - ensure it doesn't overlap navbar */
    .p-scroll {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding-bottom: 96px;
      scrollbar-width: none;
      position: relative;
      z-index: 1;
    }

    .p-scroll::-webkit-scrollbar {
      display: none;
    }

    /* Profile header */
    .p-hdr {
      background: linear-gradient(145deg, #C86500 0%, #E8820C 25%, #F5A623 60%, #FFB347 100%);
      padding: 52px 22px 24px;
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
    }

    .p-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.08) 1px, transparent 0);
      background-size: 20px 20px;
      pointer-events: none;
    }

    .p-hdr-back {
      position: absolute;
      top: 52px;
      left: 18px;
      width: 38px;
      height: 38px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #fff;
      font-size: 17px;
      z-index: 1;
      transition: background 0.2s;
    }

    .p-hdr-back:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .p-hdr-settings {
      position: absolute;
      top: 52px;
      right: 18px;
      width: 38px;
      height: 38px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #fff;
      font-size: 17px;
      z-index: 1;
      transition: background 0.2s;
    }

    .p-hdr-settings:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .p-avatar {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      border: 3.5px solid rgba(255, 255, 255, 0.8);
      object-fit: cover;
      position: relative;
      z-index: 1;
      box-shadow: 0 8px 28px rgba(0, 0, 0, 0.22);
      margin-bottom: 12px;
    }

    .p-name {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      position: relative;
      z-index: 1;
      margin-bottom: 3px;
    }

    .p-email {
      font-size: 13px;
      color: rgba(255, 255, 255, 0.8);
      position: relative;
      z-index: 1;
      margin-bottom: 12px;
    }

    .p-badges {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      justify-content: center;
      position: relative;
      z-index: 1;
    }

    .p-badge {
      display: flex;
      align-items: center;
      gap: 5px;
      background: rgba(255, 255, 255, 0.22);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255, 255, 255, 0.35);
      border-radius: 20px;
      padding: 5px 12px;
      font-size: 11px;
      font-weight: 700;
      color: #fff;
    }

    /* Profile body */
    .p-body {
      padding: 18px 18px 0;
    }

    .p-sec {
      margin-bottom: 16px;
    }

    .p-sec-ttl {
      font-size: 10px;
      font-weight: 800;
      color: var(--txt-muted);
      text-transform: uppercase;
      letter-spacing: 0.9px;
      margin-bottom: 8px;
      padding-left: 4px;
    }

    /* Profile rows */
    .p-row {
      display: flex !important;
      align-items: center !important;
      gap: 14px;
      padding: 14px 16px;
      background: var(--bg-card);
      border-radius: 16px;
      margin-bottom: 8px;
      cursor: pointer;
      border: 1.5px solid var(--border-col);
      transition: background 0.15s, transform 0.15s, box-shadow 0.15s;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }

    .p-row:hover {
      background: var(--teal-bg);
      transform: translateX(2px);
      box-shadow: 0 4px 14px rgba(232, 130, 12, 0.1);
    }

    .p-row:active {
      transform: scale(0.98);
    }

    /* Row icon container - CRITICAL: constrains SVG size */
    .p-row-ic {
      width: 44px !important;
      height: 44px !important;
      min-width: 44px !important;
      min-height: 44px !important;
      max-width: 44px !important;
      max-height: 44px !important;
      border-radius: 13px;
      background: linear-gradient(135deg, #FFE5B4, #FFF8F0);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }

    .p-row-ic svg {
      width: 22px !important;
      height: 22px !important;
      flex-shrink: 0;
    }

    .p-row-info {
      flex: 1;
      min-width: 0;
    }

    .p-row-lbl {
      font-size: 14px;
      font-weight: 700;
      color: var(--txt-primary);
    }

    .p-row-sub {
      font-size: 12px;
      color: var(--txt-muted);
      margin-top: 2px;
    }

    .p-row-arrow {
      color: #d1d5db;
      font-size: 15px;
      flex-shrink: 0;
    }

    /* Form styles (inside subSheet) */
    .s-fg {
      margin-bottom: 14px;
    }

    .s-lbl {
      font-size: 12px;
      font-weight: 700;
      color: var(--txt-primary);
      margin-bottom: 6px;
      display: block;
    }

    .s-iw {
      position: relative;
    }

    .s-ico {
      position: absolute;
      left: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--teal);
      font-size: 16px;
    }

    .s-fi {
      width: 100%;
      padding: 13px 15px 13px 40px;
      border: 2px solid var(--border-col);
      border-radius: 13px;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      outline: none;
      background: var(--bg-input, #F7F3EE);
      color: var(--txt-primary);
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .s-fi:focus {
      border-color: var(--teal);
      box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.12);
    }

    .s-eye {
      position: absolute;
      right: 13px;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      font-size: 16px;
      cursor: pointer;
    }

    .save-btn {
      margin-top: 8px;
    }

    /* Loading splash */
    #ml {
      position: absolute;
      inset: 0;
      background: linear-gradient(145deg, #E8820C 0%, #F5A623 42%, #FFB347 72%, #FFC96B 100%);
      z-index: 999;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity .2s;
    }

    #ml.on {
      opacity: 1;
      pointer-events: all;
    }

    .ml-wrap {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
    }

    .ml-box {
      width: 64px;
      height: 64px;
      background: rgba(255, 255, 255, .2);
      border-radius: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      border: 1.5px solid rgba(255, 255, 255, .3);
    }

    .ml-box svg {
      width: 38px;
      height: 38px;
    }

    .ml-name {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #fff;
    }

    .ml-name span {
      color: rgba(255, 255, 255, .62);
      font-weight: 400;
    }

    .ml-dots {
      display: flex;
      gap: 6px;
      margin-top: 4px;
    }

    .ml-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .5);
      animation: pulse 1.1s infinite ease-in-out;
    }

    .ml-dot:nth-child(2) {
      animation-delay: .22s;
    }

    .ml-dot:nth-child(3) {
      animation-delay: .44s;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: .4;
        transform: scale(.85)
      }

      50% {
        opacity: 1;
        transform: scale(1.1)
      }
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
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
          <div class="p-hdr-back" onclick="goPage(APP_BASE + '/home.php')">
            <i class="bi bi-arrow-left"></i>
          </div>

          <div class="p-hdr-settings" onclick="openSettingsScreen()">
            <i class="bi bi-gear-fill"></i>
          </div>
          <img class="p-avatar" id="profileAvatar"
            src="https://ui-avatars.com/api/?name=User&background=FDECC8&color=E8820C&size=200" alt="Avatar">
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
              <i class="bi bi-geo-alt-fill" style="font-size:11px;"></i>
              <span id="profileAddressShort">No address</span>
            </div>
          </div>
        </div>
        <div class="p-body">


          <div class="p-sec">
            <div class="p-sec-ttl">Account</div>
            <div class="p-row" onclick="openSubSheet('profile')">
              <div class="p-row-ic">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 12a5 5 0 100-10 5 5 0 000 10z" stroke="#F5A623" stroke-width="2" />
                  <path d="M3 21c0-4.4 4-8 9-8s9 3.6 9 8" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                </svg>
              </div>
              <div class="p-row-info">
                <div class="p-row-lbl">Edit Profile</div>
                <div class="p-row-sub">Name, email, phone & address</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row" onclick="openSubSheet('security')">
              <div class="p-row-ic">
                <svg viewBox="0 0 24 24" fill="none">
                  <rect x="5" y="11" width="14" height="10" rx="2" stroke="#F5A623" stroke-width="2" />
                  <path d="M8 11V7a4 4 0 118 0v4" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                  <circle cx="12" cy="16" r="1.5" fill="#F5A623" />
                </svg>
              </div>
              <div class="p-row-info">
                <div class="p-row-lbl">Security</div>
                <div class="p-row-sub">Change your password</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row" onclick="openSubSheet('address')">
              <div class="p-row-ic">
                <svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 2a8 8 0 00-8 8c0 5.5 8 13 8 13s8-7.5 8-13a8 8 0 00-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z"
                    stroke="#F5A623" stroke-width="2" />
                </svg>
              </div>
              <div class="p-row-info">
                <div class="p-row-lbl">Saved Addresses</div>
                <div class="p-row-sub" id="addressRowSub">Manage delivery addresses</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
          </div>

          <div class="p-sec">
            <div class="p-sec-ttl">Support</div>
            <div class="p-row" onclick="openSettingsScreen('help')">
              <div class="p-row-ic">
                <svg viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="9" stroke="#F5A623" stroke-width="2" />
                  <path d="M9 9a3 3 0 015.8 1c0 2-3 3-3 3" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                  <circle cx="12" cy="17" r="1" fill="#F5A623" />
                </svg>
              </div>
              <div class="p-row-info">
                <div class="p-row-lbl">Help Center</div>
                <div class="p-row-sub">FAQs & support articles</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
          </div>

        </div>
      </div>
      <div id="navContainer">
        <div class="bnav">
          <div class="ni" onclick="goPage('<?= htmlspecialchars(rtrim($appBase, '/')) ?>/home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni" onclick="goPage('<?= htmlspecialchars(rtrim($appBase, '/')) ?>/clients/booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
          <div class="ni" onclick="goPage('<?= htmlspecialchars(rtrim($appBase, '/')) ?>/clients/service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
          <div class="ni" onclick="goPage('<?= htmlspecialchars(rtrim($appBase, '/')) ?>/clients/notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
          <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        </div>
      </div>
    </div>


    <div id="settingsScreen">
      <div class="st-hdr">
        <div class="st-back" onclick="closeSettingsScreen()">
          <i class="bi bi-arrow-left"></i>
        </div>
        <div>
          <div class="st-hdr-title">Settings</div>
          <div class="st-hdr-sub">Manage your account & preferences</div>
        </div>
      </div>

      <div class="st-scroll">
        <div class="st-sec">
          <div class="st-sec-ttl">Notifications</div>
          <div class="st-row">
            <div class="st-ic orange"><i class="bi bi-bell-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Push Notifications</div>
              <div class="st-row-sub">Booking alerts & updates</div>
            </div>
            <div class="st-toggle on" onclick="this.classList.toggle('on')"></div>
          </div>
        </div>


        <div class="st-sec">
          <div class="st-sec-ttl">Support</div>
          <div class="st-row" id="stHelpRow">
            <div class="st-ic orange"><i class="bi bi-question-circle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Help Center</div>
              <div class="st-row-sub">FAQs & guides</div>
            </div>
            <i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row">
            <div class="st-ic blue"><i class="bi bi-headset"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Contact Support</div>
              <div class="st-row-sub">Chat or call us</div>
            </div>
            <span class="st-badge">Live</span>
            <i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row">
            <div class="st-ic green"><i class="bi bi-star-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Rate the App</div>
              <div class="st-row-sub">Share your experience</div>
            </div>
            <i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
        </div>


        <div class="st-sec">
          <div class="st-sec-ttl">About</div>
          <div class="st-row">
            <div class="st-ic gray"><i class="bi bi-file-text-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Terms of Service</div>
              <div class="st-row-sub">Read our terms</div>
            </div>
            <i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row">
            <div class="st-ic gray"><i class="bi bi-lock-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Privacy Policy</div>
              <div class="st-row-sub">How we use your data</div>
            </div>
            <i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
        </div>


        <div class="st-sec">
          <div class="st-sec-ttl">Session</div>
          <div class="st-row" onclick="doLogout()">
            <div class="st-ic red"><i class="bi bi-box-arrow-right"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl" style="color:#ef4444;">Log Out</div>
              <div class="st-row-sub">Sign out of your account</div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <div class="logout-confirm-ol" id="logoutConfirmOl" onclick="if(event.target===this)closeLogoutConfirm()">
      <div class="logout-confirm-card">
        <div class="logout-confirm-ic"><i class="bi bi-box-arrow-right"></i></div>
        <div class="logout-confirm-ttl">Log out?</div>
        <div class="logout-confirm-sub">You will be signed out of your account.</div>
        <div class="logout-confirm-actions">
          <button class="logout-confirm-btn cancel" onclick="closeLogoutConfirm()">Cancel</button>
          <button class="logout-confirm-btn ok" onclick="confirmLogout()">Log out</button>
        </div>
      </div>
    </div>


    <div id="subSheet" onclick="subSheetBg(event)">
      <div class="sub-sheet-inner">
        <div class="sub-hand"></div>
        <div class="sub-hdr">
          <div class="sub-ttl" id="subSheetTitle">Edit Profile</div>
          <button class="sub-close" onclick="closeSubSheet()"><i class="bi bi-x-lg"></i></button>
        </div>

        <div id="subAlert"></div>


        <div id="subProfileSection">
          <div style="display:flex;flex-direction:column;align-items:center;margin-bottom:20px;gap:8px;">
            <div style="position:relative;cursor:pointer;">
              <img id="settingsAvatar"
                src="https://ui-avatars.com/api/?name=User&background=FDECC8&color=E8820C&size=200"
                style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--teal-mid);"
                alt="Avatar">
              <div
                style="position:absolute;bottom:0;right:0;width:28px;height:28px;background:linear-gradient(135deg,#E8820C,#F5A623);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:12px;box-shadow:0 3px 10px rgba(232,130,12,0.3);">
                <i class="bi bi-camera-fill"></i>
              </div>
            </div>
            <div style="font-size:12px;color:var(--teal);font-weight:700;">Change photo</div>
          </div>
          <div
            style="font-size:10px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">
            Personal Information</div>
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


        <div id="subSecuritySection" style="display:none;">
          <div
            style="font-size:10px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;">
            Change Password</div>
          <div class="s-fg"><label class="s-lbl">Current Password</label>
            <div class="s-iw"><i class="bi bi-lock-fill s-ico"></i><input type="password" class="s-fi" id="s_cpwd"
                placeholder="Current password"><i class="bi bi-eye-fill s-eye" onclick="tPwd('s_cpwd',this)"></i></div>
          </div>
          <div class="s-fg"><label class="s-lbl">New Password</label>
            <div class="s-iw"><i class="bi bi-lock-fill s-ico"></i><input type="password" class="s-fi" id="s_npwd"
                placeholder="Min. 6 characters"><i class="bi bi-eye-fill s-eye" onclick="tPwd('s_npwd',this)"></i></div>
          </div>
          <div class="s-fg"><label class="s-lbl">Confirm New Password</label>
            <div class="s-iw"><i class="bi bi-lock-fill s-ico"></i><input type="password" class="s-fi" id="s_cpwd2"
                placeholder="Repeat new password"><i class="bi bi-eye-fill s-eye" onclick="tPwd('s_cpwd2',this)"></i>
            </div>
          </div>
        </div>


        <div id="subAddressSection" style="display:none;">
          <div
            style="background:var(--teal-bg);border-radius:14px;padding:14px 16px;margin-bottom:16px;display:flex;align-items:center;gap:12px;border:1.5px solid var(--teal-mid);">
            <div
              style="width:38px;height:38px;border-radius:11px;background:linear-gradient(135deg,#E8820C,#F5A623);display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;flex-shrink:0;">
              <i class="bi bi-geo-alt-fill"></i>
            </div>
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

        <button class="btn-p save-btn" id="saveBtn" onclick="saveSettings()">
          <i class="bi bi-check2" style="margin-right:6px;"></i>Save Changes
        </button>
      </div>
    </div>

  </div><!-- /shell -->

  <script src="<?= htmlspecialchars(rtrim($appBase, '/')) ?>/assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') {
      initTheme();
    }

    const APP_BASE = <?= json_encode(rtrim($appBase, '/')) ?> || '';

    window.HE = window.HE || {};
    window.HE.user = {
      name: <?= json_encode($_SESSION['user_name'] ?? '') ?>,
      email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
      phone: <?= json_encode($_SESSION['user_phone'] ?? '') ?>,
      address: <?= json_encode($_SESSION['user_address'] ?? '') ?>
    };


    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni" onclick="goPage(APP_BASE + '/home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage(APP_BASE + '/clients/booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage(APP_BASE + '/clients/service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage(APP_BASE + '/clients/notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
        <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;


    function loadProfile() {
      const u = window.HE.user;
      document.getElementById('profileName').textContent = u.name || 'User';
      document.getElementById('profileEmail').textContent = u.email || '';

      const shortAddr = u.address
        ? (u.address.length > 22 ? u.address.slice(0, 22) + '…' : u.address)
        : 'No address';
      document.getElementById('profileAddressShort').textContent = shortAddr;
      document.getElementById('addressRowSub').textContent = u.address || 'Manage delivery addresses';
      if (document.getElementById('stAddressSub')) document.getElementById('stAddressSub').textContent = u.address || 'Manage delivery addresses';
      document.getElementById('savedAddrDisplay').textContent = u.address || 'No address saved yet';


      const encodedName = encodeURIComponent(u.name || 'User');
      const avatarUrl = `https://ui-avatars.com/api/?name=${encodedName}&background=FDECC8&color=E8820C&size=200`;
      document.getElementById('profileAvatar').src = avatarUrl;
      document.getElementById('settingsAvatar').src = avatarUrl;


      document.getElementById('s_name').value = u.name || '';
      document.getElementById('s_email').value = u.email || '';
      document.getElementById('s_phone').value = u.phone || '';
      document.getElementById('s_addr').value = u.address || '';
      document.getElementById('s_newaddr').value = u.address || '';
    }


    function openSettingsScreen() {
      document.getElementById('settingsScreen').classList.add('on');
    }
    function closeSettingsScreen() {
      document.getElementById('settingsScreen').classList.remove('on');
    }


    let activeSection = 'profile';

    function openSubSheet(section) {
      activeSection = section;
      hideSubAlert();
      document.getElementById('subProfileSection').style.display = section === 'profile' ? 'block' : 'none';
      document.getElementById('subSecuritySection').style.display = section === 'security' ? 'block' : 'none';
      document.getElementById('subAddressSection').style.display = section === 'address' ? 'block' : 'none';

      const titles = { profile: 'Edit Profile', security: 'Password & Security', address: 'Saved Addresses' };
      document.getElementById('subSheetTitle').textContent = titles[section] || 'Settings';


      document.querySelector('#subSheet .sub-sheet-inner > div:nth-child(3)').style.display =
        section === 'profile' ? 'flex' : 'none';

      document.getElementById('subSheet').classList.add('on');
    }

    function closeSubSheet() {
      document.getElementById('subSheet').classList.remove('on');
    }

    function subSheetBg(e) {
      if (e.target === document.getElementById('subSheet')) closeSubSheet();
    }


    function showSubAlert(msg, type) {
      const el = document.getElementById('subAlert');
      el.textContent = msg;
      el.className = 'show ' + (type === 'success' ? 'ok' : 'err');
    }
    function hideSubAlert() {
      document.getElementById('subAlert').className = '';
    }


    function tPwd(id, btn) {
      const inp = document.getElementById(id);
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.className = show ? 'bi bi-eye-slash-fill s-eye' : 'bi bi-eye-fill s-eye';
    }


    async function saveSettings() {
      const btn = document.getElementById('saveBtn');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-arrow-repeat" style="margin-right:6px;animation:spin .7s linear infinite;display:inline-block;"></i>Saving…';
      hideSubAlert();

      const fd = new FormData();
      if (activeSection === 'profile') {
        fd.append('section', 'profile');
        fd.append('name', document.getElementById('s_name').value.trim());
        fd.append('email', document.getElementById('s_email').value.trim());
        fd.append('phone', document.getElementById('s_phone').value.trim());
        fd.append('address', document.getElementById('s_addr').value.trim());
      } else if (activeSection === 'security') {
        fd.append('section', 'security');
        fd.append('current_password', document.getElementById('s_cpwd').value);
        fd.append('new_password', document.getElementById('s_npwd').value);
        fd.append('confirm_password', document.getElementById('s_cpwd2').value);
      } else if (activeSection === 'address') {
        fd.append('section', 'address');
        fd.append('address', document.getElementById('s_newaddr').value.trim());
      }

      try {
        const res = await fetch(APP_BASE + '/api/profile_api.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
          if (activeSection === 'profile') {
            window.HE.user.name = document.getElementById('s_name').value.trim();
            window.HE.user.email = document.getElementById('s_email').value.trim();
            window.HE.user.phone = document.getElementById('s_phone').value.trim();
            window.HE.user.address = document.getElementById('s_addr').value.trim();
          } else if (activeSection === 'address') {
            window.HE.user.address = document.getElementById('s_newaddr').value.trim();
          }
          loadProfile();
          showSubAlert('✓ ' + data.message, 'success');
          setTimeout(() => closeSubSheet(), 1300);
        } else {
          showSubAlert(data.message || 'Something went wrong.', 'error');
        }
      } catch (e) {
        showSubAlert('Network error. Please try again.', 'error');
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2" style="margin-right:6px;"></i>Save Changes';
    }

    function doLogout() {
      openLogoutConfirm();
    }

    function openLogoutConfirm() {
      document.getElementById('logoutConfirmOl').classList.add('on');
    }

    function closeLogoutConfirm() {
      document.getElementById('logoutConfirmOl').classList.remove('on');
    }

    function confirmLogout() {
      closeLogoutConfirm();
      window.location.href = APP_BASE + '/logout.php';
    }

    loadProfile();
  </script>
</body>

</html>
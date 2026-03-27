<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once 'api/db.php';
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

    #settingsScreen {
      position: absolute;
      inset: 0;
      background: var(--bg-screen);
      z-index: 100;
      display: flex;
      flex-direction: column;
      transform: translateX(100%);
      transition: transform 0.32s cubic-bezier(.4, 0, .2, 1);
    }

    #settingsScreen.on {
      transform: translateX(0);
    }

    .st-hdr {
      padding: 48px 20px 16px;
      background: linear-gradient(145deg, #C86500 0%, #E8820C 30%, #F5A623 60%, #FFB347 100%);
      display: flex;
      align-items: center;
      gap: 14px;
      flex-shrink: 0;
      position: relative;
      overflow: hidden;
    }

    .st-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.07) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }

    .st-back {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #fff;
      font-size: 18px;
      flex-shrink: 0;
      transition: background 0.2s;
      position: relative;
      z-index: 1;
    }

    .st-back:hover {
      background: rgba(255, 255, 255, 0.3);
    }

    .st-hdr-title {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      position: relative;
      z-index: 1;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .st-hdr-sub {
      font-size: 12px;
      color: rgba(255, 255, 255, 0.75);
      position: relative;
      z-index: 1;
    }

    .st-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 20px 18px 100px;
      scrollbar-width: none;
    }

    .st-scroll::-webkit-scrollbar {
      display: none;
    }

   
    .st-sec {
      background: var(--bg-card);
      border-radius: 18px;
      overflow: hidden;
      margin-bottom: 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, 0.07);
      border: 1.5px solid var(--border-col);
    }

    .st-sec-ttl {
      padding: 12px 18px 8px;
      font-size: 10px;
      font-weight: 800;
      color: var(--tm);
      text-transform: uppercase;
      letter-spacing: 0.9px;
      background: var(--teal-bg);
      border-bottom: 1px solid var(--border-col);
    }

    .st-row {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 18px;
      cursor: pointer;
      transition: background 0.15s;
      border-bottom: 1px solid var(--border-col);
    }

    .st-row:last-child {
      border-bottom: none;
    }

    .st-row:hover {
      background: var(--teal-bg);
    }

    .st-row:active {
      background: var(--teal-mid);
    }

    .st-ic {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .st-ic.orange {
      background: linear-gradient(135deg, #FFE5B4, #FFF8F0);
      color: #E8820C;
    }

    .st-ic.blue {
      background: linear-gradient(135deg, #dbeafe, #eff6ff);
      color: #2563eb;
    }

    .st-ic.green {
      background: linear-gradient(135deg, #d1fae5, #ecfdf5);
      color: #059669;
    }

    .st-ic.red {
      background: linear-gradient(135deg, #fee2e2, #fff5f5);
      color: #ef4444;
    }

    .st-ic.purple {
      background: linear-gradient(135deg, #ede9fe, #f5f3ff);
      color: #7c3aed;
    }

    .st-ic.gray {
      background: linear-gradient(135deg, #f3f4f6, #f9fafb);
      color: #6b7280;
    }

    .st-row-info {
      flex: 1;
    }

    .st-row-lbl {
      font-size: 14px;
      font-weight: 700;
      color: var(--td);
    }

    .st-row-sub {
      font-size: 12px;
      color: var(--tm);
      margin-top: 1px;
    }

    .st-row-arrow {
      color: #d1d5db;
      font-size: 15px;
    }

    .st-row-val {
      font-size: 12px;
      font-weight: 700;
      color: var(--tm);
      margin-right: 6px;
    }

    .st-badge {
      background: linear-gradient(135deg, #F5A623, #FFB347);
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 10px;
      margin-right: 6px;
    }

 
    .st-toggle {
      width: 48px;
      height: 26px;
      background: #e5e7eb;
      border-radius: 13px;
      position: relative;
      cursor: pointer;
      transition: background 0.25s;
      flex-shrink: 0;
    }

    .st-toggle.on {
      background: linear-gradient(135deg, #E8820C, #F5A623);
      box-shadow: 0 3px 10px rgba(232, 130, 12, 0.3);
    }

    .st-toggle::after {
      content: '';
      position: absolute;
      top: 3px;
      left: 3px;
      width: 20px;
      height: 20px;
      background: #fff;
      border-radius: 50%;
      transition: transform 0.22s cubic-bezier(.34, 1.4, .64, 1);
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.18);
    }

    .st-toggle.on::after {
      transform: translateX(22px);
    }


    #subSheet {
      position: absolute;
      inset: 0;
      background: var(--modal-ol, rgba(26, 20, 8, 0.55));
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      z-index: 200;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.3s;
    }

    #subSheet.on {
      opacity: 1;
      pointer-events: all;
    }

    .sub-sheet-inner {
      background: var(--bg-card);
      border-radius: 28px 28px 0 0;
      max-height: 90%;
      overflow-y: auto;
      transform: translateY(100%);
      transition: transform 0.38s cubic-bezier(.4, 0, .2, 1);
      padding: 0 22px 44px;
      box-shadow: 0 -4px 40px rgba(232, 130, 12, 0.1);
    }

    #subSheet.on .sub-sheet-inner {
      transform: translateY(0);
    }

    .sub-hand {
      width: 40px;
      height: 4px;
      background: var(--border-col);
      border-radius: 2px;
      margin: 14px auto 18px;
    }

    .sub-hdr {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }

    .sub-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      font-weight: 800;
      color: var(--td);
    }

    .sub-close {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: var(--teal-bg);
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--tm);
      font-size: 15px;
      transition: background 0.2s;
    }

    .sub-close:hover {
      background: var(--teal-mid);
    }


    #subAlert {
      display: none;
      padding: 11px 15px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 14px;
    }

    #subAlert.show {
      display: block;
    }

    #subAlert.ok {
      background: #d1fae5;
      color: #065f46;
    }

    #subAlert.err {
      background: #fee2e2;
      color: #991b1b;
    }

    .st-version {
      text-align: center;
      padding: 8px 0 4px;
      font-size: 12px;
      color: var(--tm);
      font-weight: 600;
    }

    body.dark .st-sec {
      background: var(--bg-card);
      border-color: var(--border-col);
    }

    body.dark .st-row:hover {
      background: var(--tbg);
    }

    body.dark .st-sec-ttl {
      background: var(--tbg);
      border-color: var(--border-col);
    }

    body.dark .st-ic.gray {
      background: #2a2a2a;
      color: #9ca3af;
    }

    body.dark .sub-sheet-inner {
      background: var(--bg-card);
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
          <div class="p-hdr-back" onclick="goPage('home.php')">
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
      <div id="navContainer"></div>
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
          <div class="st-sec-ttl">Appearance</div>
          <div class="st-row">
            <div class="st-ic gray"><i class="bi bi-moon-stars-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Dark Mode</div>
              <div class="st-row-sub">Switch to dark theme</div>
            </div>
            <div class="st-toggle" id="stDarkToggle" onclick="toggleDarkMode()"></div>
          </div>
          <div class="st-row">
            <div class="st-ic orange"><i class="bi bi-phone-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Language</div>
              <div class="st-row-sub">App display language</div>
            </div>
            <span class="st-row-val">English</span>
            <i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
        </div>


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
            <div class="st-ic gray"><i class="bi bi-info-circle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">App Version</div>
              <div class="st-row-sub">HomeEase for Users</div>
            </div>
            <span class="st-row-val">v3.2.0</span>
          </div>
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
        <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
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
      document.getElementById('stAddressSub').textContent = u.address || 'Manage delivery addresses';
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
      syncDarkToggles();
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
        const res = await fetch('api/profile_api.php', { method: 'POST', body: fd });
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


    function syncDarkToggles() {
      const isDark = document.body.classList.contains('dark');
      const darkModeToggle = document.getElementById('darkModeToggle');
      const stDarkToggle = document.getElementById('stDarkToggle');
      if (darkModeToggle) darkModeToggle.classList.toggle('on', isDark);
      if (stDarkToggle) stDarkToggle.classList.toggle('on', isDark);
    }
    function toggleDarkMode() {
      toggleDark();
      syncDarkToggles();
    }

    function doLogout() { window.location.href = 'logout.php'; }

    loadProfile();
    syncDarkToggles();
  </script>
</body>

</html>
<?php session_start(); ?>
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
  <style>
    #profile {
      background: var(--bg-screen);
      justify-content: flex-start;
    }

    .p-scroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 80px;
    }

    .p-hdr {
      width: 100%;
      padding: 48px 22px 28px;
      background: var(--teal);
      border-radius: 0 0 36px 36px;
      text-align: center;
      position: relative;
    }

    .p-hdr-back {
      position: absolute;
      left: 18px;
      top: 52px;
      width: 36px;
      height: 36px;
      background: rgba(255, 255, 255, .18);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #fff;
      font-size: 18px;
    }

    .p-avatar {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      border: 3px solid rgba(255, 255, 255, .5);
      object-fit: cover;
      margin-bottom: 12px;
    }

    .p-name {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      margin-bottom: 3px;
    }

    .p-email {
      color: rgba(255, 255, 255, .75);
      font-size: 13px;
      margin-bottom: 14px;
    }

    .p-badges {
      display: flex;
      gap: 8px;
      justify-content: center;
      flex-wrap: wrap;
    }

    .p-badge {
      background: rgba(255, 255, 255, .18);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 20px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .p-stats {
      display: flex;
      margin: 18px 18px 0;
      background: #fff;
      border-radius: 18px;
      overflow: hidden;
      box-shadow: 0 2px 12px rgba(0, 0, 0, .06);
    }

    .p-stat {
      flex: 1;
      text-align: center;
      padding: 18px 8px;
      border-right: 1px solid #f0fdfa;
    }

    .p-stat:last-child {
      border-right: none;
    }

    .p-stat-val {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: var(--teal);
    }

    .p-stat-lbl {
      font-size: 11px;
      color: var(--tm);
      font-weight: 600;
      margin-top: 2px;
    }

    .p-body {
      padding: 18px;
    }

    .p-sec {
      background: #fff;
      border-radius: 18px;
      overflow: hidden;
      margin-bottom: 16px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .p-sec-ttl {
      padding: 14px 18px 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 800;
      color: var(--tm);
      text-transform: uppercase;
      letter-spacing: .7px;
      border-bottom: 1px solid #f4fffe;
    }

    .p-row {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 18px;
      cursor: pointer;
      transition: background .15s;
      border-bottom: 1px solid #f4fffe;
    }

    .p-row:last-child {
      border-bottom: none;
    }

    .p-row:hover {
      background: #f0fdfa;
    }

    .p-row-ic {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: var(--tbg);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .p-row-ic svg {
      width: 20px;
      height: 20px;
    }

    .p-row-info {
      flex: 1;
    }

    .p-row-lbl {
      font-size: 14px;
      font-weight: 700;
      color: var(--td);
    }

    .p-row-sub {
      font-size: 12px;
      color: var(--tm);
      margin-top: 1px;
    }

    .p-row-arrow {
      color: #d1d5db;
      font-size: 16px;
    }

    .p-row-badge {
      background: var(--danger);
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 10px;
    }

    .toggle-wrap {
      display: flex;
      align-items: center;
    }

    .toggle {
      width: 46px;
      height: 26px;
      background: #e5e7eb;
      border-radius: 13px;
      position: relative;
      cursor: pointer;
      transition: background .2s;
    }

    .toggle.on {
      background: var(--teal);
    }

    .toggle::after {
      content: '';
      position: absolute;
      top: 3px;
      left: 3px;
      width: 20px;
      height: 20px;
      background: #fff;
      border-radius: 50%;
      transition: transform .2s;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
    }

    .toggle.on::after {
      transform: translateX(20px);
    }

    body.dark .p-sec {
      background: var(--bg-card);
    }

    body.dark .p-stats {
      background: var(--bg-card);
    }

    body.dark .p-stat {
      border-right-color: var(--border-col);
    }

    body.dark .p-stat-lbl {
      color: var(--tm);
    }

    body.dark .p-row:hover {
      background: var(--pbg);
    }

    body.dark .p-row {
      border-bottom-color: var(--border-col);
    }

    body.dark .p-row-lbl {
      color: var(--td);
    }

    body.dark .p-row-sub {
      color: var(--tm);
    }

    body.dark .p-sec-ttl {
      color: var(--tm);
      border-bottom-color: var(--border-col);
    }

    body.dark .s-sheet {
      background: var(--bg-card);
    }

    body.dark .s-fi {
      background: var(--bg-input);
      color: var(--td);
      border-color: var(--border-col);
    }

    body.dark .s-lbl {
      color: var(--td);
    }

    body.dark .s-ttl {
      color: var(--td);
    }

    body.dark .s-sec-ttl {
      color: var(--tm);
    }

    body.dark .s-hand {
      background: var(--border-col);
    }

    body.dark #settingsModal {
      background: rgba(0, 20, 18, .7);
    }

    body.dark .logout-btn {
      background: var(--bg-card);
      border-color: var(--danger);
      color: var(--danger);
    }

    body.dark .logout-btn:hover {
      background: #3f1a1a;
    }

    .logout-btn {
      width: 100%;
      padding: 15px;
      background: #fff;
      color: #ef4444;
      border: 2px solid #ef4444;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 16px;
      transition: all .2s;
    }

    .logout-btn:hover {
      background: #fef2f2;
    }

    #settingsModal {
      position: absolute;
      inset: 0;
      background: rgba(0, 30, 28, .55);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      z-index: 200;
      opacity: 0;
      pointer-events: none;
      transition: opacity .3s;
    }

    #settingsModal.on {
      opacity: 1;
      pointer-events: all;
    }

    .s-sheet {
      background: #fff;
      border-radius: 26px 26px 0 0;
      max-height: 90%;
      overflow-y: auto;
      transform: translateY(100%);
      transition: transform .35s cubic-bezier(.4, 0, .2, 1);
      padding: 0 22px 40px;
    }

    #settingsModal.on .s-sheet {
      transform: translateY(0);
    }

    .s-hand {
      width: 38px;
      height: 4px;
      background: #e5e7eb;
      border-radius: 2px;
      margin: 13px auto 18px;
    }

    .s-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 19px;
      font-weight: 800;
      color: var(--td);
      margin-bottom: 20px;
    }

    .s-avatar-row {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 22px;
      gap: 8px;
    }

    .s-avatar-wrap {
      position: relative;
      cursor: pointer;
    }

    .s-avatar-wrap img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid var(--tbg);
    }

    .s-avatar-edit {
      position: absolute;
      bottom: 0;
      right: 0;
      width: 26px;
      height: 26px;
      background: var(--teal);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 12px;
    }

    .s-avatar-lbl {
      font-size: 12px;
      color: var(--teal);
      font-weight: 700;
    }

    .s-fg {
      margin-bottom: 14px;
    }

    .s-lbl {
      font-size: 12px;
      font-weight: 700;
      color: var(--td);
      margin-bottom: 6px;
      display: block;
    }

    .s-fi {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e5e7eb;
      border-radius: 13px;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      outline: none;
      transition: border-color .2s;
      background: #fafafa;
      color: var(--td);
    }

    .s-fi:focus {
      border-color: var(--teal);
      background: #fff;
      box-shadow: 0 0 0 4px rgba(13, 148, 136, .08);
    }

    .s-iw {
      position: relative;
    }

    .s-iw .s-ico {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--teal);
      font-size: 15px;
    }

    .s-iw .s-fi {
      padding-left: 38px;
    }

    .s-iw .s-eye {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      font-size: 15px;
      cursor: pointer;
    }

    .s-divider {
      height: 1px;
      background: #f0fdfa;
      margin: 18px 0;
    }

    .s-sec-ttl {
      font-size: 11px;
      font-weight: 800;
      color: var(--tm);
      text-transform: uppercase;
      letter-spacing: .7px;
      margin-bottom: 12px;
    }

    .save-btn {
      margin-top: 8px;
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
          <div class="p-name" id="profileName">Juan dela Cruz</div>
          <div class="p-email" id="profileEmail">juan@email.com</div>
          <div class="p-badges">
            <div class="p-badge">
              <svg viewBox="0 0 16 16" fill="none" width="12" height="12">
                <path d="M8 1l1.8 4H14l-3.3 2.4 1.2 4L8 9 4.1 11.4l1.2-4L2 5h4.2L8 1z" fill="#fff" />
              </svg>
              Verified Member
            </div>
            <div class="p-badge">
              <svg viewBox="0 0 16 16" fill="none" width="12" height="12">
                <path d="M8 2a6 6 0 100 12A6 6 0 008 2zm0 10a4 4 0 110-8 4 4 0 010 8z" fill="#fff" />
                <circle cx="8" cy="8" r="2" fill="#fff" />
              </svg>
              Mauban, Quezon
            </div>
          </div>
        </div>

        <div class="p-stats">
          <div class="p-stat">
            <div class="p-stat-val" id="statBookings">3</div>
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
            <div class="p-row" onclick="openSettings()">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 12a5 5 0 100-10 5 5 0 000 10z" stroke="#0D9488" stroke-width="2" />
                  <path d="M3 21c0-4.4 4-8 9-8s9 3.6 9 8" stroke="#0D9488" stroke-width="2" stroke-linecap="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Edit Profile</div>
                <div class="p-row-sub">Update name, email, phone</div>
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
                <div class="p-row-sub">Manage delivery addresses</div>
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
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="9" stroke="#0D9488" stroke-width="2" />
                  <path d="M12 7v5l3 3" stroke="#0D9488" stroke-width="2" stroke-linecap="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Language</div>
                <div class="p-row-sub">English (US)</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
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
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M9 12l2 2 4-4M7.8 4.8a7 7 0 1010.9 8.7" stroke="#0D9488" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Rate HomeEase</div>
                <div class="p-row-sub">Share your experience</div>
              </div>
              <span class="p-row-badge">⭐ New</span>
            </div>
          </div>

          <button class="logout-btn" onclick="goPage('index.php')">
            <i class="bi bi-box-arrow-right" style="margin-right:8px;"></i>Log Out
          </button>
        </div>
      </div>
      <div id="navContainer"></div>
    </div>

    <div id="settingsModal" onclick="settingsBg(event)">
      <div class="s-sheet">
        <div class="s-hand"></div>
        <div class="s-ttl" id="settingsTitle">Edit Profile</div>

        <div class="s-avatar-row">
          <div class="s-avatar-wrap">
            <img id="settingsAvatar" src="https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=200&q=80"
              alt="Avatar">
            <div class="s-avatar-edit"><i class="bi bi-camera-fill"></i></div>
          </div>
          <div class="s-avatar-lbl">Change photo</div>
        </div>

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
                placeholder="Your address"></div>
          </div>
        </div>

        <div id="securitySection" style="display:none;">
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
          <div class="s-divider"></div>
          <div class="s-sec-ttl">Change PIN</div>
          <div class="s-fg"><label class="s-lbl">New 4-digit PIN</label>
            <div class="s-iw"><i class="bi bi-shield-lock-fill s-ico"></i><input type="password" class="s-fi" id="s_pin"
                placeholder="••••" maxlength="4" inputmode="numeric"></div>
          </div>
        </div>

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
              <div style="font-size:12px;color:var(--tm);" id="savedAddrDisplay">123 Mauban, Quezon</div>
            </div>
          </div>
          <div class="s-fg"><label class="s-lbl">Update Home Address</label>
            <div class="s-iw"><i class="bi bi-geo-alt-fill s-ico"></i><input type="text" class="s-fi" id="s_newaddr"
                placeholder="Enter new address"></div>
          </div>
        </div>

        <button class="btn-p save-btn" onclick="saveSettings()">Save Changes</button>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>initTheme();</script>
  <script>
    document.getElementById('navContainer').innerHTML = `
  <div class="bnav">
    <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
    <div class="ni" onclick="goPage('bookings.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
    <div class="ni" onclick="goPage('bookings.php?newbooking=1')" style="cursor:pointer;"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
    <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
    <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
  </div>`;

    function loadProfile() {
      const u = window.HE.user;
      document.getElementById('profileName').textContent = u.name;
      document.getElementById('profileEmail').textContent = u.email;
      document.getElementById('statBookings').textContent = window.HE.bookings.length;
      document.getElementById('s_name').value = u.name;
      document.getElementById('s_email').value = u.email;
      document.getElementById('s_phone').value = u.phone;
      document.getElementById('s_addr').value = u.address;
      document.getElementById('savedAddrDisplay').textContent = u.address;
    }

    let settingsSection = 'profile';
    function openSettings(section) {
      section = section || 'profile';
      settingsSection = section;
      document.getElementById('settingsModal').classList.add('on');
      document.getElementById('profileSection').style.display = section === 'profile' ? 'block' : 'none';
      document.getElementById('securitySection').style.display = section === 'security' ? 'block' : 'none';
      document.getElementById('addressSection').style.display = section === 'address' ? 'block' : 'none';
      const titles = { profile: 'Edit Profile', security: 'Security & PIN', address: 'Saved Addresses' };
      document.getElementById('settingsTitle').textContent = titles[section];
    }
    function settingsBg(e) { if (e.target === document.getElementById('settingsModal')) closeSettings(); }
    function closeSettings() { document.getElementById('settingsModal').classList.remove('on'); }

    function saveSettings() {
      if (settingsSection === 'profile') {
        window.HE.user.name = document.getElementById('s_name').value || window.HE.user.name;
        window.HE.user.email = document.getElementById('s_email').value || window.HE.user.email;
        window.HE.user.phone = document.getElementById('s_phone').value || window.HE.user.phone;
        window.HE.user.address = document.getElementById('s_addr').value || window.HE.user.address;
        loadProfile();
      } else if (settingsSection === 'address') {
        const newAddr = document.getElementById('s_newaddr').value;
        if (newAddr) { window.HE.user.address = newAddr; loadProfile(); }
      }
      
      const btn = document.querySelector('.save-btn');
      btn.textContent = '✓ Saved!';
      btn.style.background = '#10b981';
      setTimeout(() => { btn.textContent = 'Save Changes'; btn.style.background = ''; closeSettings(); }, 1200);
    }

    loadProfile();
  </script>
</body>

</html>
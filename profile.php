<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once 'api/db.php';
$stmt = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($row) {
  $_SESSION['user_name'] = $row['name'];
  $_SESSION['user_email'] = $row['email'];
  $_SESSION['user_phone'] = $row['phone'];
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

          <button class="logout-btn" onclick="doLogout()">
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
  <script>
    initTheme();
  </script>
  <script>
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
    <div class="ni" onclick="goPage('bookings.php?newbooking=1')" style="cursor:pointer;"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
    <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
    <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
  </div>`;

    function loadProfile() {
      const u = window.HE.user;
      document.getElementById('profileName').textContent = u.name || 'User';
      document.getElementById('profileEmail').textContent = u.email || '';
      document.getElementById('statBookings').textContent = <?= (int) $bookingCount ?>;
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
      const titles = {
        profile: 'Edit Profile',
        security: 'Security & PIN',
        address: 'Saved Addresses'
      };
      document.getElementById('settingsTitle').textContent = titles[section];
    }

    function settingsBg(e) {
      if (e.target === document.getElementById('settingsModal')) closeSettings();
    }

    function closeSettings() {
      document.getElementById('settingsModal').classList.remove('on');
    }

    function saveSettings() {
      if (settingsSection === 'profile') {
        window.HE.user.name = document.getElementById('s_name').value || window.HE.user.name;
        window.HE.user.email = document.getElementById('s_email').value || window.HE.user.email;
        window.HE.user.phone = document.getElementById('s_phone').value || window.HE.user.phone;
        window.HE.user.address = document.getElementById('s_addr').value || window.HE.user.address;
        loadProfile();
      } else if (settingsSection === 'address') {
        const newAddr = document.getElementById('s_newaddr').value;
        if (newAddr) {
          window.HE.user.address = newAddr;
          loadProfile();
        }
      }

      const btn = document.querySelector('.save-btn');
      btn.textContent = '✓ Saved!';
      btn.style.background = '#10b981';
      setTimeout(() => {
        btn.textContent = 'Save Changes';
        btn.style.background = '';
        closeSettings();
      }, 1200);
    }

    loadProfile();

    function doLogout() {
      window.location.href = 'logout.php';
    }
  </script>
</body>

</html>
<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
$access = enforceProviderSectionAccess('profile', $conn);
$isVerified = $access['is_verified'];
$verificationState = $access['state'];
$isPendingUi = in_array($verificationState, ['pending', 'approval_ready'], true);
$profileUiState = $isVerified ? 'verified' : ($isPendingUi ? 'pending' : 'not-verified');
$name = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');
$email = htmlspecialchars($_SESSION['provider_email'] ?? '');
$phone = htmlspecialchars($_SESSION['provider_phone'] ?? 'Not set');
$address = htmlspecialchars($_SESSION['provider_address'] ?? 'Not set');
$specialty = htmlspecialchars($_SESSION['provider_specialty'] ?? 'General Services');
$workingHours = 'Mon-Sat, 8:00 AM - 6:00 PM';

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$colCheck = $conn->query("SHOW COLUMNS FROM service_providers LIKE 'working_hours'");
$hasWorkingHours = $colCheck && $colCheck->num_rows > 0;

if ($hasWorkingHours) {
  $profileStmt = $conn->prepare('SELECT working_hours FROM service_providers WHERE provider_id = ? LIMIT 1');
  if ($profileStmt) {
    $profileStmt->bind_param('i', $providerId);
    $profileStmt->execute();
    $profileRow = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();
    $wh = trim((string) ($profileRow['working_hours'] ?? ''));
    if ($wh !== '') {
      $workingHours = htmlspecialchars($wh);
    }
  }
}

$availabilityStatus = $isVerified ? 'online' : 'offline';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Provider Profile</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link href="../assets/css/profile.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_profile.css">
</head>

<body class="<?= $profileUiState ?>">
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
          <div class="p-hdr-back" onclick="goPage('provider_home.php')"><i class="bi bi-arrow-left"></i></div>
          <div class="p-hdr-settings" onclick="openSettingsScreen()"><i class="bi bi-gear-fill"></i></div>
          <div
            style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,rgba(255,255,255,.25),rgba(255,255,255,.1));border:3px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:#fff;margin:0 auto 12px;box-shadow:0 8px 24px rgba(0,0,0,.12);">
            <?= strtoupper(substr($name, 0, 1)) ?>
          </div>
          <div class="p-name" id="profileNameValue"><?= $name ?></div>
          <div class="p-email"><?= $email ?></div>
          <div class="p-badges">
            <div class="p-badge"><i class="bi <?= $isVerified ? 'bi-patch-check-fill' : 'bi-hourglass-split' ?>" style="font-size:11px;"></i> <?= $isVerified ? 'Verified Provider' : ('Verification: ' . ucfirst(str_replace('_', ' ', $verificationState))) ?></div>
            <div class="p-badge service-badge"><i class="bi bi-tools" style="font-size:11px;"></i> <?= $specialty ?></div>
          </div>
          <div class="p-status-row" id="profileStatusRow">
            <div class="p-status-text">Status: <span id="profileAvailLabel"><?= ($isVerified && $availabilityStatus === 'online') ? 'Online' : 'Offline' ?></span></div>
            <label class="p-status-switch <?= $isVerified ? '' : 'disabled' ?>" id="profileStatusSwitchWrap">
              <input type="checkbox" id="profileAvailToggle" <?= ($isVerified && $availabilityStatus === 'online') ? 'checked' : '' ?> disabled>
              <span class="p-status-slider"></span>
            </label>
          </div>
        </div>

     
        <div class="p-stats">
          <div class="p-stat">
            <div class="p-stat-val">24</div>
            <div class="p-stat-lbl">Jobs Done</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val">4.9</div>
            <div class="p-stat-lbl">Rating</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val">6</div>
            <div class="p-stat-lbl">Yrs Exp.</div>
          </div>
        </div>

        <div class="p-body">
        
          <div class="p-sec">
            <div class="p-sec-ttl">Contact & Availability</div>
            <div class="p-row" onclick="editPhone()" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();editPhone();}">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path
                    d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 11.18 19.79 19.79 0 01.12 2.57 2 2 0 012.11.39h3A2 2 0 017.1 2.07c.36 1.07.83 2.1 1.38 3.07a2 2 0 01-.46 2.31L6.29 9A16 16 0 0015 17.71l1.55-1.73a2 2 0 012.31-.46c.97.55 2 1.02 3.07 1.38a2 2 0 011.07 1.02z"
                    stroke="#F5A623" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Phone</div>
                <div class="p-row-sub" id="profilePhoneValue"><?= $phone ?></div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row" onclick="editServiceArea()" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();editServiceArea();}">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 2a8 8 0 00-8 8c0 5.5 8 13 8 13s8-7.5 8-13a8 8 0 00-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z"
                    stroke="#F5A623" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Service Area</div>
                <div class="p-row-sub" id="profileAddressValue"><?= $address ?></div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row" onclick="editWorkingHours()" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();editWorkingHours();}">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="#F5A623" stroke-width="2" />
                  <path d="M12 6v6l4 2" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Working Hours</div>
                <div class="p-row-sub" id="profileWorkingHoursValue"><?= $workingHours ?></div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
          </div>

          <?php if ($isVerified): ?>
            <div class="p-sec">
              <div class="p-sec-ttl">Services & Portfolio</div>
              <div class="p-row" onclick="goPage('provider_services.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <path
                      d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"
                      stroke="#F5A623" stroke-width="2" />
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">My Services</div>
                  <div class="p-row-sub">Manage what you offer</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
              <div class="p-row" onclick="goPage('provider_job_history.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <path d="M9 11l3 3L22 4" stroke="#F5A623" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" />
                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="#F5A623" stroke-width="2"
                      stroke-linecap="round" />
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Job History</div>
                  <div class="p-row-sub">View completed jobs</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
              <div class="p-row" onclick="goPage('provider_earnings.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <rect x="2" y="7" width="20" height="14" rx="2" stroke="#F5A623" stroke-width="2" />
                    <path d="M16 7v-2a2 2 0 00-2-2h-4a2 2 0 00-2 2v2M7 11h10M7 15h10" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Earnings</div>
                  <div class="p-row-sub">Track your income</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
              <div class="p-row" onclick="goPage('provider_reviews.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                      stroke="#F5A623" stroke-width="2" stroke-linejoin="round"/>
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Reviews</div>
                  <div class="p-row-sub">View customer feedback</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
            </div>
          <?php else: ?>
            <div class="p-sec">
              <div class="p-sec-ttl">Access Locked</div>
              <div class="p-row" onclick="goPage('provider_home.php')">
                <div class="p-row-ic"><i class="bi bi-shield-lock" style="font-size:20px;color:#F5A623;"></i></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Requests, Schedule, and Earnings are locked</div>
                  <div class="p-row-sub">Go to Home and submit verification requirements to unlock provider tools.</div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="bnav">
        <?php if ($isVerified): ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
          <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span
            class="nl">Calendar</span></div>
          <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span
            class="nl">Notifications</span></div>
          <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span
            class="nl">Notifications</span></div>
          <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php endif; ?>
      </div>
    </div>

 
    <div id="settingsScreen">
      <div class="st-hdr">
        <div class="st-back" onclick="closeSettingsScreen()"><i class="bi bi-arrow-left"></i></div>
        <div>
          <div class="st-hdr-title">Settings</div>
          <div class="st-hdr-sub">Manage your provider account</div>
        </div>
      </div>
      <div class="st-scroll">
        <div class="st-sec">
          <div class="st-sec-ttl">Account</div>
          <div class="st-row" onclick="openEditProfile()" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openEditProfile();}">
            <div class="st-ic orange"><i class="bi bi-person-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Edit Profile</div>
              <div class="st-row-sub">Name, phone, service area</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row" onclick="openChangePassword()" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openChangePassword();}">
            <div class="st-ic blue"><i class="bi bi-shield-lock-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Change Password</div>
              <div class="st-row-sub">Update your password</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row" onclick="editServiceArea()" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();editServiceArea();}">
            <div class="st-ic green"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Service Area</div>
              <div class="st-row-sub" id="settingsServiceAreaValue"><?= $address ?></div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
        </div>
        
        <div class="st-sec">
          <div class="st-sec-ttl">Notifications</div>
          <div class="st-row">
            <div class="st-ic orange"><i class="bi bi-bell-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">New Job Requests</div>
              <div class="st-row-sub">Get notified of new requests</div>
            </div>
            <div class="st-toggle on" onclick="this.classList.toggle('on')"></div>
          </div>
        </div>
        <div class="st-sec">
          <div class="st-sec-ttl">Support</div>
          <div class="st-row">
            <div class="st-ic orange"><i class="bi bi-question-circle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Help Center</div>
              <div class="st-row-sub">FAQs & guides</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row">
            <div class="st-ic gray"><i class="bi bi-info-circle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">App Version</div>
              <div class="st-row-sub">HomeEase for Providers</div>
            </div><span style="font-size:12px;color:var(--tm);font-weight:700;margin-right:6px;">v3.2.0</span>
          </div>
        </div>
        <div class="st-sec">
          <div class="st-sec-ttl">Session</div>
          <div class="st-row" onclick="openLogoutConfirm()">
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
        <div class="logout-confirm-sub">You will be signed out of your provider account.</div>
        <div class="logout-confirm-actions">
          <button class="logout-confirm-btn cancel" onclick="closeLogoutConfirm()">Cancel</button>
          <button class="logout-confirm-btn ok" onclick="confirmLogout()">Log out</button>
        </div>
      </div>
    </div>

    <div class="edit-modal-ol" id="editModalOl" onclick="if(event.target===this)closeEditModal(null)">
      <div class="edit-modal-card" role="dialog" aria-modal="true" aria-labelledby="editModalTitle">
        <div class="edit-modal-ttl" id="editModalTitle">Edit</div>
        <div class="edit-modal-sub" id="editModalSubtitle">Update your details.</div>
        <form id="editModalForm" class="edit-modal-form"></form>
        <div class="edit-modal-actions">
          <button type="button" class="edit-modal-btn cancel" onclick="closeEditModal(null)">Cancel</button>
          <button type="button" class="edit-modal-btn save" id="editModalSaveBtn" onclick="submitEditModal()">Save</button>
        </div>
      </div>
    </div>

  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') {
      initTheme();
    }
    const backendProfileState = <?= json_encode($profileUiState) ?>;
    const backendName = <?= json_encode(htmlspecialchars_decode($name, ENT_QUOTES)) ?>;
    const backendPhone = <?= json_encode(htmlspecialchars_decode($phone, ENT_QUOTES)) ?>;
    const backendAddress = <?= json_encode(htmlspecialchars_decode($address, ENT_QUOTES)) ?>;
    const backendWorkingHours = <?= json_encode(htmlspecialchars_decode($workingHours, ENT_QUOTES)) ?>;
    const backendIsVerified = <?= json_encode($isVerified) ?>;
    const backendAvailability = <?= json_encode($availabilityStatus) ?>;

    function applyProfileUiState(state) {
      document.body.classList.remove('not-verified', 'pending', 'verified');
      document.body.classList.add(state);

      const toggle = document.getElementById('profileAvailToggle');
      const lbl = document.getElementById('profileAvailLabel');
      const wrap = document.getElementById('profileStatusSwitchWrap');
      const allowToggle = false;

      if (toggle && lbl && wrap) {
        toggle.disabled = true;
        wrap.classList.add('disabled');
        lbl.textContent = state === 'verified' ? 'Online' : 'Offline';
        toggle.checked = state === 'verified';
      }
    }

    const profileAvailToggle = document.getElementById('profileAvailToggle');
    const profileAvailLabel = document.getElementById('profileAvailLabel');
    let isSavingAvailability = false;
    let editModalResolver = null;

    function showProfileToast(message, type = 'success') {
      const old = document.getElementById('profileToast');
      if (old) old.remove();

      const toast = document.createElement('div');
      toast.id = 'profileToast';
      toast.textContent = message;
      toast.style.position = 'fixed';
      toast.style.left = '50%';
      toast.style.bottom = '98px';
      toast.style.transform = 'translateX(-50%)';
      toast.style.maxWidth = '78%';
      toast.style.zIndex = '9999';
      toast.style.padding = '11px 14px';
      toast.style.borderRadius = '12px';
      toast.style.fontSize = '12px';
      toast.style.fontWeight = '800';
      toast.style.textAlign = 'center';
      toast.style.boxShadow = '0 10px 24px rgba(0,0,0,.18)';
      toast.style.border = type === 'success' ? '1px solid #86efac' : '1px solid #fecaca';
      toast.style.background = type === 'success' ? '#dcfce7' : '#fef2f2';
      toast.style.color = type === 'success' ? '#166534' : '#991b1b';
      document.body.appendChild(toast);

      setTimeout(() => {
        toast.style.transition = 'opacity .25s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 260);
      }, 2100);
    }

    function closeEditModal(result) {
      const ol = document.getElementById('editModalOl');
      if (ol) ol.classList.remove('on');
      if (editModalResolver) {
        const resolve = editModalResolver;
        editModalResolver = null;
        resolve(result);
      }
    }

    function submitEditModal() {
      const form = document.getElementById('editModalForm');
      if (!form) {
        closeEditModal(null);
        return;
      }
      const firstInvalid = form.querySelector(':invalid');
      if (firstInvalid) {
        firstInvalid.reportValidity();
        return;
      }
      const formData = new FormData(form);
      const payload = {};
      for (const [k, v] of formData.entries()) payload[k] = String(v);
      closeEditModal(payload);
    }

    function openEditModal(config) {
      const titleEl = document.getElementById('editModalTitle');
      const subEl = document.getElementById('editModalSubtitle');
      const form = document.getElementById('editModalForm');
      const saveBtn = document.getElementById('editModalSaveBtn');
      const ol = document.getElementById('editModalOl');
      if (!titleEl || !subEl || !form || !saveBtn || !ol) return Promise.resolve(null);

      titleEl.textContent = config.title || 'Edit';
      subEl.textContent = config.subtitle || 'Update your details.';
      saveBtn.textContent = config.submitLabel || 'Save';

      form.innerHTML = (config.fields || []).map((field, idx) => {
        const type = field.type || 'text';
        const value = field.value || '';
        const req = field.required ? 'required' : '';
        const ph = field.placeholder || '';
        const max = field.maxLength ? `maxlength="${field.maxLength}"` : '';
        const min = field.minLength ? `minlength="${field.minLength}"` : '';
        return `
          <label class="edit-fg">
            <span class="edit-flbl">${field.label || field.name}</span>
            <input class="edit-fin" type="${type}" name="${field.name}" value="${value.replace(/\"/g, '&quot;')}" placeholder="${ph.replace(/\"/g, '&quot;')}" ${req} ${max} ${min} ${idx === 0 ? 'autofocus' : ''}>
          </label>`;
      }).join('');

      ol.classList.add('on');
      setTimeout(() => form.querySelector('.edit-fin')?.focus(), 20);

      return new Promise(resolve => {
        editModalResolver = resolve;
      });
    }

    function applyAvailability(availability) {
      const isOnline = String(availability || '').toLowerCase() === 'online';
      if (profileAvailToggle) profileAvailToggle.checked = isOnline;
      if (profileAvailLabel) profileAvailLabel.textContent = isOnline ? 'Online' : 'Offline';
    }

    async function syncAvailabilityFromServer() {
      if (!backendIsVerified) {
        applyAvailability('offline');
        return;
      }
      try {
        const res = await fetch('../api/provider_availability_api.php', { cache: 'no-store' });
        const data = await res.json();
        if (data.success) {
          applyAvailability(data.availability || 'offline');
        }
      } catch (e) {
        applyAvailability(backendAvailability);
      }
    }

    if (profileAvailToggle && profileAvailLabel) {
      profileAvailToggle.addEventListener('change', async function () {
        if (!backendIsVerified || isSavingAvailability) {
          applyAvailability('offline');
          return;
        }
        isSavingAvailability = true;
        const desired = this.checked ? 'online' : 'offline';
        const previous = this.checked ? 'offline' : 'online';
        try {
          const fd = new FormData();
          fd.append('availability', desired);
          const res = await fetch('../api/provider_availability_api.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (!data.success) {
            applyAvailability(previous);
            return;
          }
          applyAvailability(data.availability || desired);
        } catch (e) {
          applyAvailability(previous);
        } finally {
          isSavingAvailability = false;
        }
      });
    }

    applyProfileUiState(backendProfileState);
    if (backendProfileState === 'verified') {
      syncAvailabilityFromServer();
    } else {
      applyAvailability('offline');
    }

    function openSettingsScreen() { document.getElementById('settingsScreen').classList.add('on'); }
    function closeSettingsScreen() { document.getElementById('settingsScreen').classList.remove('on'); }
    function openLogoutConfirm() { document.getElementById('logoutConfirmOl').classList.add('on'); }
    function closeLogoutConfirm() { document.getElementById('logoutConfirmOl').classList.remove('on'); }
    function updateProfileUiValues(next) {
      const nameEl = document.getElementById('profileNameValue');
      const phoneEl = document.getElementById('profilePhoneValue');
      const addrEl = document.getElementById('profileAddressValue');
      const setAddrEl = document.getElementById('settingsServiceAreaValue');
      const hoursEl = document.getElementById('profileWorkingHoursValue');

      if (nameEl && typeof next.name === 'string') nameEl.textContent = next.name || 'Service Provider';
      if (phoneEl && typeof next.phone === 'string') phoneEl.textContent = next.phone || 'Not set';
      if (addrEl && typeof next.address === 'string') addrEl.textContent = next.address || 'Not set';
      if (setAddrEl && typeof next.address === 'string') setAddrEl.textContent = next.address || 'Not set';
      if (hoursEl && typeof next.working_hours === 'string') hoursEl.textContent = next.working_hours || 'Not set';
    }

    async function postProfileAction(action, payload) {
      const fd = new FormData();
      fd.append('action', action);
      Object.keys(payload || {}).forEach(k => fd.append(k, payload[k]));
      const res = await fetch('../api/provider_profile_api.php', { method: 'POST', body: fd });
      return res.json();
    }

    async function editPhone() {
      const current = document.getElementById('profilePhoneValue')?.textContent?.trim() || backendPhone || '';
      const result = await openEditModal({
        title: 'Update Phone Number',
        subtitle: 'Use an active number so clients can reach you.',
        submitLabel: 'Update',
        fields: [{ name: 'phone', label: 'Phone', value: current, required: true, maxLength: 40 }]
      });
      if (!result) return;
      const value = String(result.phone || '').trim();
      const data = await postProfileAction('update_phone', { phone: value });
      if (!data.success) {
        showProfileToast(data.message || 'Could not update phone.', 'error');
        return;
      }
      updateProfileUiValues({ phone: data.phone || value });
      showProfileToast('Phone updated.');
    }

    async function editServiceArea() {
      const current = document.getElementById('profileAddressValue')?.textContent?.trim() || backendAddress || '';
      const result = await openEditModal({
        title: 'Update Service Area',
        subtitle: 'Set where you currently accept bookings.',
        submitLabel: 'Update',
        fields: [{ name: 'address', label: 'Service Area', value: current, required: true, maxLength: 255 }]
      });
      if (!result) return;
      const value = String(result.address || '').trim();
      const data = await postProfileAction('update_service_area', { address: value });
      if (!data.success) {
        showProfileToast(data.message || 'Could not update service area.', 'error');
        return;
      }
      updateProfileUiValues({ address: data.address || value });
      showProfileToast('Service area updated.');
    }

    async function editWorkingHours() {
      const current = document.getElementById('profileWorkingHoursValue')?.textContent?.trim() || backendWorkingHours || '';
      const result = await openEditModal({
        title: 'Update Working Hours',
        subtitle: 'Example: Mon-Sat, 8:00 AM - 6:00 PM',
        submitLabel: 'Update',
        fields: [{ name: 'working_hours', label: 'Working Hours', value: current, required: true, maxLength: 120 }]
      });
      if (!result) return;
      const value = String(result.working_hours || '').trim();
      const data = await postProfileAction('update_working_hours', { working_hours: value });
      if (!data.success) {
        showProfileToast(data.message || 'Could not update working hours.', 'error');
        return;
      }
      updateProfileUiValues({ working_hours: data.working_hours || value });
      showProfileToast('Working hours updated.');
    }

    async function openEditProfile() {
      const currentName = document.getElementById('profileNameValue')?.textContent?.trim() || backendName || '';
      const currentPhone = document.getElementById('profilePhoneValue')?.textContent?.trim() || backendPhone || '';
      const currentAddress = document.getElementById('profileAddressValue')?.textContent?.trim() || backendAddress || '';

      const result = await openEditModal({
        title: 'Edit Profile',
        subtitle: 'Update your account basics.',
        submitLabel: 'Save Profile',
        fields: [
          { name: 'name', label: 'Full Name', value: currentName, required: true, maxLength: 120 },
          { name: 'phone', label: 'Phone', value: currentPhone, required: false, maxLength: 40 },
          { name: 'address', label: 'Service Area', value: currentAddress, required: false, maxLength: 255 }
        ]
      });
      if (!result) return;

      const name = String(result.name || '').trim();
      const phone = String(result.phone || '').trim();
      const address = String(result.address || '').trim();

      const data = await postProfileAction('update_profile', {
        name,
        phone,
        address
      });
      if (!data.success) {
        showProfileToast(data.message || 'Could not update profile.', 'error');
        return;
      }
      updateProfileUiValues({
        name: data.name || name,
        phone: data.phone || phone,
        address: data.address || address
      });
      showProfileToast('Profile updated.');
    }

    async function openChangePassword() {
      const result = await openEditModal({
        title: 'Change Password',
        subtitle: 'Use at least 6 characters for your new password.',
        submitLabel: 'Change Password',
        fields: [
          { name: 'current_password', label: 'Current Password', type: 'password', value: '', required: true },
          { name: 'new_password', label: 'New Password', type: 'password', value: '', required: true, minLength: 6 },
          { name: 'confirm_password', label: 'Confirm New Password', type: 'password', value: '', required: true, minLength: 6 }
        ]
      });
      if (!result) return;

      const current = String(result.current_password || '');
      const next = String(result.new_password || '');
      const confirm = String(result.confirm_password || '');

      const data = await postProfileAction('change_password', {
        current_password: current,
        new_password: next,
        confirm_password: confirm
      });
      if (!data.success) {
        showProfileToast(data.message || 'Could not change password.', 'error');
        return;
      }
      showProfileToast('Password updated successfully.');
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeEditModal(null);
    });

    function confirmLogout() {
      closeLogoutConfirm();
      window.location.href = '../logout.php';
    }
  </script>
</body>

</html>
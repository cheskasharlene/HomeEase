<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';

$access = enforceProviderSectionAccess('home', $conn);
$verificationState = $access['state'];
$isVerified = $access['is_verified'];

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Provider');
$providerPhone = htmlspecialchars($_SESSION['provider_phone'] ?? '');
$providerAddress = htmlspecialchars($_SESSION['provider_address'] ?? '');
$providerSpecialty = htmlspecialchars($_SESSION['provider_specialty'] ?? 'Service Provider');

require_once __DIR__ . '/provider_dashboard_data.php';
$dashboardSummary = providerDashboardSummary();
$dashboardReviews = providerDashboardReviews();
$reviewPreview = $dashboardReviews[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase - Provider Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_home.css">
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

    <div class="screen" id="home">
      <div class="ph-scroll" id="phScroll">
        <div class="ph-hdr">
          <div class="ph-top">
            <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:1;">
              <div style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;border:2px solid rgba(255,255,255,.4);">
                <?= strtoupper(substr($providerName, 0, 1)) ?>
              </div>
              <div class="ph-info">
                <div class="ph-greet"><?= $greeting ?></div>
                <div class="ph-name"><?= $providerName ?></div>
                <div class="ph-badge"><i class="bi bi-tools" style="font-size:9px;margin-right:4px;"></i><?= $providerSpecialty ?></div>
                <div class="verified-pill" id="verifiedPill" style="<?= $isVerified ? '' : 'display:none;' ?>"><i class="bi bi-patch-check-fill"></i> Verified Provider</div>
              </div>
            </div>
            <div class="ph-right">
              <div class="ph-btn" onclick="goPage('provider_notifications.php')">
                <i class="bi bi-bell-fill"></i>
                <div class="ph-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
            </div>
          </div>
          <div class="avail-wrap" id="availWrap" style="<?= $isVerified ? '' : 'display:none;' ?>">
            <div class="avail-lbl">Status:</div>
            <label class="avail-sw">
              <input type="checkbox" id="availToggle">
              <span class="avail-slider"></span>
            </label>
            <div class="avail-status" id="availLabel">Offline</div>
          </div>
        </div>

        <?php if (isset($_GET['restricted'])): ?>
          <div class="restriction-note"><i class="bi bi-lock-fill"></i> Verify your account first to unlock Requests, Schedule, and Earnings.</div>
        <?php endif; ?>

        <div class="verify-flow" id="verifyFlow">
          <section class="verify-panel not-verified" id="panelNotVerified">
            <div class="verify-card">
              <div class="verify-card-icon"><i class="bi bi-shield-lock-fill"></i></div>
              <h2>Become a Verified Provider</h2>
              <p>Submit the requirements below. Service-specific fields update automatically after you choose your service.</p>
            </div>

            <div class="verify-card">
              <div class="verify-subttl">Service Type</div>
              <div class="section-divider"></div>
              <input type="text" class="vinput" value="<?= $providerSpecialty ?>" readonly style="background:#f7f7f7;cursor:not-allowed;">
              <input type="hidden" name="selected_service" id="selectedService" value="<?= $providerSpecialty ?>">
            </div>

            <div class="verify-card">
              <div class="verify-subttl">General Requirements</div>
              <div class="section-divider"></div>
              <ul class="verify-checklist">
                <li><i class="bi bi-check-circle-fill"></i> Valid Government ID</li>
                <li><i class="bi bi-check-circle-fill"></i> Selfie Verification</li>
                <li><i class="bi bi-check-circle-fill"></i> Proof of Address</li>
                <li><i class="bi bi-check-circle-fill"></i> Basic Profile Info</li>
                <li><i class="bi bi-check-circle-fill"></i> Selected Service</li>
              </ul>
              <div class="upload-grid" style="margin-top:12px;">
                <label class="upload-slot" for="uploadIdDoc">
                  <input type="file" id="uploadIdDoc" accept="image/*,application/pdf" />
                  <i class="bi bi-card-image"></i>
                  <div>
                    <span>Valid Government ID</span>
                    <small id="fileNameUploadIdDoc">Tap to upload</small>
                  </div>
                </label>
                <label class="upload-slot" for="uploadSelfieDoc">
                  <input type="file" id="uploadSelfieDoc" accept="image/*" />
                  <i class="bi bi-person-bounding-box"></i>
                  <div>
                    <span>Selfie Verification</span>
                    <small id="fileNameUploadSelfieDoc">Tap to upload</small>
                  </div>
                </label>
                <label class="upload-slot" for="uploadAddressDoc">
                  <input type="file" id="uploadAddressDoc" accept="image/*,application/pdf" />
                  <i class="bi bi-house-check-fill"></i>
                  <div>
                    <span>Proof of Address</span>
                    <small id="fileNameUploadAddressDoc">Tap to upload</small>
                  </div>
                </label>
                <label class="upload-slot" for="uploadCertification">
                  <input type="file" id="uploadCertification" accept="image/*,application/pdf" />
                  <i class="bi bi-award-fill"></i>
                  <div>
                    <span id="serviceCertLabel">Barangay Clearance (optional)</span>
                    <small id="fileNameUploadCertification">Tap to upload</small>
                  </div>
                </label>
              </div>
              <div class="profile-grid">
                <div>
                  <label class="vlabel">Full Name</label>
                  <input id="profileName" class="vinput" type="text" value="<?= $providerName ?>">
                </div>
                <div>
                  <label class="vlabel">Phone</label>
                  <input id="profilePhone" class="vinput" type="text" value="<?= $providerPhone ?>">
                </div>
                <div class="full">
                  <label class="vlabel">Address</label>
                  <input id="profileAddress" class="vinput" type="text" value="<?= $providerAddress ?>">
                </div>
              </div>
            </div>

            <div class="verify-card" id="serviceSpecificSection">
              <div class="verify-subttl">Service-Specific Requirements</div>
              <div class="section-divider"></div>

              <div class="mini-section-title">Tools & Kits</div>
              <div class="upload-grid">
                <label class="upload-slot" for="uploadServiceProof">
                  <input type="file" id="uploadServiceProof" accept="image/*,application/pdf" />
                  <i class="bi bi-images"></i>
                  <div>
                    <span id="serviceProofLabel">Tools & Kits</span>
                    <small id="fileNameUploadServiceProof">Tap to upload</small>
                  </div>
                </label>
                <!-- Certification upload moved above under Proof of Address as Barangay Clearance (optional) -->
              </div>

              <div class="mini-section-title">Experience</div>
              <textarea id="experienceDescription" class="vtextarea" rows="4" placeholder="Describe your experience, work samples, and years in this service."></textarea>
              <!-- Removed empty upload slot under Experience section -->
            </div>

            <div class="verify-card">
              <div class="verify-subttl">Submit Section</div>
              <div class="section-divider"></div>
              <div id="dynamicServiceRequirements" class="dynamic-reqs"></div>
              <button class="verify-primary-btn" id="submitRequirementsBtn">Submit Requirements</button>
              <p class="submit-note">Your application will be reviewed by admin.</p>
            </div>
          </section>

          <section class="verify-panel pending" id="panelPending" style="display:none;">
            <div class="verify-card status-card">
              <div class="status-icon pending"><i class="bi bi-hourglass-split"></i></div>
              <h2>Verification in Progress</h2>
              <p id="pendingMessage">Please wait for admin approval.</p>
              <div class="pending-actions">
                <button class="verify-primary-btn" id="simulateApprovalBtn" onclick="simulateApprovalNotification()">Simulate Approval Notification</button>
                <button class="verify-secondary-btn" id="goNotifBtn" style="display:none;" onclick="goPage('provider_notifications.php')">Go to Notifications</button>
              </div>
            </div>
          </section>
        </div>

        <div id="verifiedDashboard" style="display:none;">
          <div class="p-stats-row">
            <div class="p-stat-chip">
              <div class="val">5</div>
              <div class="lbl">Pending</div>
            </div>
            <div class="p-stat-chip">
              <div class="val">2</div>
              <div class="lbl">Active</div>
            </div>
            <div class="p-stat-chip">
              <div class="val">4.8</div>
              <div class="lbl">Rating</div>
            </div>
            <div class="p-stat-chip">
              <div class="val">24</div>
              <div class="lbl">Done</div>
            </div>
          </div>

          <div class="sec-row">
            <div class="sec-ttl">Incoming Requests</div>
            <span class="sec-lnk" onclick="goPage('provider_requests.php')">See all -></span>
          </div>
          <div class="req-list">
            <div class="req-card">
              <div class="req-ic">PL</div>
              <div class="req-body">
                <div class="req-type">Plumbing</div>
                <div class="req-name">John Doe</div>
                <div class="req-meta">Address: 123 Main St<br>Time: Apr 1, 10:00 AM</div>
              </div>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
                <div class="req-price">PHP 2,500</div>
                <div class="req-btns">
                  <button class="btn-accept">Accept</button>
                  <button class="btn-decline">Decline</button>
                </div>
              </div>
            </div>
            <div class="req-card">
              <div class="req-ic">EL</div>
              <div class="req-body">
                <div class="req-type">Electrical</div>
                <div class="req-name">Maria Santos</div>
                <div class="req-meta">Address: Rizal Avenue<br>Time: Apr 2, 2:00 PM</div>
              </div>
              <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
                <div class="req-price">PHP 3,750</div>
                <div class="req-btns">
                  <button class="btn-accept">Accept</button>
                  <button class="btn-decline">Decline</button>
                </div>
              </div>
            </div>
          </div>

          <div class="sec-row">
            <div class="sec-ttl">Today's Schedule</div>
            <span class="sec-lnk" onclick="goPage('provider_schedule.php')">Full calendar -></span>
          </div>
          <div class="sched-list" id="schedList">
            <div class="sched-card">
              <div class="sched-dot"></div>
              <div>
                <div class="sched-time">Loading...</div>
                <div class="sched-title">Fetching today's schedule</div>
              </div>
            </div>
          </div>

          <div class="sec-row">
            <div class="sec-ttl">Earnings This Month</div>
          </div>
          <div class="earn-card clickable" onclick="goPage('provider_earnings.php')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();goPage('provider_earnings.php');}">
            <div class="earn-total">PHP <?= number_format((int) ($dashboardSummary['this_month'] ?? 0)) ?></div>
            <div class="earn-lbl"><?= (int) ($dashboardSummary['jobs_completed'] ?? 0) ?> jobs completed - Goal:
              PHP <?= number_format((int) ($dashboardSummary['monthly_goal'] ?? 0)) ?></div>
            <div class="earn-bar-track">
              <?php
              $goal = max(1, (int) ($dashboardSummary['monthly_goal'] ?? 1));
              $progress = min(100, (int) round(((int) ($dashboardSummary['this_month'] ?? 0) / $goal) * 100));
              ?>
              <div class="earn-bar-fill" style="width:<?= $progress ?>%;"></div>
            </div>
          </div>

          <div class="sec-row">
            <div class="sec-ttl">Recent Reviews</div>
            <span class="sec-lnk" onclick="goPage('provider_reviews.php')">See all -></span>
          </div>
          <div class="rev-list">
            <?php if ($reviewPreview): ?>
              <?php
              $previewName = htmlspecialchars($reviewPreview['customer_name']);
              $previewComment = htmlspecialchars($reviewPreview['comment']);
              $previewRating = (float) ($reviewPreview['rating'] ?? 0);
              $previewStars = str_repeat('*', (int) round($previewRating));
              ?>
              <div class="rev-card clickable" onclick="goPage('provider_reviews.php')" role="button" tabindex="0"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();goPage('provider_reviews.php');}">
                <div class="rev-top">
                  <div class="rev-avatar"><?= strtoupper(substr($previewName, 0, 1)) ?></div>
                  <div>
                    <div class="rev-name"><?= $previewName ?></div>
                    <div class="rev-stars"><?= $previewStars ?></div>
                  </div>
                </div>
                <div class="rev-text"><?= $previewComment ?></div>
              </div>
            <?php else: ?>
              <div class="rev-card">
                <div class="rev-text">No reviews yet.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="bnav" id="providerNav">
        <?php if ($isVerified): ?>
          <div class="ni on" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
          <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span class="nl">Calendar</span></div>
          <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni on" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();

    function showNotice(message, type = 'error') {
      const old = document.getElementById('homeToast');
      if (old) old.remove();

      const toast = document.createElement('div');
      toast.id = 'homeToast';
      toast.textContent = message;
      toast.style.position = 'fixed';
      toast.style.left = '50%';
      toast.style.bottom = '98px';
      toast.style.transform = 'translateX(-50%)';
      toast.style.width = 'min(92%, 420px)';
      toast.style.zIndex = '9999';
      toast.style.padding = '12px 14px';
      toast.style.borderRadius = '12px';
      toast.style.fontSize = '13px';
      toast.style.fontWeight = '800';
      toast.style.boxShadow = '0 10px 26px rgba(0,0,0,.16)';
      toast.style.border = type === 'success' ? '1px solid #86efac' : '1px solid #fecaca';
      toast.style.background = type === 'success' ? '#dcfce7' : '#fef2f2';
      toast.style.color = type === 'success' ? '#166534' : '#991b1b';
      toast.style.textAlign = 'center';
      document.body.appendChild(toast);

      setTimeout(() => {
        toast.style.transition = 'opacity .25s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 260);
      }, 2200);
    }
    const bellDot = document.getElementById('bellDot');
    if (bellDot) bellDot.style.display = 'block';

    const backendVerificationState = <?= json_encode($verificationState) ?>;
    const backendIsVerified = <?= json_encode($isVerified) ?>;

      // SERVICE_CONFIG and all tools/skills/checkboxes logic removed as not needed anymore

    const selectedServiceInput = document.getElementById('selectedService');
    const serviceProofLabel = document.getElementById('serviceProofLabel');
    const serviceCertLabel = document.getElementById('serviceCertLabel');
    const dynamicServiceRequirements = document.getElementById('dynamicServiceRequirements');
    const serviceSpecificSection = document.getElementById('serviceSpecificSection');
    const serviceSpecificNotes = document.getElementById('serviceSpecificNotes');

    // Service selection and tools/skills logic removed

    function bindFileName(inputId, smallId) {
      const input = document.getElementById(inputId);
      const small = document.getElementById(smallId);
      if (!input || !small) return;
      input.addEventListener('change', function () {
        small.textContent = this.files && this.files[0] ? this.files[0].name : 'Tap to upload';
      });
    }

    bindFileName('uploadIdDoc', 'fileNameUploadIdDoc');
    bindFileName('uploadSelfieDoc', 'fileNameUploadSelfieDoc');
    bindFileName('uploadAddressDoc', 'fileNameUploadAddressDoc');
    bindFileName('uploadServiceProof', 'fileNameUploadServiceProof');
    bindFileName('uploadCertification', 'fileNameUploadCertification');

    const toggle = document.getElementById('availToggle');
    const lbl = document.getElementById('availLabel');
    if (toggle && lbl) {
      toggle.addEventListener('change', function () {
        lbl.textContent = this.checked ? 'Online' : 'Offline';
      });
    }

    function setProviderUiState(state) {
      const notVerified = document.getElementById('panelNotVerified');
      const pending = document.getElementById('panelPending');
      const verifiedDashboard = document.getElementById('verifiedDashboard');
      const availWrap = document.getElementById('availWrap');
      const verifiedPill = document.getElementById('verifiedPill');

      document.body.classList.remove('not-verified', 'pending', 'verified');
      document.body.classList.add(state);

      notVerified.style.display = state === 'not-verified' ? 'block' : 'none';
      pending.style.display = state === 'pending' ? 'block' : 'none';
      verifiedDashboard.style.display = state === 'verified' ? 'block' : 'none';
      availWrap.style.display = state === 'verified' ? 'flex' : 'none';
      verifiedPill.style.display = state === 'verified' ? 'inline-flex' : 'none';

      if (toggle && lbl) {
        if (state === 'verified') {
          toggle.checked = true;
          lbl.textContent = 'Online';
        } else {
          toggle.checked = false;
          lbl.textContent = 'Offline';
        }
      }
    }

    function statusClass(statusRaw) {
      const s = String(statusRaw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'completed';
      if (s === 'progress' || s === 'confirmed' || s === 'active') return 'confirmed';
      return 'pending';
    }

    function statusLabel(statusRaw) {
      const s = String(statusRaw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'Completed';
      if (s === 'progress' || s === 'confirmed' || s === 'active') return 'Confirmed';
      return 'Pending';
    }

    function renderTodaySchedule(bookings) {
      const list = document.getElementById('schedList');
      if (!list) return;

      const today = new Date().toISOString().slice(0, 10);
      const todayItems = bookings
        .filter(b => b.date === today)
        .sort((a, b) => String(a.time || '').localeCompare(String(b.time || '')));

      if (!todayItems.length) {
        list.innerHTML = `
          <div class="sched-card">
            <div class="sched-dot" style="background:linear-gradient(135deg,#94a3b8,#64748b);box-shadow:0 2px 6px rgba(100,116,139,.3);"></div>
            <div class="sched-card-main">
              <div class="sched-time" style="color:#64748b;">No bookings today</div>
              <div class="sched-title">You're all caught up for now</div>
              <div class="sched-sub">Tap Full calendar to plan upcoming jobs</div>
            </div>
          </div>`;
        return;
      }

      list.innerHTML = todayItems.slice(0, 3).map(item => {
        const cls = statusClass(item.status_raw || item.status);
        const lblText = statusLabel(item.status_raw || item.status);
        return `
          <div class="sched-card" onclick="goPage('provider_schedule.php?date=${encodeURIComponent(item.date)}')">
            <div class="sched-dot"></div>
            <div class="sched-card-main">
              <div class="sched-time">${item.time || 'All day'}</div>
              <div class="sched-top-row">
                <div class="sched-title">${item.service} with ${item.client_name || 'Client'}</div>
                <span class="sched-status ${cls}">${lblText}</span>
              </div>
              <div class="sched-sub">${item.address || 'Address not specified'}</div>
            </div>
          </div>`;
      }).join('');
    }

    function loadTodaySchedule() {
      if (!backendIsVerified) return;
      fetch('../api/provider_schedule_api.php')
        .then(r => r.json())
        .then(data => {
          if (!data.success) {
            renderTodaySchedule([]);
            return;
          }
          renderTodaySchedule(Array.isArray(data.bookings) ? data.bookings : []);
        })
        .catch(() => renderTodaySchedule([]));
    }

    async function submitRequirements() {
      const selectedService = selectedServiceInput.value.trim();
      if (!selectedService) {
        showNotice('Please select a service first.');
        return;
      }

      const idDoc = document.getElementById('uploadIdDoc').files[0];
      const selfieDoc = document.getElementById('uploadSelfieDoc').files[0];
      const addressDoc = document.getElementById('uploadAddressDoc').files[0];
      if (!idDoc || !selfieDoc || !addressDoc) {
        showNotice('Please upload a valid Government ID, Selfie Verification, and Proof of Address.');
        return;
      }

      const certDoc = document.getElementById('uploadCertification').files[0] || null;
      const serviceProof = document.getElementById('uploadServiceProof').files[0] || null;

      const fd = new FormData();
      fd.append('action', 'upload_documents');
      fd.append('selected_service', selectedService);
      fd.append('profile_name', document.getElementById('profileName').value || '');
      fd.append('profile_phone', document.getElementById('profilePhone').value || '');
      fd.append('profile_address', document.getElementById('profileAddress').value || '');
      fd.append('experience_description', document.getElementById('experienceDescription').value || '');
      
      // Map old field names to new API field names
      fd.append('valid_id', idDoc);
      fd.append('barangay_clearance', certDoc);  // Barangay clearance was in certificate field
      fd.append('selfie', selfieDoc);
      fd.append('proof_of_address', addressDoc);
      if (serviceProof) fd.append('tools_kits', serviceProof);  // Tools & kits was proof_of_experience

      try {
        const res = await fetch('../api/provider_documents_api.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          showNotice(data.message || 'Failed to submit requirements.');
          return;
        }
        setProviderUiState('pending');
        showNotice('Requirements submitted. Please wait for admin approval.', 'success');
      } catch (e) {
        console.error('Error:', e);
        showNotice('Could not submit requirements right now.');
      }
    }

    const submitBtn = document.getElementById('submitRequirementsBtn');
    if (submitBtn) {
      submitBtn.addEventListener('click', submitRequirements);
    }

    function updatePendingStateDetails() {
      const pendingMessage = document.getElementById('pendingMessage');
      const goNotifBtn = document.getElementById('goNotifBtn');
      const simulateApprovalBtn = document.getElementById('simulateApprovalBtn');
      if (!pendingMessage || !goNotifBtn || !simulateApprovalBtn) return;

      if (backendVerificationState === 'approval_ready') {
        pendingMessage.textContent = 'Admin approved your application. Check Notifications to unlock your dashboard.';
        goNotifBtn.style.display = 'block';
        simulateApprovalBtn.style.display = 'none';
      } else {
        pendingMessage.textContent = 'Please wait for admin approval.';
        goNotifBtn.style.display = 'none';
        simulateApprovalBtn.style.display = 'block';
      }
    }

    async function simulateApprovalNotification() {
      try {
        const fd = new FormData();
        fd.append('action', 'simulate_approval_ready');
        const res = await fetch('../api/provider_verification.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          showNotice(data.message || 'Could not simulate approval.');
          return;
        }
        goPage('provider_notifications.php');
      } catch (error) {
        showNotice('Could not simulate approval right now.');
      }
    }

    if (backendVerificationState === 'verified') {
      setProviderUiState('verified');
    } else if (backendVerificationState === 'pending' || backendVerificationState === 'approval_ready') {
      setProviderUiState('pending');
      updatePendingStateDetails();
    } else {
      setProviderUiState('not-verified');
    }

    loadTodaySchedule();
    window.addEventListener('focus', loadTodaySchedule);
    setInterval(loadTodaySchedule, 30000);
  </script>
</body>

</html>

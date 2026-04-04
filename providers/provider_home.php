<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Provider');
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
  <title>HomeEase – Provider Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
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

        <!-- Header -->
        <div class="ph-hdr">
          <div class="ph-top">
            <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:1;">
              <div
                style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;border:2px solid rgba(255,255,255,.4);">
                <?= strtoupper(substr($providerName, 0, 1)) ?>
              </div>
              <div class="ph-info">
                <div class="ph-greet"><?= $greeting ?> 👷</div>
                <div class="ph-name"><?= $providerName ?></div>
                <div class="ph-badge"><i class="bi bi-tools"
                    style="font-size:9px;margin-right:4px;"></i><?= htmlspecialchars($_SESSION['provider_specialty'] ?? 'Service Provider') ?>
                </div>
                <div class="verified-pill" id="verifiedPill" style="display:none;"><i class="bi bi-patch-check-fill"></i> Verified Provider</div>
              </div>
            </div>
            <div class="ph-right">
              <div class="ph-btn" onclick="goPage('provider_notifications.php')">
                <i class="bi bi-bell-fill"></i>
                <div class="ph-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
            </div>
          </div>
          <div class="avail-wrap">
            <div class="avail-lbl">Status:</div>
            <label class="avail-sw">
              <input type="checkbox" id="availToggle">
              <span class="avail-slider"></span>
            </label>
            <div class="avail-status" id="availLabel">Offline</div>
          </div>
        </div>

        <div class="verify-flow" id="verifyFlow">
          <section class="verify-panel not-verified" id="panelNotVerified">
            <div class="verify-card">
              <div class="verify-card-icon"><i class="bi bi-shield-lock-fill"></i></div>
              <h2>Become a Verified Provider</h2>
              <p>Submit your requirements to start receiving bookings.</p>
            </div>

            <div class="verify-card">
              <div class="verify-subttl">Requirements</div>
              <ul class="verify-checklist">
                <li><i class="bi bi-check-circle-fill"></i> Valid ID</li>
                <li><i class="bi bi-check-circle-fill"></i> Proof of Address</li>
                <li><i class="bi bi-check-circle-fill"></i> NBI/Police Clearance</li>
                <li><i class="bi bi-check-circle-fill"></i> Certifications (if applicable)</li>
              </ul>
            </div>

            <div class="verify-card">
              <div class="verify-subttl">Upload Documents</div>
              <div class="upload-grid">
                <label class="upload-slot" for="uploadIdDoc">
                  <input type="file" id="uploadIdDoc" />
                  <i class="bi bi-card-image"></i>
                  <span>Upload ID</span>
                  <small>Tap to upload</small>
                </label>
                <label class="upload-slot" for="uploadAddressDoc">
                  <input type="file" id="uploadAddressDoc" />
                  <i class="bi bi-house-check-fill"></i>
                  <span>Upload Proof of Address</span>
                  <small>Tap to upload</small>
                </label>
                <label class="upload-slot" for="uploadClearanceDoc">
                  <input type="file" id="uploadClearanceDoc" />
                  <i class="bi bi-file-earmark-check-fill"></i>
                  <span>Upload Clearance</span>
                  <small>Tap to upload</small>
                </label>
              </div>
              <button class="verify-primary-btn" onclick="setProviderUiState('pending')">Submit Requirements</button>
            </div>
          </section>

          <section class="verify-panel pending" id="panelPending" style="display:none;">
            <div class="verify-card status-card">
              <div class="status-icon pending"><i class="bi bi-hourglass-split"></i></div>
              <h2>Verification in Progress</h2>
              <p>Your documents are under review. Please wait for approval.</p>
              <div class="pending-actions">
                <button class="verify-secondary-btn" onclick="setProviderUiState('not-verified')">Edit Submission</button>
                <button class="verify-primary-btn" onclick="setProviderUiState('verified')">Simulate Approval</button>
              </div>
            </div>

            <div class="verify-card disabled-modules">
              <div class="verify-subttl">Modules Locked Until Verification</div>
              <div class="disabled-item"><i class="bi bi-lock-fill"></i> Booking Requests</div>
              <div class="disabled-item"><i class="bi bi-lock-fill"></i> Earnings</div>
              <div class="disabled-item"><i class="bi bi-lock-fill"></i> Schedule</div>
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
          <span class="sec-lnk" onclick="goPage('provider_requests.php')">See all →</span>
        </div>
        <div class="req-list">
          <div class="req-card">
            <div class="req-ic">🔧</div>
            <div class="req-body">
              <div class="req-type">Plumbing</div>
              <div class="req-name">John Doe</div>
              <div class="req-meta">📍 123 Main St<br>🕐 Apr 1, 10:00 AM</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
              <div class="req-price">₱2,500</div>
              <div class="req-btns">
                <button class="btn-accept">Accept</button>
                <button class="btn-decline">Decline</button>
              </div>
            </div>
          </div>
          <div class="req-card">
            <div class="req-ic">⚡</div>
            <div class="req-body">
              <div class="req-type">Electrical</div>
              <div class="req-name">Maria Santos</div>
              <div class="req-meta">📍 Rizal Avenue<br>🕐 Apr 2, 2:00 PM</div>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
              <div class="req-price">₱3,750</div>
              <div class="req-btns">
                <button class="btn-accept">Accept</button>
                <button class="btn-decline">Decline</button>
              </div>
            </div>
          </div>
        </div>

   
        <div class="sec-row">
          <div class="sec-ttl">Today's Schedule</div>
          <span class="sec-lnk" onclick="goPage('provider_schedule.php')">Full calendar →</span>
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
        <div class="earn-card clickable" onclick="goPage('provider_earnings.php')" role="button" tabindex="0"
          onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();goPage('provider_earnings.php');}">
          <div class="earn-total">₱<?= number_format((int) ($dashboardSummary['this_month'] ?? 0)) ?></div>
          <div class="earn-lbl"><?= (int) ($dashboardSummary['jobs_completed'] ?? 0) ?> jobs completed · Goal:
            ₱<?= number_format((int) ($dashboardSummary['monthly_goal'] ?? 0)) ?></div>
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
          <span class="sec-lnk" onclick="goPage('provider_reviews.php')">See all →</span>
        </div>
        <div class="rev-list">
          <?php if ($reviewPreview): ?>
            <?php
            $previewName = htmlspecialchars($reviewPreview['customer_name']);
            $previewComment = htmlspecialchars($reviewPreview['comment']);
            $previewRating = (float) ($reviewPreview['rating'] ?? 0);
            $previewStars = str_repeat('★', (int) round($previewRating));
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

      <div class="bnav">
        <div class="ni on" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span
            class="nl">Calendar</span></div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span
            class="nl">Notifications</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();
    document.getElementById('bellDot').style.display = 'block';
    const toggle = document.getElementById('availToggle');
    const lbl = document.getElementById('availLabel');
    toggle.addEventListener('change', function () { lbl.textContent = this.checked ? 'Online' : 'Offline'; });

    function setProviderUiState(state) {
      const notVerified = document.getElementById('panelNotVerified');
      const pending = document.getElementById('panelPending');
      const verifiedDashboard = document.getElementById('verifiedDashboard');
      const availWrap = document.querySelector('.avail-wrap');
      const verifiedPill = document.getElementById('verifiedPill');

      document.body.classList.remove('not-verified', 'pending', 'verified');
      document.body.classList.add(state);

      notVerified.style.display = state === 'not-verified' ? 'block' : 'none';
      pending.style.display = state === 'pending' ? 'block' : 'none';
      verifiedDashboard.style.display = state === 'verified' ? 'block' : 'none';
      availWrap.style.display = state === 'verified' ? 'flex' : 'none';
      verifiedPill.style.display = state === 'verified' ? 'inline-flex' : 'none';

      if (state === 'verified') {
        toggle.checked = true;
        lbl.textContent = 'Online';
      } else {
        toggle.checked = false;
        lbl.textContent = 'Offline';
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
        const lbl = statusLabel(item.status_raw || item.status);
        return `
          <div class="sched-card" onclick="goPage('provider_schedule.php?date=${encodeURIComponent(item.date)}')">
            <div class="sched-dot"></div>
            <div class="sched-card-main">
              <div class="sched-time">${item.time || 'All day'}</div>
              <div class="sched-top-row">
                <div class="sched-title">${item.service} with ${item.client_name || 'Client'}</div>
                <span class="sched-status ${cls}">${lbl}</span>
              </div>
              <div class="sched-sub">${item.address || 'Address not specified'}</div>
            </div>
          </div>`;
      }).join('');
    }

    function loadTodaySchedule() {
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

    setProviderUiState('not-verified');
    loadTodaySchedule();
    window.addEventListener('focus', loadTodaySchedule);
    setInterval(loadTodaySchedule, 30000);
  </script>
</body>

</html>
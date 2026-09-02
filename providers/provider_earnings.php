<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
// Enforce section access (using the existing mysqli connection $conn)
enforceProviderSectionAccess('earnings', $conn);
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');

// Retrieve provider ID from session (assumes $_SESSION['user_id'] per requirements, falls back to $_SESSION['provider_id'])
$providerId = (int) ($_SESSION['user_id'] ?? $_SESSION['provider_id'] ?? 0);

if ($providerId > 0 && $conn instanceof mysqli) {
  ensureRemittancesForProvider($conn, $providerId);
}

// Initialize earnings variables with strict null-coalescing defaults
$todayEarnings = 0.00;
$thisMonthEarnings = 0.00;
$totalEarnings = 0.00;
$recentEarnings = [];

if ($providerId > 0 && $conn instanceof mysqli) {
  // 1. "TODAY" Earnings: Sum of completed/done booking prices matching today's date
  $todayDateStr = date('Y-m-d');
  
  $queryToday = "SELECT SUM(price) AS today_sum 
                 FROM bookings 
                 WHERE provider_id = ? 
                   AND status IN ('completed', 'done') 
                   AND COALESCE(STR_TO_DATE(date, '%Y-%m-%d'), STR_TO_DATE(date, '%b %d, %Y')) = ?";
                   
  if ($stmtToday = $conn->prepare($queryToday)) {
    $stmtToday->bind_param("is", $providerId, $todayDateStr);
    $stmtToday->execute();
    $resultToday = $stmtToday->get_result()->fetch_assoc();
    $todayEarnings = (float) ($resultToday['today_sum'] ?? 0.00);
    $stmtToday->close();
  }

  // 2. "THIS MONTH" Earnings: Sum of completed/done booking prices in the current calendar month and year
  $currentMonth = date('n'); // 1-12
  $currentYear  = date('Y'); // 4-digit year
  
  $queryThisMonth = "SELECT SUM(price) AS this_month_sum 
                     FROM bookings 
                     WHERE provider_id = ? 
                       AND status IN ('completed', 'done') 
                       AND COALESCE(STR_TO_DATE(date, '%Y-%m-%d'), STR_TO_DATE(date, '%b %d, %Y')) IS NOT NULL
                       AND MONTH(COALESCE(STR_TO_DATE(date, '%Y-%m-%d'), STR_TO_DATE(date, '%b %d, %Y'))) = ? 
                       AND YEAR(COALESCE(STR_TO_DATE(date, '%Y-%m-%d'), STR_TO_DATE(date, '%b %d, %Y'))) = ?";
                       
  if ($stmtThisMonth = $conn->prepare($queryThisMonth)) {
    $stmtThisMonth->bind_param("iii", $providerId, $currentMonth, $currentYear);
    $stmtThisMonth->execute();
    $resultThisMonth = $stmtThisMonth->get_result()->fetch_assoc();
    $thisMonthEarnings = (float) ($resultThisMonth['this_month_sum'] ?? 0.00);
    $stmtThisMonth->close();
  }

  // 3. "TOTAL EARNINGS": Sum of all-time completed/done booking prices for this provider
  $queryTotal = "SELECT SUM(price) AS total_sum 
                 FROM bookings 
                 WHERE provider_id = ? 
                   AND status IN ('completed', 'done')";
                   
  if ($stmtTotal = $conn->prepare($queryTotal)) {
    $stmtTotal->bind_param("i", $providerId);
    $stmtTotal->execute();
    $resultTotal = $stmtTotal->get_result()->fetch_assoc();
    $totalEarnings = (float) ($resultTotal['total_sum'] ?? 0.00);
    $stmtTotal->close();
  }

  // 4. "Recent Earnings" List: 10 most recent bookings (completed, done, and pending), ordered by date descending
  $queryRecent = "SELECT service, date, price, status 
                  FROM bookings 
                  WHERE provider_id = ? 
                    AND status IN ('completed', 'done', 'pending') 
                  ORDER BY COALESCE(STR_TO_DATE(date, '%Y-%m-%d'), STR_TO_DATE(date, '%b %d, %Y')) DESC, id DESC 
                  LIMIT 10";
                  
  if ($stmtRecent = $conn->prepare($queryRecent)) {
    $stmtRecent->bind_param("i", $providerId);
    $stmtRecent->execute();
    $resRecent = $stmtRecent->get_result();
    if ($resRecent) {
      while ($row = $resRecent->fetch_assoc()) {
        $recentEarnings[] = $row;
      }
    }
    $stmtRecent->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Earnings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_earnings.css">
</head>

<body>
  <div class="shell" id="app" style="position:relative;">
    <div class="screen" id="earnings">
      <div id="earnScroll">
        <!-- Header -->
        <div class="earn-hdr">
          <div class="earn-hdr-top">
            <div class="earn-back" onclick="goPage('provider_profile.php')">
              <i class="bi bi-arrow-left"></i>
            </div>
            <div class="earn-hdr-title">My Earnings</div>
            <button class="earn-remit-btn" onclick="openRemittanceModal()">
              <i class="bi bi-cash"></i> Remittance
            </button>
          </div>
          
          <!-- Earnings Summary Card -->
          <div class="earn-summary-card">
            <div class="earn-summary-item">
              <div class="earn-sum-lbl">Today</div>
              <div class="earn-sum-val">₱<?= number_format($todayEarnings, 2) ?></div>
            </div>
            <div class="earn-summary-item">
              <div class="earn-sum-lbl">This Month</div>
              <div class="earn-sum-val">₱<?= number_format($thisMonthEarnings, 2) ?></div>
            </div>
            <div class="earn-summary-item">
              <div class="earn-sum-lbl">Total Earnings</div>
              <div class="earn-sum-val">₱<?= number_format($totalEarnings, 2) ?></div>
            </div>
          </div>
        </div>

        <!-- Earnings List -->
        <div class="earn-body">
          <div class="sec-row">
            <div class="sec-ttl">Recent Earnings</div>
          </div>
          <div class="earn-list">
            <?php if (!empty($recentEarnings)): ?>
              <?php foreach ($recentEarnings as $item): ?>
                <?php
                $service = htmlspecialchars($item['service'] ?? 'Service');
                $status = strtolower((string) ($item['status'] ?? 'pending'));
                $statusClass = ($status === 'completed' || $status === 'done') ? 'completed' : 'pending';
                $statusLabel = ($status === 'completed' || $status === 'done') ? 'Completed' : 'Pending';
                
                // Formulate the date label dynamically based on status (matching the mock logic)
                $dateText = $item['date'] ?? 'No date';
                // If it is in YYYY-MM-DD format, parse and format it dynamically for high-fidelity presentation
                $ts = strtotime($dateText);
                $formattedDate = $ts ? date('M j, Y', $ts) : $dateText;
                $dateLabel = (($status === 'completed' || $status === 'done') ? 'Completed on ' : 'Scheduled for ') . htmlspecialchars($formattedDate);
                
                $price = (float) ($item['price'] ?? 0.00);
                ?>
                <div class="earn-card">
                  <div class="earn-card-left">
                    <div class="earn-card-service"><?= $service ?></div>
                    <div class="earn-card-meta"><?= $dateLabel ?></div>
                    <div class="earn-card-status <?= $statusClass ?>"><?= $statusLabel ?></div>
                  </div>
                  <div class="earn-card-amount">+₱<?= number_format($price, 2) ?></div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <div class="empty-icon">💰</div>
                <div class="empty-txt">No earnings data yet.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
        <div class="ni on"><i class="bi bi-cash-stack"></i><span class="nl">Earnings</span></div>
        <div class="ni ni-bell" onclick="goPage('provider_notifications.php')">
          <div class="ni-bell-wrap"><i class="bi bi-bell-fill"></i><span class="ni-badge" id="navBellBadge" style="display:none;"></span></div>
          <span class="nl">Notifs</span>
        </div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>
    </div>

    <!-- Remittance Modal – INSIDE .shell for mobile containment -->
    <div class="remit-modal-overlay" id="remitModal" onclick="handleRemitOverlayClick(event)">
      <div class="remit-modal-card">
        <div class="remit-modal-header">
          <span id="remitModalTitle">Remittance Details</span>
          <button onclick="closeRemittanceModal()" aria-label="Close">
            <i class="bi bi-x-lg"></i>
          </button>
        </div>
        <div class="remit-modal-body">

          <!-- ── Details View ── -->
          <div id="remitDetailsView">
            <div class="remit-amount-card">
              <div class="remit-amount-lbl">Amount Due</div>
              <div class="remit-amount-val" id="remitDtlAmount">₱850.00</div>
            </div>

            <div class="remit-details-box">
              <div class="remit-detail-row">
                <span>Status</span>
                <span><span class="remit-badge pending" id="remitDtlStatus">Pending</span></span>
              </div>
              <div class="remit-detail-row">
                <span>Due Date</span>
                <span id="remitDtlDueDate">June 15, 2026</span>
              </div>
              <div class="remit-detail-row">
                <span>Reference No.</span>
                <span id="remitDtlRef">REF-2026-00824</span>
              </div>
              <div class="remit-detail-row">
                <span>Period</span>
                <span id="remitDtlPeriod">Daily</span>
              </div>
              <div class="remit-detail-row">
                <span>Pay Via</span>
                <span id="remitDtlMethod">GCash</span>
              </div>
            </div>

            <div class="remit-modal-footer">
              <button class="remit-btn-primary" id="remitPayNowBtn" onclick="payRemittance()">
                <i class="bi bi-wallet2"></i> Pay Now
              </button>
              <button class="remit-btn-secondary" onclick="toggleRemitView('history')">
                <i class="bi bi-clock-history"></i> View History
              </button>
            </div>
          </div>

          <!-- ── History View ── -->
          <div id="remitHistoryView" style="display:none;">
            <div class="remit-title-text">Payment History</div>
            <div class="remit-history-list">
              <!-- Populated by JS -->
              <div class="remit-history-item">
                <div class="remit-history-info">
                  <div class="remit-history-ref">REF-2026-00712</div>
                  <div class="remit-history-meta">Paid on Jun 8, 2026 · Daily</div>
                </div>
                <div class="remit-history-amount-status">
                  <div class="remit-history-amt">₱750.00</div>
                  <span class="remit-badge paid">Paid</span>
                </div>
              </div>
            </div>
            <div class="remit-divider"></div>
            <div class="remit-modal-footer">
              <button class="remit-btn-secondary" onclick="toggleRemitView('details')">
                <i class="bi bi-arrow-left"></i> Back to Details
              </button>
            </div>
          </div>

          <!-- ── Pay View ── -->
          <div id="remitPayView" style="display:none;">
            <div class="remit-title-text">Pay via GCash</div>

            <!-- QR Card -->
            <div class="remit-qr-card">
              <img src="../assets/images/admin_gcash_qr.png" alt="Admin GCash QR Code" class="remit-qr-img">
              <div class="remit-qr-account">
                <i class="bi bi-person-fill"></i> LA**E HE****X A.
              </div>
              <div class="remit-qr-number">
                <i class="bi bi-phone-fill"></i> 0981 684 ****
              </div>
            </div>

            <form id="remitPaymentForm" onsubmit="submitRemitPayment(event)">
              <input type="hidden" id="remitPayId" name="remittance_id">

              <!-- File Upload -->
              <div class="remit-upload-area" id="remitUploadArea" onclick="document.getElementById('remitReceiptInput').click()">
                <div class="remit-upload-placeholder" id="remitUploadPlaceholder">
                  <i class="bi bi-cloud-arrow-up-fill"></i>
                  <span>Tap to upload receipt</span>
                  <small>JPG, PNG, or PDF</small>
                </div>
                <img id="remitReceiptPreview" class="remit-receipt-preview" style="display:none;" alt="Receipt preview">
                <input type="file" id="remitReceiptInput" name="receipt" accept="image/*" required
                  onchange="handleReceiptChange(event)" style="display:none;">
              </div>
              <div id="remitReceiptFileName" class="remit-file-name" style="display:none;"></div>

              <div class="remit-modal-footer" style="margin-top:12px;">
                <button type="submit" class="remit-btn-primary" id="remitSubmitBtn">
                  <i class="bi bi-upload"></i> Submit Payment
                </button>
                <button type="button" class="remit-btn-secondary" onclick="toggleRemitView('details')">
                  <i class="bi bi-arrow-left"></i> Cancel
                </button>
              </div>
            </form>
          </div>

    </div>

    <!-- In-App Toast Notification -->
    <div id="appToast" class="app-toast">
      <div class="app-toast-icon" id="appToastIcon"><i class="bi bi-check-circle-fill"></i></div>
      <div class="app-toast-text" id="appToastText">Done!</div>
    </div>

  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    function logRemote(type, info) {
      fetch('../api/log_error.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ type: type, info: info, url: window.location.href })
      }).catch(err => console.error(err));
    }

    window.onerror = function(message, source, lineno, colno, error) {
      logRemote('window_error', {
        message: message,
        source: source,
        lineno: lineno,
        colno: colno,
        error: error ? error.stack : null
      });
      return false;
    };

    if (typeof initTheme === 'function') {
      initTheme();
    }

    function goPage(page) {
      window.location.href = page;
    }

    function openRemittanceModal() {
      const modal = document.getElementById('remitModal');
      modal.style.display = 'flex';
      modal.offsetHeight;
      modal.classList.add('on');
      toggleRemitView('details');
      loadRemittances();
    }

    function closeRemittanceModal() {
      const modal = document.getElementById('remitModal');
      modal.classList.remove('on');
      setTimeout(() => {
        if (!modal.classList.contains('on')) {
          modal.style.display = 'none';
        }
      }, 220);
    }

    function handleRemitOverlayClick(e) {
      if (e.target.id === 'remitModal') {
        closeRemittanceModal();
      }
    }

    let remittancesList = [];
    let activeRemittance = null;

    async function loadRemittances() {
      try {
        logRemote('info', 'loadRemittances called');
        const response = await fetch('../api/provider_remittance_api.php?action=list');
        logRemote('info', 'fetch response status: ' + response.status);
        const text = await response.text();
        logRemote('info', 'fetch response body: ' + text);
        
        let data;
        try {
          data = JSON.parse(text);
        } catch (e) {
          logRemote('error', 'JSON parse failed: ' + e.message);
          return;
        }

        if (data.success && Array.isArray(data.remittances)) {
          remittancesList = data.remittances;
          renderRemittances();
        } else {
          logRemote('info', 'data.success was false or remittances not array: ' + JSON.stringify(data));
        }
      } catch (err) {
        logRemote('error', 'loadRemittances caught exception: ' + err.message + '\nStack: ' + err.stack);
        console.error('Failed to load remittances', err);
      }
    }

    function renderRemittances() {
      activeRemittance = remittancesList.find(r => r.status === 'overdue') ||
                         remittancesList.find(r => r.status === 'pending') ||
                         remittancesList.find(r => r.status === 'submitted');

      if (activeRemittance) {
        document.getElementById('remitDtlAmount').textContent = '₱' + parseFloat(activeRemittance.amount_due).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        
        const badge = document.getElementById('remitDtlStatus');
        badge.className = 'remit-badge ' + activeRemittance.status;
        badge.textContent = activeRemittance.status.charAt(0).toUpperCase() + activeRemittance.status.slice(1);
        
        document.getElementById('remitDtlDueDate').textContent = formatDateString(activeRemittance.due_date);
        document.getElementById('remitDtlRef').textContent = activeRemittance.reference_no;
        document.getElementById('remitDtlPeriod').textContent = 'Daily';
        document.getElementById('remitDtlMethod').textContent = activeRemittance.payment_method && activeRemittance.payment_method !== '-' ? activeRemittance.payment_method : 'GCash';

        document.getElementById('remitPayId').value = activeRemittance.id;

        const payBtn = document.getElementById('remitPayNowBtn');
        if (activeRemittance.status === 'submitted') {
          payBtn.disabled = true;
          payBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Awaiting Verification';
        } else {
          payBtn.disabled = false;
          payBtn.innerHTML = '<i class="bi bi-wallet2"></i> Pay Now';
        }
      } else {
        document.getElementById('remitDtlAmount').textContent = '\u20b10.00';
        const badge = document.getElementById('remitDtlStatus');
        badge.className = 'remit-badge paid';
        badge.textContent = 'Paid';

        document.getElementById('remitDtlDueDate').textContent = '-';
        document.getElementById('remitDtlRef').textContent = '-';
        document.getElementById('remitDtlPeriod').textContent = '-';
        document.getElementById('remitDtlMethod').textContent = '-';

        const payBtn = document.getElementById('remitPayNowBtn');
        payBtn.disabled = true;
        payBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i> All Paid';
      }

      const historyList = document.querySelector('.remit-history-list');
      if (remittancesList.length === 0) {
        historyList.innerHTML = '<div style="text-align:center; padding:20px; font-size:12px; color:#777;">No remittance history found.</div>';
        return;
      }

      historyList.innerHTML = remittancesList.map(r => {
        const statusLabel = r.status.charAt(0).toUpperCase() + r.status.slice(1);
        const metaText = r.status === 'paid' 
          ? 'Paid on ' + formatDateString(r.date_remitted) 
          : (r.status === 'submitted' ? 'Submitted on ' + formatDateString(r.submitted_at) : 'Due ' + formatDateString(r.due_date));
        
        return `
          <div class="remit-history-item">
            <div class="remit-history-info">
              <div class="remit-history-ref">${r.reference_no}</div>
              <div class="remit-history-meta">${metaText} • Daily</div>
            </div>
            <div class="remit-history-amount-status">
              <div class="remit-history-amt">₱${parseFloat(r.amount_due).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
              <span class="remit-badge ${r.status}">${statusLabel}</span>
            </div>
          </div>
        `;
      }).join('');
    }

    function formatDateString(dateStr) {
      if (!dateStr || dateStr === '-') return '-';
      const d = new Date(dateStr);
      if (isNaN(d.getTime())) return dateStr;
      return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function toggleRemitView(view) {
      const detailsView = document.getElementById('remitDetailsView');
      const historyView = document.getElementById('remitHistoryView');
      const payView = document.getElementById('remitPayView');
      const titleText = document.getElementById('remitModalTitle');
      
      detailsView.style.display = 'none';
      historyView.style.display = 'none';
      payView.style.display = 'none';

      if (view === 'history') {
        historyView.style.display = 'block';
        titleText.textContent = 'Remittance History';
      } else if (view === 'pay') {
        payView.style.display = 'block';
        titleText.textContent = 'Pay Remittance';
      } else {
        detailsView.style.display = 'block';
        titleText.textContent = 'Remittance Details';
      }
    }

    function payRemittance() {
      if (activeRemittance) {
        toggleRemitView('pay');
      }
    }

    async function submitRemitPayment(e) {
      e.preventDefault();
      const form = document.getElementById('remitPaymentForm');
      const fileInput = document.getElementById('remitReceiptInput');
      if (!fileInput.files || fileInput.files.length === 0) {
        showToast('Please select a receipt image first.', 'warn');
        return;
      }
      const formData = new FormData(form);
      const submitBtn = document.getElementById('remitSubmitBtn');

      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span style="display:inline-block;width:14px;height:14px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;vertical-align:middle;margin-right:6px;"></span> Submitting...';

      try {
        const res = await fetch('../api/provider_remittance_api.php?action=submit_payment', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (data.success) {
          showToast(data.message || 'Payment submitted successfully!', 'success');
          form.reset();
          resetReceiptUpload();
          await loadRemittances();
          toggleRemitView('details');
        } else {
          showToast(data.message || 'Payment submission failed.', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('An error occurred. Please try again.', 'error');
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-upload"></i> Submit Payment';
      }
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const modal = document.getElementById('remitModal');
        if (modal && modal.classList.contains('on')) {
          closeRemittanceModal();
        }
      }
    });

    /* ── In-App Toast ────────────────────────────────────────── */
    let _toastTimer = null;
    function showToast(message, type = 'success') {
      const toast   = document.getElementById('appToast');
      const iconEl  = document.getElementById('appToastIcon');
      const textEl  = document.getElementById('appToastText');

      const icons = { success: 'bi-check-circle-fill', error: 'bi-x-circle-fill', warn: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill' };
      iconEl.innerHTML = `<i class="bi ${icons[type] || icons.info}"></i>`;
      textEl.textContent = message;

      toast.className = 'app-toast app-toast--' + type + ' app-toast--show';

      if (_toastTimer) clearTimeout(_toastTimer);
      _toastTimer = setTimeout(() => {
        toast.classList.remove('app-toast--show');
      }, 3500);
    }

    /* ── Receipt upload preview ──────────────────────────────── */
    function handleReceiptChange(e) {
      const file = e.target.files[0];
      if (!file) return;
      const placeholder = document.getElementById('remitUploadPlaceholder');
      const preview     = document.getElementById('remitReceiptPreview');
      const fileName    = document.getElementById('remitReceiptFileName');

      placeholder.style.display = 'none';
      preview.style.display = 'block';
      preview.src = URL.createObjectURL(file);
      fileName.style.display = 'block';
      fileName.textContent = file.name;

      document.getElementById('remitUploadArea').classList.add('has-file');
    }

    function resetReceiptUpload() {
      const placeholder = document.getElementById('remitUploadPlaceholder');
      const preview     = document.getElementById('remitReceiptPreview');
      const fileName    = document.getElementById('remitReceiptFileName');
      if (placeholder) placeholder.style.display = '';
      if (preview)     { preview.style.display = 'none'; preview.src = ''; }
      if (fileName)    fileName.style.display = 'none';
      const area = document.getElementById('remitUploadArea');
      if (area) area.classList.remove('has-file');
    }

    document.addEventListener('DOMContentLoaded', loadRemittances);
  </script>
</body>

</html>

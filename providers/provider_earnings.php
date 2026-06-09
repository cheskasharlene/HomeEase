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
  <div class="shell" id="app">
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
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
        <div class="ni on" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span
          class="nl">Earnings</span></div>

        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
      </div>
    </div>
  </div>

  <!-- Remittance Modal -->
  <div class="remit-modal-overlay" id="remitModal" onclick="handleRemitOverlayClick(event)">
    <div class="remit-modal-card">
      <div class="remit-modal-header">
        <span id="remitModalTitle">Remittance Details</span>
        <button onclick="closeRemittanceModal()"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="remit-modal-body">
        
        <!-- Details View -->
        <div id="remitDetailsView">
          <div class="remit-amount-card">
            <div class="remit-amount-lbl">Amount Due</div>
            <div class="remit-amount-val">₱850.00</div>
          </div>
          <div class="remit-details-box">
            <div class="remit-detail-row">
              <span>Remittance Status</span>
              <span><span class="remit-badge pending">Pending</span></span>
            </div>
            <div class="remit-detail-row">
              <span>Due Date</span>
              <span>June 15, 2026</span>
            </div>
            <div class="remit-detail-row">
              <span>Reference Number</span>
              <span>REF-2026-00824</span>
            </div>
            <div class="remit-detail-row">
              <span>Payment Period</span>
              <span>Weekly</span>
            </div>
            <div class="remit-detail-row">
              <span>Payment Method</span>
              <span>GCash / PayMaya / Bank</span>
            </div>
          </div>
          <div class="remit-modal-footer">
            <button class="remit-btn-primary" onclick="payRemittance()">
              <i class="bi bi-wallet2"></i> Pay Now
            </button>
            <button class="remit-btn-secondary" onclick="toggleRemitView('history')">
              <i class="bi bi-clock-history"></i> View Remittance History
            </button>
          </div>
        </div>

        <!-- History View (Hidden by default) -->
        <div id="remitHistoryView" style="display: none;">
          <div class="remit-title-text">Past Payments</div>
          <div class="remit-history-list">
            
            <div class="remit-history-item">
              <div class="remit-history-info">
                <div class="remit-history-ref">REF-2026-00712</div>
                <div class="remit-history-meta">Paid on Jun 8, 2026 • Weekly</div>
              </div>
              <div class="remit-history-amount-status">
                <div class="remit-history-amt">₱750.00</div>
                <span class="remit-badge paid">Paid</span>
              </div>
            </div>

            <div class="remit-history-item">
              <div class="remit-history-info">
                <div class="remit-history-ref">REF-2026-00605</div>
                <div class="remit-history-meta">Paid on Jun 1, 2026 • Weekly</div>
              </div>
              <div class="remit-history-amount-status">
                <div class="remit-history-amt">₱1,200.00</div>
                <span class="remit-badge paid">Paid</span>
              </div>
            </div>

            <div class="remit-history-item">
              <div class="remit-history-info">
                <div class="remit-history-ref">REF-2026-00511</div>
                <div class="remit-history-meta">Due May 25, 2026 • Weekly</div>
              </div>
              <div class="remit-history-amount-status">
                <div class="remit-history-amt">₱950.00</div>
                <span class="remit-badge overdue">Overdue</span>
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

      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();

    function goPage(page) {
      window.location.href = page;
    }

    function openRemittanceModal() {
      const modal = document.getElementById('remitModal');
      modal.style.display = 'flex';
      // Force layout reflow
      modal.offsetHeight;
      modal.classList.add('on');
      toggleRemitView('details');
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

    function toggleRemitView(view) {
      const detailsView = document.getElementById('remitDetailsView');
      const historyView = document.getElementById('remitHistoryView');
      const titleText = document.getElementById('remitModalTitle');
      
      if (view === 'history') {
        detailsView.style.display = 'none';
        historyView.style.display = 'block';
        titleText.textContent = 'Remittance History';
      } else {
        detailsView.style.display = 'block';
        historyView.style.display = 'none';
        titleText.textContent = 'Remittance Details';
      }
    }

    function payRemittance() {
      alert('Remittance payment functionality will be available in a future update.');
    }

    // Keyboard accessibility
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        const modal = document.getElementById('remitModal');
        if (modal && modal.classList.contains('on')) {
          closeRemittanceModal();
        }
      }
    });
  </script>
</body>

</html>

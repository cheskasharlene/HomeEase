<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');
require_once __DIR__ . '/provider_dashboard_data.php';
$dashboardSummary = providerDashboardSummary();
$dashboardEarnings = providerDashboardEarnings();
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
  <style>
    body {
      background: #fff;
    }

    .screen {
      background: #F9F5EF;
      align-items: stretch;
      justify-content: flex-start;
    }

    #earnScroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 90px;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    #earnScroll::-webkit-scrollbar {
      display: none;
    }

    .earn-hdr {
      width: 100%;
      padding: 48px 22px 24px;
      background: linear-gradient(145deg, #C86500 0%, #D97108 20%, #E8820C 42%, #F5A623 65%, #FFB347 85%, #FFC96B 100%);
      border-radius: 0 0 30px 30px;
      box-shadow: 0 8px 32px rgba(232, 130, 12, .28);
      position: relative;
      overflow: hidden;
    }

    .earn-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .07) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }

    .earn-hdr-top {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
      position: relative;
      z-index: 1;
    }

    .earn-back {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, .2);
      backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255, 255, 255, .3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 18px;
      cursor: pointer;
      transition: background .2s;
    }

    .earn-back:hover {
      background: rgba(255, 255, 255, .3);
    }

    .earn-hdr-title {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, .1);
      position: relative;
      z-index: 1;
    }

    .earn-summary-card {
      background: rgba(255, 255, 255, .15);
      backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255, 255, 255, .25);
      border-radius: 16px;
      padding: 16px 18px;
      position: relative;
      z-index: 1;
      margin-bottom: 10px;
    }

    .earn-summary-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 12px;
    }

    .earn-summary-item:last-child {
      margin-bottom: 0;
    }

    .earn-sum-lbl {
      font-size: 11px;
      font-weight: 600;
      color: rgba(255, 255, 255, .75);
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .earn-sum-val {
      font-family: 'Poppins', sans-serif;
      font-size: 15px;
      font-weight: 800;
      color: #fff;
    }

    .earn-body {
      padding: 16px 12px 0;
    }

    .sec-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 12px 10px;
    }

    .sec-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 16px;
      font-weight: 800;
      color: #1A1A2E;
    }

    .earn-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 0 12px;
    }

    .earn-card {
      background: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .06);
      border: 1.5px solid #EDE8E0;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .earn-card-left {
      flex: 1;
    }

    .earn-card-service {
      font-size: 13px;
      font-weight: 700;
      color: #1A1A2E;
    }

    .earn-card-meta {
      font-size: 11px;
      color: #8E8E93;
      margin-top: 3px;
    }

    .earn-card-amount {
      font-size: 14px;
      font-weight: 800;
      color: #10b981;
      font-family: 'Poppins', sans-serif;
    }

    .earn-card-status {
      font-size: 10px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 8px;
      margin-top: 4px;
    }

    .earn-card-status.completed {
      background: #d1fae5;
      color: #065f46;
    }

    .earn-card-status.pending {
      background: #fef3c7;
      color: #b45309;
    }

    .empty-state {
      text-align: center;
      padding: 40px 24px;
      color: #8E8E93;
    }

    .empty-icon {
      font-size: 48px;
      margin-bottom: 12px;
      opacity: .5;
    }

    .empty-txt {
      font-size: 14px;
      font-weight: 600;
    }

    .bnav {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff !important;
      border-top: 1px solid #EDE8E0 !important;
      display: flex;
      padding: 9px 0 calc(12px + env(safe-area-inset-bottom));
      box-shadow: 0 -4px 20px rgba(232, 130, 12, .07);
      z-index: 50;
    }

    .ni {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      cursor: pointer;
      color: #C5BEB3;
      font-family: "Nunito", sans-serif;
      padding: 2px 0;
    }

    .ni i {
      font-size: 22px;
    }

    .ni.on,
    .ni.on i,
    .ni.on .nl {
      color: #F5A623;
    }

    .nl {
      font-size: 10px;
      font-weight: 700;
    }

    .h-pb {
      height: 90px;
    }
  </style>
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
          </div>
          
          <!-- Earnings Summary Card -->
          <div class="earn-summary-card">
            <div class="earn-summary-item">
              <div class="earn-sum-lbl">This Month</div>
              <div class="earn-sum-val">₱<?= number_format((int) ($dashboardSummary['this_month'] ?? 0)) ?></div>
            </div>
            <div class="earn-summary-item">
              <div class="earn-sum-lbl">Total Earnings</div>
              <div class="earn-sum-val">₱<?= number_format((int) ($dashboardSummary['total_earnings'] ?? 0)) ?></div>
            </div>
            <div class="earn-summary-item">
              <div class="earn-sum-lbl">Pending Payout</div>
              <div class="earn-sum-val">₱<?= number_format((int) ($dashboardSummary['pending_payout'] ?? 0)) ?></div>
            </div>
          </div>
        </div>

        <!-- Earnings List -->
        <div class="earn-body">
          <div class="sec-row">
            <div class="sec-ttl">Recent Earnings</div>
          </div>
          <div class="earn-list">
            <?php if (!empty($dashboardEarnings)): ?>
              <?php foreach ($dashboardEarnings as $item): ?>
                <?php
                $service = htmlspecialchars($item['service'] ?? 'Service');
                $meta = htmlspecialchars($item['date_label'] ?? '');
                $status = strtolower((string) ($item['status'] ?? 'pending')) === 'completed' ? 'completed' : 'pending';
                $statusLabel = $status === 'completed' ? 'Completed' : 'Pending';
                $amount = (int) ($item['amount'] ?? 0);
                ?>
                <div class="earn-card">
                  <div class="earn-card-left">
                    <div class="earn-card-service"><?= $service ?></div>
                    <div class="earn-card-meta"><?= $meta ?></div>
                    <div class="earn-card-status <?= $status ?>"><?= $statusLabel ?></div>
                  </div>
                  <div class="earn-card-amount">+₱<?= number_format($amount) ?></div>
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

        <div class="h-pb"></div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
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
    (function () { const ic = document.getElementById('dmIcon'); if (ic && document.body.classList.contains('dark')) ic.className = 'bi bi-sun-fill'; })();

    function goPage(page) {
      window.location.href = page;
    }
  </script>
</body>

</html>

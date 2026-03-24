<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');
require_once __DIR__ . '/provider_dashboard_data.php';
$dashboardReviews = providerDashboardReviews();
$reviewsCount = count($dashboardReviews);
$avgRating = $reviewsCount ? array_sum(array_map(static fn($r) => (float) ($r['rating'] ?? 0), $dashboardReviews)) / $reviewsCount : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Reviews</title>
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

    #revScroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 90px;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    #revScroll::-webkit-scrollbar {
      display: none;
    }

    .rev-hdr {
      width: 100%;
      padding: 48px 22px 24px;
      background: linear-gradient(145deg, #C86500 0%, #D97108 20%, #E8820C 42%, #F5A623 65%, #FFB347 85%, #FFC96B 100%);
      border-radius: 0 0 30px 30px;
      box-shadow: 0 8px 32px rgba(232, 130, 12, .28);
      position: relative;
      overflow: hidden;
    }

    .rev-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .07) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }

    .rev-hdr-top {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
      position: relative;
      z-index: 1;
    }

    .rev-back {
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

    .rev-back:hover {
      background: rgba(255, 255, 255, .3);
    }

    .rev-hdr-title {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, .1);
      position: relative;
      z-index: 1;
    }

    .rev-rating-card {
      background: rgba(255, 255, 255, .15);
      backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255, 255, 255, .25);
      border-radius: 16px;
      padding: 14px 16px;
      text-align: center;
      position: relative;
      z-index: 1;
      margin-bottom: 10px;
    }

    .rev-avg-stars {
      font-size: 20px;
      color: #fff;
      margin-bottom: 4px;
      letter-spacing: 1px;
    }

    .rev-avg-text {
      font-size: 11px;
      color: rgba(255, 255, 255, .75);
      font-weight: 600;
      letter-spacing: .5px;
    }

    .rev-body {
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

    .rev-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 0 12px;
    }

    .rev-card {
      background: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .06);
      border: 1.5px solid #EDE8E0;
    }

    .rev-top {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 8px;
    }

    .rev-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 14px;
      font-weight: 800;
      flex-shrink: 0;
    }

    .rev-info {
      flex: 1;
    }

    .rev-name {
      font-size: 13px;
      font-weight: 700;
      color: #1A1A2E;
    }

    .rev-stars {
      font-size: 13px;
      color: #f59e0b;
      margin-top: 1px;
    }

    .rev-date {
      font-size: 10px;
      color: #8E8E93;
      margin-top: 1px;
    }

    .rev-text {
      font-size: 13px;
      color: #8E8E93;
      line-height: 1.5;
      margin-top: 8px;
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
    <div class="screen" id="reviews">
      <div id="revScroll">
        <!-- Header -->
        <div class="rev-hdr">
          <div class="rev-hdr-top">
            <div class="rev-back" onclick="goPage('provider_profile.php')">
              <i class="bi bi-arrow-left"></i>
            </div>
            <div class="rev-hdr-title">My Reviews</div>
          </div>
          
          <!-- Average Rating Card -->
          <div class="rev-rating-card">
            <div class="rev-avg-stars">★★★★★</div>
            <div class="rev-avg-text"><?= number_format($avgRating, 1) ?> Overall Rating (<?= $reviewsCount ?> Reviews)</div>
          </div>
        </div>

        <!-- Reviews List -->
        <div class="rev-body">
          <div class="sec-row">
            <div class="sec-ttl">All Reviews</div>
          </div>
          <div class="rev-list">
            <?php if (!empty($dashboardReviews)): ?>
              <?php foreach ($dashboardReviews as $review): ?>
                <?php
                $name = htmlspecialchars($review['customer_name'] ?? 'Customer');
                $comment = htmlspecialchars($review['comment'] ?? '');
                $rating = (float) ($review['rating'] ?? 0);
                $stars = str_repeat('★', (int) round($rating));
                $dateText = !empty($review['date']) ? date('M j, Y', strtotime($review['date'])) : 'No date';
                ?>
                <div class="rev-card">
                  <div class="rev-top">
                    <div class="rev-avatar"><?= strtoupper(substr($name, 0, 1)) ?></div>
                    <div class="rev-info">
                      <div class="rev-name"><?= $name ?></div>
                      <div class="rev-stars"><?= $stars ?> · <?= number_format($rating, 1) ?></div>
                      <div class="rev-date"><?= $dateText ?></div>
                    </div>
                  </div>
                  <div class="rev-text"><?= $comment ?></div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state">
                <div class="empty-icon">⭐</div>
                <div class="empty-txt">No reviews yet.</div>
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

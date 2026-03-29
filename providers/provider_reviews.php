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
  <link rel="stylesheet" href="../assets/css/provider_reviews.css">
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

<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_dashboard_data.php';

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$providerSpecialty = trim((string) ($_SESSION['provider_specialty'] ?? ''));
$jobs = providerJobHistory($conn, $providerId, $providerSpecialty);

$filter = strtolower(trim((string) ($_GET['filter'] ?? 'all')));
if (!in_array($filter, ['all', 'with-review', 'no-review'], true)) {
  $filter = 'all';
}

$filteredJobs = array_values(array_filter($jobs, static function (array $job) use ($filter): bool {
  if ($filter === 'with-review') {
    return !empty($job['review_comment']);
  }
  if ($filter === 'no-review') {
    return empty($job['review_comment']);
  }
  return true;
}));

$totalJobs = count($jobs);
$withReviewCount = count(array_filter($jobs, static fn(array $j): bool => !empty($j['review_comment'])));
$noReviewCount = $totalJobs - $withReviewCount;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Job History</title>
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

    #jhScroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 92px;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    #jhScroll::-webkit-scrollbar {
      display: none;
    }

    .jh-hdr {
      width: 100%;
      padding: 48px 22px 24px;
      background: linear-gradient(145deg, #C86500 0%, #D97108 20%, #E8820C 42%, #F5A623 65%, #FFB347 85%, #FFC96B 100%);
      border-radius: 0 0 30px 30px;
      box-shadow: 0 8px 32px rgba(232, 130, 12, .28);
      position: relative;
      overflow: hidden;
    }

    .jh-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .07) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }

    .jh-top {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 16px;
      position: relative;
      z-index: 1;
    }

    .jh-back {
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

    .jh-back:hover {
      background: rgba(255, 255, 255, .3);
    }

    .jh-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, .1);
    }

    .jh-summary {
      background: rgba(255, 255, 255, .14);
      border: 1.5px solid rgba(255, 255, 255, .24);
      border-radius: 16px;
      padding: 12px 14px;
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 8px;
      position: relative;
      z-index: 1;
    }

    .jh-s-item {
      text-align: center;
    }

    .jh-s-val {
      font-family: 'Poppins', sans-serif;
      font-size: 17px;
      font-weight: 800;
      color: #fff;
    }

    .jh-s-lbl {
      font-size: 10px;
      color: rgba(255, 255, 255, .8);
      font-weight: 700;
      letter-spacing: .4px;
      text-transform: uppercase;
    }

    .jh-body {
      padding: 14px 12px 0;
    }

    .filter-row {
      display: flex;
      gap: 8px;
      padding: 0 12px 10px;
      overflow-x: auto;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    .filter-row::-webkit-scrollbar {
      display: none;
    }

    .filter-chip {
      padding: 8px 12px;
      border-radius: 999px;
      border: 1.5px solid #EDE8E0;
      background: #fff;
      color: #8E8E93;
      font-size: 12px;
      font-weight: 700;
      text-decoration: none;
      white-space: nowrap;
    }

    .filter-chip.on {
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff;
      border-color: #F5A623;
    }

    .job-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding: 0 12px;
    }

    .job-card {
      background: #fff;
      border-radius: 16px;
      padding: 14px 16px;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .06);
      border: 1.5px solid #EDE8E0;
    }

    .job-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 8px;
    }

    .job-service {
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      font-weight: 800;
      color: #1A1A2E;
    }

    .job-client {
      font-size: 12px;
      color: #8E8E93;
      margin-top: 2px;
    }

    .job-status {
      font-size: 10px;
      font-weight: 800;
      padding: 4px 8px;
      border-radius: 999px;
      background: #d1fae5;
      color: #047857;
      white-space: nowrap;
    }

    .job-meta {
      font-size: 12px;
      color: #8E8E93;
      margin-top: 8px;
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .job-review {
      margin-top: 10px;
      background: #FFF8F0;
      border: 1px solid #FFE5B4;
      border-radius: 12px;
      padding: 10px 12px;
    }

    .job-review-rating {
      font-size: 12px;
      font-weight: 800;
      color: #F5A623;
      margin-bottom: 4px;
    }

    .job-review-text {
      font-size: 12px;
      color: #8E8E93;
      line-height: 1.45;
    }

    .job-no-review {
      margin-top: 10px;
      font-size: 12px;
      color: #8E8E93;
      font-style: italic;
    }

    .empty {
      background: #fff;
      border-radius: 16px;
      padding: 24px 16px;
      text-align: center;
      color: #8E8E93;
      box-shadow: 0 3px 14px rgba(232, 130, 12, .06);
      border: 1.5px solid #EDE8E0;
      margin: 0 12px;
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
    <div class="screen" id="jobHistory">
      <div id="jhScroll">
        <div class="jh-hdr">
          <div class="jh-top">
            <div class="jh-back" onclick="goPage('provider_profile.php')">
              <i class="bi bi-arrow-left"></i>
            </div>
            <div class="jh-ttl">Job History</div>
          </div>
          <div class="jh-summary">
            <div class="jh-s-item">
              <div class="jh-s-val"><?= $totalJobs ?></div>
              <div class="jh-s-lbl">Completed</div>
            </div>
            <div class="jh-s-item">
              <div class="jh-s-val"><?= $withReviewCount ?></div>
              <div class="jh-s-lbl">With Review</div>
            </div>
            <div class="jh-s-item">
              <div class="jh-s-val"><?= $noReviewCount ?></div>
              <div class="jh-s-lbl">No Review</div>
            </div>
          </div>
        </div>

        <div class="jh-body">
          <div class="filter-row">
            <a class="filter-chip <?= $filter === 'all' ? 'on' : '' ?>" href="provider_job_history.php?filter=all">All</a>
            <a class="filter-chip <?= $filter === 'with-review' ? 'on' : '' ?>" href="provider_job_history.php?filter=with-review">With Reviews</a>
            <a class="filter-chip <?= $filter === 'no-review' ? 'on' : '' ?>" href="provider_job_history.php?filter=no-review">No Reviews</a>
          </div>

          <?php if (empty($filteredJobs)): ?>
            <div class="empty">No completed jobs match this filter.</div>
          <?php else: ?>
            <div class="job-list">
              <?php foreach ($filteredJobs as $job): ?>
                <?php
                $service = htmlspecialchars((string) ($job['service'] ?? 'Service'));
                $clientName = htmlspecialchars((string) ($job['client_name'] ?? 'Client'));
                $dateText = htmlspecialchars((string) ($job['date_text'] ?? 'No date'));
                $timeText = htmlspecialchars((string) ($job['time_text'] ?? ''));
                $statusText = htmlspecialchars((string) ($job['status_text'] ?? 'Completed'));
                $reviewComment = (string) ($job['review_comment'] ?? '');
                $reviewRating = $job['review_rating'] !== null ? (float) $job['review_rating'] : null;
                ?>
                <div class="job-card">
                  <div class="job-head">
                    <div>
                      <div class="job-service"><?= $service ?></div>
                      <div class="job-client">Client: <?= $clientName ?></div>
                    </div>
                    <span class="job-status"><?= $statusText ?></span>
                  </div>
                  <div class="job-meta">
                    <span><i class="bi bi-calendar3"></i> <?= $dateText ?></span>
                    <?php if ($timeText !== ''): ?>
                      <span><i class="bi bi-clock"></i> <?= $timeText ?></span>
                    <?php endif; ?>
                  </div>

                  <?php if ($reviewComment !== ''): ?>
                    <div class="job-review">
                      <div class="job-review-rating">⭐ <?= number_format((float) $reviewRating, 1) ?></div>
                      <div class="job-review-text"><?= htmlspecialchars($reviewComment) ?></div>
                    </div>
                  <?php else: ?>
                    <div class="job-no-review">No review yet.</div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
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

    function goPage(page) {
      window.location.href = page;
    }
  </script>
</body>

</html>

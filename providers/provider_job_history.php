<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/provider_access.php';

require_once __DIR__ . '/../api/db.php';
enforceProviderSectionAccess('job_history', $conn);
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
  <link rel="stylesheet" href="../assets/css/provider_job_history.css">
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
        <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span
          class="nl">Earnings</span></div>
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

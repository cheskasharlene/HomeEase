<?php

/**
 * Fetch real reviews for a provider from the database.
 * Returns an array of review rows or an empty array if none found.
 */
function providerDashboardReviews(mysqli $conn, int $providerId): array
{
  if ($providerId <= 0) {
    return [];
  }

  $stmt = $conn->prepare("
    SELECT
      COALESCE(u.name, 'Client')       AS customer_name,
      r.rating,
      r.comment,
      DATE(r.created_at)               AS date
    FROM   provider_reviews r
    LEFT   JOIN users u ON u.id = r.user_id
    WHERE  r.provider_id = ?
    ORDER  BY r.created_at DESC
    LIMIT  50
  ");

  if (!$stmt) {
    return [];
  }

  $stmt->bind_param('i', $providerId);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  return $rows;
}

function providerDashboardEarnings(): array
{
  return [
    [
      'service' => 'Bathroom House Cleaner',
      'date_label' => 'Completed on Mar 20, 2026',
      'status' => 'completed',
      'amount' => 1500
    ],
    [
      'service' => 'Electrical Repair',
      'date_label' => 'Completed on Mar 18, 2026',
      'status' => 'completed',
      'amount' => 2200
    ],
    [
      'service' => 'Kitchen Plumbing',
      'date_label' => 'Completed on Mar 15, 2026',
      'status' => 'completed',
      'amount' => 1800
    ],
    [
      'service' => 'Garden Maintenance',
      'date_label' => 'Scheduled for Mar 25, 2026',
      'status' => 'pending',
      'amount' => 1200
    ],
    [
      'service' => 'Appliance Installation',
      'date_label' => 'Completed on Mar 12, 2026',
      'status' => 'completed',
      'amount' => 2500
    ],
  ];
}

function providerDashboardSummary(): array
{
  return [
    'this_month' => 12400,
    'total_earnings' => 48750,
    'pending_payout' => 2100,
    'jobs_completed' => 24,
    'monthly_goal' => 16500,
  ];
}

function normalizeServiceKey(string $value): string
{
  $v = strtolower(trim($value));
  $v = preg_replace('/[^a-z0-9\s]/', ' ', $v);
  if ($v === '') {
    return '';
  }

  if (strpos($v, 'clean') !== false) return 'clean';
  if (strpos($v, 'plumb') !== false) return 'plumb';
  if (strpos($v, 'electric') !== false) return 'electric';
  if (strpos($v, 'paint') !== false) return 'paint';
  if (strpos($v, 'laundry') !== false) return 'laundry';
  if (strpos($v, 'carpenter') !== false) return 'carpenter';
  if (strpos($v, 'helper') !== false) return 'helper';
  if (strpos($v, 'appliance') !== false) return 'appliance';
  if (strpos($v, 'garden') !== false) return 'garden';

  return $v;
}

function serviceMatches(string $providerService, string $requestService): bool
{
  $p = normalizeServiceKey($providerService);
  $r = normalizeServiceKey($requestService);
  if ($p === '' || $r === '') {
    return false;
  }
  if ($p === $r) {
    return true;
  }
  return stripos($r, $p) !== false || stripos($p, $r) !== false;
}

function providerIncomingRequests(mysqli $conn, int $providerId, int $limit = 2): array
{
  if ($providerId <= 0) {
    return [];
  }

  $provStmt = $conn->prepare('SELECT service_category FROM service_providers WHERE provider_id = ? LIMIT 1');
  if (!$provStmt) {
    return [];
  }
  $provStmt->bind_param('i', $providerId);
  $provStmt->execute();
  $provRow = $provStmt->get_result()->fetch_assoc();
  $provStmt->close();

  if (!$provRow) {
    return [];
  }

  $providerService = (string) ($provRow['service_category'] ?? '');
  if ($providerService === '') {
    return [];
  }

  $limitSql = max(1, (int) $limit);
  $sql = "SELECT id, booking_id, service, fixed_price, date, time_slot, address, customer_name
          FROM booking_requests
          WHERE provider_id = ? AND status = 'pending'
          ORDER BY created_at DESC
          LIMIT $limitSql";
  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }
  $stmt->bind_param('i', $providerId);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  $rows = array_values(array_filter($rows, function ($row) use ($providerService) {
    return serviceMatches($providerService, (string) ($row['service'] ?? ''));
  }));

  return $rows;
}

function providerJobHistory(mysqli $conn, int $providerId, string $providerSpecialty = ''): array
{
  // Fetch all real reviews for this provider, keyed by booking_id,
  // so we can attach them to job history rows without a second query per row.
  $revMap = [];
  $revStmt = $conn->prepare("
    SELECT booking_id, rating, comment
    FROM   provider_reviews
    WHERE  provider_id = ?
  ");
  if ($revStmt) {
    $revStmt->bind_param('i', $providerId);
    $revStmt->execute();
    foreach ($revStmt->get_result()->fetch_all(MYSQLI_ASSOC) as $rev) {
      $revMap[(int) $rev['booking_id']] = $rev;
    }
    $revStmt->close();
  }

  $colRes = $conn->query("SHOW COLUMNS FROM bookings");
  if (!$colRes) {
    return [];
  }

  $bcols = [];
  while ($c = $colRes->fetch_assoc()) {
    $bcols[] = $c['Field'];
  }

  $has = function (string $col) use ($bcols): bool {
    return in_array($col, $bcols, true);
  };

  $select = "b.id, b.service, b.status, b.date, b.address, b.created_at, COALESCE(u.name, 'Client') AS client_name";
  if ($has('time_slot')) {
    $select .= ", b.time_slot";
  }

  $where = [];
  $params = [];
  $types = '';

  if ($has('provider_id')) {
    $where[] = "b.provider_id = ?";
    $types .= 'i';
    $params[] = $providerId;
  } else {
    return [];
  }

  $where[] = "LOWER(b.status) IN ('done','completed')";
  $whereSql = 'WHERE ' . implode(' AND ', $where);

  $orderSql = $has('time_slot')
    ? "ORDER BY b.date DESC, b.time_slot DESC, b.created_at DESC"
    : "ORDER BY b.date DESC, b.created_at DESC";

  $sql = "SELECT $select
          FROM bookings b
          LEFT JOIN users u ON b.user_id = u.id
          $whereSql
          $orderSql";

  $stmt = $conn->prepare($sql);
  if (!$stmt) {
    return [];
  }

  if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
  }

  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  $items = [];
  foreach ($rows as $row) {
    $bookingId  = (int)   ($row['id']          ?? 0);
    $clientName = (string) ($row['client_name'] ?? 'Client');
    // Look up review by booking_id — accurate and no name-collision risk.
    $review     = $revMap[$bookingId] ?? null;

    $dateRaw = (string) ($row['date'] ?? '');
    $timeRaw = trim((string) ($row['time_slot'] ?? ''));
    $ts      = strtotime($dateRaw);
    $dateText = $ts ? date('M j, Y', $ts) : 'No date';

    if ($timeRaw === '' && $ts) {
      $timePart = date('H:i:s', $ts);
      if ($timePart !== '00:00:00') {
        $timeRaw = date('g:i A', $ts);
      }
    }

    $items[] = [
      'id'             => $bookingId,
      'service'        => (string) ($row['service'] ?? 'Service'),
      'client_name'    => $clientName,
      'date_text'      => $dateText,
      'time_text'      => $timeRaw !== '' ? $timeRaw : null,
      'status_text'    => 'Completed',
      'review_rating'  => $review ? (float) ($review['rating']  ?? 0)  : null,
      'review_comment' => $review ? (string) ($review['comment'] ?? '') : null,
    ];
  }

  return $items;
}

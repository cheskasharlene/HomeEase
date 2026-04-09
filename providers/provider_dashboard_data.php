<?php

function providerDashboardReviews(): array
{
  return [
    [
      'customer_name' => 'Anna K.',
      'rating' => 5.0,
      'comment' => 'Great work, very professional and on time! Would definitely hire again.',
      'date' => '2026-03-22'
    ],
    [
      'customer_name' => 'Maria S.',
      'rating' => 4.2,
      'comment' => 'Good service, completed the job quickly. Minor communication delays but overall satisfied.',
      'date' => '2026-03-17'
    ],
    [
      'customer_name' => 'John R.',
      'rating' => 5.0,
      'comment' => 'Excellent quality work. Very detail-oriented and friendly. Highly recommended!',
      'date' => '2026-03-10'
    ],
    [
      'customer_name' => 'Rosa M.',
      'rating' => 5.0,
      'comment' => 'Perfect! Fixed everything as promised. Very reliable and trustworthy.',
      'date' => '2026-03-03'
    ],
  ];
}

function providerDashboardEarnings(): array
{
  return [
    [
      'service' => 'Bathroom Cleaning',
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

function providerJobHistory(mysqli $conn, int $providerId, string $providerSpecialty = ''): array
{
  $reviews = providerDashboardReviews();
  $reviewMap = [];
  foreach ($reviews as $review) {
    $key = strtolower(trim((string) ($review['customer_name'] ?? '')));
    if ($key !== '' && !isset($reviewMap[$key])) {
      $reviewMap[$key] = $review;
    }
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
  } elseif ($has('technician_id')) {
    $where[] = "b.technician_id = ?";
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
    $clientName = (string) ($row['client_name'] ?? 'Client');
    $clientKey = strtolower(trim($clientName));
    $review = $reviewMap[$clientKey] ?? null;

    $dateRaw = (string) ($row['date'] ?? '');
    $timeRaw = trim((string) ($row['time_slot'] ?? ''));
    $ts = strtotime($dateRaw);
    $dateText = $ts ? date('M j, Y', $ts) : 'No date';

    if ($timeRaw === '' && $ts) {
      $timePart = date('H:i:s', $ts);
      if ($timePart !== '00:00:00') {
        $timeRaw = date('g:i A', $ts);
      }
    }

    $items[] = [
      'id' => (int) ($row['id'] ?? 0),
      'service' => (string) ($row['service'] ?? 'Service'),
      'client_name' => $clientName,
      'date_text' => $dateText,
      'time_text' => $timeRaw !== '' ? $timeRaw : null,
      'status_text' => 'Completed',
      'review_rating' => $review ? (float) ($review['rating'] ?? 0) : null,
      'review_comment' => $review ? (string) ($review['comment'] ?? '') : null,
    ];
  }

  return $items;
}

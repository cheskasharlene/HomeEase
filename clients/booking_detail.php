<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once __DIR__ . '/../api/db.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$bookingId = (int) ($_GET['booking_id'] ?? 0);

$booking = null;
if ($bookingId > 0 && $userId > 0) {
  $cols = [];
  $cr = $conn->query("SHOW COLUMNS FROM bookings");
  if ($cr) {
    while ($c = $cr->fetch_assoc()) $cols[] = $c['Field'];
  }
  $hasProviderId = in_array('provider_id', $cols, true);
  $hasServiceId = in_array('service_id', $cols, true);
  $hasTimeSlot = in_array('time_slot', $cols, true);
  $hasNotes = in_array('notes', $cols, true);
  $hasPrice = in_array('price', $cols, true);
  $hasSupplyOption = in_array('supply_option', $cols, true);
  $hasSupplyFee = in_array('supply_fee', $cols, true);
  $hasStart = in_array('start_time', $cols, true);
  $hasEnd = in_array('end_time', $cols, true);
  $hasComp = in_array('completed_at', $cols, true);
  $hasHours = in_array('hours', $cols, true);

  ensureBookingRequestsTable($conn);
  ensureProviderReviewsTable($conn);
  ensurePaymentsTable($conn);

  $serviceSelect = $hasServiceId ? 'COALESCE(sv.name, b.service)' : 'b.service';
  $select = "b.id, {$serviceSelect} AS service, b.date, b.address, b.status, b.created_at";
  if ($hasTimeSlot) $select .= ', b.time_slot';
  if ($hasStart) $select .= ', b.start_time';
  if ($hasEnd) $select .= ', b.end_time';
  if ($hasComp) $select .= ', b.completed_at';
  if ($hasHours) $select .= ', b.hours';
  if ($hasNotes) $select .= ', b.notes';
  if ($hasPrice) $select .= ', b.price';
  if ($hasSupplyOption) $select .= ', b.supply_option';
  if ($hasSupplyFee) $select .= ', b.supply_fee';
  $select .= ', br.details, br.fixed_price, br.provider_id AS request_provider_id';

  $providerJoinExpr = $hasProviderId ? 'COALESCE(b.provider_id, br.provider_id)' : 'br.provider_id';
  $select .= ', sp.provider_id AS provider_id, sp.full_name AS provider_name, sp.contact_number AS provider_phone,';
  $select .= ' sp.rating AS provider_rating, sp.jobs_done AS provider_jobs, s_sp.name AS provider_service';
  $select .= ', p.payment_method, p.payment_status, p.amount AS payment_amount, p.payment_reference, p.transaction_id, p.payment_proof_path';
  $select .= ', pr.rating AS review_rating, pr.comment AS review_comment, pr.created_at AS review_created_at';

  $serviceJoin = $hasServiceId ? 'LEFT JOIN services sv ON sv.id = b.service_id' : '';
  $sql = "SELECT $select
          FROM bookings b
          $serviceJoin
          LEFT JOIN booking_requests br ON br.booking_id = b.id AND br.status = 'accepted'
          LEFT JOIN service_providers sp ON sp.provider_id = $providerJoinExpr
          LEFT JOIN services s_sp ON s_sp.id = sp.service_id
          LEFT JOIN payments p ON p.booking_id = b.id
          LEFT JOIN provider_reviews pr ON (pr.booking_id = b.id AND pr.user_id = ?)
          WHERE b.user_id = ? AND b.id = ?
          LIMIT 1";

  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param('iii', $userId, $userId, $bookingId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
  }

  $schPHP = null;
  if ($booking) {
    $schPHP = calculateBookingTimes(
      $booking['date'] ?? '',
      $booking['time_slot'] ?? '',
      $booking['hours'] ?? 1,
      $booking['start_time'] ?? '',
      $booking['end_time'] ?? '',
      $booking['completed_at'] ?? null,
      $booking['arrived_at'] ?? null
    );

    $providerIdResolved = (int) ($booking['provider_id'] ?? 0);
    if ($providerIdResolved <= 0) {
      $providerIdResolved = (int) ($booking['request_provider_id'] ?? 0);
    }
    $booking['provider_id_resolved'] = $providerIdResolved;

    $revCount = 0;
    $revAvg = null;
    if ($providerIdResolved > 0) {
      $revStmt = $conn->prepare('SELECT COUNT(*) AS review_count, AVG(rating) AS avg_rating FROM provider_reviews WHERE provider_id = ?');
      if ($revStmt) {
        $revStmt->bind_param('i', $providerIdResolved);
        $revStmt->execute();
        $revRow = $revStmt->get_result()->fetch_assoc();
        $revStmt->close();
        $revCount = (int) ($revRow['review_count'] ?? 0);
        if ($revRow && $revRow['avg_rating'] !== null) {
          $revAvg = (float) $revRow['avg_rating'];
        }
      }
    }
    $booking['provider_review_count'] = $revCount;
    if (!empty($booking['provider_rating']) && (float)$booking['provider_rating'] > 0) {
      $booking['provider_rating_display'] = (float)$booking['provider_rating'];
    } else {
      $booking['provider_rating_display'] = $revAvg ?? 0;
    }
  }
}

function getServiceIcon(string $service): string {
  $map = [
    'House Cleaner'        => '🧹',
    'Appliance Technician' => '🔧',
    'Plumber'              => '🚰',
    'Carpenter'            => '🪚',
    'Laundry Worker'       => '🧺',
    'Helper'               => '🤝',
  ];
  return $map[$service] ?? '🛠️';
}

function formatBookingDate(?string $date, ?string $timeSlot): string {
  if (!$date || $date === '0000-00-00') {
    return $timeSlot ? htmlspecialchars($timeSlot) : 'Schedule not set';
  }
  $ts = strtotime($date);
  $formatted = date('M j, Y', $ts);
  if ($timeSlot) {
    $formatted .= ' · ' . htmlspecialchars($timeSlot);
  }
  return $formatted;
}

function renderStarsHtml($rating): string {
  $full = (int) round((float) $rating);
  $html = '';
  for ($i = 1; $i <= 5; $i++) {
    $html .= ($i <= $full) ? '★' : '☆';
  }
  return $html;
}

$serviceName = htmlspecialchars($booking['service'] ?? 'Service');
$serviceIcon = getServiceIcon($booking['service'] ?? '');
$rawStatus = strtolower(trim((string)($booking['status'] ?? 'pending')));
$isDone = in_array($rawStatus, ['done', 'completed'], true);
$isArrived = ($rawStatus === 'arrived');
$isProgress = in_array($rawStatus, ['confirmed', 'progress', 'active', 'awaiting_payment'], true);
$isCancelled = in_array($rawStatus, ['cancelled', 'canceled', 'declined'], true);

$displayPrice = (float)($booking['fixed_price'] ?? $booking['price'] ?? 0);
if ($displayPrice <= 0 && !empty($booking['payment_amount'])) {
  $displayPrice = (float)$booking['payment_amount'];
}

$rawNotes = trim((string)($booking['details'] ?? $booking['notes'] ?? ''));
$parsedNotes = [];
if ($rawNotes !== '') {
  $lines = preg_split('/\r\n|\r|\n/', $rawNotes);
  foreach ($lines as $line) {
    $clean = trim($line, " \t\n\r\0\x0B-•*");
    if ($clean === '' || stripos($clean, 'selected options') !== false) continue;
    $parsedNotes[] = $clean;
  }
}

$hasProvider = !empty($booking['provider_id_resolved']) || !empty($booking['provider_name']);
$providerName = (string)($booking['provider_name'] ?? '');
$providerPhone = (string)($booking['provider_phone'] ?? '');
$providerService = (string)($booking['provider_service'] ?? $booking['service'] ?? '');
$providerRating = (float)($booking['provider_rating_display'] ?? 0);
$providerReviewCount = (int)($booking['provider_review_count'] ?? 0);

$hasReviewed = !empty($booking['review_rating']) && (int)$booking['review_rating'] > 0;
$userRating = (int)($booking['review_rating'] ?? 0);
$userComment = (string)($booking['review_comment'] ?? '');
$userReviewDate = !empty($booking['review_created_at']) ? date('M j, Y', strtotime($booking['review_created_at'])) : '';

$payMethod = strtolower((string)($booking['payment_method'] ?? 'cash'));
$payMethodLabel = ($payMethod === 'gcash') ? 'GCash' : (($payMethod === 'bank') ? 'Bank Transfer' : 'Cash');
$payMethodIcon = ($payMethod === 'gcash' || $payMethod === 'bank') ? 'bi-qr-code' : 'bi-cash';
$payStatus = strtolower((string)($booking['payment_status'] ?? ''));
$isPaid = ($payStatus === 'completed' || $isDone);
$transactionId = (string)($booking['transaction_id'] ?? '');
$paymentProofPath = (string)($booking['payment_proof_path'] ?? '');
$paymentProofUrl = $paymentProofPath ? ('../' . ltrim($paymentProofPath, '/')) : '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Booking Details</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/accepted_booking.css">
  <link rel="stylesheet" href="../assets/css/booking_detail.css">
  <style>
    .screen.pbd-screen {
      justify-content: flex-start;
      background: var(--bg-screen, #FFF7EE);
    }
    .pbd-scroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 130px;
    }
    .pbd-scroll::-webkit-scrollbar { display: none; }

    /* Hero Header */
    .pbd-hero {
      width: 100%;
      padding: 48px 22px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background:
        radial-gradient(circle at 15% 0%, rgba(255, 255, 255, 0.5) 0%, transparent 45%),
        radial-gradient(circle at 80% 80%, rgba(232, 130, 12, 0.16) 0%, transparent 55%),
        linear-gradient(160deg, rgba(216, 100, 8, 0.85) 0%, rgba(232, 130, 12, 0.7) 35%, rgba(245, 166, 35, 0.45) 65%, rgba(255, 183, 107, 0.2) 85%, transparent 100%);
      position: relative;
    }
    .pbd-title {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #1A1A2E;
      line-height: 1.2;
    }
    .pbd-ref-pill {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      margin-top: 4px;
      font-size: 11px;
      font-weight: 700;
      color: #7A5100;
      background: rgba(255, 255, 255, 0.45);
      padding: 2px 8px;
      border-radius: 999px;
    }
    .pbd-back-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 1.5px solid rgba(255, 255, 255, 0.6);
      background: rgba(255, 255, 255, 0.35);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #1A1A2E;
      font-size: 18px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
      transition: transform .15s ease, background .15s ease;
    }
    .pbd-back-btn:active {
      transform: scale(0.93);
      background: rgba(255, 255, 255, 0.6);
    }

    /* Cards */
    .pbd-card {
      background: #ffffff;
      border-radius: 20px;
      padding: 18px;
      margin: 14px 16px 0;
      border: 1px solid #f1ded0;
      box-shadow: 0 8px 24px rgba(232, 130, 12, 0.08);
      position: relative;
    }
    .pbd-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
    }
    .pbd-card-title {
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.6px;
      color: #8C5311;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    /* Top Status & Price Card */
    .pbd-status-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #ffffff;
      border-radius: 20px;
      padding: 16px 18px;
      margin: 16px 16px 0;
      border: 1px solid #f1ded0;
      box-shadow: 0 8px 24px rgba(232, 130, 12, 0.08);
    }
    .pbd-status-left {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .pbd-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 800;
      font-family: 'Nunito', sans-serif;
      letter-spacing: 0.2px;
      width: fit-content;
    }
    .pbd-status-pill.done {
      background: linear-gradient(135deg, #ecfdf5, #d1fae5);
      color: #065f46;
      border: 1.5px solid #a7f3d0;
    }
    .pbd-status-pill.arrived {
      background: linear-gradient(135deg, #e0f2fe, #bae6fd);
      color: #0369a1;
      border: 1.5px solid #7dd3fc;
    }
    .pbd-status-pill.progress {
      background: linear-gradient(135deg, #eff6ff, #dbeafe);
      color: #1d4ed8;
      border: 1.5px solid #bfdbfe;
    }
    .pbd-status-pill.cancelled {
      background: linear-gradient(135deg, #fef2f2, #fee2e2);
      color: #b91c1c;
      border: 1.5px solid #fecaca;
    }
    .pbd-status-pill.pending {
      background: linear-gradient(135deg, #fef9e7, #fef3c7);
      color: #b45309;
      border: 1.5px solid #fde68a;
    }
    .pbd-status-time {
      font-size: 12px;
      font-weight: 600;
      color: #64748b;
      margin-top: 2px;
    }
    .pbd-status-price {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #E8820C;
      text-align: right;
    }
    .pbd-status-price-sub {
      font-size: 11px;
      font-weight: 700;
      color: #94a3b8;
      text-align: right;
      text-transform: uppercase;
    }

    /* Key-Value Rows */
    .pbd-row {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      padding: 8px 0;
      border-bottom: 1px dashed #f1e4d8;
      font-size: 13px;
    }
    .pbd-row:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }
    .pbd-row:first-child {
      padding-top: 0;
    }
    .pbd-lbl {
      color: #64748b;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
      flex-shrink: 0;
    }
    .pbd-val {
      color: #1e293b;
      font-weight: 700;
      text-align: right;
      max-width: 60%;
      word-break: break-word;
    }

    /* Option Badge Pills */
    .pbd-option-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      border-radius: 12px;
      background: #FFF8EE;
      border: 1px solid #FDE68A;
      color: #92400E;
      font-size: 12px;
      font-weight: 700;
      margin: 3px;
    }

    /* Provider Header & Actions */
    .pbd-client-box {
      display: flex;
      align-items: center;
      gap: 14px;
      padding-bottom: 14px;
      border-bottom: 1px solid #f1e4d8;
      margin-bottom: 12px;
    }
    .pbd-client-av {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: linear-gradient(135deg, #F5A623, #E8820C);
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-weight: 800;
      font-size: 20px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 14px rgba(232, 130, 12, 0.28);
      flex-shrink: 0;
    }
    .pbd-client-info {
      flex: 1;
      min-width: 0;
    }
    .pbd-client-name {
      font-family: 'Poppins', sans-serif;
      font-size: 16px;
      font-weight: 800;
      color: #1e293b;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .pbd-client-sub {
      font-size: 12px;
      font-weight: 600;
      color: #64748b;
      margin-top: 2px;
    }
    .pbd-action-btns {
      display: flex;
      gap: 8px;
      margin-top: 14px;
    }
    .pbd-act-btn {
      flex: 1;
      height: 40px;
      border-radius: 12px;
      border: none;
      font-family: 'Poppins', sans-serif;
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      cursor: pointer;
      text-decoration: none;
      transition: transform .12s ease;
    }
    .pbd-act-btn:active { transform: scale(0.96); }
    .pbd-act-btn.call {
      background: linear-gradient(135deg, #059669, #10B981);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);
    }
    .pbd-act-btn.sms {
      background: #F1F5F9;
      color: #334155;
      border: 1px solid #CBD5E1;
    }

    /* Receipt Preview */
    .pbd-receipt-preview {
      margin-top: 12px;
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 14px;
      padding: 10px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .pbd-receipt-thumb {
      width: 48px;
      height: 48px;
      border-radius: 8px;
      object-fit: cover;
      cursor: pointer;
      border: 1px solid #CBD5E1;
    }
    .pbd-receipt-btn {
      padding: 6px 12px;
      border-radius: 8px;
      background: #E8820C;
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      border: none;
      cursor: pointer;
    }

    /* Review Card & Stars */
    .pbd-review-stars {
      color: #F5A623;
      font-size: 16px;
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .pbd-review-box {
      background: #FFFDF9;
      border: 1px solid #FDE68A;
      border-radius: 14px;
      padding: 14px;
      margin-top: 10px;
    }
    .pbd-review-text {
      font-size: 13px;
      font-style: italic;
      color: #334155;
      line-height: 1.5;
      margin-top: 6px;
    }

    /* Rate & Review Button */
    .rr-btn {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 16px;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 6px 20px rgba(232, 130, 12, .35);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .rr-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(232, 130, 12, .45);
    }
    .rr-btn:active { transform: scale(.97); }

    /* Review Bottom Sheet Modal */
    .rr-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,.48);
      z-index: 200; display: none; align-items: flex-end; justify-content: center;
    }
    .rr-overlay.open { display: flex; }
    .rr-sheet {
      width: min(420px,100vw); background: var(--card,#fff);
      border-radius: 28px 28px 0 0; padding: 0 0 40px;
      box-shadow: 0 -8px 40px rgba(0,0,0,.18);
      animation: sheetUp .32s cubic-bezier(.22,1,.36,1);
    }
    @keyframes sheetUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
    .rr-handle {
      width: 44px; height: 4px; border-radius: 99px;
      background: #ddd; margin: 12px auto 0;
    }
    .rr-sheet-title {
      font-family: 'Poppins',sans-serif; font-size: 17px; font-weight: 800;
      color: var(--td,#1a1a2e); text-align: center; padding: 16px 24px 4px;
    }
    .rr-sheet-sub {
      font-family: 'Nunito',sans-serif; font-size: 13px; color: var(--tm,#6b7280);
      text-align: center; padding: 0 24px 18px;
    }
    .rr-stars {
      display: flex; justify-content: center; gap: 10px; padding: 0 24px 6px;
    }
    .rr-star {
      font-size: 38px; cursor: pointer; color: #d1d5db;
      transition: color .15s ease, transform .15s ease;
      -webkit-tap-highlight-color: transparent;
    }
    .rr-star.active { color: #F5A623; }
    .rr-star:hover { transform: scale(1.18); }
    .rr-star-label {
      font-family: 'Nunito',sans-serif; font-size: 12px; font-weight: 700;
      color: #F5A623; text-align: center; height: 18px; margin-bottom: 4px;
    }
    .rr-textarea-wrap { padding: 10px 24px 0; }
    .rr-textarea {
      width: 100%; box-sizing: border-box;
      border: 1.5px solid var(--border,#e5e7eb); border-radius: 14px;
      padding: 12px 14px; font-family: 'Nunito',sans-serif; font-size: 14px;
      color: var(--td,#1a1a2e); background: var(--card,#fff);
      resize: none; outline: none; min-height: 90px;
      transition: border-color .2s ease;
    }
    .rr-textarea:focus { border-color: #F5A623; }
    .rr-actions { padding: 16px 24px 0; display: flex; gap: 10px; }
    .rr-cancel {
      flex: 1; padding: 13px; border: 1.5px solid var(--border,#e5e7eb);
      border-radius: 14px; background: transparent;
      font-family: 'Poppins',sans-serif; font-size: 13px; font-weight: 700;
      color: var(--tm,#6b7280); cursor: pointer;
    }
    .rr-submit {
      flex: 2; padding: 13px; border: none; border-radius: 14px;
      background: linear-gradient(135deg,#E8820C,#F5A623);
      font-family: 'Poppins',sans-serif; font-size: 13px; font-weight: 800;
      color: #fff; cursor: pointer;
      box-shadow: 0 4px 14px rgba(232,130,12,.35);
      transition: opacity .2s ease;
    }
    .rr-submit:disabled { opacity: .55; cursor: not-allowed; }
    .rr-err {
      font-family: 'Nunito',sans-serif; font-size: 12px; font-weight: 700;
      color: #ef4444; text-align: center; padding: 6px 24px 0; min-height: 20px;
    }

    /* Receipt Image Modal */
    .image-preview-overlay {
      position: fixed; inset: 0; background: rgba(0, 0, 0, 0.85);
      z-index: 1000; display: none; align-items: center; justify-content: center;
      backdrop-filter: blur(4px);
    }
    .image-preview-modal {
      width: min(440px, 92vw); background: #ffffff; border-radius: 20px;
      overflow: hidden; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      display: flex; flex-direction: column; max-height: 85vh;
    }
    .image-preview-header {
      padding: 12px 16px; display: flex; align-items: center;
      justify-content: space-between; border-bottom: 1px solid #E2E8F0;
    }
    .image-preview-title {
      font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 14px; color: #1E293B;
    }
    .image-preview-controls { display: flex; gap: 6px; }
    .preview-btn, .preview-close {
      width: 32px; height: 32px; border-radius: 8px; border: 1px solid #E2E8F0;
      background: #F8FAFC; color: #475569; display: flex; align-items: center;
      justify-content: center; cursor: pointer;
    }
    .preview-close { color: #EF4444; background: #FEF2F2; border-color: #FEE2E2; }
    .image-preview-container {
      padding: 14px; display: flex; align-items: center; justify-content: center;
      overflow: auto; background: #0F172A; min-height: 280px;
    }
    .preview-image {
      max-width: 100%; max-height: 60vh; object-fit: contain; transition: transform 0.2s ease;
    }

    /* Homeowner Bottom Navigation */
    .bnav {
      position: fixed;
      left: 50%;
      bottom: 0;
      transform: translateX(-50%);
      width: min(420px, 100vw);
      z-index: 70;
    }

    /* Dark Mode Support */
    body.dark .pbd-screen { background: #0f172a; }
    body.dark .pbd-title { color: #f8fafc; }
    body.dark .pbd-card,
    body.dark .pbd-status-card {
      background: #1e293b;
      border-color: #334155;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    }
    body.dark .pbd-card-title { color: #fbbf24; }
    body.dark .pbd-row { border-bottom-color: #334155; }
    body.dark .pbd-lbl { color: #94a3b8; }
    body.dark .pbd-val { color: #f1f5f9; }
    body.dark .pbd-option-badge {
      background: #2a2216;
      border-color: #7c5b1e;
      color: #fbbf24;
    }
    body.dark .pbd-client-box { border-bottom-color: #334155; }
    body.dark .pbd-client-name { color: #f1f5f9; }
    body.dark .pbd-client-sub { color: #94a3b8; }
    body.dark .pbd-act-btn.sms {
      background: #334155;
      color: #f1f5f9;
      border-color: #475569;
    }
    body.dark .pbd-review-box {
      background: #1e293b;
      border-color: #7c5b1e;
    }
    body.dark .pbd-review-text { color: #e2e8f0; }
    body.dark .pbd-receipt-preview {
      background: #0f172a;
      border-color: #334155;
    }
  </style>
</head>

<body>
  <div class="shell" id="app">
    <div class="screen pbd-screen">
      <div class="pbd-scroll">

        <!-- Hero Header -->
        <div class="pbd-hero">
          <div>
            <div class="pbd-title">Booking Details</div>
            <div class="pbd-ref-pill" id="bookingRefPill">
              <i class="bi bi-hash"></i>Booking #<span id="bookingIdText"><?= htmlspecialchars((string)($bookingId ?: '')) ?></span>
            </div>
          </div>
          <button class="pbd-back-btn" onclick="handleBack()" aria-label="Back">
            <i class="bi bi-arrow-left"></i>
          </button>
        </div>

        <?php if (!$booking): ?>
          <!-- Booking Not Found (Empty State) -->
          <div class="pbd-card" id="emptyStateCard" style="text-align:center;padding:40px 20px;margin-top:30px;">
            <div style="font-size:44px;margin-bottom:12px;">🔍</div>
            <h3 style="font-family:'Poppins',sans-serif;font-size:17px;color:#1E293B;margin-bottom:6px;">Booking Not Found</h3>
            <p style="font-size:13px;color:#64748B;line-height:1.5;margin-bottom:20px;">We couldn't retrieve the details for this booking, or it is not assigned to your account.</p>
            <button class="pbd-act-btn call" onclick="goPage('booking_history.php')" style="max-width:220px;margin:0 auto;">
              <i class="bi bi-calendar-check"></i> Back to Bookings
            </button>
          </div>
        <?php else: ?>

          <!-- Status & Price Card -->
          <div class="pbd-status-card" id="statusCard">
            <div class="pbd-status-left">
              <?php if ($isDone): ?>
                <span class="pbd-status-pill done" id="bookingStatusPill"><i class="bi bi-check-circle-fill"></i> Completed</span>
              <?php elseif ($isArrived): ?>
                <span class="pbd-status-pill arrived" id="bookingStatusPill"><i class="bi bi-geo-alt-fill"></i> Arrived</span>
              <?php elseif ($isProgress): ?>
                <span class="pbd-status-pill progress" id="bookingStatusPill"><i class="bi bi-hourglass-split"></i> In Progress</span>
              <?php elseif ($isCancelled): ?>
                <span class="pbd-status-pill cancelled" id="bookingStatusPill"><i class="bi bi-x-circle-fill"></i> Cancelled</span>
              <?php else: ?>
                <span class="pbd-status-pill pending" id="bookingStatusPill"><i class="bi bi-clock-history"></i> Pending</span>
              <?php endif; ?>
            </div>
            <div>
              <div class="pbd-status-price" id="statusPriceText">₱<?= number_format($displayPrice, 2) ?></div>
              <div class="pbd-status-price-sub">Service Price</div>
            </div>
          </div>

          <!-- Service Summary Card -->
          <div class="pbd-card" id="serviceCard">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-briefcase-fill" style="color:#E8820C;"></i> Service Summary
              </div>
              <span style="font-size:22px;line-height:1;" id="serviceIcon"><?= $serviceIcon ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-tools"></i> Service</span>
              <span class="pbd-val" style="font-size:14px;color:#0F172A;" id="bookingService"><?= $serviceName ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-play-circle-fill" style="color:#E8820C;"></i> Scheduled Start</span>
              <span class="pbd-val" id="bookingScheduledStart"><?= htmlspecialchars($schPHP['scheduled_start'] ?? formatBookingDate($booking['date'] ?? null, $booking['time_slot'] ?? null)) ?></span>
            </div>

            <div class="pbd-row" id="arrivedAtRow" style="<?= (!empty($schPHP['formatted_arrived'])) ? '' : 'display:none;' ?>">
              <span class="pbd-lbl" style="color:#0284C7;"><i class="bi bi-geo-alt-fill" style="color:#0284C7;"></i> Arrival Time</span>
              <span class="pbd-val" style="color:#0284C7;font-weight:800;" id="bookingArrivedAt"><?= htmlspecialchars($schPHP['formatted_arrived'] ?? '—') ?></span>
            </div>

            <div class="pbd-row" id="completedAtRow" style="<?= ($isDone && !empty($schPHP['formatted_completed'])) ? '' : 'display:none;' ?>">
              <span class="pbd-lbl" style="color:#059669;"><i class="bi bi-check-circle-fill" style="color:#059669;"></i> Completed At</span>
              <span class="pbd-val" style="color:#059669;font-weight:800;" id="bookingCompletedAt"><?= htmlspecialchars($schPHP['formatted_completed'] ?? '—') ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-geo-alt-fill"></i> Location</span>
              <span class="pbd-val" id="bookingAddress"><?= htmlspecialchars($booking['address'] ?? '—') ?></span>
            </div>

            <div id="serviceDetailsBox">
              <?php if (!empty($parsedNotes)): ?>
                <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #f1e4d8;">
                  <div style="font-size:11px;font-weight:800;color:#8C5311;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
                    <i class="bi bi-list-check"></i> Selected Options &amp; Details
                  </div>
                  <div style="display:flex;flex-wrap:wrap;gap:4px;" id="parsedNotesList">
                    <?php foreach ($parsedNotes as $opt): ?>
                      <span class="pbd-option-badge">
                        <i class="bi bi-check2"></i> <?= htmlspecialchars($opt) ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php elseif ($rawNotes !== ''): ?>
                <div style="margin-top:12px;padding-top:10px;border-top:1px dashed #f1e4d8;">
                  <div style="font-size:11px;font-weight:800;color:#8C5311;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;">
                    <i class="bi bi-chat-left-text"></i> Service Notes
                  </div>
                  <div style="font-size:13px;color:#334155;line-height:1.45;" id="rawNotesText"><?= nl2br(htmlspecialchars($rawNotes)) ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Provider Details Card -->
          <div class="pbd-card" id="providerCard">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-person-badge-fill" style="color:#E8820C;"></i> Provider Details
              </div>
            </div>

            <div class="pbd-client-box" id="providerBox" style="<?= $hasProvider ? '' : 'display:none;' ?>">
              <div class="pbd-client-av" id="providerAvatar">
                <?= strtoupper(substr($providerName ?: 'P', 0, 1)) ?>
              </div>
              <div class="pbd-client-info">
                <div class="pbd-client-name" id="providerName"><?= htmlspecialchars($providerName ?: 'Service Provider') ?></div>
                <div class="pbd-client-sub" id="providerPhoneSub">
                  <i class="bi bi-telephone"></i> <span id="providerPhone"><?= htmlspecialchars($providerPhone ?: 'No phone provided') ?></span>
                </div>
              </div>
            </div>

            <div class="pbd-row" id="providerServiceRow" style="<?= $hasProvider ? '' : 'display:none;' ?>">
              <span class="pbd-lbl"><i class="bi bi-tools"></i> Service</span>
              <span class="pbd-val" id="providerService"><?= htmlspecialchars($providerService ?: '—') ?></span>
            </div>

            <div class="pbd-row" id="providerRatingRow" style="<?= $hasProvider ? '' : 'display:none;' ?>">
              <span class="pbd-lbl"><i class="bi bi-star-fill" style="color:#F5A623;"></i> Rating</span>
              <span class="pbd-val" id="providerRatingVal">
                <span class="pbd-review-stars" style="display:inline-flex;margin-right:4px;" id="providerStars">
                  <?= renderStarsHtml($providerRating) ?>
                </span>
                <span id="providerRatingText">
                  <?= $providerRating > 0 ? (number_format($providerRating, 1) . ' (' . $providerReviewCount . ' reviews)') : 'No ratings yet' ?>
                </span>
              </span>
            </div>

            <div class="pbd-action-btns" id="providerActionBtns" style="<?= ($hasProvider && !empty($providerPhone) && !$isDone && !$isCancelled) ? '' : 'display:none;' ?>">
              <a href="tel:<?= urlencode($providerPhone) ?>" class="pbd-act-btn call" id="callProviderBtn">
                <i class="bi bi-telephone-fill"></i> Call Provider
              </a>
              <a href="sms:<?= urlencode($providerPhone) ?>" class="pbd-act-btn sms" id="smsProviderBtn">
                <i class="bi bi-chat-dots-fill"></i> Send SMS
              </a>
            </div>

            <div class="bd-provider-note" id="providerNote" style="<?= $hasProvider ? 'display:none;' : '' ?>">
              <i class="bi bi-info-circle" style="color:#E8820C;margin-right:4px;"></i> Provider details will appear once assigned.
            </div>
          </div>

          <!-- Payment Summary Card -->
          <div class="pbd-card" id="paymentCard">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-credit-card-2-front-fill" style="color:#E8820C;"></i> Payment Summary
              </div>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi <?= $payMethodIcon ?>" id="payMethodIcon"></i> Payment Method</span>
              <span class="pbd-val" id="payMethodVal"><?= $payMethodLabel ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-shield-check"></i> Payment Status</span>
              <span class="pbd-val" id="payStatusVal">
                <?php if ($isPaid): ?>
                  <span style="color:#059669;font-weight:800;"><i class="bi bi-check-circle-fill"></i> Paid</span>
                <?php else: ?>
                  <span style="color:#D97706;font-weight:800;"><i class="bi bi-hourglass-split"></i> <?= htmlspecialchars(ucfirst($payStatus ?: 'Pending')) ?></span>
                <?php endif; ?>
              </span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-receipt"></i> Amount</span>
              <span class="pbd-val" style="color:#E8820C;font-size:15px;font-weight:800;" id="payAmountVal">
                ₱<?= number_format($displayPrice, 2) ?>
              </span>
            </div>

            <div class="pbd-row" id="payRefRow" style="<?= !empty($transactionId) ? '' : 'display:none;' ?>">
              <span class="pbd-lbl"><i class="bi bi-hash"></i> Reference No.</span>
              <span class="pbd-val" style="font-family:monospace;font-size:11px;" id="payRefVal"><?= htmlspecialchars($transactionId) ?></span>
            </div>

            <div class="pbd-receipt-preview" id="payReceiptPreview" style="<?= !empty($paymentProofUrl) ? '' : 'display:none;' ?>">
              <img src="<?= htmlspecialchars($paymentProofUrl) ?>" class="pbd-receipt-thumb" id="payReceiptThumb" onclick="openReceiptModal(this.src)" alt="Receipt proof">
              <div style="flex:1;">
                <div style="font-size:12px;font-weight:700;color:#1E293B;">Payment Receipt</div>
                <div style="font-size:11px;color:#64748B;">Proof of payment uploaded</div>
              </div>
              <button class="pbd-receipt-btn" id="payReceiptBtn" onclick="openReceiptModal('<?= htmlspecialchars($paymentProofUrl) ?>')">
                <i class="bi bi-eye"></i> View
              </button>
            </div>
          </div>

          <!-- Customer Review Card (For Completed Bookings) -->
          <div class="pbd-card" id="reviewSectionCard" style="<?= ($isDone && $hasProvider) ? '' : 'display:none;' ?>">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-star-fill" style="color:#E8820C;"></i> Customer Review
              </div>
              <div class="pbd-review-stars" id="reviewCardStars" style="<?= $hasReviewed ? '' : 'display:none;' ?>">
                <span id="reviewCardStarsText"><?= renderStarsHtml($userRating) ?></span>
                <span style="font-size:12px;font-weight:800;color:#1E293B;margin-left:4px;" id="reviewCardScore"><?= number_format($userRating, 1) ?></span>
              </div>
            </div>

            <!-- Existing Review Display -->
            <div id="reviewedState" style="<?= $hasReviewed ? '' : 'display:none;' ?>">
              <div class="pbd-review-box">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-size:12px;font-weight:800;color:#92400E;">Your Rating</span>
                  <span style="font-size:11px;color:#94A3B8;" id="reviewDateText"><?= htmlspecialchars($userReviewDate) ?></span>
                </div>
                <div class="pbd-review-text" id="reviewCommentText">
                  <?= !empty($userComment) ? ('"' . htmlspecialchars($userComment) . '"') : 'No written feedback provided.' ?>
                </div>
              </div>
              <div style="margin-top:12px;display:flex;gap:8px;">
                <button class="pbd-act-btn sms" onclick="openReviewSheet(_cachedReviewRating, _cachedReviewComment)" style="height:36px;font-size:12px;">
                  <i class="bi bi-pencil-square"></i> Edit Review
                </button>
              </div>
            </div>

            <!-- Not Reviewed Yet -->
            <div id="unreviewedState" style="<?= !$hasReviewed ? '' : 'display:none;' ?>">
              <p style="font-size:13px;color:#64748B;line-height:1.5;margin-bottom:14px;">How was your service experience? Share your feedback to help your service provider and community.</p>
              <button class="rr-btn" id="openReviewBtn" onclick="openReviewSheet()">
                <i class="bi bi-star-fill"></i> Rate &amp; Review
              </button>
            </div>
          </div>

        <?php endif; ?>

        <div style="height:30px;"></div>
      </div>

      <!-- Homeowner Bottom Navigation -->
      <div class="bnav">
        <div class="ni" onclick="goPage('../home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni on" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni ni-bell" onclick="goPage('notifications.php')">
          <div class="ni-bell-wrap">
            <i class="bi bi-bell-fill"></i>
            <span class="ni-badge" id="navBellBadge" style="display:none;"></span>
          </div>
          <span class="nl">Notifications</span>
        </div>
        <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>
    </div>
  </div>

  <!-- Rate & Review Bottom Sheet -->
  <div class="rr-overlay" id="rrOverlay" onclick="handleOverlayClick(event)">
    <div class="rr-sheet" id="rrSheet">
      <div class="rr-handle"></div>
      <div class="rr-sheet-title" id="rrSheetTitle">Rate Your Experience</div>
      <div class="rr-sheet-sub" id="rrSheetSub">How was the service?</div>
      <div class="rr-stars" id="rrStars" onmouseleave="previewStars(_selectedStar)">
        <span class="rr-star" data-v="1" onclick="selectStar(1)" onmouseenter="previewStars(1)">&#9733;</span>
        <span class="rr-star" data-v="2" onclick="selectStar(2)" onmouseenter="previewStars(2)">&#9733;</span>
        <span class="rr-star" data-v="3" onclick="selectStar(3)" onmouseenter="previewStars(3)">&#9733;</span>
        <span class="rr-star" data-v="4" onclick="selectStar(4)" onmouseenter="previewStars(4)">&#9733;</span>
        <span class="rr-star" data-v="5" onclick="selectStar(5)" onmouseenter="previewStars(5)">&#9733;</span>
      </div>
      <div class="rr-star-label" id="rrStarLabel">Tap a star to rate</div>
      <div class="rr-textarea-wrap">
        <textarea class="rr-textarea" id="rrComment" placeholder="Share your experience (optional)..." maxlength="500"></textarea>
      </div>
      <div class="rr-err" id="rrErr"></div>
      <div class="rr-actions">
        <button class="rr-cancel" onclick="closeReviewSheet()">Cancel</button>
        <button class="rr-submit" id="rrSubmitBtn" onclick="submitReview()">Submit Review</button>
      </div>
    </div>
  </div>

  <!-- Payment Receipt Preview Modal -->
  <div class="image-preview-overlay" id="receiptModal" onclick="if(event.target===this)closeReceiptModal()">
    <div class="image-preview-modal">
      <div class="image-preview-header">
        <div class="image-preview-title">Payment Receipt</div>
        <div class="image-preview-controls">
          <button class="preview-btn" onclick="zoomImage(0.15)" title="Zoom In"><i class="bi bi-zoom-in"></i></button>
          <button class="preview-btn" onclick="zoomImage(-0.15)" title="Zoom Out"><i class="bi bi-zoom-out"></i></button>
          <button class="preview-btn" onclick="resetImageZoom()" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></button>
          <button class="preview-close" onclick="closeReceiptModal()" title="Close"><i class="bi bi-x-lg"></i></button>
        </div>
      </div>
      <div class="image-preview-container" id="receiptContainer">
        <img id="receiptImage" class="preview-image" src="" alt="Payment Receipt">
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') {
      initTheme();
    }

    function goPage(page) {
      window.location.href = page;
    }

    function handleBack() {
      goPage('booking_history.php');
    }

    const SERVICE_ICONS = {
      'House Cleaner': '🧹',
      'Appliance Technician': '🔧',
      'Plumber': '🚰',
      'Carpenter': '🪚',
      'Laundry Worker': '🧺',
      'Helper': '🤝'
    };

    function getServiceIcon(name) {
      return SERVICE_ICONS[name] || '🛠️';
    }

    function formatPrice(value) {
      const num = Number(value || 0);
      return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatSchedule(date, timeSlot) {
      if (!date || date === '0000-00-00') return timeSlot || 'Schedule not set';
      const dt = new Date(date + 'T00:00:00');
      const dateLabel = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      return timeSlot ? (dateLabel + ' · ' + timeSlot) : dateLabel;
    }

    function renderStars(rating) {
      const full = Math.round(Number(rating || 0));
      let str = '';
      for (let i = 1; i <= 5; i++) {
        str += (i <= full) ? '★' : '☆';
      }
      return str;
    }

    let _bookingId = <?= json_encode((string)($bookingId ?: '')) ?>;
    let _providerId = <?= json_encode((int)($booking['provider_id_resolved'] ?? 0)) ?>;
    let _selectedStar = 0;
    let _cachedReviewRating = <?= json_encode($userRating) ?>;
    let _cachedReviewComment = <?= json_encode($userComment) ?>;
    const starLabels = ['', 'Terrible 😟', 'Not Great 😕', 'Okay 😐', 'Good 😊', 'Excellent 🤩'];

    async function loadBookingDetail() {
      const params = new URLSearchParams(window.location.search);
      const qId = params.get('booking_id');
      if (qId) _bookingId = qId;
      if (!_bookingId) return;

      const url = '../api/bookings_api.php?action=detail&booking_id=' + encodeURIComponent(_bookingId);
      try {
        const res = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !data.booking) return;

        const b = data.booking;
        _providerId = Number(b.provider_id || 0);

        // Header
        const idEl = document.getElementById('bookingIdText');
        if (idEl) idEl.textContent = b.id;

        // Status & Price
        const rawStatus = (b.status || 'pending').toLowerCase();
        const isDone = (rawStatus === 'done' || rawStatus === 'completed');
        const isArrived = (rawStatus === 'arrived');
        const isProgress = (rawStatus === 'confirmed' || rawStatus === 'progress' || rawStatus === 'active');
        const isCancelled = (rawStatus === 'cancelled' || rawStatus === 'canceled' || rawStatus === 'declined');

        const pill = document.getElementById('bookingStatusPill');
        if (pill) {
          if (isDone) {
            pill.className = 'pbd-status-pill done';
            pill.innerHTML = '<i class="bi bi-check-circle-fill"></i> Completed';
          } else if (isArrived) {
            pill.className = 'pbd-status-pill arrived';
            pill.innerHTML = '<i class="bi bi-geo-alt-fill"></i> Arrived';
          } else if (isProgress) {
            pill.className = 'pbd-status-pill progress';
            pill.innerHTML = '<i class="bi bi-hourglass-split"></i> In Progress';
          } else if (isCancelled) {
            pill.className = 'pbd-status-pill cancelled';
            pill.innerHTML = '<i class="bi bi-x-circle-fill"></i> Cancelled';
          } else {
            pill.className = 'pbd-status-pill pending';
            pill.innerHTML = '<i class="bi bi-clock-history"></i> Pending';
          }
        }

        const price = Number(b.price || b.payment_amount || 0);
        const priceEl = document.getElementById('statusPriceText');
        if (priceEl) priceEl.textContent = formatPrice(price);

        // Service Summary
        const sNameEl = document.getElementById('bookingService');
        if (sNameEl) sNameEl.textContent = b.service || 'Service';

        const sIconEl = document.getElementById('serviceIcon');
        if (sIconEl) sIconEl.textContent = getServiceIcon(b.service || '');

        const schStartEl = document.getElementById('bookingScheduledStart');
        if (schStartEl) schStartEl.textContent = b.scheduled_start || formatSchedule(b.date, b.time_slot);

        const arrRow = document.getElementById('arrivedAtRow');
        const arrVal = document.getElementById('bookingArrivedAt');
        if (arrRow && arrVal) {
          if (b.formatted_arrived || b.arrived_at) {
            arrVal.textContent = b.formatted_arrived || b.arrived_at;
            arrRow.style.display = 'flex';
          } else {
            arrRow.style.display = 'none';
          }
        }

        const compRow = document.getElementById('completedAtRow');
        const compVal = document.getElementById('bookingCompletedAt');
        if (compRow && compVal) {
          if (isDone && (b.formatted_completed || b.completed_at)) {
            compVal.textContent = b.formatted_completed || b.completed_at;
            compRow.style.display = 'flex';
          } else {
            compRow.style.display = 'none';
          }
        }

        const sAddrEl = document.getElementById('bookingAddress');
        if (sAddrEl) sAddrEl.textContent = b.address || '—';

        // Options & Details parsing
        const rawNotes = (b.details || b.notes || '').trim();
        const notesBox = document.getElementById('serviceDetailsBox');
        if (notesBox) {
          if (rawNotes) {
            const lines = rawNotes.split(/\r\n|\r|\n/);
            const parsed = [];
            for (let l of lines) {
              const clean = l.replace(/^[\s\-•*]+|[\s\-•*]+$/g, '').trim();
              if (clean && !clean.toLowerCase().includes('selected options')) {
                parsed.push(clean);
              }
            }
            if (parsed.length > 0) {
              let badgesHtml = '<div style="margin-top:14px;padding-top:12px;border-top:1px dashed #f1e4d8;">';
              badgesHtml += '<div style="font-size:11px;font-weight:800;color:#8C5311;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;"><i class="bi bi-list-check"></i> Selected Options &amp; Details</div>';
              badgesHtml += '<div style="display:flex;flex-wrap:wrap;gap:4px;">';
              parsed.forEach(opt => {
                badgesHtml += `<span class="pbd-option-badge"><i class="bi bi-check2"></i> ${escapeHtml(opt)}</span>`;
              });
              badgesHtml += '</div></div>';
              notesBox.innerHTML = badgesHtml;
            } else {
              notesBox.innerHTML = `
                <div style="margin-top:12px;padding-top:10px;border-top:1px dashed #f1e4d8;">
                  <div style="font-size:11px;font-weight:800;color:#8C5311;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:4px;"><i class="bi bi-chat-left-text"></i> Service Notes</div>
                  <div style="font-size:13px;color:#334155;line-height:1.45;">${escapeHtml(rawNotes).replace(/\n/g, '<br>')}</div>
                </div>`;
            }
          } else {
            notesBox.innerHTML = '';
          }
        }

        // Provider Details
        const hasProvider = _providerId > 0 || (b.provider_name && b.provider_name.trim());
        const pBox = document.getElementById('providerBox');
        const pRow1 = document.getElementById('providerServiceRow');
        const pRow2 = document.getElementById('providerRatingRow');
        const pNote = document.getElementById('providerNote');
        const pActBtns = document.getElementById('providerActionBtns');

        if (hasProvider) {
          if (pBox) pBox.style.display = 'flex';
          if (pRow1) pRow1.style.display = 'flex';
          if (pRow2) pRow2.style.display = 'flex';
          if (pNote) pNote.style.display = 'none';

          const av = document.getElementById('providerAvatar');
          if (av) av.textContent = (b.provider_name || 'P').trim().charAt(0).toUpperCase();

          const nameEl = document.getElementById('providerName');
          if (nameEl) nameEl.textContent = b.provider_name || 'Service Provider';

          const phoneEl = document.getElementById('providerPhone');
          if (phoneEl) phoneEl.textContent = b.provider_phone || 'No phone provided';

          const servEl = document.getElementById('providerService');
          if (servEl) servEl.textContent = b.provider_service || b.service || '—';

          const rStars = document.getElementById('providerStars');
          if (rStars) rStars.textContent = renderStars(b.provider_rating);

          const rText = document.getElementById('providerRatingText');
          if (rText) {
            const rVal = Number(b.provider_rating || 0);
            rText.textContent = rVal > 0 ? `${rVal.toFixed(1)} (${b.provider_review_count || 0} reviews)` : 'No ratings yet';
          }

          if (pActBtns) {
            if (b.provider_phone && !isDone && !isCancelled) {
              pActBtns.style.display = 'flex';
              pActBtns.innerHTML = `
                <a href="tel:${encodeURIComponent(b.provider_phone)}" class="pbd-act-btn call" id="callProviderBtn">
                  <i class="bi bi-telephone-fill"></i> Call Provider
                </a>
                <a href="sms:${encodeURIComponent(b.provider_phone)}" class="pbd-act-btn sms" id="smsProviderBtn">
                  <i class="bi bi-chat-dots-fill"></i> Send SMS
                </a>`;
            } else {
              pActBtns.style.display = 'none';
            }
          }
        } else {
          if (pBox) pBox.style.display = 'none';
          if (pRow1) pRow1.style.display = 'none';
          if (pRow2) pRow2.style.display = 'none';
          if (pActBtns) pActBtns.style.display = 'none';
          if (pNote) pNote.style.display = 'block';
        }

        // Payment Summary
        const pMethod = (b.payment_method || 'cash').toLowerCase();
        const pMethodLabel = pMethod === 'gcash' ? 'GCash' : (pMethod === 'bank' ? 'Bank Transfer' : 'Cash');
        const pMethodIcon = (pMethod === 'gcash' || pMethod === 'bank') ? 'bi-qr-code' : 'bi-cash';
        const pStatus = (b.payment_status || '').toLowerCase();
        const isPaid = (pStatus === 'completed' || isDone);

        const pmIconEl = document.getElementById('payMethodIcon');
        if (pmIconEl) pmIconEl.className = 'bi ' + pMethodIcon;

        const pmValEl = document.getElementById('payMethodVal');
        if (pmValEl) pmValEl.textContent = pMethodLabel;

        const pStatValEl = document.getElementById('payStatusVal');
        if (pStatValEl) {
          pStatValEl.innerHTML = isPaid
            ? '<span style="color:#059669;font-weight:800;"><i class="bi bi-check-circle-fill"></i> Paid</span>'
            : `<span style="color:#D97706;font-weight:800;"><i class="bi bi-hourglass-split"></i> ${escapeHtml(pStatus ? pStatus.charAt(0).toUpperCase() + pStatus.slice(1) : 'Pending')}</span>`;
        }

        const pAmtEl = document.getElementById('payAmountVal');
        if (pAmtEl) pAmtEl.textContent = formatPrice(price);

        const pRefRow = document.getElementById('payRefRow');
        const pRefVal = document.getElementById('payRefVal');
        if (b.transaction_id || b.payment_reference) {
          if (pRefRow) pRefRow.style.display = 'flex';
          if (pRefVal) pRefVal.textContent = b.transaction_id || b.payment_reference;
        } else if (pRefRow) {
          pRefRow.style.display = 'none';
        }

        const pReceiptPreview = document.getElementById('payReceiptPreview');
        if (b.payment_proof_path) {
          const proofUrl = '../' + b.payment_proof_path.replace(/^\/+/, '');
          if (pReceiptPreview) {
            pReceiptPreview.style.display = 'flex';
            pReceiptPreview.innerHTML = `
              <img src="${escapeHtml(proofUrl)}" class="pbd-receipt-thumb" onclick="openReceiptModal('${escapeHtml(proofUrl)}')" alt="Receipt proof">
              <div style="flex:1;">
                <div style="font-size:12px;font-weight:700;color:#1E293B;">Payment Receipt</div>
                <div style="font-size:11px;color:#64748B;">Proof of payment uploaded</div>
              </div>
              <button class="pbd-receipt-btn" onclick="openReceiptModal('${escapeHtml(proofUrl)}')">
                <i class="bi bi-eye"></i> View
              </button>`;
          }
        } else if (pReceiptPreview) {
          pReceiptPreview.style.display = 'none';
        }

        // Customer Review
        const revCard = document.getElementById('reviewSectionCard');
        if (revCard) {
          if (isDone && hasProvider) {
            revCard.style.display = 'block';
            if (b.has_reviewed) {
              _cachedReviewRating = Number(b.review_rating || 0);
              _cachedReviewComment = b.review_comment || '';
              showReviewedState(_cachedReviewRating, _cachedReviewComment, b.review_created_at);
            } else {
              showUnreviewedState();
              checkExistingReview(_bookingId);
            }
          } else {
            revCard.style.display = 'none';
          }
        }

      } catch (e) {
        console.error('Error loading booking detail:', e);
      }
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    async function checkExistingReview(bookingId) {
      try {
        const res = await fetch('../api/reviews_api.php?action=check_review&booking_id=' + encodeURIComponent(bookingId), { cache: 'no-store' });
        const data = await res.json();
        if (data.reviewed) {
          _cachedReviewRating = Number(data.rating || 0);
          _cachedReviewComment = data.comment || '';
          showReviewedState(_cachedReviewRating, _cachedReviewComment, data.created_at);
        }
      } catch (e) {}
    }

    function showReviewedState(rating, comment, createdAt) {
      _cachedReviewRating = rating;
      _cachedReviewComment = comment;

      const cardStars = document.getElementById('reviewCardStars');
      if (cardStars) {
        cardStars.style.display = 'flex';
        const starsText = document.getElementById('reviewCardStarsText');
        if (starsText) starsText.textContent = renderStars(rating);
        const score = document.getElementById('reviewCardScore');
        if (score) score.textContent = Number(rating).toFixed(1);
      }

      const revBox = document.getElementById('reviewedState');
      if (revBox) revBox.style.display = 'block';

      const unrevBox = document.getElementById('unreviewedState');
      if (unrevBox) unrevBox.style.display = 'none';

      const commentEl = document.getElementById('reviewCommentText');
      if (commentEl) {
        commentEl.textContent = comment ? `"${comment}"` : 'No written feedback provided.';
      }

      const dateEl = document.getElementById('reviewDateText');
      if (dateEl && createdAt) {
        const dt = new Date(createdAt);
        if (!isNaN(dt.getTime())) {
          dateEl.textContent = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
      }
    }

    function showUnreviewedState() {
      const cardStars = document.getElementById('reviewCardStars');
      if (cardStars) cardStars.style.display = 'none';

      const revBox = document.getElementById('reviewedState');
      if (revBox) revBox.style.display = 'none';

      const unrevBox = document.getElementById('unreviewedState');
      if (unrevBox) unrevBox.style.display = 'block';
    }

    // Rate & Review Sheet Operations
    function openReviewSheet(existingRating = 0, existingComment = '') {
      document.getElementById('rrErr').textContent = '';
      const isEditing = existingRating > 0;
      const titleEl = document.getElementById('rrSheetTitle');
      const submitBtn = document.getElementById('rrSubmitBtn');

      if (titleEl) titleEl.textContent = isEditing ? 'Edit Your Review' : 'Rate Your Experience';
      if (submitBtn) submitBtn.textContent = isEditing ? 'Update Review' : 'Submit Review';

      if (isEditing) {
        _selectedStar = existingRating;
        document.getElementById('rrComment').value = existingComment || '';
        selectStar(existingRating);
      } else {
        _selectedStar = 0;
        document.getElementById('rrComment').value = '';
        document.getElementById('rrStarLabel').textContent = 'Tap a star to rate';
        document.querySelectorAll('.rr-star').forEach(s => s.classList.remove('active'));
      }
      document.getElementById('rrOverlay').classList.add('open');
    }

    function closeReviewSheet() {
      document.getElementById('rrOverlay').classList.remove('open');
    }

    function handleOverlayClick(e) {
      if (e.target === document.getElementById('rrOverlay')) closeReviewSheet();
    }

    function previewStars(val) {
      document.querySelectorAll('.rr-star').forEach(s => {
        s.classList.toggle('active', Number(s.dataset.v) <= val);
      });
      document.getElementById('rrStarLabel').textContent = starLabels[val] || (val === 0 ? 'Tap a star to rate' : '');
    }

    function selectStar(val) {
      _selectedStar = val;
      document.getElementById('rrStarLabel').textContent = starLabels[val] || '';
      document.querySelectorAll('.rr-star').forEach(s => {
        s.classList.toggle('active', Number(s.dataset.v) <= val);
      });
      document.getElementById('rrErr').textContent = '';
    }

    async function submitReview() {
      const errEl = document.getElementById('rrErr');
      const btn = document.getElementById('rrSubmitBtn');
      const comment = document.getElementById('rrComment').value.trim();

      if (!_selectedStar) {
        errEl.textContent = 'Please select a star rating.';
        return;
      }
      if (!_bookingId || !_providerId) {
        errEl.textContent = 'Missing booking or provider info.';
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Submitting...';
      errEl.textContent = '';

      try {
        const fd = new FormData();
        fd.append('booking_id', _bookingId);
        fd.append('provider_id', _providerId);
        fd.append('rating', _selectedStar);
        fd.append('comment', comment);

        const res = await fetch('../api/submit_review.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          closeReviewSheet();
          showReviewedState(_selectedStar, comment, new Date().toISOString());
          showToast('Thank you for your review! ⭐', 'success');
        } else {
          errEl.textContent = data.message || 'Could not submit. Try again.';
          btn.disabled = false;
          btn.textContent = 'Submit Review';
        }
      } catch (e) {
        errEl.textContent = 'Network error. Please try again.';
        btn.disabled = false;
        btn.textContent = 'Submit Review';
      }
    }

    // Receipt Modal Zoom Controls
    let _receiptZoom = 1.0;
    function openReceiptModal(url) {
      const modal = document.getElementById('receiptModal');
      const img = document.getElementById('receiptImage');
      if (!modal || !img) return;
      img.src = url;
      _receiptZoom = 1.0;
      img.style.transform = 'scale(1)';
      modal.style.display = 'flex';
    }

    function closeReceiptModal() {
      const modal = document.getElementById('receiptModal');
      if (modal) modal.style.display = 'none';
    }

    function zoomImage(delta) {
      const img = document.getElementById('receiptImage');
      if (!img) return;
      _receiptZoom = Math.min(Math.max(0.5, _receiptZoom + delta), 3.0);
      img.style.transform = `scale(${_receiptZoom})`;
    }

    function resetImageZoom() {
      const img = document.getElementById('receiptImage');
      if (!img) return;
      _receiptZoom = 1.0;
      img.style.transform = 'scale(1)';
    }

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeReceiptModal();
    });

    function showToast(msg, type = 'success') {
      const old = document.getElementById('bdToast');
      if (old) old.remove();
      const t = document.createElement('div');
      t.id = 'bdToast';
      Object.assign(t.style, {
        position: 'fixed', left: '50%', bottom: '100px',
        transform: 'translateX(-50%)', zIndex: '9999',
        padding: '12px 20px', borderRadius: '14px',
        fontFamily: 'Poppins,sans-serif', fontSize: '13px', fontWeight: '800',
        background: type === 'success' ? '#dcfce7' : '#fef2f2',
        color: type === 'success' ? '#166534' : '#991b1b',
        border: type === 'success' ? '1px solid #86efac' : '1px solid #fecaca',
        boxShadow: '0 8px 24px rgba(0,0,0,.14)', textAlign: 'center',
        width: 'min(88vw,340px)'
      });
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => {
        t.style.transition = 'opacity .25s';
        t.style.opacity = '0';
        setTimeout(() => t.remove(), 260);
      }, 2400);
    }

    // Run hydration & load updates
    loadBookingDetail();
  </script>
</body>

</html>

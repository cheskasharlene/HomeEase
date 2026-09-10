<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
enforceProviderSectionAccess('requests', $conn);

$providerId = (int) ($_SESSION['provider_id'] ?? 0);
$bookingId = (int) ($_GET['booking_id'] ?? 0);

$booking = null;
if ($bookingId > 0 && $providerId > 0) {
  $sql = "SELECT b.*,
                 COALESCE(s.name, b.service) AS service_name,
                 COALESCE(NULLIF(br.customer_name, ''), NULLIF(u.name, ''), 'Client') AS customer_name,
                 COALESCE(NULLIF(br.customer_phone, ''), NULLIF(u.phone, ''), '') AS customer_phone,
                 COALESCE(NULLIF(br.customer_address, ''), NULLIF(b.address, ''), NULLIF(u.address, ''), '') AS customer_address,
                 u.email AS customer_email,
                 br.details AS request_details,
                 br.fixed_price AS request_fixed_price,
                 br.status AS request_status,
                 p.payment_method,
                 p.payment_status,
                 p.amount AS payment_amount,
                 p.payment_proof_path,
                 p.payment_reference,
                 p.transaction_id,
                 pr.rating AS review_rating,
                 pr.comment AS review_comment,
                 pr.created_at AS review_created_at
          FROM bookings b
          LEFT JOIN services s ON s.id = b.service_id
          LEFT JOIN users u ON u.id = b.user_id
          LEFT JOIN booking_requests br ON (br.booking_id = b.id AND br.provider_id = ?)
          LEFT JOIN payments p ON p.booking_id = b.id
          LEFT JOIN provider_reviews pr ON (pr.booking_id = b.id AND pr.provider_id = ?)
          WHERE b.id = ? AND (b.provider_id = ? OR br.provider_id = ?)
          ORDER BY br.id DESC
          LIMIT 1";

  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param('iiiii', $providerId, $providerId, $bookingId, $providerId, $providerId);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();
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

$serviceName = htmlspecialchars($booking['service_name'] ?? $booking['service'] ?? 'Service');
$serviceIcon = getServiceIcon($booking['service_name'] ?? $booking['service'] ?? '');
$rawStatus = strtolower(trim((string)($booking['status'] ?? 'pending')));
$isDone = in_array($rawStatus, ['done', 'completed'], true);
$isProgress = in_array($rawStatus, ['confirmed', 'progress', 'active', 'awaiting_payment'], true);
$isCancelled = in_array($rawStatus, ['cancelled', 'canceled', 'declined'], true);

$displayPrice = (float)($booking['request_fixed_price'] ?? $booking['price'] ?? 0);
if ($displayPrice <= 0 && !empty($booking['payment_amount'])) {
  $displayPrice = (float)$booking['payment_amount'];
}

$rawNotes = trim((string)($booking['request_details'] ?? $booking['notes'] ?? ''));
$parsedNotes = [];
if ($rawNotes !== '') {
  $lines = preg_split('/\r\n|\r|\n/', $rawNotes);
  foreach ($lines as $line) {
    $clean = trim($line, " \t\n\r\0\x0B-•*");
    if ($clean === '' || stripos($clean, 'selected options') !== false) continue;
    $parsedNotes[] = $clean;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Booking Details</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/accepted_booking.css">
  <link rel="stylesheet" href="../assets/css/booking_detail.css">
  <style>
    /* Screen layout overrides */
    .screen.pbd-screen {
      justify-content: flex-start;
      background: var(--bg-screen, #FFF7EE);
    }
    .pbd-scroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 120px;
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
      background: rgba(255,255,255,0.45);
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
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
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

    /* Status Banner Card */
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

    /* Option Items */
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

    /* Customer Card */
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
      margin-top: 12px;
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

    /* Review Card */
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

    /* Image Preview Modal */
    .image-preview-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.85);
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(4px);
    }
    .image-preview-modal {
      width: min(440px, 92vw);
      background: #ffffff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
      display: flex;
      flex-direction: column;
      max-height: 85vh;
    }
    .image-preview-header {
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #E2E8F0;
    }
    .image-preview-title {
      font-family: 'Poppins', sans-serif;
      font-weight: 700;
      font-size: 14px;
      color: #1E293B;
    }
    .image-preview-controls {
      display: flex;
      gap: 6px;
    }
    .preview-btn, .preview-close {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      border: 1px solid #E2E8F0;
      background: #F8FAFC;
      color: #475569;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
    .preview-close {
      color: #EF4444;
      background: #FEF2F2;
      border-color: #FEE2E2;
    }
    .image-preview-container {
      padding: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: auto;
      background: #0F172A;
      min-height: 280px;
    }
    .preview-image {
      max-width: 100%;
      max-height: 60vh;
      object-fit: contain;
      transition: transform 0.2s ease;
    }

    /* Fixed Bottom Navigation */
    .bnav {
      position: fixed;
      left: 50%;
      bottom: 0;
      transform: translateX(-50%);
      width: min(420px, 100vw);
      z-index: 70;
    }
  </style>
</head>

<body>
  <div class="shell" id="app">
    <div class="screen pbd-screen">
      <div class="pbd-scroll">

        <!-- Top Hero Bar -->
        <div class="pbd-hero">
          <div>
            <div class="pbd-title">Booking Details</div>
            <div class="pbd-ref-pill">
              <i class="bi bi-hash"></i>Booking #<?= htmlspecialchars((string)$bookingId) ?>
            </div>
          </div>
          <button class="pbd-back-btn" onclick="handleBack()" aria-label="Back">
            <i class="bi bi-arrow-left"></i>
          </button>
        </div>

        <?php if (!$booking): ?>
          <!-- Empty / Not Found State -->
          <div class="pbd-card" style="text-align:center;padding:40px 20px;margin-top:30px;">
            <div style="font-size:44px;margin-bottom:12px;">🔍</div>
            <h3 style="font-family:'Poppins',sans-serif;font-size:17px;color:#1E293B;margin-bottom:6px;">Booking Not Found</h3>
            <p style="font-size:13px;color:#64748B;line-height:1.5;margin-bottom:20px;">We couldn't retrieve the details for this booking, or it is not assigned to your account.</p>
            <button class="pbd-act-btn call" onclick="goPage('provider_requests.php?tab=completed')" style="max-width:220px;margin:0 auto;">
              <i class="bi bi-clipboard-check"></i> Back to Requests
            </button>
          </div>
        <?php else: ?>

          <!-- Status & Price Hero Card -->
          <div class="pbd-status-card">
            <div class="pbd-status-left">
              <?php if ($isDone): ?>
                <span class="pbd-status-pill done"><i class="bi bi-check-circle-fill"></i> Completed</span>
              <?php elseif ($isProgress): ?>
                <span class="pbd-status-pill progress"><i class="bi bi-hourglass-split"></i> In Progress</span>
              <?php elseif ($isCancelled): ?>
                <span class="pbd-status-pill cancelled"><i class="bi bi-x-circle-fill"></i> Cancelled</span>
              <?php else: ?>
                <span class="pbd-status-pill done"><i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars(ucfirst($rawStatus)) ?></span>
              <?php endif; ?>
              <div class="pbd-status-time">
                <i class="bi bi-calendar3"></i> <?= formatBookingDate($booking['date'] ?? null, $booking['time_slot'] ?? null) ?>
              </div>
            </div>
            <div>
              <div class="pbd-status-price">₱<?= number_format($displayPrice, 2) ?></div>
              <div class="pbd-status-price-sub">Service Price</div>
            </div>
          </div>

          <!-- Service Summary Card -->
          <div class="pbd-card">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-briefcase-fill" style="color:#E8820C;"></i> Service Summary
              </div>
              <span style="font-size:22px;line-height:1;"><?= $serviceIcon ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-tools"></i> Service</span>
              <span class="pbd-val" style="font-size:14px;color:#0F172A;"><?= $serviceName ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-clock-history"></i> Schedule</span>
              <span class="pbd-val"><?= formatBookingDate($booking['date'] ?? null, $booking['time_slot'] ?? null) ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-geo-alt-fill"></i> Location</span>
              <span class="pbd-val"><?= htmlspecialchars($booking['address'] ?? '—') ?></span>
            </div>

            <?php if (!empty($parsedNotes)): ?>
              <div style="margin-top:14px;padding-top:12px;border-top:1px dashed #f1e4d8;">
                <div style="font-size:11px;font-weight:800;color:#8C5311;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;">
                  <i class="bi bi-list-check"></i> Selected Options &amp; Details
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:4px;">
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
                <div style="font-size:13px;color:#334155;line-height:1.45;"><?= nl2br(htmlspecialchars($rawNotes)) ?></div>
              </div>
            <?php endif; ?>
          </div>

          <!-- Customer Information Card -->
          <div class="pbd-card">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-person-badge-fill" style="color:#E8820C;"></i> Customer Details
              </div>
            </div>

            <div class="pbd-client-box">
              <div class="pbd-client-av">
                <?= strtoupper(substr($booking['customer_name'] ?? 'C', 0, 1)) ?>
              </div>
              <div class="pbd-client-info">
                <div class="pbd-client-name"><?= htmlspecialchars($booking['customer_name'] ?? 'Homeowner') ?></div>
                <div class="pbd-client-sub">
                  <i class="bi bi-telephone"></i> <?= htmlspecialchars($booking['customer_phone'] ?: 'No phone provided') ?>
                </div>
              </div>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-geo-alt"></i> Service Address</span>
              <span class="pbd-val"><?= htmlspecialchars($booking['customer_address'] ?: ($booking['address'] ?: '—')) ?></span>
            </div>

            <?php if (!$isDone && !empty($booking['customer_phone'])): ?>
              <div class="pbd-action-btns">
                <a href="tel:<?= urlencode($booking['customer_phone']) ?>" class="pbd-act-btn call">
                  <i class="bi bi-telephone-fill"></i> Call Client
                </a>
                <a href="sms:<?= urlencode($booking['customer_phone']) ?>" class="pbd-act-btn sms">
                  <i class="bi bi-chat-dots-fill"></i> Send SMS
                </a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Payment Information Card -->
          <div class="pbd-card">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-credit-card-2-front-fill" style="color:#E8820C;"></i> Payment Summary
              </div>
            </div>

            <?php
            $payMethod = strtolower((string)($booking['payment_method'] ?? 'cash'));
            $payMethodLabel = ($payMethod === 'gcash') ? 'GCash' : (($payMethod === 'bank') ? 'Bank Transfer' : 'Cash');
            $payMethodIcon = ($payMethod === 'gcash' || $payMethod === 'bank') ? 'bi-qr-code' : 'bi-cash';
            $payStatus = strtolower((string)($booking['payment_status'] ?? ''));
            $isPaid = ($payStatus === 'completed' || $isDone);
            ?>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi <?= $payMethodIcon ?>"></i> Payment Method</span>
              <span class="pbd-val"><?= $payMethodLabel ?></span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-shield-check"></i> Payment Status</span>
              <span class="pbd-val">
                <?php if ($isPaid): ?>
                  <span style="color:#059669;font-weight:800;"><i class="bi bi-check-circle-fill"></i> Paid</span>
                <?php else: ?>
                  <span style="color:#D97706;font-weight:800;"><i class="bi bi-hourglass-split"></i> <?= htmlspecialchars(ucfirst($payStatus ?: 'Pending')) ?></span>
                <?php endif; ?>
              </span>
            </div>

            <div class="pbd-row">
              <span class="pbd-lbl"><i class="bi bi-receipt"></i> Amount</span>
              <span class="pbd-val" style="color:#E8820C;font-size:15px;font-weight:800;">
                ₱<?= number_format($displayPrice, 2) ?>
              </span>
            </div>

            <?php if (!empty($booking['transaction_id'])): ?>
              <div class="pbd-row">
                <span class="pbd-lbl"><i class="bi bi-hash"></i> Reference No.</span>
                <span class="pbd-val" style="font-family:monospace;font-size:11px;"><?= htmlspecialchars($booking['transaction_id']) ?></span>
              </div>
            <?php endif; ?>

            <?php if (!empty($booking['payment_proof_path'])): ?>
              <?php $proofUrl = '../' . ltrim($booking['payment_proof_path'], '/'); ?>
              <div class="pbd-receipt-preview">
                <img src="<?= htmlspecialchars($proofUrl) ?>" class="pbd-receipt-thumb" onclick="openReceiptModal('<?= htmlspecialchars($proofUrl) ?>')" alt="Receipt proof">
                <div style="flex:1;">
                  <div style="font-size:12px;font-weight:700;color:#1E293B;">Payment Receipt</div>
                  <div style="font-size:11px;color:#64748B;">Client uploaded proof of payment</div>
                </div>
                <button class="pbd-receipt-btn" onclick="openReceiptModal('<?= htmlspecialchars($proofUrl) ?>')">
                  <i class="bi bi-eye"></i> View
                </button>
              </div>
            <?php endif; ?>
          </div>

          <!-- Customer Review Card -->
          <div class="pbd-card">
            <div class="pbd-card-head">
              <div class="pbd-card-title">
                <i class="bi bi-star-fill" style="color:#E8820C;"></i> Customer Review
              </div>
              <?php if (!empty($booking['review_rating'])): ?>
                <div class="pbd-review-stars">
                  <?php
                  $starCount = (int)$booking['review_rating'];
                  for ($i = 1; $i <= 5; $i++) {
                    echo $i <= $starCount ? '★' : '☆';
                  }
                  ?>
                  <span style="font-size:12px;font-weight:800;color:#1E293B;margin-left:4px;"><?= number_format((float)$booking['review_rating'], 1) ?></span>
                </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($booking['review_rating'])): ?>
              <div class="pbd-review-box">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                  <span style="font-size:12px;font-weight:800;color:#92400E;">Client Rating</span>
                  <?php if (!empty($booking['review_created_at'])): ?>
                    <span style="font-size:11px;color:#94A3B8;"><?= date('M j, Y', strtotime($booking['review_created_at'])) ?></span>
                  <?php endif; ?>
                </div>
                <?php if (!empty($booking['review_comment'])): ?>
                  <div class="pbd-review-text">"<?= htmlspecialchars($booking['review_comment']) ?>"</div>
                <?php else: ?>
                  <div class="pbd-review-text" style="color:#94a3b8;">No written feedback provided.</div>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <div style="text-align:center;padding:16px 8px;color:#94A3B8;font-size:12px;">
                <i class="bi bi-chat-square-dots" style="font-size:24px;display:block;margin-bottom:6px;opacity:0.6;"></i>
                No customer review submitted yet.
              </div>
            <?php endif; ?>
          </div>

        <?php endif; ?>

        <div style="height:30px;"></div>
      </div><!-- /.pbd-scroll -->

      <!-- Fixed Bottom Nav -->
      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni on" onclick="goPage('provider_requests.php?tab=completed')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span class="nl">Earnings</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>
    </div>
  </div>

  <!-- Receipt Image Modal -->
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
    function goPage(url) {
      window.location.href = url;
    }

    function handleBack() {
      if (document.referrer && document.referrer.includes('provider_')) {
        history.back();
      } else {
        goPage('provider_requests.php?tab=completed');
      }
    }

    /* Receipt Image Viewer */
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
  </script>
</body>

</html>

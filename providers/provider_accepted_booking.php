<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
enforceProviderSectionAccess('requests', $conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase - Accepted Booking</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link href="../assets/css/accepted_booking.css" rel="stylesheet">
</head>

<body>
  <div class="shell" id="app">
    <div class="screen" id="acceptedPage">
      <div class="ab-scroll">
        <div class="ab-hero">
          <div>
            <div class="ab-title">Accepted Booking</div>
            <div class="ab-sub">Client details and booking summary</div>
          </div>
          <button class="ab-back" onclick="goPage('provider_requests.php')" aria-label="Back to requests">
            <i class="bi bi-arrow-left"></i>
          </button>
        </div>

        <div class="ab-card" id="clientCard">
          <div class="ab-card-title">Client Details</div>
          <div class="ab-list">
            <div class="ab-row">
              <div class="ab-col">
                <div class="ab-label">Name</div>
                <div class="ab-value" id="clientName">-</div>
              </div>
              <div class="ab-col">
                <div class="ab-label">Contact</div>
                <div class="ab-value" id="clientPhone">-</div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col ab-wide">
                <div class="ab-label">Address</div>
                <div class="ab-value" id="clientAddress">-</div>
              </div>
            </div>
          </div>
        </div>

        <div class="ab-card" id="summaryCard">
          <div class="ab-card-title">Booking Summary</div>
          <div class="ab-list">
            <div class="ab-row">
              <div class="ab-col">
                <div class="ab-label">Service</div>
                <div class="ab-value" id="bookingService">-</div>
              </div>
              <div class="ab-col">
                <div class="ab-label">Schedule</div>
                <div class="ab-value" id="bookingSchedule">-</div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col">
                <div class="ab-label">Agreed Price</div>
                <div class="ab-value" id="bookingPrice">-</div>
              </div>
              <div class="ab-col">
                <div class="ab-label">Status</div>
                <div class="ab-value"><span class="ab-pill" id="bookingStatus">-</span></div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col ab-wide">
                <div class="ab-label">Details</div>
                <div class="ab-value ab-notes" id="bookingDetails">-</div>
              </div>
            </div>
          </div>
        </div>

        <div class="ab-actions">
          <button class="ab-btn" onclick="goPage('provider_schedule.php')">Open schedule</button>
          <button class="ab-btn secondary" onclick="goPage('provider_requests.php')">Back to requests</button>
        </div>

        <div class="ab-empty ab-hide" id="emptyState">
          <div class="ab-empty-title">No accepted booking</div>
          <div class="ab-empty-sub">Accepted booking details will appear here once a request is confirmed.</div>
          <button class="ab-btn" onclick="goPage('provider_requests.php')">Back to requests</button>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni on" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span class="nl">Calendar</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
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

    function formatPrice(value) {
      const num = Number(value || 0);
      return 'PHP ' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatSchedule(date, timeSlot) {
      if (!date) return '-';
      const dt = new Date(date + 'T00:00:00');
      const dateLabel = dt.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      if (!timeSlot) return dateLabel;
      return dateLabel + ' - ' + timeSlot;
    }

    function showEmptyState() {
      document.getElementById('clientCard').classList.add('ab-hide');
      document.getElementById('summaryCard').classList.add('ab-hide');
      document.querySelector('.ab-actions').classList.add('ab-hide');
      document.getElementById('emptyState').classList.remove('ab-hide');
    }

    async function loadAcceptedBooking() {
      const params = new URLSearchParams(window.location.search);
      const bookingId = params.get('booking_id');
      const url = '../api/accepted_booking_api.php' + (bookingId ? '?booking_id=' + encodeURIComponent(bookingId) : '');

      try {
        const res = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !data.booking) {
          showEmptyState();
          return;
        }

        const b = data.booking;
        document.getElementById('clientName').textContent = b.client_name || 'Client';
        document.getElementById('clientPhone').textContent = b.client_phone || 'Not available';
        document.getElementById('clientAddress').textContent = b.client_address || 'Address not available';

        document.getElementById('bookingService').textContent = b.service || 'Service';
        document.getElementById('bookingSchedule').textContent = formatSchedule(b.date, b.time_slot);
        document.getElementById('bookingPrice').textContent = formatPrice(b.price || 0);
        document.getElementById('bookingStatus').textContent = (b.status || 'confirmed').toString().toUpperCase();
        document.getElementById('bookingDetails').textContent = b.details || b.notes || 'None';
      } catch (e) {
        showEmptyState();
      }
    }

    loadAcceptedBooking();
  </script>
</body>

</html>

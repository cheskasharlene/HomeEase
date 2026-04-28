<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase - Accepted Booking</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/accepted_booking.css">
</head>

<body>
  <div class="shell">
    <div class="screen" id="acceptedPage">
      <div class="ab-scroll">
        <div class="ab-hero">
          <div>
            <div class="ab-title">Booking Accepted</div>
            <div class="ab-sub">Your provider is confirmed. Review the details below.</div>
          </div>
          <button class="ab-back" onclick="goPage('booking_history.php')" aria-label="Back to bookings">
            <i class="bi bi-arrow-left"></i>
          </button>
        </div>

        <div class="ab-card" id="providerCard">
          <div class="ab-card-title">Provider Details</div>
          <div class="ab-list">
            <div class="ab-row">
              <div class="ab-col">
                <div class="ab-label">Name</div>
                <div class="ab-value" id="providerName">-</div>
              </div>
              <div class="ab-col">
                <div class="ab-label">Service</div>
                <div class="ab-value" id="providerService">-</div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col">
                <div class="ab-label">Contact</div>
                <div class="ab-value" id="providerPhone">-</div>
              </div>
              <div class="ab-col">
                <div class="ab-label">Rating</div>
                <div class="ab-rating">
                  <div class="ab-stars" id="providerStars"></div>
                  <div class="ab-rating-text" id="providerRatingText">-</div>
                </div>
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
                <div class="ab-label">Address</div>
                <div class="ab-value" id="bookingAddress">-</div>
              </div>
              <div class="ab-col">
                <div class="ab-label">Price</div>
                <div class="ab-value" id="bookingPrice">-</div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col ab-wide">
                <div class="ab-label">Notes</div>
                <div class="ab-value ab-notes" id="bookingNotes">-</div>
              </div>
            </div>
          </div>
        </div>

        <div class="ab-empty ab-hide" id="emptyState">
          <div class="ab-empty-title">No accepted booking</div>
          <div class="ab-empty-sub">Once a provider accepts your request, details will appear here.</div>
          <button class="ab-btn" onclick="goPage('booking_history.php')">Back to bookings</button>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('../home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni on" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
        <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
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

    function renderStars(rating) {
      const full = Math.round(Number(rating || 0));
      let html = '';
      for (let i = 1; i <= 5; i += 1) {
        html += `<i class="bi ${i <= full ? 'bi-star-fill' : 'bi-star'}"></i>`;
      }
      return html;
    }

    function showEmptyState() {
      document.getElementById('providerCard').classList.add('ab-hide');
      document.getElementById('summaryCard').classList.add('ab-hide');
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
        document.getElementById('providerName').textContent = b.provider_name || 'Service Provider';
        document.getElementById('providerService').textContent = b.provider_service || b.service || 'Service';
        document.getElementById('providerPhone').textContent = b.provider_phone || 'Not available';

        const ratingVal = Number(b.provider_rating || 0);
        const ratingText = ratingVal > 0
          ? ratingVal.toFixed(1) + ' (' + (b.provider_review_count || 0) + ' reviews)'
          : 'No ratings yet';

        document.getElementById('providerStars').innerHTML = renderStars(ratingVal);
        document.getElementById('providerRatingText').textContent = ratingText;

        document.getElementById('bookingService').textContent = b.service || 'Service';
        document.getElementById('bookingSchedule').textContent = formatSchedule(b.date, b.time_slot);
        document.getElementById('bookingAddress').textContent = b.address || 'Address not available';
        document.getElementById('bookingPrice').textContent = formatPrice(b.price || 0);
        document.getElementById('bookingNotes').textContent = b.details || b.notes || 'None';
      } catch (e) {
        showEmptyState();
      }
    }

    loadAcceptedBooking();
  </script>
</body>

</html>

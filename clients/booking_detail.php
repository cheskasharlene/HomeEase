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
  <title>HomeEase - Booking Details</title>
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
    .screen.booking-detail-screen {
      justify-content: flex-start;
    }

    .booking-detail-screen .ab-scroll {
      padding-bottom: 154px;
    }

    .booking-detail-screen .bnav {
      position: fixed;
      left: 50%;
      bottom: 0;
      transform: translateX(-50%);
      width: min(420px, 100vw);
      z-index: 70;
    }

    @media (max-width: 420px) {
      .booking-detail-screen .ab-scroll {
        padding-bottom: 148px;
      }
    }
  </style>
</head>

<body>
  <div class="shell">
    <div class="screen booking-detail-screen">
      <div class="ab-scroll">
        <div class="ab-hero">
          <div>
            <div class="ab-title">Booking Details</div>
            <div class="ab-sub">Review your booking information below.</div>
          </div>
          <button class="ab-back" onclick="goPage('booking_history.php')" aria-label="Back to bookings">
            <i class="bi bi-arrow-left"></i>
          </button>
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
                <div class="ab-label">Status</div>
                <div class="ab-value">
                  <span class="bd-pill pending" id="bookingStatusPill">Pending</span>
                </div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col">
                <div class="ab-label">Schedule</div>
                <div class="ab-value" id="bookingSchedule">-</div>
              </div>
              <div class="ab-col">
                <div class="ab-label">Price</div>
                <div class="ab-value" id="bookingPrice">-</div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col ab-wide">
                <div class="ab-label">Address</div>
                <div class="ab-value" id="bookingAddress">-</div>
              </div>
            </div>
            <div class="ab-row">
              <div class="ab-col ab-wide">
                <div class="ab-label">Notes / Details</div>
                <div class="ab-value ab-notes" id="bookingNotes">-</div>
              </div>
            </div>
          </div>
        </div>

        <div class="ab-card service-scope-card" id="serviceScopeCardDetail" style="display:none;"></div>

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
          <div class="bd-provider-note" id="providerNote">Provider details will appear once assigned.</div>
        </div>

        <div class="ab-empty ab-hide" id="emptyState">
          <div class="ab-empty-title">Booking not found</div>
          <div class="ab-empty-sub">This booking may have been removed or you no longer have access.</div>
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

    function normalizeStatus(raw) {
      const s = String(raw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'done';
      if (s === 'cancelled' || s === 'canceled') return 'cancelled';
      if (s === 'confirmed' || s === 'progress' || s === 'active') return 'progress';
      return 'pending';
    }

    function statusLabel(raw) {
      const s = String(raw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'Completed';
      if (s === 'cancelled' || s === 'canceled') return 'Canceled';
      if (s === 'confirmed' || s === 'progress' || s === 'active') return 'In Progress';
      return 'Pending';
    }

    function renderStars(rating) {
      const full = Math.round(Number(rating || 0));
      let html = '';
      for (let i = 1; i <= 5; i += 1) {
        html += `<i class="bi ${i <= full ? 'bi-star-fill' : 'bi-star'}"></i>`;
      }
      return html;
    }

    const serviceScopeConfig = {
      'House Cleaner': {
        baseFee: 500,
        included: [
          'Basic room cleaning and tidying',
          'Sweeping, mopping, dusting, and wiping surfaces',
          'Bathroom and kitchen surface cleanup'
        ],
        excluded: [
          'Cleaning materials and supplies',
          'Heavy stain removal or deep restoration',
          'Special equipment rentals or disposal fees'
        ]
      },
      'Plumber': {
        baseFee: 500,
        included: [
          'Inspection, diagnosis, and labor for the requested repair',
          'Minor troubleshooting and standard installation labor'
        ],
        excluded: [
          'Replacement pipes, valves, fixtures, and other parts',
          'Special materials or specialty fittings',
          'Major demolition or rebuilding work'
        ]
      },
      'Carpenter': {
        baseFee: 600,
        included: [
          'Labor for repairs, assembly, or installation',
          'Basic carpentry assessment and measurement'
        ],
        excluded: [
          'Wood, hardware, paint, varnish, and other materials',
          'Custom fabrication beyond the agreed scope'
        ]
      },
      'Appliance Technician': {
        baseFee: 500,
        included: [
          'Diagnosis and labor for the selected appliance issue',
          'Basic cleaning of accessible parts during service'
        ],
        excluded: [
          'Replacement parts, refrigerant, or specialty components',
          'Major repairs that require manufacturer-only parts'
        ]
      }
    };

    function renderScopeItems(items) {
      return (items || []).map(item => `<li>${item}</li>`).join('');
    }

    function renderServiceScopeCard(booking) {
      const card = document.getElementById('serviceScopeCardDetail');
      const scope = serviceScopeConfig[String(booking.service || '')];
      if (!card || !scope) {
        if (card) {
          card.style.display = 'none';
          card.innerHTML = '';
        }
        return;
      }

      const supplyOption = String(booking.supply_option || 'client').toLowerCase();
      const supplyFee = Number(booking.supply_fee || 0);

      card.style.display = 'block';
      card.innerHTML = `
        <div class="service-scope-head">
          <div>
            <div class="service-scope-title">Service Conditions / Scope</div>
            <div class="service-scope-sub">Service fee only. Included and excluded items are shown here for clarity.</div>
          </div>
          <div class="service-scope-badge">Base fee: PHP ${scope.baseFee.toLocaleString('en-PH')}</div>
        </div>
        <div class="service-scope-grid">
          <div class="service-scope-panel included">
            <div class="service-scope-panel-title"><i class="bi bi-check2-circle"></i> Included</div>
            <ul class="service-scope-list">${renderScopeItems(scope.included)}</ul>
          </div>
          <div class="service-scope-panel excluded">
            <div class="service-scope-panel-title"><i class="bi bi-x-circle"></i> Excluded</div>
            <ul class="service-scope-list">${renderScopeItems(scope.excluded)}</ul>
          </div>
        </div>
        <div class="service-scope-supply">
          <div class="service-scope-panel-title"><i class="bi bi-box-seam"></i> Materials / Supplies</div>
          <div class="service-scope-choice service-scope-choice-static">
            <div>
              <div class="service-scope-choice-name">${supplyOption === 'provider' ? 'Service Provider will bring the supplies' : 'Client will provide the materials/supplies'}</div>
              <div class="service-scope-choice-note">${supplyOption === 'provider' ? `Added to total: PHP ${supplyFee.toLocaleString('en-PH')}` : 'No additional supply fee.'}</div>
            </div>
          </div>
        </div>
      `;
    }

    function showEmptyState() {
      document.getElementById('summaryCard').classList.add('ab-hide');
      document.getElementById('providerCard').classList.add('ab-hide');
      document.getElementById('emptyState').classList.remove('ab-hide');
    }

    async function loadBookingDetail() {
      const params = new URLSearchParams(window.location.search);
      const bookingId = params.get('booking_id');
      if (!bookingId) {
        showEmptyState();
        return;
      }

      const url = '../api/bookings_api.php?action=detail&booking_id=' + encodeURIComponent(bookingId);

      try {
        const res = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !data.booking) {
          showEmptyState();
          return;
        }

        const b = data.booking;
        document.getElementById('bookingService').textContent = b.service || 'Service';
        document.getElementById('bookingSchedule').textContent = formatSchedule(b.date, b.time_slot);
        document.getElementById('bookingAddress').textContent = b.address || 'Address not available';
        document.getElementById('bookingPrice').textContent = formatPrice(b.price || 0);
        document.getElementById('bookingNotes').textContent = b.details || b.notes || 'None';
        renderServiceScopeCard(b);

        const pill = document.getElementById('bookingStatusPill');
        const statusKey = normalizeStatus(b.status);
        pill.textContent = statusLabel(b.status);
        pill.className = 'bd-pill ' + statusKey;

        const providerId = Number(b.provider_id || 0);
        const hasProvider = providerId > 0 || (b.provider_name && b.provider_name.trim());

        document.getElementById('providerName').textContent = hasProvider ? (b.provider_name || 'Service Provider') : 'Awaiting assignment';
        document.getElementById('providerService').textContent = hasProvider ? (b.provider_service || b.service || 'Service') : '—';
        document.getElementById('providerPhone').textContent = hasProvider ? (b.provider_phone || 'Not available') : '—';

        const ratingVal = Number(b.provider_rating || 0);
        const ratingText = ratingVal > 0
          ? ratingVal.toFixed(1) + ' (' + (b.provider_review_count || 0) + ' reviews)'
          : (hasProvider ? 'No ratings yet' : 'No provider yet');

        document.getElementById('providerStars').innerHTML = hasProvider ? renderStars(ratingVal) : '';
        document.getElementById('providerRatingText').textContent = ratingText;
        document.getElementById('providerNote').style.display = hasProvider ? 'none' : 'block';
      } catch (e) {
        showEmptyState();
      }
    }

    loadBookingDetail();
  </script>
</body>

</html>

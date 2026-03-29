<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}
$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Booking History</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/bookings.css">
  <link rel="stylesheet" href="assets/css/booking_history.css">
</head>

<body>
  <div id="toastBox"></div>
  <div class="shell">

    <div class="hdr">
      <div class="hdr-top">
        <div>
          <div class="hdr-sub">Hi, <?= $userName ?> 👋</div>
          <div class="hdr-title">Booking History</div>
        </div>
        <a href="home.php" class="hdr-btn"><i class="bi bi-arrow-left"></i></a>
      </div>
    </div>

    <div class="scroll">
      <div class="sec-head">
        <div class="sec-title" style="margin:0;">My Bookings</div>
        <div class="sec-count" id="bkCount">0</div>
      </div>

      <div class="tabs-row">
        <button class="tab-chip on" data-filter="all">All Bookings</button>
        <button class="tab-chip" data-filter="pending">Pending</button>
        <button class="tab-chip" data-filter="completed">Completed</button>
        <button class="tab-chip" data-filter="canceled">Canceled</button>
      </div>

      <div class="sec" style="padding-top:0;">
        <div id="myBookings">
          <div class="loader-txt"><i class="bi bi-arrow-clockwise"></i> Loading...</div>
        </div>
      </div>
    </div>

    <div class="bnav">
      <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
      <div class="ni on" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
      <div class="ni" onclick="goPage('service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
      <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
      <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
    </div>
  </div>

  <div class="review-modal-overlay" id="reviewModalOverlay" onclick="closeReviewModal()">
    <div class="review-modal-card" onclick="event.stopPropagation()">
      <div class="review-modal-close" onclick="closeReviewModal()"><i class="bi bi-x-lg" style="font-size:14px;"></i></div>
      <div style="text-align:center;margin-bottom:4px;">
        <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#FEF3C7,#FDE68A);display:inline-flex;align-items:center;justify-content:center;font-size:24px;margin-bottom:10px;">⭐</div>
        <div style="font-size:18px; font-weight:800; color:var(--td); font-family:'Poppins',sans-serif;">Rate your experience</div>
        <div style="font-size:13px; color:var(--tm); margin-top:4px;">How was the service by <span id="revProvName" style="font-weight:700; color:var(--td);"></span>?</div>
      </div>

      <div class="stars-container" id="starContainer">
        <i class="bi bi-star-fill star-btn" data-val="1"></i>
        <i class="bi bi-star-fill star-btn" data-val="2"></i>
        <i class="bi bi-star-fill star-btn" data-val="3"></i>
        <i class="bi bi-star-fill star-btn" data-val="4"></i>
        <i class="bi bi-star-fill star-btn" data-val="5"></i>
      </div>

      <textarea class="review-textarea" id="revComment" placeholder="Write a short review (optional)..."></textarea>

      <button class="btn-book" style="width:100%; border-radius:14px; height:50px;" id="btnSubmitReview" onclick="submitReview()">
        Submit Review
      </button>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    initTheme();

    function goPage(page) {
      window.location.href = page;
    }

    let allBookings = [];
    let activeFilter = 'all';

    function normalizeStatus(raw) {
      const s = String(raw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'completed';
      if (s === 'cancelled' || s === 'canceled') return 'canceled';
      if (s === 'confirmed' || s === 'progress' || s === 'active') return 'pending';
      return 'pending';
    }

    function filterBookings() {
      if (activeFilter === 'all') return allBookings;
      return allBookings.filter(b => normalizeStatus(b.status) === activeFilter);
    }

    async function loadMyBookings() {
      const el = document.getElementById('myBookings');
      try {
        const res = await fetch('api/bookings_api.php');
        const data = await res.json();

        allBookings = data.success && Array.isArray(data.bookings) ? data.bookings : [];
        renderBookings();
      } catch (e) {
        el.innerHTML = `<div class="empty"><i class="bi bi-wifi-off"></i><p>Could not load bookings.</p></div>`;
      }
    }

    function renderBookings() {
      const el = document.getElementById('myBookings');
      const rows = filterBookings();
      document.getElementById('bkCount').textContent = `${rows.length} ${rows.length === 1 ? 'booking' : 'bookings'}`;

      if (!rows.length) {
        el.innerHTML = `<div class="empty"><i class="bi bi-calendar-x"></i><p>No bookings for this tab.</p></div>`;
        return;
      }

      const svcIcon = {
        'Cleaning': '🧹', 'Plumbing': '🔧', 'Electrical': '⚡',
        'Painting': '🖌️', 'Appliance Repair': '🔩'
      };

      const svcIcClass = {
        'Cleaning': 'ic-cleaning', 'Plumbing': 'ic-plumbing', 'Electrical': 'ic-electrical',
        'Painting': 'ic-painting', 'Appliance Repair': 'ic-appliance'
      };

      el.innerHTML = rows.map(b => {
        const icon = svcIcon[b.service] || '🏠';
        const icCls = svcIcClass[b.service] || '';
        const rawStatus = normalizeStatus(b.status);
        const pillClass = rawStatus === 'canceled' ? 'cancelled' : rawStatus === 'completed' ? 'done' : 'pending';
        const srcStatus = String(b.status || '').toLowerCase();
        const statusText = rawStatus === 'canceled'
          ? 'Canceled'
          : rawStatus === 'completed'
            ? 'Completed'
            : (srcStatus === 'confirmed' || srcStatus === 'progress' ? 'In Progress' : 'Pending');
        const providerName = b.technician_name ? b.technician_name : 'Awaiting assignment';
        const dateFormatted = b.date ? new Date(b.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '—';
        const timeSlot = b.time_slot || '';
        const price = b.price ? '₱' + parseFloat(b.price).toLocaleString('en-PH', { minimumFractionDigits: 0 }) : '';
        const createdAt = b.created_at ? new Date(String(b.created_at).replace(' ', 'T')) : null;
        const minsWaiting = createdAt ? ((Date.now() - createdAt.getTime()) / 60000) : 0;
        const isUnserved = rawStatus === 'pending' && !b.technician_name && minsWaiting >= 5;
        const waitingHint = rawStatus === 'pending' && !b.technician_name
          ? `<div style="margin-top:10px;padding:8px 12px;background:${isUnserved ? '#FEF2F2' : '#FFFBEB'};border-radius:10px;font-size:11px;color:${isUnserved ? '#B91C1C' : '#92400E'};font-weight:600;display:flex;align-items:center;gap:6px;">
              <i class="bi bi-${isUnserved ? 'exclamation-triangle-fill' : 'hourglass-split'}" style="font-size:13px;"></i>
              ${isUnserved ? 'No providers available. Try changing schedule.' : 'Waiting for a provider to accept…'}
            </div>`
          : '';

        const leaveReviewHint = rawStatus === 'completed' && b.technician_id && parseInt(b.has_reviewed || 0) === 0
          ? `<div style="margin-top:12px; border-top: 1px dashed var(--border-col); padding-top: 12px;">
              <button class="btn-book" style="height:40px;font-size:12px;width:100%;border-radius:12px;background:linear-gradient(135deg,#FFF7ED,#FEF3C7);color:#D97706;border:1.5px solid #FDE68A;box-shadow:none;font-family:'Nunito',sans-serif;font-weight:800;" onclick="openReviewModal(${b.id}, ${b.technician_id}, '${providerName.replace(/'/g, "\\'")}')">
                <i class="bi bi-star-fill" style="color:#F59E0B;"></i> Rate & Review
              </button>
            </div>`
          : '';

        return `
        <div class="bk-card" data-svc="${b.service || ''}">
          <div class="bk-top">
            <div class="bk-ic ${icCls}">${icon}</div>
            <div style="flex:1;min-width:0;">
              <div class="bk-svc">${b.service || 'Service'}</div>
              <div class="bk-meta">
                <i class="bi bi-person-badge" style="font-size:9px;margin-right:2px;"></i> ${providerName}<br>
                <i class="bi bi-calendar3" style="font-size:9px;margin-right:2px;"></i> ${dateFormatted}${timeSlot ? ' · ' + timeSlot : ''}
              </div>
            </div>
            <div class="bk-right">
              <span class="pill ${pillClass}">${statusText}</span>
              ${price ? `<div class="bk-price">${price}</div>` : ''}
            </div>
          </div>
          ${waitingHint}
          ${leaveReviewHint}
        </div>`;
      }).join('');
    }

    document.querySelectorAll('.tab-chip').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.tab-chip').forEach(b => b.classList.remove('on'));
        this.classList.add('on');
        activeFilter = this.dataset.filter;
        renderBookings();
      });
    });

    let currentReviewBooking = 0;
    let currentReviewProvider = 0;
    let currentRating = 5;

    document.querySelectorAll('.star-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const val = parseInt(this.dataset.val);
            setRating(val);
        });
    });

    function setRating(val) {
        currentRating = val;
        document.querySelectorAll('.star-btn').forEach(btn => {
            if (parseInt(btn.dataset.val) <= val) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    function openReviewModal(bId, pId, pName) {
        currentReviewBooking = bId;
        currentReviewProvider = pId;
        document.getElementById('revProvName').textContent = pName;
        document.getElementById('revComment').value = '';
        setRating(5);
        document.getElementById('reviewModalOverlay').classList.add('show');
    }

    function closeReviewModal() {
        document.getElementById('reviewModalOverlay').classList.remove('show');
    }

    async function submitReview() {
        const btn = document.getElementById('btnSubmitReview');
        const comment = document.getElementById('revComment').value.trim();

        btn.disabled = true;
        btn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Submitting...';

        const fd = new FormData();
        fd.append('action', 'add_review');
        fd.append('booking_id', currentReviewBooking);
        fd.append('provider_id', currentReviewProvider);
        fd.append('rating', currentRating);
        fd.append('comment', comment);

        try {
            const res = await fetch('api/reviews_api.php', { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                toast(data.message, 's');
                closeReviewModal();
                loadMyBookings();
            } else {
                toast(data.message || 'Error saving review', 'e');
            }
        } catch (e) {
            toast('Network error saving review', 'e');
        }

        btn.disabled = false;
        btn.innerHTML = 'Submit Review';
    }

    function toast(msg, type = 's') {
      const t = document.createElement('div');
      t.className = `toast-n ${type}`;
      t.innerHTML = `<i class="bi bi-${type === 's' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>${msg}`;
      document.getElementById('toastBox').appendChild(t);
      setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
    }

    loadMyBookings();
  </script>
</body>

</html>

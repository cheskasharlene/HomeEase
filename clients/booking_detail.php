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
    .screen.booking-detail-screen { justify-content: flex-start; }
    .booking-detail-screen .ab-scroll { padding-bottom: 154px; }
    .booking-detail-screen .bnav {
      position: fixed; left: 50%; bottom: 0;
      transform: translateX(-50%); width: min(420px, 100vw); z-index: 70;
    }
    @media (max-width: 420px) { .booking-detail-screen .ab-scroll { padding-bottom: 148px; } }

    /* ── Rate & Review Button ── */
    .rr-btn-wrap { padding: 0 20px 16px; }
    .rr-btn {
      width: 100%; padding: 14px; border: none; border-radius: 16px;
      background: linear-gradient(135deg,#E8820C,#F5A623);
      color: #fff; font-family: 'Poppins',sans-serif;
      font-size: 14px; font-weight: 800; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      box-shadow: 0 6px 20px rgba(232,130,12,.35);
      transition: transform .2s ease, box-shadow .2s ease;
    }
    .rr-btn:hover { transform: translateY(-2px); box-shadow: 0 10px 28px rgba(232,130,12,.45); }
    .rr-btn:active { transform: scale(.97); }
    .rr-btn i { font-size: 16px; }
    .rr-done-badge {
      width: 100%; padding: 13px; border-radius: 16px;
      background: linear-gradient(135deg,#d1fae5,#a7f3d0);
      color: #065f46; font-family: 'Poppins',sans-serif;
      font-size: 13px; font-weight: 800; text-align: center;
      display: flex; align-items: center; justify-content: center; gap: 7px;
      border: 1.5px solid #6ee7b7;
    }
    .rr-done-badge i { font-size: 15px; }

    /* ── Review Bottom Sheet ── */
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
    /* Stars */
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
    /* Comment */
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
    /* Actions */
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

        <!-- Rate & Review section — shown only on completed bookings -->
        <div class="rr-btn-wrap ab-hide" id="reviewSection">
          <div id="reviewBtnArea">
            <button class="rr-btn" id="openReviewBtn" onclick="openReviewSheet()">
              <i class="bi bi-star-fill"></i> Rate &amp; Review
            </button>
          </div>
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

  <!-- ── Review Bottom Sheet ── -->
  <div class="rr-overlay" id="rrOverlay" onclick="handleOverlayClick(event)">
    <div class="rr-sheet" id="rrSheet">
      <div class="rr-handle"></div>
      <div class="rr-sheet-title">Rate Your Experience</div>
      <div class="rr-sheet-sub" id="rrSheetSub">How was the service?</div>
      <div class="rr-stars" id="rrStars">
        <span class="rr-star" data-v="1" onclick="selectStar(1)">&#9733;</span>
        <span class="rr-star" data-v="2" onclick="selectStar(2)">&#9733;</span>
        <span class="rr-star" data-v="3" onclick="selectStar(3)">&#9733;</span>
        <span class="rr-star" data-v="4" onclick="selectStar(4)">&#9733;</span>
        <span class="rr-star" data-v="5" onclick="selectStar(5)">&#9733;</span>
      </div>
      <div class="rr-star-label" id="rrStarLabel"></div>
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

    function showEmptyState() {
      document.getElementById('summaryCard').classList.add('ab-hide');
      document.getElementById('providerCard').classList.add('ab-hide');
      document.getElementById('emptyState').classList.remove('ab-hide');
    }

    // ── State ──────────────────────────────────────────────────────────────
    let _bookingId   = null;
    let _providerId  = null;
    let _selectedStar = 0;
    const starLabels = ['','Terrible 😟','Not Great 😕','Okay 😐','Good 😊','Excellent 🤩'];

    // ── Load booking ───────────────────────────────────────────────────────
    async function loadBookingDetail() {
      const params = new URLSearchParams(window.location.search);
      _bookingId = params.get('booking_id');
      if (!_bookingId) { showEmptyState(); return; }

      const url = '../api/bookings_api.php?action=detail&booking_id=' + encodeURIComponent(_bookingId);
      try {
        const res  = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !data.booking) { showEmptyState(); return; }

        const b = data.booking;
        document.getElementById('bookingService').textContent  = b.service || 'Service';
        document.getElementById('bookingSchedule').textContent = formatSchedule(b.date, b.time_slot);
        document.getElementById('bookingAddress').textContent  = b.address || 'Address not available';
        document.getElementById('bookingPrice').textContent    = formatPrice(b.price || 0);
        document.getElementById('bookingNotes').textContent    = b.details || b.notes || 'None';

        const pill      = document.getElementById('bookingStatusPill');
        const statusKey = normalizeStatus(b.status);
        pill.textContent = statusLabel(b.status);
        pill.className   = 'bd-pill ' + statusKey;

        _providerId = Number(b.provider_id || 0);
        const hasProvider = _providerId > 0 || (b.provider_name && b.provider_name.trim());

        document.getElementById('providerName').textContent    = hasProvider ? (b.provider_name || 'Service Provider') : 'Awaiting assignment';
        document.getElementById('providerService').textContent = hasProvider ? (b.provider_service || b.service || 'Service') : '—';
        document.getElementById('providerPhone').textContent   = hasProvider ? (b.provider_phone || 'Not available') : '—';

        const ratingVal  = Number(b.provider_rating || 0);
        const ratingText = ratingVal > 0
          ? ratingVal.toFixed(1) + ' (' + (b.provider_review_count || 0) + ' reviews)'
          : (hasProvider ? 'No ratings yet' : 'No provider yet');
        document.getElementById('providerStars').innerHTML    = hasProvider ? renderStars(ratingVal) : '';
        document.getElementById('providerRatingText').textContent = ratingText;
        document.getElementById('providerNote').style.display = hasProvider ? 'none' : 'block';

        // Show Rate & Review section only on completed bookings
        if (statusKey === 'done' && hasProvider) {
          document.getElementById('reviewSection').classList.remove('ab-hide');
          // Check if already reviewed
          checkExistingReview(_bookingId);
        }
      } catch (e) { showEmptyState(); }
    }

    // ── Check if already reviewed ──────────────────────────────────────────
    async function checkExistingReview(bookingId) {
      try {
        const res  = await fetch('../api/reviews_api.php?action=check_review&booking_id=' + encodeURIComponent(bookingId), { cache: 'no-store' });
        const data = await res.json();
        if (data.reviewed) showAlreadyReviewed();
      } catch (e) { /* non-critical */ }
    }

    function showAlreadyReviewed() {
      document.getElementById('reviewBtnArea').innerHTML =
        '<div class="rr-done-badge"><i class="bi bi-patch-check-fill"></i> You already reviewed this booking</div>';
    }

    // ── Review Sheet ───────────────────────────────────────────────────────
    function openReviewSheet() {
      _selectedStar = 0;
      document.getElementById('rrComment').value = '';
      document.getElementById('rrErr').textContent = '';
      document.getElementById('rrStarLabel').textContent = '';
      document.querySelectorAll('.rr-star').forEach(s => s.classList.remove('active'));
      document.getElementById('rrOverlay').classList.add('open');
    }

    function closeReviewSheet() {
      document.getElementById('rrOverlay').classList.remove('open');
    }

    function handleOverlayClick(e) {
      if (e.target === document.getElementById('rrOverlay')) closeReviewSheet();
    }

    function selectStar(val) {
      _selectedStar = val;
      document.getElementById('rrStarLabel').textContent = starLabels[val] || '';
      document.querySelectorAll('.rr-star').forEach(s => {
        s.classList.toggle('active', Number(s.dataset.v) <= val);
      });
      document.getElementById('rrErr').textContent = '';
    }

    // ── Submit ─────────────────────────────────────────────────────────────
    async function submitReview() {
      const errEl  = document.getElementById('rrErr');
      const btn    = document.getElementById('rrSubmitBtn');
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
        fd.append('booking_id',  _bookingId);
        fd.append('provider_id', _providerId);
        fd.append('rating',      _selectedStar);
        fd.append('comment',     comment);

        const res  = await fetch('../api/submit_review.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          closeReviewSheet();
          showAlreadyReviewed(); // swap button to done-badge
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

    // ── Toast ──────────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
      const old = document.getElementById('bdToast');
      if (old) old.remove();
      const t = document.createElement('div');
      t.id = 'bdToast';
      Object.assign(t.style, {
        position:'fixed', left:'50%', bottom:'100px',
        transform:'translateX(-50%)', zIndex:'9999',
        padding:'12px 20px', borderRadius:'14px',
        fontFamily:'Poppins,sans-serif', fontSize:'13px', fontWeight:'800',
        background: type==='success'?'#dcfce7':'#fef2f2',
        color: type==='success'?'#166534':'#991b1b',
        border: type==='success'?'1px solid #86efac':'1px solid #fecaca',
        boxShadow:'0 8px 24px rgba(0,0,0,.14)', textAlign:'center',
        width:'min(88vw,340px)'
      });
      t.textContent = msg;
      document.body.appendChild(t);
      setTimeout(() => { t.style.transition='opacity .25s'; t.style.opacity='0'; setTimeout(()=>t.remove(),260); }, 2400);
    }

    loadBookingDetail();
  </script>
</body>

</html>

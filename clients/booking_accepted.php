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
          <div class="ab-card-head">
            <div class="ab-card-title">Booking Summary</div>
          </div>
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

        <div class="ab-card" id="paymentCard">
          <div class="ab-card-head">
            <div class="ab-card-title">Payment Details</div>
          </div>
          <div class="ab-list">
            <div id="paymentInfo">
              <div class="ab-row">
                <div class="ab-col">
                  <div class="ab-label">Expected Amount</div>
                  <div class="ab-value" id="expectedAmount">-</div>
                </div>
                <div class="ab-col">
                  <div class="ab-label">Payment Method</div>
                  <div class="ab-value" id="paymentMethodLabel">-</div>
                </div>
              </div>
              <div class="ab-row">
                <div class="ab-col">
                  <div class="ab-label">Payment Status</div>
                  <div class="ab-value" id="paymentStatus">-</div>
                </div>
                <div class="ab-col">
                  <div class="ab-label">Pay Before</div>
                  <div class="ab-value" id="paymentDeadline">-</div>
                </div>
              </div>
            </div>

            <div id="cashPaymentNote" class="ab-note ab-note-success ab-hide">
              Cash payment — pay your provider when the service is completed.
            </div>

            <div id="paymentWaitingNote" class="ab-note ab-note-warning ab-hide">
              Receipt submitted. Waiting for your provider to confirm payment.
            </div>

            <div id="paymentCompletedNote" class="ab-note ab-note-success ab-hide">
              Payment confirmed! Your provider will proceed with the service.
              <div class="ab-note-actions">
                <button class="ab-btn" type="button" onclick="goToTracking()">Track Provider</button>
              </div>
            </div>

            <form id="paymentForm" class="ab-hide" onsubmit="submitPayment(event)" enctype="multipart/form-data">
              <input type="hidden" id="paymentMethod" name="payment_method" value="">

              <div class="ab-qr-box" id="qrContainerWrapper">
                <div class="ab-qr-title">Scan Provider QR to Pay</div>
                <div id="qrImageDiv" class="ab-qr-image-wrap"></div>
                <div id="qrTextFallback" class="ab-qr-text"></div>
              </div>

              <div class="ab-row">
                <div class="ab-col">
                  <label class="ab-label">Transaction/Reference No.</label>
                  <input type="text" id="txnRef" name="payment_reference" required placeholder="Enter reference number" />
                </div>
                <div class="ab-col">
                  <label class="ab-label">Sender Name</label>
                  <input type="text" id="senderName" name="sender_name" required placeholder="Enter sender name" />
                </div>
              </div>

              <div class="ab-upload-card">
                <label class="ab-label" for="paymentProof">Proof of Payment Image</label>
                <div class="ab-upload-row">
                  <label for="paymentProof" class="ab-upload-btn">
                    <i class="bi bi-cloud-arrow-up"></i>
                    <span>Upload Receipt</span>
                  </label>
                  <div class="ab-upload-file" id="paymentProofName">No file selected</div>
                </div>
                <input type="file" id="paymentProof" name="payment_proof" accept="image/jpeg,image/png,image/webp" required />
              </div>

              <div class="ab-submit-wrap">
                <button class="ab-btn" type="submit" id="btnSubmitPayment">Submit Payment</button>
              </div>
            </form>
          </div>
        </div>

        <div class="ab-empty ab-hide" id="emptyState">
          <div class="ab-empty-title">No accepted booking</div>
          <div class="ab-empty-sub">Once a provider accepts your request, details will appear here.</div>
          <button class="ab-btn" onclick="goPage('booking_history.php')">Back to bookings</button>
        </div>
      </div>

      <div class="bnav">
        <button class="ni" type="button" onclick="goPage('home.php')">
          <i class="bi bi-house"></i>
          <span class="nl">Home</span>
        </button>
        <button class="ni on" type="button" onclick="goPage('booking_history.php')">
          <i class="bi bi-calendar-event"></i>
          <span class="nl">Bookings</span>
        </button>
        <button class="ni" type="button" onclick="goPage('profile.php')">
          <i class="bi bi-person"></i>
          <span class="nl">Profile</span>
        </button>
      </div>

      <div class="payment-expired-overlay" id="paymentExpiredOverlay" aria-hidden="true">
        <div class="payment-expired-card" role="dialog" aria-modal="true" aria-labelledby="paymentExpiredTitle">
          <div class="payment-expired-head">
            <div class="payment-expired-icon"><i class="bi bi-clock-history"></i></div>
            <div>
              <h3 id="paymentExpiredTitle">Payment Time Expired</h3>
              <p>Your payment session has expired. Please try again or go back to your bookings to rebook.</p>
            </div>
          </div>
          <div class="payment-expired-note">
            <i class="bi bi-info-circle"></i>
            The payment window has closed for this booking.
          </div>
          <div class="payment-expired-actions">
            <button type="button" class="payment-expired-btn primary" id="paymentExpiredPrimary">Back to Bookings</button>
            <button type="button" class="payment-expired-btn secondary" id="paymentExpiredSecondary">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/main.js"></script>
  <script>
    let currentProviderPayment = null;
    let currentPaymentData = null;
    let paymentExpiryTimer = null;
    let paymentExpiryModalShown = false;

    function goPage(page) {
      window.location.href = page;
    }

    function goToTracking() {
      const params = new URLSearchParams(window.location.search);
      const bookingId = params.get('booking_id');
      if (bookingId) {
        goPage('waiting_for_provider.php?booking_id=' + encodeURIComponent(bookingId));
      }
    }

    function formatPrice(val) {
      return '₱' + parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatPaymentMethod(method) {
      const m = String(method || '').toLowerCase();
      if (m === 'gcash') return 'GCash';
      if (m === 'bank') return 'Bank Transfer';
      if (m === 'cash') return 'Cash';
      return method || '-';
    }

    function formatPaymentStatus(status) {
      const s = String(status || '').toLowerCase();
      if (s === 'pending') return 'Awaiting Payment';
      if (s === 'submitted') return 'Awaiting Confirmation';
      if (s === 'completed') return 'Confirmed';
      if (s === 'cancelled') return 'Cancelled';
      return status || '-';
    }

    function formatDeadline(value) {
      if (!value) return '-';
      const d = new Date(String(value).replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return '-';
      return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function getExpiryTimestamp(value) {
      if (!value) return null;
      const d = new Date(String(value).replace(' ', 'T'));
      return Number.isNaN(d.getTime()) ? null : d.getTime();
    }

    function setPaymentModalOpen(open) {
      document.body.classList.toggle('modal-open', !!open);
      document.getElementById('paymentExpiredOverlay').classList.toggle('show', !!open);
      document.getElementById('paymentExpiredOverlay').setAttribute('aria-hidden', open ? 'false' : 'true');
    }

    function goBackToBookings() {
      goPage('booking_history.php');
    }

    function closePaymentExpiredModal() {
      setPaymentModalOpen(false);
    }

    function showPaymentExpiredModal(message) {
      if (paymentExpiryModalShown) return;
      paymentExpiryModalShown = true;
      const overlay = document.getElementById('paymentExpiredOverlay');
      const title = overlay.querySelector('h3');
      const subtitle = overlay.querySelector('p');
      const note = overlay.querySelector('.payment-expired-note');

      title.textContent = 'Payment Time Expired';
      subtitle.textContent = message || 'Your payment session has expired. Please try again or go back to your bookings to rebook.';
      note.innerHTML = '<i class="bi bi-info-circle"></i>The payment window has closed for this booking.';
      setPaymentModalOpen(true);
    }

    function stopPaymentExpiryTimer() {
      if (paymentExpiryTimer) {
        clearInterval(paymentExpiryTimer);
        paymentExpiryTimer = null;
      }
    }

    function startPaymentExpiryTimer(expiryValue) {
      stopPaymentExpiryTimer();
      paymentExpiryModalShown = false;
      const expiryTs = getExpiryTimestamp(expiryValue);
      if (!expiryTs) return;

      const tick = () => {
        if (!currentPaymentData) return;
        const status = String(currentPaymentData.payment_status || '').toLowerCase();
        if (status !== 'pending') {
          stopPaymentExpiryTimer();
          return;
        }

        if (Date.now() >= expiryTs) {
          stopPaymentExpiryTimer();
          showPaymentExpiredModal('The payment session has expired. Please retry or rebook from your booking history.');
        }
      };

      tick();
      paymentExpiryTimer = window.setInterval(tick, 1000);
    }

    function formatSchedule(dateStr, timeStr) {
      if (!dateStr) return '-';
      const d = new Date(dateStr);
      const options = { month: 'short', day: 'numeric', year: 'numeric' };
      return d.toLocaleDateString('en-US', options) + (timeStr ? (' at ' + timeStr) : '');
    }

    function renderStars(rating) {
      let r = parseFloat(rating || 5);
      let html = '';
      for (let i = 1; i <= 5; i++) {
        if (r >= i) {
          html += '<i class="bi bi-star-fill"></i>';
        } else if (r >= i - 0.5) {
          html += '<i class="bi bi-star-half"></i>';
        } else {
          html += '<i class="bi bi-star"></i>';
        }
      }
      return html;
    }

    function showEmptyState() {
      stopPaymentExpiryTimer();
      document.getElementById('providerCard').classList.add('ab-hide');
      document.getElementById('summaryCard').classList.add('ab-hide');
      document.getElementById('paymentCard').classList.add('ab-hide');
      document.getElementById('emptyState').classList.remove('ab-hide');
    }

    async function loadAcceptedBooking() {
      const params = new URLSearchParams(window.location.search);
      const bookingId = params.get('booking_id');
      if (!bookingId) {
        showEmptyState();
        return;
      }

      try {
        const res = await fetch('../api/bookings_api.php?action=accepted_detail&booking_id=' + encodeURIComponent(bookingId));
        const d = await res.json();
        if (!d.success || !d.booking) {
          showEmptyState();
          return;
        }

        const b = d.booking;
        document.getElementById('providerName').textContent = b.provider_name || 'Assigned Provider';
        document.getElementById('providerService').textContent = b.provider_specialty || b.service || 'Worker';
        document.getElementById('providerPhone').textContent = b.provider_phone || 'No contact info';

        const ratingVal = parseFloat(b.provider_rating || 5);
        const jobsDone = parseInt(b.provider_jobs_done || 0);
        const ratingText = ratingVal.toFixed(1) + ' (' + jobsDone + ' jobs)';

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

    function toggleQRDisplay(method) {
      const qrWrapper = document.getElementById('qrContainerWrapper');
      const qrImgDiv = document.getElementById('qrImageDiv');
      const qrText = document.getElementById('qrTextFallback');

      if (method === 'gcash' || method === 'bank') {
        qrWrapper.style.display = 'flex';

        if (method === 'gcash') {
          if (currentProviderPayment && currentProviderPayment.gcash_qr) {
            qrImgDiv.innerHTML = '<img src="../' + currentProviderPayment.gcash_qr + '" alt="GCash QR">';
            qrText.innerHTML = currentProviderPayment.contact_number ? ('GCash No: <b>' + currentProviderPayment.contact_number + '</b>') : '';
          } else {
            qrImgDiv.innerHTML = '<div class="ab-qr-placeholder"><i class="bi bi-qr-code"></i></div>';
            qrText.innerHTML = '<span class="ab-qr-text-error">No GCash QR uploaded by provider.</span>' +
              (currentProviderPayment && currentProviderPayment.contact_number ? '<br>Send payment to: <b>' + currentProviderPayment.contact_number + '</b>' : '');
          }
        } else {
          if (currentProviderPayment && currentProviderPayment.bank_qr) {
            qrImgDiv.innerHTML = '<img src="../' + currentProviderPayment.bank_qr + '" alt="Bank QR">';
            qrText.innerHTML = '';
          } else {
            qrImgDiv.innerHTML = '<div class="ab-qr-placeholder"><i class="bi bi-qr-code"></i></div>';
            qrText.innerHTML = '<span class="ab-qr-text-error">No Bank QR uploaded by provider.</span>';
          }
        }
      } else {
        qrWrapper.style.display = 'none';
        qrImgDiv.innerHTML = '';
        qrText.textContent = '';
      }
    }

    function renderPaymentState(p) {
      currentPaymentData = p;
      const method = String(p.payment_method || '').toLowerCase();
      const status = String(p.payment_status || '').toLowerCase();

      document.getElementById('expectedAmount').textContent = formatPrice(p.amount || 0);
      document.getElementById('paymentMethodLabel').textContent = formatPaymentMethod(method);
      document.getElementById('paymentStatus').textContent = formatPaymentStatus(status);
      document.getElementById('paymentDeadline').textContent = formatDeadline(p.expected_until);

      document.getElementById('paymentForm').classList.add('ab-hide');
      document.getElementById('cashPaymentNote').classList.add('ab-hide');
      document.getElementById('paymentWaitingNote').classList.add('ab-hide');
      document.getElementById('paymentCompletedNote').classList.add('ab-hide');
      closePaymentExpiredModal();

      if (method === 'cash') {
        document.getElementById('cashPaymentNote').classList.remove('ab-hide');
        if (status === 'completed' || status === 'pending') {
          document.getElementById('paymentCompletedNote').classList.remove('ab-hide');
        }
        stopPaymentExpiryTimer();
        return;
      }

      if (status === 'completed') {
        document.getElementById('paymentCompletedNote').classList.remove('ab-hide');
        stopPaymentExpiryTimer();
        return;
      }

      if (status === 'submitted') {
        document.getElementById('paymentWaitingNote').classList.remove('ab-hide');
        stopPaymentExpiryTimer();
        return;
      }

      if (status === 'pending') {
        document.getElementById('paymentMethod').value = method;
        document.getElementById('paymentForm').classList.remove('ab-hide');
        toggleQRDisplay(method);
        startPaymentExpiryTimer(p.expected_until);
      }
    }

    async function loadPaymentDetails() {
      const params = new URLSearchParams(window.location.search);
      const bookingId = params.get('booking_id');
      if (!bookingId) return;

      try {
        const res = await fetch('../api/payments_api.php?action=detail&booking_id=' + encodeURIComponent(bookingId));
        const d = await res.json();
        if (!d.success || !d.payment) {
          if ((d.message || '').toLowerCase().includes('payment time window expired')) {
            showPaymentExpiredModal('The payment session has expired. Please retry or rebook from your booking history.');
          }
          return;
        }

        if (d.provider_payment) {
          currentProviderPayment = d.provider_payment;
        }

        renderPaymentState(d.payment);
      } catch (e) {
        console.warn('Failed to load payment details', e);
      }
    }

    async function submitPayment(e) {
      e.preventDefault();
      const params = new URLSearchParams(window.location.search);
      const bookingId = params.get('booking_id');
      if (!bookingId) return;

      const btn = document.getElementById('btnSubmitPayment');
      btn.disabled = true;
      btn.textContent = 'Submitting...';

      const fd = new FormData(document.getElementById('paymentForm'));
      fd.append('booking_id', bookingId);
      try {
        const res = await fetch('../api/payments_api.php?action=submit', { method: 'POST', body: fd });
        const j = await res.json();
        if (j.success) {
          alert(j.message || 'Payment submitted');
          await loadPaymentDetails();
        } else {
          if ((j.message || '').toLowerCase().includes('payment time window expired')) {
            showPaymentExpiredModal('The payment session has expired. Please retry or rebook from your booking history.');
          } else {
            alert(j.message || 'Submit failed');
          }
        }
      } catch (err) {
        alert('Error submitting payment');
      }
      btn.disabled = false;
      btn.textContent = 'Submit Payment';
    }

    document.getElementById('paymentProof').addEventListener('change', function (e) {
      const name = e.target.files && e.target.files.length ? e.target.files[0].name : 'No file selected';
      document.getElementById('paymentProofName').textContent = name;
    });

    document.getElementById('paymentExpiredPrimary').addEventListener('click', goBackToBookings);
    document.getElementById('paymentExpiredSecondary').addEventListener('click', closePaymentExpiredModal);
    document.getElementById('paymentExpiredOverlay').addEventListener('click', function (e) {
      if (e.target === this) closePaymentExpiredModal();
    });

    loadAcceptedBooking().then(loadPaymentDetails);
  </script>
</body>

</html>

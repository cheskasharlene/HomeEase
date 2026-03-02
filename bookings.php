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
  <title>HomeEase – Book a Service</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/bookings.css">
</head>

<body>
  <div id="toastBox"></div>
  <div class="shell">

    <div class="hdr">
      <div class="hdr-top">
        <div>
          <div class="hdr-sub">Hi, <?= $userName ?> 👋</div>
          <div class="hdr-title">Book a Service</div>
        </div>
        <a href="index.php" class="hdr-btn"><i class="bi bi-arrow-left"></i></a>
      </div>
    </div>

    <div class="scroll">

      <div class="sec">
        <div class="sec-title">Choose a Service</div>
        <div class="svc-grid" id="svcGrid">
          <div class="svc-loading"><i class="bi bi-arrow-clockwise"></i> Loading services...</div>
        </div>
      </div>

      <div class="form-card" id="bookForm">

        <div class="form-title">
          <span id="formSvcLabel">Book Service</span>
          <span id="formSvcIcon" style="font-size:22px;"></span>
        </div>

        <div class="fg" id="pricingSection">
          <label class="fl">Pricing Type</label>
          <div class="price-toggle">
            <div class="pt-opt on" id="ptFlat" onclick="setPricing('flat')">
              <i class="bi bi-tag-fill"></i> Flat Rate
            </div>
            <div class="pt-opt" id="ptHourly" onclick="setPricing('hourly')">
              <i class="bi bi-clock-fill"></i> Hourly
            </div>
          </div>
        </div>

        <div class="fg" id="hoursSection" style="display:none;">
          <label class="fl">Number of Hours</label>
          <div class="stepper">
            <button onclick="changeHours(-1)">−</button>
            <input type="number" id="hoursInput" value="1" min="1" max="12" readonly>
            <button onclick="changeHours(1)">+</button>
          </div>
          <div class="stepper-lbl" id="minHoursNote"></div>
        </div>

        <div class="price-preview">
          <span class="pp-label" id="ppLabel">Flat rate</span>
          <span class="pp-val" id="ppVal">₱0</span>
        </div>

        <div class="fg-row">
          <div class="fg">
            <label class="fl">Date</label>
            <input class="fi" type="date" id="bDate" min="">
          </div>
          <div class="fg">
            <label class="fl">Time Slot</label>
            <select class="fi" id="bTime">
              <option value="8:00 AM">8:00 AM</option>
              <option value="9:00 AM">9:00 AM</option>
              <option value="10:00 AM">10:00 AM</option>
              <option value="11:00 AM">11:00 AM</option>
              <option value="1:00 PM">1:00 PM</option>
              <option value="2:00 PM">2:00 PM</option>
              <option value="3:00 PM">3:00 PM</option>
              <option value="4:00 PM">4:00 PM</option>
            </select>
          </div>
        </div>

        <div class="fg">
          <label class="fl">Service Address</label>
          <input class="fi" type="text" id="bAddr" placeholder="House No., Street, Barangay">
        </div>

        <div class="fg">
          <label class="fl">Notes <span style="font-weight:400;color:var(--tm);">(optional)</span></label>
          <input class="fi" type="text" id="bNotes" placeholder="Any special instructions?">
        </div>

        <div class="tech-section">
          <label class="fl">Choose Technician <span style="font-weight:400;color:var(--tm);">(optional)</span></label>
          <div class="tech-list" id="techList">
            <div class="tech-none"><i class="bi bi-arrow-clockwise"></i> Loading technicians...</div>
          </div>
          <div class="skip-tech" onclick="skipTech()">
            <i class="bi bi-person-x"></i> Skip — assign later
          </div>
        </div>

        <button class="btn-book" id="btnSubmit" onclick="submitBooking()">
          <i class="bi bi-calendar-check"></i> Confirm Booking
        </button>
      </div>

      <div class="sec" style="margin-top:22px;">
        <div class="sec-title">My Bookings</div>
        <div id="myBookings">
          <div class="loader-txt"><i class="bi bi-arrow-clockwise"></i> Loading...</div>
        </div>
      </div>

    </div>
  </div>

  <script>
    let services = [];   
    let selectedSvc = null;  
    let selectedTechId = null;  
    let pricingType = 'flat';
    let hours = 1;

    async function loadServices() {
      try {
        const res = await fetch('api/bookings_api.php?action=services');
        const data = await res.json();
        if (!data.success || !data.services.length) {
          document.getElementById('svcGrid').innerHTML =
            '<div class="svc-loading" style="color:#ef4444;">No services available yet.</div>';
          return;
        }
        services = data.services;
        renderServices();
      } catch (e) {
        document.getElementById('svcGrid').innerHTML =
          '<div class="svc-loading" style="color:#ef4444;"><i class="bi bi-wifi-off"></i> Failed to load services.</div>';
      }
    }

    function renderServices() {
      document.getElementById('svcGrid').innerHTML = services.map((s, i) => `
    <div class="svc-card" id="svcCard${i}" onclick="selectService(${i})">
      <div class="svc-ic">${s.icon}</div>
      <div class="svc-name">${s.name}</div>
      <div class="svc-price">
        ${s.pricing_type === 'hourly'
          ? '₱' + parseFloat(s.hourly_rate).toLocaleString() + '/hr'
          : s.pricing_type === 'both'
            ? 'from ₱' + parseFloat(s.flat_rate).toLocaleString()
            : '₱' + parseFloat(s.flat_rate).toLocaleString()}
      </div>
    </div>
  `).join('');
    }

    function selectService(i) {
      document.querySelectorAll('.svc-card').forEach(c => c.classList.remove('selected'));
      document.getElementById('svcCard' + i).classList.add('selected');
      selectedSvc = services[i];
      selectedTechId = null;
      hours = parseInt(selectedSvc.min_hours) || 1;

      document.getElementById('formSvcLabel').textContent = selectedSvc.name;
      document.getElementById('formSvcIcon').textContent = selectedSvc.icon;

      const pricingSection = document.getElementById('pricingSection');
      if (selectedSvc.pricing_type === 'flat') {
        pricingSection.style.display = 'none';
        setPricing('flat', false);
      } else if (selectedSvc.pricing_type === 'hourly') {
        pricingSection.style.display = 'none';
        setPricing('hourly', false);
      } else {
        pricingSection.style.display = 'block';
        setPricing('flat', false);
      }

      document.getElementById('hoursInput').value = hours;
      document.getElementById('minHoursNote').textContent =
        'Min ' + selectedSvc.min_hours + ' hour(s) · ₱' + parseFloat(selectedSvc.hourly_rate).toLocaleString() + '/hr';

      updatePricePreview();
      loadTechnicians(selectedSvc.name);

      document.getElementById('bookForm').classList.add('show');
      document.getElementById('bookForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function setPricing(type, updatePreview = true) {
      pricingType = type;
      document.getElementById('ptFlat').classList.toggle('on', type === 'flat');
      document.getElementById('ptHourly').classList.toggle('on', type === 'hourly');
      document.getElementById('hoursSection').style.display = type === 'hourly' ? 'block' : 'none';
      if (updatePreview) updatePricePreview();
    }

    function changeHours(delta) {
      const min = parseInt(selectedSvc?.min_hours || 1);
      hours = Math.max(min, Math.min(12, hours + delta));
      document.getElementById('hoursInput').value = hours;
      updatePricePreview();
    }

    function updatePricePreview() {
      if (!selectedSvc) return;
      let price, label;
      if (pricingType === 'hourly') {
        price = parseFloat(selectedSvc.hourly_rate) * hours;
        label = `${hours} hr${hours > 1 ? 's' : ''} × ₱${parseFloat(selectedSvc.hourly_rate).toLocaleString()}/hr`;
      } else {
        price = parseFloat(selectedSvc.flat_rate);
        label = 'Flat rate';
      }
      document.getElementById('ppLabel').textContent = label;
      document.getElementById('ppVal').textContent = '₱' + price.toLocaleString('en-PH', { minimumFractionDigits: 0 });
    }

    function getPrice() {
      if (!selectedSvc) return 0;
      return pricingType === 'hourly'
        ? parseFloat(selectedSvc.hourly_rate) * hours
        : parseFloat(selectedSvc.flat_rate);
    }

    async function loadTechnicians(specialty) {
      document.getElementById('techList').innerHTML =
        '<div class="tech-none"><i class="bi bi-arrow-clockwise"></i> Loading...</div>';
      try {
        const res = await fetch('api/bookings_api.php?action=technicians&specialty=' + encodeURIComponent(specialty));
        const data = await res.json();
        if (!data.success || !data.technicians.length) {
          document.getElementById('techList').innerHTML =
            '<div class="tech-none">No technicians available for this service right now.</div>';
          return;
        }
        renderTechnicians(data.technicians);
      } catch (e) {
        document.getElementById('techList').innerHTML =
          '<div class="tech-none" style="color:#ef4444;">Could not load technicians.</div>';
      }
    }

    function renderTechnicians(techs) {
      document.getElementById('techList').innerHTML = techs.map(t => `
    <div class="tech-card ${t.availability === 'unavailable' ? 'unavailable' : ''} ${selectedTechId == t.id ? 'selected' : ''}"
         id="techCard${t.id}"
         onclick="${t.availability !== 'unavailable' ? `selectTech(${t.id}, this)` : ''}">
      <div class="tech-av">${t.name[0]}</div>
      <div style="flex:1;min-width:0;">
        <div class="tech-name">${t.name}</div>
        <div class="tech-meta">
          <span class="avail-dot ${t.availability}"></span>${t.availability}
          &nbsp;·&nbsp; ⭐ ${t.rating}
          &nbsp;·&nbsp; ${t.jobs_done} jobs
        </div>
      </div>
      <span class="tech-badge ${t.availability}">${t.availability}</span>
    </div>
  `).join('');
    }

    function selectTech(id, el) {
      if (selectedTechId === id) {
        selectedTechId = null;
        el.classList.remove('selected');
        return;
      }
      selectedTechId = id;
      document.querySelectorAll('.tech-card').forEach(c => c.classList.remove('selected'));
      el.classList.add('selected');
    }

    function skipTech() {
      selectedTechId = null;
      document.querySelectorAll('.tech-card').forEach(c => c.classList.remove('selected'));
      toast('Technician will be assigned later', 's');
    }

    async function submitBooking() {
      if (!selectedSvc) { toast('Please select a service first', 'e'); return; }
      const date = document.getElementById('bDate').value;
      const time = document.getElementById('bTime').value;
      const addr = document.getElementById('bAddr').value.trim();
      const notes = document.getElementById('bNotes').value.trim();
      const price = getPrice();

      if (!date) { toast('Please pick a date', 'e'); return; }
      if (!addr) { toast('Please enter your address', 'e'); return; }

      const btn = document.getElementById('btnSubmit');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Booking...';

      const fd = new FormData();
      fd.append('service', selectedSvc.name);
      fd.append('date', date);
      fd.append('time_slot', time);
      fd.append('address', addr);
      fd.append('notes', notes);
      fd.append('pricing_type', pricingType);
      fd.append('hours', hours);
      fd.append('price', price);
      if (selectedTechId) fd.append('technician_id', selectedTechId);

      try {
        const res = await fetch('api/bookings_api.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          toast('Booking confirmed! 🎉', 's');
          document.getElementById('bookForm').classList.remove('show');
          document.querySelectorAll('.svc-card').forEach(c => c.classList.remove('selected'));
          document.getElementById('bDate').value = '';
          document.getElementById('bAddr').value = '';
          document.getElementById('bNotes').value = '';
          selectedSvc = null;
          selectedTechId = null;
          loadMyBookings();
        } else {
          toast(data.message || 'Failed to book. Try again.', 'e');
        }
      } catch (e) {
        toast('Network error — make sure bookings_api.php exists on your server', 'e');
        console.error(e);
      }

      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-calendar-check"></i> Confirm Booking';
    }

    async function loadMyBookings() {
      const el = document.getElementById('myBookings');
      try {
        const res = await fetch('api/bookings_api.php');
        const data = await res.json();

        if (!data.success || !data.bookings || !data.bookings.length) {
          el.innerHTML = `<div class="empty"><i class="bi bi-calendar-x"></i><p>No bookings yet.<br>Book your first service above!</p></div>`;
          return;
        }

        const SVC_IC = {
          'Cleaning': '🧹', 'Plumbing': '🔧', 'Electrical': '⚡',
          'Painting': '🖌️', 'Appliance Repair': '🔩', 'Gardening': '🌿'
        };

        el.innerHTML = data.bookings.map(b => {
          const icon = SVC_IC[b.service] || '🏠';
          const price = parseFloat(b.price).toLocaleString('en-PH', { minimumFractionDigits: 2 });
          const pLabel = b.pricing_type === 'hourly' ? `${b.hours}hr · ₱${price}` : `₱${price} flat`;
          const tech = b.technician_name
            ? `<i class="bi bi-person-badge-fill" style="color:var(--teal);"></i> ${b.technician_name} ⭐${b.tech_rating}`
            : `<i class="bi bi-person-dash" style="color:var(--tm);"></i> No technician assigned`;
          return `
        <div class="bk-card">
          <div class="bk-top">
            <div class="bk-ic">${icon}</div>
            <div style="flex:1;min-width:0;">
              <div class="bk-svc">${b.service}</div>
              <div class="bk-meta">
                <i class="bi bi-calendar3" style="font-size:9px;"></i> ${b.date} · ${b.time_slot || '—'}<br>
                <i class="bi bi-geo-alt" style="font-size:9px;"></i> ${b.address}
              </div>
            </div>
            <div class="bk-right">
              <div class="bk-price">₱${price}</div>
              <span class="pill ${b.status}">${b.status}</span>
            </div>
          </div>
          <div class="bk-footer">
            ${tech}
            &nbsp;·&nbsp; ${pLabel}
          </div>
          ${b.notes ? `<div style="margin-top:6px;font-size:11px;color:var(--tm);">📝 ${b.notes}</div>` : ''}
        </div>`;
        }).join('');
      } catch (e) {
        el.innerHTML = `<div class="empty"><i class="bi bi-wifi-off"></i><p>Could not load bookings.</p></div>`;
      }
    }

    function toast(msg, type = 's') {
      const t = document.createElement('div');
      t.className = `toast-n ${type}`;
      t.innerHTML = `<i class="bi bi-${type === 's' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>${msg}`;
      document.getElementById('toastBox').appendChild(t);
      setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
    }

    document.getElementById('bDate').min = new Date().toISOString().split('T')[0];
    loadServices();
    loadMyBookings();
  </script>
</body>

</html>
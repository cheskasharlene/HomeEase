<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

// Check if service is pre-selected
$serviceName = isset($_GET['svc']) ? trim($_GET['svc']) : '';
if (!$serviceName) {
  // No service selected, redirect to service selection
  header('Location: service_selection.php');
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
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/bookings.css">
  <style>
    .scroll {
      padding-bottom: 96px;
    }

    .fixed-total-wrap {
      position: static;
      background: #FFF8EE;
      border: 1px solid #F3DFC2;
      border-radius: 16px;
      padding: 18px;
      box-shadow: 0 3px 10px rgba(232, 130, 12, 0.08);
      margin-top: 20px;
      margin-bottom: 14px;
    }

    .fixed-total-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-family: "Poppins", sans-serif;
      color: #1A1A2E;
      font-size: 14px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .fixed-total-value {
      color: #E8820C;
      font-size: 24px;
      font-weight: 800;
      letter-spacing: -0.2px;
    }

    .fixed-total-note {
      font-size: 11px;
      line-height: 1.35;
      color: #7A7064;
      font-weight: 600;
    }

    .opt-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-top: 6px;
    }

    .opt-chip {
      border: 1px solid #EEDBC0;
      background: #FFF8F0;
      border-radius: 10px;
      padding: 8px 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: #5E564D;
      font-weight: 700;
    }

    .opt-chip input {
      accent-color: #E8820C;
      width: 15px;
      height: 15px;
      margin: 0;
    }

    .bnav {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: #fff !important;
      border-top: 1px solid #EDE8E0 !important;
      display: flex;
      padding: 9px 0 calc(12px + env(safe-area-inset-bottom));
      box-shadow: 0 -4px 20px rgba(232, 130, 12, .07);
      z-index: 50;
    }

    .ni {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      cursor: pointer;
      color: #C5BEB3;
      font-family: "Nunito", sans-serif;
      padding: 2px 0;
    }

    .ni i {
      font-size: 22px;
    }

    .ni.on,
    .ni.on i,
    .ni.on .nl {
      color: #F5A623;
    }

    .nl {
      font-size: 10px;
      font-weight: 700;
    }

    .nb-c {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 22px;
      margin-top: -22px;
      box-shadow: 0 6px 20px rgba(232, 130, 12, .45);
    }
  </style>
</head>

<body>
  <div id="toastBox"></div>
  <div class="shell">

    <div class="hdr">
      <div class="hdr-top">
        <div>
          <div class="hdr-sub">Hi, <?= $userName ?> 👋</div>
          <div class="hdr-title">Booking Details</div>
        </div>
        <a href="service_selection.php" class="hdr-btn"><i class="bi bi-arrow-left"></i></a>
      </div>
    </div>

    <div class="scroll">

      <div class="form-card" id="bookForm" style="display: block; margin-top: 18px;">

        <div class="form-title">
          <span id="formSvcLabel">Select Service</span>
          <span id="formSvcIcon" style="font-size:22px;"></span>
        </div>

        <div class="fg" style="margin-bottom:14px;">
          <label class="fl" style="font-family:'Poppins',sans-serif;font-size:13px;">Your Information</label>
          <div class="fg-row">
            <div class="fg" style="margin-bottom:0;">
              <label class="fl">Full Name</label>
              <input class="fi" type="text" id="uName" placeholder="Your full name">
            </div>
            <div class="fg" style="margin-bottom:0;">
              <label class="fl">Phone Number</label>
              <input class="fi" type="text" id="uPhone" placeholder="09XXXXXXXXX">
            </div>
          </div>
          <div class="fg" style="margin-bottom:0;margin-top:10px;">
            <label class="fl">Address</label>
            <input class="fi" type="text" id="uAddress" placeholder="House No., Street, Barangay">
          </div>
        </div>

        <!-- Dynamic service-specific fields -->
        <div id="serviceSpecificFields"></div>

        <div class="fg" id="pricingSection" style="display:none;">
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

        <div class="price-preview" style="display:none;">
          <span class="pp-label" id="ppLabel">Flat rate</span>
          <span class="pp-val" id="ppValLegacy">₱0</span>
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

        <div class="fg" style="display:none;">
          <label class="fl">Service Address</label>
          <input class="fi" type="text" id="bAddr" placeholder="House No., Street, Barangay">
        </div>

        <div class="fg">
          <label class="fl">Notes <span style="font-weight:400;color:var(--tm);">(optional)</span></label>
          <input class="fi" type="text" id="bNotes" placeholder="Any special instructions?">
        </div>

        <div class="tech-section" style="display:none;">
          <label class="fl">Choose Technician <span style="font-weight:400;color:var(--tm);">(optional)</span></label>
          <div class="tech-list" id="techList">
            <div class="tech-none"><i class="bi bi-arrow-clockwise"></i> Loading technicians...</div>
          </div>
          <div class="skip-tech" onclick="skipTech()">
            <i class="bi bi-person-x"></i> Skip — assign later
          </div>
        </div>

        <div class="fixed-total-wrap">
          <div class="fixed-total-row">
            <span>Total Price</span>
            <span class="fixed-total-value" id="fixedTotalVal">₱0</span>
          </div>
          <div class="fixed-total-note">This is a fixed system-generated price (no hidden charges).</div>
        </div>

        <button class="btn-book" id="btnSubmit" onclick="submitBooking()">
          <i class="bi bi-calendar-check"></i> Confirm Booking
        </button>
      </div>

    </div>

    <div class="bnav">
      <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
      <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
      <div class="ni on" onclick="goPage('service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
      <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
      <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    initTheme();
  </script>
  <script>
    window.HE = window.HE || {};
    window.HE.user = {
      name: <?= json_encode($_SESSION['user_name'] ?? '') ?>,
      phone: <?= json_encode($_SESSION['user_phone'] ?? '') ?>,
      address: <?= json_encode($_SESSION['user_address'] ?? '') ?>
    };

    // Service-specific dynamic fields and pricing rules
    const serviceFields = {
      'Cleaner': [
        { name: 'cleaning_type', label: 'Cleaning Type', type: 'select', options: ['General', 'Deep Cleaning', 'Move-in/out'] },
        { name: 'property_type', label: 'Property Type', type: 'select', options: ['Condo/Apartment', 'House'] },
        { name: 'num_rooms', label: 'Number of Rooms', type: 'number', min: 1, max: 10 },
        { name: 'num_bathrooms', label: 'Number of Bathrooms', type: 'number', min: 1, max: 5 },
        { name: 'inclusions_note', label: 'Additional Notes', type: 'text', placeholder: 'e.g., windows, carpets, etc.' }
      ],
      'Plumber': [
        { name: 'issue_type', label: 'Issue Type', type: 'select', options: ['Leak', 'Clog', 'Installation'] },
        { name: 'issue_location', label: 'Location', type: 'select', options: ['Kitchen', 'Bathroom', 'Outdoor'] },
        { name: 'urgency', label: 'Urgency', type: 'select', options: ['Normal', 'Urgent'] }
      ],
      'Laundry Worker': [
        { name: 'laundry_options', label: 'Laundry Options', type: 'checkbox-group', options: ['Wash', 'Fold', 'Iron'], defaultChecked: ['Wash'] },
        { name: 'load_size', label: 'Load Size', type: 'select', options: ['Small', 'Medium', 'Large'] },
        { name: 'pickup_delivery', label: 'Pickup & Delivery?', type: 'select', options: ['No', 'Yes'] },
        { name: 'pickup_address', label: 'Pickup Address (if needed)', type: 'text', placeholder: 'Leave blank if not needed' }
      ],
      'Helper': [
        { name: 'duration', label: 'Duration', type: 'select', options: ['Half Day', 'Full Day'] },
        { name: 'helper_tasks', label: 'Tasks', type: 'checkbox-group', options: ['Cleaning', 'Cooking', 'Childcare', 'Errands'] },
        { name: 'tasks_needed', label: 'Extra Notes', type: 'textarea', placeholder: 'Optional details for the helper' }
      ],
      'Carpenter': [
        { name: 'work_type', label: 'Work Type', type: 'select', options: ['Repair', 'Custom', 'Installation'] },
        { name: 'material_type', label: 'Material Type', type: 'select', options: ['Standard', 'Premium'] },
        { name: 'size', label: 'Size', type: 'select', options: ['Small', 'Medium', 'Large'] },
        { name: 'dimensions', label: 'Dimensions / Notes', type: 'text', placeholder: 'e.g., 5ft x 3ft' }
      ],
      'Appliance Technician': [
        { name: 'appliance_type', label: 'Appliance Type', type: 'select', options: ['Aircon', 'Ref', 'Washing Machine', 'TV'] },
        { name: 'problem_severity', label: 'Problem Severity', type: 'select', options: ['Minor', 'Major'] },
        { name: 'urgency_level', label: 'Urgency', type: 'select', options: ['Normal', 'Urgent'] },
        { name: 'problem_desc', label: 'Problem Description', type: 'textarea', placeholder: 'Describe the issue in detail' },
        { name: 'brand_model', label: 'Brand/Model (optional)', type: 'text', placeholder: 'e.g., Samsung WM5000' },
        { name: 'media_upload', label: 'Upload Photo/Video', type: 'file', accept: 'image/*,video/*' }
      ]
    };

    let services = [];
    let selectedSvc = null;
    let selectedTechId = null;
    let pricingType = 'flat';
    let hours = 1;
    const preselectedSvc = <?= json_encode($serviceName) ?>;

    async function initForm() {
      // Populate user info
      document.getElementById('uName').value = window.HE.user.name || '';
      document.getElementById('uPhone').value = window.HE.user.phone || '';
      document.getElementById('uAddress').value = window.HE.user.address || '';
      document.getElementById('bAddr').value = window.HE.user.address || '';

      try {
        const res = await fetch('api/bookings_api.php?action=services');
        const data = await res.json();
        if (!data.success || !data.services.length) {
          document.getElementById('formSvcLabel').textContent = 'Service not found';
          return;
        }
        services = data.services;

        // Find service by pre-selected name
        const idx = services.findIndex(s => String(s.name || '').trim() === preselectedSvc.trim());
        if (idx >= 0) {
          selectService(idx);
        } else {
          document.getElementById('formSvcLabel').textContent = 'Service not found';
        }
      } catch (e) {
        document.getElementById('formSvcLabel').textContent = 'Failed to load service';
        console.error(e);
      }
    }

    function renderServices() {
      // Removed - service selection now happens on separate page
    }

    function selectService(i) {
      selectedSvc = services[i];
      selectedTechId = null;
      pricingType = 'flat';
      hours = 1;

      document.getElementById('formSvcLabel').textContent = selectedSvc.name;
      document.getElementById('formSvcIcon').textContent = selectedSvc.icon;

      // Render dynamic fields for this service
      renderDynamicFields(selectedSvc.name);

      updatePricePreview();
    }

    function renderDynamicFields(serviceName) {
      const fieldsContainer = document.getElementById('serviceSpecificFields');
      const fields = serviceFields[serviceName] || [];

      if (!fields.length) {
        fieldsContainer.innerHTML = '';
        return;
      }

      fieldsContainer.innerHTML = fields.map(field => {
        if (field.type === 'select') {
          return `
            <div class="fg">
              <label class="fl">${field.label}</label>
              <select class="fi" id="field_${field.name}">
                <option value="">Select an option...</option>
                ${field.options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
              </select>
            </div>
          `;
        } else if (field.type === 'textarea') {
          return `
            <div class="fg">
              <label class="fl">${field.label}</label>
              <textarea class="fi" id="field_${field.name}" placeholder="${field.placeholder || ''}" rows="3" style="resize: vertical; min-height: 70px;"></textarea>
            </div>
          `;
        } else if (field.type === 'number') {
          return `
            <div class="fg">
              <label class="fl">${field.label}</label>
              <input class="fi calc-input" id="field_${field.name}" type="number" min="${field.min}" max="${field.max}" value="1">
            </div>
          `;
        } else if (field.type === 'checkbox-group') {
          return `
            <div class="fg">
              <label class="fl">${field.label}</label>
              <div class="opt-grid">
                ${field.options.map(opt => {
                  const safe = opt.replace(/[^a-zA-Z0-9]/g, '_');
                  const checked = (field.defaultChecked || []).includes(opt) ? 'checked' : '';
                  return `<label class="opt-chip"><input class="calc-input" type="checkbox" id="field_${field.name}_${safe}" name="field_${field.name}" value="${opt}" ${checked}>${opt}</label>`;
                }).join('')}
              </div>
            </div>
          `;
        } else if (field.type === 'file') {
          return `
            <div class="fg">
              <label class="fl">${field.label}</label>
              <input class="fi" id="field_${field.name}" type="file" accept="${field.accept || ''}">
            </div>
          `;
        } else {
          return `
            <div class="fg">
              <label class="fl">${field.label}</label>
              <input class="fi calc-input" id="field_${field.name}" type="text" placeholder="${field.placeholder || ''}">
            </div>
          `;
        }
      }).join('');

      attachPriceListeners();
    }

    function goBack() {
      window.location.href = 'service_selection.php';
    }

    function goPage(page) {
      window.location.href = page;
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

    function normalizeNumber(value, fallback = 0) {
      const n = parseInt(value, 10);
      if (Number.isNaN(n)) return fallback;
      return n;
    }

    function collectSelections() {
      const selections = {};
      const fields = serviceFields[selectedSvc?.name] || [];
      fields.forEach(field => {
        if (field.type === 'checkbox-group') {
          const checks = Array.from(document.querySelectorAll(`input[name="field_${field.name}"]:checked`)).map(el => el.value);
          selections[field.name] = checks;
          return;
        }
        const input = document.getElementById(`field_${field.name}`);
        selections[field.name] = input ? String(input.value || '').trim() : '';
      });
      return selections;
    }

    function computePriceAndSummary() {
      if (!selectedSvc) return { total: 0, summaryLines: [] };

      const v = collectSelections();
      let total = 0;
      const lines = [];

      if (selectedSvc.name === 'Cleaner') {
        total = 500;
        lines.push('Base cleaning: ₱500');

        const cleanTypeAdd = { 'General': 0, 'Deep Cleaning': 500, 'Move-in/out': 700 };
        const propertyAdd = { 'Condo/Apartment': 0, 'House': 200 };
        const rooms = Math.max(0, normalizeNumber(v.num_rooms, 1));
        const baths = Math.max(0, normalizeNumber(v.num_bathrooms, 1));

        const cAdd = cleanTypeAdd[v.cleaning_type] ?? 0;
        const pAdd = propertyAdd[v.property_type] ?? 0;
        const rAdd = rooms * 100;
        const bAdd = baths * 150;

        total += cAdd + pAdd + rAdd + bAdd;
        lines.push(`Cleaning type: +₱${cAdd}`);
        lines.push(`Property: +₱${pAdd}`);
        lines.push(`Rooms (${rooms}): +₱${rAdd}`);
        lines.push(`Bathrooms (${baths}): +₱${bAdd}`);
      } else if (selectedSvc.name === 'Laundry Worker') {
        total = 300;
        lines.push('Base laundry: ₱300');

        const selectedOpts = Array.isArray(v.laundry_options) ? v.laundry_options : [];
        const optAdd = selectedOpts.reduce((sum, opt) => sum + ({ 'Wash': 0, 'Fold': 100, 'Iron': 200 }[opt] ?? 0), 0);
        const sizeAdd = ({ 'Small': 0, 'Medium': 100, 'Large': 200 }[v.load_size] ?? 0);
        const pickupAdd = v.pickup_delivery === 'Yes' ? 100 : 0;

        total += optAdd + sizeAdd + pickupAdd;
        lines.push(`Options (${selectedOpts.join(', ') || 'None'}): +₱${optAdd}`);
        lines.push(`Load size: +₱${sizeAdd}`);
        lines.push(`Pickup/Delivery: +₱${pickupAdd}`);
      } else if (selectedSvc.name === 'Helper') {
        total = 500;
        lines.push('Base helper (Half Day): ₱500');

        const durAdd = v.duration === 'Full Day' ? 400 : 0;
        const tasks = Array.isArray(v.helper_tasks) ? v.helper_tasks : [];
        const taskAdd = tasks.reduce((sum, t) => sum + ({ 'Cleaning': 100, 'Cooking': 150, 'Childcare': 200, 'Errands': 100 }[t] ?? 0), 0);

        total += durAdd + taskAdd;
        lines.push(`Duration: +₱${durAdd}`);
        lines.push(`Tasks (${tasks.join(', ') || 'None'}): +₱${taskAdd}`);
      } else if (selectedSvc.name === 'Plumber') {
        total = 500;
        lines.push('Base plumbing: ₱500');

        const issueAdd = ({ 'Leak': 300, 'Clog': 300, 'Installation': 800 }[v.issue_type] ?? 0);
        const locAdd = ({ 'Kitchen': 0, 'Bathroom': 100, 'Outdoor': 150 }[v.issue_location] ?? 0);
        const urgAdd = ({ 'Normal': 0, 'Urgent': 300 }[v.urgency] ?? 0);

        total += issueAdd + locAdd + urgAdd;
        lines.push(`Issue type: +₱${issueAdd}`);
        lines.push(`Location: +₱${locAdd}`);
        lines.push(`Urgency: +₱${urgAdd}`);
      } else if (selectedSvc.name === 'Carpenter') {
        total = 700;
        lines.push('Base carpenter: ₱700');

        const workAdd = ({ 'Repair': 300, 'Custom': 800, 'Installation': 500 }[v.work_type] ?? 0);
        const matAdd = ({ 'Standard': 0, 'Premium': 300 }[v.material_type] ?? 0);
        const sizeAdd = ({ 'Small': 0, 'Medium': 300, 'Large': 700 }[v.size] ?? 0);

        total += workAdd + matAdd + sizeAdd;
        lines.push(`Work type: +₱${workAdd}`);
        lines.push(`Material: +₱${matAdd}`);
        lines.push(`Size: +₱${sizeAdd}`);
      } else if (selectedSvc.name === 'Appliance Technician') {
        total = 500;
        lines.push('Base technician: ₱500');

        const appAdd = ({ 'Aircon': 500, 'Ref': 400, 'Washing Machine': 400, 'TV': 300 }[v.appliance_type] ?? 0);
        const sevAdd = ({ 'Minor': 300, 'Major': 800 }[v.problem_severity] ?? 0);
        const urgAdd = ({ 'Normal': 0, 'Urgent': 300 }[v.urgency_level] ?? 0);

        total += appAdd + sevAdd + urgAdd;
        lines.push(`Appliance: +₱${appAdd}`);
        lines.push(`Severity: +₱${sevAdd}`);
        lines.push(`Urgency: +₱${urgAdd}`);
      }

      return { total, summaryLines: lines };
    }

    function updatePricePreview() {
      const calc = computePriceAndSummary();
      const formatted = '₱' + calc.total.toLocaleString('en-PH', { minimumFractionDigits: 0 });
      document.getElementById('fixedTotalVal').textContent = formatted;
    }

    function getPrice() {
      return computePriceAndSummary().total;
    }

    function attachPriceListeners() {
      const inputs = document.querySelectorAll('#serviceSpecificFields .calc-input, #serviceSpecificFields select');
      inputs.forEach(input => {
        input.addEventListener('change', updatePricePreview);
        input.addEventListener('input', updatePricePreview);
      });
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

    function getDynamicFieldsData() {
      const data = {};
      const fields = serviceFields[selectedSvc?.name] || [];
      fields.forEach(field => {
        if (field.type === 'checkbox-group') {
          data[field.name] = Array.from(document.querySelectorAll(`input[name="field_${field.name}"]:checked`)).map(el => el.value).join(',');
          return;
        }
        const input = document.getElementById(`field_${field.name}`);
        if (input) {
          data[field.name] = input.value || '';
        }
      });
      return data;
    }

    function buildConfirmationMessage(price) {
      const selections = collectSelections();
      const lines = [`Service: ${selectedSvc.name}`];

      Object.keys(selections).forEach(key => {
        const val = selections[key];
        if (Array.isArray(val)) {
          lines.push(`${key.replaceAll('_', ' ')}: ${val.join(', ') || 'None'}`);
        } else if (String(val).trim() !== '') {
          lines.push(`${key.replaceAll('_', ' ')}: ${val}`);
        }
      });

      lines.push(`Final Price: ₱${price.toLocaleString('en-PH')}`);
      lines.push('This is a fixed system-generated price (no additional charges).');
      return lines.join('\n');
    }

    async function submitBooking() {
      if (!selectedSvc) { toast('Please select a service first', 'e'); return; }
      const date = document.getElementById('bDate').value;
      const time = document.getElementById('bTime').value;
      const uName = document.getElementById('uName').value.trim();
      const uPhone = document.getElementById('uPhone').value.trim();
      const addr = document.getElementById('uAddress').value.trim();
      const notes = document.getElementById('bNotes').value.trim();
      const price = getPrice();

      if (!date) { toast('Please pick a date', 'e'); return; }
      if (!uName) { toast('Please enter your full name', 'e'); return; }
      if (!uPhone) { toast('Please enter your phone number', 'e'); return; }
      if (!addr) { toast('Please enter your address', 'e'); return; }

      const ok = window.confirm(buildConfirmationMessage(price));
      if (!ok) return;

      const btn = document.getElementById('btnSubmit');
      btn.disabled = true;
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Booking...';

      const fd = new FormData();
      fd.append('service', selectedSvc.name);
      fd.append('date', date);
      fd.append('time_slot', time);
      fd.append('address', addr);
      fd.append('notes', notes);
      fd.append('pricing_type', 'flat');
      fd.append('hours', 1);
      fd.append('computed_price_client', price);
      fd.append('customer_name', uName);
      fd.append('customer_phone', uPhone);
      fd.append('customer_address', addr);

      // Add dynamic field data
      const dynamicData = getDynamicFieldsData();
      Object.keys(dynamicData).forEach(key => {
        fd.append(key, dynamicData[key]);
      });

      try {
        const res = await fetch('api/bookings_api.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          toast(data.waiting_message || 'Waiting for a provider to accept your booking…', 's');
          document.getElementById('bookForm').classList.remove('show');
          document.getElementById('bDate').value = '';
          document.getElementById('uAddress').value = window.HE.user.address || '';
          document.getElementById('bAddr').value = window.HE.user.address || '';
          document.getElementById('bNotes').value = '';
          selectedSvc = null;
          selectedTechId = null;
          setTimeout(() => goPage('booking_history.php'), 450);
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

    function toast(msg, type = 's') {
      const t = document.createElement('div');
      t.className = `toast-n ${type}`;
      t.innerHTML = `<i class="bi bi-${type === 's' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>${msg}`;
      document.getElementById('toastBox').appendChild(t);
      setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 3000);
    }

    document.getElementById('bDate').min = new Date().toISOString().split('T')[0];
    initForm();
  </script>
</body>

</html>
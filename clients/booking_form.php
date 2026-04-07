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
  <link rel="stylesheet" href="../assets/css/main.css">
  <link rel="stylesheet" href="../assets/css/bookings.css">
  <link rel="stylesheet" href="../assets/css/booking_form.css">
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
          <label class="fl">Choose Professional <span style="font-weight:400;color:var(--tm);">(optional)</span></label>
          <div class="tech-list" id="techList">
            <div class="tech-none"><i class="bi bi-arrow-clockwise"></i> Loading providers...</div>
          </div>
          <div class="skip-tech" onclick="skipTech()">
            <i class="bi bi-person-x"></i> Skip — assign later
          </div>
        </div>

        <div class="fixed-total-wrap">
          <div class="fixed-total-row">
            <span><i class="bi bi-receipt" style="margin-right:4px;opacity:.6;"></i>Total Price</span>
            <span class="fixed-total-value" id="fixedTotalVal">₱0</span>
          </div>
          <div class="fixed-total-note"><i class="bi bi-shield-check" style="color:#10b981;"></i> Fixed system-generated
            price — no hidden charges.</div>
        </div>

        <button class="btn-book" id="btnSubmit" onclick="submitBooking()"
          style="height:52px;font-size:15px;border-radius:16px;">
          <i class="bi bi-calendar-check"></i> Confirm Booking
        </button>
      </div>

    </div>

    <div class="bnav">
      <div class="ni" onclick="goPage('../home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
      <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span
          class="nl">Bookings</span></div>
      <div class="ni on" onclick="goPage('service_selection.php')">
        <div class="nb-c"><i class="bi bi-plus-lg"></i></div>
      </div>
      <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span
          class="nl">Notifications</span></div>
      <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span>
      </div>
    </div>
  </div>

  <div class="tech-modal-overlay" id="techModalOverlay" onclick="closeTechInfo()">
    <div class="tech-modal-card" onclick="event.stopPropagation()">
      <div class="tech-modal-close" onclick="closeTechInfo()"><i class="bi bi-x"></i></div>
      <div style="display:flex; align-items:center; gap:16px; margin-bottom: 20px;">
        <div class="tech-av" id="tmAv" style="width:60px; height:60px; font-size:24px;"></div>
        <div>
          <div style="font-size:18px; font-weight:700; color:#1A1A2E;" id="tmName"></div>
          <div style="font-size:13px; color:#E8820C; font-weight:600;" id="tmSpec"></div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px;">
        <div
          style="background:linear-gradient(135deg,#FEF9C3,#FEF3C7); padding:14px; border-radius:14px; text-align:center;">
          <div style="font-size:20px; font-weight:800; color:#92400E; font-family:'Poppins',sans-serif;" id="tmRating">
          </div>
          <div style="font-size:10px; color:#A16207; text-transform:uppercase; font-weight:800; letter-spacing:.3px;">
            Rating</div>
        </div>
        <div
          style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5); padding:14px; border-radius:14px; text-align:center;">
          <div style="font-size:20px; font-weight:800; color:#047857; font-family:'Poppins',sans-serif;" id="tmJobs">
          </div>
          <div style="font-size:10px; color:#059669; text-transform:uppercase; font-weight:800; letter-spacing:.3px;">
            Jobs Done</div>
        </div>
      </div>

      <div style="font-size:14px; color:#5E564D; line-height:1.6; margin-bottom:16px;">
        <div style="margin-bottom:8px;"><i class="bi bi-geo-alt-fill" style="color:#C5BEB3; margin-right:6px;"></i>
          <span id="tmLocation"></span>
        </div>
        <div style="margin-bottom:8px;"><i class="bi bi-telephone-fill" style="color:#C5BEB3; margin-right:6px;"></i>
          <span id="tmPhone"></span>
        </div>
        <div style="margin-bottom:8px;"><i class="bi bi-clock-fill" style="color:#C5BEB3; margin-right:6px;"></i> <span
            id="tmAvail"></span></div>
      </div>

      <div style="margin-top:20px; border-top: 1px dashed #EDE8E0; padding-top: 16px;">
        <div style="font-size:14px; font-weight:800; color:#1A1A2E; margin-bottom: 12px;">Recent Reviews</div>
        <div id="tmReviewsContainer" style="max-height: 180px; overflow-y: auto;">
          <div style="font-size:12px; color:#7A7064; text-align:center; padding:10px;">Loading reviews...</div>
        </div>
      </div>

      <button class="btn-book" style="margin-top:20px; width:100%; border-radius:12px; height:48px;" id="tmSelectBtn">
        Select Professional
      </button>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
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
      'Helper': [
        { name: 'helper_tasks', label: 'Tasks Needed', type: 'checkbox-group', options: ['Cleaning', 'Cooking', 'Childcare', 'General Errands'], defaultChecked: ['Cleaning'] },
        { name: 'helper_hours', label: 'Number of Hours', type: 'number', min: 4, max: 12 }
      ],
      'Laundry Worker': [
        { name: 'laundry_services', label: 'Services', type: 'checkbox-group', options: ['Wash & Dry', 'Fold', 'Iron'], defaultChecked: ['Wash & Dry'] },
        { name: 'laundry_kilos', label: 'Laundry Load', type: 'select', options: ['Under 5kg', '5-10kg', 'Over 10kg'] }
      ],
      'Plumber': [
        { name: 'issue_type', label: 'Issue Type', type: 'select', options: ['Leak', 'Clog', 'Installation'] },
        { name: 'issue_location', label: 'Location', type: 'select', options: ['Kitchen', 'Bathroom', 'Outdoor'] },
        { name: 'urgency', label: 'Urgency', type: 'select', options: ['Normal', 'Urgent'] }
      ],
      'Carpenter': [
        { name: 'carpentry_task', label: 'Task', type: 'select', options: ['Repairs', 'Furniture Making', 'Installation'] },
        { name: 'complexity', label: 'Complexity', type: 'select', options: ['Simple', 'Complex'] },
        { name: 'description', label: 'Description', type: 'textarea', placeholder: 'Describe the woodwork needed...' }
      ],
      'Appliance Technician': [
        { name: 'appliance_type', label: 'Appliance Type', type: 'select', options: ['Aircon', 'Ref', 'Washing Machine', 'TV', 'Other'] },
        { name: 'problem_severity', label: 'Problem Severity', type: 'select', options: ['Minor', 'Major'] },
        { name: 'urgency_level', label: 'Urgency', type: 'select', options: ['Normal', 'Urgent'] },
        { name: 'problem_desc', label: 'Problem Description', type: 'textarea', placeholder: 'Describe the issue in detail' }
      ]
    };

    let services = [];
    let selectedSvc = null;
    let selectedTechId = null;
    let loadedTechs = [];
    let pricingType = 'flat';
    let hours = 1;
    const preselectedSvc = <?= json_encode($serviceName) ?>;

    // Map display names (used in home.php/app.js) to actual DB service names
    const svcNameAliases = {
      'Cleaning': 'Cleaner',
      'Plumbing': 'Plumber',
      'Laundry': 'Laundry Worker',
      'Carpentry': 'Carpenter',
      'Helper': 'Helper',
      'Appliance Technician': 'Appliance Technician',
    };

    async function initForm() {
      // Populate user info
      document.getElementById('uName').value = window.HE.user.name || '';
      document.getElementById('uPhone').value = window.HE.user.phone || '';
      document.getElementById('uAddress').value = window.HE.user.address || '';
      document.getElementById('bAddr').value = window.HE.user.address || '';

      try {
        const res = await fetch('../api/bookings_api.php?action=services');
        const data = await res.json();
        if (!data.success || !data.services.length) {
          document.getElementById('formSvcLabel').textContent = 'Service not found';
          return;
        }
        services = data.services;

        // Resolve alias (e.g. "Plumbing" → "Plumber") then match against DB names
        const resolvedName = (svcNameAliases[preselectedSvc.trim()] || preselectedSvc).trim();
        const idx = services.findIndex(s => String(s.name || '').trim() === resolvedName);
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
      document.querySelector('.tech-section').style.display = 'block';
      loadTechnicians(selectedSvc.name);
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
      } else if (selectedSvc.name === 'Helper') {
        total = 400;
        lines.push('Base helper: ₱400 (4 hours)');

        const tasks = Array.isArray(v.helper_tasks) ? v.helper_tasks : [];
        const taskAdd = tasks.reduce((sum, t) => sum + ({ 'Cleaning': 100, 'Cooking': 150, 'Childcare': 200, 'General Errands': 100 }[t] ?? 0), 0);
        const hours = Math.max(1, normalizeNumber(v.helper_hours, 4));
        const excess = (hours > 4) ? ((hours - 4) * 100) : 0;

        total += taskAdd + excess;
        lines.push(`Tasks (${tasks.join(', ') || 'None'}): +₱${taskAdd}`);
        if (excess > 0) lines.push(`Excess Hours (+${hours - 4}): +₱${excess}`);
      } else if (selectedSvc.name === 'Laundry Worker') {
        total = 300;
        lines.push('Base laundry: ₱300');

        const tasks = Array.isArray(v.laundry_services) ? v.laundry_services : [];
        const taskAdd = tasks.reduce((sum, t) => sum + ({ 'Wash & Dry': 100, 'Fold': 100, 'Iron': 150 }[t] ?? 0), 0);
        const kiloAdd = ({ 'Under 5kg': 0, '5-10kg': 200, 'Over 10kg': 400 }[v.laundry_kilos] ?? 0);

        total += taskAdd + kiloAdd;
        lines.push(`Services (${tasks.join(', ') || 'None'}): +₱${taskAdd}`);
        lines.push(`Load size: +₱${kiloAdd}`);
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
        total = 600;
        lines.push('Base carpentry: ₱600');

        const typeAdd = ({ 'Repairs': 0, 'Furniture Making': 500, 'Installation': 300 }[v.carpentry_task] ?? 0);
        const prepAdd = ({ 'Simple': 0, 'Complex': 500 }[v.complexity] ?? 0);

        total += typeAdd + prepAdd;
        lines.push(`Task: +₱${typeAdd}`);
        lines.push(`Complexity: +₱${prepAdd}`);
      } else if (selectedSvc.name === 'Appliance Technician') {
        total = 500;
        lines.push('Base repair: ₱500');

        const appAdd = ({ 'Aircon': 500, 'Ref': 400, 'Washing Machine': 400, 'TV': 300, 'Other': 200 }[v.appliance_type] ?? 0);
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
        const res = await fetch('../api/bookings_api.php?action=technicians&specialty=' + encodeURIComponent(specialty));
        const data = await res.json();
        if (!data.success || !data.technicians.length) {
          document.getElementById('techList').innerHTML =
            '<div class="tech-none">No providers available for this service right now.</div>';
          return;
        }
        loadedTechs = data.technicians;
        renderTechnicians(loadedTechs);
      } catch (e) {
        document.getElementById('techList').innerHTML =
          '<div class="tech-none" style="color:#ef4444;">Could not load providers.</div>';
      }
    }

    function renderTechnicians(techs) {
      document.getElementById('techList').innerHTML = techs.map(t => {
        const ratingDisplay = (t.rating && parseFloat(t.rating) > 0) ? `⭐ ${parseFloat(t.rating).toFixed(1)}` : 'No rating yet';
        return `
    <div class="tech-card ${t.availability === 'unavailable' ? 'unavailable' : ''} ${selectedTechId == t.id ? 'selected' : ''}"
         id="techCard${t.id}"
         onclick="${t.availability !== 'unavailable' ? `selectTech(${t.id}, this)` : ''}">
      <div class="tech-av">${t.name[0]}</div>
      <div style="flex:1;min-width:0;">
        <div class="tech-name">${t.name}</div>
        <div class="tech-meta">
          <span class="avail-dot ${t.availability}"></span>${t.availability}
          &nbsp;·&nbsp; ${ratingDisplay}
          &nbsp;·&nbsp; ${t.jobs_done} jobs
        </div>
      </div>
      <div class="tech-info-btn ${t.availability === 'unavailable' ? 'unavailable' : ''}" onclick="event.stopPropagation(); showTechInfo(${t.id})">
        <i class="bi bi-info-circle"></i>
      </div>
    </div>
  `}).join('');
    }

    async function showTechInfo(id) {
      const t = loadedTechs.find(x => x.id == id);
      if (!t) return;

      const isRated = (t.rating && parseFloat(t.rating) > 0);

      document.getElementById('tmAv').textContent = t.name[0];
      document.getElementById('tmName').textContent = t.name;
      document.getElementById('tmSpec').textContent = t.specialty;
      document.getElementById('tmRating').textContent = isRated ? `⭐ ${parseFloat(t.rating).toFixed(1)}` : '-';
      document.getElementById('tmJobs').textContent = t.jobs_done;
      document.getElementById('tmLocation').textContent = t.address || 'Address not available';
      document.getElementById('tmPhone').textContent = t.phone || 'Phone not available';
      document.getElementById('tmAvail').innerHTML = `<span class="avail-dot ${t.availability}"></span> ${t.availability}`;

      const btn = document.getElementById('tmSelectBtn');
      if (t.availability === 'unavailable') {
        btn.style.display = 'none';
      } else {
        btn.style.display = 'block';
        btn.onclick = () => {
          const card = document.getElementById('techCard' + t.id);
          if (card) selectTech(t.id, card);
          closeTechInfo();
        };
      }

      document.getElementById('tmReviewsContainer').innerHTML = '<div style="font-size:12px; color:#7A7064; text-align:center; padding:10px;"><i class="bi bi-arrow-clockwise"></i> Loading reviews...</div>';
      document.getElementById('techModalOverlay').classList.add('show');

      try {
        const res = await fetch('../api/reviews_api.php?action=get_reviews&provider_id=' + id);
        const data = await res.json();
        const rc = document.getElementById('tmReviewsContainer');

        if (!data.success || !data.reviews || data.reviews.length === 0) {
          rc.innerHTML = '<div style="font-size:12px; color:#7A7064; text-align:center; padding:10px;">' + (isRated ? 'No written reviews yet.' : 'No rating yet.') + '</div>';
          return;
        }

        rc.innerHTML = data.reviews.map(r => {
          const d = new Date(r.created_at.replace(' ', 'T')).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
          const stars = '⭐'.repeat(r.rating) + '<span style="color:#DDD">★</span>'.repeat(5 - r.rating);
          return `
                <div class="tm-review-card">
                    <div class="tm-review-hdr">
                        <div class="tm-review-user">${r.user_name || 'Customer'}</div>
                        <div class="tm-review-stars">${stars}</div>
                    </div>
                    ${r.comment ? `<div class="tm-review-txt">"${r.comment}"</div>` : ''}
                    <div class="tm-review-date">${d}</div>
                </div>`;
        }).join('');

      } catch (e) {
        document.getElementById('tmReviewsContainer').innerHTML = '<div style="font-size:12px; color:#ef4444; text-align:center; padding:10px;">Failed to load reviews.</div>';
      }
    }

    function closeTechInfo() {
      document.getElementById('techModalOverlay').classList.remove('show');
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

      if (selectedTechId) {
        fd.append('technician_id', selectedTechId);
      }

      // Add dynamic field data
      const dynamicData = getDynamicFieldsData();
      Object.keys(dynamicData).forEach(key => {
        fd.append(key, dynamicData[key]);
      });

      try {
        const res = await fetch('../api/bookings_api.php', { method: 'POST', body: fd });
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
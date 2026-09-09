<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$hour = (int) date('H');
if ($hour < 12)
  $greeting = 'Good Morning';
elseif ($hour < 18)
  $greeting = 'Good Afternoon';
else
  $greeting = 'Good Evening';

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');

require_once __DIR__ . '/api/db.php';
$unreadNotifsCount = 0;
$uid = (int) ($_SESSION['user_id'] ?? 0);
if ($uid > 0) {
  $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0");
  if ($stmt) {
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $unreadNotifsCount = (int) ($res['cnt'] ?? 0);
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Home</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
  <link href="assets/css/home.css?v=<?= time() ?>" rel="stylesheet">
</head>

<body>
  <div class="shell" id="app">
    <div id="ml">
      <div class="ml-wrap">
        <div class="ml-box"><svg viewBox="0 0 54 54" fill="none">
            <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white" />
            <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" />
          </svg></div>
        <div class="ml-name">Home<span>Ease</span></div>
        <div class="ml-dots">
          <div class="ml-dot"></div>
          <div class="ml-dot"></div>
          <div class="ml-dot"></div>
        </div>
      </div>
    </div>

    <div class="screen" id="home">
      <div class="h-scroll">


        <div class="h-hdr">
          <div class="h-top">
            <div>
              <div class="h-greet"><?= $greeting ?></div>
              <div class="h-name" id="hUserName"><?= $userName ?></div>
            </div>

          </div>
        </div>

        <div class="sec-row">
          <div class="sec-ttl">Our Services</div>
        </div>
        <div class="svc-ads-grid" id="svcAdsGrid"></div>

        <!-- Service Detail Panel -->
        <div class="svc-detail-overlay" id="svcDetailOverlay" onclick="closeSvcDetail(event)"></div>
        <div class="svc-detail-panel" id="svcDetailPanel">
          <div class="sdp-handle"></div>

          <!-- Hero banner -->
          <div class="sdp-hero" id="sdpHero">
            <div class="sdp-hero-icon"><i id="sdpIcon"></i></div>
            <div class="sdp-hero-text">
              <div class="sdp-tag" id="sdpTag">SERVICE</div>
              <div class="sdp-title" id="sdpTitle"></div>
            </div>
            <div class="sdp-hero-close" onclick="closeSvcDetail()"><i class="bi bi-x"></i></div>
          </div>

          <!-- Price + Duration chips -->
          <div class="sdp-chips" id="sdpChips"></div>

          <!-- Description -->
          <div class="sdp-section-lbl">About This Service</div>
          <div class="sdp-desc" id="sdpDesc"></div>

          <!-- What's included -->
          <div class="sdp-section-lbl">What's Included</div>
          <div class="sdp-features" id="sdpFeatures"></div>

          <!-- How it works -->
          <div class="sdp-section-lbl">How It Works</div>
          <div class="sdp-steps" id="sdpSteps"></div>

          <!-- Highlights -->
          <div class="sdp-highlights" id="sdpHighlights"></div>

          <!-- Guarantee -->
          <div class="sdp-guarantee" id="sdpGuarantee"></div>

          <button class="sdp-book-btn" id="sdpBookBtn">Book Now &nbsp;<i class="bi bi-arrow-right"></i></button>
        </div>







        <!-- All Providers Panel -->
        <div class="svc-detail-overlay" id="proOverlay" onclick="closeAllProviders(event)"></div>
        <div class="svc-detail-panel" id="proPanel">
          <div class="sdp-handle"></div>
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <div>
              <div style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:var(--td)">All Providers</div>
              <div style="font-family:'Nunito',sans-serif;font-size:12px;color:var(--tm);margin-top:2px">Verified service professionals</div>
            </div>
            <div class="sdp-hero-close" onclick="closeAllProviders()" style="background:var(--teal-xlt)"><i class="bi bi-x" style="color:var(--td)"></i></div>
          </div>
          <div id="proList" style="display:flex;flex-direction:column;gap:12px"><div style="text-align:center;padding:30px;color:var(--tm);font-family:Nunito,sans-serif">Loading providers...</div></div>
        </div>

        <!-- Provider Profile Popout -->
        <div class="svc-detail-overlay" id="proProfileOverlay" onclick="closeProviderProfile(event)"></div>
        <div class="svc-detail-panel" id="proProfilePanel">
          <div class="sdp-handle"></div>
          <div class="sdp-hero-close" onclick="closeProviderProfile()" style="position:absolute;top:16px;right:16px;background:var(--teal-xlt);z-index:10"><i class="bi bi-x" style="color:var(--td)"></i></div>
          
          <div style="display:flex;flex-direction:column;align-items:center;margin-top:10px;padding:0 24px;">
            <div id="proProfileAvatarWrap" style="width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:12px;position:relative;">
              <img id="proProfileImg" src="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;display:none;">
              <div id="proProfileInitials" style="font-size:32px;font-weight:800;color:#fff;display:none;"></div>
              <div id="proProfileVerified" style="position:absolute;bottom:0;right:0;width:24px;height:24px;background:#10B981;border-radius:50%;border:2px solid #fff;align-items:center;justify-content:center;color:#fff;font-size:12px;display:none;"><i class="bi bi-patch-check-fill"></i></div>
            </div>
            
            <div style="display:flex;align-items:center;gap:6px;">
              <div id="proProfileName" style="font-family:'Poppins',sans-serif;font-size:22px;font-weight:800;color:var(--td);text-align:center;"></div>
            </div>
            <div id="proProfileSpecialty" style="font-family:'Nunito',sans-serif;font-size:14px;font-weight:700;margin-top:6px;padding:4px 14px;border-radius:20px;"></div>
            
          </div>
          
          <div style="padding:24px;margin-top:24px;">
            <button id="proProfileBookBtn" style="width:100%;padding:16px;border:none;border-radius:16px;color:#fff;font-family:'Poppins',sans-serif;font-size:15px;font-weight:800;cursor:pointer;box-shadow:0 6px 20px rgba(0,0,0,0.15);">
              Book Now
            </button>
          </div>
        </div>

        <div class="h-pb"></div>
      </div>
      <div id="navContainer"></div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    window.HE = window.HE || {};
    window.HE.unreadNotifs = <?= (int) $unreadNotifsCount ?>;
    window.HE.user = {
      name: <?= json_encode($_SESSION['user_name'] ?? '') ?>,
      email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
      phone: <?= json_encode($_SESSION['user_phone'] ?? '') ?>,
      address: <?= json_encode($_SESSION['user_address'] ?? '') ?>
    };
    
    // Store loaded providers for modals
    window._recentPros = [];
    window._allPros = [];

    function updateNotificationDot(unreadCount) {
      window.HE.unreadNotifs = Number(unreadCount) || 0;
      const dot = document.getElementById('navNotifDot') || document.querySelector('.bnav .ndot');
      if (dot) {
        dot.style.display = window.HE.unreadNotifs > 0 ? 'block' : 'none';
      }
      try {
        localStorage.setItem('he_unread_notifs', String(window.HE.unreadNotifs));
      } catch (e) {}
    }

    async function checkUnreadNotifications() {
      try {
        const res = await fetch('api/notifications_api.php?t=' + Date.now(), { cache: 'no-store' });
        const data = await res.json();
        if (data.success && Array.isArray(data.notifications)) {
          const unread = data.notifications.filter(n => Number(n.is_read) === 0);
          updateNotificationDot(unread.length);
        }
      } catch (e) {}
    }

    checkUnreadNotifications();
    setInterval(checkUnreadNotifications, 6000);
    window.addEventListener('focus', checkUnreadNotifications);
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        checkUnreadNotifications();
      }
    });
    window.addEventListener('storage', (e) => {
      if (e.key === 'he_unread_notifs') {
        const count = parseInt(e.newValue || '0', 10);
        updateNotificationDot(count);
      }
    });

    // ── AD-STYLE SERVICE CARDS (2-column grid layout matching reference)
    const svcAdData = [
      {
        key: 'House Cleaner',
        displayName: 'House Cleaning',
        bookingKey: 'House Cleaner',
        icon: 'bi-stars',
        gradient: 'linear-gradient(145deg,#E8820C 0%,#F5A623 55%,#FFB347 100%)',
        accentColor: '#E8820C',
        lightColor: '#FFF3E0',
        badge: '⭐ Top Rated',
        optionsSummary: 'General · Deep · Move-in/out',
        detailsSummary: 'Condo/Apartment & House',
        startsAt: 'Starts at ₱750',
        price: 750,
        extraChip: 'Add-ons: +₱100/room • +₱150/bath',
        duration: '2–4 hrs',
        desc: 'Professional house cleaning services. Choose from General Cleaning, Deep Cleaning, or Move-in/Move-out turnovers for Condo/Apartment and House properties. Convenient add-ons for additional rooms and bathrooms.',
        features: [
          'General Cleaning for living areas & bedrooms',
          'Deep Cleaning & sanitization',
          'Move-in / Move-out property turnovers',
          'Property types: Condo/Apartment & House',
          'Add-ons: +₱100/additional room',
          'Add-ons: +₱150/additional bathroom',
          'Basic cleaning supplies provided by cleaner'
        ],
        steps: [
          'Choose cleaning type, property type & rooms',
          'A vetted cleaner arrives on time with supplies',
          'Thorough cleaning from room to room',
          'You inspect — satisfaction guaranteed'
        ],
        highlights: [
          { icon: 'bi-shield-check', text: 'Background-checked cleaners' },
          { icon: 'bi-building', text: 'Condo/Apartment & House' },
          { icon: 'bi-tag-fill', text: 'Starts at ₱750' }
        ],
        guarantee: 'Not satisfied? We re-clean within 24 hours free of charge.',
        bgImg: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=500&q=75&auto=format&fit=crop'
      },
      {
        key: 'Plumber',
        displayName: 'Plumbing',
        bookingKey: 'Plumber',
        icon: 'bi-wrench-adjustable-circle',
        gradient: 'linear-gradient(135deg,#D97706 0%,#F5A623 55%,#FBB73C 100%)',
        accentColor: '#D97706',
        lightColor: '#FEF9E7',
        badge: '🔧 Licensed & Insured',
        optionsSummary: 'Leak Repair · Clog · Installation',
        detailsSummary: 'Kitchen · Bathroom · Outdoor',
        startsAt: 'Starts at ₱800',
        price: 800,
        extraChip: 'Kitchen • Bath • Outdoor',
        duration: '1–3 hrs',
        desc: 'Professional plumbing services. Expert diagnosis and repairs for water leaks, drain and toilet clogs, and fixture installations across kitchen, bathroom, and outdoor locations.',
        features: [
          'Water leak detection & pipe repair',
          'Drain & toilet clog removal',
          'Faucet & fixture installation',
          'Locations: Kitchen, Bathroom & Outdoor',
          'Licensed & insured plumbing professionals',
          'Basic tools & supplies provided by plumber'
        ],
        steps: [
          'Select issue type and fixture location',
          'Licensed plumber arrives with full toolkit',
          'Diagnosis & transparent quote before work begins',
          'Problem fixed, area cleaned, parts warrantied'
        ],
        highlights: [
          { icon: 'bi-award', text: 'Licensed & insured plumbers' },
          { icon: 'bi-geo-alt', text: 'Kitchen, Bathroom & Outdoor' },
          { icon: 'bi-tag-fill', text: 'Starts at ₱800' }
        ],
        guarantee: 'All repair work guaranteed for 30 days. Recurring issues fixed free.',
        bgImg: 'https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?w=500&q=75&auto=format&fit=crop'
      },
      {
        key: 'Helper',
        displayName: 'Helper',
        bookingKey: 'Helper',
        icon: 'bi-person-arms-up',
        gradient: 'linear-gradient(120deg,#F97316 0%,#F5A623 50%,#FCD34D 100%)',
        accentColor: '#F97316',
        lightColor: '#FFF7E6',
        badge: '💪 Most Flexible',
        optionsSummary: 'Cooking · Errands · Childcare',
        detailsSummary: 'Combined tasks · +₱100/hr',
        startsAt: 'Starts at ₱500',
        price: 500,
        extraChip: '+₱100/additional hour',
        duration: '4–8 hrs',
        desc: 'Reliable household assistance for cooking, general errands, and childcare supervision. Combine multiple tasks to fit your daily needs. Flexible hourly booking starting from 4 hours.',
        features: [
          'Home cooking & meal preparation',
          'General errands & grocery shopping',
          'Childcare supervision & basic care',
          'Combined task options available',
          'Starting duration: 4 hours (Starts at ₱500)',
          'Additional hour: +₱100/hour',
          'Background-checked & ID-verified helpers'
        ],
        steps: [
          'Select task combination and required hours',
          'Helper arrives promptly at scheduled time',
          'Tasks completed attentively and neatly',
          'Transparent pricing based on hours selected'
        ],
        highlights: [
          { icon: 'bi-person-check', text: 'Background-checked & verified' },
          { icon: 'bi-layers', text: 'Combined task options' },
          { icon: 'bi-clock', text: 'Starts at ₱500 · +₱100/hr' }
        ],
        guarantee: 'Not satisfied with your helper? We reassign a new one promptly at no extra cost.',
        bgImg: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500&q=75&auto=format&fit=crop'
      },
      {
        key: 'Appliance Technician',
        displayName: 'Appliance Repair',
        bookingKey: 'Appliance Technician',
        icon: 'bi-tools',
        gradient: 'linear-gradient(150deg,#C2410C 0%,#E8820C 50%,#F5A623 100%)',
        accentColor: '#C2410C',
        lightColor: '#FFF0E0',
        badge: '⚡ Certified Experts',
        optionsSummary: 'Aircon · Ref · Washer · TV',
        detailsSummary: 'Minor & Major Service Details',
        startsAt: 'Starts at ₱800',
        price: 800,
        extraChip: 'Minor & Major repairs',
        duration: '1–3 hrs',
        desc: 'Certified technician service for major household appliances: Aircon, Refrigerator, Washing Machine, and TV. Professional troubleshooting and repairs for both Minor and Major problem severity.',
        features: [
          'Aircon servicing & repair',
          'Refrigerator & freezer troubleshooting',
          'Washing machine diagnostics & fix',
          'Television repair & electronics servicing',
          'Service details: Minor & Major severity',
          'Basic tools & supplies provided by technician'
        ],
        steps: [
          'Select appliance type & problem severity',
          'Technician arrives with diagnostic tools',
          'Clear inspection and quote before repair starts',
          'Repair completed, tested & demonstrated'
        ],
        highlights: [
          { icon: 'bi-patch-check', text: 'Certified technicians' },
          { icon: 'bi-tv', text: 'Aircon, Ref, Washer & TV' },
          { icon: 'bi-tag-fill', text: 'Starts at ₱800' }
        ],
        guarantee: 'All repair work covered by a 14-day service warranty.',
        bgImg: 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=500&q=75&auto=format&fit=crop'
      },
      {
        key: 'Laundry Worker',
        displayName: 'Laundry',
        bookingKey: 'Laundry Worker',
        icon: 'bi-basket2-fill',
        gradient: 'linear-gradient(125deg,#F59E0B 0%,#F5B942 50%,#FBBF24 100%)',
        accentColor: '#F59E0B',
        lightColor: '#FFFCE6',
        badge: '✨ Most Popular',
        optionsSummary: 'Wash & Dry · Iron · Fold',
        detailsSummary: 'Weight-based • Combinations',
        startsAt: 'Starts at ₱450',
        price: 450,
        extraChip: 'Weight-based pricing',
        duration: '2–6 hrs',
        desc: 'Convenient at-home laundry care. Select from Wash & Dry, Iron, Fold, or combined laundry services. Simple weight-based pricing based on Under 5kg, 5–10kg, or Over 10kg.',
        features: [
          'Wash & Dry machine cycles',
          'Professional clothing ironing & pressing',
          'Neat folding & organizing',
          'Combination services available',
          'Weight-based pricing structure',
          'Care for delicates, linens & uniforms',
          'Equipment provided by laundry worker'
        ],
        steps: [
          'Select laundry service combo & weight bracket',
          'Worker arrives and handles laundry at your home',
          'Clothes washed, dried, ironed and folded',
          'Everything neatly returned and organized'
        ],
        highlights: [
          { icon: 'bi-droplet', text: 'Gentle fabric care' },
          { icon: 'bi-layers', text: 'Combo services available' },
          { icon: 'bi-tag-fill', text: 'Starts at ₱450' }
        ],
        guarantee: 'Careful handling guaranteed for all fabrics and clothing.',
        bgImg: 'https://images.unsplash.com/photo-1545173168-9f1947eebb7f?w=500&q=75&auto=format&fit=crop'
      },
      {
        key: 'Carpenter',
        displayName: 'Carpentry',
        bookingKey: 'Carpenter',
        icon: 'bi-hammer',
        gradient: 'linear-gradient(160deg,#EA580C 0%,#F5A623 55%,#FBA94C 100%)',
        accentColor: '#EA580C',
        lightColor: '#FFF1E6',
        badge: '🪚 Skilled Craftsmen',
        optionsSummary: 'Repair · Installation',
        detailsSummary: 'Simple & Complex Tasks',
        startsAt: 'Starts at ₱800',
        price: 800,
        extraChip: 'Simple & Complex tasks',
        duration: '2–8 hrs',
        desc: 'Skilled carpentry services for woodwork repair and custom installation. From simple door, hinge, and furniture fixes to complex cabinetry and custom shelving installations.',
        features: [
          'Woodwork repairs & restoration',
          'Cabinet & shelving installation',
          'Door, window frame & hinge fittings',
          'Task complexity: Simple & Complex',
          'Furniture assembly & custom builds',
          'Basic tools & supplies provided by carpenter'
        ],
        steps: [
          'Select carpentry task & complexity level',
          'Carpenter arrives with professional tools',
          'Work executed cleanly and accurately',
          'Job completed, inspected & work area swept clean'
        ],
        highlights: [
          { icon: 'bi-rulers', text: 'Precision craftsmanship' },
          { icon: 'bi-hammer', text: 'Repair & Installation' },
          { icon: 'bi-tag-fill', text: 'Starts at ₱800' }
        ],
        guarantee: 'Structural work guaranteed for 60 days. Joint or fitting issues fixed free.',
        bgImg: 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=500&q=75&auto=format&fit=crop'
      }
    ];

    // ── OPEN DETAIL PANEL
    function openSvcDetail(svc) {
      const panel = document.getElementById('svcDetailPanel');
      const overlay = document.getElementById('svcDetailOverlay');
      // Hero
      const hero = document.getElementById('sdpHero');
      hero.style.background = svc.gradient;
      document.getElementById('sdpIcon').className = `bi ${svc.icon}`;
      document.getElementById('sdpTag').textContent = svc.badge;
      document.getElementById('sdpTitle').textContent = svc.displayName || svc.key;
      // Chips
      document.getElementById('sdpChips').innerHTML = `
        <div class="sdp-chip" style="background:${svc.lightColor};color:${svc.accentColor}"><i class="bi bi-tag-fill"></i> ${svc.startsAt}</div>
        ${svc.extraChip ? `<div class="sdp-chip" style="background:${svc.lightColor};color:${svc.accentColor}"><i class="bi bi-info-circle-fill"></i> ${svc.extraChip}</div>` : ''}
        <div class="sdp-chip" style="background:${svc.lightColor};color:${svc.accentColor}"><i class="bi bi-clock"></i> ${svc.duration}</div>
        ${svc.priceNote ? `<div class="sdp-chip sdp-chip-note">${svc.priceNote}</div>` : ''}`;
      // Desc
      document.getElementById('sdpDesc').textContent = svc.desc;
      // Features
      document.getElementById('sdpFeatures').innerHTML = svc.features
        .map(f => `<div class="sdp-feat-item"><i class="bi bi-check-circle-fill" style="color:${svc.accentColor}"></i><span>${f}</span></div>`)
        .join('');
      // Steps
      document.getElementById('sdpSteps').innerHTML = svc.steps
        .map((s, i) => `<div class="sdp-step"><div class="sdp-step-num" style="background:${svc.gradient}">${i+1}</div><div class="sdp-step-txt">${s}</div></div>`)
        .join('');
      // Highlights
      document.getElementById('sdpHighlights').innerHTML = svc.highlights
        .map(h => `<div class="sdp-hl-item"><div class="sdp-hl-ic" style="color:${svc.accentColor};background:${svc.lightColor}"><i class="bi ${h.icon}"></i></div><span>${h.text}</span></div>`)
        .join('');
      // Guarantee
      document.getElementById('sdpGuarantee').innerHTML = `<i class="bi bi-shield-fill-check" style="color:${svc.accentColor}"></i><span>${svc.guarantee}</span>`;
      // Book button
      document.getElementById('sdpBookBtn').style.background = svc.gradient;
      document.getElementById('sdpBookBtn').onclick = () => goPage(`clients/booking_form.php?svc=${encodeURIComponent(svc.bookingKey || svc.key)}&newbooking=1`);
      overlay.classList.add('on');
      requestAnimationFrame(() => panel.classList.add('on'));
      document.body.style.overflow = 'hidden';
    }

    function closeSvcDetail(e) {
      // Only close if called from X button (no event) or backdrop click
      if (e && e.target !== document.getElementById('svcDetailOverlay')) return;
      document.getElementById('svcDetailPanel').classList.remove('on');
      document.getElementById('svcDetailOverlay').classList.remove('on');
      document.body.style.overflow = '';
    }

    // ── RENDER AD CARDS
    const svcAdsGrid = document.getElementById('svcAdsGrid');
    svcAdData.forEach((svc, i) => {
      const card = document.createElement('div');
      card.className = 'svc-ad-card';
      card.style.animationDelay = `${i * 0.08}s`;
      card.innerHTML = `
        <div class="sac-bg" style="background:${svc.gradient}"></div>
        ${svc.bgImg ? `<div class="sac-img" style="background-image:url('${svc.bgImg}')"></div>` : ''}
        <div class="sac-pattern"></div>
        <div class="sac-top">
          <div class="sac-badge">${svc.badge}</div>
        </div>
        <div class="sac-body">
          <div class="sac-tagline">${svc.displayName}</div>
          <div class="sac-subline">${svc.optionsSummary}</div>
          <div class="sac-details">${svc.detailsSummary}</div>
        </div>
        <div class="sac-footer">
          <div class="sac-footer-left">
            <div class="sac-price">${svc.startsAt}</div>
          </div>
          <div class="sac-cta"></div>
        </div>`;
      card.addEventListener('click', () => openSvcDetail(svc));
      svcAdsGrid.appendChild(card);
    });

    // ── ALL PROVIDERS PANEL
    let allProsLoaded = false;
    function buildProCard(w) {
      const initials = w.name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
      const specialtyColors = {
        'House Cleaner': { g:'linear-gradient(135deg,#92400E,#EA580C)', light:'#FFF1E6', text:'#C2410C' },
        'Plumbing':      { g:'linear-gradient(135deg,#78350F,#D97706)', light:'#FEF3C7', text:'#B45309' },
        'Helper':        { g:'linear-gradient(135deg,#F59E0B,#FCD34D)', light:'#FFFBEB', text:'#D97706' },
        'Appliance Technician': { g:'linear-gradient(135deg,#7C2D12,#F97316)', light:'#FFF7ED', text:'#EA580C' },
        'Laundry':       { g:'linear-gradient(135deg,#A16207,#EAB308)', light:'#FEF9C3', text:'#CA8A04' },
        'Carpentry':     { g:'linear-gradient(135deg,#431407,#C2410C)', light:'#FEF2E7', text:'#9A3412' },
      };
      const sc = specialtyColors[w.specialty] || { g:'linear-gradient(135deg,#E8820C,#F5A623)', light:'#FFF3E0', text:'#B45309' };
      const stars = (w.rating !== undefined && w.rating !== null && !isNaN(w.rating)) ? parseFloat(w.rating) : 0;
      const starsHtml = Array.from({length:5},(_,i)=>`<i class="bi ${i<Math.floor(stars)?'bi-star-fill':(i<stars?'bi-star-half':'bi-star')}" style="color:#F5A623;font-size:10px;"></i>`).join('');
      return `<div class="pro-card" onclick="openProviderProfile(${w._allIdx}, 'all')">
        ${w.top?'<div class="pro-top-badge"><i class="bi bi-trophy-fill"></i> TOP PRO</div>':''}
        <div class="pro-avatar-wrap" style="background:${sc.g}">
          <img class="pro-avatar" src="${w.img}" alt="${w.name}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="pro-initials" style="display:none">${initials}</div>
        </div>
        <div class="pro-info">
          <div class="pro-name-row"><span class="pro-name">${w.name}</span>${w.is_verified?'<i class="bi bi-patch-check-fill pro-verified"></i>':''}</div>
          <div class="pro-pill" style="background:${sc.light};color:${sc.text}">${w.specialty}</div>
          <div class="pro-meta"><div class="pro-stars">${starsHtml}<span>${stars.toFixed(1)}</span></div><span class="pro-sep">·</span><span class="pro-jobs"><i class="bi bi-briefcase-fill"></i> ${w.jobs_done} jobs</span></div>
        </div>
        <button class="pro-book-btn" style="background:${sc.g}" onclick="event.stopPropagation(); goPage('clients/booking_form.php?svc=${encodeURIComponent(w.specialty)}&newbooking=1')">Book<i class="bi bi-chevron-right"></i></button>
      </div>`;
    }

    async function openAllProviders() {
      document.getElementById('proOverlay').classList.add('on');
      requestAnimationFrame(() => document.getElementById('proPanel').classList.add('on'));
      document.body.style.overflow = 'hidden';
      if (allProsLoaded) return;
      try {
        const res = await fetch('api/workers_api.php?action=pros&all=1', { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !data.pros.length) {
          document.getElementById('proList').innerHTML = '<div style="text-align:center;padding:30px;color:var(--tm);font-family:Nunito,sans-serif">No providers found.</div>';
          return;
        }
        
        window._allPros = data.pros.map((p, i) => ({...p, _allIdx: i}));
        document.getElementById('proList').innerHTML = window._allPros.map(w => buildProCard(w)).join('');
        allProsLoaded = true;
      } catch(e) {
        document.getElementById('proList').innerHTML = '<div style="text-align:center;padding:30px;color:var(--tm);font-family:Nunito,sans-serif">Could not load providers.</div>';
      }
    }
    function closeAllProviders(e) {
      if (e && e.target !== document.getElementById('proOverlay')) return;
      document.getElementById('proPanel').classList.remove('on');
      document.getElementById('proOverlay').classList.remove('on');
      document.body.style.overflow = '';
    }


    // Most Popular section removed




    // ── PROVIDER PROFILE MODAL ──
    const proSpecColors = {
      'House Cleaner':       { g: 'linear-gradient(145deg,#E8820C,#F5A623,#FFB347)', light: '#FFF3E0', text: '#C2410C' },
      'Plumbing':            { g: 'linear-gradient(135deg,#D97706,#F5A623,#FBB73C)', light: '#FEF9E7', text: '#B45309' },
      'Helper':              { g: 'linear-gradient(120deg,#F97316,#F5A623,#FCD34D)', light: '#FFF7E6', text: '#EA580C' },
      'Appliance Technician':{ g: 'linear-gradient(150deg,#C2410C,#E8820C,#F5A623)', light: '#FFF0E0', text: '#C2410C' },
      'Laundry':             { g: 'linear-gradient(125deg,#F59E0B,#F5B942,#FBBF24)', light: '#FFFCE6', text: '#D97706' },
      'Carpentry':           { g: 'linear-gradient(160deg,#EA580C,#F5A623,#FBA94C)', light: '#FFF1E6', text: '#EA580C' },
    };

    function openProviderProfile(idx, listType = 'recent') {
      const w = listType === 'all' ? window._allPros[idx] : window._recentPros[idx];
      if (!w) return;
      
      const sc = proSpecColors[w.specialty] || { g: 'linear-gradient(135deg,#E8820C,#F5A623)', light: '#FFF3E0', text: '#C2410C' };
      const stars = (w.rating !== undefined && w.rating !== null && !isNaN(w.rating)) ? parseFloat(w.rating) : 0;
      const initials = w.name.split(' ').map(n => n[0]).join('').substring(0,2).toUpperCase();
      
      const overlay = document.getElementById('proProfileOverlay');
      const panel = document.getElementById('proProfilePanel');
      
      // Avatar
      const img = document.getElementById('proProfileImg');
      const initDiv = document.getElementById('proProfileInitials');
      const wrap = document.getElementById('proProfileAvatarWrap');
      wrap.style.background = sc.g;
      
      img.src = w.img;
      img.style.display = 'block';
      initDiv.style.display = 'none';
      img.onerror = () => { img.style.display='none'; initDiv.style.display='block'; initDiv.textContent = initials; };
      
      document.getElementById('proProfileVerified').style.display = w.is_verified ? 'flex' : 'none';
      
      // Info
      document.getElementById('proProfileName').textContent = w.name;
      const spec = document.getElementById('proProfileSpecialty');
      spec.textContent = w.specialty;
      spec.style.background = sc.light;
      spec.style.color = sc.text;
      

      
      // Book btn
      const btn = document.getElementById('proProfileBookBtn');
      btn.style.background = sc.g;
      btn.onclick = () => goPage(`clients/booking_form.php?svc=${encodeURIComponent(w.specialty)}&newbooking=1`);
      
      overlay.classList.add('on');
      requestAnimationFrame(() => panel.classList.add('on'));
      document.body.style.overflow = 'hidden';
    }

    function closeProviderProfile(e) {
      if (e && e.target !== document.getElementById('proProfileOverlay')) return;
      document.getElementById('proProfilePanel').classList.remove('on');
      document.getElementById('proProfileOverlay').classList.remove('on');
      document.body.style.overflow = '';
    }
    const notifDotStyle = (window.HE.unreadNotifs || 0) > 0 ? 'display:block;' : 'display:none;';
    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni on"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('clients/booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('clients/service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('clients/notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span><div class="ndot" id="navNotifDot" style="${notifDotStyle}"></div></div>
        <div class="ni" onclick="goPage('clients/profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;

    // Dynamic greeting update
    function updateGreeting() {
      const hour = new Date().getHours();
      let greeting = 'Good Morning';
      if (hour >= 12 && hour < 18) {
        greeting = 'Good Afternoon';
      } else if (hour >= 18) {
        greeting = 'Good Evening';
      }
      const greetingElement = document.querySelector('.h-greet');
      if (greetingElement) {
        greetingElement.textContent = greeting;
      }
    }

    updateGreeting();
    setInterval(updateGreeting, 60000); // Update every 60 seconds
  </script>
</body>

</html>

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
  <link href="assets/css/main.css?v=2.0" rel="stylesheet">
  <link href="assets/css/home.css?v=2.0" rel="stylesheet">
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
            <div class="h-top-right">
              <div class="h-bell" onclick="openChat('support')" title="Chat with us" style="position:relative;">
                <i class="bi bi-chat-dots-fill"></i>
                <div
                  style="position:absolute;top:4px;right:4px;width:8px;height:8px;background:#f59e0b;border-radius:50%;">
                </div>
              </div>
              <div class="h-bell" onclick="goPage('clients/notifications.php')" style="position:relative;">
                <i class="bi bi-bell-fill"></i>
                <div class="h-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
            </div>
          </div>
          <div class="s-bar" onclick="openSearch()"><i class="bi bi-search"></i><span>Search for a service...</span>
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

          <!-- Stats row -->
          <div class="sdp-stats" id="sdpStats"></div>

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
    window.HE.user = {
      name: <?= json_encode($_SESSION['user_name'] ?? '') ?>,
      email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
      phone: <?= json_encode($_SESSION['user_phone'] ?? '') ?>,
      address: <?= json_encode($_SESSION['user_address'] ?? '') ?>
    };
    
    // Store loaded providers for modals
    window._recentPros = [];
    window._allPros = [];

    fetch('api/notifications_api.php')
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const unread = data.notifications.filter(n => n.is_read == 0);
          if (unread.length > 0) document.getElementById('bellDot').style.display = 'block';
        }
      }).catch(() => { });

    // ── AD-STYLE SERVICE CARDS (names & prices match booking system exactly)
    const svcAdData = [{
        key: 'House Cleaner',
        icon: 'bi-stars',
        gradient: 'linear-gradient(145deg,#E8820C 0%,#F5A623 55%,#FFB347 100%)',
        accentColor: '#E8820C',
        lightColor: '#FFF3E0',
        badge: '⭐ #1 Top Rated',
        tagline: 'Spotless Home,\nGuaranteed',
        subline: 'Home & office cleaning — done right',
        emoji: '🧹',
        promoLabel: 'MOST BOOKED',
        price: 500, priceNote: 'Base rate — final price set on booking',
        duration: '2–4 hrs', rating: '4.9', jobs: '1.2k+', repeat: '89%',
        desc: 'Professional home & office cleaners who deep-clean every room — scrubbing grout, degreasing kitchens, sanitizing bathrooms, and leaving your space spotless and fresh. Background-checked and satisfaction-guaranteed.',
        features: ['Full room deep-clean (all floors)', 'Kitchen degreasing & appliance wipe-down', 'Bathroom scrub & toilet disinfection', 'Window sills, baseboards & surface polish', 'Vacuuming & mopping all floor types', 'Bed linen change (on request)'],
        steps: ['Book your slot in 60 seconds', 'A vetted cleaner arrives on time', 'We clean top-to-bottom, room by room', 'You inspect — we fix anything you flag'],
        highlights: [{ icon: 'bi-shield-check', text: 'Background-checked cleaners' }, { icon: 'bi-clock', text: 'On-time or your next session is free' }, { icon: 'bi-recycle', text: 'Eco-friendly cleaning products' }],
        guarantee: 'Not satisfied? We re-clean within 24 hours — free of charge.',
        bgImg: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=500&q=75&auto=format&fit=crop'
      },{
        key: 'Plumber',
        icon: 'bi-wrench-adjustable-circle',
        gradient: 'linear-gradient(135deg,#D97706 0%,#F5A623 55%,#FBB73C 100%)',
        accentColor: '#D97706',
        lightColor: '#FEF9E7',
        badge: '🔧 Licensed & Insured',
        tagline: 'Leaks Fixed,\nNo Mess Left',
        subline: 'Pipes, leaks & fixtures — same day',
        emoji: '🚰',
        promoLabel: 'FREE DIAGNOSIS',
        price: 500, priceNote: 'Base rate — diagnosis included',
        duration: '1–3 hrs', rating: '4.8', jobs: '980+', repeat: '82%',
        desc: 'From dripping faucets to burst pipes, our licensed plumbers handle it all. We diagnose the root cause — not just the symptom — and fix it right the first time. Transparent pricing, no surprise charges.',
        features: ['Leak detection & pipe repair', 'Drain & toilet unclogging', 'Faucet & fixture installation', 'Water heater service & installation', 'Shower & bathtub repairs', 'Water pressure diagnosis & fix'],
        steps: ['Describe your issue when booking', 'Licensed plumber arrives with full toolkit', 'Free on-site diagnosis before work starts', 'Problem fixed, area cleaned, parts warrantied'],
        highlights: [{ icon: 'bi-award', text: 'Licensed & insured plumbers' }, { icon: 'bi-currency-dollar', text: 'Transparent pricing — no hidden fees' }, { icon: 'bi-tools', text: 'Parts warranted for 30 days' }],
        guarantee: 'All repair work is guaranteed for 30 days. If the same issue recurs, we fix it free.',
        bgImg: 'https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?w=500&q=75&auto=format&fit=crop'
      },{
        key: 'Helper',
        icon: 'bi-person-arms-up',
        gradient: 'linear-gradient(120deg,#F97316 0%,#F5A623 50%,#FCD34D 100%)',
        accentColor: '#F97316',
        lightColor: '#FFF7E6',
        badge: '💪 Most Flexible',
        tagline: 'Extra Hands,\nWhen You Need',
        subline: 'All-around household help — book hourly',
        emoji: '🙌',
        promoLabel: 'NO MIN. HOURS',
        price: 400, priceNote: 'Base rate — final price set on booking',
        duration: '1–8 hrs', rating: '4.7', jobs: '2.1k+', repeat: '76%',
        desc: 'Need a reliable extra pair of hands? Our helpers are trustworthy, physically fit, and ready for almost anything — moving furniture, grocery errands, packing boxes, or general household tasks. Book hourly, no commitment.',
        features: ['Moving & heavy lifting', 'Grocery & errand runs', 'Event set-up & pack-down', 'Home organizing & decluttering', 'Garden & outdoor clean-up', 'Office & storage room sorting'],
        steps: ['Choose your task type when booking', 'Helper arrives at your scheduled time', 'Work together or let them handle it solo', 'Pay only for the hours used'],
        highlights: [{ icon: 'bi-person-check', text: 'Background-checked & ID-verified' }, { icon: 'bi-stopwatch', text: 'Book for as little as 1 hour' }, { icon: 'bi-bag-check', text: 'No task too big or too small' }],
        guarantee: 'Not satisfied with your helper? We reassign a new one the same day at no extra cost.',
        bgImg: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=500&q=75&auto=format&fit=crop'
      },{
        key: 'Appliance Technician',
        icon: 'bi-tools',
        gradient: 'linear-gradient(150deg,#C2410C 0%,#E8820C 50%,#F5A623 100%)',
        accentColor: '#C2410C',
        lightColor: '#FFF0E0',
        badge: '⚡ Certified Experts',
        tagline: 'Appliances Fixed\nSame Day',
        subline: 'Fix home appliances — all major brands',
        emoji: '🔌',
        promoLabel: 'SAME-DAY SLOTS',
        price: 500, priceNote: 'Base rate — parts quoted separately',
        duration: '1–3 hrs', rating: '4.8', jobs: '750+', repeat: '85%',
        desc: 'Certified technicians trained to service all major brands — LG, Samsung, Carrier, Panasonic and more. We carry common replacement parts and can often fix your appliance in a single visit.',
        features: ['Air conditioner cleaning, repair & regas', 'Refrigerator & chest freezer repair', 'Washing machine & dryer diagnostics', 'Microwave, oven & stove repair', 'Television & electronics troubleshooting', 'General electrical appliance servicing'],
        steps: ['Describe the appliance & symptom', 'Technician arrives with diagnostic tools', 'Transparent quote before any work begins', 'Repair done, appliance tested & signed off'],
        highlights: [{ icon: 'bi-patch-check', text: 'Certified by major appliance brands' }, { icon: 'bi-box-seam', text: 'Common parts on hand — faster fix' }, { icon: 'bi-calendar2-check', text: 'Same-day & next-day slots available' }],
        guarantee: 'All repair work covered by a 14-day service warranty. Parts warranty varies by manufacturer.',
        bgImg: 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=500&q=75&auto=format&fit=crop'
      },{
        key: 'Laundry Worker',
        icon: 'bi-basket2-fill',
        gradient: 'linear-gradient(125deg,#F59E0B 0%,#F5B942 50%,#FBBF24 100%)',
        accentColor: '#F59E0B',
        lightColor: '#FFFCE6',
        badge: '✨ Most Popular',
        tagline: 'Fresh Clothes,\nZero Effort',
        subline: 'Washing & ironing — done at your home',
        emoji: '👕',
        promoLabel: 'FREE IRONING',
        price: 300, priceNote: 'Base rate — final price set on booking',
        duration: '2–6 hrs', rating: '4.9', jobs: '1.5k+', repeat: '91%',
        desc: 'Our trained laundry workers handle your clothes with care — separating colours, choosing the right wash cycle, and returning everything neatly pressed and folded. Delicates washed by hand. Stain treatment included.',
        features: ['Machine wash & dry', 'Hand wash for delicates & woollens', 'Stain pre-treatment & odour removal', 'Ironing & pressing (shirts, uniforms, linens)', 'Fold, sort & organise by type', 'Linen, bedsheets & curtain washing'],
        steps: ['Book a time slot that suits you', 'Worker arrives with supplies (or uses yours)', 'Laundry sorted, washed, dried & ironed', 'Everything neatly returned to your wardrobe'],
        highlights: [{ icon: 'bi-droplet', text: 'Hypoallergenic detergent options' }, { icon: 'bi-heart', text: 'Gentle care for delicates & silk' }, { icon: 'bi-check2-all', text: 'Highest customer repeat rate — 91%' }],
        guarantee: 'If any item is damaged in our care, we compensate up to the replacement value.',
        bgImg: 'https://images.unsplash.com/photo-1545173168-9f1947eebb7f?w=500&q=75&auto=format&fit=crop'
      },{
        key: 'Carpenter',
        icon: 'bi-hammer',
        gradient: 'linear-gradient(160deg,#EA580C 0%,#F5A623 55%,#FBA94C 100%)',
        accentColor: '#EA580C',
        lightColor: '#FFF1E6',
        badge: '🪚 Skilled Craftsmen',
        tagline: 'Build It Right,\nMake It Last',
        subline: 'Furniture & woodwork — custom builds',
        emoji: '🪑',
        promoLabel: 'FREE ESTIMATE',
        price: 600, priceNote: 'Base rate — free on-site estimate first',
        duration: '2–8 hrs', rating: '4.8', jobs: '620+', repeat: '80%',
        desc: 'Our skilled carpenters bring years of experience to every project — from flat-pack furniture assembly to custom built-in shelves and cabinet installations. We work cleanly, finish neatly, and clean up after.',
        features: ['Furniture assembly (IKEA, local brands, etc.)', 'Cabinet, shelf & wardrobe installation', 'Door & window frame repair or replacement', 'Flooring & decking installation', 'Custom built-in shelving & storage', 'Wood repair, sanding & refinishing'],
        steps: ['Describe the job & share photos if possible', 'Carpenter visits for a free on-site estimate', 'Work scheduled at your convenience', 'Job completed, area swept clean'],
        highlights: [{ icon: 'bi-rulers', text: 'Precision measurements, clean finish' }, { icon: 'bi-file-earmark-check', text: 'Free estimate — no obligation' }, { icon: 'bi-recycle', text: 'Responsible wood sourcing & waste disposal' }],
        guarantee: 'Structural work guaranteed for 60 days. We fix any joint or fitting issues free of charge.',
        bgImg: 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=500&q=75&auto=format&fit=crop'
      }];

    // ── OPEN DETAIL PANEL
    function openSvcDetail(svc) {
      const panel = document.getElementById('svcDetailPanel');
      const overlay = document.getElementById('svcDetailOverlay');
      // Hero
      const hero = document.getElementById('sdpHero');
      hero.style.background = svc.gradient;
      document.getElementById('sdpIcon').className = `bi ${svc.icon}`;
      document.getElementById('sdpTag').textContent = svc.badge;
      document.getElementById('sdpTitle').textContent = svc.key;
      // Stats
      document.getElementById('sdpStats').innerHTML = `
        <div class="sdp-stat"><span class="sdp-stat-val" style="color:${svc.accentColor}">${svc.rating}★</span><span class="sdp-stat-lbl">Rating</span></div>
        <div class="sdp-stat-div"></div>
        <div class="sdp-stat"><span class="sdp-stat-val" style="color:${svc.accentColor}">${svc.jobs}</span><span class="sdp-stat-lbl">Jobs Done</span></div>
        <div class="sdp-stat-div"></div>
        <div class="sdp-stat"><span class="sdp-stat-val" style="color:${svc.accentColor}">${svc.repeat}</span><span class="sdp-stat-lbl">Repeat Clients</span></div>
        <div class="sdp-stat-div"></div>
        <div class="sdp-stat"><span class="sdp-stat-val" style="color:${svc.accentColor}">${svc.duration}</span><span class="sdp-stat-lbl">Avg. Time</span></div>`;
      // Chips
      document.getElementById('sdpChips').innerHTML = `
        <div class="sdp-chip" style="background:${svc.lightColor};color:${svc.accentColor}"><i class="bi bi-tag-fill"></i> from ₱${svc.price.toLocaleString()}/hr</div>
        <div class="sdp-chip" style="background:${svc.lightColor};color:${svc.accentColor}"><i class="bi bi-clock"></i> ${svc.duration}</div>
        <div class="sdp-chip sdp-chip-note">${svc.priceNote}</div>`;
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
      document.getElementById('sdpBookBtn').onclick = () => goPage(`clients/booking_form.php?svc=${encodeURIComponent(svc.key)}&newbooking=1`);
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
      card.style.animationDelay = `${i * 0.09}s`;
      card.innerHTML = `
        <div class="sac-bg" style="background:${svc.gradient}"></div>
        ${svc.bgImg ? `<div class="sac-img" style="background-image:url('${svc.bgImg}')"></div>` : ''}
        <div class="sac-pattern"></div>
        <div class="sac-top">
          <div class="sac-badge">${svc.badge}</div>
          <div class="sac-promo">${svc.promoLabel}</div>
        </div>
        <div class="sac-emoji">${svc.emoji}</div>
        <div class="sac-body">
          <div class="sac-tagline">${svc.tagline}</div>
          <div class="sac-subline">${svc.subline}</div>
        </div>
        <div class="sac-footer">
          <div class="sac-footer-left">
            <div class="sac-price">from ₱${svc.price.toLocaleString()}<span>/hr</span></div>
            <div class="sac-rating"><i class="bi bi-star-fill"></i>${svc.rating} · ${svc.jobs} jobs</div>
          </div>
          <div class="sac-cta">See Details <i class="bi bi-arrow-right"></i></div>
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
      const stars = parseFloat(w.rating || 4.8);
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
      const stars = parseFloat(w.rating || 4.8);
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
    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni on"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('clients/booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('clients/service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('clients/notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span><div class="ndot"></div></div>
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
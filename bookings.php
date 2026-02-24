<?php session_start();
$preService = isset($_GET['svc']) ? $_GET['svc'] : '';
$newBooking = isset($_GET['newbooking']); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Bookings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    #bookings {
      background: var(--bg-screen);
      justify-content: flex-start;
    }

    .bk-scroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 80px;
    }

    .bk-hdr {
      width: 100%;
      padding: 48px 22px 18px;
      background: var(--teal);
      border-radius: 0 0 28px 28px;
    }

    .bk-hdr-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .bk-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #fff;
    }

    .bk-sub {
      color: rgba(255, 255, 255, .75);
      font-size: 12px;
      margin-top: 2px;
    }

    .tabs {
      display: flex;
      background: #f0fdfa;
      border-radius: 13px;
      padding: 4px;
      margin: 15px 18px 0;
      gap: 3px;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .tabs::-webkit-scrollbar {
      display: none;
    }

    .tab {
      flex: 1;
      min-width: fit-content;
      padding: 9px 8px;
      text-align: center;
      border-radius: 9px;
      font-size: 10.5px;
      font-weight: 700;
      color: var(--tm);
      cursor: pointer;
      transition: all .2s;
      white-space: nowrap;
    }

    .tab.on {
      background: #fff;
      color: var(--teal);
      box-shadow: 0 2px 8px rgba(13, 148, 136, .12);
    }

    .bk-body {
      padding: 15px 18px;
    }

    .bc {
      background: #fff;
      border-radius: 17px;
      padding: 15px;
      margin-bottom: 13px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
    }

    .bc-hdr {
      display: flex;
      align-items: center;
      gap: 11px;
      margin-bottom: 11px;
    }

    .bc-ic {
      width: 46px;
      height: 46px;
      border-radius: 13px;
      overflow: hidden;
      flex-shrink: 0;
      background: var(--tbg);
    }

    .bc-ic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .bc-nm {
      font-size: 14px;
      font-weight: 700;
      color: var(--td);
    }

    .bc-dt {
      font-size: 11px;
      color: var(--tm);
      margin-top: 2px;
    }

    .badge {
      padding: 4px 11px;
      border-radius: 18px;
      font-size: 11px;
      font-weight: 700;
    }

    .b-pending {
      background: #fef3c7;
      color: #d97706;
    }

    .b-progress {
      background: #dbeafe;
      color: #2563eb;
    }

    .b-done {
      background: #d1fae5;
      color: #059669;
    }

    .bc-det {
      border-top: 1px solid #f3f4f6;
      padding-top: 11px;
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
    }

    .bc-txt {
      font-size: 12px;
      color: var(--tm);
      margin-bottom: 3px;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .bc-p {
      font-size: 16px;
      font-weight: 800;
      color: var(--teal);
    }

    .empty {
      text-align: center;
      padding: 40px 20px;
      color: var(--tm);
    }

    .empty-ic {
      font-size: 54px;
      margin-bottom: 14px;
    }

    .empty-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--td);
      margin-bottom: 7px;
    }

    #bModal {
      position: absolute;
      inset: 0;
      background: rgba(0, 30, 28, .55);
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      z-index: 200;
      opacity: 0;
      pointer-events: none;
      transition: opacity .3s;
    }

    #bModal.on {
      opacity: 1;
      pointer-events: all;
    }

    .m-sheet {
      background: #fff;
      border-radius: 26px 26px 0 0;
      max-height: 93%;
      overflow-y: auto;
      transform: translateY(100%);
      transition: transform .35s cubic-bezier(.4, 0, .2, 1);
      padding: 0 22px 40px;
    }

    #bModal.on .m-sheet {
      transform: translateY(0);
    }

    .m-hand {
      width: 38px;
      height: 4px;
      background: #e5e7eb;
      border-radius: 2px;
      margin: 13px auto 18px;
    }

    .m-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 19px;
      font-weight: 800;
      color: var(--td);
      margin-bottom: 18px;
    }

    .m-head {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 18px;
    }

    .m-head .m-ttl {
      margin-bottom: 0;
    }

    .ml {
      font-size: 12px;
      font-weight: 700;
      color: var(--td);
      margin-bottom: 7px;
      display: block;
    }

    .sp-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 9px;
      margin-bottom: 18px;
    }

    .sp-c {
      position: relative;
      border: 2px solid #e5e7eb;
      border-radius: 13px;
      padding: 10px 6px;
      text-align: center;
      cursor: pointer;
      transition: all .2s;
    }

    .sp-c.sel {
      border-color: var(--teal);
      background: var(--tbg);
    }

    .sp-c .sp-img {
      width: 100%;
      height: 52px;
      border-radius: 8px;
      object-fit: cover;
      margin-bottom: 6px;
    }

    .sp-c .sp-nm {
      font-size: 10px;
      font-weight: 700;
      color: var(--td);
    }

    .sp-star {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 22px;
      height: 22px;
      background: rgba(255, 255, 255, .95);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      color: #d1d5db;
      cursor: pointer;
      z-index: 2;
      transition: all .2s;
      box-shadow: 0 1px 4px rgba(0, 0, 0, .1);
    }

    .sp-star:hover {
      background: #fff;
      color: #f59e0b;
      transform: scale(1.1);
    }

    .sp-star.starred {
      color: #f59e0b;
      background: #fef3c7;
    }

    body.dark .sp-star {
      background: rgba(26, 46, 43, .95);
    }

    body.dark .sp-star:hover {
      background: var(--bg-card);
    }

    body.dark .sp-star.starred {
      background: #451a03;
      color: #fbbf24;
    }

    .rt {
      display: flex;
      background: #f0fdfa;
      border-radius: 11px;
      padding: 4px;
      margin-bottom: 18px;
    }

    .rt-tab {
      flex: 1;
      padding: 9px;
      text-align: center;
      border-radius: 7px;
      font-size: 12px;
      font-weight: 700;
      color: var(--tm);
      cursor: pointer;
      transition: all .2s;
    }

    .rt-tab.on {
      background: #fff;
      color: var(--teal);
      box-shadow: 0 2px 7px rgba(13, 148, 136, .12);
    }

    .p-disp {
      background: var(--tbg);
      border-radius: 15px;
      padding: 18px;
      text-align: center;
      margin-bottom: 18px;
    }

    .p-lbl {
      font-size: 11px;
      color: var(--teal);
      font-weight: 600;
      margin-bottom: 3px;
    }

    .p-amt {
      font-family: 'Poppins', sans-serif;
      font-size: 30px;
      font-weight: 800;
      color: var(--teal);
    }

    .p-note {
      font-size: 10px;
      color: var(--tm);
      margin-top: 3px;
    }

    .dur-row {
      display: flex;
      align-items: center;
      gap: 11px;
      margin-bottom: 18px;
    }

    .d-btn {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: 2px solid var(--teal);
      background: #fff;
      color: var(--teal);
      font-size: 21px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      transition: all .2s;
      flex-shrink: 0;
    }

    .d-btn:hover {
      background: var(--teal);
      color: #fff;
    }

    .d-lbl {
      flex: 1;
      text-align: center;
      font-family: 'Poppins', sans-serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--td);
    }

    .mi {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #e5e7eb;
      border-radius: 13px;
      font-family: 'Nunito', sans-serif;
      font-size: 13px;
      outline: none;
      transition: border-color .2s;
      background: #fafafa;
      margin-bottom: 14px;
      color: var(--td);
    }

    .mi:focus {
      border-color: var(--teal);
      background: #fff;
    }

    .mi.ta {
      resize: none;
      min-height: 75px;
    }

    body.dark .bc {
      background: var(--bg-card);
    }

    body.dark .bc-nm {
      color: var(--td);
    }

    body.dark .bc-dt {
      color: var(--tm);
    }

    body.dark .bc-txt {
      color: var(--tm);
    }

    body.dark .bc-det {
      border-top-color: var(--border-col);
    }

    body.dark .tabs {
      background: var(--pbg);
    }

    body.dark .tab.on {
      background: var(--bg-card);
      color: var(--teal);
    }

    body.dark .tab {
      color: var(--tm);
    }

    body.dark .m-sheet {
      background: var(--bg-card);
    }

    body.dark .m-ttl {
      color: var(--td);
    }

    body.dark .ml {
      color: var(--td);
    }

    body.dark .mi {
      background: var(--bg-input);
      color: var(--td);
      border-color: var(--border-col);
    }

    body.dark .sp-c {
      border-color: var(--border-col);
    }

    body.dark .sp-c .sp-nm {
      color: var(--td);
    }

    body.dark .rt {
      background: var(--pbg);
    }

    body.dark .rt-tab {
      color: var(--tm);
    }

    body.dark .rt-tab.on {
      background: var(--bg-card);
    }

    body.dark .p-disp {
      background: var(--pbg);
    }

    body.dark .p-note {
      color: var(--tm);
    }

    body.dark .d-lbl {
      color: var(--td);
    }

    body.dark .empty-ttl {
      color: var(--td);
    }

    body.dark .empty {
      color: var(--tm);
    }

    body.dark #bModal {
      background: rgba(0, 20, 18, .7);
    }

    .dr {
      display: flex;
      gap: 9px;
      margin-bottom: 14px;
    }

    .dr .mi {
      margin-bottom: 0;
    }

    .bk-btn-mini {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: var(--pbg);
      border: none;
      color: var(--teal);
      font-size: 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .2s;
      flex-shrink: 0;
    }

    .bk-btn-mini:hover {
      background: var(--tab-bg);
      transform: scale(1.08);
    }

    .bk-btn-mini.saved {
      background: var(--teal);
      color: #fff;
    }

    .bk-btn-mini i {
      transition: transform .2s;
    }

    .bk-btn-mini i.anim {
      animation: bounce .4s ease;
    }

    body.dark .bk-btn-mini {
      background: var(--pbg);
    }

    body.dark .bk-btn-mini:hover {
      background: var(--tab-bg);
    }

    body.dark .bk-btn-mini.saved {
      background: var(--teal);
    }

    .fav-card {
      background: #fff;
      border-radius: 17px;
      overflow: hidden;
      margin-bottom: 13px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
      cursor: pointer;
      transition: transform .2s;
    }

    .fav-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 16px rgba(0, 0, 0, .1);
    }

    .fav-card-img {
      width: 100%;
      height: 140px;
      overflow: hidden;
    }

    .fav-card-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .fav-card-body {
      padding: 14px 16px;
    }

    .fav-card-top {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 12px;
    }

    .fav-card-nm {
      font-size: 15px;
      font-weight: 800;
      color: var(--td);
      margin-bottom: 4px;
    }

    .fav-card-price {
      font-size: 12px;
      color: var(--tm);
    }

    .fav-btn-mini {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: var(--pbg);
      border: none;
      color: #f59e0b;
      font-size: 17px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .2s;
      flex-shrink: 0;
    }

    .fav-btn-mini:hover {
      background: var(--tab-bg);
      transform: scale(1.08);
    }

    .fav-btn-mini.saved {
      background: #f59e0b;
      color: #fff;
    }

    .fav-card-btn {
      width: 100%;
      padding: 10px;
      background: var(--teal);
      color: #fff;
      border: none;
      border-radius: 11px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: all .2s;
    }

    .fav-card-btn:hover {
      background: var(--pdark);
    }

    body.dark .fav-card {
      background: var(--bg-card);
    }

    body.dark .fav-card-nm {
      color: var(--td);
    }

    body.dark .fav-card-price {
      color: var(--tm);
    }

    body.dark .fav-btn-mini {
      background: var(--pbg);
    }

    body.dark .fav-btn-mini:hover {
      background: var(--tab-bg);
    }

    body.dark .fav-btn-mini.saved {
      background: #f59e0b;
    }
  </style>
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

    <div class="screen" id="bookings">
      <div class="bk-scroll">
        <div class="bk-hdr">
          <div class="bk-hdr-row">
            <div>
              <div class="bk-ttl">My Bookings</div>
              <div class="bk-sub">Manage all your service requests</div>
            </div>
            <div
              style="width:38px;height:38px;background:rgba(255,255,255,.18);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;"
              onclick="openModal(null)"><svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                <path d="M12 5v14M5 12h14" stroke="#fff" stroke-width="2.5" stroke-linecap="round" />
              </svg></div>
          </div>
        </div>
        <div class="tabs">
          <div class="tab on" onclick="switchTab(this,'pending')">Pending</div>
          <div class="tab" onclick="switchTab(this,'progress')">In Progress</div>
          <div class="tab" onclick="switchTab(this,'done')">Done</div>
          <div class="tab" onclick="switchTab(this,'bookmarks')">Bookmarks</div>
          <div class="tab" onclick="switchTab(this,'favorites')">Favorites</div>
        </div>
        <div class="bk-body" id="bkBody"></div>
      </div>
      <div id="navContainer"></div>
    </div>

    <div id="bModal" onclick="modalBg(event)">
      <div class="m-sheet" id="mSheet">
        <div class="m-hand"></div>
        <div class="m-head">
          <button class="bk" type="button" onclick="closeModal()" aria-label="Back">
            <i class="bi bi-arrow-left"></i>
          </button>
          <div class="m-ttl">Book a Service</div>
        </div>

        <label class="ml">Select Service</label>
        <div class="sp-grid" id="spGrid"></div>

        <label class="ml">Pricing Type</label>
        <div class="rt">
          <div class="rt-tab on" onclick="setRate('hourly',this)">⏱ Hourly Rate</div>
          <div class="rt-tab" onclick="setRate('flat',this)">💵 Flat Rate</div>
        </div>

        <div id="durSec">
          <label class="ml">Duration</label>
          <div class="dur-row">
            <button class="d-btn" onclick="chDur(-1)">−</button>
            <div class="d-lbl" id="durLbl">2 hours</div>
            <button class="d-btn" onclick="chDur(1)">+</button>
          </div>
        </div>

        <div class="p-disp">
          <div class="p-lbl" id="pLbl">Estimated Total</div>
          <div class="p-amt" id="pAmt">₱0</div>
          <div class="p-note" id="pNote">Based on hourly rate × duration</div>
        </div>

        <label class="ml">Schedule</label>
        <div class="dr">
          <input type="date" class="mi" id="bDate" style="flex:1;">
          <input type="time" class="mi" id="bTime" style="flex:1;">
        </div>

        <label class="ml">Service Address</label>
        <input type="text" class="mi" id="bAddr" placeholder="Enter your complete address"
          onkeydown="if(event.key==='Enter')document.getElementById('bDesc').focus()">

        <label class="ml">Description / Special Instructions</label>
        <textarea class="mi ta" id="bDesc" placeholder="Describe what needs to be done..."></textarea>

        <button class="btn-p" onclick="submitB()">Confirm Booking</button>
      </div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>initTheme();</script>
  <script>
    let curTab = 'pending';
    let bS = { svc: 'Cleaning', rateType: 'hourly', dur: 2 };

    document.getElementById('navContainer').innerHTML = `
  <div class="bnav">
    <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
    <div class="ni on"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
    <div class="ni" onclick="goPage('bookings.php?newbooking=1')" style="cursor:pointer;"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
    <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
    <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
  </div>`;

    const spGrid = document.getElementById('spGrid');
    Object.entries(SVCS).forEach(([name, data]) => {
      const imgUrl = SVC_IMGS[data.key];
      const isDefault = name === 'Cleaning';
      const isFavorite = _loadFavorites().includes(name);
      spGrid.innerHTML += `
    <div class="sp-c${isDefault ? ' sel' : ''}" onclick="selSvc(this,'${name}')">
      <div class="sp-star${isFavorite ? ' starred' : ''}" onclick="event.stopPropagation();toggleFavoriteService('${name}')" title="${isFavorite ? 'Remove from favorites' : 'Add to favorites'}">
        <i class="bi bi-star${isFavorite ? '-fill' : ''}"></i>
      </div>
      <img class="sp-img" src="${imgUrl}" alt="${name}">
      <div class="sp-nm">${name === 'Appliance Repair' ? 'Appliance' : name}</div>
    </div>`;
    });

    function renderBk() {
      let list;
      const el = document.getElementById('bkBody');

      if (curTab === 'favorites') {
        const favServices = _loadFavorites();
        if (!favServices.length) {
          el.innerHTML = `<div class="empty">
        <svg viewBox="0 0 64 64" fill="none" style="width:60px;height:60px"><circle cx="32" cy="32" r="30" fill="#fef3c7"/><path d="M32 12l4 12h12l-10 8 4 12-10-8-10 8 4-12-10-8h12l4-12z" fill="#f59e0b" stroke="#d97706" stroke-width="2"/></svg>
        <div class="empty-ttl" style="margin-top:12px;">No favorite services</div>
        <p style="font-size:12px;">Star your favorite services to quickly book them</p>
      </div>`;
          return;
        }

        el.innerHTML = favServices.map(svcName => {
          const svc = SVCS[svcName];
          if (!svc) return '';
          const imgSrc = SVC_IMGS[svc.key];
          return `<div class="fav-card" onclick="openModal('${svcName}')">
        <div class="fav-card-img"><img src="${imgSrc}" alt="${svcName}"></div>
        <div class="fav-card-body">
          <div class="fav-card-top">
            <div>
              <div class="fav-card-nm">${svcName}</div>
              <div class="fav-card-price">₱${svc.hr}/hr · ₱${svc.flat} flat</div>
            </div>
            <button class="fav-btn-mini saved" onclick="event.stopPropagation();toggleFavorite('${svcName}')" title="Remove from favorites">
              <i class="bi bi-star-fill"></i>
            </button>
          </div>
          <button class="fav-card-btn" onclick="event.stopPropagation();openModal('${svcName}')">
            <i class="bi bi-calendar-plus"></i> Book Now
          </button>
        </div>
      </div>`;
        }).join('');
        return;
      }

      if (curTab === 'bookmarks') {
        const bookmarkedIds = _loadBookmarks();
        list = window.HE.bookings.filter(b => bookmarkedIds.includes(b.id));
      } else {
        list = window.HE.bookings.filter(b => b.status === curTab);
      }

      const empIcons = { pending: '<svg viewBox="0 0 64 64" fill="none" style="width:60px;height:60px"><circle cx="32" cy="32" r="30" fill="#f0fdfa"/><path d="M20 32h24M32 20v24" stroke="#5eead4" stroke-width="3" stroke-linecap="round"/><rect x="18" y="18" width="28" height="28" rx="6" stroke="#7c3aed" stroke-width="2" fill="none"/></svg>', progress: '<svg viewBox="0 0 64 64" fill="none" style="width:60px;height:60px"><circle cx="32" cy="32" r="30" fill="#dbeafe"/><path d="M20 32a12 12 0 1024 0 12 12 0 00-24 0z" stroke="#2563eb" stroke-width="2"/><path d="M32 26v6l4 4" stroke="#2563eb" stroke-width="2" stroke-linecap="round"/></svg>', done: '<svg viewBox="0 0 64 64" fill="none" style="width:60px;height:60px"><circle cx="32" cy="32" r="30" fill="#d1fae5"/><path d="M22 32l8 8 14-14" stroke="#059669" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>', bookmarks: '<svg viewBox="0 0 64 64" fill="none" style="width:60px;height:60px"><circle cx="32" cy="32" r="30" fill="#fef3c7"/><path d="M24 18h16a2 2 0 012 2v26l-10-6-10 6V20a2 2 0 012-2z" fill="#f59e0b" stroke="#d97706" stroke-width="2"/></svg>' };
      const empLbl = { pending: 'No pending bookings', progress: 'No services in progress', done: 'No completed bookings', bookmarks: 'No bookmarked bookings' };
      const empDesc = { pending: 'Tap the + button to book a new service', progress: 'When services start, they\'ll appear here', done: 'Completed services will show here', bookmarks: 'Tap the bookmark icon on any booking to save it' };
      if (!list.length) {
        el.innerHTML = `<div class="empty">${empIcons[curTab]}<div class="empty-ttl" style="margin-top:12px;">${empLbl[curTab]}</div><p style="font-size:12px;">${empDesc[curTab]}</p></div>`;
        return;
      }
      el.innerHTML = list.map(b => {
        const imgSrc = SVC_IMGS[b.key] || SVC_IMGS.cleaning;
        const isBookmarked = _loadBookmarks().includes(b.id);
        return `<div class="bc">
      <div class="bc-hdr">
        <div class="bc-ic"><img src="${imgSrc}" alt="${b.svc}"></div>
        <div style="flex:1"><div class="bc-nm">${b.svc}</div><div class="bc-dt"><i class="bi bi-calendar3" style="font-size:10px;margin-right:3px;"></i>${b.date} · ${b.time}</div></div>
        <div style="display:flex;align-items:center;gap:8px;">
          <button class="bk-btn-mini${isBookmarked ? ' saved' : ''}" onclick="event.stopPropagation();toggleBookmark(${b.id})" title="${isBookmarked ? 'Remove bookmark' : 'Save booking'}">
            <i class="bi bi-bookmark${isBookmarked ? '-fill' : ''}"></i>
          </button>
          <span class="badge b-${b.status}">${b.status === 'pending' ? 'Pending' : b.status === 'progress' ? 'In Progress' : 'Done'}</span>
        </div>
      </div>
      <div class="bc-det">
        <div>
          <div class="bc-txt"><svg viewBox="0 0 16 16" width="11" height="11" fill="none"><path d="M8 1a5 5 0 00-5 5c0 3.5 5 9 5 9s5-5.5 5-9a5 5 0 00-5-5zm0 7a2 2 0 110-4 2 2 0 010 4z" fill="#6b7280"/></svg>${b.addr}</div>
          <div class="bc-txt">${b.rateType === 'hourly' ? `<svg viewBox="0 0 16 16" width="11" height="11" fill="none"><circle cx="8" cy="8" r="6" stroke="#6b7280" stroke-width="1.5"/><path d="M8 5v3l2 2" stroke="#6b7280" stroke-width="1.5" stroke-linecap="round"/></svg> ${b.dur}h × ₱${SVCS[b.svc]?.hr || 0}/hr` : '<svg viewBox="0 0 16 16" width="11" height="11" fill="none"><rect x="2" y="4" width="12" height="9" rx="1.5" stroke="#6b7280" stroke-width="1.5"/><path d="M5 4V3a3 3 0 016 0v1" stroke="#6b7280" stroke-width="1.5"/></svg> Flat Rate'}</div>
        </div>
        <div class="bc-p">₱${b.price.toLocaleString()}</div>
      </div>
    </div>`;
      }).join('');
    }

    function switchTab(el, tab) {
      document.querySelectorAll('.tab').forEach(t => t.classList.remove('on'));
      el.classList.add('on'); curTab = tab; renderBk();
    }

    function openModal(preService) {
      if (preService) { bS.svc = preService; }
      document.getElementById('bModal').classList.add('on');
      document.getElementById('bDate').value = new Date().toISOString().split('T')[0];
      document.getElementById('bTime').value = '09:00';
      document.querySelectorAll('.sp-c').forEach(c => {
        const nm = c.querySelector('.sp-nm').textContent;
        const fullName = nm === 'Appliance' ? 'Appliance Repair' : nm;
        c.classList.toggle('sel', fullName === bS.svc);
      });
      updPrice();
    }

    function modalBg(e) { if (e.target === document.getElementById('bModal')) closeModal(); }
    function closeModal() { document.getElementById('bModal').classList.remove('on'); }

    function selSvc(el, name) {
      document.querySelectorAll('.sp-c').forEach(c => c.classList.remove('sel'));
      el.classList.add('sel'); bS.svc = name; updPrice();
    }

    function setRate(type, el) {
      document.querySelectorAll('.rt-tab').forEach(t => t.classList.remove('on'));
      el.classList.add('on'); bS.rateType = type;
      document.getElementById('durSec').style.display = type === 'hourly' ? 'block' : 'none';
      updPrice();
    }

    function chDur(d) {
      bS.dur = Math.max(1, Math.min(12, bS.dur + d));
      document.getElementById('durLbl').textContent = bS.dur + (bS.dur === 1 ? ' hour' : ' hours');
      updPrice();
    }

    function updPrice() {
      const s = SVCS[bS.svc];
      if (!s) return;
      let amt, lbl, note;
      if (bS.rateType === 'hourly') { amt = s.hr * bS.dur; lbl = 'Estimated Total'; note = `₱${s.hr}/hr × ${bS.dur} hour${bS.dur > 1 ? 's' : ''}`; }
      else { amt = s.flat; lbl = 'Flat Rate Price'; note = 'Fixed price — no surprises'; }
      document.getElementById('pAmt').textContent = '₱' + amt.toLocaleString();
      document.getElementById('pLbl').textContent = lbl;
      document.getElementById('pNote').textContent = note;
    }

    function submitB() {
      const date = document.getElementById('bDate').value;
      const time = document.getElementById('bTime').value;
      const addr = document.getElementById('bAddr').value || 'No address provided';
      const desc = document.getElementById('bDesc').value || '';
      const s = SVCS[bS.svc];
      const price = bS.rateType === 'hourly' ? s.hr * bS.dur : s.flat;
      const dateStr = date ? fmtDate(date) : 'TBD';
      const timeStr = time ? fmtTime(time) : 'TBD';
      window.HE.bookings.unshift({ id: window.HE.nid++, svc: bS.svc, key: s.key, status: 'pending', date: dateStr, time: timeStr, price, addr, desc, rateType: bS.rateType, dur: bS.rateType === 'hourly' ? bS.dur : null });
      document.getElementById('bAddr').value = '';
      document.getElementById('bDesc').value = '';
      closeModal();
      curTab = 'pending';
      document.querySelectorAll('.tab').forEach((t, i) => t.classList.toggle('on', i === 0));
      renderBk();
    }

    renderBk();
    const preService = `<?= $preService ?>`;
    if (preService) { setTimeout(() => openModal(preService), 500); }
    else if (<?= $newBooking ? 'true' : 'false' ?>) { setTimeout(() => openModal(null), 500); }

    function _loadBookmarks() {
      try { return JSON.parse(localStorage.getItem('he_bookmarks') || '[]'); } catch (e) { return []; }
    }
    function _saveBookmarks(ids) {
      localStorage.setItem('he_bookmarks', JSON.stringify(ids));
    }
    function toggleBookmark(id) {
      let bms = _loadBookmarks();
      const had = bms.includes(id);
      bms = had ? bms.filter(x => x !== id) : [...bms, id];
      _saveBookmarks(bms);
      renderBk();

      const msg = had ? 'Bookmark removed' : '🔖 Booking saved!';
      console.log(msg); 
      return !had;
    }

    function _loadFavorites() {
      try { return JSON.parse(localStorage.getItem('he_favorites') || '[]'); } catch (e) { return []; }
    }
    function _saveFavorites(services) {
      localStorage.setItem('he_favorites', JSON.stringify(services));
    }
    function toggleFavorite(serviceName) {
      let favs = _loadFavorites();
      const had = favs.includes(serviceName);
      favs = had ? favs.filter(x => x !== serviceName) : [...favs, serviceName];
      _saveFavorites(favs);
      renderBk();
      const msg = had ? 'Removed from favorites' : '⭐ Added to favorites!';
      console.log(msg);
      return !had;
    }

    function toggleFavoriteService(serviceName) {
      let favs = _loadFavorites();
      const had = favs.includes(serviceName);
      favs = had ? favs.filter(x => x !== serviceName) : [...favs, serviceName];
      _saveFavorites(favs);

      const stars = document.querySelectorAll('.sp-star');
      stars.forEach(star => {
        const starServiceName = star.parentElement.querySelector('.sp-nm').textContent;
        const fullName = starServiceName === 'Appliance' ? 'Appliance Repair' : starServiceName;
        if (fullName === serviceName) {
          const isFav = favs.includes(serviceName);
          star.classList.toggle('starred', isFav);
          const icon = star.querySelector('i');
          icon.className = isFav ? 'bi bi-star-fill' : 'bi bi-star';
        }
      });

      const msg = had ? 'Removed from favorites' : '⭐ Added to favorites!';
      console.log(msg);
      return !had;
    }


  </script>
</body>

</html>
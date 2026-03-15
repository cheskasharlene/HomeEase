<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
  header('Location: index.php');
  exit;
}
$adminName = htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/admindashboard.css" rel="stylesheet">
  <style>
    /* ── Extra styles not in admindashboard.css ── */
    .screen {
      display: none;
      flex-direction: column;
      position: absolute;
      inset: 0;
      overflow: hidden;
      background: var(--bg-screen);
    }

    .screen.active {
      display: flex;
    }

    .ni.on {
      color: var(--ni-on);
    }

    .ni {
      color: var(--ni-idle);
    }

    .sec-pad {
      padding: 14px 18px 0;
    }

    .badge-red {
      background: #fee2e2;
      color: #dc2626;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
    }

    .badge-green {
      background: #d1fae5;
      color: #059669;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
    }

    .badge-blue {
      background: #dbeafe;
      color: #2563eb;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
    }

    .badge-amber {
      background: #fef3c7;
      color: #d97706;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
    }

    .badge-gray {
      background: #f3f4f6;
      color: #6b7280;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
    }

    .svc-row {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 18px;
      border-bottom: 1px solid var(--border-col);
      cursor: pointer;
      transition: background .15s;
    }

    .svc-row:last-child {
      border-bottom: none;
    }

    .svc-row:hover {
      background: var(--teal-bg);
    }

    .svc-ic-sm {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: var(--teal-mid);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .toggle-sw {
      width: 44px;
      height: 24px;
      border-radius: 12px;
      position: relative;
      cursor: pointer;
      transition: background .2s;
      flex-shrink: 0;
    }

    .toggle-sw.on {
      background: var(--teal);
    }

    .toggle-sw.off {
      background: #e5e7eb;
    }

    body.dark .toggle-sw.off {
      background: #374151;
    }

    .toggle-sw::after {
      content: '';
      position: absolute;
      top: 3px;
      left: 3px;
      width: 18px;
      height: 18px;
      background: #fff;
      border-radius: 50%;
      transition: transform .2s;
      box-shadow: 0 1px 3px rgba(0, 0, 0, .2);
    }

    .toggle-sw.on::after {
      transform: translateX(20px);
    }

    .offer-list-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 12px 18px;
      border-bottom: 1px solid var(--border-col);
      cursor: pointer;
    }

    .offer-list-item:last-child {
      border-bottom: none;
    }

    .offer-list-item:hover {
      background: var(--teal-bg);
    }

    .offer-ic {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      background: #fef3c7;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      flex-shrink: 0;
    }

    .more-row {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 15px 18px;
      border-bottom: 1px solid var(--border-col);
      cursor: pointer;
      transition: background .15s;
    }

    .more-row:last-child {
      border-bottom: none;
    }

    .more-row:hover {
      background: var(--teal-bg);
    }

    .more-ic {
      width: 40px;
      height: 40px;
      border-radius: 13px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 19px;
      flex-shrink: 0;
    }

    .more-nm {
      font-size: 14px;
      font-weight: 700;
      color: var(--txt-primary);
    }

    .more-sub {
      font-size: 11px;
      color: var(--txt-muted);
      margin-top: 1px;
    }

    .more-arrow {
      margin-left: auto;
      color: #d1d5db;
      font-size: 15px;
    }

    body.dark .more-row:hover {
      background: var(--teal-bg);
    }

    /* Status filter tabs */
    .status-tabs {
      display: flex;
      gap: 6px;
      padding: 12px 18px;
      overflow-x: auto;
      scrollbar-width: none;
      flex-shrink: 0;
    }

    .status-tabs::-webkit-scrollbar {
      display: none;
    }

    .stab {
      padding: 6px 13px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 700;
      white-space: nowrap;
      cursor: pointer;
      border: 2px solid var(--border-col);
      color: var(--txt-muted);
      background: var(--bg-card);
      transition: all .2s;
    }

    .stab.on {
      background: var(--teal);
      border-color: var(--teal);
      color: #fff;
    }

    /* User avatar */
    .user-av {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--teal), #E8960F);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 15px;
      font-weight: 800;
      flex-shrink: 0;
    }

    /* Revenue mini bars */
    .rev-bar-wrap {
      height: 64px;
      display: flex;
      align-items: flex-end;
      gap: 4px;
      margin-top: 10px;
    }

    .rev-bar-item {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
    }

    .rev-bar-fill {
      width: 100%;
      border-radius: 4px 4px 0 0;
      min-height: 3px;
      background: var(--teal);
      opacity: .8;
      transition: height .5s;
    }

    .rev-bar-lbl {
      font-size: 8px;
      color: var(--txt-muted);
      font-weight: 700;
    }

    /* Donut chart */
    .donut-wrap svg {
      transform: rotate(-90deg);
    }


    .notif-target {
      display: flex;
      gap: 8px;
      margin-bottom: 12px;
    }

    .notif-target .nt-opt {
      flex: 1;
      padding: 9px;
      text-align: center;
      border-radius: 10px;
      border: 2px solid var(--border-col);
      font-size: 12px;
      font-weight: 700;
      color: var(--txt-muted);
      cursor: pointer;
      transition: all .2s;
    }

    .notif-target .nt-opt.on {
      background: var(--teal);
      border-color: var(--teal);
      color: #fff;
    }
  </style>
</head>

<body>

  <div class="shell" id="app">
    <!-- Loading splash -->
    <div id="ml" class="on">
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


    <div id="toastBox"></div>

    <div class="screen active" id="sc-overview">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Welcome back, <?= $adminName ?></div>
          <div class="a-ttl">Dashboard</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="bi bi-moon-fill"
              id="dmIcon"></i></button>
          <button class="hdr-btn" onclick="loadOverview()" title="Refresh"><i
              class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="a-scroll" id="overview-scroll">
 
        <div class="stat-grid" id="statGrid">
          <div class="stat-card">
            <div class="stat-ic teal"><i class="bi bi-people-fill"></i></div>
            <div>
              <div class="stat-val" id="st-users">–</div>
              <div class="stat-lbl">Total Users</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic green"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
              <div class="stat-val" id="st-bookings">–</div>
              <div class="stat-lbl">Bookings</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic amber"><i class="bi bi-currency-dollar"></i></div>
            <div>
              <div class="stat-val" id="st-revenue">–</div>
              <div class="stat-lbl">Revenue</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic blue"><i class="bi bi-person-badge-fill"></i></div>
            <div>
              <div class="stat-val" id="st-workers">–</div>
              <div class="stat-lbl">Workers</div>
            </div>
          </div>
        </div>

   
        <div class="quick-actions">
          <button class="qa-btn" onclick="showTab('bookings')">
            <div class="qa-ic" style="background:#d1fae5;color:#059669"><i class="bi bi-calendar3"></i></div><span
              class="qa-lbl">Bookings</span>
          </button>
          <button class="qa-btn" onclick="showTab('workers')">
            <div class="qa-ic" style="background:#dbeafe;color:#2563eb"><i class="bi bi-person-gear"></i></div><span
              class="qa-lbl">Workers</span>
          </button>
          <button class="qa-btn" onclick="showTab('users')">
            <div class="qa-ic" style="background:#fef3c7;color:#d97706"><i class="bi bi-people"></i></div><span
              class="qa-lbl">Users</span>
          </button>
          <button class="qa-btn" onclick="showTab('more')">
            <div class="qa-ic" style="background:#f3e8ff;color:#7c3aed"><i class="bi bi-grid-fill"></i></div><span
              class="qa-lbl">More</span>
          </button>
        </div>

  
        <div class="chart-card">
          <div class="sec-hdr">
            <div class="sec-ttl">Revenue (₱)</div><span id="revTotal"
              style="font-size:12px;font-weight:700;color:var(--teal);">Loading...</span>
          </div>
          <div class="rev-bar-wrap" id="revChart"></div>
        </div>

  
        <div class="chart-card" style="margin-top:12px;">
          <div class="sec-ttl">Booking Status</div>
          <div class="donut-wrap">
            <svg class="donut-svg" viewBox="0 0 80 80" id="donutSvg">
              <circle cx="40" cy="40" r="30" fill="none" stroke="var(--border-col)" stroke-width="12" />
            </svg>
            <div class="donut-legend" id="donutLegend"></div>
          </div>
        </div>


        <div class="sec-pad">
          <div class="sec-hdr">
            <div class="sec-ttl">Recent Bookings</div>
            <span onclick="showTab('bookings')"
              style="font-size:12px;font-weight:700;color:var(--teal);cursor:pointer;">See all</span>
          </div>
          <div class="card" id="recentBookings">
            <div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite;"></i>
              <p>Loading...</p>
            </div>
          </div>
        </div>
        <div style="height:20px;"></div>
      </div>
    </div>


    <div class="screen" id="sc-bookings">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Bookings</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="loadBookings()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="bkSearch"
          placeholder="Search by user, service, address..." oninput="debounce(loadBookings,400)()"></div>
      <div class="status-tabs">
        <div class="stab on" data-bk="all" onclick="setBkFilter(this,'all')">All</div>
        <div class="stab" data-bk="pending" onclick="setBkFilter(this,'pending')">Pending</div>
        <div class="stab" data-bk="progress" onclick="setBkFilter(this,'progress')">In Progress</div>
        <div class="stab" data-bk="done" onclick="setBkFilter(this,'done')">Done</div>
        <div class="stab" data-bk="cancelled" onclick="setBkFilter(this,'cancelled')">Cancelled</div>
      </div>
      <div class="a-scroll" id="bk-scroll" style="padding:12px 18px 80px;">
        <div id="bkList">
          <div class="empty-state">
            <p>Loading...</p>
          </div>
        </div>
      </div>
    </div>


    <div class="screen" id="sc-workers">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Workers</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="openWorkerSheet(null)" title="Add worker"><i
              class="bi bi-plus-lg"></i></button>
          <button class="hdr-btn" onclick="loadWorkers()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="wkSearch"
          placeholder="Search workers..." oninput="debounce(loadWorkers,400)()"></div>
      <div class="a-scroll" id="wk-scroll" style="padding:12px 18px 80px;">
        <div id="wkList">
          <div class="empty-state">
            <p>Loading...</p>
          </div>
        </div>
      </div>
    </div>


    <div class="screen" id="sc-users">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Users</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="openNotifSheet(0)" title="Broadcast notification"><i
              class="bi bi-megaphone-fill"></i></button>
          <button class="hdr-btn" onclick="loadUsers()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="usSearch" placeholder="Search users..."
          oninput="debounce(loadUsers,400)()"></div>
      <div class="a-scroll" id="us-scroll" style="padding:12px 18px 80px;">
        <div id="usList">
          <div class="empty-state">
            <p>Loading...</p>
          </div>
        </div>
      </div>
    </div>

    <div class="screen" id="sc-more">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Admin</div>
          <div class="a-ttl">More</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill" id="dmIcon2"></i></button>
        </div>
      </div>
      <div class="a-scroll" id="more-scroll">
 
        <div class="sec-pad">
          <div class="sec-hdr">
            <div class="sec-ttl">Services</div>
            <button onclick="openSvcSheet(null)"
              style="background:var(--teal);color:#fff;border:none;border-radius:20px;padding:5px 13px;font-size:12px;font-weight:700;cursor:pointer;"><i
                class="bi bi-plus-lg"></i> Add</button>
          </div>
          <div class="card" id="svcList">
            <div class="empty-state">
              <p>Loading...</p>
            </div>
          </div>
        </div>

   
        <div class="sec-pad" style="margin-top:14px;">
          <div class="sec-hdr">
            <div class="sec-ttl">Special Offers</div>
            <button onclick="openOfferSheet(null)"
              style="background:var(--teal);color:#fff;border:none;border-radius:20px;padding:5px 13px;font-size:12px;font-weight:700;cursor:pointer;"><i
                class="bi bi-plus-lg"></i> Add</button>
          </div>
          <div class="card" id="offerList">
            <div class="empty-state">
              <p>Loading...</p>
            </div>
          </div>
        </div>


        <div class="sec-pad" style="margin-top:14px;">
          <div class="sec-ttl">Admin</div>
          <div class="card">
            <div class="more-row" onclick="openNotifSheet(0)">
              <div class="more-ic" style="background:#fef3c7;color:#d97706;"><i class="bi bi-megaphone-fill"></i></div>
              <div>
                <div class="more-nm">Broadcast Notification</div>
                <div class="more-sub">Send to all users</div>
              </div>
              <i class="bi bi-chevron-right more-arrow"></i>
            </div>
            <div class="more-row" onclick="doLogout()">
              <div class="more-ic" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-box-arrow-right"></i></div>
              <div>
                <div class="more-nm" style="color:#dc2626;">Logout</div>
                <div class="more-sub">Sign out of admin portal</div>
              </div>
              <i class="bi bi-chevron-right more-arrow"></i>
            </div>
          </div>
        </div>
        <div style="height:20px;"></div>
      </div>
    </div>

    <div class="bnav">
      <div class="ni on" id="nav-overview" onclick="showTab('overview')"><i class="bi bi-grid-1x2-fill"></i><span
          class="nl">Overview</span></div>
      <div class="ni" id="nav-bookings" onclick="showTab('bookings')"><i class="bi bi-calendar-check-fill"></i><span
          class="nl">Bookings</span></div>
      <div class="ni" id="nav-workers" onclick="showTab('workers')"><i class="bi bi-person-badge-fill"></i><span
          class="nl">Workers</span></div>
      <div class="ni" id="nav-users" onclick="showTab('users')"><i class="bi bi-people-fill"></i><span
          class="nl">Users</span></div>
      <div class="ni" id="nav-more" onclick="showTab('more')"><i class="bi bi-grid-fill"></i><span
          class="nl">More</span></div>
    </div>


    <div class="sheet-ol" id="bkDetailOl" onclick="if(event.target===this)closeSheet('bkDetailOl')">
      <div class="sheet">
        <div class="sh-hand"></div>
        <div class="sh-hdr">
          <div class="sh-ttl">Booking Details</div>
          <button class="sh-close" onclick="closeSheet('bkDetailOl')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="bkDetailBody"></div>
      </div>
    </div>

 
    <div class="sheet-ol" id="wkSheetOl" onclick="if(event.target===this)closeSheet('wkSheetOl')">
      <div class="sheet">
        <div class="sh-hand"></div>
        <div class="sh-hdr">
          <div class="sh-ttl" id="wkSheetTtl">Add Worker</div>
          <button class="sh-close" onclick="closeSheet('wkSheetOl')"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="wkId">
        <div class="fg"><label class="fl">Full Name *</label><input class="fi" id="wkName"
            placeholder="e.g. Juan dela Cruz"></div>
        <div class="fg"><label class="fl">Specialty *</label>
          <select class="fi" id="wkSpecialty">
            <option value="Cleaning">Cleaning</option>
            <option value="Plumbing">Plumbing</option>
            <option value="Electrical">Electrical</option>
            <option value="Painting">Painting</option>
            <option value="Appliance Repair">Appliance Repair</option>
            <option value="Gardening">Gardening</option>
          </select>
        </div>
        <div class="fg"><label class="fl">Phone</label><input class="fi" id="wkPhone" placeholder="09xxxxxxxxx"
            type="tel"></div>
        <div class="fg-row">
          <div class="fg"><label class="fl">Availability</label>
            <select class="fi" id="wkAvail">
              <option value="available">Available</option>
              <option value="busy">Busy</option>
              <option value="offline">Offline</option>
            </select>
          </div>
          <div class="fg"><label class="fl">Status</label>
            <select class="fi" id="wkStatus">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
        <div class="fg-row">
          <div class="fg"><label class="fl">Rating (0-5)</label><input class="fi" id="wkRating" type="number" step="0.1"
              min="0" max="5" value="5.0"></div>
          <div class="fg"><label class="fl">Jobs Done</label><input class="fi" id="wkJobs" type="number" min="0"
              value="0"></div>
        </div>
        <div class="modal-btns">
          <button class="btn-p" onclick="saveWorker()">Save Worker</button>
          <button class="btn-danger" id="wkDelBtn" style="display:none;" onclick="deleteWorker()">Delete</button>
        </div>
      </div>
    </div>

  
    <div class="sheet-ol" id="usDetailOl" onclick="if(event.target===this)closeSheet('usDetailOl')">
      <div class="sheet">
        <div class="sh-hand"></div>
        <div class="sh-hdr">
          <div class="sh-ttl">User Details</div>
          <button class="sh-close" onclick="closeSheet('usDetailOl')"><i class="bi bi-x-lg"></i></button>
        </div>
        <div id="usDetailBody"></div>
      </div>
    </div>

   
    <div class="sheet-ol" id="svcSheetOl" onclick="if(event.target===this)closeSheet('svcSheetOl')">
      <div class="sheet">
        <div class="sh-hand"></div>
        <div class="sh-hdr">
          <div class="sh-ttl" id="svcSheetTtl">Add Service</div>
          <button class="sh-close" onclick="closeSheet('svcSheetOl')"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="svcId">
        <div class="fg-row">
          <div class="fg"><label class="fl">Icon (emoji)</label><input class="fi" id="svcIcon" placeholder="🔧"
              maxlength="5"></div>
          <div class="fg"><label class="fl">Name *</label><input class="fi" id="svcName" placeholder="e.g. Cleaning">
          </div>
        </div>
        <div class="fg"><label class="fl">Description</label><input class="fi" id="svcDesc"
            placeholder="Short description"></div>
        <div class="fg-row">
          <div class="fg"><label class="fl">Hourly Rate (₱)</label><input class="fi" id="svcHourly" type="number"
              min="0" value="0"></div>
          <div class="fg"><label class="fl">Flat Rate (₱)</label><input class="fi" id="svcFlat" type="number" min="0"
              value="0"></div>
        </div>
        <div class="fg-row">
          <div class="fg"><label class="fl">Min Hours</label><input class="fi" id="svcMinH" type="number" min="1"
              value="1"></div>
          <div class="fg"><label class="fl">Pricing Type</label>
            <select class="fi" id="svcPtype">
              <option value="both">Both</option>
              <option value="hourly">Hourly Only</option>
              <option value="flat">Flat Only</option>
            </select>
          </div>
        </div>
        <div class="modal-btns">
          <button class="btn-p" onclick="saveSvc()">Save Service</button>
          <button class="btn-danger" id="svcDelBtn" style="display:none;" onclick="deleteSvc()">Delete</button>
        </div>
      </div>
    </div>

  
    <div class="sheet-ol" id="offerSheetOl" onclick="if(event.target===this)closeSheet('offerSheetOl')">
      <div class="sheet">
        <div class="sh-hand"></div>
        <div class="sh-hdr">
          <div class="sh-ttl" id="offerSheetTtl">Add Offer</div>
          <button class="sh-close" onclick="closeSheet('offerSheetOl')"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="offerId">
        <div class="fg"><label class="fl">Offer Title *</label><input class="fi" id="offerTitle"
            placeholder="e.g. Summer Sale"></div>
        <div class="fg-row">
          <div class="fg"><label class="fl">Promo Code *</label><input class="fi" id="offerCode" placeholder="SUMMER20"
              style="text-transform:uppercase;"></div>
          <div class="fg"><label class="fl">Type</label>
            <select class="fi" id="offerType">
              <option value="percent">Percent %</option>
              <option value="flat">Flat ₱</option>
            </select>
          </div>
        </div>
        <div class="fg-row">
          <div class="fg"><label class="fl">Discount Value</label><input class="fi" id="offerVal" type="number" min="0"
              value="10"></div>
          <div class="fg"><label class="fl">Min Booking ₱</label><input class="fi" id="offerMin" type="number" min="0"
              value="0"></div>
        </div>
        <div class="fg-row">
          <div class="fg"><label class="fl">Max Uses (0=unlimited)</label><input class="fi" id="offerMaxUses"
              type="number" min="0" value="0"></div>
          <div class="fg"><label class="fl">Expires At</label><input class="fi" id="offerExpires" type="datetime-local">
          </div>
        </div>
        <div class="fg"><label class="fl">Description</label><input class="fi" id="offerDesc"
            placeholder="Brief description"></div>
        <div class="modal-btns">
          <button class="btn-p" onclick="saveOffer()">Save Offer</button>
          <button class="btn-danger" id="offerDelBtn" style="display:none;" onclick="deleteOffer()">Delete</button>
        </div>
      </div>
    </div>

   
    <div class="sheet-ol" id="notifSheetOl" onclick="if(event.target===this)closeSheet('notifSheetOl')">
      <div class="sheet">
        <div class="sh-hand"></div>
        <div class="sh-hdr">
          <div class="sh-ttl" id="notifSheetTtl">Send Notification</div>
          <button class="sh-close" onclick="closeSheet('notifSheetOl')"><i class="bi bi-x-lg"></i></button>
        </div>
        <input type="hidden" id="notifUserId">
        <div class="notif-target" id="notifTargetRow">
          <div class="nt-opt on" onclick="setNotifTarget(0)">All Users</div>
          <div class="nt-opt" id="notifSpecificOpt" style="display:none;">Specific User</div>
        </div>
        <div class="fg"><label class="fl">Title *</label><input class="fi" id="notifTitle"
            placeholder="e.g. Special Offer!"></div>
        <div class="fg"><label class="fl">Message *</label><textarea class="fi" id="notifMsg" rows="3"
            placeholder="Your message here..." style="resize:none;"></textarea></div>
        <div class="fg"><label class="fl">Icon</label>
          <select class="fi" id="notifIcon">
            <option value="cleaning">🧹 Cleaning</option>
            <option value="plumbing">🔧 Plumbing</option>
            <option value="electrical">⚡ Electrical</option>
            <option value="painting">🖌️ Painting</option>
            <option value="appliance">🔩 Appliance</option>
            <option value="gardening">🌿 Gardening</option>
          </select>
        </div>
        <div class="modal-btns"><button class="btn-p" onclick="sendNotification()">Send Notification</button></div>
      </div>
    </div>

  </div><!-- /.shell -->

  <script>

    const API = (section, action = 'list', extra = '') =>
      `api/admin_api.php?section=${section}&action=${action}${extra}`;

    async function api(section, action = 'list', body = null, extra = '') {
      const url = API(section, action, extra);
      const opts = body ? { method: 'POST', body } : { method: 'GET' };
      const res = await fetch(url, opts);
      return res.json();
    }

    function fd(obj) {
      const f = new FormData();
      for (const [k, v] of Object.entries(obj)) if (v !== undefined && v !== null) f.append(k, v);
      return f;
    }

    function toast(msg, type = 's') {
      const box = document.getElementById('toastBox');
      const t = document.createElement('div');
      t.className = `toast-n ${type}`;
      t.innerHTML = `<i class="bi bi-${type === 's' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>${msg}`;
      box.appendChild(t);
      setTimeout(() => t.remove(), 3200);
    }

    function confirm2(msg) { return window.confirm(msg); }

    function openSheet(id) { document.getElementById(id).classList.add('on'); }
    function closeSheet(id) { document.getElementById(id).classList.remove('on'); }

    const debounce = (fn, ms) => {
      let t;
      return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
    };

    function statusPill(s) {
      const map = { pending: 'badge-amber', progress: 'badge-blue', done: 'badge-green', cancelled: 'badge-gray', active: 'badge-green', inactive: 'badge-red', available: 'badge-green', busy: 'badge-amber', offline: 'badge-gray' };
      return `<span class="${map[s] || 'badge-gray'}">${s}</span>`;
    }

    function php(n) { return '₱' + parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

    function toggleDark() {
      document.body.classList.toggle('dark');
      localStorage.setItem('he_dark', document.body.classList.contains('dark') ? '1' : '0');
      document.querySelectorAll('#dmIcon,#dmIcon2').forEach(ic => {
        ic.className = document.body.classList.contains('dark') ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
      });
    }

    let curTab = 'overview';
    const tabMap = { overview: 'sc-overview', bookings: 'sc-bookings', workers: 'sc-workers', users: 'sc-users', more: 'sc-more' };
    const loadMap = { bookings: loadBookings, workers: loadWorkers, users: loadUsers, more: loadMore };

    function showTab(tab) {
      document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
      document.querySelectorAll('.ni').forEach(n => n.classList.remove('on'));
      document.getElementById(tabMap[tab]).classList.add('active');
      const navEl = document.getElementById('nav-' + tab);
      if (navEl) navEl.classList.add('on');
      curTab = tab;
      if (loadMap[tab]) loadMap[tab]();
    }

    async function loadOverview() {
      try {
        const data = await api('stats');
        if (!data.success) return;
        const s = data.stats;

        document.getElementById('st-users').textContent = s.total_users;
        document.getElementById('st-bookings').textContent = s.total_bookings;
        document.getElementById('st-revenue').textContent = '₱' + (s.total_revenue / 1000).toFixed(1) + 'k';
        document.getElementById('st-workers').textContent = s.active_workers;
        document.getElementById('revTotal').textContent = php(s.total_revenue);

        // Revenue chart
        const chart = document.getElementById('revChart');
        const revRows = s.revenue_chart || [];
        if (revRows.length) {
          const max = Math.max(...revRows.map(r => parseFloat(r.rev)), 1);
          chart.innerHTML = revRows.map(r => {
            const h = Math.max(4, Math.round((parseFloat(r.rev) / max) * 60));
            return `<div class="rev-bar-item">
          <div class="rev-bar-fill" style="height:${h}px;" title="${php(r.rev)}"></div>
          <div class="rev-bar-lbl">${r.mo}</div>
        </div>`;
          }).join('');
        } else {
          chart.innerHTML = '<div style="font-size:12px;color:var(--txt-muted);text-align:center;width:100%;padding:20px 0;">No revenue data yet</div>';
        }

        // Donut
        const bd = s.breakdown || {};
        const colors = { pending: '#f59e0b', progress: '#3b82f6', done: '#F5A623', cancelled: '#9ca3af' };
        const total = Object.values(bd).reduce((a, b) => a + b, 0) || 1;
        let offset = 0; const circ = 2 * Math.PI * 30;
        const svg = document.getElementById('donutSvg');
        const legend = document.getElementById('donutLegend');

        svg.innerHTML = '<circle cx="40" cy="40" r="30" fill="none" stroke="var(--border-col)" stroke-width="12"/>';
        legend.innerHTML = '';
        Object.entries(bd).forEach(([st, cnt]) => {
          const pct = cnt / total; const dash = pct * circ;
          const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
          circle.setAttribute('cx', '40'); circle.setAttribute('cy', '40'); circle.setAttribute('r', '30');
          circle.setAttribute('fill', 'none'); circle.setAttribute('stroke', colors[st] || '#e5e7eb');
          circle.setAttribute('stroke-width', '12');
          circle.setAttribute('stroke-dasharray', `${dash} ${circ}`);
          circle.setAttribute('stroke-dashoffset', `${-offset}`);
          svg.appendChild(circle);
          offset += dash;
          legend.innerHTML += `<div class="legend-item"><div class="legend-dot" style="background:${colors[st] || '#e5e7eb'}"></div>${st}: <strong>${cnt}</strong></div>`;
        });

        // Recent bookings
        const rb = document.getElementById('recentBookings');
        const recent = s.recent_bookings || [];
        if (!recent.length) { rb.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings yet</p></div>'; return; }
        rb.innerHTML = recent.map(b => `
      <div class="list-item" onclick="openBkDetail(${JSON.stringify(b).replace(/"/g, '&quot;')})">
        <div class="li-av" style="font-size:13px;">${(b.service || '?')[0]}</div>
        <div class="li-body"><div class="li-name">${b.service}</div><div class="li-sub">${b.user_name || '–'} · ${b.date}</div></div>
        <div class="li-right">${statusPill(b.status)}<span style="font-size:12px;font-weight:700;color:var(--teal);">${php(b.price)}</span></div>
      </div>`).join('');
      } catch (e) { console.error(e); }
    }

    let bkFilter = 'all';

    function setBkFilter(el, f) {
      document.querySelectorAll('.stab').forEach(e => e.classList.remove('on'));
      el.classList.add('on'); bkFilter = f; loadBookings();
    }

    async function loadBookings() {
      const search = (document.getElementById('bkSearch') || {}).value || '';
      const extra = `&status=${bkFilter}&search=${encodeURIComponent(search)}`;
      document.getElementById('bkList').innerHTML = '<div class="empty-state"><p>Loading...</p></div>';
      try {
        const data = await api('bookings', 'list', null, extra);
        if (!data.success) { document.getElementById('bkList').innerHTML = `<div class="empty-state"><p>${data.message}</p></div>`; return; }
        const bks = data.bookings || [];
        if (!bks.length) { document.getElementById('bkList').innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings found.</p></div>'; return; }
        document.getElementById('bkList').innerHTML = bks.map(b => `
      <div class="bk-card" onclick='openBkDetail(${JSON.stringify(b)})'>
        <div style="display:flex;align-items:flex-start;gap:11px;">
          <div style="width:42px;height:42px;border-radius:12px;background:var(--teal-mid);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
            ${svcEmoji(b.service)}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:700;color:var(--txt-primary);">${b.service}</div>
            <div style="font-size:11px;color:var(--txt-muted);margin-top:2px;">${b.user_name || '–'} · ${b.date} ${b.time_slot || ''}</div>
            <div style="font-size:11px;color:var(--txt-muted);">${b.address}</div>
          </div>
          <div style="text-align:right;flex-shrink:0;">
            ${statusPill(b.status)}
            <div class="bk-price" style="margin-top:4px;">${php(b.price)}</div>
          </div>
        </div>
      </div>`).join('');
      } catch (e) { document.getElementById('bkList').innerHTML = '<div class="empty-state"><p>Error loading bookings.</p></div>'; }
    }

    async function openBkDetail(b) {
      if (typeof b === 'string') b = JSON.parse(b);
      const body = document.getElementById('bkDetailBody');
      body.innerHTML = `
    <div class="detail-row"><span class="detail-lbl">Booking ID</span><span class="detail-val">#${b.id}</span></div>
    <div class="detail-row"><span class="detail-lbl">Service</span><span class="detail-val">${b.service}</span></div>
    <div class="detail-row"><span class="detail-lbl">Customer</span><span class="detail-val">${b.user_name || '–'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Phone</span><span class="detail-val">${b.user_phone || '–'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Date</span><span class="detail-val">${b.date} ${b.time_slot || ''}</span></div>
    <div class="detail-row"><span class="detail-lbl">Address</span><span class="detail-val">${b.address}</span></div>
    <div class="detail-row"><span class="detail-lbl">Price</span><span class="detail-val" style="color:var(--teal);font-size:15px;">${php(b.price)}</span></div>
    <div class="detail-row"><span class="detail-lbl">Status</span><span class="detail-val">${statusPill(b.status)}</span></div>
    ${b.technician_name ? `<div class="detail-row"><span class="detail-lbl">Technician</span><span class="detail-val">${b.technician_name}</span></div>` : ''}
    ${b.notes ? `<div class="detail-row"><span class="detail-lbl">Notes</span><span class="detail-val">${b.notes}</span></div>` : ''}
    <div style="margin-top:16px;">
      <div class="fl">Update Status</div>
      <div style="display:flex;gap:6px;flex-wrap:wrap;">
        ${['pending', 'progress', 'done', 'cancelled'].map(s => `
          <button onclick="updateBkStatus(${b.id},'${s}')" style="padding:7px 14px;border-radius:20px;border:2px solid;cursor:pointer;font-size:12px;font-weight:700;
            background:${b.status === s ? 'var(--teal)' : 'transparent'};
            color:${b.status === s ? '#fff' : 'var(--txt-muted)'};
            border-color:${b.status === s ? 'var(--teal)' : 'var(--border-col)'};">${s}</button>`).join('')}
      </div>
    </div>
    <div class="modal-btns" style="margin-top:14px;">
      <button class="btn-danger" onclick="deleteBk(${b.id})">Delete Booking</button>
    </div>`;
      openSheet('bkDetailOl');
    }

    async function updateBkStatus(id, status) {
      try {
        const data = await api('bookings', 'update_status', fd({ id, status }));
        if (data.success) { toast('Status updated to ' + status); closeSheet('bkDetailOl'); loadBookings(); }
        else toast(data.message || 'Failed', 'e');
      } catch (e) { toast('Error', 'e'); }
    }

    async function deleteBk(id) {
      if (!confirm2('Delete this booking?')) return;
      const data = await api('bookings', 'delete', fd({ id }));
      if (data.success) { toast('Booking deleted'); closeSheet('bkDetailOl'); loadBookings(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function loadWorkers() {
      const search = (document.getElementById('wkSearch') || {}).value || '';
      document.getElementById('wkList').innerHTML = '<div class="empty-state"><p>Loading...</p></div>';
      try {
        const data = await api('workers', 'list', null, `&search=${encodeURIComponent(search)}`);
        const workers = data.workers || [];
        if (!workers.length) { document.getElementById('wkList').innerHTML = '<div class="empty-state"><i class="bi bi-person-x"></i><p>No workers found.</p></div>'; return; }
        document.getElementById('wkList').innerHTML = workers.map(w => `
      <div class="list-item">
        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(w.name)}&background=FDECC8&color=F5A623&size=80" style="width:44px;height:44px;border-radius:50%;object-fit:cover;" alt="">
        <div class="li-body">
          <div class="li-name">${w.name}</div>
          <div class="li-sub">${w.specialty} · ${w.phone || 'No phone'}</div>
          <div style="display:flex;gap:5px;margin-top:4px;">${statusPill(w.availability)} ${statusPill(w.status)}</div>
        </div>
        <div class="li-right">
          <div class="act-btns">
            <button class="act-btn edit" onclick='openWorkerSheet(${JSON.stringify(w)})'><i class="bi bi-pencil-fill"></i></button>
            <button class="act-btn tog" onclick="toggleWorkerStatus(${w.id},this)"><i class="bi bi-${w.status === 'active' ? 'pause' : 'play'}-fill"></i></button>
            <button class="act-btn del" onclick="deleteWorkerById(${w.id})"><i class="bi bi-trash-fill"></i></button>
          </div>
          <div style="font-size:11px;color:var(--txt-muted);margin-top:4px;">⭐ ${parseFloat(w.rating || 0).toFixed(1)} · ${w.jobs_done || 0} jobs</div>
        </div>
      </div>`).join('');
      } catch (e) { document.getElementById('wkList').innerHTML = '<div class="empty-state"><p>Error loading workers.</p></div>'; }
    }

    function openWorkerSheet(w) {
      document.getElementById('wkId').value = w ? w.id : '';
      document.getElementById('wkName').value = w ? w.name : '';
      document.getElementById('wkSpecialty').value = w ? (w.specialty || 'Cleaning') : 'Cleaning';
      document.getElementById('wkPhone').value = w ? (w.phone || '') : '';
      document.getElementById('wkAvail').value = w ? (w.availability || 'available') : 'available';
      document.getElementById('wkStatus').value = w ? (w.status || 'active') : 'active';
      document.getElementById('wkRating').value = w ? parseFloat(w.rating || 5).toFixed(1) : '5.0';
      document.getElementById('wkJobs').value = w ? (w.jobs_done || 0) : 0;
      document.getElementById('wkSheetTtl').textContent = w ? 'Edit Worker' : 'Add Worker';
      document.getElementById('wkDelBtn').style.display = w ? 'block' : 'none';
      openSheet('wkSheetOl');
    }

    async function saveWorker() {
      const id = document.getElementById('wkId').value;
      const name = document.getElementById('wkName').value.trim();
      const specialty = document.getElementById('wkSpecialty').value;
      if (!name) { toast('Name required', 'e'); return; }
      const body = fd({ id: id || '', name, specialty, phone: document.getElementById('wkPhone').value, availability: document.getElementById('wkAvail').value, status: document.getElementById('wkStatus').value, rating: document.getElementById('wkRating').value, jobs_done: document.getElementById('wkJobs').value });
      const data = await api('workers', id ? 'edit' : 'add', body);
      if (data.success) { toast(id ? 'Worker updated' : 'Worker added'); closeSheet('wkSheetOl'); loadWorkers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function deleteWorker() {
      const id = document.getElementById('wkId').value;
      if (!confirm2('Delete this worker?')) return;
      const data = await api('workers', 'delete', fd({ id }));
      if (data.success) { toast('Worker deleted'); closeSheet('wkSheetOl'); loadWorkers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function deleteWorkerById(id) {
      if (!confirm2('Delete this worker?')) return;
      const data = await api('workers', 'delete', fd({ id }));
      if (data.success) { toast('Worker deleted'); loadWorkers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function toggleWorkerStatus(id, btn) {
      const data = await api('workers', 'toggle_status', fd({ id }));
      if (data.success) { toast(data.message); loadWorkers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function loadUsers() {
      const search = (document.getElementById('usSearch') || {}).value || '';
      document.getElementById('usList').innerHTML = '<div class="empty-state"><p>Loading...</p></div>';
      try {
        const data = await api('users', 'list', null, `&search=${encodeURIComponent(search)}`);
        const users = data.users || [];
        if (!users.length) { document.getElementById('usList').innerHTML = '<div class="empty-state"><i class="bi bi-people"></i><p>No users found.</p></div>'; return; }
        document.getElementById('usList').innerHTML = users.map(u => `
      <div class="list-item" onclick='openUserDetail(${JSON.stringify(u)})'>
        <div class="user-av">${(u.name || '?')[0].toUpperCase()}</div>
        <div class="li-body">
          <div class="li-name" style="${u.disabled ? 'text-decoration:line-through;color:var(--txt-muted);' : ''}">${u.name}</div>
          <div class="li-sub">${u.email}</div>
          <div class="li-sub">${u.booking_count} bookings · ${u.done_count} done</div>
        </div>
        <div class="li-right">
          ${u.disabled ? '<span class="badge-red">Disabled</span>' : '<span class="badge-green">Active</span>'}
          ${u.phone ? `<div style="font-size:11px;color:var(--txt-muted);">${u.phone}</div>` : ''}
        </div>
      </div>`).join('');
      } catch (e) { document.getElementById('usList').innerHTML = '<div class="empty-state"><p>Error.</p></div>'; }
    }

    function openUserDetail(u) {
      if (typeof u === 'string') u = JSON.parse(u);
      document.getElementById('usDetailBody').innerHTML = `
    <div style="text-align:center;margin-bottom:16px;">
      <div class="user-av" style="width:60px;height:60px;font-size:22px;margin:0 auto 8px;">${(u.name || '?')[0].toUpperCase()}</div>
      <div style="font-size:16px;font-weight:800;color:var(--txt-primary);">${u.name}</div>
      <div style="font-size:12px;color:var(--txt-muted);">${u.email}</div>
    </div>
    <div class="detail-row"><span class="detail-lbl">Phone</span><span class="detail-val">${u.phone || '–'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Address</span><span class="detail-val">${u.address || '–'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Total Bookings</span><span class="detail-val">${u.booking_count}</span></div>
    <div class="detail-row"><span class="detail-lbl">Completed</span><span class="detail-val">${u.done_count}</span></div>
    <div class="detail-row"><span class="detail-lbl">Account Status</span><span class="detail-val">${u.disabled ? '<span class="badge-red">Disabled</span>' : '<span class="badge-green">Active</span>'}</span></div>
    <div class="modal-btns" style="margin-top:16px;">
      <button class="btn-outline" onclick="toggleUserDisable(${u.id})">${u.disabled ? 'Enable Account' : 'Disable Account'}</button>
      <button class="btn-p" onclick="openNotifSheet(${u.id},'${u.name.replace(/'/g, "\\'")}')">Send Notification</button>
      <button class="btn-danger" onclick="deleteUser(${u.id})" style="margin-top:8px;">Delete User</button>
    </div>`;
      openSheet('usDetailOl');
    }

    async function toggleUserDisable(id) {
      const data = await api('users', 'toggle_disable', fd({ id }));
      if (data.success) { toast(data.message); closeSheet('usDetailOl'); loadUsers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function deleteUser(id) {
      if (!confirm2('Delete this user and all their data?')) return;
      const data = await api('users', 'delete', fd({ id }));
      if (data.success) { toast('User deleted'); closeSheet('usDetailOl'); loadUsers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function loadMore() {
      loadServices();
      loadOffers();
    }

    async function loadServices() {
      const data = await api('services');
      const svcs = data.services || [];
      document.getElementById('svcList').innerHTML = svcs.length ? svcs.map(s => `
    <div class="svc-row">
      <div class="svc-ic-sm">${s.icon || '🔧'}</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--txt-primary);">${s.name}</div>
        <div style="font-size:11px;color:var(--txt-muted);">${php(s.hourly_rate)}/hr · ${php(s.flat_rate)} flat</div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div class="toggle-sw ${s.active ? 'on' : 'off'}" onclick="toggleSvc(${s.id},this)" title="${s.active ? 'Disable' : 'Enable'}"></div>
        <button class="act-btn edit" onclick='openSvcSheet(${JSON.stringify(s)})'><i class="bi bi-pencil-fill"></i></button>
      </div>
    </div>`).join('') : '<div class="empty-state"><i class="bi bi-grid-x"></i><p>No services yet.</p></div>';
    }

    function openSvcSheet(s) {
      document.getElementById('svcId').value = s ? s.id : '';
      document.getElementById('svcIcon').value = s ? (s.icon || '') : '';
      document.getElementById('svcName').value = s ? s.name : '';
      document.getElementById('svcDesc').value = s ? (s.description || '') : '';
      document.getElementById('svcHourly').value = s ? parseFloat(s.hourly_rate || 0) : 0;
      document.getElementById('svcFlat').value = s ? parseFloat(s.flat_rate || 0) : 0;
      document.getElementById('svcMinH').value = s ? (s.min_hours || 1) : 1;
      document.getElementById('svcPtype').value = s ? (s.pricing_type || 'both') : 'both';
      document.getElementById('svcSheetTtl').textContent = s ? 'Edit Service' : 'Add Service';
      document.getElementById('svcDelBtn').style.display = s ? 'block' : 'none';
      openSheet('svcSheetOl');
    }

    async function saveSvc() {
      const id = document.getElementById('svcId').value;
      const name = document.getElementById('svcName').value.trim();
      if (!name) { toast('Service name required', 'e'); return; }
      const body = fd({ id: id || '', name, icon: document.getElementById('svcIcon').value, description: document.getElementById('svcDesc').value, hourly_rate: document.getElementById('svcHourly').value, flat_rate: document.getElementById('svcFlat').value, min_hours: document.getElementById('svcMinH').value, pricing_type: document.getElementById('svcPtype').value, active: 1 });
      const data = await api('services', id ? 'edit' : 'add', body);
      if (data.success) { toast(id ? 'Service updated' : 'Service added'); closeSheet('svcSheetOl'); loadServices(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function deleteSvc() {
      const id = document.getElementById('svcId').value;
      if (!confirm2('Delete this service?')) return;
      const data = await api('services', 'delete', fd({ id }));
      if (data.success) { toast('Service deleted'); closeSheet('svcSheetOl'); loadServices(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function toggleSvc(id, el) {
      el.classList.toggle('on'); el.classList.toggle('off');
      const data = await api('services', 'toggle', fd({ id }));
      if (!data.success) { el.classList.toggle('on'); el.classList.toggle('off'); toast('Failed', 'e'); }
      else toast(data.message);
    }

    async function loadOffers() {
      const data = await api('offers');
      const offers = data.offers || [];
      document.getElementById('offerList').innerHTML = offers.length ? offers.map(o => `
    <div class="offer-list-item">
      <div class="offer-ic">🏷️</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--txt-primary);">${o.title}</div>
        <div style="font-size:11px;color:var(--teal);font-weight:700;">${o.code}</div>
        <div style="font-size:11px;color:var(--txt-muted);">${o.discount_type === 'percent' ? o.discount_value + '%' : '₱' + o.discount_value} OFF · Used: ${o.used_count}/${o.max_uses || '∞'}</div>
        ${o.expires_at ? `<div style="font-size:10px;color:var(--txt-muted);">Exp: ${o.expires_at}</div>` : ''}
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div class="toggle-sw ${o.active ? 'on' : 'off'}" onclick="toggleOffer(${o.id},this)"></div>
        <button class="act-btn edit" onclick='openOfferSheet(${JSON.stringify(o)})'><i class="bi bi-pencil-fill"></i></button>
      </div>
    </div>`).join('') : '<div class="empty-state"><i class="bi bi-tag-x"></i><p>No offers yet.</p></div>';
    }

    function openOfferSheet(o) {
      document.getElementById('offerId').value = o ? o.id : '';
      document.getElementById('offerTitle').value = o ? o.title : '';
      document.getElementById('offerCode').value = o ? o.code : '';
      document.getElementById('offerType').value = o ? (o.discount_type || 'percent') : 'percent';
      document.getElementById('offerVal').value = o ? parseFloat(o.discount_value || 0) : 10;
      document.getElementById('offerMin').value = o ? parseFloat(o.min_booking_price || 0) : 0;
      document.getElementById('offerMaxUses').value = o ? (o.max_uses || 0) : 0;
      document.getElementById('offerExpires').value = o && o.expires_at ? o.expires_at.replace(' ', 'T').substring(0, 16) : '';
      document.getElementById('offerDesc').value = o ? (o.description || '') : '';
      document.getElementById('offerSheetTtl').textContent = o ? 'Edit Offer' : 'Add Offer';
      document.getElementById('offerDelBtn').style.display = o ? 'block' : 'none';
      openSheet('offerSheetOl');
    }

    async function saveOffer() {
      const id = document.getElementById('offerId').value;
      const title = document.getElementById('offerTitle').value.trim();
      const code = document.getElementById('offerCode').value.trim().toUpperCase();
      if (!title || !code) { toast('Title and code required', 'e'); return; }
      const exp = document.getElementById('offerExpires').value;
      const body = fd({ id: id || '', title, code, discount_type: document.getElementById('offerType').value, discount_value: document.getElementById('offerVal').value, min_booking_price: document.getElementById('offerMin').value, max_uses: document.getElementById('offerMaxUses').value, expires_at: exp ? exp.replace('T', ' ') : '', description: document.getElementById('offerDesc').value, active: 1 });
      const data = await api('offers', id ? 'edit' : 'add', body);
      if (data.success) { toast(id ? 'Offer updated' : 'Offer added'); closeSheet('offerSheetOl'); loadOffers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function deleteOffer() {
      const id = document.getElementById('offerId').value;
      if (!confirm2('Delete this offer?')) return;
      const data = await api('offers', 'delete', fd({ id }));
      if (data.success) { toast('Offer deleted'); closeSheet('offerSheetOl'); loadOffers(); }
      else toast(data.message || 'Failed', 'e');
    }

    async function toggleOffer(id, el) {
      el.classList.toggle('on'); el.classList.toggle('off');
      const data = await api('offers', 'toggle', fd({ id }));
      if (!data.success) { el.classList.toggle('on'); el.classList.toggle('off'); toast('Failed', 'e'); }
      else toast(data.message);
    }


    function openNotifSheet(userId, userName) {
      document.getElementById('notifUserId').value = userId || 0;
      const specOpt = document.getElementById('notifSpecificOpt');
      if (userId && userId > 0) {
        specOpt.style.display = 'block';
        specOpt.textContent = userName || 'Specific User';
        setNotifTarget(userId);
      } else {
        specOpt.style.display = 'none';
        setNotifTarget(0);
      }
      document.getElementById('notifTitle').value = '';
      document.getElementById('notifMsg').value = '';
      document.getElementById('notifSheetTtl').textContent = userId ? `Notify ${userName || 'User'}` : 'Broadcast Notification';
      openSheet('notifSheetOl');
    }

    function setNotifTarget(uid) {
      document.getElementById('notifUserId').value = uid;
      document.querySelectorAll('.nt-opt').forEach(o => o.classList.remove('on'));
      if (uid == 0) { document.querySelector('.nt-opt').classList.add('on'); }
      else { document.getElementById('notifSpecificOpt').classList.add('on'); }
    }

    async function sendNotification() {
      const uid = parseInt(document.getElementById('notifUserId').value) || 0;
      const title = document.getElementById('notifTitle').value.trim();
      const msg = document.getElementById('notifMsg').value.trim();
      const icon = document.getElementById('notifIcon').value;
      if (!title || !msg) { toast('Title and message required', 'e'); return; }
      const body = fd({ user_id: uid, title, message: msg, icon });
      const data = await api('users', 'send_notification', body);
      if (data.success) { toast(data.message); closeSheet('notifSheetOl'); }
      else toast(data.message || 'Failed', 'e');
    }

    async function doLogout() {
      if (!confirm2('Sign out of admin portal?')) return;
      window.location.href = 'logout.php';
    }


    function svcEmoji(svc) {
      const m = { Cleaning: '🧹', Plumbing: '🔧', Electrical: '⚡', Painting: '🖌️', 'Appliance Repair': '🔩', Gardening: '🌿' };
      return m[svc] || '🏠';
    }

    (function init() {
      if (localStorage.getItem('he_dark') === '1') {
        document.body.classList.add('dark');
        document.querySelectorAll('#dmIcon,#dmIcon2').forEach(ic => ic.className = 'bi bi-sun-fill');
      }
      setTimeout(() => {
        const ml = document.getElementById('ml');
        if (ml) { ml.style.opacity = '0'; setTimeout(() => ml.style.display = 'none', 200); }
      }, 800);
      loadOverview();
    })();
  </script>
</body>

</html>
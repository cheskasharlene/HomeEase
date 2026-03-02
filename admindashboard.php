<?php
session_start();
$_dbfile = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php'
  : (file_exists(__DIR__ . '/api/db.php') ? __DIR__ . '/api/db.php' : null);
if (!$_dbfile || empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
  header('Location: index.php');
  exit;
}
require_once $_dbfile;
$adminName = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');

$_current_uri = $_SERVER['REQUEST_URI'];                          
$_current_uri = strtok($_current_uri, '?');                      
$_current_uri = str_replace('\\', '/', $_current_uri);            
$ADMIN_API_URL = preg_replace('/[^\/]+\.php$/i', 'admin_api.php', $_current_uri);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/admindashboard.css">
</head>

<body>
  <div id="toastBox"></div>
  <div class="shell">

    <div class="sheet-ol" id="sheetOl">
      <div class="sheet">
        <div class="sh-hand"></div>
        <div class="sh-hdr">
          <div class="sh-ttl" id="sheetTitle"></div><button class="sh-close" onclick="closeSheet()"><i
              class="bi bi-x-lg"></i></button>
        </div>
        <div id="sheetBody"></div>
      </div>
    </div>

    <div class="screen" id="ovScreen">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Admin · <?= $adminName ?></div>
          <div class="a-ttl">Dashboard</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="loadStats()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
          <button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill" id="dmIcon"></i></button>
          <button class="hdr-btn" onclick="doLogout()"><i class="bi bi-box-arrow-right"></i></button>
        </div>
      </div>
      <div class="a-scroll">
        <div class="stat-grid">
          <div class="stat-card" onclick="goPanel('bookings')">
            <div class="stat-ic teal"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
              <div class="stat-val" id="stBk">—</div>
              <div class="stat-lbl">Total Bookings</div>
            </div>
          </div>
          <div class="stat-card" onclick="goPanel('users')">
            <div class="stat-ic green"><i class="bi bi-people-fill"></i></div>
            <div>
              <div class="stat-val" id="stUsers">—</div>
              <div class="stat-lbl">Users</div>
            </div>
          </div>
          <div class="stat-card" onclick="goPanel('technicians')">
            <div class="stat-ic amber"><i class="bi bi-person-badge-fill"></i></div>
            <div>
              <div class="stat-val" id="stTechs">—</div>
              <div class="stat-lbl">Technicians</div>
            </div>
          </div>
          <div class="stat-card" onclick="goPanel('analytics')">
            <div class="stat-ic blue"><i class="bi bi-currency-exchange"></i></div>
            <div>
              <div class="stat-val" id="stRev">—</div>
              <div class="stat-lbl">Revenue</div>
            </div>
          </div>
        </div>
        <div class="quick-actions">
          <button class="qa-btn" onclick="goPanel('bookings')">
            <div class="qa-ic" style="background:#d1fae5;color:#059669;"><i class="bi bi-calendar-check-fill"></i></div>
            <div class="qa-lbl">Bookings</div>
          </button>
          <button class="qa-btn" onclick="goPanel('users')">
            <div class="qa-ic" style="background:#dbeafe;color:#2563eb;"><i class="bi bi-people-fill"></i></div>
            <div class="qa-lbl">Users</div>
          </button>
          <button class="qa-btn" onclick="goPanel('technicians')">
            <div class="qa-ic" style="background:#fef3c7;color:#d97706;"><i class="bi bi-person-badge-fill"></i></div>
            <div class="qa-lbl">Techs</div>
          </button>
          <button class="qa-btn" onclick="goPanel('offers')">
            <div class="qa-ic" style="background:#fce7f3;color:#db2777;"><i class="bi bi-tag-fill"></i></div>
            <div class="qa-lbl">Offers</div>
          </button>
        </div>
        <div class="chart-card" style="margin-top:14px;">
          <div class="sec-ttl" style="margin-bottom:4px;">Booking Status</div>
          <div class="donut-wrap">
            <svg class="donut-svg" viewBox="0 0 36 36" id="donutSvg">
              <circle cx="18" cy="18" r="15.9155" fill="transparent" stroke="var(--border-col)" stroke-width="3.5" />
            </svg>
            <div class="donut-legend" id="donutLegend"></div>
          </div>
        </div>
        <div style="padding:14px 18px 0;">
          <div class="sec-hdr">
            <div class="sec-ttl">Recent Bookings</div><span
              style="font-size:12px;font-weight:700;color:var(--teal);cursor:pointer;" onclick="goPanel('bookings')">See
              all →</span>
          </div>
          <div id="recentBk">
            <div style="text-align:center;padding:20px;color:var(--txt-muted);font-size:12px;"><i
                class="bi bi-arrow-clockwise"></i> Loading...</div>
          </div>
        </div>
      </div>
      <div id="navOv"></div>
    </div>

    <div class="screen hidden" id="bkScreen">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Bookings</div>
        </div>
        <div class="a-hdr-right"><button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill"></i></button>
        </div>
      </div>
      <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="bkSearch"
          placeholder="Search bookings..." oninput="loadBookings()"></div>
      <div class="tab-row" id="bkTabs">
        <div class="ctab on" onclick="setBkTab('all',this)">All</div>
        <div class="ctab" onclick="setBkTab('pending',this)">Pending</div>
        <div class="ctab" onclick="setBkTab('progress',this)">In Progress</div>
        <div class="ctab" onclick="setBkTab('done',this)">Done</div>
        <div class="ctab" onclick="setBkTab('cancelled',this)">Cancelled</div>
      </div>
      <div class="a-scroll" style="padding:12px 18px 0;" id="bkBody"></div>
      <div id="navBk"></div>
    </div>

    <div class="screen hidden" id="usersScreen">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Users</div>
        </div>
        <div class="a-hdr-right"><button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill"></i></button>
        </div>
      </div>
      <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="userSearch"
          placeholder="Search users..." oninput="loadUsers()"></div>
      <div class="a-scroll" style="padding:12px 0 0;" id="usersBody"></div>
      <div id="navUsers"></div>
    </div>

    <div class="screen hidden" id="techScreen">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Technicians</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="openTechForm(null)"><i class="bi bi-plus-lg"></i></button>
          <button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill"></i></button>
        </div>
      </div>
      <div class="a-scroll" style="padding:12px 0 0;" id="techBody"></div>
      <div id="navTech"></div>
    </div>

    <div class="screen hidden" id="svcScreen">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Services & Pricing</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="openSvcForm(null)"><i class="bi bi-plus-lg"></i></button>
          <button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill"></i></button>
        </div>
      </div>
      <div class="a-scroll" style="padding:12px 18px 0;" id="svcBody"></div>
      <div id="navSvc"></div>
    </div>

    <div class="screen hidden" id="analyticsScreen">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Business</div>
          <div class="a-ttl">Analytics</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="loadAnalytics()"><i class="bi bi-arrow-clockwise"></i></button>
          <button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill"></i></button>
          <button class="hdr-btn" onclick="goPanel('overview')"><i class="bi bi-x-lg"></i></button>
        </div>
      </div>
      <div class="a-scroll" style="padding:16px 18px;" id="analyticsBody"></div>
    </div>

    <div class="screen hidden" id="offersScreen">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Special Offers</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="openOfferForm(null)"><i class="bi bi-plus-lg"></i></button>
          <button class="hdr-btn" onclick="toggleDark()"><i class="bi bi-moon-fill"></i></button>
        </div>
      </div>
      <div class="a-scroll" style="padding:12px 18px 0;" id="offersBody"></div>
      <div id="navOffers"></div>
    </div>

  </div>
  <script>
    (function () { if (localStorage.getItem('he_dark') === '1') { document.body.classList.add('dark'); document.getElementById('dmIcon').className = 'bi bi-sun-fill'; } })();
    function toggleDark() { document.body.classList.toggle('dark'); localStorage.setItem('he_dark', document.body.classList.contains('dark') ? '1' : '0'); document.querySelectorAll('#dmIcon,#dmIcon~i').forEach(ic => ic.className = document.body.classList.contains('dark') ? 'bi bi-sun-fill' : 'bi bi-moon-fill'); }

    const API_BASE = '<?= $ADMIN_API_URL ?>';
    console.log('[HomeEase] API_BASE =', API_BASE);

    async function api(params, postData = null) {
      const qs = new URLSearchParams(params).toString();
      let opts = {};
      if (postData) {
        const body = new URLSearchParams();
        if (params.action) body.append('action', params.action);
        Object.entries(postData).forEach(([k, v]) => body.append(k, v ?? ''));
        opts = { method: 'POST', body };
      }
      try {
        const res = await fetch(API_BASE + '?' + qs, opts);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const text = await res.text();
        try { return JSON.parse(text); }
        catch (je) { console.error('Non-JSON response:', text); return { success: false, message: 'Server error (non-JSON)' }; }
      } catch (e) {
        console.error('API error:', e);
        return { success: false, message: e.message };
      }
    }

    async function doLogout() {
      try { await fetch('logout.php'); } catch (e) { }
      window.location.href = 'index.php';
    }

    const PANELS = {
      overview: 'ovScreen', bookings: 'bkScreen', users: 'usersScreen',
      technicians: 'techScreen', services: 'svcScreen', analytics: 'analyticsScreen',
      offers: 'offersScreen'
    };
    let curPanel = 'overview', curBkTab = 'all';

    function goPanel(name) {
      Object.values(PANELS).forEach(id => document.getElementById(id)?.classList.add('hidden'));
      document.getElementById(PANELS[name])?.classList.remove('hidden');
      curPanel = name;
      if (name === 'bookings') loadBookings();
      if (name === 'users') loadUsers();
      if (name === 'technicians') loadTechnicians();
      if (name === 'services') loadServices();
      if (name === 'analytics') loadAnalytics();
      if (name === 'offers') loadOffers();
    }

    function makeNav(id, active) {
      const el = document.getElementById(id); if (!el) return;
      el.innerHTML = `<div class="bnav">
    <div class="ni${active === 'overview' ? ' on' : ''}" onclick="goPanel('overview')"><i class="bi bi-grid-fill"></i><span class="nl">Overview</span></div>
    <div class="ni${active === 'bookings' ? ' on' : ''}" onclick="goPanel('bookings')"><i class="bi bi-calendar-check-fill"></i><span class="nl">Bookings</span></div>
    <div class="ni${active === 'services' ? ' on' : ''}" onclick="goPanel('services')"><div class="nb-c"><i class="bi bi-grid-3x3-gap-fill"></i></div></div>
    <div class="ni${active === 'offers' ? ' on' : ''}" onclick="goPanel('offers')"><i class="bi bi-tag-fill"></i><span class="nl">Offers</span></div>
    <div class="ni${active === 'users' ? ' on' : ''}" onclick="goPanel('users')"><i class="bi bi-people-fill"></i><span class="nl">Users</span></div>
  </div>`;
    }

    async function loadStats() {
      try {
        const d = await api({ action: 'stats' });
        if (!d.success) return;
        const s = d.stats;
        document.getElementById('stBk').textContent = s.total_bookings;
        document.getElementById('stUsers').textContent = s.total_users;
        document.getElementById('stTechs').textContent = s.total_techs;
        document.getElementById('stRev').textContent = '₱' + parseFloat(s.total_revenue).toLocaleString('en-PH', { maximumFractionDigits: 0 });
        renderDonut([
          { val: s.done, color: '#0d9488', label: `Done (${s.done})` },
          { val: s.in_progress, color: '#3b82f6', label: `Progress (${s.in_progress})` },
          { val: s.pending, color: '#f59e0b', label: `Pending (${s.pending})` },
          { val: s.cancelled, color: '#ef4444', label: `Cancelled (${s.cancelled})` },
        ], s.total_bookings || 1);
      } catch (e) { }
    }

    function renderDonut(segs, total) {
      const svg = document.getElementById('donutSvg');
      const leg = document.getElementById('donutLegend');
      let offset = 25, html = '<circle cx="18" cy="18" r="15.9155" fill="transparent" stroke="var(--border-col)" stroke-width="3.5"/>';
      segs.forEach(s => {
        const dash = (s.val / total) * 100;
        if (dash > 0) {
          html += `<circle cx="18" cy="18" r="15.9155" fill="transparent" stroke="${s.color}" stroke-width="3.5" stroke-dasharray="${dash} ${100 - dash}" stroke-dashoffset="${-(offset - 100)}"/>`;
          offset += dash;
        }
      });
      svg.innerHTML = html;
      leg.innerHTML = segs.map(s => `<div class="legend-item"><div class="legend-dot" style="background:${s.color};"></div><span>${s.label}</span></div>`).join('');
    }

    async function loadRecentBookings() {
      const el = document.getElementById('recentBk');
      try {
        const d = await api({ action: 'get_bookings', status: 'all', search: '' });
        if (!d.success || !d.bookings.length) {
          el.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings yet</p></div>'; return;
        }
        el.innerHTML = d.bookings.slice(0, 5).map(b => bkCard(b)).join('');
      } catch (e) { el.innerHTML = '<div class="empty-state"><p>Failed to load</p></div>'; }
    }

    function setBkTab(s, el) {
      document.querySelectorAll('#bkTabs .ctab').forEach(t => t.classList.remove('on'));
      el.classList.add('on'); curBkTab = s; loadBookings();
    }

    async function loadBookings() {
      const el = document.getElementById('bkBody');
      const q = document.getElementById('bkSearch')?.value || '';
      el.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>';
      try {
        const d = await api({ action: 'get_bookings', status: curBkTab, search: q });
        if (!d.success || !d.bookings.length) {
          el.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings found</p></div>'; return;
        }
        el.innerHTML = d.bookings.map(b => bkCard(b, true)).join('');
      } catch (e) { el.innerHTML = '<div class="empty-state"><p>Failed to load</p></div>'; }
    }

    function bkCard(b, showActions = false) {
      const SVC_IC = { 'Cleaning': '🧹', 'Plumbing': '🔧', 'Electrical': '⚡', 'Painting': '🖌️', 'Appliance Repair': '🔩', 'Gardening': '🌿' };
      const icon = SVC_IC[b.service] || '🏠';
      const tech = b.technician_name || 'No technician';
      const price = parseFloat(b.price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
      const pLabel = b.pricing_type === 'hourly' ? `${b.hours}hr` : 'flat';
      const acts = showActions ? `
    <div class="act-btns" onclick="event.stopPropagation()">
      <button class="act-btn edit" onclick="openBkEdit(${b.id})" title="Edit"><i class="bi bi-pencil-fill"></i></button>
      <button class="act-btn del"  onclick="deleteBk(${b.id})"  title="Delete"><i class="bi bi-trash3-fill"></i></button>
    </div>` : '';
      return `
    <div class="bk-card" onclick="openBkDetail(${b.id})">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
        <div style="width:42px;height:42px;border-radius:11px;background:var(--teal-mid);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">${icon}</div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:13px;font-weight:700;color:var(--txt-primary);">${b.service} <span style="font-size:10px;color:var(--txt-muted);">#${b.id}</span></div>
          <div style="font-size:11px;color:var(--txt-muted);">${b.customer_name || 'Unknown'}</div>
          <div style="font-size:10px;color:var(--txt-muted);">${b.date} · ${b.time_slot || '—'}</div>
        </div>
        <span class="pill ${b.status}">${b.status}</span>
      </div>
      <div style="display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--border-col);padding-top:8px;">
        <div style="font-size:11px;color:var(--txt-muted);">👷 ${tech} · ${pLabel}</div>
        <div style="display:flex;align-items:center;gap:8px;">${acts}<div class="bk-price">₱${price}</div></div>
      </div>
    </div>`;
    }

    let _bkList = [];
    async function openBkDetail(id) {
      const d = await api({ action: 'get_bookings', status: 'all', search: '' });
      if (!d.success) return;
      _bkList = d.bookings;
      const b = d.bookings.find(x => x.id == id); if (!b) return;
      const price = parseFloat(b.price || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
      openSheet(`Booking #${b.id}`, `
    <div style="font-size:24px;margin-bottom:12px;">${({ 'Cleaning': '🧹', 'Plumbing': '🔧', 'Electrical': '⚡', 'Painting': '🖌️', 'Appliance Repair': '🔩', 'Gardening': '🌿' })[b.service] || '🏠'} <b>${b.service}</b> <span class="pill ${b.status}">${b.status}</span></div>
    <div class="detail-row"><span class="detail-lbl">Customer</span><span class="detail-val">${b.customer_name || '—'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Date & Time</span><span class="detail-val">${b.date} · ${b.time_slot || '—'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Address</span><span class="detail-val">${b.address || '—'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Technician</span><span class="detail-val">${b.technician_name || 'Not assigned'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Pricing</span><span class="detail-val">${b.pricing_type === 'hourly' ? b.hours + 'hr' : 'Flat rate'} · ₱${price}</span></div>
    ${b.notes ? `<div class="detail-row"><span class="detail-lbl">Notes</span><span class="detail-val">${b.notes}</span></div>` : ''}
    <div class="modal-btns" style="margin-top:18px;">
      <div class="btn-outline" onclick="closeSheet();openBkEdit(${b.id})"><i class="bi bi-pencil-fill"></i> Edit</div>
      <div class="btn-danger"  onclick="closeSheet();deleteBk(${b.id})"><i class="bi bi-trash3-fill"></i> Delete</div>
    </div>`);
    }

    async function openBkEdit(id) {
      const d = await api({ action: 'get_bookings', status: 'all', search: '' });
      const b = d.bookings?.find(x => x.id == id); if (!b) return;
      const techs = (await api({ action: 'get_technicians' })).technicians || [];
      openSheet(`Edit Booking #${b.id}`, `
    <div class="fg"><label class="fl">Status</label>
      <div class="avail-toggle">
        ${['pending', 'progress', 'done', 'cancelled'].map(s => `<div class="avail-opt${b.status === s ? ' on' : ''}" onclick="setOpt(this,'bkSt','${s}')">${s}</div>`).join('')}
      </div><input type="hidden" id="bkSt" value="${b.status}">
    </div>
    <div class="fg"><label class="fl">Technician</label>
      <select class="fi" id="bkTech">
        <option value="">— No technician —</option>
        ${techs.map(t => `<option value="${t.id}"${b.technician_id == t.id ? ' selected' : ''}>${t.name} (${t.specialty})</option>`).join('')}
      </select>
    </div>
    <div class="fg"><label class="fl">Price (₱)</label><input class="fi" id="bkPrice" type="number" value="${b.price}"></div>
    <div class="fg"><label class="fl">Notes</label><input class="fi" id="bkNotes" value="${b.notes || ''}"></div>
    <div class="modal-btns"><div class="btn-outline" onclick="closeSheet()">Cancel</div><div class="btn-p" onclick="saveBk(${id})">Save Changes</div></div>`);
    }

    async function saveBk(id) {
      const ok = await api({ action: 'update_booking' }, {
        id, status: document.getElementById('bkSt').value,
        price: document.getElementById('bkPrice').value,
        notes: document.getElementById('bkNotes').value,
        technician_id: document.getElementById('bkTech').value
      });
      if (ok.success) { toast('Booking updated', 's'); closeSheet(); loadBookings(); loadRecentBookings(); loadStats(); }
      else toast('Update failed', 'e');
    }

    async function deleteBk(id) {
      if (!confirm('Delete this booking?')) return;
      const ok = await api({ action: 'delete_booking' }, { id });
      if (ok.success) { toast('Deleted', 's'); loadBookings(); loadRecentBookings(); loadStats(); }
      else toast('Delete failed', 'e');
    }

    async function loadUsers() {
      const el = document.getElementById('usersBody');
      const q = document.getElementById('userSearch')?.value || '';
      el.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>';
      try {
        const d = await api({ action: 'get_users', search: q });
        if (!d.success || !d.users.length) {
          el.innerHTML = '<div class="card" style="margin:0 18px;"><div class="empty-state"><i class="bi bi-people"></i><p>No users found</p></div></div>'; return;
        }
        el.innerHTML = `<div class="card" style="margin:0 18px;">${d.users.map(u => `
      <div class="list-item">
        <div class="li-av">${(u.name || 'U')[0].toUpperCase()}</div>
        <div class="li-body"><div class="li-name">${u.name}</div><div class="li-sub">${u.email} · ${u.booking_count} bookings</div></div>
        <div class="li-right">
          <span class="pill ${u.status}">${u.status}</span>
          <div class="act-btns">
            <button class="act-btn tog" onclick="toggleUser(${u.id})" title="Toggle status"><i class="bi bi-toggle-${u.status === 'active' ? 'on' : 'off'}" style="font-size:15px;"></i></button>
            <button class="act-btn del" onclick="deleteUser(${u.id})"><i class="bi bi-trash3-fill"></i></button>
          </div>
        </div>
      </div>`).join('')}</div>`;
      } catch (e) { el.innerHTML = '<div class="empty-state"><p>Failed to load</p></div>'; }
    }

    async function toggleUser(id) {
      const ok = await api({ action: 'toggle_user' }, { id });
      if (ok.success) { toast('User status updated', 's'); loadUsers(); loadStats(); }
      else toast('Failed', 'e');
    }

    async function deleteUser(id) {
      if (!confirm('Delete this user and all their bookings?')) return;
      const ok = await api({ action: 'delete_user' }, { id });
      if (ok.success) { toast('User deleted', 's'); loadUsers(); loadStats(); }
      else toast('Failed', 'e');
    }

    async function loadTechnicians() {
      const el = document.getElementById('techBody');
      el.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>';
      try {
        const d = await api({ action: 'get_technicians' });
        if (!d.success || !d.technicians.length) {
          el.innerHTML = '<div class="card" style="margin:0 18px;"><div class="empty-state"><i class="bi bi-person-badge"></i><p>No technicians yet.<br>Tap + to add one.</p></div></div>'; return;
        }
        const avC = { available: '#d1fae5', busy: '#dbeafe', unavailable: '#fee2e2' };
        const avT = { available: '#059669', busy: '#2563eb', unavailable: '#dc2626' };
        el.innerHTML = `<div class="card" style="margin:0 18px;">${d.technicians.map(t => `
      <div class="list-item">
        <div class="li-av" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);">${(t.name || 'T')[0]}</div>
        <div class="li-body"><div class="li-name">${t.name}</div><div class="li-sub">${t.specialty} · ⭐${t.rating} · ${t.jobs_done} jobs</div></div>
        <div class="li-right">
          <span class="pill" style="background:${avC[t.availability]};color:${avT[t.availability]};">${t.availability}</span>
          <div class="act-btns">
            <button class="act-btn edit" onclick="openTechForm(${t.id})"><i class="bi bi-pencil-fill"></i></button>
            <button class="act-btn del"  onclick="deleteTech(${t.id})"><i class="bi bi-trash3-fill"></i></button>
          </div>
        </div>
      </div>`).join('')}</div>`;
      } catch (e) { el.innerHTML = '<div class="empty-state"><p>Failed to load</p></div>'; }
    }

    async function openTechForm(id) {
      let t = null;
      if (id) {
        const d = await api({ action: 'get_technicians' });
        t = d.technicians?.find(x => x.id == id);
      }

      const svcResp = await api({ action: 'get_services' });
      let specialties = (svcResp.services || []).map(s => s.name);
      if (!specialties.length) {
        specialties = ['Cleaning', 'Plumbing', 'Electrical', 'Painting', 'Appliance Repair', 'Gardening', 'Carpentry', 'Aircon Service'];
      }

      if (t && t.specialty && !specialties.includes(t.specialty)) {
        specialties.unshift(t.specialty);
      }
      openSheet(id ? 'Edit Technician' : 'Add Technician', `
    <div class="fg"><label class="fl">Full Name</label><input class="fi" id="tName" value="${t ? t.name : ''}" placeholder="Technician name"></div>
    <div class="fg"><label class="fl">Specialty</label>
      <select class="fi" id="tSpec">
        <option value="">— Select specialty —</option>
        ${specialties.map(s => `<option value="${s}"${t && t.specialty === s ? ' selected' : ''}>${s}</option>`).join('')}
      </select>
    </div>
    <div class="fg"><label class="fl">Or type custom specialty</label><input class="fi" id="tSpecCustom" value="" placeholder="Leave blank to use dropdown above"></div>
    <div class="fg"><label class="fl">Phone</label><input class="fi" id="tPhone" value="${t ? t.phone || '' : ''}" placeholder="09XXXXXXXXX"></div>
    <div class="fg"><label class="fl">Availability</label>
      <div class="avail-toggle">
        ${['available', 'busy', 'unavailable'].map(s => `<div class="avail-opt${(!t && s === 'available') || (t && t.availability === s) ? ' on' : ''}" onclick="setOpt(this,'tAv','${s}')">${s}</div>`).join('')}
      </div><input type="hidden" id="tAv" value="${t ? t.availability : 'available'}">
    </div>
    <div class="fg"><label class="fl">Status</label>
      <select class="fi" id="tStatus">
        <option value="active"${!t || t.status === 'active' ? ' selected' : ''}>Active</option>
        <option value="inactive"${t && t.status === 'inactive' ? ' selected' : ''}>Inactive</option>
      </select>
    </div>
    <div class="modal-btns"><div class="btn-outline" onclick="closeSheet()">Cancel</div><div class="btn-p" onclick="saveTech(${id || 'null'})">Save</div></div>`);
    }

    async function saveTech(id) {
      const name = document.getElementById('tName').value.trim();
      if (!name) { toast('Technician name is required', 'e'); return; }

      const customSpec = document.getElementById('tSpecCustom')?.value.trim();
      const specialty = customSpec || document.getElementById('tSpec').value;
      if (!specialty) { toast('Please select or enter a specialty', 'e'); return; }
      const isNew = (!id || id === 'null');
      const ok = await api({ action: 'save_technician' }, {
        id: isNew ? 0 : id,
        name: name,
        specialty: specialty,
        phone: document.getElementById('tPhone').value.trim(),
        availability: document.getElementById('tAv').value,
        status: document.getElementById('tStatus').value,
      });
      if (ok.success) { toast(isNew ? 'Technician added ✅' : 'Technician updated ✅', 's'); closeSheet(); loadTechnicians(); loadStats(); }
      else toast(ok.message || 'Failed to save', 'e');
    }

    async function deleteTech(id) {
      if (!confirm('Remove this technician?')) return;
      const ok = await api({ action: 'delete_technician' }, { id });
      if (ok.success) { toast('Removed', 's'); loadTechnicians(); loadStats(); }
      else toast('Failed', 'e');
    }

    async function loadServices() {
      const el = document.getElementById('svcBody');
      el.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>';
      try {
        const d = await api({ action: 'get_services' });
        if (!d.success || !d.services.length) {
          el.innerHTML = '<div class="empty-state"><i class="bi bi-grid"></i><p>No services yet.<br>Tap + to add one.</p></div>'; return;
        }
        el.innerHTML = d.services.map(s => `
      <div class="svc-admin-card" style="${s.active ? '' : 'opacity:.6'}" onclick="openSvcForm(${s.id})">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:48px;height:48px;border-radius:14px;background:var(--teal-mid);display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0;">${s.icon}</div>
          <div style="flex:1;"><div style="font-size:14px;font-weight:700;color:var(--txt-primary);">${s.name}</div><div style="font-size:11px;color:var(--txt-muted);">${s.description || '—'}</div></div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;" onclick="event.stopPropagation()">
            <span class="pill ${s.active ? 'active' : 'inactive'}">${s.active ? 'Active' : 'Off'}</span>
            <div class="act-btns">
              <button class="act-btn edit" onclick="openSvcForm(${s.id})"><i class="bi bi-pencil-fill"></i></button>
              <button class="act-btn tog"  onclick="toggleSvc(${s.id})"><i class="bi bi-toggle-${s.active ? 'on' : 'off'}" style="font-size:15px;"></i></button>
              <button class="act-btn del"  onclick="deleteSvc(${s.id})"><i class="bi bi-trash3-fill"></i></button>
            </div>
          </div>
        </div>
        <div class="price-row">
          <div><div class="price-lbl">Hourly</div><div class="price-val">₱${parseFloat(s.hourly_rate).toLocaleString()}/hr</div></div>
          <div style="text-align:center;"><div class="price-lbl">Min hrs</div><div class="price-val">${s.min_hours}h</div></div>
          <div style="text-align:center;"><div class="price-lbl">Pricing</div><div class="price-val" style="text-transform:capitalize;">${s.pricing_type}</div></div>
          <div style="text-align:right;"><div class="price-lbl">Flat Rate</div><div class="price-val">₱${parseFloat(s.flat_rate).toLocaleString()}</div></div>
        </div>
      </div>`).join('');
      } catch (e) { el.innerHTML = '<div class="empty-state"><p>Failed to load</p></div>'; }
    }

    async function openSvcForm(id) {
      let s = null;
      if (id) { const d = await api({ action: 'get_services' }); s = d.services?.find(x => x.id == id); }
      openSheet(id ? 'Edit Service' : 'Add Service', `
    <div class="fg-row" style="display:grid;grid-template-columns:1fr 60px;gap:10px;">
      <div class="fg"><label class="fl">Service Name</label><input class="fi" id="sName" value="${s ? s.name : ''}" placeholder="e.g. Cleaning"></div>
      <div class="fg"><label class="fl">Icon</label><input class="fi" id="sIcon" value="${s ? s.icon : '🔧'}" placeholder="🔧" style="font-size:20px;text-align:center;padding:8px;"></div>
    </div>
    <div class="fg"><label class="fl">Description</label><input class="fi" id="sDesc" value="${s ? s.description || '' : ''}" placeholder="Short description"></div>
    <div class="fg"><label class="fl">Pricing Type</label>
      <div class="avail-toggle">
        ${['flat', 'hourly', 'both'].map(p => `<div class="avail-opt${(!s && p === 'both') || (s && s.pricing_type === p) ? ' on' : ''}" onclick="setOpt(this,'sPtype','${p}')">${p}</div>`).join('')}
      </div><input type="hidden" id="sPtype" value="${s ? s.pricing_type : 'both'}">
    </div>
    <div class="fg-row" style="display:grid;grid-template-columns:1fr 1fr 80px;gap:10px;">
      <div class="fg"><label class="fl">Flat Rate (₱)</label><input class="fi" id="sFlat" type="number" value="${s ? s.flat_rate : 0}"></div>
      <div class="fg"><label class="fl">Hourly Rate (₱)</label><input class="fi" id="sHourly" type="number" value="${s ? s.hourly_rate : 0}"></div>
      <div class="fg"><label class="fl">Min Hrs</label><input class="fi" id="sMinH" type="number" value="${s ? s.min_hours : 1}" min="1" max="12"></div>
    </div>
    <div class="fg"><label class="fl">Status</label>
      <div class="avail-toggle">
        ${[['active', '1'], ['inactive', '0']].map(([l, v]) => `<div class="avail-opt${(!s && v === '1') || (s && (s.active ? '1' : '0') === v) ? ' on' : ''}" onclick="setOpt(this,'sActive','${v}')">${l}</div>`).join('')}
      </div><input type="hidden" id="sActive" value="${s ? (s.active ? '1' : '0') : '1'}">
    </div>
    <div class="modal-btns"><div class="btn-outline" onclick="closeSheet()">Cancel</div><div class="btn-p" onclick="saveSvc(${id || 'null'})">Save</div></div>`);
    }

    async function saveSvc(id) {
      const name = document.getElementById('sName').value.trim();
      if (!name) { toast('Service name is required', 'e'); return; }
      const isNew = (!id || id === 'null');
      const ok = await api({ action: 'save_service' }, {
        id: isNew ? 0 : id,
        name: name,
        icon: document.getElementById('sIcon').value.trim() || '🔧',
        description: document.getElementById('sDesc').value.trim(),
        pricing_type: document.getElementById('sPtype').value,
        flat_rate: document.getElementById('sFlat').value || 0,
        hourly_rate: document.getElementById('sHourly').value || 0,
        min_hours: document.getElementById('sMinH').value || 1,
        active: document.getElementById('sActive').value,
      });
      if (ok.success) { toast(isNew ? 'Service added ✅' : 'Service updated ✅', 's'); closeSheet(); loadServices(); }
      else toast(ok.message || 'Failed to save', 'e');
    }

    async function toggleSvc(id) {
      const ok = await api({ action: 'toggle_service' }, { id });
      if (ok.success) { toast('Status toggled', 's'); loadServices(); }
      else toast('Failed', 'e');
    }

    async function deleteSvc(id) {
      if (!confirm('Delete this service?')) return;
      const ok = await api({ action: 'delete_service' }, { id });
      if (ok.success) { toast('Deleted', 's'); loadServices(); }
      else toast('Failed', 'e');
    }

    async function loadAnalytics() {
      const el = document.getElementById('analyticsBody');
      el.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>';
      try {
        const [sd, bd] = await Promise.all([
          api({ action: 'stats' }),
          api({ action: 'get_bookings', status: 'all', search: '' })
        ]);
        if (!sd.success) return;
        const s = sd.stats;
        const bookings = bd.bookings || [];
        const svcCounts = {};
        bookings.forEach(b => { svcCounts[b.service] = (svcCounts[b.service] || 0) + 1; });
        const topSvcs = Object.entries(svcCounts).sort((a, b) => b[1] - a[1]).slice(0, 6);
        const maxSvc = topSvcs[0]?.[1] || 1;
        el.innerHTML = `
      <div class="analytics-kpi">
        <div class="kpi-title">💰 Revenue (Completed Bookings)</div>
        <div style="font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--teal);">₱${parseFloat(s.total_revenue).toLocaleString('en-PH', { minimumFractionDigits: 2 })}</div>
        <div style="font-size:11px;color:var(--txt-muted);margin-top:4px;">From ${s.done} completed bookings</div>
      </div>
      <div class="analytics-kpi">
        <div class="kpi-title">📊 Booking Breakdown</div>
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;">
          ${[['✅ Done', s.done, 'var(--teal)'], ['🔵 Progress', s.in_progress, '#3b82f6'], ['⏳ Pending', s.pending, '#f59e0b'], ['❌ Cancelled', s.cancelled, '#ef4444']].map(([l, v, c]) => `
          <div style="background:var(--teal-bg);border-radius:12px;padding:12px;text-align:center;">
            <div style="font-size:20px;font-weight:800;color:${c};font-family:'Poppins',sans-serif;">${v}</div>
            <div style="font-size:11px;color:var(--txt-muted);font-weight:700;">${l}</div>
          </div>`).join('')}
        </div>
      </div>
      <div class="analytics-kpi">
        <div class="kpi-title">👥 People</div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;">
          ${[['👤 Users', s.total_users, 'var(--teal)'], ['🛠 Techs', s.total_techs, '#7c3aed'], ['📋 Bookings', s.total_bookings, '#2563eb']].map(([l, v, c]) => `
          <div style="background:var(--teal-bg);border-radius:12px;padding:10px;text-align:center;">
            <div style="font-size:20px;font-weight:800;color:${c};font-family:'Poppins',sans-serif;">${v}</div>
            <div style="font-size:10px;color:var(--txt-muted);font-weight:700;">${l}</div>
          </div>`).join('')}
        </div>
      </div>
      ${topSvcs.length ? `<div class="analytics-kpi">
        <div class="kpi-title">🏆 Top Services</div>
        ${topSvcs.map((s, i) => `
          <div style="margin-bottom:10px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:3px;">
              <span style="font-size:12px;font-weight:700;color:var(--txt-primary);">${['🥇', '🥈', '🥉', '4️⃣', '5️⃣', '6️⃣'][i]} ${s[0]}</span>
              <span style="font-size:11px;font-weight:800;color:var(--teal);">${s[1]} bookings</span>
            </div>
            <div class="rev-track"><div class="rev-fill" style="width:${(s[1] / maxSvc * 100).toFixed(0)}%;"></div></div>
          </div>`).join('')}
      </div>` : ''}`;
      } catch (e) { el.innerHTML = '<div class="empty-state"><p>Failed to load analytics</p></div>'; }
    }

    function openSheet(title, body) {
      document.getElementById('sheetTitle').textContent = title;
      document.getElementById('sheetBody').innerHTML = body;
      document.getElementById('sheetOl').classList.add('on');
    }
    function closeSheet() { document.getElementById('sheetOl').classList.remove('on'); }
    document.getElementById('sheetOl').addEventListener('click', function (e) { if (e.target === this) closeSheet(); });

    function setOpt(el, inputId, val) {
      el.closest('.avail-toggle').querySelectorAll('.avail-opt').forEach(o => o.classList.remove('on'));
      el.classList.add('on');
      document.getElementById(inputId).value = val;
    }

    function toast(msg, type = 's') {
      const t = document.createElement('div');
      t.className = `toast-n ${type}`;
      t.innerHTML = `<i class="bi bi-${type === 's' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>${msg}`;
      document.getElementById('toastBox').appendChild(t);
      setTimeout(() => { t.style.opacity = '0'; t.style.transition = 'opacity .3s'; setTimeout(() => t.remove(), 300); }, 2500);
    }

    async function loadOffers() {
      const el = document.getElementById('offersBody');
      el.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading...</p></div>';
      try {
        const d = await api({ action: 'get_offers' });
        if (!d.success) { el.innerHTML = `<div class="empty-state"><i class="bi bi-exclamation-circle"></i><p>${d.message || 'Failed to load'}</p></div>`; return; }
        if (!d.offers.length) {
          el.innerHTML = '<div class="empty-state"><i class="bi bi-tag" style="font-size:42px;color:var(--border-col);display:block;margin-bottom:12px;"></i><p>No offers yet.<br>Tap + to create one.</p></div>'; return;
        }
        el.innerHTML = d.offers.map(o => {
          const now = new Date(); const exp = o.expires_at ? new Date(o.expires_at) : null;
          const expired = exp && exp < now;
          const discLbl = o.discount_type === 'percent' ? `${o.discount_value}% OFF` : `₱${parseFloat(o.discount_value).toLocaleString()} OFF`;
          return `<div class="svc-admin-card" style="${!o.active || expired ? 'opacity:.55' : ''}">
        <div style="display:flex;align-items:center;gap:12px;">
          <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#f59e0b,#d97706);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🏷️</div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:700;color:var(--txt-primary);">${o.title}</div>
            <div style="font-size:11px;color:var(--txt-muted);">${o.description || '—'}</div>
            <div style="font-size:11px;margin-top:3px;"><span style="background:#fef3c7;color:#d97706;padding:2px 8px;border-radius:20px;font-weight:700;font-size:10px;">${discLbl}</span> <span style="color:var(--txt-muted);">code: <b>${o.code}</b></span></div>
          </div>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;" onclick="event.stopPropagation()">
            <span class="pill ${o.active && !expired ? 'active' : 'inactive'}">${expired ? 'Expired' : o.active ? 'Active' : 'Off'}</span>
            <div class="act-btns">
              <button class="act-btn edit" onclick="openOfferForm(${o.id})"><i class="bi bi-pencil-fill"></i></button>
              <button class="act-btn tog"  onclick="toggleOffer(${o.id})"><i class="bi bi-toggle-${o.active ? 'on' : 'off'}" style="font-size:15px;"></i></button>
              <button class="act-btn del"  onclick="deleteOffer(${o.id})"><i class="bi bi-trash3-fill"></i></button>
            </div>
          </div>
        </div>
        <div class="price-row">
          <div><div class="price-lbl">Min Spend</div><div class="price-val">${o.min_booking_price > 0 ? '₱' + parseFloat(o.min_booking_price).toLocaleString() : 'None'}</div></div>
          <div style="text-align:center;"><div class="price-lbl">Uses</div><div class="price-val">${o.used_count}${o.max_uses > 0 ? '/' + o.max_uses : ''}  </div></div>
          <div style="text-align:right;"><div class="price-lbl">Expires</div><div class="price-val">${o.expires_at ? o.expires_at.split(' ')[0] : 'Never'}</div></div>
        </div>
      </div>`;
        }).join('');
      } catch (e) { el.innerHTML = `<div class="empty-state"><p>Error: ${e.message}</p></div>`; }
    }

    async function openOfferForm(id) {
      let o = null;
      if (id) { const d = await api({ action: 'get_offers' }); o = d.offers?.find(x => x.id == id); }
      openSheet(id ? 'Edit Offer' : 'Add Special Offer', `
    <div class="fg"><label class="fl">Offer Title</label><input class="fi" id="oTitle" value="${o ? o.title : ''}" placeholder="e.g. Summer Promo"></div>
    <div class="fg"><label class="fl">Promo Code</label><input class="fi" id="oCode" value="${o ? o.code : ''}" placeholder="e.g. SUMMER20" style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()"></div>
    <div class="fg"><label class="fl">Description</label><input class="fi" id="oDesc" value="${o ? o.description || '' : ''}" placeholder="Short description for customers"></div>
    <div class="fg"><label class="fl">Discount Type</label>
      <div class="avail-toggle">
        ${[['percent', '% Percent'], ['flat', '₱ Fixed']].map(([v, l]) => `<div class="avail-opt${(!o && v === 'percent') || (o && o.discount_type === v) ? ' on' : ''}" onclick="setOpt(this,'oType','${v}')">${l}</div>`).join('')}
      </div><input type="hidden" id="oType" value="${o ? o.discount_type : 'percent'}">
    </div>
    <div class="fg-row" style="display:grid;grid-template-columns:1fr 1fr;gap:11px;">
      <div class="fg"><label class="fl">Discount Value</label><input class="fi" id="oVal" type="number" value="${o ? o.discount_value : 10}" min="0" placeholder="10"></div>
      <div class="fg"><label class="fl">Min Booking (₱)</label><input class="fi" id="oMin" type="number" value="${o ? o.min_booking_price : 0}" min="0" placeholder="0 = any"></div>
    </div>
    <div class="fg-row" style="display:grid;grid-template-columns:1fr 1fr;gap:11px;">
      <div class="fg"><label class="fl">Max Uses (0=∞)</label><input class="fi" id="oMaxUse" type="number" value="${o ? o.max_uses : 0}" min="0"></div>
      <div class="fg"><label class="fl">Expires (optional)</label><input class="fi" id="oExp" type="date" value="${o && o.expires_at ? o.expires_at.split(' ')[0] : ''}"></div>
    </div>
    <div class="fg"><label class="fl">Status</label>
      <div class="avail-toggle">
        ${[['active', '1'], ['inactive', '0']].map(([l, v]) => `<div class="avail-opt${(!o && v === '1') || (o && (o.active ? '1' : '0') === v) ? ' on' : ''}" onclick="setOpt(this,'oActive','${v}')">${l}</div>`).join('')}
      </div><input type="hidden" id="oActive" value="${o ? (o.active ? '1' : '0') : '1'}">
    </div>
    <div class="modal-btns"><div class="btn-outline" onclick="closeSheet()">Cancel</div><div class="btn-p" onclick="saveOffer(${id || 'null'})">Save Offer</div></div>`);
    }

    async function saveOffer(id) {
      const title = document.getElementById('oTitle').value.trim();
      const code = document.getElementById('oCode').value.trim().toUpperCase();
      if (!title || !code) { toast('Title and promo code are required', 'e'); return; }
      const isNew = (!id || id === 'null');
      const ok = await api({ action: 'save_offer' }, {
        id: isNew ? 0 : id,
        title,
        code,
        description: document.getElementById('oDesc').value.trim(),
        discount_type: document.getElementById('oType').value,
        discount_value: document.getElementById('oVal').value || 0,
        min_booking_price: document.getElementById('oMin').value || 0,
        max_uses: document.getElementById('oMaxUse').value || 0,
        expires_at: document.getElementById('oExp').value || '',
        active: document.getElementById('oActive').value,
      });
      if (ok.success) { toast(isNew ? 'Offer created ✅' : 'Offer updated ✅', 's'); closeSheet(); loadOffers(); }
      else toast(ok.message || 'Failed to save offer', 'e');
    }

    async function toggleOffer(id) {
      const ok = await api({ action: 'toggle_offer' }, { id });
      if (ok.success) { toast('Offer status toggled', 's'); loadOffers(); }
      else toast(ok.message || 'Failed', 'e');
    }

    async function deleteOffer(id) {
      if (!confirm('Delete this offer?')) return;
      const ok = await api({ action: 'delete_offer' }, { id });
      if (ok.success) { toast('Offer deleted', 's'); loadOffers(); }
      else toast('Failed', 'e');
    }

    ['navOv', 'navBk', 'navUsers', 'navTech', 'navSvc', 'navOffers'].forEach((id, i) => {
      makeNav(id, ['overview', 'bookings', 'users', 'technicians', 'services', 'offers'][i]);
    });
    loadStats();
    loadRecentBookings();
  </script>
</body>

</html>
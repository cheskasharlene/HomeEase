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
  <title>HomeEase – Workers</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/workers.css" rel="stylesheet">
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

    <div class="screen" id="workers-page">
      <div class="w-scroll">
        <div class="w-hdr">
          <div class="w-hdr-top">
            <div>
              <div class="w-hdr-ttl">Our Workers</div>
              <div class="w-hdr-sub" id="workerCount">Loading...</div>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
              <button class="w-dm-btn" onclick="manualRefresh()" title="Refresh">
                <i class="bi bi-arrow-clockwise" id="refreshIcon"></i>
              </button>
              <button class="w-dm-btn" onclick="toggleDark()" title="Toggle dark mode">
                <i class="bi bi-moon-fill" id="dmIcon"></i>
              </button>
            </div>
          </div>
          <div class="w-search">
            <i class="bi bi-search"></i>
            <input type="text" id="workerSearch" placeholder="Search by name or specialty..." oninput="onSearch()">
          </div>
        </div>

        <div class="w-filters" id="filterPills">
          <div class="w-pill active" data-filter="all">All</div>
          <div class="w-pill" data-filter="available">Available Now</div>
          <div class="w-pill" data-filter="Cleaning">Cleaning</div>
          <div class="w-pill" data-filter="Plumbing">Plumbing</div>
          <div class="w-pill" data-filter="Electrical">Electrical</div>
          <div class="w-pill" data-filter="Painting">Painting</div>
          <div class="w-pill" data-filter="Gardening">Gardening</div>
          <div class="w-pill" data-filter="Appliance Repair">Appliance Repair</div>
        </div>

        <div class="w-sec-lbl" id="workerSectionLbl">All Workers</div>
        <div class="w-list" id="workerList">
          <div class="w-loading"><i class="bi bi-arrow-clockwise"></i>
            <p>Loading workers...</p>
          </div>
        </div>
        <div class="w-pb"></div>
      </div>
      <div id="navContainer"></div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    initTheme();
    (function () {
      const ic = document.getElementById('dmIcon');
      if (ic && document.body.classList.contains('dark')) ic.className = 'bi bi-sun-fill';
    })();

    let activeFilter = 'all';
    let searchQuery = '';
    let debounceTimer = null;
    let autoRefreshInt = null;

    function buildUrl() {
      const params = new URLSearchParams({ action: 'list' });
      if (searchQuery.trim()) params.set('search', searchQuery.trim());
      if (activeFilter !== 'all') params.set('filter', activeFilter);
      return 'api/workers_api.php?' + params.toString();
    }

    function statusLabel(s) {
      return s === 'available' ? 'Available Now' : s === 'busy' ? 'Currently Busy' : 'Offline';
    }

    function starRating(r) {
      if (!r) return '';
      const full = Math.floor(r);
      const stars = '★'.repeat(full) + '☆'.repeat(5 - full);
      return `<span class="w-stars" title="${r}/5">${stars} <small>${r.toFixed(1)}</small></span>`;
    }

    async function loadWorkers(showSpinner = true) {
      const el = document.getElementById('workerList');
      if (showSpinner) {
        el.innerHTML = `<div class="w-loading"><i class="bi bi-arrow-clockwise"></i><p>Loading workers...</p></div>`;
      }
      try {
        const res = await fetch(buildUrl(), { cache: 'no-store' });
        const contentType = res.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
          const text = await res.text();
          console.error('Server response:', text);
          showError('Server error (' + res.status + '). See browser console for details.');
          return;
        }
        const data = await res.json();
        if (!data.success) { showError(data.message || 'Failed to load workers.'); return; }

        const workers = data.workers || [];
        document.getElementById('workerCount').textContent =
          `${workers.length} worker${workers.length !== 1 ? 's' : ''} found`;
        document.getElementById('workerSectionLbl').textContent =
          activeFilter === 'all' ? 'All Workers' :
            activeFilter === 'available' ? 'Available Now' : `${activeFilter} Specialists`;

        renderWorkers(workers);
        clearInterval(autoRefreshInt);
        autoRefreshInt = setInterval(() => loadWorkers(false), 30000);
      } catch (err) {
        console.error('Fetch error:', err);
        showError('Could not connect to server. Please try again.');
      }
    }

    function renderWorkers(workers) {
      const el = document.getElementById('workerList');
      if (!workers.length) {
        el.innerHTML = `<div class="w-empty"><i class="bi bi-person-x"></i><div class="w-empty-ttl">No workers found</div><p>Try a different filter or search term.</p></div>`;
        return;
      }
      el.innerHTML = workers.map(w => {
        const safeRole = (w.role || '').replace(/'/g, "\\'");
        const imgSrc = `https://ui-avatars.com/api/?name=${encodeURIComponent(w.name)}&background=FDECC8&color=F5A623&size=128`;
        const isOffline = w.status === 'offline';
        return `
          <div class="w-card" onclick="bookWorker(${w.id},'${safeRole}')">
            <div class="w-avatar-wrap">
              <img class="w-avatar" src="${imgSrc}" alt="${w.name}">
              <div class="w-status-dot dot-${w.status}"></div>
            </div>
            <div class="w-info">
              <div class="w-name">${w.name}</div>
              <div class="w-role">${w.role}</div>
              ${starRating(w.rating)}
              <div class="w-meta">
                ${w.jobs ? `<div class="w-meta-item"><i class="bi bi-briefcase-fill"></i> ${w.jobs} jobs</div>` : ''}
                ${w.phone ? `<div class="w-meta-item"><i class="bi bi-telephone-fill"></i> ${w.phone}</div>` : ''}
              </div>
            </div>
            <div class="w-right">
              <span class="w-badge-avail badge-${w.status}">${statusLabel(w.status)}</span>
              <button class="w-book-btn" ${isOffline ? 'disabled' : ''}
                onclick="event.stopPropagation();bookWorker(${w.id},'${safeRole}')">
                ${isOffline ? 'Unavailable' : 'Book Now'}
              </button>
            </div>
          </div>`;
      }).join('');
    }

    function showError(msg) {
      document.getElementById('workerList').innerHTML = `
        <div class="w-error">
          <i class="bi bi-wifi-off"></i>
          <div class="w-error-ttl">Oops!</div>
          <p>${msg}</p>
          <button class="w-retry-btn" onclick="loadWorkers()"><i class="bi bi-arrow-clockwise"></i> Retry</button>
        </div>`;
    }

    function manualRefresh() {
      const icon = document.getElementById('refreshIcon');
      icon.style.transition = 'transform 0.6s';
      icon.style.transform = 'rotate(360deg)';
      setTimeout(() => { icon.style.transform = ''; }, 650);
      loadWorkers(true);
    }

    function bookWorker(id, role) {
      if (!role) return;
      goPage('booking_form.php?newbooking=1&svc=' + encodeURIComponent(role));
    }

    function onSearch() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => {
        searchQuery = document.getElementById('workerSearch').value;
        loadWorkers();
      }, 350);
    }

    document.getElementById('filterPills').addEventListener('click', function (e) {
      const pill = e.target.closest('.w-pill');
      if (!pill) return;
      document.querySelectorAll('.w-pill').forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeFilter = pill.dataset.filter;
      loadWorkers();
    });

    loadWorkers();

    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('booking_form.php?newbooking=1')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span><div class="ndot"></div></div>
        <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;
  </script>
</body>

</html>
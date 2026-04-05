<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
enforceProviderSectionAccess('schedule', $conn);
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – My Schedule</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_schedule.css">
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

    <div class="screen" id="schedulePage">
      <div class="p-scroll">
        <div class="p-hdr">
          <div class="p-hdr-main">
            <div class="p-hdr-ttl">My Schedule</div>
            <div class="p-hdr-sub">Hello, <?= $providerName ?></div>
          </div>
          <div class="hdr-back" onclick="goPage('provider_home.php')"><i class="bi bi-arrow-left"></i></div>
        </div>

        <div class="view-toggle">
          <button class="view-btn active" data-view="month" onclick="setView('month')">Month</button>
          <button class="view-btn" data-view="week" onclick="setView('week')">Week</button>
          <button class="view-btn" data-view="day" onclick="setView('day')">Day</button>
        </div>

        <div class="filters">
          <input id="searchInput" class="f-input" type="text" placeholder="Search by service or client">
          <select id="statusFilter" class="f-select">
            <option value="all">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="completed">Completed</option>
          </select>
        </div>

        <div class="cal-card">
          <div class="cal-bar">
            <button class="cal-nav" type="button" onclick="moveCursor(-1)"><i class="bi bi-chevron-left"></i></button>
            <div class="cal-title" id="calTitle">Month</div>
            <button class="cal-nav" type="button" onclick="moveCursor(1)"><i class="bi bi-chevron-right"></i></button>
          </div>
          <div id="monthView" class="month-grid"></div>
          <div id="weekView" class="week-grid hide"></div>
          <div id="dayView" class="day-view hide"></div>
        </div>

        <div class="sec-row" style="padding:18px 18px 6px;">
          <div class="sec-ttl" id="detailTitle">Bookings</div>
        </div>
        <div id="detailList" class="book-list"></div>
        <div class="h-pb" style="height:16px;"></div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
        <div class="ni on" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span class="nl">Calendar</span></div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>
    </div>
  </div>

  <div class="bk-modal" id="bookingModal" aria-hidden="true">
    <div class="bk-card" role="dialog" aria-modal="true" aria-labelledby="bkTitle">
      <div class="bk-title" id="bkTitle">Booking Details</div>
      <div class="bk-desc" id="bkDesc"></div>
      <div class="bk-fg">
        <label class="bk-lbl" for="bkStatusSelect">Update Status</label>
        <select id="bkStatusSelect" class="bk-sel">
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="completed">Completed</option>
        </select>
      </div>
      <div class="bk-actions">
        <button type="button" class="bk-btn cancel" onclick="closeBookingModal()">Close</button>
        <button type="button" class="bk-btn save" id="bkSaveBtn" onclick="saveBookingStatus()">Save</button>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();

    const state = {
      bookings: [],
      selectedDate: new Date().toISOString().slice(0, 10),
      cursor: new Date(),
      view: 'month',
      search: '',
      status: 'all',
      activeBooking: null
    };

    const fmtMonth = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });
    const fmtWeekday = new Intl.DateTimeFormat('en-US', { weekday: 'short' });
    const fmtDayLabel = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

    const q = new URLSearchParams(window.location.search);
    const preDate = q.get('date');
    if (preDate && /^\d{4}-\d{2}-\d{2}$/.test(preDate)) {
      state.selectedDate = preDate;
      state.cursor = new Date(preDate + 'T00:00:00');
      state.view = 'day';
    }

    function normalizeStatus(raw) {
      const s = String(raw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'completed';
      if (s === 'progress' || s === 'confirmed' || s === 'active') return 'confirmed';
      return 'pending';
    }

    function statusText(status) {
      if (status === 'completed') return 'Completed';
      if (status === 'confirmed') return 'Confirmed';
      return 'Pending';
    }

    function filteredBookings() {
      return state.bookings.filter(b => {
        const statusNorm = normalizeStatus(b.status_raw || b.status);
        const text = `${b.service} ${b.client_name} ${b.address} ${statusText(statusNorm)}`.toLowerCase();
        const matchesSearch = state.search === '' || text.includes(state.search.toLowerCase());
        const matchesStatus = state.status === 'all' || statusNorm === state.status;
        return matchesSearch && matchesStatus;
      });
    }

    function bookingsOn(dateStr) {
      return filteredBookings().filter(b => b.date === dateStr).sort((a, b) => String(a.time || '').localeCompare(String(b.time || '')));
    }

    function toDateKey(d) {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    }

    function setView(view) {
      state.view = view;
      document.querySelectorAll('.view-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.view === view));
      document.getElementById('monthView').classList.toggle('hide', view !== 'month');
      document.getElementById('weekView').classList.toggle('hide', view !== 'week');
      document.getElementById('dayView').classList.toggle('hide', view !== 'day');
      renderCalendar();
    }

    function moveCursor(step) {
      if (state.view === 'month') {
        state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth() + step, 1);
      } else if (state.view === 'week') {
        state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), state.cursor.getDate() + step * 7);
      } else {
        state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), state.cursor.getDate() + step);
        state.selectedDate = toDateKey(state.cursor);
      }
      renderCalendar();
    }

    function renderMonth() {
      const el = document.getElementById('monthView');
      const y = state.cursor.getFullYear();
      const m = state.cursor.getMonth();
      const first = new Date(y, m, 1);
      const startDay = first.getDay();
      const daysInMonth = new Date(y, m + 1, 0).getDate();

      let html = '';
      ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(w => {
        html += `<div class="wk-label">${w}</div>`;
      });

      for (let i = 0; i < startDay; i++) {
        html += '<div class="day-cell empty"></div>';
      }

      for (let d = 1; d <= daysInMonth; d++) {
        const dateKey = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const count = bookingsOn(dateKey).length;
        const selected = dateKey === state.selectedDate ? ' selected' : '';
        html += `
          <div class="day-cell${selected}" onclick="pickDate('${dateKey}', true)">
            <div class="day-num">${d}</div>
            <div class="day-count">${count ? `${count} booking${count > 1 ? 's' : ''}` : ''}</div>
          </div>`;
      }

      el.innerHTML = html;
      document.getElementById('calTitle').textContent = fmtMonth.format(new Date(y, m, 1));
    }

    function startOfWeek(dateObj) {
      const d = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate());
      const day = d.getDay();
      d.setDate(d.getDate() - day);
      return d;
    }

    function renderWeek() {
      const el = document.getElementById('weekView');
      const start = startOfWeek(state.cursor);
      let html = '';
      for (let i = 0; i < 7; i++) {
        const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
        const key = toDateKey(d);
        const count = bookingsOn(key).length;
        const selected = key === state.selectedDate ? ' selected' : '';
        html += `
          <div class="week-day${selected}" onclick="pickDate('${key}', true)">
            <div class="week-day-name">${fmtWeekday.format(d)}</div>
            <div class="week-day-num">${d.getDate()}</div>
            <div class="week-day-count">${count ? `${count} jobs` : 'No jobs'}</div>
          </div>`;
      }
      el.innerHTML = html;
      const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
      document.getElementById('calTitle').textContent = `${fmtDayLabel.format(start)} - ${fmtDayLabel.format(end)}`;
    }

    function renderDay() {
      const dayView = document.getElementById('dayView');
      const items = bookingsOn(state.selectedDate);
      if (!items.length) {
        dayView.innerHTML = '<div class="empty" style="margin:0;">No bookings for this day.</div>';
      } else {
        dayView.innerHTML = items.map(item => {
          const status = normalizeStatus(item.status_raw || item.status);
          return `
            <div class="book-card" onclick="openBookingById(${item.id})">
              <div class="book-top">
                <div class="book-time">${item.time || 'All day'}</div>
                <span class="book-status ${status}">${statusText(status)}</span>
              </div>
              <div class="book-service">${item.service}</div>
              <div class="book-sub">${item.client_name || 'Client'}${item.address ? ' · ' + item.address : ''}</div>
            </div>`;
        }).join('');
      }
      document.getElementById('calTitle').textContent = fmtDayLabel.format(new Date(state.selectedDate + 'T00:00:00'));
    }

    function renderDetails() {
      const list = document.getElementById('detailList');
      const items = bookingsOn(state.selectedDate);
      const dt = new Date(state.selectedDate + 'T00:00:00');
      document.getElementById('detailTitle').textContent = `Bookings on ${fmtDayLabel.format(dt)}`;

      if (!items.length) {
        list.innerHTML = '<div class="empty" style="margin:0;">No bookings match your filters for this date.</div>';
        return;
      }

      list.innerHTML = items.map(item => {
        const status = normalizeStatus(item.status_raw || item.status);
        return `
          <div class="book-card" onclick="openBookingById(${item.id})">
            <div class="book-top">
              <div class="book-time">${item.time || 'All day'}</div>
              <span class="book-status ${status}">${statusText(status)}</span>
            </div>
            <div class="book-service">${item.service}</div>
            <div class="book-sub">Client: ${item.client_name || 'Client'}</div>
            <div class="book-sub">${item.address || 'Address not provided'}</div>
          </div>`;
      }).join('');
    }

    function pickDate(dateKey, jumpToDay) {
      state.selectedDate = dateKey;
      state.cursor = new Date(dateKey + 'T00:00:00');
      if (jumpToDay) setView('day');
      renderCalendar();
    }

    function renderCalendar() {
      if (state.view === 'month') renderMonth();
      if (state.view === 'week') renderWeek();
      if (state.view === 'day') renderDay();
      renderDetails();
    }

    function openBookingById(id) {
      const item = state.bookings.find(b => String(b.id) === String(id));
      if (!item) return;
      state.activeBooking = item;
      const statusNorm = normalizeStatus(item.status_raw || item.status);
      document.getElementById('bkDesc').textContent = `${item.service}\n${item.date} ${item.time}\nClient: ${item.client_name || 'Client'}\n${item.address || 'Address not provided'}`;
      document.getElementById('bkStatusSelect').value = statusNorm;
      document.getElementById('bookingModal').classList.add('on');
      document.getElementById('bookingModal').setAttribute('aria-hidden', 'false');
    }

    function closeBookingModal() {
      state.activeBooking = null;
      document.getElementById('bookingModal').classList.remove('on');
      document.getElementById('bookingModal').setAttribute('aria-hidden', 'true');
    }

    async function saveBookingStatus() {
      if (!state.activeBooking) return;
      const saveBtn = document.getElementById('bkSaveBtn');
      const status = document.getElementById('bkStatusSelect').value;
      saveBtn.disabled = true;
      saveBtn.textContent = 'Saving...';

      try {
        const fd = new FormData();
        fd.append('action', 'update_status');
        fd.append('id', String(state.activeBooking.id));
        fd.append('status', status);

        const res = await fetch('../api/provider_schedule_api.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          alert(data.message || 'Could not update booking.');
          return;
        }

        await loadBookings();
        closeBookingModal();
      } catch (e) {
        alert('Network error. Please try again.');
      } finally {
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save';
      }
    }

    function loadBookings() {
      return fetch('../api/provider_schedule_api.php')
        .then(r => r.json())
        .then(data => {
          state.bookings = data.success && Array.isArray(data.bookings) ? data.bookings : [];
          renderCalendar();
        })
        .catch(() => {
          state.bookings = [];
          renderCalendar();
        });
    }

    document.getElementById('searchInput').addEventListener('input', function () {
      state.search = this.value.trim();
      renderCalendar();
    });

    document.getElementById('statusFilter').addEventListener('change', function () {
      state.status = this.value;
      renderCalendar();
    });

    document.getElementById('bookingModal').addEventListener('click', function (e) {
      if (e.target === this) closeBookingModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeBookingModal();
    });

    loadBookings();

    setView(state.view);
  </script>
</body>

</html>

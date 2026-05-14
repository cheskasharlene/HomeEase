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
            <div class="p-hdr-sub">Hello, <?= $providerName ?> 👋</div>
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
            <option value="cancelled">Cancelled</option>
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
        <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span class="nl">Earnings</span></div>
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
          <option value="cancelled">Cancelled</option>
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
    const currentProviderId = <?= (int) ($_SESSION['provider_id'] ?? 0) ?>;

    const state = {
      bookings: [],
      selectedDate: new Date().toISOString().slice(0, 10),
      cursor: new Date(),
      view: 'month',
      search: '',
      status: 'all',
      activeBooking: null,
      loading: true
    };

    const fmtMonth = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });
    const fmtWeekday = new Intl.DateTimeFormat('en-US', { weekday: 'short' });
    const fmtDayLabel = new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const fmtLongDayLabel = new Intl.DateTimeFormat('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

    const q = new URLSearchParams(window.location.search);
    const preDate = q.get('date');
    if (preDate && /^\d{4}-\d{2}-\d{2}$/.test(preDate)) {
      state.selectedDate = preDate;
      state.cursor = new Date(preDate + 'T00:00:00');
      state.view = 'day';
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function formatPrice(value) {
      return '₱' + Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function normalizeStatus(raw) {
      const s = String(raw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'completed';
      if (s === 'progress' || s === 'confirmed' || s === 'active') return 'confirmed';
      if (s === 'cancelled' || s === 'canceled') return 'cancelled';
      return 'pending';
    }

    function statusText(status) {
      if (status === 'completed') return 'Completed';
      if (status === 'confirmed') return 'Confirmed';
      if (status === 'cancelled') return 'Cancelled';
      return 'Pending';
    }

    function dateKeyToDate(dateKey) {
      return new Date(dateKey + 'T00:00:00');
    }

    function toDateKey(d) {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    }

    function timeSortKey(value) {
      const raw = String(value || '').trim().toLowerCase();
      if (!raw || raw === 'all day') return '99:99';
      const match = raw.match(/^(\d{1,2}):(\d{2})(?:\s*([ap]m))?$/i);
      if (match) {
        let hour = parseInt(match[1], 10);
        const minute = match[2];
        const suffix = (match[3] || '').toLowerCase();
        if (suffix === 'pm' && hour !== 12) hour += 12;
        if (suffix === 'am' && hour === 12) hour = 0;
        return `${String(hour).padStart(2, '0')}:${minute}`;
      }
      return raw;
    }

    function sortBookings(items) {
      return [...items].sort((a, b) => {
        const dateCompare = String(a.date || '').localeCompare(String(b.date || ''));
        if (dateCompare !== 0) return dateCompare;
        return timeSortKey(a.time).localeCompare(timeSortKey(b.time));
      });
    }

    function filteredBookings() {
      return state.bookings.filter(b => {
        const statusNorm = normalizeStatus(b.status_raw || b.status);
        const text = `${b.service} ${b.client_name} ${b.address} ${statusText(statusNorm)} ${b.price}`.toLowerCase();
        const matchesSearch = state.search === '' || text.includes(state.search.toLowerCase());
        const matchesStatus = state.status === 'all' || statusNorm === state.status;
        return matchesSearch && matchesStatus;
      });
    }

    function bookingsOn(dateStr) {
      return sortBookings(filteredBookings().filter(b => b.date === dateStr));
    }

    function bookingsForMonth() {
      const y = state.cursor.getFullYear();
      const m = state.cursor.getMonth();
      return filteredBookings().filter(b => {
        const d = dateKeyToDate(b.date);
        return d.getFullYear() === y && d.getMonth() === m;
      });
    }

    function startOfWeek(dateObj) {
      const d = new Date(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate());
      d.setDate(d.getDate() - d.getDay());
      return d;
    }

    function renderLoadingCards() {
      return `
        <div class="schedule-skeleton-list">
          <div class="schedule-skeleton-card"></div>
          <div class="schedule-skeleton-card"></div>
          <div class="schedule-skeleton-card"></div>
        </div>`;
    }

    function renderEmptyState(title, desc) {
      return `
        <div class="empty empty-schedule">
          <div class="empty-ic"><i class="bi bi-calendar2-x"></i></div>
          <div class="empty-title">${escapeHtml(title)}</div>
          <div class="empty-copy">${escapeHtml(desc)}</div>
          <button type="button" class="empty-cta" onclick="goPage('provider_requests.php')">Go to Requests</button>
        </div>`;
    }

    function renderBookingCard(item) {
      const status = normalizeStatus(item.status_raw || item.status);
      const dateText = item.date ? fmtLongDayLabel.format(dateKeyToDate(item.date)) : 'Date not set';
      const timeText = item.time && item.time !== 'All day' ? item.time : 'All day';
      const addressText = item.address || 'Address not provided';
      const clientText = item.client_name || 'Client';
      return `
        <article class="book-card" onclick="openBookingById(${item.id})">
          <div class="book-top">
            <div class="book-type">${escapeHtml(item.service)}</div>
            <div class="book-price">${escapeHtml(formatPrice(item.price))}</div>
          </div>
          <div class="book-client">${escapeHtml(clientText)}</div>
          <div class="book-meta">📍 ${escapeHtml(addressText)}</div>
          <div class="book-meta">📅 ${escapeHtml(dateText)} · ${escapeHtml(timeText)}</div>
          <div class="book-foot">
            <span class="book-status ${status}">${escapeHtml(statusText(status))}</span>
            <button type="button" class="book-detail-btn" onclick="event.stopPropagation(); openBookingById(${item.id})">View Details</button>
          </div>
        </article>`;
    }

    function renderGroupSection(title, subtitle, items, emptyText) {
      const body = items.length ? items.map(renderBookingCard).join('') : `<div class="schedule-empty-row">${escapeHtml(emptyText)}</div>`;
      return `
        <section class="schedule-section">
          <div class="schedule-section-head">
            <div>
              <div class="schedule-section-title">${escapeHtml(title)}</div>
              <div class="schedule-section-sub">${escapeHtml(subtitle)}</div>
            </div>
          </div>
          <div class="schedule-section-list">${body}</div>
        </section>`;
    }

    function renderMonth() {
      const el = document.getElementById('monthView');
      const y = state.cursor.getFullYear();
      const m = state.cursor.getMonth();
      const first = new Date(y, m, 1);
      const startDay = first.getDay();
      const daysInMonth = new Date(y, m + 1, 0).getDate();
      const monthBookingsList = bookingsForMonth();
      const todayKey = toDateKey(new Date());

      let html = '';
      ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].forEach(w => {
        html += `<div class="wk-label">${w}</div>`;
      });

      for (let i = 0; i < startDay; i++) {
        html += '<div class="day-filler" aria-hidden="true"></div>';
      }

      for (let d = 1; d <= daysInMonth; d++) {
        const dateKey = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const count = monthBookingsList.filter(b => b.date === dateKey).length;
        const selected = dateKey === state.selectedDate ? ' selected' : '';
        const today = dateKey === todayKey ? ' today' : '';
        const hasBookings = count > 0 ? ' has-bookings' : '';
        const badge = count > 0 ? `<span class="day-badge">${count}</span>` : '';
        html += `
          <div class="day-cell${selected}${today}${hasBookings}" onclick="pickDate('${dateKey}', true)">
            ${badge}
            <div class="day-num">${d}</div>
            <div class="day-count">${count ? `${count} job${count > 1 ? 's' : ''}` : ''}</div>
          </div>`;
      }

      el.innerHTML = html;
      document.getElementById('calTitle').textContent = fmtMonth.format(new Date(y, m, 1));
    }

    function renderWeek() {
      const el = document.getElementById('weekView');
      const start = startOfWeek(state.cursor);
      const weekItems = filteredBookings();
      let html = '';

      for (let i = 0; i < 7; i++) {
        const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
        const key = toDateKey(d);
        const count = weekItems.filter(b => b.date === key).length;
        const selected = key === state.selectedDate ? ' selected' : '';
        const today = key === toDateKey(new Date()) ? ' today' : '';
        html += `
          <div class="week-day${selected}${today}" onclick="pickDate('${key}', true)">
            <div class="week-day-name">${fmtWeekday.format(d)}</div>
            <div class="week-day-num">${d.getDate()}</div>
            <div class="week-day-count">${count ? `${count} booking${count > 1 ? 's' : ''}` : 'Free'}</div>
          </div>`;
      }

      el.innerHTML = html;
      const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
      document.getElementById('calTitle').textContent = `${fmtDayLabel.format(start)} - ${fmtDayLabel.format(end)}`;
    }

    function renderDay() {
      const dayView = document.getElementById('dayView');
      const items = bookingsOn(state.selectedDate);
      const selectedDate = dateKeyToDate(state.selectedDate);

      if (state.loading) {
        dayView.innerHTML = '<div class="day-summary-skeleton"></div>';
      } else if (!items.length) {
        dayView.innerHTML = renderEmptyState('No bookings for this day', 'Accepted bookings will appear here when the selected date has scheduled jobs.');
      } else {
        const preview = items.slice(0, 3).map(item => `
          <div class="day-mini-row">
            <div>
              <div class="day-mini-service">${escapeHtml(item.service)}</div>
              <div class="day-mini-meta">${escapeHtml(item.client_name || 'Client')} · ${escapeHtml(item.time || 'All day')}</div>
            </div>
            <div class="day-mini-status ${normalizeStatus(item.status_raw || item.status)}">${escapeHtml(statusText(normalizeStatus(item.status_raw || item.status)))}</div>
          </div>`).join('');
        const extra = items.length > 3 ? `<div class="day-mini-more">+${items.length - 3} more in the list below</div>` : '';
        dayView.innerHTML = `
          <div class="day-summary">
            <div>
              <div class="day-summary-label">Selected day</div>
              <div class="day-summary-date">${escapeHtml(fmtLongDayLabel.format(selectedDate))}</div>
            </div>
            <div class="day-summary-count">${items.length} booking${items.length > 1 ? 's' : ''}</div>
          </div>
          <div class="day-preview-list">${preview}</div>
          ${extra}`;
      }

      document.getElementById('calTitle').textContent = fmtLongDayLabel.format(selectedDate);
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
        state.selectedDate = toDateKey(state.cursor);
      } else if (state.view === 'week') {
        state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), state.cursor.getDate() + step * 7);
        state.selectedDate = toDateKey(state.cursor);
      } else {
        state.cursor = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), state.cursor.getDate() + step);
        state.selectedDate = toDateKey(state.cursor);
      }
      renderCalendar();
    }

    function groupByDate(items) {
      return items.reduce((acc, item) => {
        if (!acc[item.date]) acc[item.date] = [];
        acc[item.date].push(item);
        return acc;
      }, {});
    }

    function renderDetails() {
      const list = document.getElementById('detailList');
      const dayItems = bookingsOn(state.selectedDate);
      const monthItems = bookingsForMonth();
      const baseDate = dateKeyToDate(state.selectedDate);

      if (state.loading) {
        list.innerHTML = renderLoadingCards();
        document.getElementById('detailTitle').textContent = 'Bookings';
        return;
      }

      if (state.view === 'day') {
        document.getElementById('detailTitle').textContent = `Bookings on ${fmtLongDayLabel.format(baseDate)}`;
        if (!dayItems.length) {
          list.innerHTML = renderEmptyState('No bookings yet', 'You do not have any scheduled jobs for this day. Accepted bookings will appear here.');
          return;
        }
        list.innerHTML = renderGroupSection(fmtLongDayLabel.format(baseDate), `${dayItems.length} booking${dayItems.length > 1 ? 's' : ''}`, dayItems, 'No bookings for this day.');
        return;
      }

      if (state.view === 'week') {
        const start = startOfWeek(state.cursor);
        const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
        document.getElementById('detailTitle').textContent = `Bookings this week`;
        const sections = [];
        for (let i = 0; i < 7; i++) {
          const d = new Date(start.getFullYear(), start.getMonth(), start.getDate() + i);
          const key = toDateKey(d);
          const items = bookingsOn(key);
          sections.push(renderGroupSection(fmtLongDayLabel.format(d), `${items.length} booking${items.length > 1 ? 's' : ''}`, items, 'No bookings for this day.'));
        }
        if (!monthItems.length) {
          list.innerHTML = renderEmptyState('No bookings yet', `Your week from ${fmtDayLabel.format(start)} to ${fmtDayLabel.format(end)} is empty.`);
          return;
        }
        list.innerHTML = sections.join('');
        return;
      }

      document.getElementById('detailTitle').textContent = 'Bookings this month';
      if (!monthItems.length) {
        list.innerHTML = renderEmptyState('No bookings yet', 'You do not have any scheduled jobs for this month. Accepted bookings will appear here.');
        return;
      }

      const grouped = groupByDate(sortBookings(monthItems));
      const keys = Object.keys(grouped).sort();
      list.innerHTML = keys.map(key => {
        const items = grouped[key];
        return renderGroupSection(fmtLongDayLabel.format(dateKeyToDate(key)), `${items.length} booking${items.length > 1 ? 's' : ''}`, items, 'No bookings for this date.');
      }).join('');
    }

    function renderDay() {
      const dayView = document.getElementById('dayView');
      const items = bookingsOn(state.selectedDate);

      if (state.loading) {
        dayView.innerHTML = '<div class="day-summary-skeleton"></div>';
        document.getElementById('calTitle').textContent = fmtLongDayLabel.format(dateKeyToDate(state.selectedDate));
        return;
      }

      if (!items.length) {
        dayView.innerHTML = renderEmptyState('No bookings for this day', 'Accepted bookings will appear here when the selected date has scheduled jobs.');
      } else {
        const preview = items.slice(0, 3).map(item => {
          const status = normalizeStatus(item.status_raw || item.status);
          return `
            <div class="day-mini-row">
              <div>
                <div class="day-mini-service">${escapeHtml(item.service)}</div>
                <div class="day-mini-meta">${escapeHtml(item.client_name || 'Client')} · ${escapeHtml(item.time || 'All day')}</div>
              </div>
              <div class="day-mini-status ${status}">${escapeHtml(statusText(status))}</div>
            </div>`;
        }).join('');
        const extra = items.length > 3 ? `<div class="day-mini-more">+${items.length - 3} more in the list below</div>` : '';
        dayView.innerHTML = `
          <div class="day-summary">
            <div>
              <div class="day-summary-label">Selected day</div>
              <div class="day-summary-date">${escapeHtml(fmtLongDayLabel.format(dateKeyToDate(state.selectedDate)))}</div>
            </div>
            <div class="day-summary-count">${items.length} booking${items.length > 1 ? 's' : ''}</div>
          </div>
          <div class="day-preview-list">${preview}</div>
          ${extra}`;
      }

      document.getElementById('calTitle').textContent = fmtLongDayLabel.format(dateKeyToDate(state.selectedDate));
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
      document.getElementById('bkDesc').textContent = [
        item.service || 'Service',
        `${formatPrice(item.price)} · ${statusText(statusNorm)}`,
        `Date & Time: ${item.date || 'Date not set'} ${item.time || 'All day'}`,
        `Client: ${item.client_name || 'Client'}`,
        `Address: ${item.address || 'Address not provided'}`
      ].join('\n');
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
      state.loading = true;
      renderCalendar();
      return fetch('../api/provider_schedule_api.php')
        .then(r => r.json())
        .then(data => {
          state.bookings = data.success && Array.isArray(data.bookings) ? data.bookings : [];
          state.loading = false;
          renderCalendar();
        })
        .catch(() => {
          state.bookings = [];
          state.loading = false;
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

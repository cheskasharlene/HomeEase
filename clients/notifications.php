<?php

session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once '../api/db.php';
$uid = $_SESSION['user_id'];
$stmt = $conn->prepare(
  "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function timeAgo($ts)
{
  $diff = time() - strtotime($ts);
  if ($diff < 60)    return 'Just now';
  if ($diff < 3600)  return floor($diff / 60) . 'm ago';
  if ($diff < 86400) return floor($diff / 3600) . 'h ago';
  return floor($diff / 86400) . 'd ago';
}

$notifications = array_map(function ($n) {
  return [
    'id'         => (int) $n['id'],
    'title'      => $n['title'],
    'msg'        => $n['message'],
    'time'       => timeAgo($n['created_at']),
    'created_at' => $n['created_at'],
    'read'       => ((int) $n['is_read']) === 1,
    'icon'       => $n['icon'] ?? 'house_cleaner',
  ];
}, $rows);

$unreadCount = count(array_filter($notifications, fn($n) => !$n['read']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Notifications</title>
  <meta name="description" content="Your HomeEase notifications – stay updated on bookings and services." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link href="../assets/css/notifications.css" rel="stylesheet">
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

    <div class="screen" id="notifications">
      <div class="n-scroll">
        <div class="n-hdr">
          <div>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="n-ttl">Notifications</div>
              <span class="notif-badge" id="nBadge" data-count="<?= (int)$unreadCount ?>" style="<?= $unreadCount > 0 ? 'display:inline-flex;' : 'display:none;' ?>"><?= $unreadCount > 0 ? ($unreadCount > 99 ? '99+' : $unreadCount) : '' ?></span>
            </div>
            <div class="n-count-sub" id="nCount">
              <?= $unreadCount > 0 ? "<strong>$unreadCount</strong> unread &nbsp;·&nbsp; " . count($notifications) . " total" : (count($notifications) > 0 ? "All caught up · " . count($notifications) . " total" : "No notifications yet") ?>
            </div>
          </div>
          <button class="n-markall" id="markAllBtn" onclick="markAllRead()" style="<?= $unreadCount ? '' : 'display:none;' ?>">
            <i class="bi bi-check2-all"></i> Mark all read
          </button>
        </div>
        <div class="n-body" id="nBody"></div>
      </div>
      <div id="navContainer"></div>
    </div>
  </div>

  <!-- Persistent toast element -->
  <div class="he-toast" id="heToast"></div>

  <script src="../assets/js/app.js"></script>
  <script>initTheme();</script>
  <script>
    window.HE = window.HE || {};
    window.HE.notifications = <?= json_encode($notifications) ?>;
    window.HE.unreadCount   = <?= (int) $unreadCount ?>;
    const clientNotifKey = 'he_client_notifs';

    /* ── Storage helpers ── */
    function getStoredClientNotifIds() {
      try { const r = JSON.parse(localStorage.getItem(clientNotifKey) || '[]'); return Array.isArray(r) ? r : []; }
      catch (e) { return []; }
    }
    function setStoredClientNotifIds(ids) {
      try { localStorage.setItem(clientNotifKey, JSON.stringify(ids)); } catch (e) {}
    }

    /* ── Toast ── */
    function showToast(message, type = 'info') {
      const t = document.getElementById('heToast');
      t.textContent = message;
      t.className = `he-toast ${type}`;
      requestAnimationFrame(() => { t.classList.add('show'); });
      clearTimeout(t._timer);
      t._timer = setTimeout(() => {
        t.classList.remove('show');
      }, 2400);
    }

    /* ── Day label helpers ── */
    function dayKey(dateStr) {
      const d = new Date(dateStr);
      return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    }

    function dayLabel(key) {
      const today     = dayKey(new Date().toISOString());
      const yesterday = dayKey(new Date(Date.now() - 864e5).toISOString());
      if (key === today)     return 'Today';
      if (key === yesterday) return 'Yesterday';
      const [y, m, d] = key.split('-');
      const dt = new Date(Number(y), Number(m)-1, Number(d));
      return dt.toLocaleDateString([], { weekday: 'long', month: 'short', day: 'numeric' });
    }

    /* ── Nav ── */
    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni" onclick="goPage('../home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('service_selection.php')" style="cursor:pointer;"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni ni-bell on">
          <div class="ni-bell-wrap">
            <i class="bi bi-bell-fill"></i>
            <span class="ni-badge" id="navBellBadge" style="display:none;"></span>
          </div>
          <span class="nl">Notifications</span>
        </div>
        <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;

    /* ── Sync unread across tabs & bottom nav ── */
    function syncUnreadCount(count) {
      const c = Math.max(0, Number(count) || 0);
      window.HE.unreadCount = c;
      try {
        localStorage.setItem('he_unread_notifs', String(c));
      } catch (e) {}

      // Update navbar bell badge
      const badges = document.querySelectorAll('#navBellBadge, .ni-badge');
      badges.forEach(b => {
        if (c > 0) {
          b.textContent = c > 99 ? '99+' : String(c);
          b.style.display = 'flex';
        } else {
          b.style.display = 'none';
        }
      });

      // Ensure no legacy nav dots ever display
      const dots = document.querySelectorAll('#navNotifDot, .bnav .ndot');
      dots.forEach(d => {
        d.style.display = 'none';
      });

      if (typeof window.updateHomeownerNotificationDot === 'function') {
        window.updateHomeownerNotificationDot(c);
      }
    }

    /* ── Render ── */
    function renderNotifs() {
      const notifs     = window.HE.notifications || [];
      const unreadList = notifs.filter(n => !n.read);
      const total      = notifs.length;

      /* Update header count */
      const countEl = document.getElementById('nCount');
      if (countEl) {
        if (unreadList.length > 0) {
          countEl.innerHTML = `<strong>${unreadList.length}</strong> unread &nbsp;·&nbsp; ${total} total`;
        } else {
          countEl.textContent = total > 0 ? `All caught up · ${total} total` : 'No notifications yet';
        }
      }

      /* Update header red badge */
      const badgeEl = document.getElementById('nBadge');
      if (badgeEl) {
        if (unreadList.length > 0) {
          badgeEl.textContent = unreadList.length > 99 ? '99+' : String(unreadList.length);
          badgeEl.setAttribute('data-count', String(unreadList.length));
          badgeEl.style.display = 'inline-flex';
        } else {
          badgeEl.textContent = '';
          badgeEl.setAttribute('data-count', '0');
          badgeEl.style.display = 'none';
        }
      }

      /* Sync with Home page and bottom nav */
      syncUnreadCount(unreadList.length);

      /* Hide "mark all" when nothing unread */
      const markBtn = document.getElementById('markAllBtn');
      if (markBtn) {
        markBtn.style.display = unreadList.length ? '' : 'none';
      }

      if (!total) {
        document.getElementById('nBody').innerHTML = emptyStateHTML();
        return;
      }

      /* Group by day */
      const groups = {};
      const order  = [];
      notifs.forEach(n => {
        const k = n.created_at ? dayKey(n.created_at) : 'unknown';
        if (!groups[k]) { groups[k] = []; order.push(k); }
        groups[k].push(n);
      });

      let html = '';
      order.forEach(key => {
        const label    = key === 'unknown' ? 'Earlier' : dayLabel(key);
        const dayItems = groups[key];
        const dayUnread = dayItems.filter(n => !n.read).length;
        html += `<div class="n-day-group">`;
        html += `<div class="n-day-lbl">
          <span class="n-day-lbl-text">${escHtml(label)}</span>
          ${dayUnread ? `<span class="n-unread-pill">${dayUnread}</span>` : ''}
        </div>`;
        html += dayItems.map(n => notifCard(n)).join('');
        html += `</div>`;
      });

      document.getElementById('nBody').innerHTML = html;
    }

    function resolveNotifIcon(n) {
      const title = (n.title || '').toLowerCase();
      const msg   = (n.msg || n.message || '').toLowerCase();
      const icon  = (n.icon || '').toLowerCase();
      const type  = (n.type || '').toLowerCase();

      // Cancelled / Rejected / Declined
      if (type === 'verification_rejected' || type === 'rejected' ||
          title.includes('cancel') || title.includes('reject') || title.includes('decline') ||
          icon.includes('x-circle') || msg.includes('cancelled') || msg.includes('rejected')) {
        return { icon: 'bi-x-circle-fill', cls: 'danger' };
      }

      // Warning / Problem / Alert
      if (type === 'warning' || title.includes('warning') || title.includes('problem') ||
          icon.includes('exclamation') || msg.includes('problem reported')) {
        return { icon: 'bi-exclamation-triangle-fill', cls: 'danger' };
      }

      // Report
      if (type === 'report' || title.includes('report') || icon.includes('shield')) {
        return { icon: 'bi-shield-fill-exclamation', cls: 'verif' };
      }

      // QR Code
      if (title.includes('qr') || msg.includes('qr') || icon.includes('qr')) {
        return { icon: 'bi-qr-code-scan', cls: title.includes('approved') ? 'remit' : 'verif' };
      }

      // Payment / Wallet / Remittance / Money
      if (type === 'remittance' || title.includes('payment') || title.includes('remit') ||
          icon.includes('wallet') || icon.includes('cash') || msg.includes('payment')) {
        return { icon: 'bi-cash-stack', cls: 'remit' };
      }

      // Completed / Verified / Confirmed / Approved / Arrived
      if (type === 'account_verified' || title.includes('verified') || title.includes('approved') ||
          title.includes('complete') || title.includes('confirmed') || msg.includes('completed') ||
          msg.includes('confirmed') || title.includes('arrived')) {
        return { icon: 'bi-patch-check-fill', cls: 'remit' };
      }

      // Overdue / Clock / Expiration
      if (icon.includes('clock') || title.includes('overdue') || msg.includes('overdue') || title.includes('expire')) {
        return { icon: 'bi-clock-history', cls: 'general' };
      }

      // Booking / Schedule / Appointment / Request
      if (title.includes('booking') || title.includes('request') || msg.includes('booking')) {
        return { icon: 'bi-calendar-check-fill', cls: 'general' };
      }

      // Specific service icons
      if (icon === 'plumbing' || title.includes('plumb')) return { icon: 'bi-wrench-adjustable-circle', cls: 'verif' };
      if (icon === 'helper' || title.includes('helper')) return { icon: 'bi-person-arms-up', cls: 'general' };
      if (icon === 'technician' || title.includes('technician') || title.includes('appliance')) return { icon: 'bi-tools', cls: 'purple' };
      if (icon === 'laundry' || title.includes('laundry')) return { icon: 'bi-water', cls: 'cyan' };
      if (icon === 'carpentry' || title.includes('carpentry')) return { icon: 'bi-hammer', cls: 'general' };
      if (icon === 'gardening' || title.includes('garden')) return { icon: 'bi-flower1', cls: 'remit' };
      if (icon === 'house_cleaner' || title.includes('clean')) return { icon: 'bi-stars', cls: 'general' };

      // Explicit bi- icon provided
      if (icon.startsWith('bi-')) return { icon: icon, cls: 'general' };
      if (icon && icon !== 'general' && icon !== 'default') {
        return { icon: 'bi-' + icon, cls: 'general' };
      }

      return { icon: 'bi-bell-fill', cls: 'general' };
    }

    function notifCard(n) {
      const ic = resolveNotifIcon(n);
      return `<div class="n-card${n.read ? '' : ' unread'}" id="nc-${n.id}" onclick="markRead(${n.id})">
        <div class="n-read-ripple"></div>
        <div class="n-card-ic ${ic.cls}"><i class="bi ${ic.icon}"></i></div>
        <div class="n-card-body">
          <div class="n-card-ttl">${escHtml(n.title)}</div>
          <div class="n-card-msg">${escHtml(n.msg)}</div>
          <div class="n-card-time"><i class="bi bi-clock"></i> ${escHtml(n.time)}</div>
        </div>
      </div>`;
    }

    function emptyStateHTML() {
      return `<div class="empty">
        <div class="empty-icon-wrap">
          <svg viewBox="0 0 64 64" fill="none" style="width:46px;height:46px">
            <path d="M20 28a12 12 0 0124 0v8l3 4H17l3-4v-8z" stroke="#E8820C" stroke-width="2" fill="rgba(232,130,12,.1)"/>
            <path d="M29 44a3 3 0 006 0" stroke="#D4790A" stroke-width="2" stroke-linecap="round"/>
            <circle cx="44" cy="14" r="5" fill="#F5A623"/>
          </svg>
        </div>
        <div class="empty-ttl">No Notifications</div>
        <p class="empty-sub">You're all caught up!<br>We'll let you know when something new arrives.</p>
      </div>`;
    }

    function escHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
    }

    /* ── Mark single read ── */
    function markRead(id) {
      const n = (window.HE.notifications || []).find(n => Number(n.id) === Number(id));
      if (!n || n.read) return;

      n.read = true;

      /* Immediately remove unread styles */
      const card = document.getElementById(`nc-${id}`);
      if (card) {
        card.classList.remove('unread');
        card.classList.add('reading');
      }

      const remainingUnread = (window.HE.notifications || []).filter(item => !item.read).length;
      const countEl = document.getElementById('nCount');
      const badgeEl = document.getElementById('nBadge');
      const total = (window.HE.notifications || []).length;
      if (countEl) {
        if (remainingUnread > 0) {
          countEl.innerHTML = `<strong>${remainingUnread}</strong> unread &nbsp;·&nbsp; ${total} total`;
        } else {
          countEl.textContent = total > 0 ? `All caught up · ${total} total` : 'No notifications yet';
        }
      }
      if (badgeEl) {
        if (remainingUnread > 0) {
          badgeEl.textContent = remainingUnread > 99 ? '99+' : String(remainingUnread);
          badgeEl.setAttribute('data-count', String(remainingUnread));
          badgeEl.style.display = 'inline-flex';
        } else {
          badgeEl.textContent = '';
          badgeEl.setAttribute('data-count', '0');
          badgeEl.style.display = 'none';
        }
      }
      const markBtn = document.getElementById('markAllBtn');
      if (markBtn) {
        markBtn.style.display = remainingUnread ? '' : 'none';
      }
      syncUnreadCount(remainingUnread);

      setTimeout(() => {
        renderNotifs();
      }, 280);

      /* Persist to server */
      const form = new FormData();
      form.append('id', id);
      fetch('../api/notifications_api.php', { method: 'POST', body: form })
        .then(r => r.json())
        .then(data => {
          if (data && typeof data.unread_count === 'number') {
            syncUnreadCount(data.unread_count);
          }
        })
        .catch(() => {});
    }

    /* ── Mark all read ── */
    function markAllRead() {
      const hasUnread = (window.HE.notifications || []).some(n => !n.read);
      if (!hasUnread) return;

      document.querySelectorAll('.n-card.unread').forEach(el => el.classList.remove('unread'));

      window.HE.notifications.forEach(n => n.read = true);
      renderNotifs();
      showToast('All notifications marked as read', 'success');
      syncUnreadCount(0);

      const form = new FormData();
      form.append('mark_all', '1');
      fetch('../api/notifications_api.php', { method: 'POST', body: form }).catch(() => {});
    }

    /* ── Polling refresh ── */
    async function refreshClientNotifications(showNewToast = false) {
      try {
        const res  = await fetch('../api/notifications_api.php?t=' + Date.now(), { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !Array.isArray(data.notifications)) return;

        const next = data.notifications.map(n => ({
          id:         Number(n.id),
          title:      n.title,
          msg:        n.message || n.msg,
          time:       n.time,
          created_at: n.created_at || null,
          read:       Number(n.is_read) === 1,
          icon:       n.icon || 'house_cleaner'
        }));

        const beforeIds = getStoredClientNotifIds();
        const nextIds   = next.map(n => String(n.id));
        const newItems  = next.filter(n => !beforeIds.includes(String(n.id)));

        // Preserve optimistic read states for in-flight clicks
        const readIdsInCurrent = new Set(
          (window.HE.notifications || []).filter(n => n.read).map(n => Number(n.id))
        );
        next.forEach(n => {
          if (readIdsInCurrent.has(n.id)) {
            n.read = true;
          }
        });

        window.HE.notifications = next;
        renderNotifs();
        setStoredClientNotifIds(nextIds);

        if (showNewToast && newItems.length) {
          showToast(newItems[0].title || 'New notification', 'success');
        }
      } catch (e) { /* keep existing data if refresh fails */ }
    }

    /* ── Boot ── */
    renderNotifs();
    setStoredClientNotifIds((window.HE.notifications || []).map(n => String(n.id)));
    refreshClientNotifications(false);
    setInterval(() => refreshClientNotifications(true), 8000);
  </script>
</body>

</html>
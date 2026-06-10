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
    'read'       => (bool) $n['is_read'],
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
            <div class="n-ttl">Notifications</div>
            <div class="n-count-sub" id="nCount">
              <?= $unreadCount > 0 ? "$unreadCount unread" : 'All caught up' ?>
            </div>
          </div>
          <button class="n-markall" id="markAllBtn" onclick="markAllRead()">
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
        <div class="ni on"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
        <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;

    /* ── Render ── */
    function renderNotifs() {
      const notifs     = window.HE.notifications;
      const unreadList = notifs.filter(n => !n.read);
      const total      = notifs.length;

      /* Update header count */
      const countEl = document.getElementById('nCount');
      if (unreadList.length > 0) {
        countEl.innerHTML = `<strong>${unreadList.length}</strong> unread &nbsp;·&nbsp; ${total} total`;
      } else {
        countEl.textContent = total > 0 ? `All caught up · ${total} total` : 'No notifications yet';
      }

      /* Hide "mark all" when nothing unread */
      document.getElementById('markAllBtn').style.display = unreadList.length ? '' : 'none';

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

    function notifCard(n) {
      const imgSrc = SVC_IMGS[n.icon] || SVC_IMGS.house_cleaner;
      return `<div class="n-card${n.read ? '' : ' unread'}" id="nc-${n.id}" onclick="markRead(${n.id})">
        <div class="n-read-ripple"></div>
        ${!n.read ? '<div class="n-unread-bar"></div>' : ''}
        <div class="n-ic"><img src="${imgSrc}" alt="" loading="lazy"></div>
        <div class="n-content">
          <div class="n-title">${escHtml(n.title)}</div>
          <div class="n-msg">${escHtml(n.msg)}</div>
          <div class="n-time">
            ${!n.read
              ? '<div class="n-dot"></div>'
              : '<i class="bi bi-check2-all n-read-check"></i>'}
            ${escHtml(n.time)}
          </div>
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
      const n = window.HE.notifications.find(n => n.id === id);
      if (!n || n.read) return;

      /* Visual ripple first */
      const card = document.getElementById(`nc-${id}`);
      if (card) {
        card.classList.add('reading');
        setTimeout(() => {
          n.read = true;
          renderNotifs();
        }, 280);
      } else {
        n.read = true;
        renderNotifs();
      }

      /* Persist to server */
      const form = new FormData();
      form.append('id', id);
      fetch('../api/notifications_api.php', { method: 'POST', body: form }).catch(() => {});
    }

    /* ── Mark all read ── */
    function markAllRead() {
      const hasUnread = window.HE.notifications.some(n => !n.read);
      if (!hasUnread) return;
      window.HE.notifications.forEach(n => n.read = true);
      renderNotifs();
      showToast('All notifications marked as read', 'success');

      const form = new FormData();
      form.append('mark_all', '1');
      fetch('../api/notifications_api.php', { method: 'POST', body: form }).catch(() => {});
    }

    /* ── Polling refresh ── */
    async function refreshClientNotifications(showNewToast = false) {
      try {
        const res  = await fetch('../api/notifications_api.php', { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !Array.isArray(data.notifications)) return;

        const next = data.notifications.map(n => ({
          id:         Number(n.id),
          title:      n.title,
          msg:        n.message || n.msg,
          time:       n.time,
          created_at: n.created_at || null,
          read:       !!n.is_read,
          icon:       n.icon || 'house_cleaner'
        }));

        const beforeIds = getStoredClientNotifIds();
        const nextIds   = next.map(n => String(n.id));
        const newItems  = next.filter(n => !beforeIds.includes(String(n.id)));

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
    setStoredClientNotifIds(window.HE.notifications.map(n => String(n.id)));
    refreshClientNotifications(false);
    setInterval(() => refreshClientNotifications(true), 8000);
  </script>
</body>

</html>
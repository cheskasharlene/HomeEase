<?php /* provider_notifications.php */
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
$access            = enforceProviderSectionAccess('notifications', $conn);
$isVerified        = $access['is_verified'];
$verificationState = $access['state'];
$providerId        = (int) ($_SESSION['provider_id'] ?? 0);
$notifs            = [];
$unread            = 0;

function providerTimeAgo($ts)
{
  $diff = time() - strtotime($ts);
  if ($diff < 60)    return 'Just now';
  if ($diff < 3600)  return floor($diff / 60) . 'm ago';
  if ($diff < 86400) return floor($diff / 3600) . 'h ago';
  return floor($diff / 86400) . 'd ago';
}

$stmt = $conn->prepare("SELECT id, title, message, icon, is_read, created_at FROM provider_notifications WHERE provider_id = ? ORDER BY created_at DESC LIMIT 100");
if ($stmt) {
  $stmt->bind_param('i', $providerId);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $notifs = array_map(function ($n) {
    return [
      'id'         => (int) $n['id'],
      'title'      => $n['title'],
      'msg'        => $n['message'],
      'time'       => providerTimeAgo($n['created_at']),
      'created_at' => $n['created_at'],
      'read'       => (bool) $n['is_read'],
      'icon'       => $n['icon'] ?? 'house_cleaner',
    ];
  }, $rows);
  $unread = count(array_filter($notifs, fn($n) => !$n['read']));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Notifications</title>
  <meta name="description" content="Your HomeEase provider notifications – stay updated on requests and earnings." />
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
              <?= $unread > 0 ? "$unread unread" : 'All caught up' ?></div>
          </div>
          <button class="n-markall" id="markAllBtn" onclick="markAllRead()">
            <i class="bi bi-check2-all"></i> Mark all read
          </button>
        </div>
        <div class="n-body" id="nBody"></div>
      </div>
      <div class="bnav">
        <?php if ($isVerified): ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
          <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span class="nl">Earnings</span></div>
          <div class="ni on"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni on"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Persistent toast element -->
  <div class="he-toast" id="heToast"></div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();

    window.HE = window.HE || {};
    window.HE.notifications    = <?= json_encode(array_values($notifs)) ?>;
    window.HE.verificationState = <?= json_encode($verificationState) ?>;
    window.HE.providerId       = <?= json_encode($providerId) ?>;

    const localNotifKey = 'he_provider_notifs_' + String(window.HE.providerId || 'default');

    /* ── Toast ── */
    function showToast(message, type = 'success') {
      const t = document.getElementById('heToast');
      t.textContent = message;
      t.className = `he-toast ${type}`;
      requestAnimationFrame(() => { t.classList.add('show'); });
      clearTimeout(t._timer);
      t._timer = setTimeout(() => { t.classList.remove('show'); }, 2400);
    }

    /* alias used by activateAndGoDashboard */
    function showNotice(msg, type = 'error') { showToast(msg, type === 'success' ? 'success' : 'error'); }

    /* ── Local storage ── */
    function readLocalNotifs() {
      try { const p = JSON.parse(localStorage.getItem(localNotifKey) || '[]'); return Array.isArray(p) ? p : []; }
      catch (e) { return []; }
    }
    function writeLocalNotifs(list) {
      try { localStorage.setItem(localNotifKey, JSON.stringify(Array.isArray(list) ? list : [])); } catch (e) {}
    }

    /* ── Time helpers ── */
    function providerTimeAgo(ts) {
      const diff = Math.floor((Date.now() - new Date(ts)) / 1000);
      if (diff < 60)    return 'Just now';
      if (diff < 3600)  return Math.floor(diff / 60) + 'm ago';
      if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
      return Math.floor(diff / 86400) + 'd ago';
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

    function escHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
    }

    /* ── Merge local + server ── */
    function mergeNotifications() {
      const localList = readLocalNotifs();
      const byId = new Map();
      localList.forEach(item => byId.set(String(item.id), item));
      window.HE.notifications.forEach(item => {
        if (!byId.has(String(item.id))) byId.set(String(item.id), item);
      });
      window.HE.notifications = Array.from(byId.values())
        .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
    }

    /* ── Render ── */
    function renderNotifs() {
      const notifs     = window.HE.notifications;
      const unreadList = notifs.filter(n => !n.read);
      const total      = notifs.length;

      const countEl = document.getElementById('nCount');
      if (unreadList.length > 0) {
        countEl.innerHTML = `<strong>${unreadList.length}</strong> unread &nbsp;·&nbsp; ${total} total`;
      } else {
        countEl.textContent = total > 0 ? `All caught up · ${total} total` : 'No notifications yet';
      }
      document.getElementById('markAllBtn').style.display = unreadList.length ? '' : 'none';

      if (!total) {
        document.getElementById('nBody').innerHTML = emptyStateHTML();
        return;
      }

      /* Group by day */
      const groups = {}, order = [];
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
      /* Special: account verified card */
      if (n.type === 'account_verified') {
        return `<div class="n-card${n.read ? '' : ' unread'}" id="nc-${n.id}" onclick="markRead('${String(n.id)}')">
          <div class="n-read-ripple"></div>
          ${!n.read ? '<div class="n-unread-bar"></div>' : ''}
          <div class="n-ic" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#059669;font-size:20px;display:flex;align-items:center;justify-content:center;">✔</div>
          <div class="n-content">
            <div class="n-title">${escHtml(n.title)}</div>
            <div class="n-msg">${escHtml(n.msg)}</div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;">
              <span style="font-size:11px;font-weight:800;color:#059669;background:#dcfce7;border:1px solid #86efac;padding:3px 10px;border-radius:999px;">Verified</span>
              <button onclick="activateAndGoDashboard(event)" style="border:none;border-radius:10px;padding:8px 10px;font-size:12px;font-weight:800;background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;cursor:pointer;">Go to Dashboard</button>
            </div>
            <div class="n-time" style="margin-top:7px;">
              ${!n.read ? '<div class="n-dot"></div>' : '<i class="bi bi-check2-all n-read-check"></i>'}
              ${escHtml(n.time || 'Now')}
            </div>
          </div>
        </div>`;
      }

      const img = SVC_IMGS[n.icon] || SVC_IMGS.house_cleaner;
      return `<div class="n-card${n.read ? '' : ' unread'}" id="nc-${n.id}" onclick="markRead(${n.id})">
        <div class="n-read-ripple"></div>
        ${!n.read ? '<div class="n-unread-bar"></div>' : ''}
        <div class="n-ic"><img src="${img}" alt="" loading="lazy"></div>
        <div class="n-content">
          <div class="n-title">${escHtml(n.title)}</div>
          <div class="n-msg">${escHtml(n.msg)}</div>
          <div class="n-time">
            ${!n.read ? '<div class="n-dot"></div>' : '<i class="bi bi-check2-all n-read-check"></i>'}
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

    /* ── Dashboard unlock ── */
    async function activateAndGoDashboard(event) {
      if (event) event.stopPropagation();
      if (window.HE.verificationState !== 'approval_ready') { goPage('provider_home.php'); return; }
      try {
        const fd = new FormData();
        fd.append('action', 'activate_verified_ui');
        const res  = await fetch('../api/provider_verification.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) { showNotice(data.message || 'Could not unlock dashboard.'); return; }
        showNotice('Dashboard unlocked. Redirecting...', 'success');
        goPage('provider_home.php?approved=1');
      } catch (error) {
        showNotice('Could not unlock dashboard right now.');
      }
    }

    /* ── Mark single read ── */
    function markRead(id) {
      const n = window.HE.notifications.find(nf => String(nf.id) === String(id));
      if (!n || n.read) return;

      /* Visual ripple */
      const card = document.getElementById(`nc-${id}`);
      if (card) {
        card.classList.add('reading');
        setTimeout(() => {
          n.read = true;
          /* Update localStorage */
          const localList = readLocalNotifs();
          const localNotif = localList.find(item => String(item.id) === String(id));
          if (localNotif) { localNotif.read = true; writeLocalNotifs(localList); }
          renderNotifs();
        }, 280);
      } else {
        n.read = true;
        const localList = readLocalNotifs();
        const localNotif = localList.find(item => String(item.id) === String(id));
        if (localNotif) { localNotif.read = true; writeLocalNotifs(localList); }
        renderNotifs();
      }

      /* Persist to server */
      const form = new FormData();
      form.append('id', id);
      fetch('../api/provider_notifications_api.php', { method: 'POST', body: form }).catch(() => {});
    }

    /* ── Mark all read ── */
    function markAllRead() {
      const hasUnread = window.HE.notifications.some(n => !n.read);
      if (!hasUnread) return;
      window.HE.notifications.forEach(n => n.read = true);
      const localList = readLocalNotifs().map(item => ({ ...item, read: true }));
      writeLocalNotifs(localList);
      renderNotifs();
      showToast('All notifications marked as read', 'success');

      const form = new FormData();
      form.append('mark_all', '1');
      fetch('../api/provider_notifications_api.php', { method: 'POST', body: form }).catch(() => {});
    }

    /* ── Polling refresh ── */
    async function refreshProviderNotifications(showNewToast = false) {
      try {
        const res  = await fetch('../api/provider_notifications_api.php', { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !Array.isArray(data.notifications)) return;

        const next = data.notifications.map(n => ({
          id:         Number(n.id),
          title:      n.title,
          msg:        n.message,
          time:       providerTimeAgo(n.created_at),
          created_at: n.created_at || null,
          read:       !!n.is_read,
          icon:       n.icon || 'house_cleaner'
        }));

        const previousIds = new Set(window.HE.notifications.map(n => String(n.id)));
        const newItems    = next.filter(n => !previousIds.has(String(n.id)));
        window.HE.notifications = next;
        writeLocalNotifs(next);
        renderNotifs();

        if (showNewToast && newItems.length) {
          showToast(newItems[0].title || 'New notification', 'success');
        }
      } catch (e) { /* keep existing state if refresh fails */ }
    }

    /* ── Boot ── */
    mergeNotifications();
    renderNotifs();
    refreshProviderNotifications(false);
    setInterval(() => refreshProviderNotifications(true), 8000);
  </script>
</body>

</html>
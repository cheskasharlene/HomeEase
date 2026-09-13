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

$rejectionReason = '';
if ($verificationState === 'rejected' && $providerId > 0 && $conn instanceof mysqli) {
  $rejStmt = $conn->prepare("SELECT rejection_reason FROM service_providers WHERE provider_id = ? LIMIT 1");
  if ($rejStmt) {
    $rejStmt->bind_param('i', $providerId);
    $rejStmt->execute();
    $rejRow = $rejStmt->get_result()->fetch_assoc();
    $rejStmt->close();
    $rejectionReason = trim((string) ($rejRow['rejection_reason'] ?? ''));
  }
}

$stmt = $conn->prepare("SELECT id, type, title, message, icon, is_read, created_at FROM provider_notifications WHERE provider_id = ? ORDER BY created_at DESC LIMIT 100");
if ($stmt) {
  $stmt->bind_param('i', $providerId);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  $notifs = array_map(function ($n) {
    return [
      'id'         => (int) $n['id'],
      'type'       => $n['type'] ?? 'general',
      'title'      => $n['title'],
      'msg'        => $n['message'],
      'time'       => providerTimeAgo($n['created_at']),
      'created_at' => $n['created_at'],
      'read'       => ((int) $n['is_read']) === 1,
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
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="n-ttl">Notifications</div>
              <span class="notif-badge" id="nBadge" data-count="<?= (int)$unread ?>" style="<?= $unread > 0 ? 'display:inline-flex;' : 'display:none;' ?>"><?= $unread > 0 ? ($unread > 99 ? '99+' : $unread) : '' ?></span>
            </div>
            <div class="n-count-sub" id="nCount">
              <?= $unread > 0 ? "<strong>$unread</strong> unread &nbsp;·&nbsp; " . count($notifs) . " total" : (count($notifs) > 0 ? "All caught up · " . count($notifs) . " total" : "No notifications yet") ?>
            </div>
          </div>
          <button class="n-markall" id="markAllBtn" onclick="markAllRead()" style="<?= $unread ? '' : 'display:none;' ?>">
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
          <div class="ni ni-bell on" onclick="goPage('provider_notifications.php')">
            <div class="ni-bell-wrap">
              <i class="bi bi-bell-fill"></i>
              <span class="ni-badge" id="navBellBadge" style="display:none;"></span>
            </div>
            <span class="nl">Notifications</span>
          </div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni ni-bell on" onclick="goPage('provider_notifications.php')">
            <div class="ni-bell-wrap">
              <i class="bi bi-bell-fill"></i>
              <span class="ni-badge" id="navBellBadge" style="display:none;"></span>
            </div>
            <span class="nl">Notifications</span>
          </div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Persistent toast element -->
  <div class="he-toast" id="heToast"></div>

  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') {
      initTheme();
    }

    window.HE = window.HE || {};
    window.HE.notifications    = <?= json_encode(array_values($notifs)) ?>;
    window.HE.verificationState = <?= json_encode($verificationState) ?>;
    window.HE.rejectionReason  = <?= json_encode($rejectionReason) ?>;
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

    /* ── Sync unread across tabs & bottom nav ── */
    function syncProviderUnread(count) {
      const c = Math.max(0, Number(count) || 0);
      try {
        localStorage.setItem('he_provider_unread_notifs', String(c));
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

      // Ensure no legacy nav dots ever display over the badge
      const dots = document.querySelectorAll('#navNotifDot, .bnav .ni-bell .ndot, #providerNav .ndot');
      dots.forEach(d => {
        d.style.display = 'none';
      });

      if (typeof window.updateProviderNotificationDot === 'function') {
        window.updateProviderNotificationDot(c);
      }
    }

    /* ── Authoritative Notification Store ── */
    function setNotifications(list) {
      const map = new Map();
      (list || []).forEach(item => {
        if (item && item.id !== undefined) {
          map.set(String(item.id), item);
        }
      });

      /* If provider is rejected, ensure a rejection notification is present */
      if (window.HE.verificationState === 'rejected') {
        const hasRej = Array.from(map.values()).some(n => 
          n && (n.type === 'verification_rejected' || n.type === 'rejected' || (String(n.id) === 'verification_rejected'))
        );
        if (!hasRej) {
          let isSynthRead = false;
          try {
            const synthKey = 'he_provider_read_synthetic_' + String(window.HE.providerId || '0');
            const synthReads = JSON.parse(localStorage.getItem(synthKey) || '[]');
            isSynthRead = synthReads.includes('verification_rejected');
          } catch(e) {}

          const createdAt = new Date().toISOString();
          map.set('verification_rejected', {
            id: 'verification_rejected',
            type: 'verification_rejected',
            title: 'Worker Application Rejected',
            msg: window.HE.rejectionReason ? ('Reason: ' + window.HE.rejectionReason) : 'Your worker verification application was rejected by the admin. Please update your requirements.',
            time: 'Recently',
            created_at: createdAt,
            read: isSynthRead,
            icon: 'bi-x-circle'
          });
        }
      }

      window.HE.notifications = Array.from(map.values())
        .sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
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

      const markBtn = document.getElementById('markAllBtn');
      if (markBtn) {
        markBtn.style.display = unreadList.length ? '' : 'none';
      }

      // Synchronize unread count immediately with navigation red dot
      syncProviderUnread(unreadList.length);

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
      /* Special: rejected worker application card (Red-themed) */
      const isRejection = n.type === 'verification_rejected' || 
                          n.type === 'rejected' || 
                          (n.title && /reject|decline/i.test(n.title)) || 
                          (n.msg && /rejected|declined/i.test(n.msg) && /verification|application|document|requirement/i.test(n.msg));

      if (isRejection) {
        return `<div class="n-card${n.read ? '' : ' unread'}" id="nc-${n.id}" onclick="markRead('${String(n.id)}')">
          <div class="n-read-ripple"></div>
          <div class="n-card-ic danger"><i class="bi bi-x-circle-fill"></i></div>
          <div class="n-card-body">
            <div class="n-card-ttl">${escHtml(n.title)}</div>
            <div class="n-card-msg">${escHtml(n.msg)}</div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;flex-wrap:wrap;gap:8px;">
              <span class="n-tag danger"><i class="bi bi-x-circle-fill"></i> Rejected</span>
              <button onclick="event.stopPropagation(); markRead('${String(n.id)}'); goPage('provider_home.php');" style="border:none;border-radius:9px;padding:6px 12px;font-size:11px;font-weight:700;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;cursor:pointer;box-shadow:0 3px 10px rgba(220,38,38,0.25);">Update Requirements</button>
            </div>
            <div class="n-card-time"><i class="bi bi-clock"></i> ${escHtml(n.time || 'Now')}</div>
          </div>
        </div>`;
      }

      /* Special: account verified card */
      if (n.type === 'account_verified') {
        return `<div class="n-card${n.read ? '' : ' unread'}" id="nc-${n.id}" onclick="markRead('${String(n.id)}')">
          <div class="n-read-ripple"></div>
          <div class="n-card-ic remit"><i class="bi bi-patch-check-fill"></i></div>
          <div class="n-card-body">
            <div class="n-card-ttl">${escHtml(n.title)}</div>
            <div class="n-card-msg">${escHtml(n.msg)}</div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;flex-wrap:wrap;gap:8px;">
              <span class="n-tag success"><i class="bi bi-patch-check-fill"></i> Verified</span>
              <button onclick="activateAndGoDashboard(event, '${String(n.id)}')" style="border:none;border-radius:9px;padding:6px 12px;font-size:11px;font-weight:700;background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;cursor:pointer;box-shadow:0 3px 10px rgba(232,130,12,0.25);">Go to Dashboard</button>
            </div>
            <div class="n-card-time"><i class="bi bi-clock"></i> ${escHtml(n.time || 'Now')}</div>
          </div>
        </div>`;
      }

      const ic = resolveNotifIcon(n);
      return `<div class="n-card${n.read ? '' : ' unread'}" id="nc-${n.id}" onclick="markRead('${String(n.id)}')">
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

    /* ── Dashboard unlock ── */
    async function activateAndGoDashboard(event, notifId = null) {
      if (event) event.stopPropagation();
      if (notifId) markRead(notifId);
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
      const n = (window.HE.notifications || []).find(nf => String(nf.id) === String(id));
      if (!n || n.read) return;

      n.read = true;

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
      syncProviderUnread(remainingUnread);

      setTimeout(() => {
        renderNotifs();
      }, 280);

      /* If synthetic ID, remember in local storage */
      if (typeof id === 'string' && !/^\d+$/.test(id)) {
        try {
          const synthKey = 'he_provider_read_synthetic_' + String(window.HE.providerId || '0');
          const synthReads = JSON.parse(localStorage.getItem(synthKey) || '[]');
          if (!synthReads.includes(id)) {
            synthReads.push(id);
            localStorage.setItem(synthKey, JSON.stringify(synthReads));
          }
        } catch(e) {}
      }

      /* Persist to server if valid DB ID */
      if (typeof id === 'number' || (typeof id === 'string' && /^\d+$/.test(id))) {
        const form = new FormData();
        form.append('id', id);
        fetch('../api/provider_notifications_api.php', { method: 'POST', body: form })
          .then(r => r.json())
          .then(data => {
            if (data && typeof data.unread_count === 'number') {
              syncProviderUnread(data.unread_count);
            }
          })
          .catch(() => {});
      } else {
        syncProviderUnread((window.HE.notifications || []).filter(item => !item.read).length);
      }
    }

    /* ── Mark all read ── */
    function markAllRead() {
      const hasUnread = (window.HE.notifications || []).some(n => !n.read);
      if (!hasUnread) return;

      document.querySelectorAll('.n-card.unread').forEach(el => el.classList.remove('unread'));

      window.HE.notifications.forEach(n => n.read = true);
      renderNotifs();
      showToast('All notifications marked as read', 'success');
      syncProviderUnread(0);

      try {
        const synthKey = 'he_provider_read_synthetic_' + String(window.HE.providerId || '0');
        const synthReads = ['verification_rejected'];
        localStorage.setItem(synthKey, JSON.stringify(synthReads));
      } catch(e) {}

      const form = new FormData();
      form.append('mark_all', '1');
      fetch('../api/provider_notifications_api.php', { method: 'POST', body: form })
        .catch(() => {});
    }

    /* ── Polling refresh ── */
    async function refreshProviderNotifications(showNewToast = false) {
      try {
        const res  = await fetch('../api/provider_notifications_api.php?t=' + Date.now(), { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !Array.isArray(data.notifications)) return;

        const fetched = data.notifications.map(n => ({
          id:         Number(n.id),
          type:       n.type || 'general',
          title:      n.title,
          msg:        n.message,
          time:       n.time || providerTimeAgo(n.created_at),
          created_at: n.created_at || null,
          read:       Number(n.is_read) === 1,
          icon:       n.icon || 'house_cleaner'
        }));

        const previousIds = new Set((window.HE.notifications || []).map(n => String(n.id)));
        const newItems    = fetched.filter(n => !previousIds.has(String(n.id)));

        // Preserve optimistic read states for in-flight requests
        const readIdsInCurrent = new Set(
          (window.HE.notifications || []).filter(n => n.read).map(n => String(n.id))
        );
        fetched.forEach(n => {
          if (readIdsInCurrent.has(String(n.id))) {
            n.read = true;
          }
        });

        setNotifications(fetched);
        renderNotifs();

        if (showNewToast && newItems.length) {
          const first = newItems[0];
          const isRej = first.type === 'verification_rejected' || first.type === 'rejected' || (first.title && /reject|decline/i.test(first.title));
          showToast(first.title || 'New notification', isRej ? 'error' : 'success');
        }
      } catch (e) { /* keep existing state if refresh fails */ }
    }

    /* ── Boot ── */
    setNotifications(window.HE.notifications);
    renderNotifs();
    refreshProviderNotifications(false);
    setInterval(() => refreshProviderNotifications(true), 8000);
  </script>
</body>

</html>
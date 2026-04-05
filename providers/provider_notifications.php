<?php /* provider_notifications.php */
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
$access = enforceProviderSectionAccess('notifications', $conn);
$isVerified = $access['is_verified'];
$verificationState = $access['state'];
$notifs = [
  ['id' => 1, 'title' => 'New service request', 'msg' => 'You have a new plumbing request waiting.', 'time' => 'Just now', 'read' => false, 'icon' => 'plumbing'],
  ['id' => 2, 'title' => 'Job reminder', 'msg' => 'Upcoming job with Jane Smith starts in 2 hours.', 'time' => '2h ago', 'read' => false, 'icon' => 'cleaning'],
  ['id' => 3, 'title' => 'New review posted', 'msg' => 'A customer rated your latest service 5 stars!', 'time' => 'Yesterday', 'read' => true, 'icon' => 'electrical'],
];

if ($verificationState === 'approval_ready' || $verificationState === 'verified') {
  array_unshift($notifs, [
    'id' => 999,
    'title' => 'You are now a Verified Provider!',
    'msg' => 'You can now accept bookings and go online.',
    'time' => 'Now',
    'read' => ($verificationState === 'verified'),
    'icon' => 'verified',
    'type' => 'approval',
  ]);
}
$unread = count(array_filter($notifs, fn($n) => !$n['read']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Notifications</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
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
            <div style="color:#6B7280;font-size:12px;" id="nCount">
              <?= $unread > 0 ? "$unread unread" : 'All caught up' ?></div>
          </div>
          <button class="n-markall" onclick="markAllRead()">Mark all read</button>
        </div>
        <div class="n-body" id="nBody"></div>
      </div>
      <div class="bnav">
        <?php if ($isVerified): ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
          <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span
            class="nl">Calendar</span></div>
          <div class="ni on"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
          <div class="ni on"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();

    function showNotice(message, type = 'error') {
      const old = document.getElementById('notifToast');
      if (old) old.remove();

      const toast = document.createElement('div');
      toast.id = 'notifToast';
      toast.textContent = message;
      toast.style.position = 'fixed';
      toast.style.left = '50%';
      toast.style.bottom = '98px';
      toast.style.transform = 'translateX(-50%)';
      toast.style.width = 'min(92%, 420px)';
      toast.style.zIndex = '9999';
      toast.style.padding = '12px 14px';
      toast.style.borderRadius = '12px';
      toast.style.fontSize = '13px';
      toast.style.fontWeight = '800';
      toast.style.boxShadow = '0 10px 26px rgba(0,0,0,.16)';
      toast.style.border = type === 'success' ? '1px solid #86efac' : '1px solid #fecaca';
      toast.style.background = type === 'success' ? '#dcfce7' : '#fef2f2';
      toast.style.color = type === 'success' ? '#166534' : '#991b1b';
      toast.style.textAlign = 'center';
      document.body.appendChild(toast);

      setTimeout(() => {
        toast.style.transition = 'opacity .25s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 260);
      }, 2200);
    }
    window.HE = window.HE || {};
    window.HE.notifications = <?= json_encode(array_values($notifs)) ?>;
    window.HE.verificationState = <?= json_encode($verificationState) ?>;

    function renderNotifs() {
      const notifs = window.HE.notifications, unread = notifs.filter(n => !n.read), read = notifs.filter(n => n.read);
      document.getElementById('nCount').textContent = unread.length > 0 ? `${unread.length} unread` : 'All caught up';
      let html = '';
      if (unread.length) { html += `<div class="n-section-lbl">New</div>`; html += unread.map(n => notifCard(n)).join(''); }
      if (read.length) { html += `<div class="n-section-lbl" style="margin-top:18px;">Earlier</div>`; html += read.map(n => notifCard(n)).join(''); }
      if (!notifs.length) html = `<div class="empty"><div class="empty-ttl">No Notifications</div><p>You're all caught up!</p></div>`;
      document.getElementById('nBody').innerHTML = html;
    }
    function notifCard(n) {
      if (n.type === 'approval') {
        return `<div class="n-card${n.read ? '' : ' unread'}" onclick="markRead(${n.id})">${!n.read ? '<div class="n-unread-bar"></div>' : ''}<div class="n-ic" style="background:linear-gradient(135deg,#dcfce7,#bbf7d0);color:#059669;font-size:20px;">✔</div><div class="n-content"><div class="n-title">${n.title}</div><div class="n-msg">${n.msg}</div><div style="display:flex;align-items:center;justify-content:space-between;margin-top:8px;"><span style="font-size:11px;font-weight:800;color:#059669;background:#dcfce7;border:1px solid #86efac;padding:3px 10px;border-radius:999px;">Verified</span><button onclick="activateAndGoDashboard(event)" style="border:none;border-radius:10px;padding:8px 10px;font-size:12px;font-weight:800;background:linear-gradient(135deg,#E8820C,#F5A623);color:#fff;cursor:pointer;">Go to Dashboard</button></div></div></div>`;
      }
      const img = SVC_IMGS[n.icon] || SVC_IMGS.cleaning;
      return `<div class="n-card${n.read ? '' : ' unread'}" onclick="markRead(${n.id})">${!n.read ? '<div class="n-unread-bar"></div>' : ''}<div class="n-ic"><img src="${img}" alt=""></div><div class="n-content"><div class="n-title">${n.title}</div><div class="n-msg">${n.msg}</div><div class="n-time">${!n.read ? '<div class="n-dot"></div>' : '<i class="bi bi-check2-all" style="color:var(--teal);font-size:12px;"></i>'}${n.time}</div></div></div>`;
    }

    async function activateAndGoDashboard(event) {
      if (event) event.stopPropagation();

      if (window.HE.verificationState !== 'approval_ready') {
        goPage('provider_home.php');
        return;
      }

      try {
        const fd = new FormData();
        fd.append('action', 'activate_verified_ui');
        const res = await fetch('../api/provider_verification.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          showNotice(data.message || 'Could not unlock dashboard.');
          return;
        }
        showNotice('Dashboard unlocked. Redirecting...', 'success');
        goPage('provider_home.php?approved=1');
      } catch (error) {
        showNotice('Could not unlock dashboard right now.');
      }
    }

    function markRead(id) { const n = window.HE.notifications.find(nf => nf.id === id); if (n && !n.read) { n.read = true; renderNotifs(); } }
    function markAllRead() { window.HE.notifications.forEach(n => n.read = true); renderNotifs(); }
    renderNotifs();
  </script>
</body>

</html>
<?php

session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

require_once '../api/db.php';
$uid = $_SESSION['user_id'];
$stmt = $conn->prepare(
  "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50"
);
$stmt->bind_param("i", $uid);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function timeAgo($ts)
{
  $diff = time() - strtotime($ts);
  if ($diff < 60)
    return 'Just now';
  if ($diff < 3600)
    return floor($diff / 60) . 'm ago';
  if ($diff < 86400)
    return floor($diff / 3600) . 'h ago';
  return floor($diff / 86400) . 'd ago';
}

$notifications = array_map(function ($n) {
  return [
    'id' => (int) $n['id'],
    'title' => $n['title'],
    'msg' => $n['message'],
    'time' => timeAgo($n['created_at']),
    'read' => (bool) $n['is_read'],
    'icon' => $n['icon'] ?? 'cleaning',
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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
              <?= $unreadCount > 0 ? "$unreadCount unread" : 'All caught up' ?>
            </div>
          </div>
          <button class="n-markall" onclick="markAllRead()">Mark all read</button>
        </div>
        <div class="n-body" id="nBody"></div>
      </div>
      <div id="navContainer"></div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>initTheme();</script>
  <script>
    window.HE = window.HE || {};
    window.HE.notifications = <?= json_encode($notifications) ?>;

    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni" onclick="goPage('../home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('service_selection.php')" style="cursor:pointer;"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni on"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span></div>
        <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;

    function renderNotifs() {
      const notifs = window.HE.notifications;
      const unread = notifs.filter(n => !n.read);
      const read = notifs.filter(n => n.read);
      document.getElementById('nCount').textContent =
        unread.length > 0 ? `${unread.length} unread` : 'All caught up';

      let html = '';
      if (unread.length) {
        html += `<div class="n-section-lbl">New</div>`;
        html += unread.map(n => notifCard(n)).join('');
      }
      if (read.length) {
        html += `<div class="n-section-lbl" style="margin-top:${unread.length ? '18px' : '4px'};">Earlier</div>`;
        html += read.map(n => notifCard(n)).join('');
      }
      if (!notifs.length) {
        html = `<div class="empty">
          <svg viewBox="0 0 64 64" fill="none" style="width:70px;height:70px"><circle cx="32" cy="32" r="30" fill="#FFF3E0"/><path d="M20 28a12 12 0 0124 0v8l3 4H17l3-4v-8z" stroke="#a78bfa" stroke-width="2" fill="none"/><path d="M29 44a3 3 0 006 0" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/></svg>
          <div class="empty-ttl">No Notifications</div>
          <p style="font-size:13px;">You're all caught up!</p>
        </div>`;
      }
      document.getElementById('nBody').innerHTML = html;
    }

    function notifCard(n) {
      const imgSrc = SVC_IMGS[n.icon] || SVC_IMGS.cleaning;
      return `<div class="n-card${n.read ? '' : ' unread'}" onclick="markRead(${n.id})">
        ${!n.read ? '<div class="n-unread-bar"></div>' : ''}
        <div class="n-ic"><img src="${imgSrc}" alt=""></div>
        <div class="n-content">
          <div class="n-title">${n.title}</div>
          <div class="n-msg">${n.msg}</div>
          <div class="n-time">
            ${!n.read
          ? '<div class="n-dot"></div>'
          : '<i class="bi bi-check2-all" style="color:var(--teal);font-size:12px;"></i>'}
            ${n.time}
          </div>
        </div>
      </div>`;
    }

    function markRead(id) {
      const n = window.HE.notifications.find(n => n.id === id);
      if (!n || n.read) return;
      n.read = true;
      renderNotifs();

      const form = new FormData();
      form.append('id', id);
      fetch('../api/notifications_api.php', { method: 'POST', body: form }).catch(() => { });
    }

    function markAllRead() {
      window.HE.notifications.forEach(n => n.read = true);
      renderNotifs();

      const form = new FormData();
      form.append('mark_all', '1');
      fetch('../api/notifications_api.php', { method: 'POST', body: form }).catch(() => { });
    }

    renderNotifs();
  </script>
</body>

</html>
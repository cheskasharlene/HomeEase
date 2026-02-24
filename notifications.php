<?php session_start(); ?>
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
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
    #notifications {
      background: var(--bg-screen);
      justify-content: flex-start;
    }

    .n-scroll {
      width: 100%;
      flex: 1;
      overflow-y: auto;
      padding-bottom: 80px;
    }

    .n-hdr {
      width: 100%;
      padding: 48px 22px 22px;
      background: var(--teal);
      border-radius: 0 0 28px 28px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .n-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #fff;
    }

    .n-markall {
      font-size: 12px;
      color: rgba(255, 255, 255, .8);
      font-weight: 700;
      cursor: pointer;
      background: rgba(255, 255, 255, .15);
      padding: 6px 12px;
      border-radius: 20px;
      border: none;
    }

    .n-markall:hover {
      background: rgba(255, 255, 255, .25);
    }

    .n-body {
      padding: 18px;
    }

    .n-section-lbl {
      font-size: 11px;
      font-weight: 800;
      color: var(--tm);
      text-transform: uppercase;
      letter-spacing: .8px;
      margin-bottom: 10px;
      margin-top: 4px;
    }

    .n-card {
      background: #fff;
      border-radius: 17px;
      padding: 15px;
      margin-bottom: 11px;
      display: flex;
      gap: 13px;
      align-items: flex-start;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .05);
      cursor: pointer;
      transition: transform .15s;
      position: relative;
      overflow: hidden;
    }

    .n-card:hover {
      transform: translateX(3px);
    }

    .n-card.unread {
      border-left: 3px solid var(--teal);
      background: linear-gradient(to right, #f0fdfa, #fff 60%);
    }

    .n-unread-bar {
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 3px;
      background: var(--teal);
      border-radius: 3px 0 0 3px;
    }

    .n-ic {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      overflow: hidden;
      flex-shrink: 0;
    }

    .n-ic img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .n-content {
      flex: 1;
    }

    .n-title {
      font-size: 14px;
      font-weight: 700;
      color: var(--td);
      margin-bottom: 3px;
    }

    .n-msg {
      font-size: 12px;
      color: var(--tm);
      line-height: 1.4;
    }

    .n-time {
      font-size: 11px;
      color: #5EEAD4;
      margin-top: 5px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .n-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--teal);
      flex-shrink: 0;
      margin-top: 2px;
    }

    body.dark .n-card {
      background: var(--bg-card);
    }

    body.dark .n-card.unread {
      background: linear-gradient(to right, var(--pbg), var(--bg-card) 60%);
      border-left-color: var(--teal);
    }

    body.dark .n-unread-bar {
      background: var(--teal);
    }

    body.dark .n-title {
      color: var(--td);
    }

    body.dark .n-msg {
      color: var(--tm);
    }

    body.dark .n-time {
      color: var(--plight);
    }

    body.dark .n-dot {
      background: var(--teal);
    }

    body.dark .n-section-lbl {
      color: var(--tm);
    }

    body.dark .empty-ttl {
      color: var(--td);
    }

    body.dark .empty {
      color: var(--tm);
    }

    .empty {
      text-align: center;
      padding: 60px 20px;
      color: var(--tm);
    }

    .empty-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 17px;
      font-weight: 700;
      color: var(--td);
      margin: 14px 0 8px;
    }
  </style>
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
            <div style="color:rgba(255,255,255,.7);font-size:12px;" id="nCount">3 unread</div>
          </div>
          <button class="n-markall" onclick="markAllRead()">Mark all read</button>
        </div>
        <div class="n-body" id="nBody"></div>
      </div>
      <div id="navContainer"></div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>initTheme();</script>
  <script>
    document.getElementById('navContainer').innerHTML = `
  <div class="bnav">
    <div class="ni" onclick="goPage('home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
    <div class="ni" onclick="goPage('bookings.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
    <div class="ni" onclick="goPage('bookings.php?newbooking=1')" style="cursor:pointer;"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
    <div class="ni on"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
    <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
  </div>`;

    function renderNotifs() {
      const notifs = window.HE.notifications;
      const unread = notifs.filter(n => !n.read);
      const read = notifs.filter(n => n.read);
      document.getElementById('nCount').textContent = unread.length > 0 ? `${unread.length} unread` : 'All caught up';

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
      <svg viewBox="0 0 64 64" fill="none" style="width:70px;height:70px"><circle cx="32" cy="32" r="30" fill="#f0fdfa"/><path d="M20 28a12 12 0 0124 0v8l3 4H17l3-4v-8z" stroke="#a78bfa" stroke-width="2" fill="none"/><path d="M29 44a3 3 0 006 0" stroke="#7c3aed" stroke-width="2" stroke-linecap="round"/></svg>
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
        ${!n.read ? '<div class="n-dot"></div>' : '<i class="bi bi-check2-all" style="color:var(--teal);font-size:12px;"></i>'}
        ${n.time}
      </div>
    </div>
  </div>`;
    }

    function markRead(id) {
      const n = window.HE.notifications.find(n => n.id === id);
      if (n) { n.read = true; renderNotifs(); }
    }

    function markAllRead() {
      window.HE.notifications.forEach(n => n.read = true);
      renderNotifs();
    }

    renderNotifs();
  </script>
</body>

</html>
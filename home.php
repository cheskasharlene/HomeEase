<?php
session_start();
if (empty($_SESSION['user_id'])) {
  header('Location: index.php');
  exit;
}

$hour = (int) date('H');
if ($hour < 12)
  $greeting = 'Good morning';
elseif ($hour < 18)
  $greeting = 'Good afternoon';
else
  $greeting = 'Good evening';

$userName = htmlspecialchars($_SESSION['user_name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Home</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <link href="assets/css/home.css" rel="stylesheet">
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

    <div class="screen" id="home">
      <div class="h-scroll">
        <div class="h-hdr">
          <div class="h-top">
            <div>
              <div class="h-greet"><?= $greeting ?></div>
              <div class="h-name" id="hUserName"><?= $userName ?></div>
            </div>
            <div class="h-top-right">
              <button class="dm-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="bi bi-moon-fill"
                  id="dmIcon"></i></button>
              <div class="h-bell" onclick="openChat('support')" title="Chat with us" style="position:relative;">
                <i class="bi bi-chat-dots-fill"></i>
                <div
                  style="position:absolute;top:4px;right:4px;width:8px;height:8px;background:#f59e0b;border-radius:50%;border:2px solid var(--teal);">
                </div>
              </div>
              <div class="h-bell" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i>
                <div class="h-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
            </div>
          </div>
          <div class="s-bar" onclick="openSearch()"><i class="bi bi-search"></i><span>Search for a service...</span>
          </div>
        </div>

        <div class="h-body">
          <div class="sec-ttl" style="margin-top:18px;">Our Services</div>
          <div class="svc-grid" id="svcGrid"></div>

          <div class="promo" onclick="openAllOffers()">
            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80" alt="Promo">
            <div class="promo-overlay"></div>
            <div class="promo-content">
              <div class="promo-tag">LIMITED OFFER</div>
              <div class="promo-ttl">20% Off Your<br>First Booking!</div>
              <div class="promo-s">Use code EASE20 · Tap to see more offers</div>
            </div>
          </div>

          <div class="sec-row">
            <div class="sec-ttl" style="margin-bottom:0;">Most Popular Services</div>
            <span class="see-more" onclick="openAllServices()">See more →</span>
          </div>
          <div class="pop-row" id="popRow"></div>
          <div class="h-pb"></div>
        </div>
      </div>
      <div id="navContainer"></div>
    </div>
  </div>

  <script src="assets/js/app.js"></script>
  <script>
    window.HE = window.HE || {};
    window.HE.user = {
      name: <?= json_encode($_SESSION['user_name'] ?? '') ?>,
      email: <?= json_encode($_SESSION['user_email'] ?? '') ?>,
      phone: <?= json_encode($_SESSION['user_phone'] ?? '') ?>,
      address: <?= json_encode($_SESSION['user_address'] ?? '') ?>
    };

    (function () {
      const ic = document.getElementById('dmIcon');
      if (ic && document.body.classList.contains('dark')) ic.className = 'bi bi-sun-fill';
    })();

    fetch('api/notifications_api.php')
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          const unread = data.notifications.filter(n => n.is_read == 0);
          if (unread.length > 0) document.getElementById('bellDot').style.display = 'block';
        }
      }).catch(() => { });

    const svcGrid = document.getElementById('svcGrid');
    Object.entries(SVCS).forEach(([name, data]) => {
      svcGrid.innerHTML += `
    <div class="svc-card" onclick="goPage('bookings.php?svc=${encodeURIComponent(name)}&newbooking=1')">
      <div class="svc-ic">${data.ic}</div>
      <div class="svc-nm">${name}</div>
    </div>`;
    });

    const popData = [{
      svc: 'Cleaning',
      title: 'Deep Home Cleaning',
      desc: 'Complete house cleaning',
      img: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=300&q=80',
      price: '₱599',
      stars: '⭐ 4.9 (238)'
    },
    {
      svc: 'Plumbing',
      title: 'Pipe Leak Repair',
      desc: 'Fix leaks & clogs fast',
      img: 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=300&q=80',
      price: '₱450',
      stars: '⭐ 4.8 (184)'
    },
    {
      svc: 'Electrical',
      title: 'Electrical Wiring',
      desc: 'Safe & certified work',
      img: 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=300&q=80',
      price: '₱750',
      stars: '⭐ 4.9 (312)'
    },
    {
      svc: 'Gardening',
      title: 'Garden Makeover',
      desc: 'Landscaping & trimming',
      img: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=300&q=80',
      price: '₱850',
      stars: '⭐ 4.7 (96)'
    },
    {
      svc: 'Painting',
      title: 'Interior Painting',
      desc: 'Walls & ceilings refreshed',
      img: 'https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=300&q=80',
      price: '₱800',
      stars: '⭐ 4.8 (150)'
    },
    ];
    const popRow = document.getElementById('popRow');
    popData.forEach(p => {
      popRow.innerHTML += `
    <div class="pop-card" onclick="goPage('bookings.php?svc=${encodeURIComponent(p.svc)}&newbooking=1')">
      <img class="pop-img" src="${p.img}" alt="${p.title}">
      <div class="pop-info">
        <div class="pop-nm">${p.title}</div>
        <div class="pop-d">${p.desc}</div>
        <div class="pop-foot"><div class="pop-price">${p.price}</div><div class="pop-stars">${p.stars}</div></div>
      </div>
    </div>`;
    });

    document.getElementById('navContainer').innerHTML = `
  <div class="bnav">
    <div class="ni on"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
    <div class="ni" onclick="goPage('bookings.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
    <div class="ni" onclick="goPage('bookings.php?newbooking=1')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
    <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span><div class="ndot"></div></div>
    <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
  </div>`;
  </script>
</body>

</html>
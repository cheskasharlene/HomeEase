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
  <link href="assets/css/main.css?v=1.2" rel="stylesheet">
  <link href="assets/css/home.css?v=1.2" rel="stylesheet">
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
              <div class="h-bell" onclick="openChat('support')" title="Chat with us" style="position:relative;">
                <i class="bi bi-chat-dots-fill"></i>
                <div
                  style="position:absolute;top:4px;right:4px;width:8px;height:8px;background:#f59e0b;border-radius:50%;">
                </div>
              </div>
              <div class="h-bell" onclick="goPage('clients/notifications.php')" style="position:relative;">
                <i class="bi bi-bell-fill"></i>
                <div class="h-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
            </div>
          </div>
          <div class="s-bar" onclick="openSearch()"><i class="bi bi-search"></i><span>Search for a service...</span>
          </div>
        </div>



        <div class="sec-row">
          <div class="sec-ttl">Our Services</div><span class="see-more" onclick="openAllServices()">See all →</span>
        </div>
        <div class="svc-grid" id="svcGrid"></div>


        <div class="sec-row">
          <div class="sec-ttl">Most Popular Services</div><span class="see-more" onclick="openAllServices()">See more
            →</span>
        </div>
        <div class="pop-row" id="popRow"></div>


        <div class="sec-row">
          <div class="sec-ttl">Our Pros</div><span class="see-more" onclick="goPage('workers.php')">See all →</span>
        </div>
        <div class="worker-row" id="workerRow"></div>


        <div class="h-pb"></div>
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

    // Services grid – unique icon & color per service
    const svcThemeMap = {
      'Cleaning': { icon: 'bi-stars', css: 'svc-cleaning', price: 400 },
      'Plumbing': { icon: 'bi-wrench-adjustable-circle', css: 'svc-plumbing', price: 400 },
      'Helper': { icon: 'bi-person-arms-up', css: 'svc-helper', price: 350 },
      'Appliance Technician': { icon: 'bi-tools', css: 'svc-technician', price: 400 },
      'Laundry': { icon: 'bi-basket2-fill', css: 'svc-laundry', price: 300 },
      'Carpentry': { icon: 'bi-hammer', css: 'svc-carpentry', price: 500 },
    };

    const svcGrid = document.getElementById('svcGrid');
    Object.entries(SVCS).forEach(([name, data]) => {
      const theme = svcThemeMap[name] || { icon: 'bi-tools', css: '', price: data.hr };
      svcGrid.innerHTML += `
        <div class="svc-card" data-svc="${data.key}" onclick="goPage('clients/booking_form.php?svc=${encodeURIComponent(name)}&newbooking=1')">
          <div class="svc-ic ${theme.css}"><i class="bi ${theme.icon}"></i></div>
          <div class="svc-nm">${name}</div>
          <div class="svc-price">from ₱${theme.price.toLocaleString()}/hr</div>
        </div>`;
    });


    const popData = [
      { svc: 'Cleaning', title: 'Deep Home Cleaning', desc: 'Complete home and office cleaning', img: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=300&q=80', price: '₱800' },
      { svc: 'Plumbing', title: 'Pipe Leak Repair', desc: 'Fix leaks, clogs, and pipe issues', img: 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=300&q=80', price: '₱800' },
      { svc: 'Helper', title: 'Household Helper', desc: 'Errands, moving, and general assistance', img: 'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=300&q=80', price: '₱700' },
      { svc: 'Appliance Technician', title: 'Appliance Diagnosis', desc: 'Repair and diagnostics for appliances', img: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=300&q=80', price: '₱800' },
      { svc: 'Laundry', title: 'Wash & Fold Service', desc: 'Laundry washing, folding, and ironing', img: 'https://images.unsplash.com/photo-1604335399105-a0c585fd81a1?w=300&q=80', price: '₱600' },
      { svc: 'Carpentry', title: 'Furniture & Wood Repair', desc: 'Installations, repairs, and builds', img: 'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=300&q=80', price: '₱1,000' },
    ];
    const popRow = document.getElementById('popRow');
    popData.forEach(p => {
      popRow.innerHTML += `
        <div class="pop-card" onclick="goPage('clients/booking_form.php?svc=${encodeURIComponent(p.svc)}&newbooking=1')">
          <img class="pop-img" src="${p.img}" alt="${p.title}">
          <div class="pop-info">
            <div class="pop-nm">${p.title}</div>
            <div class="pop-d">${p.desc}</div>
            <div class="pop-price">${p.price}</div>
          </div>
        </div>`;
    });


    async function loadPros() {
      const workerRow = document.getElementById('workerRow');
      workerRow.innerHTML = '<div style="padding:20px;color:var(--tm);font-size:13px;font-family:Nunito,sans-serif;">Loading...</div>';
      try {
        const res = await fetch('api/workers_api.php?action=pros', { cache: 'no-store' });
        const data = await res.json();
        if (!data.success || !data.pros.length) {
          workerRow.innerHTML = '<div style="padding:20px;color:var(--tm);font-size:13px;font-family:Nunito,sans-serif;">No pros available.</div>';
          return;
        }
        workerRow.innerHTML = data.pros.map(w => `
          <div class="worker-card" onclick="goPage('worker_profile.php?id=${w.id}')">
            ${w.top ? '<div class="worker-badge">TOP</div>' : ''}
            <img class="worker-avatar" src="${w.img}" alt="${w.name}"
              onerror="this.src='https://ui-avatars.com/api/?name='+encodeURIComponent('${w.name}')+'&background=FDECC8&color=F5A623&size=128'">
            <div class="worker-name">${w.name} ${w.is_verified ? '<i class="bi bi-patch-check-fill" style="color:#10b981;font-size:12px;"></i>' : ''}</div>
            <div class="worker-role">${w.specialty}</div>
            <div class="worker-jobs">${w.jobs_done} jobs done</div>
          </div>`).join('');
      } catch (e) {
        workerRow.innerHTML = '<div style="padding:20px;color:var(--tm);font-size:13px;font-family:Nunito,sans-serif;">Could not load pros.</div>';
      }
    }
    loadPros();





    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni on"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('clients/booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('clients/service_selection.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('clients/notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span><div class="ndot"></div></div>
        <div class="ni" onclick="goPage('clients/profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;
  </script>
</body>

</html>
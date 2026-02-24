<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no"/>
  <title>HomeEase – Home</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>
  #home { background:var(--bg-screen); justify-content:flex-start; }
  .h-scroll { width:100%; flex:1; overflow-y:auto; padding-bottom:80px; }
  .h-hdr { width:100%; padding:48px 22px 22px; background:var(--teal); border-radius:0 0 28px 28px; }
  .h-top  { display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; }
  .h-greet { color:rgba(255,255,255,.8); font-size:13px; font-weight:500; }
  .h-name  { color:#fff; font-family:'Poppins',sans-serif; font-size:21px; font-weight:700; }
  .h-top-right { display:flex; align-items:center; gap:8px; }
  .h-bell  { width:38px; height:38px; background:rgba(255,255,255,.15); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; cursor:pointer; position:relative; }
  .h-bell-dot { position:absolute; top:4px; right:4px; width:8px; height:8px; background:#f59e0b; border-radius:50%; border:2px solid var(--teal); }
  .h-chat  { width:38px; height:38px; background:rgba(255,255,255,.15); border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; cursor:pointer; position:relative; transition:background .2s; }
  .h-chat:hover { background:rgba(255,255,255,.25); }
  .h-chat-badge { position:absolute; top:2px; right:2px; width:16px; height:16px; background:#ef4444; border-radius:50%; border:2px solid var(--teal); font-size:9px; font-weight:700; display:flex; align-items:center; justify-content:center; color:#fff; }
  .s-bar  { background:#fff; border-radius:13px; padding:11px 15px; display:flex; align-items:center; gap:9px; color:#9ca3af; font-size:13px; cursor:pointer; margin-top:14px; transition:box-shadow .2s; }
  .s-bar:hover { box-shadow:0 4px 14px rgba(13,148,136,.25); }
  body.dark .s-bar { background:var(--bg-card); color:var(--txt-muted); }
  .s-bar i { color:var(--teal); font-size:17px; }
  .h-body { padding:18px 18px 0; }
  .svc-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:11px; margin-bottom:26px; }
  .svc-card { background:var(--bg-card); border-radius:17px; padding:15px 8px; display:flex; flex-direction:column; align-items:center; gap:8px; cursor:pointer; transition:transform .2s,box-shadow .2s; box-shadow:0 2px 10px rgba(0,0,0,.05); }
  .svc-card:hover { transform:translateY(-3px); box-shadow:0 8px 22px rgba(13,148,136,.18); }
  .svc-ic { width:52px; height:52px; display:flex; align-items:center; justify-content:center; }
  .svc-ic svg { width:52px; height:52px; }
  .svc-nm { font-size:11px; font-weight:700; color:var(--txt-primary); text-align:center; }
  .promo { position:relative; border-radius:20px; overflow:hidden; margin-bottom:26px; height:150px; cursor:pointer; }
  .promo img { width:100%; height:100%; object-fit:cover; }
  .promo-overlay { position:absolute; inset:0; background:linear-gradient(to right, rgba(13,148,136,.9) 50%, transparent); }
  .promo-content { position:absolute; inset:0; padding:20px 22px; display:flex; flex-direction:column; justify-content:center; }
  .promo-tag { background:rgba(255,255,255,.2); color:#fff; font-size:11px; font-weight:700; padding:3px 10px; border-radius:18px; margin-bottom:8px; display:inline-block; width:fit-content; }
  .promo-ttl { color:#fff; font-size:18px; font-weight:800; font-family:'Poppins',sans-serif; line-height:1.2; margin-bottom:4px; }
  .promo-s  { color:rgba(255,255,255,.85); font-size:12px; font-weight:600; }
  .sec-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:13px; }
  .see-more { font-size:12px; font-weight:700; color:var(--teal); cursor:pointer; }
  .see-more:hover { text-decoration:underline; }
  .pop-row { display:flex; gap:14px; overflow-x:auto; padding-bottom:12px; scrollbar-width:none; }
  .pop-row::-webkit-scrollbar { display:none; }
  .pop-card { flex-shrink:0; width:200px; background:var(--bg-card); border-radius:18px; overflow:hidden; box-shadow:0 2px 12px rgba(0,0,0,.07); cursor:pointer; transition:transform .2s; }
  .pop-card:hover { transform:translateY(-3px); }
  .pop-img { width:100%; height:110px; object-fit:cover; }
  .pop-info { padding:12px; }
  .pop-nm   { font-size:13px; font-weight:700; color:var(--txt-primary); margin-bottom:3px; }
  .pop-d    { font-size:11px; color:var(--txt-muted); }
  .pop-foot { display:flex; align-items:center; justify-content:space-between; margin-top:8px; }
  .pop-price { font-size:14px; font-weight:800; color:var(--teal); }
  .pop-stars { font-size:10px; color:#f59e0b; }
  .h-pb { height:20px; }
  </style>
</head>
<body>
<div class="shell" id="app">
  <div id="ml"><div class="ml-wrap"><div class="ml-box"><svg viewBox="0 0 54 54" fill="none"><path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white"/><circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)"/></svg></div><div class="ml-name">Home<span>Ease</span></div><div class="ml-dots"><div class="ml-dot"></div><div class="ml-dot"></div><div class="ml-dot"></div></div></div></div>

  <div class="screen" id="home">
    <div class="h-scroll">
      <div class="h-hdr">
        <div class="h-top">
          <div><div class="h-greet">Good morning</div><div class="h-name" id="hUserName">Juan dela Cruz</div></div>
          <div class="h-top-right">
            <button class="dm-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="bi bi-moon-fill" id="dmIcon"></i></button>
            <div class="h-chat" onclick="openChat('support')" title="Chat with us">
              <i class="bi bi-chat-dots-fill"></i>
              <div class="h-chat-badge">1</div>
            </div>
            <div class="h-bell" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><div class="h-bell-dot"></div></div>
          </div>
        </div>
        <div class="s-bar" onclick="openSearch()"><i class="bi bi-search"></i><span>Search for a service...</span></div>
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
(function(){ const ic=document.getElementById('dmIcon'); if(ic&&document.body.classList.contains('dark')) ic.className='bi bi-sun-fill'; })();

const svcGrid = document.getElementById('svcGrid');
Object.entries(SVCS).forEach(([name, data]) => {
  svcGrid.innerHTML += `
    <div class="svc-card" onclick="goPage('bookings.php?svc=${encodeURIComponent(name)}&newbooking=1')">
      <div class="svc-ic">${data.ic}</div>
      <div class="svc-nm">${name}</div>
    </div>`;
});

const popData = [
  { svc:'Cleaning',   title:'Deep Home Cleaning',   desc:'Complete house cleaning',    img:'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=300&q=80', price:'₱599', stars:'⭐ 4.9 (238)' },
  { svc:'Plumbing',   title:'Pipe Leak Repair',      desc:'Fix leaks & clogs fast',     img:'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=300&q=80', price:'₱450', stars:'⭐ 4.8 (184)' },
  { svc:'Electrical', title:'Electrical Wiring',     desc:'Safe & certified work',      img:'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=300&q=80', price:'₱750', stars:'⭐ 4.9 (312)' },
  { svc:'Gardening',  title:'Garden Makeover',       desc:'Landscaping & trimming',     img:'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=300&q=80', price:'₱850', stars:'⭐ 4.7 (96)' },
  { svc:'Painting',   title:'Interior Painting',     desc:'Walls & ceilings refreshed', img:'https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=300&q=80',  price:'₱800', stars:'⭐ 4.8 (150)' },
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

if (window.HE?.user?.name) document.getElementById('hUserName').textContent = window.HE.user.name;
</script>
</body>
</html>

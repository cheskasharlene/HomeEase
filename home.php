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
  <style>
    :root {
      --teal: #0D9488;
      --teal2: #0f766e;
      --teal-lt: #ccfbf1;
      --teal-xlt: #f0fdf9;
      --gold: #f59e0b;
      --red: #ef4444;
      --td: #111827;
      --tm: #6b7280;
      --tbg: #f3f4f6;
      --card: #ffffff;
      --border: #e5e7eb;
      --radius: 18px;
      --shadow: 0 2px 16px rgba(13, 148, 136, .10);
    }

    body.dark {
      --td: #f1f5f9;
      --tm: #94a3b8;
      --tbg: #1e293b;
      --card: #0f172a;
      --border: #334155;
      --teal-xlt: #0f2027;
      --teal-lt: #134e4a;
      background: #0a1628;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    .h-scroll {
      height: 100%;
      overflow-y: auto;
      overflow-x: hidden;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      padding-bottom: 90px;
    }

    .h-scroll::-webkit-scrollbar {
      display: none;
    }

    /* HEADER */
    .h-hdr {
      background: linear-gradient(160deg, #0D9488 0%, #0f766e 60%, #065f46 100%);
      padding: 52px 20px 28px;
      position: relative;
      overflow: hidden;
    }

    .h-hdr::before {
      content: '';
      position: absolute;
      top: -40px;
      right: -40px;
      width: 180px;
      height: 180px;
      background: rgba(255, 255, 255, .07);
      border-radius: 50%;
    }

    .h-hdr::after {
      content: '';
      position: absolute;
      bottom: -60px;
      left: -30px;
      width: 220px;
      height: 220px;
      background: rgba(255, 255, 255, .05);
      border-radius: 50%;
    }

    .h-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .h-greet {
      font-size: 13px;
      color: rgba(255, 255, 255, .75);
      font-family: 'Nunito', sans-serif;
      font-weight: 600;
    }

    .h-name {
      font-size: 22px;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-weight: 800;
      letter-spacing: -.3px;
    }

    .h-top-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .dm-btn {
      background: rgba(255, 255, 255, .15);
      border: none;
      cursor: pointer;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 16px;
      backdrop-filter: blur(8px);
    }

    .h-bell {
      background: rgba(255, 255, 255, .15);
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 18px;
      cursor: pointer;
      position: relative;
      backdrop-filter: blur(8px);
    }

    .h-bell-dot {
      position: absolute;
      top: 6px;
      right: 6px;
      width: 8px;
      height: 8px;
      background: var(--gold);
      border-radius: 50%;
    }

    .s-bar {
      background: #fff;
      border-radius: 14px;
      padding: 13px 16px;
      display: flex;
      align-items: center;
      gap: 10px;
      color: #9ca3af;
      font-size: 14px;
      font-family: 'Nunito', sans-serif;
      cursor: pointer;
      box-shadow: 0 4px 20px rgba(0, 0, 0, .12);
      position: relative;
      z-index: 1;
    }

    .s-bar i {
      color: var(--teal);
      font-size: 16px;
    }

    /* QUICK STATS (bookings + saved only) */
    .q-stats {
      display: flex;
      gap: 10px;
      padding: 16px 20px 0;
    }

    .q-stat-chip {
      background: var(--card);
      border-radius: 14px;
      padding: 14px 20px;
      flex: 1;
      box-shadow: var(--shadow);
      text-align: center;
      border: 1px solid var(--border);
    }

    .q-stat-chip .val {
      font-size: 22px;
      font-weight: 800;
      color: var(--teal);
      font-family: 'Poppins', sans-serif;
    }

    .q-stat-chip .lbl {
      font-size: 12px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      font-weight: 600;
      margin-top: 2px;
    }

    /* SECTION HEADERS */
    .sec-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 22px 20px 10px;
    }

    .sec-ttl {
      font-size: 17px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
      letter-spacing: -.2px;
    }

    .see-more {
      font-size: 13px;
      color: var(--teal);
      font-family: 'Nunito', sans-serif;
      font-weight: 700;
      cursor: pointer;
    }

    /* CATEGORY PILLS */
    .cat-pills {
      display: flex;
      gap: 8px;
      padding: 0 20px 4px;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .cat-pills::-webkit-scrollbar {
      display: none;
    }

    .cat-pill {
      background: var(--card);
      border: 2px solid var(--border);
      border-radius: 30px;
      padding: 7px 16px;
      font-size: 13px;
      font-weight: 700;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      cursor: pointer;
      white-space: nowrap;
      transition: all .2s;
      flex-shrink: 0;
    }

    .cat-pill.active {
      background: var(--teal);
      border-color: var(--teal);
      color: #fff;
    }

    /* SERVICES GRID */
    .svc-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      padding: 0 20px;
    }

    .svc-card {
      background: var(--card);
      border-radius: 16px;
      padding: 18px 10px 14px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      transition: transform .15s;
    }

    .svc-card:active {
      transform: scale(.96);
    }

    .svc-ic {
      width: 52px;
      height: 52px;
      background: var(--teal-xlt);
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }

    .svc-nm {
      font-size: 12px;
      font-weight: 700;
      color: var(--td);
      font-family: 'Nunito', sans-serif;
      text-align: center;
    }

    /* PROMO */
    .promo {
      margin: 8px 20px 0;
      border-radius: var(--radius);
      overflow: hidden;
      position: relative;
      cursor: pointer;
      height: 150px;
    }

    .promo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .promo-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(90deg, rgba(13, 148, 136, .9) 0%, rgba(6, 95, 70, .6) 100%);
    }

    .promo-content {
      position: absolute;
      inset: 0;
      padding: 20px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .promo-tag {
      background: rgba(255, 255, 255, .25);
      color: #fff;
      font-size: 10px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      letter-spacing: 1px;
      padding: 3px 10px;
      border-radius: 20px;
      display: inline-block;
      margin-bottom: 8px;
      width: fit-content;
      backdrop-filter: blur(4px);
    }

    .promo-ttl {
      font-size: 22px;
      font-weight: 800;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      line-height: 1.2;
    }

    .promo-s {
      font-size: 12px;
      color: rgba(255, 255, 255, .8);
      font-family: 'Nunito', sans-serif;
      margin-top: 6px;
    }

    /* SMALL PROMOS */
    .promo-sm-row {
      display: flex;
      gap: 12px;
      padding: 12px 20px 0;
    }

    .promo-sm {
      flex: 1;
      border-radius: 14px;
      overflow: hidden;
      position: relative;
      cursor: pointer;
      height: 100px;
    }

    .promo-sm img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .promo-sm-overlay {
      position: absolute;
      inset: 0;
    }

    .promo-sm-content {
      position: absolute;
      inset: 0;
      padding: 12px;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
    }

    .promo-sm-ttl {
      font-size: 13px;
      font-weight: 800;
      color: #fff;
      font-family: 'Poppins', sans-serif;
      line-height: 1.2;
    }

    .promo-sm-s {
      font-size: 10px;
      color: rgba(255, 255, 255, .8);
      font-family: 'Nunito', sans-serif;
    }

    /* POPULAR CARDS */
    .pop-row {
      display: flex;
      gap: 14px;
      padding: 0 20px;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .pop-row::-webkit-scrollbar {
      display: none;
    }

    .pop-card {
      background: var(--card);
      border-radius: 16px;
      overflow: hidden;
      min-width: 190px;
      flex-shrink: 0;
      cursor: pointer;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      transition: transform .15s;
    }

    .pop-card:active {
      transform: scale(.97);
    }

    .pop-img {
      width: 100%;
      height: 120px;
      object-fit: cover;
    }

    .pop-info {
      padding: 12px;
    }

    .pop-nm {
      font-size: 14px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
    }

    .pop-d {
      font-size: 12px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      margin-top: 2px;
    }

    .pop-price {
      font-size: 15px;
      font-weight: 800;
      color: var(--teal);
      font-family: 'Poppins', sans-serif;
      margin-top: 8px;
    }

    /* WORKERS */
    .worker-row {
      display: flex;
      gap: 12px;
      padding: 0 20px;
      overflow-x: auto;
      scrollbar-width: none;
    }

    .worker-row::-webkit-scrollbar {
      display: none;
    }

    .worker-card {
      background: var(--card);
      border-radius: 16px;
      padding: 16px 14px;
      min-width: 140px;
      flex-shrink: 0;
      text-align: center;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      cursor: pointer;
      position: relative;
    }

    .worker-avatar {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      object-fit: cover;
      margin: 0 auto 8px;
      display: block;
      border: 3px solid var(--teal-lt);
    }

    .worker-name {
      font-size: 13px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
    }

    .worker-role {
      font-size: 11px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      margin-top: 3px;
    }

    .worker-jobs {
      font-size: 11px;
      color: var(--teal);
      font-weight: 700;
      font-family: 'Nunito', sans-serif;
      margin-top: 6px;
    }

    .worker-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      background: var(--teal);
      color: #fff;
      font-size: 9px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      padding: 2px 7px;
      border-radius: 20px;
    }

    /* BOOKINGS */
    .booking-list {
      padding: 0 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .booking-card {
      background: var(--card);
      border-radius: 16px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      cursor: pointer;
    }

    .booking-ic {
      width: 46px;
      height: 46px;
      border-radius: 14px;
      background: var(--teal-xlt);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      flex-shrink: 0;
    }

    .booking-info {
      flex: 1;
    }

    .booking-nm {
      font-size: 14px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
    }

    .booking-sub {
      font-size: 12px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      margin-top: 2px;
    }

    .booking-status {
      font-size: 11px;
      font-weight: 700;
      font-family: 'Nunito', sans-serif;
      padding: 4px 10px;
      border-radius: 20px;
    }

    .status-done {
      background: #d1fae5;
      color: #065f46;
    }

    .status-pending {
      background: #fef3c7;
      color: #92400e;
    }

    .status-active {
      background: #dbeafe;
      color: #1e40af;
    }

    /* NEARBY */
    .nearby-list {
      padding: 0 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .nearby-card {
      background: var(--card);
      border-radius: 16px;
      padding: 14px 16px;
      display: flex;
      align-items: center;
      gap: 14px;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      cursor: pointer;
    }

    .nearby-img {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      object-fit: cover;
      flex-shrink: 0;
    }

    .nearby-info {
      flex: 1;
    }

    .nearby-nm {
      font-size: 14px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
    }

    .nearby-meta {
      font-size: 12px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      margin-top: 3px;
      display: flex;
      gap: 10px;
    }

    .nearby-price {
      font-size: 15px;
      font-weight: 800;
      color: var(--teal);
      font-family: 'Poppins', sans-serif;
      flex-shrink: 0;
    }

    /* ── EXPANDED TIPS ── */
    .tip-list {
      padding: 0 20px;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    /* Featured tip – tall hero card */
    .tip-featured {
      background: var(--card);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      cursor: pointer;
    }

    .tip-featured-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
    }

    .tip-featured-body {
      padding: 16px;
    }

    .tip-tag {
      font-size: 10px;
      font-weight: 800;
      color: var(--teal);
      font-family: 'Poppins', sans-serif;
      letter-spacing: .8px;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .tip-featured-ttl {
      font-size: 18px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
      line-height: 1.3;
    }

    .tip-featured-desc {
      font-size: 13px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      line-height: 1.6;
      margin-top: 8px;
    }

    .tip-featured-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 14px;
    }

    .tip-min {
      font-size: 12px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .tip-read-btn {
      background: var(--teal);
      color: #fff;
      font-size: 12px;
      font-weight: 700;
      font-family: 'Poppins', sans-serif;
      padding: 7px 16px;
      border-radius: 20px;
      border: none;
      cursor: pointer;
    }

    /* Regular tip – horizontal layout */
    .tip-item {
      background: var(--card);
      border-radius: 16px;
      overflow: hidden;
      display: flex;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      cursor: pointer;
      transition: transform .15s;
    }

    .tip-item:active {
      transform: scale(.98);
    }

    .tip-item-img {
      width: 110px;
      height: 100px;
      object-fit: cover;
      flex-shrink: 0;
    }

    .tip-item-body {
      padding: 14px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      flex: 1;
    }

    .tip-item-ttl {
      font-size: 14px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
      line-height: 1.3;
    }

    .tip-item-desc {
      font-size: 12px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      margin-top: 4px;
      line-height: 1.4;
    }

    .tip-item-foot {
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
    }

    .tip-item-tag {
      font-size: 10px;
      font-weight: 800;
      color: var(--teal);
      font-family: 'Poppins', sans-serif;
      background: var(--teal-xlt);
      padding: 3px 8px;
      border-radius: 10px;
    }

    .tip-item-min {
      font-size: 11px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
    }

    /* BOTTOM NAV */
    .bnav {
      position: fixed;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100%;
      max-width: 430px;
      background: var(--card);
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-around;
      padding: 8px 0 16px;
      z-index: 100;
      box-shadow: 0 -4px 20px rgba(0, 0, 0, .07);
    }

    .ni {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 3px;
      cursor: pointer;
      position: relative;
      min-width: 50px;
    }

    .ni i {
      font-size: 22px;
      color: var(--tm);
    }

    .ni.on i {
      color: var(--teal);
    }

    .nl {
      font-size: 10px;
      font-weight: 700;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
    }

    .ni.on .nl {
      color: var(--teal);
    }

    .nb-c {
      width: 52px;
      height: 52px;
      background: var(--teal);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 16px rgba(13, 148, 136, .4);
      margin-top: -22px;
    }

    .nb-c i {
      color: #fff !important;
      font-size: 24px;
    }

    .ndot {
      position: absolute;
      top: 0;
      right: 6px;
      width: 7px;
      height: 7px;
      background: var(--red);
      border-radius: 50%;
    }

    .h-pb {
      height: 20px;
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

    <div class="screen" id="home">
      <div class="h-scroll">

        <!-- HEADER -->
        <div class="h-hdr">
          <div class="h-top">
            <div>
              <div class="h-greet"><?= $greeting ?> 👋</div>
              <div class="h-name" id="hUserName"><?= $userName ?></div>
            </div>
            <div class="h-top-right">
              <button class="dm-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="bi bi-moon-fill"
                  id="dmIcon"></i></button>
              <div class="h-bell" onclick="openChat('support')" title="Chat with us" style="position:relative;">
                <i class="bi bi-chat-dots-fill"></i>
                <div
                  style="position:absolute;top:4px;right:4px;width:8px;height:8px;background:#f59e0b;border-radius:50%;">
                </div>
              </div>
              <div class="h-bell" onclick="goPage('notifications.php')" style="position:relative;">
                <i class="bi bi-bell-fill"></i>
                <div class="h-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
            </div>
          </div>
          <div class="s-bar" onclick="openSearch()"><i class="bi bi-search"></i><span>Search for a service...</span>
          </div>
        </div>

        <!-- QUICK STATS: bookings + saved only -->
        <div class="q-stats">
          <div class="q-stat-chip">
            <div class="val" id="statBook">3</div>
            <div class="lbl">My Bookings</div>
          </div>
          <div class="q-stat-chip">
            <div class="val">2</div>
            <div class="lbl">Saved Services</div>
          </div>
        </div>

        <!-- BROWSE CATEGORIES -->
        <div class="sec-row">
          <div class="sec-ttl">Browse by Category</div>
        </div>
        <div class="cat-pills">
          <div class="cat-pill active">All</div>
          <div class="cat-pill">Cleaning</div>
          <div class="cat-pill">Plumbing</div>
          <div class="cat-pill">Electrical</div>
          <div class="cat-pill">Painting</div>
          <div class="cat-pill">Gardening</div>
          <div class="cat-pill">Appliances</div>
        </div>

        <!-- SERVICES GRID -->
        <div class="sec-row" style="padding-top:12px;">
          <div class="sec-ttl">Our Services</div><span class="see-more" onclick="openAllServices()">See all →</span>
        </div>
        <div class="svc-grid" id="svcGrid"></div>

        <!-- MAIN PROMO -->
        <div style="margin-top:20px;">
          <div class="promo" onclick="openAllOffers()">
            <img src="https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=600&q=80" alt="Promo">
            <div class="promo-overlay"></div>
            <div class="promo-content">
              <div class="promo-tag">LIMITED OFFER</div>
              <div class="promo-ttl">20% Off Your<br>First Booking!</div>
              <div class="promo-s">Use code EASE20 · Tap to see more offers</div>
            </div>
          </div>
        </div>

        <!-- MINI PROMOS -->
        <div class="promo-sm-row">
          <div class="promo-sm">
            <img src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=300&q=80" alt="">
            <div class="promo-sm-overlay"
              style="background:linear-gradient(180deg,transparent 0%,rgba(13,148,136,.85) 100%);"></div>
            <div class="promo-sm-content">
              <div class="promo-sm-ttl">Free Deep Clean</div>
              <div class="promo-sm-s">New users only</div>
            </div>
          </div>
          <div class="promo-sm">
            <img src="https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=300&q=80" alt="">
            <div class="promo-sm-overlay"
              style="background:linear-gradient(180deg,transparent 0%,rgba(6,95,70,.85) 100%);"></div>
            <div class="promo-sm-content">
              <div class="promo-sm-ttl">₱200 Off Electric</div>
              <div class="promo-sm-s">This weekend only</div>
            </div>
          </div>
        </div>

        <!-- MOST POPULAR -->
        <div class="sec-row">
          <div class="sec-ttl">Most Popular Services</div><span class="see-more" onclick="openAllServices()">See more
            →</span>
        </div>
        <div class="pop-row" id="popRow"></div>

        <!-- OUR PROS -->
        <div class="sec-row">
          <div class="sec-ttl">Our Pros</div><span class="see-more" onclick="goPage('workers.php')">See all →</span>
        </div>
        <div class="worker-row" id="workerRow"></div>

        <!-- RECENT BOOKINGS -->
        <div class="sec-row">
          <div class="sec-ttl">Recent Bookings</div><span class="see-more" onclick="goPage('bookings.php')">See all
            →</span>
        </div>
        <div class="booking-list">
          <div class="booking-card" onclick="goPage('bookings.php')">
            <div class="booking-ic">🧹</div>
            <div class="booking-info">
              <div class="booking-nm">Deep Home Cleaning</div>
              <div class="booking-sub">Mar 1, 2025 · Maria S.</div>
            </div>
            <span class="booking-status status-done">Done</span>
          </div>
          <div class="booking-card" onclick="goPage('bookings.php')">
            <div class="booking-ic">🔧</div>
            <div class="booking-info">
              <div class="booking-nm">Pipe Leak Repair</div>
              <div class="booking-sub">Mar 5, 2025 · Juan R.</div>
            </div>
            <span class="booking-status status-active">Active</span>
          </div>
          <div class="booking-card" onclick="goPage('bookings.php')">
            <div class="booking-ic">⚡</div>
            <div class="booking-info">
              <div class="booking-nm">Electrical Wiring</div>
              <div class="booking-sub">Mar 10, 2025 · Ben L.</div>
            </div>
            <span class="booking-status status-pending">Pending</span>
          </div>
        </div>

        <!-- NEARBY -->
        <div class="sec-row">
          <div class="sec-ttl">Near You in Mauban</div><span class="see-more">View map →</span>
        </div>
        <div class="nearby-list">
          <div class="nearby-card">
            <img class="nearby-img" src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=200&q=80"
              alt="">
            <div class="nearby-info">
              <div class="nearby-nm">Express Cleaning Co.</div>
              <div class="nearby-meta"><span>📍 0.8 km</span><span>🕐 Available now</span></div>
            </div>
            <div class="nearby-price">₱499</div>
          </div>
          <div class="nearby-card">
            <img class="nearby-img" src="https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=200&q=80"
              alt="">
            <div class="nearby-info">
              <div class="nearby-nm">QuickFix Plumbing</div>
              <div class="nearby-meta"><span>📍 1.2 km</span><span>🕐 ~30 min</span></div>
            </div>
            <div class="nearby-price">₱450</div>
          </div>
          <div class="nearby-card">
            <img class="nearby-img" src="https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=200&q=80"
              alt="">
            <div class="nearby-info">
              <div class="nearby-nm">Green Thumb Gardens</div>
              <div class="nearby-meta"><span>📍 2.1 km</span><span>🕐 ~1 hr</span></div>
            </div>
            <div class="nearby-price">₱699</div>
          </div>
        </div>

        <!-- HOME CARE TIPS – expanded -->
        <div class="sec-row">
          <div class="sec-ttl">Home Care Tips</div><span class="see-more">Read more →</span>
        </div>
        <div class="tip-list" id="tipList"></div>

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

    // Services grid
    const svcGrid = document.getElementById('svcGrid');
    Object.entries(SVCS).forEach(([name, data]) => {
      svcGrid.innerHTML += `
        <div class="svc-card" onclick="goPage('bookings.php?svc=${encodeURIComponent(name)}&newbooking=1')">
          <div class="svc-ic">${data.ic}</div>
          <div class="svc-nm">${name}</div>
        </div>`;
    });

    // Popular services (no stars)
    const popData = [
      { svc: 'Cleaning', title: 'Deep Home Cleaning', desc: 'Complete house cleaning', img: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=300&q=80', price: '₱599' },
      { svc: 'Plumbing', title: 'Pipe Leak Repair', desc: 'Fix leaks & clogs fast', img: 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=300&q=80', price: '₱450' },
      { svc: 'Electrical', title: 'Electrical Wiring', desc: 'Safe & certified work', img: 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=300&q=80', price: '₱750' },
      { svc: 'Gardening', title: 'Garden Makeover', desc: 'Landscaping & trimming', img: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=300&q=80', price: '₱850' },
      { svc: 'Painting', title: 'Interior Painting', desc: 'Walls & ceilings refreshed', img: 'https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=300&q=80', price: '₱800' },
    ];
    const popRow = document.getElementById('popRow');
    popData.forEach(p => {
      popRow.innerHTML += `
        <div class="pop-card" onclick="goPage('bookings.php?svc=${encodeURIComponent(p.svc)}&newbooking=1')">
          <img class="pop-img" src="${p.img}" alt="${p.title}">
          <div class="pop-info">
            <div class="pop-nm">${p.title}</div>
            <div class="pop-d">${p.desc}</div>
            <div class="pop-price">${p.price}</div>
          </div>
        </div>`;
    });

    // Our Pros — loaded live from database
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
          <div class="worker-card" onclick="goPage('workers.php')">
            ${w.top ? '<div class="worker-badge">TOP</div>' : ''}
            <img class="worker-avatar" src="${w.img}" alt="${w.name}"
              onerror="this.src='https://ui-avatars.com/api/?name='+encodeURIComponent('${w.name}')+'&background=ccfbf1&color=0d9488&size=128'">
            <div class="worker-name">${w.name}</div>
            <div class="worker-role">${w.specialty}</div>
            <div class="worker-jobs">${w.jobs_done} jobs done</div>
          </div>`).join('');
      } catch (e) {
        workerRow.innerHTML = '<div style="padding:20px;color:var(--tm);font-size:13px;font-family:Nunito,sans-serif;">Could not load pros.</div>';
      }
    }
    loadPros();

    // Expanded Tips — first card is featured hero, rest are horizontal items
    const tips = [
      {
        tag: 'CLEANING',
        ttl: '5 Tips for a Spotless Kitchen Every Week',
        desc: 'Keeping your kitchen clean doesn\'t have to be a weekend project. These simple daily habits will have your kitchen sparkling with minimal effort.',
        img: 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=600&q=80',
        min: '3 min read',
        featured: true
      },
      {
        tag: 'PLUMBING',
        ttl: 'How to Prevent Pipe Leaks Before They Start',
        desc: 'Regular checks and small fixes can save you thousands in water damage.',
        img: 'https://images.unsplash.com/photo-1585704032915-c3400ca199e7?w=400&q=80',
        min: '4 min read',
        featured: false
      },
      {
        tag: 'GARDENING',
        ttl: 'Best Low-Maintenance Plants for Philippine Homes',
        desc: 'Tropical beauties that thrive in heat and humidity with almost no care.',
        img: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=400&q=80',
        min: '5 min read',
        featured: false
      },
      {
        tag: 'ELECTRICAL',
        ttl: 'Warning Signs Your Home Needs Rewiring ASAP',
        desc: 'Flickering lights, tripped breakers, burning smells — don\'t ignore these.',
        img: 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=400&q=80',
        min: '3 min read',
        featured: false
      },
      {
        tag: 'PAINTING',
        ttl: 'Choosing the Right Paint Finish for Every Room',
        desc: 'Matte, eggshell, satin or gloss — each has its place in your home.',
        img: 'https://images.unsplash.com/photo-1562259949-e8e7689d7828?w=400&q=80',
        min: '4 min read',
        featured: false
      },
      {
        tag: 'APPLIANCES',
        ttl: 'How to Extend the Life of Your Home Appliances',
        desc: 'Simple maintenance routines that keep your AC, fridge and washer running longer.',
        img: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=400&q=80',
        min: '5 min read',
        featured: false
      },
    ];

    const tipList = document.getElementById('tipList');
    tips.forEach(t => {
      if (t.featured) {
        tipList.innerHTML += `
          <div class="tip-featured">
            <img class="tip-featured-img" src="${t.img}" alt="${t.ttl}">
            <div class="tip-featured-body">
              <div class="tip-tag">${t.tag}</div>
              <div class="tip-featured-ttl">${t.ttl}</div>
              <div class="tip-featured-desc">${t.desc}</div>
              <div class="tip-featured-foot">
                <div class="tip-min"><i class="bi bi-clock"></i> ${t.min}</div>
                <button class="tip-read-btn">Read Now</button>
              </div>
            </div>
          </div>`;
      } else {
        tipList.innerHTML += `
          <div class="tip-item">
            <img class="tip-item-img" src="${t.img}" alt="${t.ttl}">
            <div class="tip-item-body">
              <div class="tip-item-ttl">${t.ttl}</div>
              <div class="tip-item-desc">${t.desc}</div>
              <div class="tip-item-foot">
                <span class="tip-item-tag">${t.tag}</span>
                <span class="tip-item-min">🕐 ${t.min}</span>
              </div>
            </div>
          </div>`;
      }
    });

    // Category pill toggle
    document.querySelectorAll('.cat-pill').forEach(pill => {
      pill.addEventListener('click', function () {
        document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
      });
    });

    // Nav
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
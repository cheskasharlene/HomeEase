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
      --teal: #F5A623;
      --teal2: #E8960F;
      --teal-lt: #FDECC8;
      --teal-xlt: #FFF3E0;
      --page-gutter: clamp(14px, 4vw, 20px);
      --gold: #f59e0b;
      --red: #ef4444;
      --td: #1A1A2E;
      --tm: #8E8E93;
      --tbg: #F8F8F8;
      --card: #ffffff;
      --border: #e5e7eb;
      --radius: 18px;
      --shadow: 0 2px 16px rgba(245, 166, 35, .09);
    }

    body.dark {
      --td: #FFF3DC;
      --tm: #B8A882;
      --tbg: #2A2216;
      --card: #2A2216;
      --border: #4A3E28;
      --teal-xlt: #2A2216;
      --teal-lt: #4A3E28;
      background: #18140C;
    }

    body.dark .shell {
      background: #201A10;
    }

    body.dark #home {
      background: #18140C;
    }

    body.dark .svc-card {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
    }

    body.dark .svc-nm {
      color: #FFF3DC !important;
    }

    body.dark .svc-ic {
      background: #332A1C !important;
      border-color: #4A3E28 !important;
    }

    body.dark .pop-card {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
    }

    body.dark .pop-nm {
      color: #FFF3DC !important;
    }

    body.dark .pop-d {
      color: #B8A882 !important;
    }

    body.dark .cat-pill {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
      color: #B8A882 !important;
    }

    body.dark .sec-ttl,
    body.dark .sec-row .sec-ttl {
      color: #FFF3DC !important;
    }

    body.dark .s-bar {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
      color: #B8A882 !important;
    }

    body.dark .h-greet {
      color: rgba(255, 255, 255, .75) !important;
    }

    body.dark .h-name {
      color: #fff !important;
    }

    body.dark .dm-btn {
      background: rgba(255, 255, 255, .15) !important;
      color: #fff !important;
    }

    body.dark .h-bell {
      background: rgba(255, 255, 255, .15) !important;
      color: #fff !important;
    }

    body.dark .worker-card {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
    }

    body.dark .worker-name {
      color: #FFF3DC !important;
    }

    body.dark .worker-role {
      color: #B8A882 !important;
    }

    body.dark .nearby-card {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
    }

    body.dark .tip-card {
      background: #2A2216 !important;
      border-color: #4A3E28 !important;
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
      background: linear-gradient(135deg, #E8820C 0%, #F5A623 35%, #FFB347 65%, #FDECC8 100%) !important;
      border-radius: 0 0 28px 28px !important;
      box-shadow: none !important;
      padding: 52px 20px 28px !important;
      position: relative !important;
      overflow: hidden !important;
      border-bottom: none !important;
    }

    .h-hdr::before {
      display: none;
    }

    .h-hdr::after {
      display: none;
    }

    .h-top {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .h-greet {
      font-size: 13px;
      color: #6B7280 !important;
      font-family: 'Nunito', sans-serif;
      font-weight: 600;
    }

    .h-name {
      font-size: 22px;
      color: #1A1A2E !important;
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
      background: rgba(255, 255, 255, .55) !important;
      border: none;
      cursor: pointer;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1A1A2E !important;
      font-size: 16px;
    }

    .h-bell {
      background: rgba(255, 255, 255, .55) !important;
      width: 38px;
      height: 38px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1A1A2E !important;
      font-size: 18px;
      cursor: pointer;
      position: relative;
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
      background: #F5F5F4;
      border-radius: 50px;
      border: 1.5px solid #E7E5E4;
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
      color: #F5A623;
      font-size: 16px;
    }

    /* SECTION HEADERS */
    .sec-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px var(--page-gutter) 10px;
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
      padding: 0 var(--page-gutter) 4px;
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
      background: linear-gradient(135deg, #FFB84D 0%, #F5A623 100%);
      border-color: #F5A623;
      color: #fff;
    }

    /* SERVICES GRID */
    .svc-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      padding: 0 var(--page-gutter);
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
      background: #FFF8F0;
      border-radius: 14px;
      border: 1px solid #FFE5B4;
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

    .pop-row {
      display: flex;
      gap: 14px;
      padding: 0 var(--page-gutter);
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

    .worker-row {
      display: flex;
      gap: 12px;
      padding: 0 var(--page-gutter);
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
      color: #F5A623;
      font-weight: 700;
      font-family: 'Nunito', sans-serif;
      margin-top: 6px;
    }

    .worker-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      background: #F5A623;
      color: #fff;
      font-size: 9px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      padding: 2px 7px;
      border-radius: 20px;
    }

    .nearby-list {
      padding: 0 var(--page-gutter);
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


    .tip-list {
      padding: 0 var(--page-gutter);
      display: flex;
      flex-direction: column;
      gap: 16px;
    }


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
      color: #F5A623;
      font-family: 'Poppins', sans-serif;
      background: #FFF8F0;
      border: 1px solid #FFE5B4;
      padding: 3px 8px;
      border-radius: 10px;
    }

    .tip-item-min {
      font-size: 11px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
    }


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
      color: #F5A623;
    }

    .nl {
      font-size: 10px;
      font-weight: 700;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
    }

    .ni.on .nl {
      color: #F5A623;
    }

    .nb-c {
      width: 52px;
      height: 52px;
      background: var(--teal);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 16px rgba(245, 166, 35, .35);
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

    body.dark .h-hdr {
      background: linear-gradient(135deg, #7A3800 0%, #A85A00 30%, #C8780A 60%, #8B6200 100%) !important;
    }

    body.dark .h-greet {
      color: rgba(255, 255, 255, .70) !important;
    }

    body.dark .h-name {
      color: #fff !important;
    }

    body.dark .dm-btn {
      background: rgba(255, 255, 255, .12) !important;
      color: #fff !important;
    }

    body.dark .h-bell {
      background: rgba(255, 255, 255, .12) !important;
      color: #fff !important;
    }

    .logout-modal {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .42);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 300;
      padding: 20px;
    }

    .logout-modal.on {
      display: flex;
    }

    .logout-card {
      width: 100%;
      max-width: 360px;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 18px 50px rgba(0, 0, 0, .2);
      border: 1px solid #F6E6CC;
      padding: 22px 20px 18px;
      text-align: center;
    }

    .logout-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      margin: 0 auto 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      color: #fff;
      background: linear-gradient(135deg, #E8820C 0%, #F5A623 100%);
      box-shadow: 0 8px 20px rgba(232, 130, 12, .28);
    }

    .logout-title {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      font-weight: 800;
      color: #1A1A2E;
      line-height: 1.35;
    }

    .logout-desc {
      margin-top: 8px;
      font-family: 'Nunito', sans-serif;
      font-size: 13px;
      line-height: 1.55;
      color: #6B7280;
    }

    .logout-actions {
      margin-top: 18px;
      display: flex;
      gap: 10px;
      justify-content: center;
    }

    .logout-btn {
      border: none;
      border-radius: 999px;
      min-width: 118px;
      padding: 10px 16px;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: transform .15s, box-shadow .2s, background .2s, color .2s;
    }

    .logout-btn:active {
      transform: scale(.97);
    }

    .logout-btn-cancel {
      background: #FFF7EA;
      border: 1.5px solid #F4DEC0;
      color: #8A5A12;
    }

    .logout-btn-cancel:hover {
      background: #FFF1DA;
    }

    .logout-btn-confirm {
      color: #fff;
      background: linear-gradient(135deg, #E8820C 0%, #F5A623 100%);
      box-shadow: 0 8px 18px rgba(232, 130, 12, .28);
    }

    .logout-btn-confirm:hover {
      box-shadow: 0 10px 22px rgba(232, 130, 12, .34);
    }

    body.dark .logout-card {
      background: #2A2216;
      border-color: #4A3E28;
    }

    body.dark .logout-title {
      color: #FFF3DC;
    }

    body.dark .logout-desc {
      color: #C8B38B;
    }

    body.dark .logout-btn-cancel {
      background: #332A1C;
      border-color: #4A3E28;
      color: #EBCB93;
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
              <div class="h-bell" onclick="goPage('notifications.php')" style="position:relative;">
                <i class="bi bi-bell-fill"></i>
                <div class="h-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
              <div class="h-bell" onclick="openLogoutModal()" title="Log out">
                <i class="bi bi-box-arrow-right"></i>
              </div>
            </div>
          </div>
          <div class="s-bar" onclick="openSearch()"><i class="bi bi-search"></i><span>Search for a service...</span>
          </div>
        </div>

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

        <div class="h-pb"></div>
      </div>
      <div id="navContainer"></div>
    </div>
  </div>

  <div class="logout-modal" id="logoutModal" aria-hidden="true">
    <div class="logout-card" role="dialog" aria-modal="true" aria-labelledby="logoutTitle" aria-describedby="logoutDesc">
      <div class="logout-icon"><i class="bi bi-box-arrow-right"></i></div>
      <div class="logout-title" id="logoutTitle">Are you sure you want to log out?</div>
      <div class="logout-desc" id="logoutDesc">You will need to log in again to access your account.</div>
      <div class="logout-actions">
        <button type="button" class="logout-btn logout-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
        <button type="button" class="logout-btn logout-btn-confirm" onclick="confirmLogout()">Log out</button>
      </div>
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

    function openLogoutModal() {
      const modal = document.getElementById('logoutModal');
      modal.classList.add('on');
      modal.setAttribute('aria-hidden', 'false');
    }

    function closeLogoutModal() {
      const modal = document.getElementById('logoutModal');
      modal.classList.remove('on');
      modal.setAttribute('aria-hidden', 'true');
    }

    function confirmLogout() {
      window.location.href = 'logout.php';
    }

    document.getElementById('logoutModal').addEventListener('click', function (e) {
      if (e.target === this) closeLogoutModal();
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeLogoutModal();
    });

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
        <div class="svc-card" onclick="goPage('booking_form.php?svc=${encodeURIComponent(name)}&newbooking=1')">
          <div class="svc-ic">${data.ic}</div>
          <div class="svc-nm">${name}</div>
        </div>`;
    });

  
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
        <div class="pop-card" onclick="goPage('booking_form.php?svc=${encodeURIComponent(p.svc)}&newbooking=1')">
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
          <div class="worker-card" onclick="goPage('workers.php')">
            ${w.top ? '<div class="worker-badge">TOP</div>' : ''}
            <img class="worker-avatar" src="${w.img}" alt="${w.name}"
              onerror="this.src='https://ui-avatars.com/api/?name='+encodeURIComponent('${w.name}')+'&background=FDECC8&color=F5A623&size=128'">
            <div class="worker-name">${w.name}</div>
            <div class="worker-role">${w.specialty}</div>
            <div class="worker-jobs">${w.jobs_done} jobs done</div>
          </div>`).join('');
      } catch (e) {
        workerRow.innerHTML = '<div style="padding:20px;color:var(--tm);font-size:13px;font-family:Nunito,sans-serif;">Could not load pros.</div>';
      }
    }
    loadPros();


    document.querySelectorAll('.cat-pill').forEach(pill => {
      pill.addEventListener('click', function () {
        document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        this.classList.add('active');
      });
    });


    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni on"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('booking_history.php')"><i class="bi bi-calendar-check"></i><span class="nl">Bookings</span></div>
        <div class="ni" onclick="goPage('booking_form.php?newbooking=1')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Notifications</span><div class="ndot"></div></div>
        <div class="ni" onclick="goPage('profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;
  </script>
</body>

</html>
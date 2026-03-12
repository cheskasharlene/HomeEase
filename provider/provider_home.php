<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}

$hour = (int) date('H');
if ($hour < 12)
  $greeting = 'Good morning';
elseif ($hour < 18)
  $greeting = 'Good afternoon';
else
  $greeting = 'Good evening';

$userName = htmlspecialchars($_SESSION['provider_name'] ?? 'Provider');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Provider Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link href="../assets/css/home.css" rel="stylesheet">
  <style>
    :root {
      --teal: #0D9488;
      --teal2: #0f766e;
      --teal-lt: #ccfbf1;
      --teal-xlt: #f0fdf9;
      --gold: #2bb9a8;
      --red: #147d8c;
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
      padding: 50px 16px 20px;
      position: relative;
      overflow: hidden;
    }

    @media (max-width: 480px) {
      .h-hdr {
        padding: 40px 16px 18px;
      }
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

    @media (max-width: 480px) {
      .h-greet {
        font-size: 12px;
      }

      .h-name {
        font-size: 20px;
      }
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
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 16px;
      backdrop-filter: blur(8px);
      touch-action: manipulation;
    }

    .h-bell {
      background: rgba(255, 255, 255, .15);
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 18px;
      cursor: pointer;
      position: relative;
      backdrop-filter: blur(8px);
      touch-action: manipulation;
    }

    @media (max-width: 480px) {
      .h-top-right {
        gap: 6px;
      }

      .dm-btn,
      .h-bell {
        width: 38px;
        height: 38px;
        font-size: 15px;
      }
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

    @media (max-width: 480px) {
      .s-bar {
        padding: 11px 14px;
        font-size: 13px;
      }

      .s-bar i {
        font-size: 15px;
      }
    }

    /* QUICK STATS (bookings + saved only) */
    .q-stats {
      display: flex;
      gap: 10px;
      padding: 16px 16px 0;
      flex-wrap: wrap;
    }

    .q-stat-chip {
      background: var(--card);
      border-radius: 14px;
      padding: 14px 16px;
      flex: 1;
      min-width: 90px;
      box-shadow: var(--shadow);
      text-align: center;
      border: 1px solid var(--border);
    }

    @media (max-width: 480px) {
      .q-stats {
        padding: 14px 16px 0;
        gap: 8px;
      }

      .q-stat-chip {
        min-width: calc(50% - 4px);
        padding: 12px 12px;
      }
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

    @media (max-width: 480px) {
      .q-stat-chip .val {
        font-size: 18px;
      }

      .q-stat-chip .lbl {
        font-size: 11px;
      }
    }

    .availability-toggle {
      display: flex;
      align-items: center;
      touch-action: manipulation;
    }

    .availability-toggle .switch {
      position: relative;
      display: inline-block;
      width: 44px;
      height: 24px;
    }

    .availability-toggle .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }

    .availability-toggle .slider {
      position: absolute;
      cursor: pointer;
      inset: 0;
      background: rgba(255, 255, 255, .32);
      transition: .3s ease;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, .18);
    }

    .availability-toggle .slider::before {
      position: absolute;
      content: '';
      width: 18px;
      height: 18px;
      left: 2px;
      top: 2px;
      background: #fff;
      transition: .3s ease;
      border-radius: 50%;
      box-shadow: 0 4px 8px rgba(15, 23, 42, .18);
    }

    .availability-toggle input:checked + .slider {
      background: rgba(20, 125, 140, .95);
    }

    .availability-toggle input:checked + .slider::before {
      transform: translateX(20px);
    }

    .sec-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 22px 16px 12px;
    }

    .sec-ttl {
      font-size: 18px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
      letter-spacing: -.2px;
    }

    .req-list,
    .svc-list,
    .sched-list,
    .earn-list,
    .rev-list {
      padding: 0 16px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .req-card,
    .svc-item,
    .sched-item,
    .earn-item,
    .rev-item {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      box-shadow: var(--shadow);
    }

    .req-card {
      padding: 16px;
    }

    .req-info {
      display: grid;
      gap: 5px;
      margin-bottom: 14px;
      color: var(--td);
      font-family: 'Nunito', sans-serif;
    }

    .req-type,
    .req-name {
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
    }

    .req-type {
      color: var(--teal);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: .5px;
    }

    .req-name {
      font-size: 17px;
      color: var(--td);
    }

    .req-loc,
    .req-time,
    .req-price {
      font-size: 14px;
      color: var(--tm);
    }

    .req-price {
      color: var(--teal);
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      margin-top: 2px;
    }

    .req-actions {
      display: flex;
      gap: 10px;
    }

    .btn-accept,
    .btn-decline,
    .svc-actions button {
      border: none;
      border-radius: 12px;
      font-family: 'Nunito', sans-serif;
      font-weight: 800;
      cursor: pointer;
      touch-action: manipulation;
    }

    .btn-accept,
    .btn-decline {
      flex: 1;
      min-height: 44px;
      font-size: 14px;
    }

    .btn-accept {
      background: var(--teal);
      color: #fff;
    }

    .btn-decline {
      background: rgba(20, 125, 140, .12);
      color: var(--teal2);
    }

    .svc-item {
      padding: 14px 16px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 12px;
      align-items: center;
    }

    .svc-item > span:first-child {
      font-size: 15px;
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
      color: var(--td);
    }

    .svc-item > span:nth-child(2) {
      color: var(--teal);
      font-weight: 800;
      font-family: 'Poppins', sans-serif;
    }

    .svc-actions {
      grid-column: 1 / -1;
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .svc-actions button {
      min-height: 38px;
      padding: 0 14px;
      background: var(--teal-xlt);
      color: var(--teal2);
    }

    .sched-item,
    .earn-item,
    .rev-item {
      padding: 14px 16px;
      font-size: 14px;
      color: var(--td);
      font-family: 'Nunito', sans-serif;
      line-height: 1.5;
    }

    .earn-overview {
      background: linear-gradient(135deg, rgba(20, 125, 140, .12), rgba(20, 125, 140, .04));
      border: 1px solid rgba(20, 125, 140, .12);
      border-radius: 18px;
      padding: 16px;
      font-size: 15px;
      font-weight: 800;
      color: var(--teal2);
      font-family: 'Poppins', sans-serif;
    }

    .rev-name {
      font-size: 15px;
      font-weight: 800;
      color: var(--td);
      font-family: 'Poppins', sans-serif;
    }

    .rev-stars {
      margin: 6px 0;
      letter-spacing: 2px;
    }

    .rev-text {
      font-size: 14px;
      color: var(--tm);
      font-family: 'Nunito', sans-serif;
      line-height: 1.5;
    }

    @media (max-width: 480px) {
      .sec-row {
        padding: 20px 16px 10px;
      }

      .sec-ttl {
        font-size: 17px;
      }

      .req-list,
      .svc-list,
      .sched-list,
      .earn-list,
      .rev-list {
        gap: 10px;
      }

      .req-card,
      .svc-item,
      .sched-item,
      .earn-item,
      .rev-item,
      .earn-overview {
        border-radius: 16px;
      }
    }

    .see-more {
      color: var(--teal);
      font-family: 'Nunito', sans-serif;
      font-weight: 700;
      cursor: pointer;
    }

    /* CATEGORY PILLS */
    .cat-pills {
      display: flex;
      gap: 8px;
      padding: 0 16px 4px;
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
      padding: 8px 16px;
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

    @media (max-width: 480px) {
      .cat-pills {
        padding: 0 16px 4px;
      }

      .cat-pill {
        padding: 7px 14px;
        font-size: 12px;
      }
    }

    /* SERVICES GRID */
    .svc-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      padding: 0 16px;
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

    @media (max-width: 480px) {
      .svc-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        padding: 0 16px;
      }

      .svc-card {
        padding: 16px 10px 12px;
      }
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

    @media (max-width: 480px) {
      .svc-ic {
        width: 48px;
        height: 48px;
        font-size: 22px;
      }

      .svc-nm {
        font-size: 11px;
      }
    }

    /* PROMO */
    .promo {
      margin: 8px 16px 0;
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

    @media (max-width: 480px) {
      .promo {
        margin: 8px 16px 0;
        height: 140px;
      }
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
      padding: 12px 16px 0;
    }

    .promo-sm {
      flex: 1;
      border-radius: 14px;
      overflow: hidden;
      position: relative;
      cursor: pointer;
      height: 100px;
    }

    @media (max-width: 480px) {
      .promo-sm-row {
        padding: 12px 16px 0;
        gap: 10px;
      }

      .promo-sm {
        height: 90px;
      }
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
      padding: 0 16px;
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
      min-width: 180px;
      flex-shrink: 0;
      cursor: pointer;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      transition: transform .15s;
    }

    .pop-card:active {
      transform: scale(.97);
    }

    @media (max-width: 480px) {
      .pop-row {
        padding: 0 16px;
        gap: 12px;
      }

      .pop-card {
        min-width: 160px;
      }
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
      padding: 0 16px;
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
      min-width: 130px;
      flex-shrink: 0;
      text-align: center;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      cursor: pointer;
      position: relative;
    }

    @media (max-width: 480px) {
      .worker-row {
        padding: 0 16px;
        gap: 10px;
      }

      .worker-card {
        min-width: 120px;
        padding: 14px 12px;
      }
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
      padding: 0 16px;
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
      padding: 6px 10px;
      border-radius: 20px;
      white-space: nowrap;
    }

    @media (max-width: 480px) {
      .booking-list {
        padding: 0 16px;
        gap: 8px;
      }

      .booking-card {
        padding: 12px 14px;
        gap: 12px;
      }

      .booking-nm {
        font-size: 13px;
      }

      .booking-sub {
        font-size: 11px;
      }

      .booking-status {
        font-size: 10px;
        padding: 5px 8px;
      }
    }

    .status-done {
      background: rgba(20, 125, 140, .12);
      color: var(--teal2);
    }

    .status-pending {
      background: rgba(43, 185, 168, .16);
      color: var(--teal2);
    }

    .status-active {
      background: rgba(20, 125, 140, .18);
      color: var(--teal);
    }

    /* NEARBY */
    .nearby-list {
      padding: 0 16px;
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

    @media (max-width: 480px) {
      .nearby-list {
        padding: 0 16px;
        gap: 8px;
      }

      .nearby-card {
        padding: 12px 14px;
        gap: 12px;
      }

      .nearby-img {
        width: 54px;
        height: 54px;
      }

      .nearby-nm {
        font-size: 13px;
      }

      .nearby-meta {
        font-size: 11px;
      }

      .nearby-price {
        font-size: 14px;
      }
    }

    /* ── EXPANDED TIPS ── */
    .tip-list {
      padding: 0 16px;
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

    @media (max-width: 480px) {
      .tip-list {
        padding: 0 16px;
        gap: 14px;
      }

      .tip-featured-img {
        height: 160px;
      }

      .tip-featured-body {
        padding: 14px;
      }
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

    @media (max-width: 480px) {
      .tip-item-img {
        width: 100px;
        height: 90px;
      }

      .tip-item-body {
        padding: 12px;
      }
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
      gap: 5px;
      cursor: pointer;
      position: relative;
      min-width: 50px;
      padding: 8px 0;
      -webkit-touch-callout: none;
      -webkit-user-select: none;
      user-select: none;
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
      width: 56px;
      height: 56px;
      background: var(--teal);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 16px rgba(13, 148, 136, .4);
      margin-top: -28px;
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

    @media (max-width: 480px) {
      .ni {
        min-width: 48px;
        padding: 6px 0;
      }

      .nl {
        font-size: 9px;
      }
    }

    .h-pb {
      height: 110px;
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
            <div style="display:flex;align-items:center;gap:12px;">
              <img src="../assets/images/default-profile.png" alt="Profile" style="width:48px;height:48px;border-radius:50%;object-fit:cover;" />
              <div>
                <div class="h-greet"><?= $greeting ?> 👋</div>
                <div class="h-name" id="hUserName"><?= $userName ?></div>
              </div>
            </div>
            <div class="h-top-right">
              <button class="dm-btn" onclick="toggleDark()" title="Toggle dark mode"><i class="bi bi-moon-fill"
                  id="dmIcon"></i></button>
              <div class="h-bell" onclick="openChat('support')" title="Chat with us" style="position:relative;">
                <i class="bi bi-chat-dots-fill"></i>
                <div
                  style="position:absolute;top:4px;right:4px;width:8px;height:8px;background:#2bb9a8;border-radius:50%;">
                </div>
              </div>
              <div class="h-bell" onclick="goPage('provider_notifications.php')" style="position:relative;">
                <i class="bi bi-bell-fill"></i>
                <div class="h-bell-dot" id="bellDot" style="display:none;"></div>
              </div>
              <div class="availability-toggle" title="Online / Offline">
                <label class="switch">
                  <input type="checkbox" id="availToggle">
                  <span class="slider"></span>
                </label>
              </div>
            </div>
          </div>
          <div class="s-bar" onclick="openSearch()"><i class="bi bi-search"></i><span>Search for a service...</span>
          </div>
        </div>

        <!-- QUICK STATS: provider version -->
        <div class="q-stats">
          <div class="q-stat-chip">
            <div class="val" id="statPending">5</div>
            <div class="lbl">Pending Requests</div>
          </div>
          <div class="q-stat-chip">
            <div class="val" id="statActive">2</div>
            <div class="lbl">Active Jobs</div>
          </div>
          <div class="q-stat-chip">
            <div class="val" id="statRating">4.8</div>
            <div class="lbl">Avg. Rating</div>
          </div>
          <div class="q-stat-chip">
            <div class="val" id="statEarnings">$1,200</div>
            <div class="lbl">Earnings</div>
          </div>
        </div>

        <!-- INCOMING SERVICE REQUESTS -->
        <div class="sec-row">
          <div class="sec-ttl">Incoming Requests</div>
        </div>
        <div class="req-list">
          <div class="req-card">
            <div class="req-info">
              <div class="req-type">Plumbing</div>
              <div class="req-name">John Doe</div>
              <div class="req-loc">123 Main St</div>
              <div class="req-time">Apr 1, 10:00 AM</div>
              <div class="req-price">$50</div>
            </div>
            <div class="req-actions">
              <button class="btn-accept">Accept</button>
              <button class="btn-decline">Decline</button>
            </div>
          </div>
          <!-- more request cards could be inserted here -->
        </div>

        <!-- MY SERVICES MANAGEMENT -->
        <div class="sec-row">
          <div class="sec-ttl">My Services</div><span class="see-more" onclick="addService()">+ Add New</span>
        </div>
        <div class="svc-list">
          <div class="svc-item">
            <span>Plumbing</span>
            <span>$40</span>
            <span class="svc-actions"><button>Edit</button><button>Delete</button></span>
          </div>
          <div class="svc-item">
            <span>Electrical Repair</span>
            <span>$60</span>
            <span class="svc-actions"><button>Edit</button><button>Delete</button></span>
          </div>
        </div>

        <!-- SCHEDULE / CALENDAR -->
        <div class="sec-row">
          <div class="sec-ttl">Schedule</div>
        </div>
        <div class="sched-list">
          <div class="sched-item">Apr 1, 10AM – Plumbing with Jane Smith</div>
          <div class="sched-item">Apr 2, 2PM – Home Cleaning with Bob Lee</div>
        </div>

        <!-- EARNINGS AND PAYMENTS -->
        <div class="sec-row">
          <div class="sec-ttl">Earnings</div>
        </div>
        <div class="earn-list">
          <div class="earn-overview">Total: $1,200 • Jobs: 24</div>
          <div class="earn-item">Mar 30 – $80 – Completed</div>
        </div>

        <!-- REVIEWS & RATINGS -->
        <div class="sec-row">
          <div class="sec-ttl">Reviews</div>
        </div>
        <div class="rev-list">
          <div class="rev-item">
            <div class="rev-name">Anna K.</div>
            <div class="rev-stars">⭐⭐⭐⭐⭐</div>
            <div class="rev-text">Great work, very professional.</div>
          </div>
        </div>

        <!-- end of provider-specific sections -->

        <div class="h-pb"></div>
      </div>

      <div class="bnav">
        <div class="ni on" onclick="goPage('provider_home.php')">
          <i class="bi bi-house-fill"></i>
          <span class="nl">Home</span>
        </div>
        <div class="ni" onclick="goPage('provider_requests.php')">
          <i class="bi bi-calendar-check"></i>
          <span class="nl">Requests</span>
        </div>
        <div class="ni" onclick="goPage('provider_services.php')" aria-label="Add or manage services">
          <div class="nb-c"><i class="bi bi-plus-lg"></i></div>
        </div>
        <div class="ni" onclick="goPage('provider_notifications.php')">
          <i class="bi bi-bell-fill"></i>
          <span class="nl">Alerts</span>
          <div class="ndot"></div>
        </div>
        <div class="ni" onclick="goPage('provider_profile.php')">
          <i class="bi bi-person-fill"></i>
          <span class="nl">Profile</span>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    window.HE = window.HE || {};
    window.HE.user = {
      name: <?= json_encode($_SESSION['provider_name'] ?? '') ?>,
      email: <?= json_encode($_SESSION['provider_email'] ?? '') ?>,
      phone: <?= json_encode($_SESSION['provider_phone'] ?? '') ?>,
      address: <?= json_encode($_SESSION['provider_address'] ?? '') ?>
    };

    (function () {
      const icon = document.getElementById('dmIcon');
      if (icon && document.body.classList.contains('dark')) {
        icon.className = 'bi bi-sun-fill';
      }
    })();

    const bellDot = document.getElementById('bellDot');
    if (bellDot) {
      bellDot.style.display = 'block';
    }

    const availabilityToggle = document.getElementById('availToggle');
    if (availabilityToggle) {
      availabilityToggle.addEventListener('change', function () {
        document.body.classList.toggle('provider-online', this.checked);
      });
    }
  </script>
</body>

</html>
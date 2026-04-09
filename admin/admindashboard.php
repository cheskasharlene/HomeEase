<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
  header('Location: index.php');
  exit;
}
$adminName = htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['admin_name'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css?v=<?= time() ?>" rel="stylesheet">
  <link href="../assets/css/admindashboard.css?v=<?= time() ?>" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
  <style>
    /* ── ADMIN DASHBOARD CRITICAL INLINE CSS ── */

    /* Screen overrides */
    .screen { display:none!important; flex-direction:column!important; align-items:stretch!important; justify-content:flex-start!important; position:absolute; inset:0; overflow:hidden; background:var(--bg-screen); }
    .screen.active { display:flex!important; flex-direction:column!important; align-items:stretch!important; justify-content:flex-start!important; }

    /* Header */
    .a-hdr { display:flex!important; align-items:center; justify-content:space-between; padding:52px 18px 16px; flex-shrink:0; background:var(--bg-screen); width:100%; }
    .a-hdr-right { display:flex; align-items:center; gap:6px; }
    .a-greet { font-size:12px; color:var(--txt-muted); font-weight:700; text-transform:uppercase; letter-spacing:.5px; }
    .a-ttl { font-family:'Poppins',sans-serif; font-size:22px; font-weight:800; color:var(--txt-primary); line-height:1.1; }
    .hdr-btn { width:36px; height:36px; border-radius:50%; border:none; background:var(--bg-card); color:var(--txt-muted); font-size:16px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all .2s; }

    /* Scroll area */
    .a-scroll { flex:1; width:100%; overflow-y:auto; overflow-x:hidden; padding:0 0 90px; }

    /* Stat grid - 2 columns */
    .stat-grid { display:grid!important; grid-template-columns:1fr 1fr!important; gap:10px; padding:0 18px; margin-bottom:14px; }
    .stat-card { background:var(--bg-card); border-radius:18px; padding:14px 12px; display:flex!important; align-items:center!important; gap:10px; border:1.5px solid var(--border-col); box-shadow:0 2px 8px rgba(0,0,0,.04); }
    .stat-ic { width:42px; height:42px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
    .stat-ic.teal { background:#ccfbf1; color:#0d9488; }
    .stat-ic.green { background:#d1fae5; color:#059669; }
    .stat-ic.amber { background:#fef3c7; color:#d97706; }
    .stat-ic.blue { background:#dbeafe; color:#2563eb; }
    .stat-val { font-family:'Poppins',sans-serif; font-size:18px; font-weight:800; color:var(--txt-primary); line-height:1.1; }
    .stat-lbl { font-size:10px; font-weight:700; color:var(--txt-muted); text-transform:uppercase; letter-spacing:.3px; margin-top:1px; }

    /* Chart & section cards */
    .chart-card { background:var(--bg-card); border-radius:18px; padding:16px; margin:0 18px 14px; border:1.5px solid var(--border-col); }
    .sec-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
    .sec-ttl { font-family:'Poppins',sans-serif; font-size:15px; font-weight:800; color:var(--txt-primary); }
    .sec-pad { padding:0 18px 14px; }
    .card { background:var(--bg-card); border-radius:18px; border:1.5px solid var(--border-col); overflow:hidden; }

    /* Donut */
    .donut-wrap { display:flex!important; align-items:center; gap:18px; padding:8px 0; }
    .donut-svg { width:110px; height:110px; flex-shrink:0; }
    .donut-legend { flex:1; display:flex; flex-direction:column; gap:6px; }
    .legend-item { display:flex; align-items:center; gap:7px; font-size:12px; font-weight:600; color:var(--txt-primary); }
    .legend-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

    /* Booking card */
    .bk-card { background:var(--bg-card); border-radius:16px; padding:14px 16px; margin-bottom:10px; border:1.5px solid var(--border-col); cursor:pointer; }
    .bk-price { font-size:13px; font-weight:800; color:var(--teal); }

    /* List items */
    .list-item { display:flex!important; align-items:center; gap:12px; padding:12px 18px; border-bottom:1px solid var(--border-col); }
    .list-item:last-child { border-bottom:none; }
    .li-av { width:42px; height:42px; border-radius:12px; background:var(--teal-mid); display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
    .li-body { flex:1; min-width:0; }
    .li-name { font-size:13px; font-weight:700; color:var(--txt-primary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .li-sub { font-size:11px; color:var(--txt-muted); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .li-right { display:flex; flex-direction:column; align-items:flex-end; gap:5px; flex-shrink:0; }

    /* Pagination */
    .pg-wrap {
      display: grid;
      grid-template-columns: auto 1fr auto;
      align-items: center;
      gap: 8px;
      margin-top: 12px;
      padding: 9px 10px;
      border-radius: 14px;
      background: linear-gradient(180deg, rgba(245, 166, 35, 0.12), rgba(232, 130, 12, 0.04));
      border: 1px solid rgba(232, 130, 12, 0.2);
    }
    .pg-info {
      font-size: 11px;
      font-weight: 700;
      color: #a16207;
      display: flex;
      align-items: center;
      gap: 6px;
      justify-self: center;
    }
    .pg-info-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 50px;
      height: 24px;
      padding: 0 8px;
      border-radius: 999px;
      border: 1px solid rgba(232, 130, 12, 0.25);
      background: rgba(255, 255, 255, 0.9);
      color: #9a5a08;
      font-size: 11px;
      font-weight: 800;
    }
    .pg-btn {
      border: 1.5px solid #f2d9ad;
      background: #fff;
      color: #8e8e93;
      border-radius: 10px;
      padding: 6px 10px;
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
      min-width: 36px;
      height: 34px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all .18s;
      justify-self: start;
    }
    .pg-btn:hover:not(:disabled) {
      transform: translateY(-1px);
      border-color: #f5a623;
      color: #b96b0a;
    }
    .pg-btn.pg-next {
      min-width: 76px;
      gap: 5px;
      border-color: transparent;
      color: #fff;
      background: linear-gradient(135deg, #E8820C, #F5A623);
      box-shadow: 0 6px 14px rgba(232, 130, 12, .24);
      justify-self: end;
    }
    .pg-btn.pg-next:hover:not(:disabled) {
      border-color: transparent;
      color: #fff;
      box-shadow: 0 8px 18px rgba(232, 130, 12, .3);
    }
    .pg-btn:disabled {
      opacity: .45;
      cursor: not-allowed;
      box-shadow: none;
      transform: none;
    }

    /* Empty state */
    .empty-state { display:flex!important; flex-direction:column; align-items:center; justify-content:center; padding:36px 20px; text-align:center; gap:10px; color:var(--txt-muted); }
    .empty-state i { font-size:30px; }
    .empty-state p { font-size:13px; font-weight:600; margin:0; }

    /* Sheet overlays */
    .sheet-ol { position:absolute; inset:0; background:rgba(26,20,8,.55); z-index:200; display:flex; flex-direction:column; justify-content:flex-end; opacity:0; pointer-events:none; transition:opacity .3s; }
    .sheet-ol.on { opacity:1; pointer-events:all; }
    .sheet { background:var(--bg-card); border-radius:28px 28px 0 0; padding:0 18px 40px; max-height:88vh; overflow-y:auto; display:flex; flex-direction:column; transform:translateY(100%); transition:transform .38s cubic-bezier(.4,0,.2,1); }
    .sheet-ol.on .sheet { transform:translateY(0); }
    .sh-hand { width:40px; height:4px; background:var(--border-col); border-radius:2px; margin:14px auto 16px; flex-shrink:0; }
    .sh-hdr { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; flex-shrink:0; }
    .sh-ttl { font-family:'Poppins',sans-serif; font-size:18px; font-weight:800; color:var(--txt-primary); }
    .sh-close { width:32px; height:32px; border-radius:50%; border:none; background:var(--bg-screen); color:var(--txt-muted); font-size:15px; cursor:pointer; display:flex; align-items:center; justify-content:center; }

    /* Branded confirm dialog */
    .confirm-ol {
      position: absolute;
      inset: 0;
      background: rgba(26, 20, 8, .48);
      z-index: 260;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity .24s ease;
      padding: 18px;
    }
    .confirm-ol.on {
      opacity: 1;
      pointer-events: all;
    }
    .confirm-card {
      width: 100%;
      max-width: 320px;
      background: var(--bg-card);
      border: 1.5px solid var(--border-col);
      border-radius: 20px;
      box-shadow: 0 18px 46px rgba(0, 0, 0, .16);
      padding: 18px;
      transform: translateY(8px) scale(.98);
      transition: transform .24s ease;
    }
    .confirm-ol.on .confirm-card {
      transform: translateY(0) scale(1);
    }
    .confirm-icon {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 10px;
      background: linear-gradient(135deg, #fee2e2, #fff1f2);
      color: #dc2626;
      font-size: 20px;
    }
    .confirm-title {
      font-family: 'Poppins', sans-serif;
      font-size: 18px;
      font-weight: 800;
      color: var(--txt-primary);
      text-align: center;
    }
    .confirm-sub {
      font-size: 12px;
      font-weight: 600;
      color: var(--txt-muted);
      text-align: center;
      margin-top: 5px;
      line-height: 1.45;
    }
    .confirm-actions {
      display: flex;
      gap: 8px;
      margin-top: 14px;
    }
    .confirm-btn {
      flex: 1;
      border-radius: 12px;
      padding: 10px;
      font-size: 13px;
      font-weight: 800;
      cursor: pointer;
      border: 1.5px solid transparent;
    }
    .confirm-btn.cancel {
      background: #fff;
      border-color: var(--border-col);
      color: var(--txt-muted);
    }
    .confirm-btn.ok {
      background: linear-gradient(135deg, #E8820C, #F5A623);
      color: #fff;
      box-shadow: 0 8px 16px rgba(232, 130, 12, .28);
    }
    .confirm-reason-wrap {
      margin-top: 12px;
    }
    .confirm-reason {
      width: 100%;
      min-height: 86px;
      border: 1.5px solid var(--border-col);
      border-radius: 12px;
      padding: 10px 12px;
      font-family: 'Nunito', sans-serif;
      font-size: 13px;
      color: var(--txt-primary);
      background: var(--bg-screen);
      resize: vertical;
      outline: none;
    }
    .confirm-reason:focus {
      border-color: #f5a623;
      box-shadow: 0 0 0 3px rgba(245, 166, 35, .16);
    }

    /* Form rows & modal buttons */
    .fg-row { display:grid!important; grid-template-columns:1fr 1fr; gap:10px; }
    .modal-btns { display:flex; flex-direction:column; gap:10px; margin-top:18px; }
    .btn-danger { width:100%; padding:13px; border-radius:50px; border:none; background:#fee2e2; color:#dc2626; font-family:'Poppins',sans-serif; font-size:14px; font-weight:700; cursor:pointer; }
    .btn-outline { width:100%; padding:13px; border-radius:50px; border:2px solid var(--border-col); background:transparent; color:var(--txt-muted); font-family:'Poppins',sans-serif; font-size:14px; font-weight:700; cursor:pointer; }

    /* Search bar */
    .search-bar { display:flex!important; align-items:center; gap:10px; margin:0 18px 10px; padding:10px 14px; background:var(--bg-card); border:1.5px solid var(--border-col); border-radius:14px; flex-shrink:0; }
    .search-bar i { color:var(--txt-muted); font-size:15px; flex-shrink:0; }
    .search-bar input { flex:1; border:none; outline:none; background:transparent; font-family:'Nunito',sans-serif; font-size:13px; color:var(--txt-primary); }

    /* Action buttons */
    .act-btns { display:flex; align-items:center; gap:6px; flex-wrap:nowrap; }
    .act-btn { width:30px; height:30px; border-radius:9px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; transition:all .16s; }
    .act-btn.edit { background:#eff6ff; color:#2563eb; }
    .act-btn.edit:hover { background:#dbeafe; }
    .act-btn.pause { background:#fff4df; color:#d4790a; }
    .act-btn.pause:hover { background:#ffe6bc; }
    .act-btn.resume { background:linear-gradient(135deg, #E8820C, #F5A623); color:#fff; box-shadow:0 4px 10px rgba(232,130,12,.24); }
    .act-btn.resume:hover { filter:brightness(1.03); }
    .act-btn.del { background:#fef2f2; color:#dc2626; }
    .act-btn.del:hover { background:#fee2e2; }

    /* Detail rows */
    .detail-row { display:flex; align-items:center; justify-content:space-between; padding:11px 16px; border-bottom:1px solid var(--border-col); }
    .detail-row:last-child { border-bottom:none; }
    .detail-lbl { font-size:12px; font-weight:700; color:var(--txt-muted); }
    .detail-val { font-size:13px; font-weight:700; color:var(--txt-primary); text-align:right; }

    /* Toast notifications */
    #toastBox { position:absolute; top:16px; left:50%; transform:translateX(-50%); z-index:999; display:flex; flex-direction:column; gap:6px; width:90%; max-width:340px; }
    .toast-n { display:flex; align-items:center; gap:10px; padding:12px 16px; border-radius:14px; font-size:13px; font-weight:700; color:#fff; animation:slideDown .35s forwards; }
    .toast-n.s { background:#10b981; }
    .toast-n.e { background:#ef4444; }

    /* Workers filter row */
    .wk-filter-row {
      display: flex;
      gap: 9px;
      padding: 0 18px 8px;
      flex-wrap: wrap;
      align-items: center;
      flex-shrink: 0;
    }
    .wk-search {
      margin: 0;
      flex: 1 1 100%;
    }
    .wk-dd {
      appearance: none;
      border: 1.5px solid var(--border-col);
      border-radius: 12px;
      padding: 10px 30px 10px 12px;
      font-family: 'Nunito', sans-serif;
      font-size: 12px;
      font-weight: 700;
      color: var(--txt-muted);
      background:
        linear-gradient(45deg, transparent 50%, #caa06b 50%) calc(100% - 15px) calc(50% - 2px) / 6px 6px no-repeat,
        linear-gradient(135deg, #caa06b 50%, transparent 50%) calc(100% - 10px) calc(50% - 2px) / 6px 6px no-repeat,
        var(--bg-card);
      min-height: 40px;
      flex: 1 1 calc(33.33% - 6px);
      min-width: 120px;
      outline: none;
      box-shadow: 0 1px 2px rgba(0,0,0,.03);
      transition: border-color .18s, box-shadow .18s, background-color .18s;
    }
    .wk-dd:focus {
      border-color: #f5a623;
      box-shadow: 0 0 0 3px rgba(245, 166, 35, .14);
    }
    .wk-dd.on {
      color: #b96b0a;
      font-weight: 800;
      border-color: #f6c77f;
      background:
        linear-gradient(45deg, transparent 50%, #d68b1b 50%) calc(100% - 15px) calc(50% - 2px) / 6px 6px no-repeat,
        linear-gradient(135deg, #d68b1b 50%, transparent 50%) calc(100% - 10px) calc(50% - 2px) / 6px 6px no-repeat,
        #fff6e8;
    }
    @media (max-width: 460px) {
      .wk-dd {
        flex: 1 1 calc(50% - 6px);
      }
    }
    .status-tabs { display:flex; gap:6px; padding:12px 18px; overflow-x:auto; scrollbar-width:none; flex-shrink:0; }
    .status-tabs::-webkit-scrollbar { display:none; }
    .stab { padding:6px 13px; border-radius:20px; font-size:11px; font-weight:700; white-space:nowrap; cursor:pointer; border:2px solid var(--border-col); color:var(--txt-muted); background:var(--bg-card); }
    .stab.on { background:var(--teal); border-color:var(--teal); color:#fff; }
    .wk-verify-btn {
      border: 2px solid var(--border-col);
      background: var(--bg-card);
      color: #b96b0a;
      border-radius: 18px;
      padding: 7px 12px;
      font-size: 11px;
      font-weight: 800;
      line-height: 1;
      white-space: nowrap;
      cursor: pointer;
      transition: all .18s;
    }
    .wk-verify-btn.on {
      background: linear-gradient(135deg, #E8820C, #F5A623);
      border-color: transparent;
      color: #fff;
      box-shadow: 0 6px 12px rgba(232, 130, 12, .22);
    }
    .wk-filter-note {
      margin: -1px 18px 8px;
      font-size: 11px;
      font-weight: 700;
      color: #b96b0a;
      display: block;
    }
    .wk-filter-note.on { display: block; }

    /* User avatar */
    .user-av { width:42px; height:42px; border-radius:50%; background:linear-gradient(135deg,var(--teal),#E8960F); display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px; font-weight:800; flex-shrink:0; }

    /* Revenue mini chart */
    .rev-bar-wrap { height:64px; display:flex; align-items:flex-end; gap:4px; margin-top:10px; }
    .rev-bar-item { flex:1; display:flex; flex-direction:column; align-items:center; gap:3px; }
    .rev-bar-fill { width:100%; border-radius:4px 4px 0 0; min-height:3px; background:var(--teal); opacity:.8; }
    .rev-bar-lbl { font-size:8px; color:var(--txt-muted); font-weight:700; }

    /* More screen */
    .more-row { display:flex; align-items:center; gap:14px; padding:15px 18px; border-bottom:1px solid var(--border-col); cursor:pointer; }
    .more-row:last-child { border-bottom:none; }
    .more-ic { width:40px; height:40px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:19px; flex-shrink:0; }
    .more-nm { font-size:14px; font-weight:700; color:var(--txt-primary); }
    .more-sub { font-size:11px; color:var(--txt-muted); margin-top:1px; }
    .more-arrow { margin-left:auto; color:#d1d5db; font-size:15px; }

    /* Toggle switch */
    .toggle-sw { width:44px; height:24px; border-radius:12px; position:relative; cursor:pointer; transition:background .2s; flex-shrink:0; }
    .toggle-sw.on { background:var(--teal); }
    .toggle-sw.off { background:#e5e7eb; }
    .toggle-sw::after { content:''; position:absolute; top:3px; left:3px; width:18px; height:18px; background:#fff; border-radius:50%; transition:transform .2s; }
    .toggle-sw.on::after { transform:translateX(20px); }

    /* Offer & svc rows */
    .svc-row { display:flex; align-items:center; gap:10px; padding:11px 18px; border-bottom:1px solid var(--border-col); cursor:pointer; }
    .svc-row:last-child { border-bottom:none; }
    .svc-ic-sm { width:36px; height:36px; border-radius:10px; background:var(--teal-mid); display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
    .offer-list-item { display:flex; align-items:flex-start; gap:10px; padding:12px 18px; border-bottom:1px solid var(--border-col); cursor:pointer; }
    .offer-list-item:last-child { border-bottom:none; }
    .offer-ic { width:38px; height:38px; border-radius:10px; background:#fef3c7; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }

    /* Loading splash override */
    #ml { position:absolute; inset:0; background:linear-gradient(145deg,#E8820C 0%,#F5A623 42%,#FFB347 72%,#FFC96B 100%); z-index:999; display:flex; flex-direction:column; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity .2s; }
    #ml.on { opacity:1; pointer-events:all; }

    /* Animations */
    @keyframes slideDown { from{opacity:0;transform:translateY(-12px)} to{opacity:1;transform:translateY(0)} }
    @keyframes w-spin { to{transform:rotate(360deg)} }

    /* Admin Notification Bell */
    .notif-bell-wrap { position:relative; display:inline-flex; }
    .notif-badge { position:absolute; top:-4px; right:-4px; min-width:18px; height:18px; padding:0 5px; border-radius:9px; background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center; line-height:1; border:2px solid var(--bg-screen); animation:badgePop .35s cubic-bezier(.4,0,.2,1); }
    .notif-badge:empty, .notif-badge[data-count='0'] { display:none; }
    @keyframes badgePop { 0%{transform:scale(0)} 50%{transform:scale(1.3)} 100%{transform:scale(1)} }
    .hdr-btn.bell-active { color:var(--teal); }
    @keyframes bellShake { 0%{transform:rotate(0)} 15%{transform:rotate(14deg)} 30%{transform:rotate(-12deg)} 45%{transform:rotate(8deg)} 60%{transform:rotate(-6deg)} 75%{transform:rotate(2deg)} 100%{transform:rotate(0)} }
    .bell-shake i { animation:bellShake .6s ease-in-out; }

    /* Admin Notification Items */
    .admin-notif-item { display:flex; align-items:flex-start; gap:12px; padding:14px 16px; border-bottom:1px solid var(--border-col); cursor:pointer; transition:background .15s; position:relative; }
    .admin-notif-item:hover { background:var(--teal-bg); }
    .admin-notif-item:last-child { border-bottom:none; }
    .admin-notif-item.unread { background:rgba(245,166,35,.06); }
    .admin-notif-item.unread::before { content:''; position:absolute; left:6px; top:50%; transform:translateY(-50%); width:6px; height:6px; border-radius:50%; background:#ef4444; }
    .admin-notif-ic { width:42px; height:42px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; flex-shrink:0; }
    .admin-notif-ic.verif { background:linear-gradient(135deg,#dbeafe,#eff6ff); color:#2563eb; }
    .admin-notif-ic.general { background:linear-gradient(135deg,#fef3c7,#fff8ef); color:#d97706; }
    .admin-notif-body { flex:1; min-width:0; }
    .admin-notif-ttl { font-size:13px; font-weight:700; color:var(--txt-primary); margin-bottom:2px; }
    .admin-notif-msg { font-size:12px; color:var(--txt-muted); line-height:1.45; }
    .admin-notif-time { font-size:10px; color:var(--txt-muted); margin-top:4px; font-weight:600; }
    .admin-notif-actions { display:flex; align-items:center; gap:4px; flex-shrink:0; }
    .admin-notif-act-btn { width:28px; height:28px; border-radius:8px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; transition:all .15s; }
    .admin-notif-act-btn.go { background:#dbeafe; color:#2563eb; }
    .admin-notif-act-btn.del { background:#fee2e2; color:#dc2626; }
    .admin-notif-act-btn:hover { transform:scale(1.1); }

    /* Analytics */
    .an-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; padding:0 18px; margin-bottom:14px; }
    .an-metric { background:var(--bg-card); border-radius:16px; padding:16px 14px; border:1.5px solid var(--border-col); }
    .an-metric-lbl { font-size:11px; font-weight:700; color:var(--txt-muted); text-transform:uppercase; letter-spacing:.3px; }
    .an-metric-val { font-size:22px; font-weight:800; color:var(--txt-primary); font-family:'Poppins',sans-serif; margin-top:4px; }
    .an-metric-chg { font-size:11px; font-weight:700; margin-top:4px; display:flex; align-items:center; gap:3px; }
    .an-metric-chg.up { color:#059669; }
    .an-metric-chg.down { color:#dc2626; }
    .an-metric-chg.flat { color:#64748b; }
    .an-cards-grid { display:grid; grid-template-columns:1fr; gap:14px; padding:0 18px 14px; }
    .an-chart-card { background:var(--bg-card); border-radius:16px; border:1.5px solid var(--border-col); padding:16px; margin:0; }
    .an-chart-ttl { font-size:14px; font-weight:800; color:var(--txt-primary); font-family:'Poppins',sans-serif; margin-bottom:4px; }
    .an-chart-sub { font-size:11px; color:var(--txt-muted); margin-bottom:14px; }
    .an-chart-canvas { width:100%; position:relative; }
    .an-worker-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-col); }
    .an-worker-row:last-child { border-bottom:none; }
    .an-worker-rank { width:24px; height:24px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:800; flex-shrink:0; }
    .an-worker-rank.gold { background:#fef3c7; color:#d97706; }
    .an-worker-rank.silver { background:#f1f5f9; color:#64748b; }
    .an-worker-rank.bronze { background:#fff7ed; color:#c2410c; }
    .an-worker-rank.other { background:var(--bg-screen); color:var(--txt-muted); }
    .an-worker-bar-wrap { flex:1; min-width:0; }
    .an-worker-nm { font-size:12px; font-weight:700; color:var(--txt-primary); font-family:'Poppins',sans-serif; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
    .an-worker-bar { height:6px; border-radius:3px; background:var(--border-col); margin-top:4px; overflow:hidden; }
    .an-worker-bar-fill { height:100%; border-radius:3px; background:linear-gradient(90deg,var(--teal),#FFB347); transition:width .6s; }
    .an-worker-jobs { font-size:11px; font-weight:700; color:var(--teal); flex-shrink:0; font-family:'Poppins',sans-serif; }
    .an-chart-card.full-span { grid-column:1 / -1; }

    @media (min-width:700px) {
      .an-cards-grid { grid-template-columns:repeat(2,minmax(0,1fr)); }
    }

  </style>
</head>

<body>

  <div class="shell" id="app">
    <!-- Loading splash -->
    <div id="ml" class="on">
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


    <div id="toastBox"></div>

    <div class="screen active" id="sc-overview">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Welcome back, <?= $adminName ?></div>
          <div class="a-ttl">Dashboard</div>
        </div>
        <div class="a-hdr-right">
          <div class="notif-bell-wrap">
            <button class="hdr-btn" id="adminBellBtn" onclick="openAdminNotifSheet()" title="Notifications">
              <i class="bi bi-bell-fill"></i>
            </button>
            <span class="notif-badge" id="adminNotifBadge" data-count="0"></span>
          </div>
          <button class="hdr-btn" onclick="loadOverview()" title="Refresh"><i
              class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="a-scroll" id="overview-scroll">

        <div class="stat-grid" id="statGrid">
          <div class="stat-card">
            <div class="stat-ic teal"><i class="bi bi-people-fill"></i></div>
            <div>
              <div class="stat-val" id="st-users">–</div>
              <div class="stat-lbl">Total Users</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic green"><i class="bi bi-calendar-check-fill"></i></div>
            <div>
              <div class="stat-val" id="st-bookings">–</div>
              <div class="stat-lbl">Bookings</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic amber"><i class="bi bi-currency-dollar"></i></div>
            <div>
              <div class="stat-val" id="st-revenue">–</div>
              <div class="stat-lbl">Revenue</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic blue"><i class="bi bi-person-badge-fill"></i></div>
            <div>
              <div class="stat-val" id="st-workers">–</div>
              <div class="stat-lbl">Workers</div>
            </div>
          </div>
        </div>

        <div class="chart-card">
          <div class="sec-hdr">
            <div class="sec-ttl">Revenue (₱)</div><span id="revTotal"
              style="font-size:12px;font-weight:700;color:var(--teal);">Loading...</span>
          </div>
          <div class="rev-bar-wrap" id="revChart"></div>
        </div>


        <div class="chart-card" style="margin-top:12px;">
          <div class="sec-ttl">Booking Status</div>
          <div class="donut-wrap">
            <svg class="donut-svg" viewBox="0 0 80 80" id="donutSvg">
              <circle cx="40" cy="40" r="30" fill="none" stroke="var(--border-col)" stroke-width="12" />
            </svg>
            <div class="donut-legend" id="donutLegend"></div>
          </div>
        </div>


        <div class="sec-pad">
          <div class="sec-hdr">
            <div class="sec-ttl">Recent Bookings</div>
            <span onclick="showTab('bookings')"
              style="font-size:12px;font-weight:700;color:var(--teal);cursor:pointer;">See all</span>
          </div>
          <div class="card" id="recentBookings">
            <div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite;"></i>
              <p>Loading...</p>
            </div>
          </div>
        </div>
        <div style="height:20px;"></div>
      </div>
    </div>


    <div class="screen" id="sc-bookings">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Bookings</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" id="bkFilterToggle" onclick="toggleBkFilters()" title="Filters"><i
              class="bi bi-funnel-fill"></i></button>
          <button class="hdr-btn" onclick="loadBookings()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="bkSearch"
          placeholder="Search by user, service, address..." oninput="debounce(loadBookings,400)()"></div>
      <div class="status-tabs">
        <div class="stab on" data-bk="all" onclick="setBkFilter(this,'all')">All</div>
        <div class="stab" data-bk="pending" onclick="setBkFilter(this,'pending')">Pending</div>
        <div class="stab" data-bk="progress" onclick="setBkFilter(this,'progress')">In Progress</div>
        <div class="stab" data-bk="done" onclick="setBkFilter(this,'done')">Done</div>
        <div class="stab" data-bk="cancelled" onclick="setBkFilter(this,'cancelled')">Cancelled</div>
      </div>
      <!-- Advanced Filters Panel -->
      <div id="bkFiltersPanel"
        style="display:none;background:var(--bg-card);border-bottom:1px solid var(--border-col);padding:12px 18px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
          <div>
            <label class="fl" style="font-size:10px;">Date From</label>
            <input class="fi" type="date" id="bkDateFrom" onchange="loadBookings()"
              style="padding:7px 10px;font-size:12px;">
          </div>
          <div>
            <label class="fl" style="font-size:10px;">Date To</label>
            <input class="fi" type="date" id="bkDateTo" onchange="loadBookings()"
              style="padding:7px 10px;font-size:12px;">
          </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;">
          <div>
            <label class="fl" style="font-size:10px;">Service Type</label>
            <select class="fi" id="bkServiceFilter" onchange="loadBookings()" style="padding:7px 10px;font-size:12px;">
              <option value="">All Services</option>
            </select>
          </div>
          <div>
            <label class="fl" style="font-size:10px;">Worker</label>
            <select class="fi" id="bkWorkerFilter" onchange="loadBookings()" style="padding:7px 10px;font-size:12px;">
              <option value="">All Workers</option>
            </select>
          </div>
        </div>
        <button onclick="resetBkFilters()"
          style="margin-top:10px;width:100%;padding:7px;border-radius:10px;border:1.5px solid var(--border-col);background:transparent;color:var(--txt-muted);font-size:12px;font-weight:700;cursor:pointer;">Reset
          Filters</button>
      </div>
      <div class="a-scroll" id="bk-scroll" style="padding:12px 18px 80px;">
        <div id="bkList">
          <div class="empty-state">
            <p>Loading...</p>
          </div>
        </div>
        <div id="bkPagination"></div>
      </div>
    </div>


    <div class="screen" id="sc-workers">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Workers</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="loadWorkers()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="wk-filter-row" id="wkFilterRow">
        <div class="search-bar wk-search"><i class="bi bi-search"></i><input type="text" id="wkSearch"
            placeholder="Search workers..." oninput="debounce(loadWorkers,320)()"></div>
        <select class="wk-dd" id="wkStatusFilter" onchange="loadWorkers()">
          <option value="all">Status: All</option>
          <option value="pending">Pending</option>
          <option value="verified">Verified</option>
          <option value="rejected">Rejected</option>
          <option value="paused">Paused / Inactive</option>
        </select>
        <select class="wk-dd" id="wkAvailabilityFilter" onchange="loadWorkers()">
          <option value="all">Availability: All</option>
          <option value="available">Available</option>
          <option value="unavailable">Unavailable</option>
          <option value="on_job">On Job</option>
        </select>
        <select class="wk-dd" id="wkServiceFilter" onchange="loadWorkers()">
          <option value="all">Service: All</option>
          <option value="cleaner">Cleaner</option>
          <option value="helper">Helper</option>
          <option value="laundry">Laundry</option>
          <option value="plumber">Plumber</option>
          <option value="carpenter">Carpenter</option>
          <option value="appliance technician">Appliance Technician</option>
        </select>
      </div>
      <div id="wkFilterNote" class="wk-filter-note">Showing: All workers</div>
      <div class="a-scroll" id="wk-scroll" style="padding:12px 18px 80px;">
        <div id="wkList">
          <div class="empty-state">
            <p>Loading...</p>
          </div>
        </div>
        <div id="wkPagination"></div>
      </div>
    </div>


    <div class="screen" id="sc-users">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Manage</div>
          <div class="a-ttl">Users</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="loadUsers()"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="wk-filter-row" id="usFilterRow">
        <div class="search-bar wk-search"><i class="bi bi-search"></i><input type="text" id="usSearch"
            placeholder="Search users..." oninput="debounce(loadUsers,320)()"></div>
        <select class="wk-dd" id="usStatusFilter" onchange="loadUsers()">
          <option value="all">Status: All</option>
          <option value="active">Active</option>
          <option value="disabled">Disabled / Suspended</option>
        </select>
      </div>
      <div id="usFilterNote" class="wk-filter-note">Showing: All users</div>
      <div class="a-scroll" id="us-scroll" style="padding:12px 18px 80px;">
        <div id="usList">
          <div class="empty-state">
            <p>Loading...</p>
          </div>
        </div>
        <div id="usPagination"></div>
      </div>
    </div>

    <div class="screen" id="sc-more">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Admin</div>
          <div class="a-ttl">More</div>
        </div>
        <div class="a-hdr-right"></div>
      </div>
      <div class="a-scroll" id="more-scroll">

        <div class="sec-pad">
          <div class="sec-hdr">
            <div class="sec-ttl">Services</div>
            <button onclick="openSvcSheet(null)"
              style="background:var(--teal);color:#fff;border:none;border-radius:20px;padding:5px 13px;font-size:12px;font-weight:700;cursor:pointer;"><i
                class="bi bi-plus-lg"></i> Add</button>
          </div>
          <div class="card" id="svcList">
            <div class="empty-state">
              <p>Loading...</p>
            </div>
          </div>
        </div>




        <div class="sec-pad" style="margin-top:14px;">
          <div class="sec-ttl">Admin</div>
          <div class="card">
            <div class="more-row" onclick="openReviewSheet()">
              <div class="more-ic" style="background:#e0e7ff;color:#4f46e5;"><i class="bi bi-star-fill"></i></div>
              <div>
                <div class="more-nm" style="color:#4f46e5;">Manage Reviews</div>
                <div class="more-sub">Monitor and moderate user feedback</div>
              </div>
              <i class="bi bi-chevron-right more-arrow"></i>
            </div>
            <div class="more-row" onclick="openLogoutConfirm()">
              <div class="more-ic" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-box-arrow-right"></i>
              </div>
              <div>
                <div class="more-nm" style="color:#dc2626;">Logout</div>
                <div class="more-sub">Sign out of admin portal</div>
              </div>
              <i class="bi bi-chevron-right more-arrow"></i>
            </div>
          </div>
        </div>
        <div style="height:20px;"></div>
      </div>
    </div>

    <!-- ── Analytics Screen ── -->
    <div class="screen" id="sc-analytics">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Business</div>
          <div class="a-ttl">Analytics</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="loadAnalytics()" title="Refresh"><i
              class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="a-scroll" id="analytics-scroll" style="padding-top:14px;padding-bottom:90px;">

        <!-- Growth Metrics -->
        <div class="an-grid" id="anMetrics">
          <div class="an-metric flat">
            <div class="an-metric-lbl">This Month</div>
            <div class="an-metric-val" id="anThisMonth">–</div>
            <div class="an-metric-chg flat" id="anGrowth"><i class="bi bi-dash"></i> –</div>
          </div>
          <div class="an-metric flat">
            <div class="an-metric-lbl">Last Month</div>
            <div class="an-metric-val" id="anLastMonth">–</div>
            <div class="an-metric-chg flat"><i class="bi bi-calendar3"></i> comparison</div>
          </div>
        </div>

        <div class="an-cards-grid">
          <!-- Bookings Trend -->
          <div class="an-chart-card">
            <div class="an-chart-ttl">Booking Trends</div>
            <div class="an-chart-sub">Daily bookings over the last 30 days</div>
            <div class="an-chart-canvas"><canvas id="chartBookingTrend" height="180"></canvas></div>
          </div>

          <!-- Service Distribution -->
          <div class="an-chart-card">
            <div class="an-chart-ttl">Service Distribution</div>
            <div class="an-chart-sub">Bookings by service category</div>
            <div class="an-chart-canvas" style="max-width:280px;margin:0 auto;"><canvas id="chartServiceDist"
                height="220"></canvas></div>
          </div>

          <!-- Revenue Chart -->
          <div class="an-chart-card full-span">
            <div class="an-chart-ttl">Weekly Revenue</div>
            <div class="an-chart-sub">Revenue from completed bookings (last 8 weeks)</div>
            <div class="an-chart-canvas"><canvas id="chartRevenue" height="180"></canvas></div>
          </div>

          <!-- Top Workers -->
          <div class="an-chart-card full-span">
            <div class="an-chart-ttl">Top Performing Workers</div>
            <div class="an-chart-sub">Ranked by total jobs completed</div>
            <div id="anTopWorkers"></div>
          </div>
        </div>

      </div>
    </div>

    <div class="bnav">
      <div class="ni on" id="nav-overview" onclick="showTab('overview')"><i class="bi bi-grid-1x2-fill"></i><span
          class="nl">Overview</span></div>
      <div class="ni" id="nav-analytics" onclick="showTab('analytics')"><i class="bi bi-graph-up"></i><span
          class="nl">Analytics</span></div>
      <div class="ni" id="nav-bookings" onclick="showTab('bookings')"><i class="bi bi-calendar-check-fill"></i><span
          class="nl">Bookings</span></div>
      <div class="ni" id="nav-workers" onclick="showTab('workers')"><i class="bi bi-person-badge-fill"></i><span
          class="nl">Workers</span></div>
      <div class="ni" id="nav-users" onclick="showTab('users')"><i class="bi bi-people-fill"></i><span
          class="nl">Users</span></div>
      <div class="ni" id="nav-more" onclick="showTab('more')"><i class="bi bi-grid-fill"></i><span
          class="nl">More</span></div>
    </div>


    <div class="confirm-ol" id="logoutConfirmOl" onclick="if(event.target===this)closeLogoutConfirm()">
      <div class="confirm-card">
        <div class="confirm-icon"><i class="bi bi-box-arrow-right"></i></div>
        <div class="confirm-title">Log out?</div>
        <div class="confirm-sub">You will be signed out of the admin portal and returned to login.</div>
        <div class="confirm-actions">
          <button class="confirm-btn cancel" onclick="closeLogoutConfirm()">Cancel</button>
          <button class="confirm-btn ok" onclick="confirmLogout()">Log out</button>
        </div>
      </div>
    </div>

    <div class="confirm-ol" id="workerVerifyConfirmOl" onclick="if(event.target===this)closeWorkerVerificationModal(null)">
      <div class="confirm-card">
        <div class="confirm-icon" id="workerVerifyIcon"><i class="bi bi-shield-check"></i></div>
        <div class="confirm-title" id="workerVerifyTitle">Approve worker verification?</div>
        <div class="confirm-sub" id="workerVerifySub">This will verify the worker and allow them to accept jobs.</div>
        <div class="confirm-reason-wrap" id="workerVerifyReasonWrap" style="display:none;">
          <textarea id="workerVerifyReasonInput" class="confirm-reason" placeholder="Enter rejection reason..."></textarea>
        </div>
        <div class="confirm-actions">
          <button class="confirm-btn cancel" onclick="closeWorkerVerificationModal(null)">Cancel</button>
          <button class="confirm-btn ok" id="workerVerifyOkBtn" onclick="submitWorkerVerificationModal()">Approve</button>
        </div>
      </div>
    </div>

    <div class="sheet-ol" id="bkDetailOl" onclick="if(event.target===this)closeSheet('bkDetailOl')">
        <div class="sheet" style="max-height:92vh;">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="sh-ttl">Booking Details</div>
              <span id="bkDetailStatus" class="badge-gray" style="font-size:10px;"></span>
            </div>
            <button class="sh-close" onclick="closeSheet('bkDetailOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <div id="bkDetailBody" style="overflow-y:auto;flex:1;"></div>
        </div>
      </div>

      <!-- Worker Picker Sheet -->
      <div class="sheet-ol" id="workerPickerOl" onclick="if(event.target===this)closeSheet('workerPickerOl')">
        <div class="sheet">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl" id="workerPickerTtl">Assign Worker</div>
            <button class="sh-close" onclick="closeSheet('workerPickerOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <div class="search-bar" style="margin:0 0 8px;"><i class="bi bi-search"></i><input type="text"
              id="workerPickerSearch" placeholder="Search workers..." oninput="filterWorkerPicker()"></div>
          <div id="workerPickerList" style="flex:1;overflow-y:auto;"></div>
        </div>
      </div>


      <div class="sheet-ol" id="wkSheetOl" onclick="if(event.target===this)closeSheet('wkSheetOl')">
        <div class="sheet">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl" id="wkSheetTtl">Worker Details</div>
            <button class="sh-close" onclick="closeSheet('wkSheetOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <div style="text-align:center;margin-bottom:16px;">
            <div class="user-av" id="wkAvatar" style="width:62px;height:62px;font-size:22px;margin:0 auto 10px;">W</div>
            <div id="wkName" style="font-size:18px;font-weight:800;color:var(--txt-primary);">Worker Name</div>
            <div id="wkSpecialty" style="font-size:12px;color:var(--txt-muted);margin-top:2px;">Specialty</div>
          </div>
          <div class="detail-row"><span class="detail-lbl">Phone</span><span class="detail-val" id="wkPhone">–</span></div>
          <div class="detail-row"><span class="detail-lbl">Availability</span><span class="detail-val" id="wkAvail">–</span></div>
          <div class="detail-row"><span class="detail-lbl">Status</span><span class="detail-val" id="wkStatus">–</span></div>
          <div class="detail-row"><span class="detail-lbl">Rating</span><span class="detail-val" id="wkRating">–</span></div>
          <div class="detail-row"><span class="detail-lbl">Jobs Done</span><span class="detail-val" id="wkJobs">–</span></div>
          <div class="detail-row" style="align-items:flex-start;gap:14px;">
            <span class="detail-lbl">Verification Documents</span>
            <div class="detail-val" id="wkVdocs" style="max-width:58%;text-align:right;font-size:12px;line-height:1.45;">
              No documents uploaded.
            </div>
          </div>
          <div id="wkActionButtons" class="modal-btns" style="margin-top:18px;gap:10px;display:none;">
            <button class="btn-outline-p" id="wkRejectBtn" onclick="rejectWorkerVerification()">Reject</button>
            <button class="btn-p" id="wkApproveBtn" onclick="approveWorkerVerification()">Approve</button>
          </div>
        </div>
      </div>


      <div class="sheet-ol" id="usDetailOl" onclick="if(event.target===this)closeSheet('usDetailOl')">
        <div class="sheet">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl">User Details</div>
            <button class="sh-close" onclick="closeSheet('usDetailOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <div id="usDetailBody"></div>
        </div>
      </div>


      <div class="sheet-ol" id="svcSheetOl" onclick="if(event.target===this)closeSheet('svcSheetOl')">
        <div class="sheet">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl" id="svcSheetTtl">Add Service</div>
            <button class="sh-close" onclick="closeSheet('svcSheetOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <input type="hidden" id="svcId">
          <div class="fg-row">
            <div class="fg"><label class="fl">Icon (emoji)</label><input class="fi" id="svcIcon" placeholder="🔧"
                maxlength="5"></div>
            <div class="fg"><label class="fl">Name *</label><input class="fi" id="svcName" placeholder="e.g. Cleaning">
            </div>
          </div>
          <div class="fg"><label class="fl">Description</label><input class="fi" id="svcDesc"
              placeholder="Short description"></div>
          <div class="fg-row">
            <div class="fg"><label class="fl">Hourly Rate (₱)</label><input class="fi" id="svcHourly" type="number"
                min="0" value="0"></div>
            <div class="fg"><label class="fl">Flat Rate (₱)</label><input class="fi" id="svcFlat" type="number" min="0"
                value="0"></div>
          </div>
          <div class="fg-row">
            <div class="fg"><label class="fl">Min Hours</label><input class="fi" id="svcMinH" type="number" min="1"
                value="1"></div>
            <div class="fg"><label class="fl">Pricing Type</label>
              <select class="fi" id="svcPtype">
                <option value="both">Both</option>
                <option value="hourly">Hourly Only</option>
                <option value="flat">Flat Only</option>
              </select>
            </div>
          </div>
          <div class="modal-btns">
            <button class="btn-p" onclick="saveSvc()">Save Service</button>
            <button class="btn-danger" id="svcDelBtn" style="display:none;" onclick="deleteSvc()">Delete</button>
          </div>
        </div>
      </div>


      <div class="sheet-ol" id="offerSheetOl" onclick="if(event.target===this)closeSheet('offerSheetOl')">
        <div class="sheet">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl" id="offerSheetTtl">Add Offer</div>
            <button class="sh-close" onclick="closeSheet('offerSheetOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <input type="hidden" id="offerId">
          <div class="fg"><label class="fl">Offer Title *</label><input class="fi" id="offerTitle"
              placeholder="e.g. Summer Promo"></div>
          <div class="fg"><label class="fl">Promo Code *</label><input class="fi" id="offerCode" placeholder="SUMMER20"
              style="text-transform:uppercase;"></div>
          <div class="fg-row">
            <div class="fg"><label class="fl">Type</label>
              <select class="fi" id="offerType">
                <option value="percent">Percent %</option>
                <option value="flat">Flat ₱</option>
              </select>
            </div>
            <div class="fg"><label class="fl">Discount Value</label><input class="fi" id="offerVal" type="number"
                min="0" value="10"></div>
          </div>
          <div class="fg-row">
            <div class="fg"><label class="fl">Min Booking ₱</label><input class="fi" id="offerMin" type="number" min="0"
                value="0"></div>
            <div class="fg"><label class="fl">Max Uses (0=unlimited)</label><input class="fi" id="offerMaxUses"
                type="number" min="0" value="0"></div>
          </div>
          <div class="fg"><label class="fl">Expires At</label><input class="fi" id="offerExpires" type="datetime-local">
          </div>
          <div class="fg"><label class="fl">Description</label><input class="fi" id="offerDesc"
              placeholder="Brief description"></div>
          <div class="modal-btns">
            <button class="btn-p" onclick="saveOffer()">Save Offer</button>
            <button class="btn-danger" id="offerDelBtn" style="display:none;" onclick="deleteOffer()">Delete</button>
          </div>
        </div>
      </div>

      <!-- Review Management Sheet -->
      <div class="sheet-ol" id="reviewSheetOl" onclick="if(event.target===this)closeSheet('reviewSheetOl')">
        <div class="sheet" style="max-height:92vh;">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl">Manage Reviews</div>
            <button class="sh-close" onclick="closeSheet('reviewSheetOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <div id="reviewSheetBody" style="overflow-y:auto;flex:1;padding:15px;">
            <div class="empty-state">
              <p>Loading reviews...</p>
            </div>
          </div>
        </div>
      </div>
      <!-- Admin Notifications Sheet -->
      <div class="sheet-ol" id="adminNotifSheetOl" onclick="if(event.target===this)closeSheet('adminNotifSheetOl')">
        <div class="sheet" style="max-height:88vh;">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="sh-ttl">Notifications</div>
              <span class="notif-badge" id="adminNotifBadgeSheet" data-count="0" style="position:static;border:none;"></span>
            </div>
            <div style="display:flex;align-items:center;gap:6px;">
              <button onclick="markAllAdminNotifsRead()" style="background:var(--teal-bg);color:var(--teal);border:none;border-radius:8px;padding:6px 10px;font-size:11px;font-weight:700;cursor:pointer;" title="Mark all as read">
                <i class="bi bi-check2-all"></i> Read all
              </button>
              <button class="sh-close" onclick="closeSheet('adminNotifSheetOl')"><i class="bi bi-x-lg"></i></button>
            </div>
          </div>
          <div id="adminNotifList" style="overflow-y:auto;flex:1;">
            <div class="empty-state"><p>Loading...</p></div>
          </div>
        </div>
      </div>

    </div><!-- /.shell -->

    <script>

      const API = (section, action = 'list', extra = '') =>
        `../api/admin_api.php?section=${section}&action=${action}${extra}`;

      async function api(section, action = 'list', body = null, extra = '') {
        const url = API(section, action, extra);
        const opts = body ? { method: 'POST', body } : { method: 'GET' };
        const res = await fetch(url, opts);
        return res.json();
      }

      function fd(obj) {
        const f = new FormData();
        for (const [k, v] of Object.entries(obj)) if (v !== undefined && v !== null) f.append(k, v);
        return f;
      }

      function toast(msg, type = 's') {
        const box = document.getElementById('toastBox');
        const t = document.createElement('div');
        t.className = `toast-n ${type}`;
        t.innerHTML = `<i class="bi bi-${type === 's' ? 'check-circle-fill' : 'exclamation-circle-fill'}"></i>${msg}`;
        box.appendChild(t);
        setTimeout(() => t.remove(), 3200);
      }

      function confirm2(msg) { return window.confirm(msg); }

      let workerVerifyResolver = null;
      let workerVerifyMode = 'approve';

      function openWorkerVerificationModal(mode = 'approve') {
        const overlay = document.getElementById('workerVerifyConfirmOl');
        const title = document.getElementById('workerVerifyTitle');
        const sub = document.getElementById('workerVerifySub');
        const okBtn = document.getElementById('workerVerifyOkBtn');
        const icon = document.getElementById('workerVerifyIcon');
        const reasonWrap = document.getElementById('workerVerifyReasonWrap');
        const reasonInput = document.getElementById('workerVerifyReasonInput');

        if (!overlay || !title || !sub || !okBtn || !icon || !reasonWrap || !reasonInput) {
          return Promise.resolve(mode === 'approve' ? false : '');
        }

        workerVerifyMode = mode;
        reasonInput.value = '';

        if (mode === 'reject') {
          title.textContent = 'Reject worker verification?';
          sub.textContent = 'Provide a reason so the worker knows what to fix before resubmitting.';
          okBtn.textContent = 'Submit Rejection';
          icon.innerHTML = '<i class="bi bi-shield-x"></i>';
          reasonWrap.style.display = 'block';
        } else {
          title.textContent = 'Approve worker verification?';
          sub.textContent = 'This will verify the worker and allow them to accept jobs.';
          okBtn.textContent = 'Approve';
          icon.innerHTML = '<i class="bi bi-shield-check"></i>';
          reasonWrap.style.display = 'none';
        }

        overlay.classList.add('on');
        if (mode === 'reject') {
          setTimeout(() => reasonInput.focus(), 60);
        }

        return new Promise(resolve => {
          workerVerifyResolver = resolve;
        });
      }

      function closeWorkerVerificationModal(result = null) {
        const overlay = document.getElementById('workerVerifyConfirmOl');
        if (overlay) overlay.classList.remove('on');
        const resolve = workerVerifyResolver;
        workerVerifyResolver = null;
        if (resolve) resolve(result);
      }

      function submitWorkerVerificationModal() {
        if (workerVerifyMode === 'reject') {
          const reasonInput = document.getElementById('workerVerifyReasonInput');
          const reason = (reasonInput?.value || '').trim();
          if (!reason) {
            toast('Please enter a rejection reason', 'e');
            if (reasonInput) reasonInput.focus();
            return;
          }
          closeWorkerVerificationModal(reason);
          return;
        }
        closeWorkerVerificationModal(true);
      }

      function openSheet(id) { document.getElementById(id).classList.add('on'); }
      function closeSheet(id) { document.getElementById(id).classList.remove('on'); }

      const debounce = (fn, ms) => {
        let t;
        return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
      };

      function statusPill(s) {
        const key = String(s || '').toLowerCase();
        const map = { pending: 'badge-amber', progress: 'badge-blue', done: 'badge-green', cancelled: 'badge-gray', active: 'badge-green', inactive: 'badge-red', available: 'badge-green', busy: 'badge-amber', offline: 'badge-gray' };
        return `<span class="${map[key] || 'badge-gray'}">${key ? key.charAt(0).toUpperCase() + key.slice(1) : '–'}</span>`;
      }

      function workerStateBadge(type, value) {
        const key = String(value || '').toLowerCase();
        const availabilityMap = { online: 'badge-green', available: 'badge-green', offline: 'badge-gray', busy: 'badge-amber' };
        const statusMap = { active: 'badge-green', inactive: 'badge-gray', paused: 'badge-amber', pending: 'badge-gray', 'pending verification': 'badge-gray' };
        const labelMap = {
          availability: { online: 'Available', available: 'Available', offline: 'Unavailable', busy: 'On Job' },
          status: { active: 'Online', inactive: 'Offline', paused: 'Paused', pending: 'Offline', 'pending verification': 'Offline' }
        };
        const map = type === 'availability' ? availabilityMap : statusMap;
        const label = (labelMap[type] && labelMap[type][key]) || (key ? key.charAt(0).toUpperCase() + key.slice(1) : '–');
        return `<span class="${map[key] || 'badge-gray'}">${label}</span>`;
      }

      function getWorkerVerificationBadgeState(worker) {
        if (isWorkerUiPaused(worker?.id)) return 'paused';
        const verificationStatus = String(worker?.verification_status || '').toLowerCase().trim();
        const isVerified = Number(worker?.is_verified) === 1 || verificationStatus === 'approved' || verificationStatus === 'verified';
        return isVerified ? 'active' : 'inactive';
      }

      function isWorkerUiPaused(workerId) {
        return !!(workerUiState[String(workerId)] && workerUiState[String(workerId)].paused);
      }

      function toggleWorkerPause(workerId) {
        const key = String(workerId);
        const currentlyPaused = isWorkerUiPaused(workerId);
        workerUiState[key] = { paused: !currentlyPaused };
        toast(!currentlyPaused ? 'Worker temporarily suspended (UI only)' : 'Worker reactivated (UI only)');
        loadWorkers();
      }

      function php(n) { return '₱' + parseFloat(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

      let curTab = 'overview';
      const tabMap = { overview: 'sc-overview', analytics: 'sc-analytics', bookings: 'sc-bookings', workers: 'sc-workers', users: 'sc-users', more: 'sc-more' };
      const loadMap = { analytics: loadAnalytics, bookings: loadBookings, workers: loadWorkers, users: loadUsers, more: loadMore };

      function showTab(tab) {
        document.querySelectorAll('.screen').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.ni').forEach(n => n.classList.remove('on'));
        document.getElementById(tabMap[tab]).classList.add('active');
        const navEl = document.getElementById('nav-' + tab);
        if (navEl) navEl.classList.add('on');
        curTab = tab;
        if (loadMap[tab]) loadMap[tab]();
      }

      setInterval(() => {
        const workerSheetOpen = document.getElementById('wkSheetOl')?.classList.contains('on') && currentWorkerDetailId;
        if (curTab === 'workers' || workerSheetOpen) loadWorkers();
      }, 15000);

      async function loadOverview() {
        try {
          const data = await api('stats');
          if (!data.success) return;
          const s = data.stats;

          document.getElementById('st-users').textContent = s.total_users;
          document.getElementById('st-bookings').textContent = s.total_bookings;
          document.getElementById('st-revenue').textContent = '₱' + (s.total_revenue / 1000).toFixed(1) + 'k';
          document.getElementById('st-workers').textContent = s.active_workers;
          document.getElementById('revTotal').textContent = php(s.total_revenue);

          // Revenue chart
          const chart = document.getElementById('revChart');
          const revRows = s.revenue_chart || [];
          if (revRows.length) {
            const max = Math.max(...revRows.map(r => parseFloat(r.rev)), 1);
            chart.innerHTML = revRows.map(r => {
              const h = Math.max(4, Math.round((parseFloat(r.rev) / max) * 60));
              return `<div class="rev-bar-item">
          <div class="rev-bar-fill" style="height:${h}px;" title="${php(r.rev)}"></div>
          <div class="rev-bar-lbl">${r.mo}</div>
        </div>`;
            }).join('');
          } else {
            chart.innerHTML = '<div style="font-size:12px;color:var(--txt-muted);text-align:center;width:100%;padding:20px 0;">No revenue data yet</div>';
          }

          // Donut
          const bd = s.breakdown || {};
          const colors = { pending: '#f59e0b', progress: '#3b82f6', done: '#F5A623', cancelled: '#9ca3af' };
          const total = Object.values(bd).reduce((a, b) => a + b, 0) || 1;
          let offset = 0; const circ = 2 * Math.PI * 30;
          const svg = document.getElementById('donutSvg');
          const legend = document.getElementById('donutLegend');

          svg.innerHTML = '<circle cx="40" cy="40" r="30" fill="none" stroke="var(--border-col)" stroke-width="12"/>';
          legend.innerHTML = '';
          Object.entries(bd).forEach(([st, cnt]) => {
            const pct = cnt / total; const dash = pct * circ;
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', '40'); circle.setAttribute('cy', '40'); circle.setAttribute('r', '30');
            circle.setAttribute('fill', 'none'); circle.setAttribute('stroke', colors[st] || '#e5e7eb');
            circle.setAttribute('stroke-width', '12');
            circle.setAttribute('stroke-dasharray', `${dash} ${circ}`);
            circle.setAttribute('stroke-dashoffset', `${-offset}`);
            svg.appendChild(circle);
            offset += dash;
            legend.innerHTML += `<div class="legend-item"><div class="legend-dot" style="background:${colors[st] || '#e5e7eb'}"></div>${st}: <strong>${cnt}</strong></div>`;
          });

          // Recent bookings
          const rb = document.getElementById('recentBookings');
          const recent = s.recent_bookings || [];
          if (!recent.length) { rb.innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings yet</p></div>'; return; }
          rb.innerHTML = recent.map(b => `
      <div class="list-item" onclick="openBkDetail(${JSON.stringify(b).replace(/"/g, '&quot;')})">
        <div class="li-av" style="font-size:13px;">${(b.service || '?')[0]}</div>
        <div class="li-body"><div class="li-name">${b.service}</div><div class="li-sub">${b.user_name || '–'} · ${b.date}</div></div>
        <div class="li-right">${statusPill(b.status)}<span style="font-size:12px;font-weight:700;color:var(--teal);">${php(b.price)}</span></div>
      </div>`).join('');
        } catch (e) { console.error(e); }
      }

      let bkFilter = 'all';
      const PAGE_SIZE = 6;
      let bkPage = 1;
      let wkPage = 1;
      let usPage = 1;
      let lastBkQuery = '';
      let lastWkQuery = '';
      let lastUsQuery = '';
      let _currentBk = null;   // currently viewed booking
      let _allWorkers = [];     // worker cache for picker
      let workerUiState = {};   // local pause/resume state (UI only)

      function buildPaginationMarkup(currentPage, totalPages, prevFn, nextFn) {
        if (totalPages <= 1) return '';
        return `
          <div class="pg-wrap">
            <button class="pg-btn" onclick="${prevFn}()" ${currentPage <= 1 ? 'disabled' : ''} aria-label="Previous page">
              <i class="bi bi-chevron-left"></i>
            </button>
            <div class="pg-info">Page <span class="pg-info-badge">${currentPage} / ${totalPages}</span></div>
            <button class="pg-btn pg-next" onclick="${nextFn}()" ${currentPage >= totalPages ? 'disabled' : ''}>
              Next <i class="bi bi-chevron-right"></i>
            </button>
          </div>`;
      }

      function prevBkPage() { if (bkPage > 1) { bkPage -= 1; loadBookings(); } }
      function nextBkPage() { bkPage += 1; loadBookings(); }
      function prevWkPage() { if (wkPage > 1) { wkPage -= 1; loadWorkers(); } }
      function nextWkPage() { wkPage += 1; loadWorkers(); }
      function prevUsPage() { if (usPage > 1) { usPage -= 1; loadUsers(); } }
      function nextUsPage() { usPage += 1; loadUsers(); }

      function setBkFilter(el, f) {
        document.querySelectorAll('.stab').forEach(e => e.classList.remove('on'));
        el.classList.add('on'); bkFilter = f; bkPage = 1; loadBookings();
      }

      function toggleBkFilters() {
        const p = document.getElementById('bkFiltersPanel');
        p.style.display = p.style.display === 'none' ? 'block' : 'none';
        if (p.style.display === 'block') {
          populateWorkerFilterDropdown();
          populateServiceFilterDropdown();
        }
      }

      let _allServices = [];
      async function populateServiceFilterDropdown() {
        if (_allServices.length === 0) {
          const d = await api('services', 'list');
          _allServices = d.services || [];
        }
        const sel = document.getElementById('bkServiceFilter');
        const cur = sel.value;
        sel.innerHTML = '<option value="">All Services</option>' +
          _allServices.map(s => `<option value="${s.name}" ${s.name === cur ? 'selected' : ''}>${s.name}</option>`).join('');
      }

      async function populateWorkerFilterDropdown() {
        if (_allWorkers.length === 0) {
          const d = await api('workers', 'list');
          _allWorkers = d.workers || [];
        }
        const sel = document.getElementById('bkWorkerFilter');
        const cur = sel.value;
        sel.innerHTML = '<option value="">All Workers</option>' +
          _allWorkers.map(w => `<option value="${w.id}" ${w.id == cur ? 'selected' : ''}>${w.name} (${w.specialty})</option>`).join('');
      }

      function resetBkFilters() {
        document.getElementById('bkDateFrom').value = '';
        document.getElementById('bkDateTo').value = '';
        document.getElementById('bkServiceFilter').value = '';
        document.getElementById('bkWorkerFilter').value = '';
        bkPage = 1;
        loadBookings();
      }

      async function loadBookings() {
        const search = (document.getElementById('bkSearch') || {}).value || '';
        const dateFrom = (document.getElementById('bkDateFrom') || {}).value || '';
        const dateTo = (document.getElementById('bkDateTo') || {}).value || '';
        const service = (document.getElementById('bkServiceFilter') || {}).value || '';
        const workerId = (document.getElementById('bkWorkerFilter') || {}).value || '';

        let extra = `&status=${bkFilter}&search=${encodeURIComponent(search)}`;
        if (dateFrom) extra += `&date_from=${dateFrom}`;
        if (dateTo) extra += `&date_to=${dateTo}`;
        if (service) extra += `&service=${encodeURIComponent(service)}`;
        if (workerId) extra += `&worker_id=${workerId}`;

        document.getElementById('bkList').innerHTML = '<div class="empty-state"><p>Loading...</p></div>';
        try {
          const data = await api('bookings', 'list', null, extra);
          if (!data.success) { document.getElementById('bkList').innerHTML = `<div class="empty-state"><p>${data.message}</p></div>`; document.getElementById('bkPagination').innerHTML = ''; return; }
          const bks = data.bookings || [];
          const bkQuery = `${bkFilter}|${search}|${dateFrom}|${dateTo}|${service}|${workerId}`;
          if (bkQuery !== lastBkQuery) {
            bkPage = 1;
            lastBkQuery = bkQuery;
          }
          if (!bks.length) {
            document.getElementById('bkList').innerHTML = '<div class="empty-state"><i class="bi bi-calendar-x"></i><p>No bookings found.</p></div>';
            document.getElementById('bkPagination').innerHTML = '';
            return;
          }
          const bkTotalPages = Math.max(1, Math.ceil(bks.length / PAGE_SIZE));
          bkPage = Math.min(Math.max(1, bkPage), bkTotalPages);
          const bkStart = (bkPage - 1) * PAGE_SIZE;
          const bkPageItems = bks.slice(bkStart, bkStart + PAGE_SIZE);

          document.getElementById('bkList').innerHTML = bkPageItems.map(b => `
      <div class="bk-card" onclick='openBkDetail(${JSON.stringify(b).replace(/'/g, "&#39;")})'>
        <div style="display:flex;align-items:flex-start;gap:11px;">
          <div style="width:42px;height:42px;border-radius:12px;background:var(--teal-mid);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
            ${svcEmoji(b.service)}
          </div>
          <div style="flex:1;min-width:0;">
            <div style="font-size:14px;font-weight:700;color:var(--txt-primary);">${b.service}</div>
            <div style="font-size:11px;color:var(--txt-muted);margin-top:2px;">${b.user_name || '–'} · ${b.date || ''} ${b.time_slot || ''}</div>
            <div style="font-size:11px;color:var(--txt-muted);">${b.address || ''}</div>
            ${b.technician_name ? `<div style="font-size:10px;color:var(--teal);font-weight:700;margin-top:2px;">👷 ${b.technician_name}</div>` : ''}
          </div>
          <div style="text-align:right;flex-shrink:0;">
            ${statusPill(b.status)}
            <div class="bk-price" style="margin-top:4px;">${php(b.price)}</div>
          </div>
        </div>
      </div>`).join('');
          document.getElementById('bkPagination').innerHTML = buildPaginationMarkup(bkPage, bkTotalPages, 'prevBkPage', 'nextBkPage');
        } catch (e) { document.getElementById('bkList').innerHTML = '<div class="empty-state"><p>Error loading bookings.</p></div>'; document.getElementById('bkPagination').innerHTML = ''; }
      }

      function openBkDetail(b) {
        if (typeof b === 'string') b = JSON.parse(b);
        _currentBk = b;
        document.getElementById('bkDetailStatus').className = {
          pending: 'badge-amber', progress: 'badge-blue', done: 'badge-green', cancelled: 'badge-gray'
        }[b.status] || 'badge-gray';
        document.getElementById('bkDetailStatus').textContent = b.status;

        const workerSection = b.technician_name ? `
        <div style="background:var(--teal-bg);border-radius:14px;padding:14px 16px;margin:14px 0;border:1.5px solid var(--teal-mid);">
          <div style="font-size:11px;font-weight:700;color:var(--teal);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">👷 Assigned Worker</div>
          <div style="display:flex;align-items:center;gap:12px;">
            <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(b.technician_name)}&background=FDECC8&color=F5A623&size=80" style="width:46px;height:46px;border-radius:50%;" alt="">
            <div style="flex:1;">
              <div style="font-size:14px;font-weight:800;color:var(--txt-primary);">${b.technician_name}</div>
              <div style="font-size:11px;color:var(--txt-muted);">${b.tech_specialty || b.technician_name} · ${b.tech_phone || '–'}</div>
              <div style="font-size:11px;color:#f59e0b;">⭐ ${parseFloat(b.tech_rating || 0).toFixed(1)}</div>
            </div>
          </div>
        </div>` : `<div style="background:#fff8f0;border:1.5px dashed #f5a623;border-radius:14px;padding:14px 16px;margin:14px 0;text-align:center;">
          <div style="font-size:12px;color:var(--txt-muted);">No worker assigned yet</div>
        </div>`;

        const notesHtml = b.notes ? `
        <div style="margin-top:2px;">
          <div class="fl" style="margin-bottom:4px;">Notes</div>
          <div style="background:var(--bg-card);border:1.5px solid var(--border-col);border-radius:10px;padding:10px 12px;font-size:12px;color:var(--txt-primary);line-height:1.6;">${b.notes}</div>
        </div>` : '';

        const body = document.getElementById('bkDetailBody');
        body.innerHTML = `
        <div style="padding:0 18px 90px;">

          <div style="background:var(--bg-card);border:1.5px solid var(--border-col);border-radius:16px;overflow:hidden;margin-bottom:14px;">
            <div style="padding:4px 0;">
              <div class="detail-row"><span class="detail-lbl">Booking ID</span><span class="detail-val" style="font-weight:800;color:var(--teal);">#${b.id}</span></div>
              <div class="detail-row"><span class="detail-lbl">Service</span><span class="detail-val">${svcEmoji(b.service)} ${b.service}</span></div>
              <div class="detail-row"><span class="detail-lbl">Date & Time</span><span class="detail-val">${b.date || '–'} ${b.time_slot || ''}</span></div>
              <div class="detail-row"><span class="detail-lbl">Price</span><span class="detail-val" style="color:var(--teal);font-size:15px;font-weight:800;">${php(b.price)}</span></div>
            </div>
          </div>

          <div style="font-size:11px;font-weight:700;color:var(--txt-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">👤 Customer Info</div>
          <div style="background:var(--bg-card);border:1.5px solid var(--border-col);border-radius:16px;overflow:hidden;margin-bottom:14px;">
            <div style="padding:4px 0;">
              <div class="detail-row"><span class="detail-lbl">Name</span><span class="detail-val">${b.user_name || '–'}</span></div>
              <div class="detail-row"><span class="detail-lbl">Email</span><span class="detail-val" style="font-size:11px;word-break:break-all;">${b.user_email || '–'}</span></div>
              <div class="detail-row"><span class="detail-lbl">Phone</span><span class="detail-val">${b.user_phone || '–'}</span></div>
              <div class="detail-row"><span class="detail-lbl">Address</span><span class="detail-val">${b.address || '–'}</span></div>
            </div>
          </div>

          ${workerSection}
          ${notesHtml}

          <div style="margin-top:16px;">
            <div class="fl" style="margin-bottom:8px;">Update Status</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
              ${['pending', 'progress', 'done', 'cancelled'].map(s => `
              <button onclick="updateBkStatus(${b.id},'${s}')" style="padding:7px 13px;border-radius:20px;border:2px solid;cursor:pointer;font-size:11px;font-weight:700;
                background:${b.status === s ? 'var(--teal)' : 'transparent'};
                color:${b.status === s ? '#fff' : 'var(--txt-muted)'};
                border-color:${b.status === s ? 'var(--teal)' : 'var(--border-col)'};">${s}</button>`).join('')}
            </div>
          </div>

          <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">
            <button class="btn-p" onclick="openWorkerPicker(${b.id},'${b.technician_name ? 'reassign' : 'assign'}')">
              <i class="bi bi-person-check-fill"></i> ${b.technician_name ? 'Reassign Worker' : 'Assign Worker'}
            </button>
            ${b.status !== 'cancelled' && b.status !== 'done' ? `
            <button onclick="cancelBk(${b.id})" style="padding:11px;border-radius:14px;border:2px solid #f59e0b;background:#fff8f0;color:#d97706;font-size:13px;font-weight:700;cursor:pointer;transition:all .2s;">
              <i class="bi bi-x-circle-fill"></i> Cancel Booking
            </button>` : ''}
            <button class="btn-danger" onclick="deleteBk(${b.id})"><i class="bi bi-trash-fill"></i> Delete Booking</button>
          </div>
        </div>`;
        openSheet('bkDetailOl');
      }

      async function updateBkStatus(id, status) {
        try {
          const data = await api('bookings', 'update_status', fd({ id, status }));
          if (data.success) { toast('Status updated to ' + status); closeSheet('bkDetailOl'); loadBookings(); }
          else toast(data.message || 'Failed', 'e');
        } catch (e) { toast('Error', 'e'); }
      }

      async function cancelBk(id) {
        if (!confirm2('Cancel this booking?')) return;
        const data = await api('bookings', 'cancel', fd({ id }));
        if (data.success) { toast('Booking cancelled'); closeSheet('bkDetailOl'); loadBookings(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function deleteBk(id) {
        if (!confirm2('Delete this booking permanently?')) return;
        const data = await api('bookings', 'delete', fd({ id }));
        if (data.success) { toast('Booking deleted'); closeSheet('bkDetailOl'); loadBookings(); }
        else toast(data.message || 'Failed', 'e');
      }

      // ── Worker Picker ──────────────────────────────────────────────────────────
      let _pickerBookingId = null;

      async function openWorkerPicker(bookingId, mode) {
        _pickerBookingId = bookingId;
        document.getElementById('workerPickerTtl').textContent = mode === 'reassign' ? 'Reassign Worker' : 'Assign Worker';
        document.getElementById('workerPickerSearch').value = '';
        if (_allWorkers.length === 0) {
          const d = await api('workers', 'list');
          _allWorkers = d.workers || [];
        }
        renderWorkerPickerList(_allWorkers);
        closeSheet('bkDetailOl');
        openSheet('workerPickerOl');
      }

      function renderWorkerPickerList(workers) {
        const el = document.getElementById('workerPickerList');
        if (!workers.length) { el.innerHTML = '<div class="empty-state"><p>No workers found.</p></div>'; return; }
        el.innerHTML = workers.map(w => `
        <div class="list-item" onclick="pickWorker(${w.id},'${w.name.replace(/'/g, "\\'")}')"
             style="cursor:pointer;padding:12px 18px;border-bottom:1px solid var(--border-col);transition:background .15s;">
          <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(w.name)}&background=FDECC8&color=F5A623&size=80" style="width:42px;height:42px;border-radius:50%;" alt="">
          <div class="li-body">
            <div class="li-name">${w.name}</div>
            <div class="li-sub">${w.specialty} · ${w.phone || 'No phone'}</div>
            <div style="display:flex;gap:5px;margin-top:3px;">${workerStateBadge('availability', w.availability)} ${workerStateBadge('status', getWorkerVerificationBadgeState(w))}</div>
          </div>
          <div class="li-right" style="text-align:right;">
            <div style="font-size:11px;color:var(--txt-muted);">⭐ ${parseFloat(w.rating || 0).toFixed(1)}</div>
            <div style="font-size:10px;color:var(--txt-muted);">${w.jobs_done || 0} jobs</div>
          </div>
        </div>`).join('');
      }

      function filterWorkerPicker() {
        const q = document.getElementById('workerPickerSearch').value.toLowerCase();
        const filtered = _allWorkers.filter(w =>
          w.name.toLowerCase().includes(q) || (w.specialty || '').toLowerCase().includes(q));
        renderWorkerPickerList(filtered);
      }

      async function pickWorker(workerId, workerName) {
        if (!_pickerBookingId) return;
        const data = await api('bookings', 'assign_worker', fd({ booking_id: _pickerBookingId, worker_id: workerId }));
        if (data.success) {
          toast(`${workerName} assigned successfully`);
          closeSheet('workerPickerOl');
          loadBookings();
        } else toast(data.message || 'Failed', 'e');
      }

      function getWorkerDisplayStatus(worker) {
        if (isWorkerUiPaused(worker?.id)) return 'paused';
        const verificationStatus = String(worker?.verification_status || '').toLowerCase().trim();
        if (Number(worker?.is_verified) === 1 || verificationStatus === 'approved' || verificationStatus === 'verified') return 'verified';
        if (verificationStatus === 'rejected') return 'rejected';
        return 'pending';
      }

      function getWorkerDisplayAvailability(worker) {
        const raw = String(worker?.availability || '').toLowerCase().trim();
        if (raw === 'online' || raw === 'available') return 'available';
        if (raw === 'busy') return 'on_job';
        return 'unavailable';
      }

      function getWorkerServiceKey(worker) {
        const svc = String(worker?.specialty || '').toLowerCase().trim();
        if (svc.includes('clean')) return 'cleaner';
        if (svc.includes('helper')) return 'helper';
        if (svc.includes('laundry')) return 'laundry';
        if (svc.includes('plumb')) return 'plumber';
        if (svc.includes('carpent')) return 'carpenter';
        if (svc.includes('appliance') || svc.includes('technician')) return 'appliance technician';
        return svc;
      }

      function updateWorkerFilterHighlighting() {
        ['wkStatusFilter', 'wkAvailabilityFilter', 'wkServiceFilter'].forEach(id => {
          const el = document.getElementById(id);
          if (!el) return;
          el.classList.toggle('on', String(el.value || 'all') !== 'all');
        });
      }

      function updateWorkerFilterNote(statusFilter, availabilityFilter, serviceFilter, count) {
        const noteEl = document.getElementById('wkFilterNote');
        if (!noteEl) return;
        const parts = [];
        if (statusFilter !== 'all') parts.push(`Status: ${statusFilter.replace('_', ' ')}`);
        if (availabilityFilter !== 'all') parts.push(`Availability: ${availabilityFilter.replace('_', ' ')}`);
        if (serviceFilter !== 'all') parts.push(`Service: ${serviceFilter}`);
        noteEl.textContent = parts.length
          ? `Showing ${count} worker(s) · ${parts.join(' · ')}`
          : `Showing: All workers (${count})`;
      }

      async function loadWorkers() {
        const search = (document.getElementById('wkSearch') || {}).value || '';
        const statusFilter = (document.getElementById('wkStatusFilter') || {}).value || 'all';
        const availabilityFilter = (document.getElementById('wkAvailabilityFilter') || {}).value || 'all';
        const serviceFilter = (document.getElementById('wkServiceFilter') || {}).value || 'all';
        document.getElementById('wkList').innerHTML = '<div class="empty-state"><p>Loading...</p></div>';
        try {
          const data = await api('workers', 'list');
          const allWorkers = (data.workers || []).slice().sort((a, b) => {
            const aName = String(a.name || '').toLowerCase();
            const bName = String(b.name || '').toLowerCase();
            return aName.localeCompare(bName);
          });
          const workers = allWorkers.filter(w => {
            const q = String(search || '').toLowerCase().trim();
            const matchesSearch = !q || String(w.name || '').toLowerCase().includes(q) || String(w.specialty || '').toLowerCase().includes(q);
            const matchesStatus = statusFilter === 'all' || getWorkerDisplayStatus(w) === statusFilter;
            const matchesAvailability = availabilityFilter === 'all' || getWorkerDisplayAvailability(w) === availabilityFilter;
            const matchesService = serviceFilter === 'all' || getWorkerServiceKey(w) === serviceFilter;
            return matchesSearch && matchesStatus && matchesAvailability && matchesService;
          });

          updateWorkerFilterHighlighting();
          updateWorkerFilterNote(statusFilter, availabilityFilter, serviceFilter, workers.length);

          const wkQuery = `${search}|${statusFilter}|${availabilityFilter}|${serviceFilter}`;
          if (wkQuery !== lastWkQuery) {
            wkPage = 1;
            lastWkQuery = wkQuery;
          }
          if (!workers.length) {
            document.getElementById('wkList').innerHTML = '<div class="empty-state"><i class="bi bi-person-x"></i><p>No workers found.</p></div>';
            document.getElementById('wkPagination').innerHTML = '';
            return;
          }
          const wkTotalPages = Math.max(1, Math.ceil(workers.length / PAGE_SIZE));
          wkPage = Math.min(Math.max(1, wkPage), wkTotalPages);
          const wkStart = (wkPage - 1) * PAGE_SIZE;
          const wkPageItems = workers.slice(wkStart, wkStart + PAGE_SIZE);

          document.getElementById('wkList').innerHTML = wkPageItems.map(w => {
            const isLow = w.rating > 0 && w.rating < 3.0;
            const starHtml = isLow ? `<span style="color:#ef4444;font-weight:800;">⭐ ${parseFloat(w.rating).toFixed(1)}</span>` : `⭐ ${parseFloat(w.rating || 0).toFixed(1)}`;
            const isPaused = isWorkerUiPaused(w.id);
            const pauseBtnClass = isPaused ? 'resume' : 'pause';
            const pauseIcon = isPaused ? 'bi-play-fill' : 'bi-pause-fill';
            const pauseTooltip = isPaused ? 'Reactivate Worker' : 'Suspend Worker';
            return `
      <div class="list-item" onclick='openWorkerSheet(${JSON.stringify(w).replace(/'/g, "&#39;")})' style="cursor:pointer;">
        <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(w.name)}&background=FDECC8&color=F5A623&size=80" style="width:44px;height:44px;border-radius:50%;object-fit:cover;" alt="">
        <div class="li-body">
          <div class="li-name">${w.name} ${w.is_verified == 1 ? '<i class="bi bi-patch-check-fill" style="color:#10b981;font-size:12px;"></i>' : ''}</div>
          <div class="li-sub">${w.specialty} · ${w.phone || 'No phone'}</div>
          <div style="display:flex;gap:5px;margin-top:4px;">${workerStateBadge('availability', w.availability)} ${workerStateBadge('status', getWorkerVerificationBadgeState(w))}</div>
        </div>
        <div class="li-right">
          <div class="act-btns">
            <button class="act-btn edit" onclick='event.stopPropagation();openWorkerSheet(${JSON.stringify(w).replace(/'/g, "&#39;")})' title="View details"><i class="bi bi-eye-fill"></i></button>
            <button class="act-btn ${pauseBtnClass}" onclick="event.stopPropagation();toggleWorkerPause(${w.id})" title="${pauseTooltip}"><i class="bi ${pauseIcon}"></i></button>
            <button class="act-btn del" onclick="event.stopPropagation();deleteWorkerById(${w.id})"><i class="bi bi-trash-fill"></i></button>
          </div>
          <div style="font-size:11px;color:var(--txt-muted);margin-top:4px;">${starHtml} · ${w.jobs_done || 0} jobs</div>
        </div>
      </div>`}).join('');
          if (currentWorkerDetailId) {
            const current = allWorkers.find(w => w.id == currentWorkerDetailId);
            if (current) fillWorkerSheet(current);
          }
          document.getElementById('wkPagination').innerHTML = buildPaginationMarkup(wkPage, wkTotalPages, 'prevWkPage', 'nextWkPage');
        } catch (e) { document.getElementById('wkList').innerHTML = '<div class="empty-state"><p>Error loading workers.</p></div>'; document.getElementById('wkPagination').innerHTML = ''; }
      }

      let currentWorkerDetailId = null;

      function fillWorkerSheet(w) {
        if (!w) return;
        currentWorkerDetailId = w.id;
        document.getElementById('wkSheetTtl').textContent = 'Worker Details';
        document.getElementById('wkAvatar').textContent = (w.name || '?')[0].toUpperCase();
        document.getElementById('wkName').textContent = w.name || '–';
        document.getElementById('wkSpecialty').textContent = w.specialty || '–';
        document.getElementById('wkPhone').textContent = w.phone || '–';
        document.getElementById('wkAvail').innerHTML = workerStateBadge('availability', w.availability || 'offline');
        document.getElementById('wkStatus').innerHTML = workerStateBadge('status', getWorkerVerificationBadgeState(w));
        document.getElementById('wkRating').textContent = parseFloat(w.rating || 0).toFixed(1);
        document.getElementById('wkJobs').textContent = w.jobs_done || 0;
        let docHtml = '';
        if (w.valid_id) docHtml += `<div><a href="${w.valid_id}" target="_blank" style="color:var(--teal);font-weight:700;text-decoration:none;">View Valid ID</a></div>`;
        if (w.selfie_verification) docHtml += `<div><a href="${w.selfie_verification}" target="_blank" style="color:var(--teal);font-weight:700;text-decoration:none;">View Selfie Verification</a></div>`;
        if (w.proof_of_address) docHtml += `<div><a href="${w.proof_of_address}" target="_blank" style="color:var(--teal);font-weight:700;text-decoration:none;">View Proof of Address</a></div>`;
        if (w.barangay_clearance) docHtml += `<div><a href="${w.barangay_clearance}" target="_blank" style="color:var(--teal);font-weight:700;text-decoration:none;">View Barangay Clearance</a></div>`;
        if (w['tools_&_kits']) docHtml += `<div><a href="${w['tools_&_kits']}" target="_blank" style="color:var(--teal);font-weight:700;text-decoration:none;">View Tools & Kits</a></div>`;
        if (!docHtml) docHtml = '<span style="color:var(--txt-muted);">No documents uploaded.</span>';
        document.getElementById('wkVdocs').innerHTML = docHtml;
        
        // Show action buttons for workers that have docs and are still awaiting review
        const hasDocuments = !!(w.valid_id || w.selfie_verification || w.proof_of_address || w.barangay_clearance || w['tools_&_kits']);
        const verificationStatus = String(w.verification_status || '').toLowerCase().trim();
        const reviewableStatuses = ['pending', 'pending_review', 'submitted', 'partial', 'approval_ready', 'not_verified', 'not_submitted'];
        const isReviewable = reviewableStatuses.includes(verificationStatus);
        const isApproved = verificationStatus === 'approved' || verificationStatus === 'verified' || Number(w.is_verified) === 1;
        const actionBtns = document.getElementById('wkActionButtons');
        if (actionBtns) {
          actionBtns.style.display = (hasDocuments && isReviewable && !isApproved) ? 'flex' : 'none';
        }
      }

      function openWorkerSheet(w) {
        if (!w) return;
        fillWorkerSheet(w);
        openSheet('wkSheetOl');
      }

      async function deleteWorkerById(id) {
        if (!confirm2('Delete this worker?')) return;
        const data = await api('workers', 'delete', fd({ id }));
        if (data.success) { toast('Worker deleted'); loadWorkers(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function approveWorkerVerification() {
        if (!currentWorkerDetailId) return;
        const approved = await openWorkerVerificationModal('approve');
        if (!approved) return;
        
        const formData = new FormData();
        formData.append('provider_id', currentWorkerDetailId);
        
        try {
          const response = await fetch('../api/admin_documents_api.php?action=approve_provider', {
            method: 'POST',
            body: formData
          });
          const data = await response.json();
          if (data.success) {
            toast('Worker approved successfully');
            closeSheet('wkSheetOl');
            loadWorkers();
          } else {
            toast(data.message || 'Failed to approve worker', 'e');
          }
        } catch (e) {
          toast('Error: ' + e.message, 'e');
        }
      }

      async function rejectWorkerVerification() {
        if (!currentWorkerDetailId) return;
        const reason = await openWorkerVerificationModal('reject');
        if (!reason) return;
        
        const formData = new FormData();
        formData.append('provider_id', currentWorkerDetailId);
        formData.append('reason', reason);
        
        try {
          const response = await fetch('../api/admin_documents_api.php?action=reject_provider', {
            method: 'POST',
            body: formData
          });
          const data = await response.json();
          if (data.success) {
            toast('Worker verification rejected');
            closeSheet('wkSheetOl');
            loadWorkers();
          } else {
            toast(data.message || 'Failed to reject worker', 'e');
          }
        } catch (e) {
          toast('Error: ' + e.message, 'e');
        }
      }

      async function loadUsers() {
        const search = (document.getElementById('usSearch') || {}).value || '';
        const statusFilter = (document.getElementById('usStatusFilter') || {}).value || 'all';
        document.getElementById('usList').innerHTML = '<div class="empty-state"><p>Loading...</p></div>';
        try {
          const data = await api('users', 'list');
          const allUsers = (data.users || []).slice().sort((a, b) => String(a.name || '').localeCompare(String(b.name || ''), undefined, { sensitivity: 'base' }));
          const users = allUsers.filter(u => {
            const q = String(search || '').toLowerCase().trim();
            const matchesSearch = !q || String(u.name || '').toLowerCase().includes(q) || String(u.email || '').toLowerCase().includes(q);
            const userStatus = u.disabled ? 'disabled' : 'active';
            const matchesStatus = statusFilter === 'all' || statusFilter === userStatus;
            return matchesSearch && matchesStatus;
          });

          updateUserFilterHighlighting(statusFilter);
          updateUserFilterNote(statusFilter, users.length);

          const usQuery = `${search}|${statusFilter}`;
          if (usQuery !== lastUsQuery) {
            usPage = 1;
            lastUsQuery = usQuery;
          }
          if (!users.length) {
            document.getElementById('usList').innerHTML = '<div class="empty-state"><i class="bi bi-people"></i><p>No users found.</p></div>';
            document.getElementById('usPagination').innerHTML = '';
            return;
          }
          const usTotalPages = Math.max(1, Math.ceil(users.length / PAGE_SIZE));
          usPage = Math.min(Math.max(1, usPage), usTotalPages);
          const usStart = (usPage - 1) * PAGE_SIZE;
          const usPageItems = users.slice(usStart, usStart + PAGE_SIZE);

          document.getElementById('usList').innerHTML = usPageItems.map(u => `
      <div class="list-item" onclick='openUserDetail(${JSON.stringify(u)})'>
        <div class="user-av">${(u.name || '?')[0].toUpperCase()}</div>
        <div class="li-body">
          <div class="li-name" style="${u.disabled ? 'text-decoration:line-through;color:var(--txt-muted);' : ''}">${u.name}</div>
          <div class="li-sub">${u.email}</div>
          <div class="li-sub">${u.booking_count} bookings · ${u.done_count} done · ${Number(u.booking_count || 0) > 0 ? 'Engaged User' : 'New User'}</div>
        </div>
        <div class="li-right">
          ${u.disabled ? '<span class="badge-red">Disabled</span>' : '<span class="badge-green">Active</span>'}
          ${u.phone ? `<div style="font-size:11px;color:var(--txt-muted);">${u.phone}</div>` : ''}
        </div>
      </div>`).join('');
          document.getElementById('usPagination').innerHTML = buildPaginationMarkup(usPage, usTotalPages, 'prevUsPage', 'nextUsPage');
        } catch (e) { document.getElementById('usList').innerHTML = '<div class="empty-state"><p>Error.</p></div>'; document.getElementById('usPagination').innerHTML = ''; }
      }

      function updateUserFilterHighlighting(statusFilter) {
        const statusEl = document.getElementById('usStatusFilter');
        if (statusEl) statusEl.classList.toggle('on', statusFilter !== 'all');
      }

      function updateUserFilterNote(statusFilter, count) {
        const noteEl = document.getElementById('usFilterNote');
        if (!noteEl) return;
        const parts = [];
        if (statusFilter !== 'all') parts.push(`Status: ${statusFilter === 'disabled' ? 'Disabled / Suspended' : 'Active'}`);
        noteEl.textContent = parts.length
          ? `Showing ${count} user(s) · ${parts.join(' · ')}`
          : `Showing: All users (${count})`;
      }

      function openUserDetail(u) {
        if (typeof u === 'string') u = JSON.parse(u);
        document.getElementById('usDetailBody').innerHTML = `
    <div style="text-align:center;margin-bottom:16px;">
      <div class="user-av" style="width:60px;height:60px;font-size:22px;margin:0 auto 8px;">${(u.name || '?')[0].toUpperCase()}</div>
      <div style="font-size:16px;font-weight:800;color:var(--txt-primary);">${u.name}</div>
      <div style="font-size:12px;color:var(--txt-muted);">${u.email}</div>
    </div>
    <div class="detail-row"><span class="detail-lbl">Phone</span><span class="detail-val">${u.phone || '–'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Address</span><span class="detail-val">${u.address || '–'}</span></div>
    <div class="detail-row"><span class="detail-lbl">Total Bookings</span><span class="detail-val">${u.booking_count}</span></div>
    <div class="detail-row"><span class="detail-lbl">Completed</span><span class="detail-val">${u.done_count}</span></div>
    <div class="detail-row"><span class="detail-lbl">Account Status</span><span class="detail-val">${u.disabled ? '<span class="badge-red">Disabled</span>' : '<span class="badge-green">Active</span>'}</span></div>
    <div class="modal-btns" style="margin-top:16px;gap:14px;">
      <button class="btn-outline" onclick="toggleUserDisable(${u.id})">${u.disabled ? 'Enable Account' : 'Disable Account'}</button>
      <button class="btn-danger" onclick="deleteUser(${u.id})">Delete User</button>
    </div>`;
        openSheet('usDetailOl');
      }

      async function toggleUserDisable(id) {
        const data = await api('users', 'toggle_disable', fd({ id }));
        if (data.success) { toast(data.message); closeSheet('usDetailOl'); loadUsers(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function deleteUser(id) {
        if (!confirm2('Delete this user and all their data?')) return;
        const data = await api('users', 'delete', fd({ id }));
        if (data.success) { toast('User deleted'); closeSheet('usDetailOl'); loadUsers(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function loadMore() {
        loadServices();
      }

      async function loadServices() {
        const data = await api('services');
        const svcs = data.services || [];
        document.getElementById('svcList').innerHTML = svcs.length ? svcs.map(s => `
    <div class="svc-row">
      <div class="svc-ic-sm">${s.icon || '🔧'}</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--txt-primary);">${s.name}</div>
        <div style="font-size:11px;color:var(--txt-muted);">${php(s.hourly_rate)}/hr · ${php(s.flat_rate)} flat</div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div class="toggle-sw ${s.active ? 'on' : 'off'}" onclick="toggleSvc(${s.id},this)" title="${s.active ? 'Disable' : 'Enable'}"></div>
        <button class="act-btn edit" onclick='openSvcSheet(${JSON.stringify(s)})'><i class="bi bi-pencil-fill"></i></button>
      </div>
    </div>`).join('') : '<div class="empty-state"><i class="bi bi-grid-x"></i><p>No services yet.</p></div>';
      }

      function openSvcSheet(s) {
        document.getElementById('svcId').value = s ? s.id : '';
        document.getElementById('svcIcon').value = s ? (s.icon || '') : '';
        document.getElementById('svcName').value = s ? s.name : '';
        document.getElementById('svcDesc').value = s ? (s.description || '') : '';
        document.getElementById('svcHourly').value = s ? parseFloat(s.hourly_rate || 0) : 0;
        document.getElementById('svcFlat').value = s ? parseFloat(s.flat_rate || 0) : 0;
        document.getElementById('svcMinH').value = s ? (s.min_hours || 1) : 1;
        document.getElementById('svcPtype').value = s ? (s.pricing_type || 'both') : 'both';
        document.getElementById('svcSheetTtl').textContent = s ? 'Edit Service' : 'Add Service';
        document.getElementById('svcDelBtn').style.display = s ? 'block' : 'none';
        openSheet('svcSheetOl');
      }

      async function saveSvc() {
        const id = document.getElementById('svcId').value;
        const name = document.getElementById('svcName').value.trim();
        if (!name) { toast('Service name required', 'e'); return; }
        const body = fd({ id: id || '', name, icon: document.getElementById('svcIcon').value, description: document.getElementById('svcDesc').value, hourly_rate: document.getElementById('svcHourly').value, flat_rate: document.getElementById('svcFlat').value, min_hours: document.getElementById('svcMinH').value, pricing_type: document.getElementById('svcPtype').value, active: 1 });
        const data = await api('services', id ? 'edit' : 'add', body);
        if (data.success) { toast(id ? 'Service updated' : 'Service added'); closeSheet('svcSheetOl'); loadServices(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function deleteSvc() {
        const id = document.getElementById('svcId').value;
        if (!confirm2('Delete this service?')) return;
        const data = await api('services', 'delete', fd({ id }));
        if (data.success) { toast('Service deleted'); closeSheet('svcSheetOl'); loadServices(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function toggleSvc(id, el) {
        el.classList.toggle('on'); el.classList.toggle('off');
        const data = await api('services', 'toggle', fd({ id }));
        if (!data.success) { el.classList.toggle('on'); el.classList.toggle('off'); toast('Failed', 'e'); }
        else toast(data.message);
      }

      async function loadOffers() {
        const data = await api('offers');
        const offers = data.offers || [];
        document.getElementById('offerList').innerHTML = offers.length ? offers.map(o => `
    <div class="offer-list-item">
      <div class="offer-ic">🏷️</div>
      <div style="flex:1;min-width:0;">
        <div style="font-size:13px;font-weight:700;color:var(--txt-primary);">${o.title}</div>
        <div style="font-size:11px;color:var(--teal);font-weight:700;">${o.code}</div>
        <div style="font-size:11px;color:var(--txt-muted);">${o.discount_type === 'percent' ? o.discount_value + '%' : '₱' + o.discount_value} OFF · Used: ${o.used_count}/${o.max_uses || '∞'}</div>
        ${o.expires_at ? `<div style="font-size:10px;color:var(--txt-muted);">Exp: ${o.expires_at}</div>` : ''}
      </div>
      <div style="display:flex;align-items:center;gap:8px;">
        <div class="toggle-sw ${o.active ? 'on' : 'off'}" onclick="toggleOffer(${o.id},this)"></div>
        <button class="act-btn edit" onclick='openOfferSheet(${JSON.stringify(o)})'><i class="bi bi-pencil-fill"></i></button>
      </div>
    </div>`).join('') : '<div class="empty-state"><i class="bi bi-tag-x"></i><p>No offers yet.</p></div>';
      }

      function openOfferSheet(o) {
        document.getElementById('offerId').value = o ? o.id : '';
        document.getElementById('offerTitle').value = o ? o.title : '';
        document.getElementById('offerCode').value = o ? o.code : '';
        document.getElementById('offerType').value = o ? (o.discount_type || 'percent') : 'percent';
        document.getElementById('offerVal').value = o ? parseFloat(o.discount_value || 0) : 10;
        document.getElementById('offerMin').value = o ? parseFloat(o.min_booking_price || 0) : 0;
        document.getElementById('offerMaxUses').value = o ? (o.max_uses || 0) : 0;
        document.getElementById('offerExpires').value = o && o.expires_at ? o.expires_at.replace(' ', 'T').substring(0, 16) : '';
        document.getElementById('offerDesc').value = o ? (o.description || '') : '';
        document.getElementById('offerSheetTtl').textContent = o ? 'Edit Offer' : 'Add Offer';
        document.getElementById('offerDelBtn').style.display = o ? 'block' : 'none';
        openSheet('offerSheetOl');
      }

      async function saveOffer() {
        const id = document.getElementById('offerId').value;
        const title = document.getElementById('offerTitle').value.trim();
        const code = document.getElementById('offerCode').value.trim().toUpperCase();
        if (!title || !code) { toast('Title and code required', 'e'); return; }
        const exp = document.getElementById('offerExpires').value;
        const body = fd({ id: id || '', title, code, discount_type: document.getElementById('offerType').value, discount_value: document.getElementById('offerVal').value, min_booking_price: document.getElementById('offerMin').value, max_uses: document.getElementById('offerMaxUses').value, expires_at: exp ? exp.replace('T', ' ') : '', description: document.getElementById('offerDesc').value, active: 1 });
        const data = await api('offers', id ? 'edit' : 'add', body);
        if (data.success) { toast(id ? 'Offer updated' : 'Offer added'); closeSheet('offerSheetOl'); loadOffers(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function deleteOffer() {
        const id = document.getElementById('offerId').value;
        if (!confirm2('Delete this offer?')) return;
        const data = await api('offers', 'delete', fd({ id }));
        if (data.success) { toast('Offer deleted'); closeSheet('offerSheetOl'); loadOffers(); }
        else toast(data.message || 'Failed', 'e');
      }

      async function toggleOffer(id, el) {
        el.classList.toggle('on'); el.classList.toggle('off');
        const data = await api('offers', 'toggle', fd({ id }));
        if (!data.success) { el.classList.toggle('on'); el.classList.toggle('off'); toast('Failed', 'e'); }
        else toast(data.message);
      }
      function openLogoutConfirm() {
        document.getElementById('logoutConfirmOl').classList.add('on');
      }

      function closeLogoutConfirm() {
        document.getElementById('logoutConfirmOl').classList.remove('on');
      }

      async function confirmLogout() {
        closeLogoutConfirm();
        window.location.href = '../logout.php';
      }


      function svcEmoji(svc) {
        const m = { Cleaning: '🧹', Plumbing: '🔧', Electrical: '⚡', Painting: '🖌️', 'Appliance Repair': '🔩' };
        return m[svc] || '🏠';
      }

      // ── Admin Notifications ─────────────────────────────────────────────────
      let _adminNotifInterval = null;

      async function loadAdminNotifCount() {
        try {
          const data = await api('admin_notifications', 'count');
          if (data.success) {
            updateNotifBadge(data.unread_count);
          }
        } catch (e) { /* silent */ }
      }

      function updateNotifBadge(count) {
        const badge = document.getElementById('adminNotifBadge');
        const badgeSheet = document.getElementById('adminNotifBadgeSheet');
        const bellBtn = document.getElementById('adminBellBtn');
        const prevCount = parseInt(badge.dataset.count || '0');

        badge.dataset.count = count;
        badge.textContent = count > 99 ? '99+' : count;
        badge.style.display = count > 0 ? 'flex' : 'none';

        badgeSheet.dataset.count = count;
        badgeSheet.textContent = count > 99 ? '99+' : count;
        badgeSheet.style.display = count > 0 ? 'flex' : 'none';

        if (count > 0) {
          bellBtn.classList.add('bell-active');
          if (count > prevCount) {
            bellBtn.classList.add('bell-shake');
            setTimeout(() => bellBtn.classList.remove('bell-shake'), 700);
          }
        } else {
          bellBtn.classList.remove('bell-active');
        }
      }

      async function openAdminNotifSheet() {
        document.getElementById('adminNotifList').innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite;display:inline-block;"></i><p>Loading...</p></div>';
        openSheet('adminNotifSheetOl');
        await loadAdminNotifications();
      }

      async function loadAdminNotifications() {
        try {
          const data = await api('admin_notifications', 'list');
          if (!data.success) {
            document.getElementById('adminNotifList').innerHTML = '<div class="empty-state"><p>Error loading notifications.</p></div>';
            return;
          }
          updateNotifBadge(data.unread_count);
          renderAdminNotifications(data.notifications);
        } catch (e) {
          document.getElementById('adminNotifList').innerHTML = '<div class="empty-state"><p>Error loading notifications.</p></div>';
        }
      }

      function renderAdminNotifications(notifs) {
        const el = document.getElementById('adminNotifList');
        if (!notifs || !notifs.length) {
          el.innerHTML = '<div class="empty-state" style="padding:40px 20px;"><i class="bi bi-bell" style="font-size:36px;color:var(--txt-muted);opacity:.4;"></i><p style="margin-top:8px;">No notifications yet</p><p style="font-size:11px;color:var(--txt-muted);">You\'ll be notified when workers submit verification documents.</p></div>';
          return;
        }

        el.innerHTML = notifs.map(n => {
          const isVerif = n.type === 'verification';
          const iconClass = isVerif ? 'verif' : 'general';
          const icon = isVerif ? '<i class="bi bi-file-earmark-check-fill"></i>' : '<i class="bi bi-bell-fill"></i>';
          const timeAgo = getTimeAgo(n.created_at);
          const unreadClass = n.is_read == 0 ? 'unread' : '';

          // Provider info for verification notifications
          let providerTag = '';
          if (isVerif && n.provider_name) {
            const verified = n.is_verified == 1;
            providerTag = `<div style="margin-top:6px;display:flex;align-items:center;gap:6px;">
              <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;
                background:${verified ? '#d1fae5' : '#fef3c7'};color:${verified ? '#059669' : '#d97706'};">
                <i class="bi bi-${verified ? 'patch-check-fill' : 'clock-fill'}"></i>
                ${verified ? 'Verified' : 'Pending Review'}
              </span>
              <span style="font-size:10px;color:var(--txt-muted);">${n.service_category || ''}</span>
            </div>`;
          }

          return `
          <div class="admin-notif-item ${unreadClass}" data-id="${n.id}">
            <div class="admin-notif-ic ${iconClass}">${icon}</div>
            <div class="admin-notif-body">
              <div class="admin-notif-ttl">${n.title}</div>
              <div class="admin-notif-msg">${n.message}</div>
              ${providerTag}
              <div class="admin-notif-time"><i class="bi bi-clock"></i> ${timeAgo}</div>
            </div>
            <div class="admin-notif-actions">
              ${isVerif && n.reference_id ? `<button class="admin-notif-act-btn go" onclick="goToWorkerFromNotif(${n.reference_id}, ${n.id})" title="Review Worker"><i class="bi bi-arrow-right"></i></button>` : ''}
              <button class="admin-notif-act-btn del" onclick="deleteAdminNotif(${n.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>
            </div>
          </div>`;
        }).join('');
      }

      function getTimeAgo(dateStr) {
        const now = new Date();
        const d = new Date(dateStr.replace(' ', 'T'));
        const diffMs = now - d;
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'Just now';
        if (mins < 60) return mins + 'm ago';
        const hrs = Math.floor(mins / 60);
        if (hrs < 24) return hrs + 'h ago';
        const days = Math.floor(hrs / 24);
        if (days < 7) return days + 'd ago';
        return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
      }

      async function markAllAdminNotifsRead() {
        try {
          await api('admin_notifications', 'mark_read', fd({}));
          toast('All notifications marked as read');
          await loadAdminNotifications();
        } catch (e) { toast('Error', 'e'); }
      }

      async function deleteAdminNotif(id) {
        try {
          await api('admin_notifications', 'delete', fd({ id }));
          await loadAdminNotifications();
        } catch (e) { toast('Error', 'e'); }
      }

      async function goToWorkerFromNotif(providerId, notifId) {
        // Mark this notification as read
        api('admin_notifications', 'mark_read', fd({ id: notifId }));
        closeSheet('adminNotifSheetOl');

        // Switch to workers tab and find the worker
        showTab('workers');

        // Small delay to let the tab and workers load
        setTimeout(async () => {
          if (_allWorkers.length === 0) {
            const d = await api('workers', 'list');
            _allWorkers = d.workers || [];
          }
          const worker = _allWorkers.find(w => w.id == providerId);
          if (worker) {
            openWorkerSheet(worker);
          }
          loadAdminNotifCount();
        }, 600);
      }

      (function init() {
        setTimeout(() => {
          const ml = document.getElementById('ml');
          if (ml) { ml.style.opacity = '0'; setTimeout(() => ml.style.display = 'none', 200); }
        }, 800);
        loadOverview();
        loadAdminNotifCount();
        // Poll for new notifications every 30 seconds
        _adminNotifInterval = setInterval(loadAdminNotifCount, 30000);
      })();

      // ── Manage Reviews ───────────────────────────────────────────────────────
      async function openReviewSheet() {
        document.getElementById('reviewSheetBody').innerHTML = '<div class="empty-state"><p><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite; display:inline-block;"></i> Loading reviews...</p></div>';
        openSheet('reviewSheetOl');
        loadAdminReviews();
      }

      async function loadAdminReviews() {
        try {
          const res = await fetch('../api/admin_api.php?section=reviews&action=list');
          const data = await res.json();
          if (!data.success) {
            document.getElementById('reviewSheetBody').innerHTML = `<div class="empty-state"><p>${data.message}</p></div>`;
            return;
          }
          renderAdminReviews(data.reviews);
        } catch (e) {
          document.getElementById('reviewSheetBody').innerHTML = '<div class="empty-state"><p>Error loading reviews.</p></div>';
        }
      }

      function renderAdminReviews(reviews) {
        const body = document.getElementById('reviewSheetBody');
        if (!reviews || reviews.length === 0) {
          body.innerHTML = '<div class="empty-state"><i class="bi bi-star"></i><p>No reviews found.</p></div>';
          return;
        }

        // Summary stats
        const total = reviews.length;
        const avgRating = (reviews.reduce((s, r) => s + parseInt(r.rating), 0) / total).toFixed(1);
        const dist = [5,4,3,2,1].map(n => ({ n, cnt: reviews.filter(r => parseInt(r.rating) === n).length }));
        const maxDist = Math.max(...dist.map(d => d.cnt), 1);

        // Active filter state
        const activeFilter = body.dataset.filter || 'all';
        let filtered = reviews;
        if (activeFilter !== 'all') filtered = reviews.filter(r => parseInt(r.rating) === parseInt(activeFilter));

        const summaryHtml = `
          <div style="background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:18px;padding:18px;margin-bottom:16px;color:#fff;">
            <div style="display:flex;align-items:center;gap:16px;">
              <div style="text-align:center;flex-shrink:0;">
                <div style="font-size:40px;font-weight:900;font-family:'Poppins',sans-serif;line-height:1;">${avgRating}</div>
                <div style="font-size:16px;margin:4px 0 2px;">${'\u2605'.repeat(Math.round(avgRating))}${'\u2606'.repeat(5-Math.round(avgRating))}</div>
                <div style="font-size:11px;opacity:.8;">${total} review${total !== 1 ? 's' : ''}</div>
              </div>
              <div style="flex:1;">
                ${dist.map(d => `
                  <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                    <span style="font-size:11px;width:8px;text-align:right;opacity:.85;">${d.n}</span>
                    <i class="bi bi-star-fill" style="font-size:9px;opacity:.85;"></i>
                    <div style="flex:1;height:6px;background:rgba(255,255,255,.25);border-radius:3px;overflow:hidden;">
                      <div style="height:100%;width:${Math.round(d.cnt/maxDist*100)}%;background:#fff;border-radius:3px;transition:width .5s;"></div>
                    </div>
                    <span style="font-size:11px;width:18px;opacity:.85;">${d.cnt}</span>
                  </div>`).join('')}
              </div>
            </div>
          </div>`;

        // Filter tabs
        const tabsHtml = `
          <div style="display:flex;gap:6px;margin-bottom:14px;overflow-x:auto;scrollbar-width:none;padding-bottom:2px;">
            ${['all',5,4,3,2,1].map(f => {
              const isActive = activeFilter == f;
              const label = f === 'all' ? 'All' : '\u2605'.repeat(f);
              const cnt = f === 'all' ? total : reviews.filter(r => parseInt(r.rating) === parseInt(f)).length;
              return `<button onclick="filterReviews('${f}')" style="flex-shrink:0;padding:6px 12px;border-radius:20px;border:2px solid ${isActive ? '#4f46e5' : 'var(--border-col)'};background:${isActive ? '#4f46e5' : 'transparent'};color:${isActive ? '#fff' : 'var(--txt-muted)'};font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">${label} (${cnt})</button>`;
            }).join('')}
          </div>`;

        const cardsHtml = filtered.map(r => {
          const stars = parseInt(r.rating);
          const starsHtml = Array.from({length:5},(_,i) =>
            `<i class="bi bi-star${i < stars ? '-fill' : ''}" style="color:${i < stars ? '#f59e0b' : '#d1d5db'};font-size:13px;"></i>`
          ).join('');
          const initials = (r.user_name || '?').split(' ').map(w=>w[0]).join('').toUpperCase().slice(0,2);
          const dateStr = new Date(r.created_at.replace(' ','T')).toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'});
          return `
          <div style="background:var(--bg-card);border-radius:16px;padding:16px;margin-bottom:10px;border:1.5px solid var(--border-col);box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div style="display:flex;align-items:flex-start;gap:12px;">
              <div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#4f46e5,#7c3aed);display:flex;align-items:center;justify-content:center;color:#fff;font-size:14px;font-weight:800;flex-shrink:0;">${initials}</div>
              <div style="flex:1;min-width:0;">
                <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                  <div style="font-size:13px;font-weight:800;color:var(--txt-primary);">${r.user_name || 'Unknown'}</div>
                  <button onclick="deleteReview(${r.id})" style="background:#fee2e2;color:#dc2626;border:none;padding:5px 10px;border-radius:8px;font-size:11px;cursor:pointer;font-weight:700;display:flex;align-items:center;gap:4px;flex-shrink:0;"><i class="bi bi-trash3-fill"></i></button>
                </div>
                <div style="font-size:11px;color:var(--txt-muted);margin-top:1px;">For: <strong style="color:#4f46e5;">${r.provider_name || 'Unknown'}</strong></div>
                <div style="margin:6px 0;">${starsHtml}</div>
                ${r.comment ? `<div style="font-size:12px;color:var(--txt-primary);line-height:1.55;background:var(--teal-bg);border-radius:10px;padding:10px 12px;border-left:3px solid #4f46e5;font-style:italic;">&ldquo;${r.comment}&rdquo;</div>` : '<div style="font-size:11px;color:var(--txt-muted);font-style:italic;">No comment left</div>'}
                <div style="font-size:10px;color:var(--txt-muted);margin-top:7px;">${dateStr}</div>
              </div>
            </div>
          </div>`;
        }).join('');

        body.innerHTML = summaryHtml + tabsHtml + (filtered.length ? cardsHtml : '<div class="empty-state"><i class="bi bi-star"></i><p>No reviews match this filter.</p></div>');
      }

      function filterReviews(rating) {
        document.getElementById('reviewSheetBody').dataset.filter = rating;
        loadAdminReviews();
      }

      async function deleteReview(id) {
        if (!confirm2('Delete this review? This will recalculate the rating.')) return;
        const fd = new FormData();
        fd.append('section', 'reviews'); fd.append('action', 'delete'); fd.append('id', id);
        try {
          const res = await fetch('../api/admin_api.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (data.success) {
            toast('Review deleted successfully', 's');
            loadAdminReviews();
          } else {
            toast(data.message || 'Error deleting review', 'e');
          }
        } catch (e) {
          toast('Network error', 'e');
        }
      }

      // ── Analytics Charts ────────────────────────────────────────────────────────
      let _chartInstances = {};

      function destroyChart(name) {
        if (_chartInstances[name]) { _chartInstances[name].destroy(); delete _chartInstances[name]; }
      }

      function chartColors() {
        return {
          grid: 'rgba(0,0,0,.06)',
          text: '#9ca3af',
          bg: '#ffffff',
        };
      }

      const AN_PALETTE = ['#F5A623', '#3b82f6', '#10b981', '#f472b6', '#8b5cf6', '#f59e0b', '#ec4899', '#06b6d4', '#ef4444', '#84cc16'];

      async function loadAnalytics() {
        try {
          const data = await api('analytics');
          if (!data.success) return;
          const a = data.analytics;
          const c = chartColors();

          // ── Growth metrics ──
          const mc = a.monthly_comparison;
          document.getElementById('anThisMonth').textContent = mc.this_total;
          document.getElementById('anLastMonth').textContent = mc.last_total;
          const growthEl = document.getElementById('anGrowth');
          const metricEl = growthEl.closest('.an-metric');
          if (mc.growth_pct > 0) {
            metricEl.className = 'an-metric up';
            growthEl.className = 'an-metric-chg up';
            growthEl.innerHTML = `<i class="bi bi-arrow-up-short"></i> ${mc.growth_pct}% growth`;
          } else if (mc.growth_pct < 0) {
            metricEl.className = 'an-metric down';
            growthEl.className = 'an-metric-chg down';
            growthEl.innerHTML = `<i class="bi bi-arrow-down-short"></i> ${Math.abs(mc.growth_pct)}% decline`;
          } else {
            metricEl.className = 'an-metric flat';
            growthEl.className = 'an-metric-chg flat';
            growthEl.innerHTML = `<i class="bi bi-dash"></i> No change`;
          }

          // ── Booking Trend (line) ──
          destroyChart('trend');
          const trendCtx = document.getElementById('chartBookingTrend').getContext('2d');
          _chartInstances.trend = new Chart(trendCtx, {
            type: 'line',
            data: {
              labels: a.daily_bookings.map(d => d.day),
              datasets: [{
                label: 'Bookings',
                data: a.daily_bookings.map(d => d.count),
                borderColor: '#F5A623',
                backgroundColor: 'rgba(245,166,35,.12)',
                fill: true,
                tension: .4,
                borderWidth: 2.5,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: '#F5A623',
              }]
            },
            options: {
              responsive: true,
              plugins: { legend: { display: false } },
              scales: {
                x: { grid: { display: false }, ticks: { color: c.text, font: { size: 9, family: 'Nunito' }, maxTicksLimit: 7 } },
                y: { beginAtZero: true, grid: { color: c.grid }, ticks: { color: c.text, font: { size: 10 }, stepSize: 1 } }
              },
              interaction: { intersect: false, mode: 'index' }
            }
          });

          // ── Service Distribution (doughnut) ──
          destroyChart('svcDist');
          const distCtx = document.getElementById('chartServiceDist').getContext('2d');
          _chartInstances.svcDist = new Chart(distCtx, {
            type: 'doughnut',
            data: {
              labels: a.service_distribution.map(s => s.name),
              datasets: [{
                data: a.service_distribution.map(s => s.count),
                backgroundColor: AN_PALETTE.slice(0, a.service_distribution.length),
                borderWidth: 2,
                borderColor: c.bg,
                hoverOffset: 8,
              }]
            },
            options: {
              responsive: true,
              cutout: '62%',
              plugins: {
                legend: {
                  position: 'bottom',
                  labels: { color: c.text, font: { size: 11, family: 'Nunito', weight: '700' }, padding: 12, usePointStyle: true, pointStyleWidth: 10 }
                }
              }
            }
          });

          // ── Revenue (bar) ──
          destroyChart('revenue');
          const revCtx = document.getElementById('chartRevenue').getContext('2d');
          _chartInstances.revenue = new Chart(revCtx, {
            type: 'bar',
            data: {
              labels: a.weekly_revenue.map(w => w.week),
              datasets: [{
                label: 'Revenue ₱',
                data: a.weekly_revenue.map(w => w.revenue),
                backgroundColor: a.weekly_revenue.map((_, i) => {
                  const gradient = revCtx.createLinearGradient(0, 0, 0, 180);
                  gradient.addColorStop(0, 'rgba(245,166,35,.85)');
                  gradient.addColorStop(1, 'rgba(245,166,35,.25)');
                  return gradient;
                }),
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 32,
              }]
            },
            options: {
              responsive: true,
              plugins: { legend: { display: false } },
              scales: {
                x: { grid: { display: false }, ticks: { color: c.text, font: { size: 9, family: 'Nunito' } } },
                y: { beginAtZero: true, grid: { color: c.grid }, ticks: { color: c.text, font: { size: 10 }, callback: v => '₱' + (v >= 1000 ? (v / 1000).toFixed(1) + 'k' : v) } }
              }
            }
          });

          // ── Top Workers ──
          const twEl = document.getElementById('anTopWorkers');
          if (!a.top_workers.length) {
            twEl.innerHTML = '<div class="empty-state"><p>No worker data yet.</p></div>';
          } else {
            const maxJobs = Math.max(...a.top_workers.map(w => w.jobs), 1);
            twEl.innerHTML = a.top_workers.map((w, i) => {
              const rankClass = i === 0 ? 'gold' : i === 1 ? 'silver' : i === 2 ? 'bronze' : 'other';
              const pct = Math.max(5, Math.round((w.jobs / maxJobs) * 100));
              return `<div class="an-worker-row">
              <div class="an-worker-rank ${rankClass}">${i + 1}</div>
              <div class="an-worker-bar-wrap">
                <div class="an-worker-nm">${w.name}</div>
                <div class="an-worker-bar"><div class="an-worker-bar-fill" style="width:${pct}%"></div></div>
              </div>
              <div class="an-worker-jobs">${w.jobs}</div>
            </div>`;
            }).join('');
          }

        } catch (e) { console.error('Analytics error:', e); }
      }
    </script>
</body>

</html>
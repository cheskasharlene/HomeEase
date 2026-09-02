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
      background: var(--bg-card);
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

    /* Verification Documents Container */
    #wkVdocs {
      display: flex !important;
      flex-direction: column !important;
      align-items: stretch !important;
      gap: 10px !important;
      text-align: left !important;
      width: 100% !important;
    }

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

    /* Document View Button Container */
    #wkVdocs {
      display: flex;
      flex-direction: column;
      gap: 10px;
      width: 100%;
    }

    /* Document View Button */
    .doc-view-btn { 
      background: linear-gradient(135deg, #E8820C 0%, #F5A623 100%);
      color: #fff; 
      border: none; 
      padding: 10px 18px; 
      border-radius: 10px; 
      font-size: 13px; 
      font-weight: 700; 
      cursor: pointer; 
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      font-family: 'Nunito', sans-serif;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      white-space: nowrap;
      box-shadow: 0 2px 8px rgba(232, 130, 12, 0.2);
      position: relative;
      overflow: hidden;
    }

    .doc-view-btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: rgba(255, 255, 255, 0.15);
      transition: left 0.3s ease;
    }

    .doc-view-btn:hover { 
      background: linear-gradient(135deg, #D46C00 0%, #E67600 100%);
      transform: translateY(-3px);
      box-shadow: 0 6px 16px rgba(232, 130, 12, 0.35);
    }

    .doc-view-btn:hover::before {
      left: 100%;
    }

    .doc-view-btn:active {
      transform: translateY(-1px);
      box-shadow: 0 2px 6px rgba(232, 130, 12, 0.25);
    }

    .doc-view-btn i {
      font-size: 14px;
      opacity: 0.95;
    }

    /* Image Preview Modal */
    .image-preview-overlay { 
      display: none; 
      position: fixed; 
      inset: 0; 
      background: rgba(0, 0, 0, 0.85); 
      z-index: 9999; 
      padding: 20px; 
      overflow: auto;
    }
    .image-preview-overlay.active { 
      display: flex; 
      align-items: center; 
      justify-content: center; 
    }
    .image-preview-modal { 
      background: var(--bg-card); 
      border-radius: 20px; 
      max-width: 90%; 
      max-height: 90vh; 
      width: 100%; 
      display: flex; 
      flex-direction: column; 
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
      overflow: hidden;
    }
    .image-preview-header { 
      display: flex; 
      align-items: center; 
      justify-content: space-between; 
      padding: 18px 24px; 
      border-bottom: 1.5px solid var(--border-col); 
      background: linear-gradient(135deg, rgba(232, 130, 12, 0.08), rgba(245, 166, 35, 0.04));
    }
    .image-preview-title { 
      font-family: 'Poppins', sans-serif; 
      font-size: 16px; 
      font-weight: 800; 
      color: var(--txt-primary); 
    }
    .image-preview-controls { 
      display: flex; 
      align-items: center; 
      gap: 8px; 
    }
    .preview-btn { 
      width: 36px; 
      height: 36px; 
      border-radius: 50%; 
      border: 1.5px solid var(--border-col); 
      background: var(--bg-screen); 
      color: var(--txt-primary); 
      cursor: pointer; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      font-size: 14px; 
      transition: all 0.2s;
    }
    .preview-btn:hover { 
      background: var(--teal); 
      border-color: var(--teal); 
      color: #fff; 
    }
    .preview-close { 
      width: 36px; 
      height: 36px; 
      border-radius: 50%; 
      border: none; 
      background: #ef4444; 
      color: #fff; 
      cursor: pointer; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      font-size: 16px; 
      transition: all 0.2s;
    }
    .preview-close:hover { 
      background: #dc2626; 
      transform: scale(1.1); 
    }
    .image-preview-container { 
      flex: 1; 
      overflow: auto; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      background: #000; 
      position: relative;
      padding: 20px;
      min-height: 300px;
    }
    .preview-image { 
      max-width: 100%; 
      max-height: 100%; 
      object-fit: contain; 
      transition: transform 0.2s; 
      cursor: grab;
      display: block;
    }
    .preview-image:active { 
      cursor: grabbing; 
    }
    .image-preview-footer { 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      padding: 12px 24px; 
      border-top: 1.5px solid var(--border-col); 
      background: var(--bg-screen);
    }
    .zoom-level { 
      font-size: 12px; 
      font-weight: 700; 
      color: var(--txt-muted); 
      min-width: 50px; 
      text-align: center;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

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

    <div class="screen" id="sc-revenue">
      <div class="a-hdr">
        <div>
          <div class="a-greet">Monitor</div>
          <div class="a-ttl">Revenue</div>
        </div>
        <div class="a-hdr-right">
          <button class="hdr-btn" onclick="loadRevenue()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
      </div>
      <div class="a-scroll" id="revenue-scroll" style="overflow-x:hidden;">
        <!-- Metric summary cards -->
        <div class="stat-grid" style="margin-bottom:14px;">
          <div class="stat-card">
            <div class="stat-ic amber"><i class="bi bi-piggy-bank-fill"></i></div>
            <div>
              <div class="stat-val" id="total-revenue-val">₱0.00</div>
              <div class="stat-lbl">Total Revenue</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic teal"><i class="bi bi-calendar3"></i></div>
            <div>
              <div class="stat-val" id="month-revenue-val">₱0.00</div>
              <div class="stat-lbl">This Month</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic blue"><i class="bi bi-calendar2-week-fill"></i></div>
            <div>
              <div class="stat-val" id="week-revenue-val">₱0.00</div>
              <div class="stat-lbl">This Week</div>
            </div>
          </div>
          <div class="stat-card">
            <div class="stat-ic green"><i class="bi bi-calendar-event-fill"></i></div>
            <div>
              <div class="stat-val" id="today-revenue-val">₱0.00</div>
              <div class="stat-lbl">Today</div>
            </div>
          </div>
        </div>

        <!-- Pending Remittance -->
        <div style="padding:0 18px; margin-bottom:14px;">
          <div class="stat-card" style="display:flex; justify-content:space-between; align-items:center;">
            <div style="display:flex; align-items:center; gap:10px;">
              <div class="stat-ic red"><i class="bi bi-hourglass-split"></i></div>
              <div>
                <div class="stat-val" id="pending-remittance-val">₱0.00</div>
                <div class="stat-lbl">Pending Remittance</div>
              </div>
            </div>
            <div style="text-align:right;">
              <span class="badge-amber" style="font-size:10px; font-weight:700; padding:4px 8px; border-radius:8px;">Awaiting Collection</span>
            </div>
          </div>
        </div>

        <!-- Chart -->
        <div class="chart-card">
          <div class="sec-hdr" style="margin-bottom: 12px;">
            <div class="sec-ttl" style="display:flex; flex-direction:column; gap:2px;">
              <span>Earnings Analytics</span>
              <span style="font-size:11px; font-weight:500; color:var(--txt-muted); text-transform:none; letter-spacing:0;">Platform Revenue</span>
            </div>
            <div style="display:flex; background:var(--bg-input); border-radius:10px; padding:2px; gap:2px; border:1px solid var(--border-col);">
              <button class="rev-filter-btn active" onclick="updateRevenueChart('daily', this)">Daily</button>
              <button class="rev-filter-btn" onclick="updateRevenueChart('weekly', this)">Weekly</button>
              <button class="rev-filter-btn" onclick="updateRevenueChart('monthly', this)">Monthly</button>
              <button class="rev-filter-btn" onclick="updateRevenueChart('yearly', this)">Yearly</button>
            </div>
          </div>
          <div style="position:relative; height:180px; width:100%;">
            <canvas id="revenueAnalyticsChart"></canvas>
          </div>
        </div>

        <!-- Breakdown -->
        <div class="sec-pad">
          <div class="sec-hdr">
            <div class="sec-ttl">Revenue Breakdown</div>
          </div>
          <div class="card" style="padding:16px; display:flex; flex-direction:column; gap:16px;">
            <div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <div style="width:12px; height:12px; border-radius:50%; background:var(--teal);"></div>
                  <span style="font-size:12px; font-weight:700; color:var(--txt-primary);">Service Provider Commission</span>
                </div>
                <span style="font-size:12px; font-weight:800; color:var(--txt-primary);" id="breakdown-commission-val">₱0.00</span>
              </div>
              <div style="width:100%; height:8px; background:var(--bg-input); border-radius:4px; overflow:hidden;">
                <div style="width:0%; height:100%; background:var(--teal); border-radius:4px;" id="breakdown-commission-bar"></div>
              </div>
              <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--txt-muted); margin-top:4px;">
                <span>4% remittance fee per booking</span>
                <span id="breakdown-commission-pct">0% of total</span>
              </div>
            </div>
            <div>
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <div style="width:12px; height:12px; border-radius:50%; background:#2563eb;"></div>
                  <span style="font-size:12px; font-weight:700; color:var(--txt-primary);">Platform Convenience Fees</span>
                </div>
                <span style="font-size:12px; font-weight:800; color:var(--txt-primary);" id="breakdown-convenience-val">₱0.00</span>
              </div>
              <div style="width:100%; height:8px; background:var(--bg-input); border-radius:4px; overflow:hidden;">
                <div style="width:0%; height:100%; background:#2563eb; border-radius:4px;" id="breakdown-convenience-bar"></div>
              </div>
              <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--txt-muted); margin-top:4px;">
                <span>Fixed service/booking fees</span>
                <span id="breakdown-convenience-pct">0% of total</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Revenue Performance -->
        <div class="sec-pad">
          <div class="sec-hdr">
            <div class="sec-ttl">Revenue Performance</div>
          </div>
          <div class="card" style="padding:14px 16px;">
            <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:10px; text-align:center;">
              <div style="background:var(--bg-input); border-radius:12px; padding:12px 6px; border:1px solid var(--border-col);">
                <div style="font-size:18px; color:var(--teal); margin-bottom:4px;"><i class="bi bi-check2-circle"></i></div>
                <div style="font-size:15px; font-weight:800; color:var(--txt-primary);" id="perf-completed-val">0</div>
                <div style="font-size:10px; font-weight:700; color:var(--txt-muted); margin-top:2px; line-height:1.2;">Completed Bookings</div>
              </div>
              <div style="background:var(--bg-input); border-radius:12px; padding:12px 6px; border:1px solid var(--border-col);">
                <div style="font-size:18px; color:#2563eb; margin-bottom:4px;"><i class="bi bi-coin"></i></div>
                <div style="font-size:15px; font-weight:800; color:var(--txt-primary);" id="perf-avg-val">₱0.00</div>
                <div style="font-size:10px; font-weight:700; color:var(--txt-muted); margin-top:2px; line-height:1.2;">Avg. / Booking</div>
              </div>
              <div style="background:var(--bg-input); border-radius:12px; padding:12px 6px; border:1px solid var(--border-col);">
                <div style="font-size:18px; color:#16a34a; margin-bottom:4px;"><i class="bi bi-graph-up-arrow"></i></div>
                <div style="font-size:14px; font-weight:800; color:#16a34a;" id="perf-growth-val">0.0%</div>
                <div style="font-size:10px; font-weight:700; color:var(--txt-muted); margin-top:2px; line-height:1.2;">vs. Prev. Month</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Revenue Comparison -->
        <div class="sec-pad" style="margin-top:2px;">
          <div class="sec-hdr">
            <div class="sec-ttl">Revenue Comparison</div>
            <span id="comp-growth-badge" style="font-size:11px; font-weight:700; color:#16a34a; background:rgba(22,163,74,0.12); padding:3px 8px; border-radius:12px; display:inline-flex; align-items:center; gap:4px;">
              <i class="bi bi-dash"></i> 0.0%
            </span>
          </div>
          <div class="card" style="padding:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
              <div style="display:flex; align-items:center; gap:10px;">
                <div class="stat-ic green" style="width:36px; height:36px; font-size:16px;"><i class="bi bi-calendar-check-fill"></i></div>
                <div>
                  <div style="font-size:10px; font-weight:700; color:var(--txt-muted); text-transform:uppercase; letter-spacing:0.3px;">This Month</div>
                  <div style="font-size:16px; font-weight:800; color:var(--txt-primary);" id="comp-this-month-val">₱0.00</div>
                </div>
              </div>
              <div style="text-align:right;">
                <span class="badge-green" style="font-size:10px; font-weight:800; padding:3px 8px; border-radius:10px;">Current</span>
              </div>
            </div>
            
            <div style="height:1px; background:var(--border-col); margin:10px 0;"></div>

            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px;">
              <div style="display:flex; align-items:center; gap:10px;">
                <div class="stat-ic amber" style="width:36px; height:36px; font-size:16px;"><i class="bi bi-calendar-minus-fill"></i></div>
                <div>
                  <div style="font-size:10px; font-weight:700; color:var(--txt-muted); text-transform:uppercase; letter-spacing:0.3px;">Last Month</div>
                  <div style="font-size:16px; font-weight:800; color:var(--txt-primary);" id="comp-last-month-val">₱0.00</div>
                </div>
              </div>
              <div style="text-align:right;">
                <span style="font-size:10px; font-weight:700; color:var(--txt-muted);">Previous Period</span>
              </div>
            </div>

            <div style="margin-top:14px; background:var(--bg-input); border-radius:10px; padding:10px 12px; border:1px solid var(--border-col);">
              <div style="display:flex; justify-content:space-between; align-items:center; font-size:11px; margin-bottom:6px;">
                <span style="font-weight:700; color:var(--txt-muted);">Month-over-Month Change</span>
                <span id="comp-change-txt" style="font-weight:800; color:#16a34a;">0.0% (₱0.00)</span>
              </div>
              <div style="width:100%; height:6px; background:var(--border-col); border-radius:3px; overflow:hidden;">
                <div id="comp-progress-bar" style="width:0%; height:100%; background:linear-gradient(90deg, #F5A623, #16a34a); border-radius:3px;"></div>
              </div>
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
          <option value="house_cleaner">House Cleaner</option>
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
            <div class="more-row" onclick="openRemitSheet()">
              <div class="more-ic" style="background:#fff7ed;color:#ea580c;"><i class="bi bi-cash-stack"></i></div>
              <div>
                <div class="more-nm" style="color:#ea580c;">Manage Remittances</div>
                <div class="more-sub">Monitor and audit provider payment remittances</div>
              </div>
              <i class="bi bi-chevron-right more-arrow"></i>
            </div>
            <div class="more-row" onclick="openReviewSheet()">
              <div class="more-ic" style="background:#e0e7ff;color:#4f46e5;"><i class="bi bi-star-fill"></i></div>
              <div>
                <div class="more-nm" style="color:#4f46e5;">Manage Reviews</div>
                <div class="more-sub">Monitor and moderate user feedback</div>
              </div>
              <i class="bi bi-chevron-right more-arrow"></i>
            </div>
            <div class="more-row" onclick="openIncidentLogsSheet()">
              <div class="more-ic" style="background:#fef3c7;color:#d97706;"><i class="bi bi-shield-fill-exclamation"></i></div>
              <div>
                <div class="more-nm" style="color:#d97706;">Incident Logs</div>
                <div class="more-sub">View and manage reported incidents and disputes</div>
              </div>
              <i class="bi bi-chevron-right more-arrow"></i>
            </div>
            <div class="more-row" onclick="openQrRequestsSheet()" id="qrMoreRow">
              <div class="more-ic" style="background:#d1fae5;color:#059669;"><i class="bi bi-qr-code-scan"></i></div>
              <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;">
                  <div class="more-nm" style="color:#059669;">QR Change Requests</div>
                  <span id="qrAdminBadge" style="display:none;background:#ef4444;color:#fff;font-size:10px;font-weight:800;padding:2px 7px;border-radius:20px;line-height:1.4;">0</span>
                </div>
                <div class="more-sub">Review GCash/Bank QR code change requests</div>
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

    <div class="bnav">
      <div class="ni on" id="nav-overview" onclick="showTab('overview')"><i class="bi bi-grid-1x2-fill"></i><span
          class="nl">Overview</span></div>
      <div class="ni" id="nav-revenue" onclick="showTab('revenue')"><i class="bi bi-cash-coin"></i><span
          class="nl">Revenue</span></div>
      <div class="ni" id="nav-bookings" onclick="showTab('bookings')"><i class="bi bi-calendar-check-fill"></i><span
          class="nl">Bookings</span></div>
      <div class="ni" id="nav-workers" onclick="showTab('workers')"><i class="bi bi-person-badge-fill"></i><span
          class="nl">Workers</span></div>
      <div class="ni" id="nav-users" onclick="showTab('users')"><i class="bi bi-people-fill"></i><span
          class="nl">Users</span></div>
      <div class="ni" id="nav-more" onclick="showTab('more')"><i class="bi bi-grid-fill"></i><span
          class="nl">More</span></div>
    </div>


    <div class="confirm-ol" id="deleteUserConfirmOl" onclick="if(event.target===this)closeDeleteUserConfirm()">
      <div class="confirm-card">
        <div class="confirm-icon" style="background: linear-gradient(135deg, #fee2e2, #fff1f2); color: #dc2626;"><i class="bi bi-trash3-fill"></i></div>
        <div class="confirm-title">Delete User?</div>
        <div class="confirm-sub">Are you sure you want to delete this user and all associated data?</div>
        <div class="confirm-actions">
          <button class="confirm-btn cancel" onclick="closeDeleteUserConfirm()">Cancel</button>
          <button class="confirm-btn ok" style="background: linear-gradient(135deg, #dc2626, #ef4444); box-shadow: 0 8px 16px rgba(220, 38, 38, .28); border: none;" onclick="confirmDeleteUser()">Delete</button>
        </div>
      </div>
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
          <div class="detail-row" style="align-items:flex-start;gap:14px;flex-direction:column;">
            <span class="detail-lbl">Verification Documents</span>
            <div class="detail-val" id="wkVdocs" style="width:100%;font-size:12px;line-height:1.45;text-align:left;">
              No documents uploaded.
            </div>
          </div>
          <div id="wkActionButtons" class="modal-btns" style="margin-top:18px;gap:10px;display:none;">
            <button class="btn-outline-p" id="wkRejectBtn" onclick="rejectWorkerVerification()">Reject</button>
            <button class="btn-p" id="wkApproveBtn" onclick="approveWorkerVerification()">Approve</button>
          </div>
        </div>
      </div>

      <!-- Image Preview Modal -->
      <div class="image-preview-overlay" id="imagePreviewOverlay" onclick="if(event.target===this)closeImagePreview()">
        <div class="image-preview-modal">
          <div class="image-preview-header">
            <div class="image-preview-title" id="imagePreviewTitle">Document Preview</div>
            <div class="image-preview-controls">
              <button class="preview-btn" id="zoomInBtn" onclick="zoomImage(0.1)" title="Zoom In">
                <i class="bi bi-zoom-in"></i>
              </button>
              <button class="preview-btn" id="zoomOutBtn" onclick="zoomImage(-0.1)" title="Zoom Out">
                <i class="bi bi-zoom-out"></i>
              </button>
              <button class="preview-btn" id="resetZoomBtn" onclick="resetImageZoom()" title="Reset Zoom">
                <i class="bi bi-arrow-counterclockwise"></i>
              </button>
              <button class="preview-close" onclick="closeImagePreview()">
                <i class="bi bi-x-lg"></i>
              </button>
            </div>
          </div>
          <div class="image-preview-container" id="imagePreviewContainer">
            <img id="previewImage" class="preview-image" src="" alt="Document preview">
          </div>
          <div class="image-preview-footer">
            <span class="zoom-level" id="zoomLevel">100%</span>
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
            <div class="fg"><label class="fl">Name *</label><input class="fi" id="svcName" placeholder="e.g. House Cleaner">
            </div>
          </div>
          <div class="fg"><label class="fl">Description</label><input class="fi" id="svcDesc"
              placeholder="Short description"></div>
          <div style="font-size:11px;color:var(--txt-muted);margin:4px 0 12px;">Pricing is computed at booking based on selected options.</div>
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

      <!-- Incident Logs Sheet -->
      <div class="sheet-ol" id="incidentSheetOl" onclick="if(event.target===this)closeSheet('incidentSheetOl')">
        <div class="sheet" style="max-height:92vh;">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl">Incident Logs</div>
            <button class="sh-close" onclick="closeSheet('incidentSheetOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          
          <div class="search-bar" style="margin:0 0 10px 0;"><i class="bi bi-search"></i><input type="text" id="incSearch" placeholder="Search incidents..." oninput="filterIncidents()"></div>
          <div class="status-tabs" style="padding: 6px 0 12px; margin: 0;">
            <div class="stab on" id="inc-tab-all" onclick="setIncFilter('all')">All</div>
            <div class="stab" id="inc-tab-pending" onclick="setIncFilter('pending')">Pending</div>
            <div class="stab" id="inc-tab-investigation" onclick="setIncFilter('under investigation')">Under Investigation</div>
            <div class="stab" id="inc-tab-resolved" onclick="setIncFilter('resolved')">Resolved</div>
            <div class="stab" id="inc-tab-rejected" onclick="setIncFilter('rejected')">Rejected</div>
          </div>

          <div id="incidentSheetBody" style="overflow-y:auto;flex:1;padding:4px 0 20px;">
            <div class="empty-state">
              <p>Loading incidents...</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Incident Details Sheet -->
      <div class="sheet-ol" id="incidentDetailOl" onclick="if(event.target===this)closeSheet('incidentDetailOl')">
        <div class="sheet" style="max-height:92vh;">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl">Incident Details</div>
            <button class="sh-close" onclick="closeSheet('incidentDetailOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          <div id="incidentDetailBody" style="overflow-y:auto;flex:1;padding:0 4px 20px 4px;">
            <!-- Rendered dynamically -->
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

      <!-- Remittance Sheet (UI Only) -->
      <div class="sheet-ol" id="remitSheetOl" onclick="if(event.target===this)closeSheet('remitSheetOl')">
        <div class="sheet" style="max-height:92vh;">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div class="sh-ttl">Manage Remittances</div>
            <button class="sh-close" onclick="closeSheet('remitSheetOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          
          <!-- Summary Cards Grid -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:0 18px 12px;">
            <div style="background:#fff7ed; border:1px solid #ffedd5; border-radius:14px; padding:10px 12px; box-shadow:0 1px 3px rgba(234,88,12,0.05);">
              <div style="font-size:10px; font-weight:700; color:#c2410c; text-transform:uppercase; letter-spacing:0.3px;">This Month</div>
              <div style="font-size:14px; font-weight:800; color:#1f2937; margin-top:2px;" id="remitStatMonth">₱0.00</div>
            </div>
            <div style="background:#fff7ed; border:1px solid #ffedd5; border-radius:14px; padding:10px 12px; box-shadow:0 1px 3px rgba(234,88,12,0.05);">
              <div style="font-size:10px; font-weight:700; color:#c2410c; text-transform:uppercase; letter-spacing:0.3px;">This Week</div>
              <div style="font-size:14px; font-weight:800; color:#1f2937; margin-top:2px;" id="remitStatWeek">₱0.00</div>
            </div>
            <div style="background:#fff7ed; border:1px solid #ffedd5; border-radius:14px; padding:10px 12px; box-shadow:0 1px 3px rgba(234,88,12,0.05);">
              <div style="font-size:10px; font-weight:700; color:#c2410c; text-transform:uppercase; letter-spacing:0.3px;">Total Received</div>
              <div style="font-size:14px; font-weight:800; color:#1f2937; margin-top:2px;" id="remitStatTotal">₱0.00</div>
            </div>
            <div style="background:#fef2f2; border:1px solid #fee2e2; border-radius:14px; padding:10px 12px; box-shadow:0 1px 3px rgba(220,38,38,0.05);">
              <div style="font-size:10px; font-weight:700; color:#b91c1c; text-transform:uppercase; letter-spacing:0.3px;">Outstanding</div>
              <div style="font-size:14px; font-weight:800; color:#b91c1c; margin-top:2px;" id="remitStatOutstanding">₱0.00</div>
            </div>
          </div>

          <!-- Controls: Filters and Sorting -->
          <div style="background:var(--bg-card); border-bottom:1.5px solid var(--border-col); padding:10px 18px 12px; flex-shrink:0;">
            <!-- Filter Tabs -->
            <div style="display:flex; gap:6px; overflow-x:auto; scrollbar-width:none; padding-bottom:8px;">
              <div class="stab on" id="remit-tab-all" onclick="setRemitFilter('all')" style="padding:5px 12px; font-size:11px;">All</div>
              <div class="stab" id="remit-tab-paid" onclick="setRemitFilter('paid')" style="padding:5px 12px; font-size:11px;">Paid</div>
              <div class="stab" id="remit-tab-pending" onclick="setRemitFilter('pending')" style="padding:5px 12px; font-size:11px;">Pending</div>
              <div class="stab" id="remit-tab-overdue" onclick="setRemitFilter('overdue')" style="padding:5px 12px; font-size:11px;">Overdue</div>
            </div>
            <!-- Sorting dropdown -->
            <div style="display:flex; align-items:center; gap:8px; margin-top:6px;">
              <span style="font-size:11px; font-weight:700; color:var(--txt-muted);">Sort By:</span>
              <select class="fi" id="remitSort" onchange="loadAdminRemittances()" style="flex:1; padding:6px 10px; font-size:12px; height:auto; min-height:auto;">
                <option value="name_asc">Alphabetical (A–Z)</option>
                <option value="name_desc">Alphabetical (Z–A)</option>
                <option value="due_desc">Due Date (Newest)</option>
                <option value="due_asc">Due Date (Oldest)</option>
                <option value="remit_desc">Date Remitted (Newest)</option>
                <option value="amt_desc">Highest Amount Due</option>
                <option value="amt_asc">Lowest Amount Due</option>
              </select>
            </div>
          </div>

          <!-- Remittance List -->
          <div id="remitSheetBody" style="overflow-y:auto; flex:1; padding:12px 18px 24px;">
            <!-- Loaded dynamically -->
          </div>
        </div>
      </div>

      <!-- Remittance Details Modal -->
      <div class="sheet-ol" id="remitDetailOl" onclick="if(event.target===this)closeSheet('remitDetailOl')">
        <div class="sheet" style="max-height:85vh;">
          <div class="sh-hand"></div>
          <div class="sh-hdr">
            <div style="display:flex; align-items:center; gap:8px;">
              <div class="sh-ttl">Remittance Details</div>
              <span id="remitDtlStatusBadge"></span>
            </div>
            <button class="sh-close" onclick="closeSheet('remitDetailOl')"><i class="bi bi-x-lg"></i></button>
          </div>
          
          <div id="remitDetailBody" style="overflow-y:auto; flex:1; padding:0 4px 20px;">
            <!-- Rendered dynamically -->
          </div>
        </div>
      </div>

      <!-- Remittance Approve Confirm Dialog -->
      <div class="confirm-ol" id="remitApproveConfirmOl" onclick="if(event.target===this)closeRemitApproveConfirm()">
        <div class="confirm-card">
          <div class="confirm-icon" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669;">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="confirm-title">Approve Remittance?</div>
          <div class="confirm-sub">This will mark the payment as <strong>Paid</strong> and notify the provider. This action cannot be undone.</div>
          <div class="confirm-actions">
            <button class="confirm-btn cancel" onclick="closeRemitApproveConfirm()">Cancel</button>
            <button class="confirm-btn ok" id="remitApproveOkBtn" onclick="submitRemitApprove()"
              style="background:linear-gradient(135deg,#059669,#10b981);box-shadow:0 8px 16px rgba(5,150,105,.28);">
              Approve
            </button>
          </div>
        </div>
      </div>

      <!-- Remittance Reject Confirm Dialog -->
      <div class="confirm-ol" id="remitRejectConfirmOl" onclick="if(event.target===this)closeRemitRejectConfirm()">
        <div class="confirm-card">
          <div class="confirm-icon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);color:#dc2626;">
            <i class="bi bi-x-circle-fill"></i>
          </div>
          <div class="confirm-title">Reject Remittance?</div>
          <div class="confirm-sub">Please provide a reason. The provider will be notified of the rejection.</div>
          <div class="confirm-reason-wrap">
            <textarea id="remitRejectReasonInput" class="confirm-reason" placeholder="Enter rejection reason (required)..."></textarea>
          </div>
          <div class="confirm-actions">
            <button class="confirm-btn cancel" onclick="closeRemitRejectConfirm()">Cancel</button>
            <button class="confirm-btn ok" id="remitRejectOkBtn" onclick="submitRemitReject()"
              style="background:#ef4444;box-shadow:0 8px 16px rgba(239,68,68,.28);">
              Reject
            </button>
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
      const tabMap = { overview: 'sc-overview', revenue: 'sc-revenue', bookings: 'sc-bookings', workers: 'sc-workers', users: 'sc-users', more: 'sc-more' };
      const loadMap = { revenue: loadRevenue, bookings: loadBookings, workers: loadWorkers, users: loadUsers, more: loadMore };

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

      let revenueChartInstance = null;

      async function loadRevenue() {
        try {
          // 1. Load summary metrics
          const summaryData = await api('revenue', 'summary');
          if (summaryData.success) {
            document.getElementById('total-revenue-val').textContent = formatMetric(summaryData.total_revenue, true);
            document.getElementById('month-revenue-val').textContent = formatMetric(summaryData.month_revenue, true);
            document.getElementById('week-revenue-val').textContent = formatMetric(summaryData.week_revenue, true);
            document.getElementById('today-revenue-val').textContent = formatMetric(summaryData.today_revenue, false);
            document.getElementById('pending-remittance-val').textContent = formatMetric(summaryData.pending_remittance, false);

            // Populate breakdown dynamically
            const total = summaryData.total_revenue;
            const commissionAmt = total;
            const convenienceAmt = 0.00;
            
            let commissionPct = 0;
            let conveniencePct = 0;
            if (total > 0) {
              commissionPct = 100;
              conveniencePct = 0;
            }
            
            document.getElementById('breakdown-commission-val').textContent = '₱' + commissionAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('breakdown-commission-bar').style.width = commissionPct + '%';
            document.getElementById('breakdown-commission-pct').textContent = commissionPct + '% of total';
            
            document.getElementById('breakdown-convenience-val').textContent = '₱' + convenienceAmt.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            document.getElementById('breakdown-convenience-bar').style.width = conveniencePct + '%';
            document.getElementById('breakdown-convenience-pct').textContent = conveniencePct + '% of total';

            // Populate Revenue Performance dynamically
            const completedCount = parseInt(summaryData.completed_bookings) || 0;
            const avgRevenue = parseFloat(summaryData.avg_revenue_per_booking) || 0.00;
            const growthPct = parseFloat(summaryData.growth_pct) || 0.0;
            const growthDir = summaryData.growth_direction || 'flat';
            const growthDiff = parseFloat(summaryData.growth_diff) || 0.00;

            const perfCompEl = document.getElementById('perf-completed-val');
            if (perfCompEl) perfCompEl.textContent = completedCount.toLocaleString('en-US');

            const perfAvgEl = document.getElementById('perf-avg-val');
            if (perfAvgEl) perfAvgEl.textContent = '₱' + avgRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const perfGrowthEl = document.getElementById('perf-growth-val');
            if (perfGrowthEl) {
              if (growthDir === 'up') {
                perfGrowthEl.style.color = '#16a34a';
                perfGrowthEl.textContent = `↑ ${growthPct.toFixed(1)}%`;
              } else if (growthDir === 'down') {
                perfGrowthEl.style.color = '#dc2626';
                perfGrowthEl.textContent = `↓ ${growthPct.toFixed(1)}%`;
              } else {
                perfGrowthEl.style.color = 'var(--txt-muted)';
                perfGrowthEl.textContent = `0.0%`;
              }
            }

            // Populate Revenue Comparison dynamically
            const thisMonthVal = parseFloat(summaryData.month_revenue) || 0.00;
            const lastMonthVal = parseFloat(summaryData.last_month_revenue) || 0.00;

            const compThisMonthEl = document.getElementById('comp-this-month-val');
            if (compThisMonthEl) compThisMonthEl.textContent = '₱' + thisMonthVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const compLastMonthEl = document.getElementById('comp-last-month-val');
            if (compLastMonthEl) compLastMonthEl.textContent = '₱' + lastMonthVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            const compBadgeEl = document.getElementById('comp-growth-badge');
            if (compBadgeEl) {
              if (growthDir === 'up') {
                compBadgeEl.style.background = 'rgba(22,163,74,0.12)';
                compBadgeEl.style.color = '#16a34a';
                compBadgeEl.innerHTML = `<i class="bi bi-arrow-up-right"></i> +${growthPct.toFixed(1)}%`;
              } else if (growthDir === 'down') {
                compBadgeEl.style.background = 'rgba(220,38,38,0.12)';
                compBadgeEl.style.color = '#dc2626';
                compBadgeEl.innerHTML = `<i class="bi bi-arrow-down-right"></i> -${growthPct.toFixed(1)}%`;
              } else {
                compBadgeEl.style.background = 'var(--bg-input)';
                compBadgeEl.style.color = 'var(--txt-muted)';
                compBadgeEl.innerHTML = `<i class="bi bi-dash"></i> 0.0%`;
              }
            }

            const compChangeTxtEl = document.getElementById('comp-change-txt');
            if (compChangeTxtEl) {
              const sign = growthDiff >= 0 ? '+' : '-';
              const formattedDiff = `${sign}₱` + Math.abs(growthDiff).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
              if (growthDir === 'up') {
                compChangeTxtEl.style.color = '#16a34a';
                compChangeTxtEl.textContent = `↑ ${growthPct.toFixed(1)}% (${formattedDiff})`;
              } else if (growthDir === 'down') {
                compChangeTxtEl.style.color = '#dc2626';
                compChangeTxtEl.textContent = `↓ ${growthPct.toFixed(1)}% (${formattedDiff})`;
              } else {
                compChangeTxtEl.style.color = 'var(--txt-muted)';
                compChangeTxtEl.textContent = `0.0% (₱0.00)`;
              }
            }

            const compBarEl = document.getElementById('comp-progress-bar');
            if (compBarEl) {
              const maxVal = Math.max(thisMonthVal, lastMonthVal);
              const barPct = (maxVal > 0) ? Math.min(100, Math.round((thisMonthVal / maxVal) * 100)) : 0;
              compBarEl.style.width = barPct + '%';
              if (growthDir === 'down') {
                compBarEl.style.background = '#dc2626';
              } else {
                compBarEl.style.background = 'linear-gradient(90deg, #F5A623, #16a34a)';
              }
            }
          }

          // 2. Load chart data
          const activeBtn = document.querySelector('#sc-revenue .rev-filter-btn.active');
          const activeFilter = activeBtn ? activeBtn.getAttribute('onclick').match(/'([^']+)'/)[1] : 'daily';
          await fetchAndDrawChart(activeFilter);
        } catch (err) {
          console.error('Failed to load revenue analytics: ', err);
        }
      }

      function formatMetric(val, isAbbrev = true) {
        const floatVal = parseFloat(val) || 0;
        if (isAbbrev && floatVal >= 1000) {
          return '₱' + (floatVal / 1000).toFixed(1) + 'k';
        }
        return '₱' + floatVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      }

      async function fetchAndDrawChart(filter) {
        const ctx = document.getElementById('revenueAnalyticsChart');
        if (!ctx) return;

        try {
          const chartData = await api('revenue', 'chart', null, `&filter=${filter}`);
          if (!chartData.success) return;

          if (revenueChartInstance) {
            revenueChartInstance.destroy();
          }

          const isDark = document.body.classList.contains('dark');
          const gridColor = isDark ? '#4a3e28' : '#ede8e0';
          const labelColor = isDark ? '#a19685' : '#8e8e93';

          revenueChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
              labels: chartData.labels,
              datasets: [{
                label: 'Platform Revenue (₱)',
                data: chartData.data,
                borderColor: '#F5A623',
                borderWidth: 3,
                backgroundColor: createChartGradient(ctx, isDark),
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#F5A623',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  display: false
                },
                tooltip: {
                  backgroundColor: isDark ? '#2a2216' : '#ffffff',
                  titleColor: isDark ? '#ffffff' : '#1A1A2E',
                  bodyColor: '#F5A623',
                  borderColor: '#F5A623',
                  borderWidth: 1,
                  padding: 10,
                  displayColors: false,
                  callbacks: {
                    label: function(context) {
                      return '₱' + context.parsed.y.toLocaleString();
                    }
                  }
                }
              },
              scales: {
                x: {
                  grid: {
                    display: false
                  },
                  ticks: {
                    color: labelColor,
                    font: {
                      family: 'Nunito',
                      size: 10,
                      weight: 'bold'
                    }
                  }
                },
                y: {
                  grid: {
                    color: gridColor,
                    drawBorder: false
                  },
                  ticks: {
                    color: labelColor,
                    font: {
                      family: 'Nunito',
                      size: 10,
                      weight: 'bold'
                    },
                    callback: function(value) {
                      return '₱' + value;
                    }
                  }
                }
              }
            }
          });
        } catch (err) {
          console.error('Failed to draw chart: ', err);
        }
      }

      function createChartGradient(ctx, isDark) {
        const c = ctx.getContext('2d');
        const gradient = c.createLinearGradient(0, 0, 0, 180);
        gradient.addColorStop(0, 'rgba(245, 166, 35, 0.4)');
        gradient.addColorStop(1, 'rgba(245, 166, 35, 0)');
        return gradient;
      }

      async function updateRevenueChart(filter, btn) {
        const buttons = btn.parentElement.querySelectorAll('.rev-filter-btn');
        buttons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        await fetchAndDrawChart(filter);
      }

      async function loadOverview() {
        try {
          const data = await api('stats');
          if (!data.success) return;
          const s = data.stats;

          document.getElementById('st-users').textContent = s.total_users;
          document.getElementById('st-bookings').textContent = s.total_bookings;
          
          const floatVal = parseFloat(s.total_revenue) || 0;
          if (floatVal >= 1000) {
            document.getElementById('st-revenue').textContent = '₱' + (floatVal / 1000).toFixed(1) + 'k';
          } else {
            document.getElementById('st-revenue').textContent = '₱' + floatVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
          }
          
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

          // Donut (Safe-guarded)
          const svg = document.getElementById('donutSvg');
          const legend = document.getElementById('donutLegend');
          if (svg && legend) {
            const bd = s.breakdown || {};
            const colors = { pending: '#f59e0b', progress: '#3b82f6', done: '#F5A623', cancelled: '#9ca3af' };
            const total = Object.values(bd).reduce((a, b) => a + b, 0) || 1;
            let offset = 0; const circ = 2 * Math.PI * 30;
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
          }

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
            <div class="fl" style="margin-bottom:8px;">Status</div>
            <div style="display:inline-block;">
              ${statusPill(b.status)}
            </div>
          </div>

          <div style="margin-top:14px;display:flex;flex-direction:column;gap:8px;">
            ${!b.technician_name ? `
            <button class="btn-p" onclick="openWorkerPicker(${b.id},'assign')">
              <i class="bi bi-person-check-fill"></i> Assign Worker
            </button>` : ''}
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
        if (svc.includes('clean')) return 'house_cleaner';
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
        const wkVdocs = document.getElementById('wkVdocs');
        if (wkVdocs) {
          wkVdocs.innerHTML = '<span style="color:var(--txt-muted);">Loading documents...</span>';
        }

        const docOrder = ['valid_id', 'selfie', 'proof_of_address', 'barangay_clearance', 'tools_kits', 'gcash_qr', 'bank_qr'];
        const docMeta = {
          valid_id: { title: 'Valid ID', icon: 'bi-card-list' },
          selfie: { title: 'Selfie Verification', icon: 'bi-person-circle' },
          proof_of_address: { title: 'Proof of Address', icon: 'bi-geo-alt' },
          barangay_clearance: { title: 'Barangay Clearance', icon: 'bi-shield-check' },
          tools_kits: { title: 'Tools & Kits', icon: 'bi-hammer' },
          gcash_qr: { title: 'GCash QR', icon: 'bi-qr-code' },
          bank_qr: { title: 'Bank QR', icon: 'bi-qr-code-scan' }
        };

        const renderWorkerDocuments = (documents) => {
          if (!wkVdocs || currentWorkerDetailId !== w.id) return;
          const html = docOrder.flatMap(type => {
            const items = Array.isArray(documents?.[type]) ? documents[type] : [];
            const first = items[0];
            if (!first || !first.file_path) return [];
            const meta = docMeta[type];
            return [`<button class="doc-view-btn" onclick="openImagePreview('${first.file_path}', '${meta.title}')"><i class="bi ${meta.icon}"></i>View ${meta.title}</button>`];
          }).join('');
          wkVdocs.innerHTML = html || '<span style="color:var(--txt-muted);">No documents uploaded.</span>';
        };

        const fallbackDocs = {};
        if (w.valid_id) fallbackDocs.valid_id = [{ file_path: w.valid_id }];
        if (w.selfie_verification) fallbackDocs.selfie = [{ file_path: w.selfie_verification }];
        if (w.proof_of_address) fallbackDocs.proof_of_address = [{ file_path: w.proof_of_address }];
        if (w.barangay_clearance) fallbackDocs.barangay_clearance = [{ file_path: w.barangay_clearance }];
        if (w['tools_&_kits']) fallbackDocs.tools_kits = [{ file_path: w['tools_&_kits'] }];
        if (w.gcash_qr) fallbackDocs.gcash_qr = [{ file_path: w.gcash_qr }];
        if (w.bank_qr) fallbackDocs.bank_qr = [{ file_path: w.bank_qr }];
        renderWorkerDocuments(fallbackDocs);

        fetch(`../api/admin_documents_api.php?action=provider_documents&provider_id=${encodeURIComponent(w.id)}`, { cache: 'no-store' })
          .then(r => r.json())
          .then(data => {
            if (!data || !data.success || !data.documents) return;
            renderWorkerDocuments(data.documents);
          })
          .catch(() => {
            // Keep the fallback documents already rendered.
          });
        
        // Show action buttons for workers that have docs and are still awaiting review
        const hasDocuments = !!(w.valid_id || w.selfie_verification || w.proof_of_address || w.barangay_clearance || w['tools_&_kits'] || w.gcash_qr || w.bank_qr);
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

      function deleteUser(id) {
        userIdToDelete = id;
        document.getElementById('deleteUserConfirmOl').classList.add('on');
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
        <div style="font-size:11px;color:var(--txt-muted);">Pricing computed at booking</div>
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
        document.getElementById('svcSheetTtl').textContent = s ? 'Edit Service' : 'Add Service';
        document.getElementById('svcDelBtn').style.display = s ? 'block' : 'none';
        openSheet('svcSheetOl');
      }

      async function saveSvc() {
        const id = document.getElementById('svcId').value;
        const name = document.getElementById('svcName').value.trim();
        if (!name) { toast('Service name required', 'e'); return; }
        const body = fd({ id: id || '', name, icon: document.getElementById('svcIcon').value, description: document.getElementById('svcDesc').value, active: 1 });
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

      let userIdToDelete = null;

      function closeDeleteUserConfirm() {
        userIdToDelete = null;
        document.getElementById('deleteUserConfirmOl').classList.remove('on');
      }

      async function confirmDeleteUser() {
        if (!userIdToDelete) return;
        const id = userIdToDelete;
        closeDeleteUserConfirm();
        const data = await api('users', 'delete', fd({ id }));
        if (data.success) {
          toast('User deleted');
          closeSheet('usDetailOl');
          loadUsers();
        } else {
          toast(data.message || 'Failed', 'e');
        }
      }


      function svcEmoji(svc) {
        const m = { 'House Cleaner': '🧹', Plumbing: '🔧', Electrical: '⚡', Painting: '🖌️', 'Appliance Repair': '🔩' };
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
          el.innerHTML = '<div class="empty-state" style="padding:40px 20px;"><i class="bi bi-bell" style="font-size:36px;color:var(--txt-muted);opacity:.4;"></i><p style="margin-top:8px;">No notifications yet</p><p style="font-size:11px;color:var(--txt-muted);">You\'ll be notified when workers submit verification documents or remittances.</p></div>';
          return;
        }

        el.innerHTML = notifs.map(n => {
          const isVerif = n.type === 'verification';
          const isRemit = n.type === 'remittance';
          const iconClass = isVerif ? 'verif' : (isRemit ? 'verif' : 'general');
          const icon = isVerif ? '<i class="bi bi-file-earmark-check-fill"></i>' : (isRemit ? '<i class="bi bi-cash-stack"></i>' : '<i class="bi bi-bell-fill"></i>');
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

          let actionBtn = '';
          if (isVerif && n.reference_id) {
            actionBtn = `<button class="admin-notif-act-btn go" onclick="goToWorkerFromNotif(${n.reference_id}, ${n.id})" title="Review Worker"><i class="bi bi-arrow-right"></i></button>`;
          } else if (isRemit && n.remittance_id) {
            actionBtn = `<button class="admin-notif-act-btn go" onclick="goToRemittanceFromNotif(${n.remittance_id}, ${n.id})" title="Verify Remittance"><i class="bi bi-arrow-right"></i></button>`;
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
              ${actionBtn}
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
        api('admin_notifications', 'mark_read', fd({ id: notifId }));
        closeSheet('adminNotifSheetOl');
        showTab('workers');

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

      // ── Image Preview Modal ──────────────────────────────────────────────────
      let currentImageZoom = 1;

      function openImagePreview(imagePath, title) {
        console.log('Opening image preview:', { title, imagePath });
        
        const container = document.getElementById('imagePreviewContainer');
        container.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:#ccc;"><i class="bi bi-hourglass-split" style="font-size:36px;opacity:0.5;animation:spin 1s linear infinite;"></i></div>';
        
        // Use the image server endpoint to bypass .htaccess restrictions
        const imageUrl = `../api/image_serve.php?path=${encodeURIComponent(imagePath)}`;
        
        // Verify file exists first using the verification endpoint
        fetch(`../api/verify_document.php?path=${encodeURIComponent(imagePath)}`)
          .then(r => r.json())
          .then(data => {
            console.log('File verification result:', data);
            
            if (!data.success) {
              container.innerHTML = `
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;height:100%;color:#ccc;font-size:14px;padding:20px;text-align:center;">
                  <i class="bi bi-exclamation-circle" style="font-size:48px;margin-bottom:16px;opacity:0.4;"></i>
                  <p style="margin-bottom:8px;"><strong>File Not Found</strong></p>
                  <p style="font-size:12px;opacity:0.6;max-width:90%;word-break:break-all;margin-bottom:8px;">${imagePath}</p>
                  <p style="font-size:11px;opacity:0.4;">The file may have been deleted or moved</p>
                </div>
              `;
              return;
            }
            
            // File exists, now load it through the image server
            const img = document.createElement('img');
            img.id = 'previewImage';
            img.className = 'preview-image';
            img.alt = title || 'Document preview';
            img.style.transform = 'scale(1)';
            
            img.onload = function() {
              console.log('Image loaded successfully');
              currentImageZoom = 1;
            };
            
            img.onerror = function() {
              console.error('Image element failed to load:', imageUrl);
              container.innerHTML = `
                <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;height:100%;color:#ccc;font-size:14px;padding:20px;text-align:center;">
                  <i class="bi bi-image" style="font-size:48px;margin-bottom:16px;opacity:0.4;"></i>
                  <p style="margin-bottom:8px;">Failed to Display Image</p>
                  <p style="font-size:12px;opacity:0.6;">File: ${data.mime_type ? data.mime_type : 'unknown type'}</p>
                  <p style="font-size:11px;opacity:0.4;">Size: ${data.size ? (data.size / 1024).toFixed(2) + ' KB' : 'unknown'}</p>
                </div>
              `;
            };
            
            container.innerHTML = '';
            container.appendChild(img);
            img.src = imageUrl;
            console.log('Image src set to:', imageUrl);
          })
          .catch(err => {
            console.error('File verification error:', err);
            container.innerHTML = `
              <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;width:100%;height:100%;color:#ccc;font-size:14px;padding:20px;text-align:center;">
                <i class="bi bi-exclamation-triangle" style="font-size:48px;margin-bottom:16px;opacity:0.4;"></i>
                <p>Could Not Verify File</p>
                <p style="font-size:11px;opacity:0.4;margin-top:12px;">Please try again</p>
              </div>
            `;
          });
        
        document.getElementById('imagePreviewTitle').textContent = title || 'Document Preview';
        document.getElementById('zoomLevel').textContent = '100%';
        currentImageZoom = 1;
        
        document.getElementById('imagePreviewOverlay').classList.add('active');
      }

      function closeImagePreview() {
        document.getElementById('imagePreviewOverlay').classList.remove('active');
        document.getElementById('previewImage').src = '';
        document.getElementById('imagePreviewContainer').innerHTML = '<img id="previewImage" class="preview-image" src="" alt="Document preview">';
        currentImageZoom = 1;
      }

      function zoomImage(delta) {
        currentImageZoom = Math.max(0.5, Math.min(currentImageZoom + delta, 3));
        const img = document.getElementById('previewImage');
        if (img) {
          img.style.transform = `scale(${currentImageZoom})`;
        }
        document.getElementById('zoomLevel').textContent = Math.round(currentImageZoom * 100) + '%';
      }

      function resetImageZoom() {
        currentImageZoom = 1;
        const img = document.getElementById('previewImage');
        if (img) {
          img.style.transform = 'scale(1)';
        }
        document.getElementById('zoomLevel').textContent = '100%';
      }

      // ── Incident Logs ────────────────────────────────────────────────────────
      let _incFilter = 'all';
      let _incidents = [];

      async function openIncidentLogsSheet() {
        openSheet('incidentSheetOl');
        _incFilter = 'all';
        document.getElementById('incSearch').value = '';
        updateIncFilterTabs();
        document.getElementById('incidentSheetBody').innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite; font-size: 24px; display: block; margin-bottom: 8px;"></i><p>Loading incidents...</p></div>';
        await fetchIncidents();
      }

      async function fetchIncidents() {
        try {
          const data = await api('incidents', 'list');
          if (data.success) {
            _incidents = (data.incidents || []).map(row => {
              return {
                reportId: row.report_id,
                bookingId: row.booking_id || 'GENERAL',
                dateReported: row.created_at ? row.created_at.split(' ')[0] : '–',
                incidentType: row.category,
                status: row.status ? row.status.toLowerCase() : 'pending',
                reporter: {
                  id: row.reporter_id,
                  name: row.reporter_name || 'Unknown',
                  contact: row.reporter_phone || '–',
                  role: row.reporter_role === 'client' ? 'Homeowner' : 'Service Provider'
                },
                reportedUser: {
                  id: row.reported_user_id || '',
                  name: row.reported_name || 'None',
                  contact: row.reported_phone || '–',
                  role: row.reported_user_role ? (row.reported_user_role === 'client' ? 'Homeowner' : 'Service Provider') : '–'
                },
                description: row.description,
                evidenceName: row.evidence_path ? row.evidence_path.split('/').pop() : '',
                evidencePath: row.evidence_path || '',
                notes: row.notes || ''
              };
            });
            renderIncidents();
          } else {
            document.getElementById('incidentSheetBody').innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-triangle"></i><p>Failed to load incidents.</p></div>';
          }
        } catch (e) {
          document.getElementById('incidentSheetBody').innerHTML = '<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Network error.</p></div>';
        }
      }

      function setIncFilter(filter) {
        _incFilter = filter;
        updateIncFilterTabs();
        renderIncidents();
      }

      function updateIncFilterTabs() {
        const ids = {
          'all': 'inc-tab-all',
          'pending': 'inc-tab-pending',
          'under investigation': 'inc-tab-investigation',
          'resolved': 'inc-tab-resolved',
          'rejected': 'inc-tab-rejected'
        };
        Object.entries(ids).forEach(([f, id]) => {
          const tab = document.getElementById(id);
          if (tab) {
            if (f === _incFilter) {
              tab.classList.add('on');
            } else {
              tab.classList.remove('on');
            }
          }
        });
      }

      function renderIncidents() {
        const body = document.getElementById('incidentSheetBody');
        const query = document.getElementById('incSearch').value.toLowerCase().trim();

        let filtered = _incidents;
        if (_incFilter !== 'all') {
          filtered = filtered.filter(i => i.status === _incFilter);
        }
        if (query) {
          filtered = filtered.filter(i => 
            i.reportId.toLowerCase().includes(query) ||
            i.bookingId.toLowerCase().includes(query) ||
            i.reporter.name.toLowerCase().includes(query) ||
            i.reportedUser.name.toLowerCase().includes(query) ||
            i.incidentType.toLowerCase().includes(query)
          );
        }

        if (!filtered.length) {
          body.innerHTML = `
            <div class="empty-state">
              <i class="bi bi-shield-slash" style="font-size: 30px; opacity: 0.4;"></i>
              <p>No incidents found.</p>
            </div>
          `;
          return;
        }

        body.innerHTML = filtered.map(i => {
          return `
            <div class="list-item" style="cursor:default;align-items:flex-start;flex-direction:column;gap:8px;padding:16px;background:var(--bg-card);border:1.5px solid var(--border-col);border-radius:16px;margin-bottom:10px;box-shadow:0 2px 8px rgba(0,0,0,.03);">
              <div style="display:flex;width:100%;align-items:center;justify-content:space-between;margin-bottom:2px;">
                <span style="font-family:'Poppins',sans-serif;font-size:13px;font-weight:800;color:var(--txt-primary);">${i.reportId}</span>
                <span style="font-size:12px;font-weight:700;color:var(--txt-muted);">${i.dateReported}</span>
              </div>
              <div style="display:flex;width:100%;justify-content:space-between;align-items:center;">
                <div style="font-size:12px;color:var(--txt-muted);">
                  Booking ID: <strong style="color:var(--txt-primary);">${i.bookingId}</strong>
                </div>
                <div>${statusBadge(i.status)}</div>
              </div>
              <div style="width:100%;border-top:1px solid var(--border-col);margin:4px 0;"></div>
              <div style="display:flex;width:100%;justify-content:space-between;align-items:center;gap:12px;">
                <div style="flex:1;min-width:0;">
                  <div style="font-size:12px;font-weight:700;color:var(--txt-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    Type: ${i.incidentType}
                  </div>
                  <div style="font-size:11px;color:var(--txt-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    Reporter: ${i.reporter.name} (${i.reporter.role})
                  </div>
                  <div style="font-size:11px;color:var(--txt-muted);margin-top:1px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    Reported: ${i.reportedUser.name} (${i.reportedUser.role})
                  </div>
                </div>
                <button class="doc-view-btn" onclick="openIncidentDetail('${i.reportId}')" style="padding:6px 12px;font-size:11px;height:auto;flex-shrink:0;">
                  View Details
                </button>
              </div>
            </div>
          `;
        }).join('');
      }

      function filterIncidents() {
        renderIncidents();
      }

      function openIncidentDetail(reportId) {
        const inc = _incidents.find(i => i.reportId === reportId);
        if (!inc) return;

        renderIncidentDetail(inc);
        openSheet('incidentDetailOl');
      }

      function renderIncidentDetail(inc) {
        const body = document.getElementById('incidentDetailBody');
        body.innerHTML = `
          <div style="background:var(--bg-screen);border-radius:16px;padding:16px;margin-bottom:14px;border:1.5px solid var(--border-col);">
            <div style="font-size:11px;font-weight:800;color:var(--teal);text-transform:uppercase;margin-bottom:10px;letter-spacing:0.5px;font-family:'Poppins',sans-serif;">Report Information</div>
            <div class="detail-row"><span class="detail-lbl">Report ID</span><span class="detail-val">${inc.reportId}</span></div>
            <div class="detail-row"><span class="detail-lbl">Booking ID</span><span class="detail-val">${inc.bookingId}</span></div>
            <div class="detail-row"><span class="detail-lbl">Date Reported</span><span class="detail-val">${inc.dateReported}</span></div>
            <div class="detail-row"><span class="detail-lbl">Incident Type</span><span class="detail-val">${inc.incidentType}</span></div>
            <div class="detail-row"><span class="detail-lbl">Status</span><span class="detail-val">${statusBadge(inc.status)}</span></div>
          </div>

          <div style="background:var(--bg-screen);border-radius:16px;padding:16px;margin-bottom:14px;border:1.5px solid var(--border-col);">
            <div style="font-size:11px;font-weight:800;color:var(--teal);text-transform:uppercase;margin-bottom:10px;letter-spacing:0.5px;font-family:'Poppins',sans-serif;">Reporter Information</div>
            <div class="detail-row"><span class="detail-lbl">Name</span><span class="detail-val">${inc.reporter.name}</span></div>
            <div class="detail-row"><span class="detail-lbl">Contact Number</span><span class="detail-val">${inc.reporter.contact}</span></div>
            <div class="detail-row"><span class="detail-lbl">Role</span><span class="detail-val">${inc.reporter.role}</span></div>
          </div>

          <div style="background:var(--bg-screen);border-radius:16px;padding:16px;margin-bottom:14px;border:1.5px solid var(--border-col);">
            <div style="font-size:11px;font-weight:800;color:var(--teal);text-transform:uppercase;margin-bottom:10px;letter-spacing:0.5px;font-family:'Poppins',sans-serif;">Incident Description</div>
            <div style="font-size:12.5px;color:var(--txt-primary);line-height:1.55;white-space:pre-line;margin-bottom:12px;">${inc.description}</div>
            
            ${inc.evidencePath ? `
            <div style="font-size:11px;font-weight:700;color:var(--txt-muted);margin-bottom:6px;">Evidence Attachment</div>
            <div style="display:flex;align-items:center;gap:10px;background:var(--bg-card);border:1.5px dashed var(--border-col);border-radius:12px;padding:12px;cursor:pointer;" onclick="openImagePreview('../${inc.evidencePath}', 'Evidence — ${inc.reportId}')">
              <i class="bi bi-file-earmark-image-fill" style="font-size:24px;color:#d97706;"></i>
              <div style="flex:1;">
                <div style="font-size:12px;font-weight:700;color:var(--txt-primary);">${inc.evidenceName}</div>
                <div style="font-size:10px;color:var(--txt-muted);">Tap to preview</div>
              </div>
              <i class="bi bi-zoom-in" style="font-size:16px;color:var(--txt-muted);"></i>
            </div>
            ` : `
            <div style="font-size:11px;font-weight:700;color:var(--txt-muted);margin-bottom:6px;">Evidence Attachment</div>
            <div style="display:flex;align-items:center;gap:10px;background:var(--bg-screen);border:1.5px dashed var(--border-col);border-radius:12px;padding:12px;color:var(--txt-muted);">
              <i class="bi bi-file-earmark-x" style="font-size:24px;color:var(--txt-muted);"></i>
              <div style="font-size:12px;">No evidence uploaded</div>
            </div>
            `}
          </div>

          ${inc.notes ? `
          <div style="background:var(--bg-screen);border-radius:16px;padding:16px;margin-bottom:14px;border:1.5px solid var(--border-col);">
            <div style="font-size:11px;font-weight:800;color:var(--teal);text-transform:uppercase;margin-bottom:10px;letter-spacing:0.5px;font-family:'Poppins',sans-serif;">Investigation Notes</div>
            <div style="font-size:12px;color:var(--txt-primary);line-height:1.5;white-space:pre-line;background:#fff8ef;border-left:3px solid #d97706;padding:8px 10px;border-radius:8px;">${inc.notes}</div>
          </div>
          ` : ''}

          <div style="margin-top:20px;">
            <div style="font-size:11px;font-weight:800;color:var(--txt-muted);text-transform:uppercase;margin-bottom:10px;letter-spacing:0.5px;font-family:'Poppins',sans-serif;">Actions</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
              <button class="doc-view-btn" onclick="markIncUnderInvestigation('${inc.reportId}')" style="background:#eff6ff;color:#2563eb;box-shadow:none;border:1.5px solid #bfdbfe;font-size:12px;padding:10px 8px;justify-content:center;">
                <i class="bi bi-search" style="color:#2563eb;"></i> Investigate
              </button>
              <button class="doc-view-btn" onclick="resolveIncCase('${inc.reportId}')" style="background:#ecfdf5;color:#059669;box-shadow:none;border:1.5px solid #a7f3d0;font-size:12px;padding:10px 8px;justify-content:center;">
                <i class="bi bi-check-circle" style="color:#059669;"></i> Resolve Case
              </button>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px;">
              <button class="doc-view-btn" onclick="rejectIncReport('${inc.reportId}')" style="background:#fff5f5;color:#e53e3e;box-shadow:none;border:1.5px solid #feb2b2;font-size:12px;padding:10px 8px;justify-content:center;">
                <i class="bi bi-x-circle" style="color:#e53e3e;"></i> Reject Report
              </button>
              <button class="doc-view-btn" onclick="addIncNotes('${inc.reportId}')" style="background:#fffbeb;color:#d97706;box-shadow:none;border:1.5px solid #fde68a;font-size:12px;padding:10px 8px;justify-content:center;">
                <i class="bi bi-journal-text" style="color:#d97706;"></i> Add Notes
              </button>
            </div>
          </div>
        `;
      }

      function statusBadge(s) {
        const key = String(s || '').toLowerCase().trim();
        const map = {
          'pending': 'badge-amber',
          'under investigation': 'badge-blue',
          'resolved': 'badge-green',
          'rejected': 'badge-gray'
        };
        return `<span class="${map[key] || 'badge-gray'}" style="text-transform:capitalize;font-weight:700;padding:3px 8px;border-radius:12px;font-size:10px;display:inline-block;">${key}</span>`;
      }

      async function markIncUnderInvestigation(reportId) {
        try {
          const res = await api('incidents', 'update_status', fd({ id: reportId, status: 'under investigation' }));
          if (res.success) {
            toast('Incident status set to Under Investigation');
            await fetchIncidents();
            const inc = _incidents.find(i => i.reportId === reportId);
            if (inc) renderIncidentDetail(inc);
          } else {
            toast(res.message || 'Failed to update status', 'e');
          }
        } catch (e) {
          toast('Network error', 'e');
        }
      }

      async function resolveIncCase(reportId) {
        try {
          const res = await api('incidents', 'update_status', fd({ id: reportId, status: 'resolved' }));
          if (res.success) {
            toast('Incident case resolved successfully');
            await fetchIncidents();
            const inc = _incidents.find(i => i.reportId === reportId);
            if (inc) renderIncidentDetail(inc);
          } else {
            toast(res.message || 'Failed to resolve case', 'e');
          }
        } catch (e) {
          toast('Network error', 'e');
        }
      }

      async function rejectIncReport(reportId) {
        try {
          const res = await api('incidents', 'update_status', fd({ id: reportId, status: 'rejected' }));
          if (res.success) {
            toast('Incident report rejected');
            await fetchIncidents();
            const inc = _incidents.find(i => i.reportId === reportId);
            if (inc) renderIncidentDetail(inc);
          } else {
            toast(res.message || 'Failed to reject report', 'e');
          }
        } catch (e) {
          toast('Network error', 'e');
        }
      }

      async function suspendIncUser(userId, role, name) {
        if (!confirm2(`Are you sure you want to suspend the ${role === 'client' ? 'Homeowner' : 'Service Provider'} "${name}"?`)) return;
        try {
          const res = await api('incidents', 'suspend_user', fd({ reporter_id: userId, role: role }));
          if (res.success) {
            toast(`${role === 'client' ? 'Homeowner' : 'Service Provider'} "${name}" suspended successfully`, 's');
          } else {
            toast(res.message || 'Failed to suspend user', 'e');
          }
        } catch (e) {
          toast('Network error', 'e');
        }
      }

      async function sendIncWarning(userId, role, name) {
        const msg = prompt(`Enter warning message for ${role === 'client' ? 'Homeowner' : 'Service Provider'} "${name}":`, `This is a warning notification from the admin team regarding a reported incident.`);
        if (msg === null) return;
        const cleanMsg = msg.trim();
        try {
          const res = await api('incidents', 'warn_user', fd({ reporter_id: userId, role: role, message: cleanMsg }));
          if (res.success) {
            toast(`Warning sent to "${name}" successfully`, 's');
          } else {
            toast(res.message || 'Failed to send warning', 'e');
          }
        } catch (e) {
          toast('Network error', 'e');
        }
      }

      async function addIncNotes(reportId) {
        const inc = _incidents.find(i => i.reportId === reportId);
        if (!inc) return;
        const notes = prompt('Enter investigation notes:', inc.notes);
        if (notes !== null) {
          const cleanNotes = notes.trim();
          try {
            const res = await api('incidents', 'add_notes', fd({ id: reportId, notes: cleanNotes }));
            if (res.success) {
              toast('Investigation notes updated');
              await fetchIncidents();
              const newInc = _incidents.find(i => i.reportId === reportId);
              if (newInc) renderIncidentDetail(newInc);
            } else {
              toast(res.message || 'Failed to update notes', 'e');
            }
          } catch (e) {
            toast('Network error', 'e');
          }
        }
      }
    </script>


<!-- ══════════════════════════════════════════════════════════════
     QR CHANGE REQUESTS — ADMIN BOTTOM SHEET
══════════════════════════════════════════════════════════════ -->

<!-- Main sheet overlay -->
<div class="sheet-ol" id="qrRequestsSheetOl" onclick="if(event.target===this)closeQrRequestsSheet()">
  <div class="sheet" style="max-height:94vh;">
    <div class="sh-hand"></div>
    <div class="sh-hdr">
      <div style="display:flex;align-items:center;gap:10px;">
        <div class="sh-ttl">QR Change Requests</div>
        <span id="qrSheetBadge" style="display:none;background:#ef4444;color:#fff;font-size:10px;font-weight:800;padding:2px 7px;border-radius:20px;">0</span>
      </div>
      <div style="display:flex;align-items:center;gap:6px;">
        <select id="qrStatusFilter" onchange="loadQrRequests()" style="border:1.5px solid var(--border-col);border-radius:10px;padding:5px 10px;font-family:'Nunito',sans-serif;font-size:12px;font-weight:700;color:var(--txt-muted);background:var(--bg-card);outline:none;">
          <option value="all">All</option>
          <option value="pending">Pending</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
        </select>
        <button class="sh-close" onclick="closeQrRequestsSheet()"><i class="bi bi-x-lg"></i></button>
      </div>
    </div>
    <div id="qrRequestsList" style="flex:1;overflow-y:auto;padding-bottom:20px;">
      <div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite;"></i><p>Loading...</p></div>
    </div>
  </div>
</div>

<!-- Approve confirm dialog -->
<div class="confirm-ol" id="qrApproveConfirmOl" onclick="if(event.target===this)closeQrApproveConfirm()">
  <div class="confirm-card">
    <div class="confirm-icon" style="background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669;"><i class="bi bi-check-circle-fill"></i></div>
    <div class="confirm-title">Approve QR Change?</div>
    <div class="confirm-sub">The provider's active QR code will be replaced with the newly uploaded one immediately.</div>
    <div class="confirm-actions">
      <button class="confirm-btn cancel" onclick="closeQrApproveConfirm()">Cancel</button>
      <button class="confirm-btn ok" id="qrApproveOkBtn" onclick="submitQrApprove()">Approve</button>
    </div>
  </div>
</div>

<!-- Reject confirm dialog -->
<div class="confirm-ol" id="qrRejectConfirmOl" onclick="if(event.target===this)closeQrRejectConfirm()">
  <div class="confirm-card">
    <div class="confirm-icon"><i class="bi bi-x-circle-fill"></i></div>
    <div class="confirm-title">Reject QR Change?</div>
    <div class="confirm-sub">Please provide a reason for rejection. The provider will be notified.</div>
    <div class="confirm-reason-wrap">
      <textarea id="qrRejectRemarks" class="confirm-reason" placeholder="Enter rejection remarks (required)..."></textarea>
    </div>
    <div class="confirm-actions">
      <button class="confirm-btn cancel" onclick="closeQrRejectConfirm()">Cancel</button>
      <button class="confirm-btn ok" id="qrRejectOkBtn" onclick="submitQrReject()" style="background:#ef4444;box-shadow:0 8px 16px rgba(239,68,68,.28);">Reject</button>
    </div>
  </div>
</div>

<style>
  .qr-req-card { background:var(--bg-card); border-radius:16px; border:1.5px solid var(--border-col); padding:14px 16px; margin:10px 18px; }
  .qr-req-hdr  { display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; }
  .qr-req-name { font-size:14px; font-weight:800; color:var(--txt-primary); }
  .qr-req-date { font-size:10px; color:var(--txt-muted); font-weight:600; }
  .qr-req-reason { font-size:12px; color:var(--txt-muted); line-height:1.5; margin-bottom:12px; }
  .qr-qrs-row { display:flex; gap:12px; margin-bottom:12px; }
  .qr-qr-box { flex:1; background:var(--bg-screen); border-radius:12px; border:1.5px solid var(--border-col); padding:10px; text-align:center; }
  .qr-qr-lbl { font-size:10px; font-weight:800; color:var(--txt-muted); text-transform:uppercase; letter-spacing:.3px; margin-bottom:6px; }
  .qr-qr-img  { width:72px; height:72px; object-fit:contain; border-radius:8px; cursor:pointer; transition:opacity .15s; border:1px solid var(--border-col); background:#fff; }
  .qr-qr-img:hover { opacity:.8; }
  .qr-qr-none { width:72px; height:72px; border-radius:8px; background:var(--bg-card); border:1.5px dashed var(--border-col); display:flex; align-items:center; justify-content:center; font-size:22px; color:#cbd5e1; margin:0 auto; }
  .qr-req-actions { display:flex; gap:8px; }
  .qr-act-btn { flex:1; padding:10px; border-radius:12px; border:none; font-family:'Poppins',sans-serif; font-size:12px; font-weight:800; cursor:pointer; transition:all .18s; }
  .qr-act-approve { background:linear-gradient(135deg,#059669,#10b981); color:#fff; box-shadow:0 6px 14px rgba(5,150,105,.28); }
  .qr-act-reject  { background:#fee2e2; color:#dc2626; }
  .qr-act-approve:hover { filter:brightness(1.05); }
  .qr-act-reject:hover  { background:#fecaca; }
  .qr-remarks-box { background:#fff7ed; border:1.5px solid #fed7aa; border-radius:10px; padding:9px 12px; font-size:11px; color:#9a3412; font-weight:600; margin-top:8px; line-height:1.5; }
</style>

<script>
  // ── QR Change Requests — Admin ───────────────────────────────────────────
  var _qrPendingId = null;

  function openQrRequestsSheet() {
    document.getElementById('qrRequestsSheetOl').classList.add('on');
    loadQrRequests();
  }

  function closeQrRequestsSheet() {
    document.getElementById('qrRequestsSheetOl').classList.remove('on');
  }

  function loadQrRequests() {
    const list = document.getElementById('qrRequestsList');
    const status = document.getElementById('qrStatusFilter').value;
    list.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite;"></i><p>Loading...</p></div>';
    fetch('../api/qr_change_api.php?action=list&status=' + encodeURIComponent(status), { cache: 'no-store' })
      .then(r => r.json())
      .then(data => {
        if (!data.success) { list.innerHTML = '<div class="empty-state"><i class="bi bi-exclamation-triangle"></i><p>Failed to load requests.</p></div>'; return; }
        renderQrRequests(data.requests);
      })
      .catch(() => { list.innerHTML = '<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Network error.</p></div>'; });
  }

  function renderQrRequests(rows) {
    const list = document.getElementById('qrRequestsList');
    if (!rows || !rows.length) {
      list.innerHTML = '<div class="empty-state"><i class="bi bi-inbox"></i><p>No requests found.</p></div>';
      return;
    }
    const baseUrl = '../';
    list.innerHTML = rows.map(r => {
      const statusPill = {
        pending:  '<span class="badge-amber" style="font-size:10px;padding:3px 9px;border-radius:20px;font-weight:800;">Pending Review</span>',
        approved: '<span class="badge-green" style="font-size:10px;padding:3px 9px;border-radius:20px;font-weight:800;">Approved</span>',
        rejected: '<span class="badge-gray"  style="font-size:10px;padding:3px 9px;border-radius:20px;font-weight:800;">Rejected</span>',
      }[r.status] || '';

      const currentQrHtml = r.current_qr_path
        ? `<img src="${baseUrl}${qrEsc(r.current_qr_path)}" class="qr-qr-img" alt="Current QR" onclick="openImagePreview('${baseUrl}${qrEsc(r.current_qr_path)}', 'Current QR — ${qrEsc(r.provider_name)}')"> `
        : '<div class="qr-qr-none"><i class="bi bi-qr-code"></i></div>';

      const newQrHtml = r.new_qr_path
        ? `<img src="${baseUrl}${qrEsc(r.new_qr_path)}" class="qr-qr-img" alt="New QR" onclick="openImagePreview('${baseUrl}${qrEsc(r.new_qr_path)}', 'New QR — ${qrEsc(r.provider_name)}')">`
        : '<div class="qr-qr-none"><i class="bi bi-qr-code"></i></div>';

      const date = r.submitted_at ? r.submitted_at.substring(0,10) : '–';

      const remarksHtml = (r.status === 'rejected' && r.admin_remarks)
        ? `<div class="qr-remarks-box"><i class="bi bi-chat-left-text" style="margin-right:5px;"></i>Rejection remarks: ${qrEsc(r.admin_remarks)}</div>`
        : '';

      const actionsHtml = r.status === 'pending'
        ? `<div class="qr-req-actions">
             <button class="qr-act-btn qr-act-reject" onclick="openQrRejectConfirm(${r.id})"><i class="bi bi-x-circle"></i> Reject</button>
             <button class="qr-act-btn qr-act-approve" onclick="openQrApproveConfirm(${r.id})"><i class="bi bi-check-circle"></i> Approve</button>
           </div>`
        : '';

      return `<div class="qr-req-card">
        <div class="qr-req-hdr">
          <div>
            <div class="qr-req-name">${qrEsc(r.provider_name || 'Unknown')}</div>
            <div style="font-size:11px;color:var(--txt-muted);margin-top:1px;">${qrEsc(r.service_category || '')} · ${qrEsc(r.contact_number || '')}</div>
          </div>
          <div style="text-align:right;">
            ${statusPill}
            <div class="qr-req-date" style="margin-top:4px;">Submitted: ${qrEsc(date)}</div>
          </div>
        </div>
        <div style="font-size:11px;font-weight:700;color:var(--txt-muted);text-transform:uppercase;letter-spacing:.3px;margin-bottom:5px;">Reason for Change</div>
        <div class="qr-req-reason">${qrEsc(r.reason)}</div>
        <div class="qr-qrs-row">
          <div class="qr-qr-box">
            <div class="qr-qr-lbl">Current QR</div>
            ${currentQrHtml}
          </div>
          <div class="qr-qr-box">
            <div class="qr-qr-lbl">Requested QR</div>
            ${newQrHtml}
          </div>
        </div>
        ${remarksHtml}
        ${actionsHtml}
      </div>`;
    }).join('');
  }

  // Approve flow
  function openQrApproveConfirm(id) {
    _qrPendingId = id;
    document.getElementById('qrApproveConfirmOl').classList.add('on');
  }
  function closeQrApproveConfirm() {
    document.getElementById('qrApproveConfirmOl').classList.remove('on');
    _qrPendingId = null;
  }
  function submitQrApprove() {
    if (!_qrPendingId) return;
    const btn = document.getElementById('qrApproveOkBtn');
    btn.disabled = true; btn.textContent = 'Approving...';
    const fd = new FormData();
    fd.append('id', _qrPendingId);
    fetch('../api/qr_change_api.php?action=approve', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        btn.disabled = false; btn.textContent = 'Approve';
        closeQrApproveConfirm();
        if (data.success) {
          toast('QR change request approved. Provider notified.', 's');
          loadQrRequests();
          pollQrRequestCount();
        } else {
          toast(data.message || 'Approval failed.', 'e');
        }
      })
      .catch(() => { btn.disabled = false; btn.textContent = 'Approve'; toast('Network error.', 'e'); });
  }

  // Reject flow
  function openQrRejectConfirm(id) {
    _qrPendingId = id;
    document.getElementById('qrRejectRemarks').value = '';
    document.getElementById('qrRejectConfirmOl').classList.add('on');
  }
  function closeQrRejectConfirm() {
    document.getElementById('qrRejectConfirmOl').classList.remove('on');
    _qrPendingId = null;
  }
  function submitQrReject() {
    const remarks = document.getElementById('qrRejectRemarks').value.trim();
    if (!remarks) { toast('Rejection remarks are required.', 'e'); return; }
    if (!_qrPendingId) return;
    const btn = document.getElementById('qrRejectOkBtn');
    btn.disabled = true; btn.textContent = 'Rejecting...';
    const fd = new FormData();
    fd.append('id', _qrPendingId);
    fd.append('remarks', remarks);
    fetch('../api/qr_change_api.php?action=reject', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(data => {
        btn.disabled = false; btn.textContent = 'Reject';
        closeQrRejectConfirm();
        if (data.success) {
          toast('QR change request rejected. Provider notified.', 's');
          loadQrRequests();
          pollQrRequestCount();
        } else {
          toast(data.message || 'Rejection failed.', 'e');
        }
      })
      .catch(() => { btn.disabled = false; btn.textContent = 'Reject'; toast('Network error.', 'e'); });
  }

  // Badge poll
  function pollQrRequestCount() {
    fetch('../api/qr_change_api.php?action=pending_count', { cache: 'no-store' })
      .then(r => r.json())
      .then(data => {
        const count = data.count || 0;
        const moreBadge = document.getElementById('qrAdminBadge');
        const sheetBadge = document.getElementById('qrSheetBadge');
        if (moreBadge) { moreBadge.textContent = count; moreBadge.style.display = count > 0 ? 'inline-block' : 'none'; }
        if (sheetBadge) { sheetBadge.textContent = count; sheetBadge.style.display = count > 0 ? 'inline-block' : 'none'; }
      })
      .catch(() => {});
  }



  // Safe HTML escape
  function qrEsc(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  // Re-parent QR and Remittance overlays into the #app shell so they respect the mobile layout
  (function() {
    var appShell = document.getElementById('app');
    ['qrRequestsSheetOl', 'qrApproveConfirmOl', 'qrRejectConfirmOl',
     'remitSheetOl', 'remitDetailOl',
     'remitApproveConfirmOl', 'remitRejectConfirmOl'].forEach(function(id) {
      var el = document.getElementById(id);
      if (appShell && el) appShell.appendChild(el);
    });
  })();

  // ── Remittance Section (Live Data) ─────────────────────────────────────────
  let remitFilter = 'all';
  let remittancesCache = [];

  function openRemitSheet() {
    openSheet('remitSheetOl');
    remitFilter = 'all';
    document.querySelectorAll('#remitSheetOl .stab').forEach(e => e.classList.remove('on'));
    document.getElementById('remit-tab-all').classList.add('on');
    document.getElementById('remitSort').value = 'name_asc';
    loadAdminRemittances();
  }

  function setRemitFilter(f) {
    document.querySelectorAll('#remitSheetOl .stab').forEach(e => e.classList.remove('on'));
    document.getElementById(`remit-tab-${f}`).classList.add('on');
    remitFilter = f;
    loadAdminRemittances();
  }

  async function loadAdminRemittances() {
    const body = document.getElementById('remitSheetBody');
    const sortVal = document.getElementById('remitSort').value;
    
    body.innerHTML = '<div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite;display:inline-block; font-size:32px;"></i><p>Loading...</p></div>';
    
    try {
      const data = await api('remittances', 'list', null, `&status=${remitFilter}&sort=${sortVal}`);
      if (!data.success) {
        body.innerHTML = `<div class="empty-state"><p>${data.message || 'Error loading remittances.'}</p></div>`;
        return;
      }
      
      const s = data.stats;
      document.getElementById('remitStatMonth').textContent = php(s.month_received);
      document.getElementById('remitStatWeek').textContent = php(s.week_received);
      document.getElementById('remitStatTotal').textContent = php(s.total_received);
      document.getElementById('remitStatOutstanding').textContent = php(s.outstanding);

      remittancesCache = data.remittances || [];
      if (!remittancesCache.length) {
        body.innerHTML = '<div class="empty-state"><i class="bi bi-cash-coin" style="font-size:32px;"></i><p>No remittances found.</p></div>';
        return;
      }

      body.innerHTML = remittancesCache.map(r => {
        const statusClass = r.status === 'paid' ? 'badge-green' : (r.status === 'overdue' ? 'badge-red' : (r.status === 'submitted' ? 'badge-blue' : 'badge-amber'));
        const statusLabel = r.status.charAt(0).toUpperCase() + r.status.slice(1);
        const dateText = r.status === 'paid' ? `Remitted: ${formatDate(r.date_remitted)}` : `Due: ${formatDate(r.due_date)}`;
        
        return `
          <div class="bk-card" onclick='openRemitDetailById(${r.id})' style="margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; align-items:flex-start;">
              <div>
                <div style="font-size:13px; font-weight:700; color:var(--txt-primary);">${r.provider_name}</div>
                <div style="font-size:11px; color:var(--txt-muted); margin-top:2px;">${r.service_type || 'Worker'} · ${r.reference_no}</div>
                <div style="font-size:11px; color:var(--txt-muted); margin-top:1px;">${dateText}</div>
              </div>
              <div style="display:flex; flex-direction:column; align-items:flex-end; gap:6px;">
                <span class="${statusClass}" style="font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px;">${statusLabel}</span>
                <div style="font-size:13px; font-weight:800; color:var(--teal);">${php(r.amount_due)}</div>
              </div>
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:8px; border-top:1px solid var(--border-col); padding-top:8px;">
              <span style="font-size:11px; font-weight:700; color:#ea580c; display:flex; align-items:center; gap:3px; cursor:pointer;">
                View Details <i class="bi bi-chevron-right" style="font-size:10px;"></i>
              </span>
            </div>
          </div>
        `;
      }).join('');
    } catch (err) {
      console.error(err);
      body.innerHTML = '<div class="empty-state"><p>Error loading remittances.</p></div>';
    }
  }

  function formatDate(dateStr) {
    if (!dateStr || dateStr === '-') return '-';
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  function openRemitDetailById(id) {
    const remit = remittancesCache.find(r => r.id == id);
    if (remit) {
      openRemitDetail(remit);
    }
  }

  async function openRemitDetail(r) {
    const modal = document.getElementById('remitDetailOl');
    const badge = document.getElementById('remitDtlStatusBadge');
    
    const statusClass = r.status === 'paid' ? 'badge-green' : (r.status === 'overdue' ? 'badge-red' : (r.status === 'submitted' ? 'badge-blue' : 'badge-amber'));
    const statusLabel = r.status.charAt(0).toUpperCase() + r.status.slice(1);
    
    badge.className = statusClass;
    badge.textContent = statusLabel;
    badge.style.fontSize = '10px';
    badge.style.padding = '2px 8px';
    badge.style.borderRadius = '20px';

    // Receipt HTML
    let receiptHtml = '';
    if (r.receipt_path) {
      receiptHtml = `
        <div class="detail-row" style="align-items:flex-start; flex-direction:column; gap:8px; margin-top:12px; padding:10px 16px;">
          <span class="detail-lbl" style="font-weight:700;">GCash Receipt Uploaded</span>
          <div style="text-align:center; width:100%;">
            <img src="../${r.receipt_path}" alt="Receipt" style="max-height:220px; max-width:100%; border-radius:8px; cursor:zoom-in; border:1px solid var(--border-col);" onclick="openImagePreview('../${r.receipt_path}', 'Remittance Receipt')">
            <div style="font-size:10px; color:var(--txt-muted); margin-top:4px;"><i class="bi bi-zoom-in"></i> Click image to zoom</div>
          </div>
        </div>
      `;
    }

    // Action buttons for submitted remittance
    let actionButtons = '';
    if (r.status === 'submitted') {
      actionButtons = `
        <div style="display:flex; gap:10px; margin-top:16px; padding:0 16px;">
          <button class="btn-p" style="flex:1;" onclick="verifyRemittance(${r.id}, 'approve')">
            <i class="bi bi-check-circle-fill"></i> Approve
          </button>
          <button class="btn-danger" style="flex:1; background:#ef4444; border:none; border-radius:12px; color:#fff; font-weight:700; cursor:pointer; padding:12px;" onclick="verifyRemittance(${r.id}, 'reject')">
            <i class="bi bi-x-circle-fill"></i> Reject
          </button>
        </div>
      `;
    }

    // Load history for this specific provider (last 3 payments)
    let historyHtml = '<div style="font-size:11px; color:var(--txt-muted); padding:10px 16px;">No past remittance records.</div>';
    const pastRemits = remittancesCache.filter(rem => rem.provider_id === r.provider_id && rem.id !== r.id).slice(0, 3);
    if (pastRemits.length > 0) {
      historyHtml = pastRemits.map(h => {
        const hStatusClass = h.status === 'paid' ? 'badge-green' : (h.status === 'overdue' ? 'badge-red' : (h.status === 'submitted' ? 'badge-blue' : 'badge-amber'));
        const hStatusLabel = h.status.charAt(0).toUpperCase() + h.status.slice(1);
        const hDate = h.status === 'paid' ? `Paid on ${formatDate(h.date_remitted)}` : `Due ${formatDate(h.due_date)}`;
        return `
          <div class="detail-row" style="padding:10px 16px;">
            <div>
              <div style="font-size:12px; font-weight:700; color:var(--txt-primary);">${h.reference_no}</div>
              <div style="font-size:10px; color:var(--txt-muted); margin-top:1px;">${hDate}</div>
            </div>
            <div style="text-align:right;">
              <div style="font-size:12px; font-weight:800; color:var(--txt-primary);">${php(h.amount_due)}</div>
              <span class="${hStatusClass}" style="font-size:9px; font-weight:700; padding:1px 6px; border-radius:10px;">${hStatusLabel}</span>
            </div>
          </div>
        `;
      }).join('');
    }

    document.getElementById('remitDetailBody').innerHTML = `
      <div style="background:linear-gradient(135deg, rgba(234,88,12,0.08), rgba(245,166,35,0.04)); border-radius:18px; padding:16px; margin:8px 16px 16px; border:1px solid #ffedd5;">
        <div style="font-size:12px; font-weight:700; color:#c2410c; text-transform:uppercase; letter-spacing:0.3px;">Provider Info</div>
        <div style="font-size:16px; font-weight:800; color:var(--txt-primary); margin-top:4px;">${r.provider_name}</div>
        <div style="font-size:12px; color:var(--txt-muted); margin-top:2px;">Specialty: <strong>${r.service_type || 'Worker'}</strong></div>
        <div style="font-size:12px; color:var(--txt-muted); margin-top:1px;">Email: ${r.provider_email}</div>
        <div style="font-size:12px; color:var(--txt-muted); margin-top:1px;">Phone: ${r.provider_phone}</div>
      </div>
      
      <div class="sec-ttl" style="margin-bottom:8px; font-size:14px; padding:0 16px;">Payment details</div>
      <div class="card" style="margin:0 16px 16px;">
        <div class="detail-row"><span class="detail-lbl">Reference Number</span><span class="detail-val">${r.reference_no}</span></div>
        <div class="detail-row"><span class="detail-lbl">Amount Due</span><span class="detail-val" style="color:var(--txt-primary);">${php(r.amount_due)}</span></div>
        <div class="detail-row"><span class="detail-lbl">Amount Paid</span><span class="detail-val" style="color:var(--teal);">${php(r.amount_paid)}</span></div>
        <div class="detail-row"><span class="detail-lbl">Due Date</span><span class="detail-val">${formatDate(r.due_date)}</span></div>
        <div class="detail-row"><span class="detail-lbl">Date Remitted</span><span class="detail-val">${formatDate(r.date_remitted)}</span></div>
        <div class="detail-row"><span class="detail-lbl">Payment Method</span><span class="detail-val">${r.payment_method || '-'}</span></div>
        <div class="detail-row"><span class="detail-lbl">Payment Period</span><span class="detail-val">Daily</span></div>
      </div>

      ${receiptHtml}
      ${actionButtons}

      <div class="sec-ttl" style="margin:16px 0 8px; font-size:14px; padding:0 16px;">Remittance History</div>
      <div class="card" style="margin:0 16px 20px;">
        ${historyHtml}
      </div>
    `;
    openSheet('remitDetailOl');
  }

  // ── In-App Remittance Confirm Dialogs ─────────────────────────────────────
  let _remitPendingId   = null;
  let _remitPendingAction = null;

  function verifyRemittance(remitId, verifyAction) {
    _remitPendingId     = remitId;
    _remitPendingAction = verifyAction;
    if (verifyAction === 'reject') {
      document.getElementById('remitRejectReasonInput').value = '';
      document.getElementById('remitRejectConfirmOl').classList.add('on');
    } else {
      document.getElementById('remitApproveConfirmOl').classList.add('on');
    }
  }

  function closeRemitApproveConfirm() {
    document.getElementById('remitApproveConfirmOl').classList.remove('on');
    _remitPendingId = null;
  }

  function closeRemitRejectConfirm() {
    document.getElementById('remitRejectConfirmOl').classList.remove('on');
    _remitPendingId = null;
  }

  async function submitRemitApprove() {
    if (!_remitPendingId) return;
    const btn = document.getElementById('remitApproveOkBtn');
    btn.disabled = true;
    btn.textContent = 'Approving...';
    try {
      const response = await api('remittances', 'verify', fd({
        remittance_id: _remitPendingId,
        verify_action: 'approve',
        notes: ''
      }));
      btn.disabled = false;
      btn.textContent = 'Approve';
      closeRemitApproveConfirm();
      if (response.success) {
        toast(response.message || 'Remittance approved successfully.', 's');
        closeSheet('remitDetailOl');
        loadAdminRemittances();
      } else {
        toast(response.message || 'Approval failed.', 'e');
      }
    } catch (err) {
      btn.disabled = false;
      btn.textContent = 'Approve';
      console.error(err);
      toast('An error occurred. Please try again.', 'e');
    }
  }

  async function submitRemitReject() {
    const reason = document.getElementById('remitRejectReasonInput').value.trim();
    if (!reason) {
      toast('Please enter a rejection reason.', 'e');
      document.getElementById('remitRejectReasonInput').focus();
      return;
    }
    if (!_remitPendingId) return;
    const btn = document.getElementById('remitRejectOkBtn');
    btn.disabled = true;
    btn.textContent = 'Rejecting...';
    try {
      const response = await api('remittances', 'verify', fd({
        remittance_id: _remitPendingId,
        verify_action: 'reject',
        notes: reason
      }));
      btn.disabled = false;
      btn.textContent = 'Reject';
      closeRemitRejectConfirm();
      if (response.success) {
        toast(response.message || 'Remittance rejected.', 's');
        closeSheet('remitDetailOl');
        loadAdminRemittances();
      } else {
        toast(response.message || 'Rejection failed.', 'e');
      }
    } catch (err) {
      btn.disabled = false;
      btn.textContent = 'Reject';
      console.error(err);
      toast('An error occurred. Please try again.', 'e');
    }
  }

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      ['remitSheetOl', 'remitDetailOl'].forEach(id => {
        const el = document.getElementById(id);
        if (el && el.classList.contains('on')) {
          closeSheet(id);
        }
      });
    }
  });

  // Poll on page load and every 2 minutes
  pollQrRequestCount();
  setInterval(pollQrRequestCount, 120000);
</script>

</body>

</html>
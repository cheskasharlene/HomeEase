<?php
session_name('homeease_admin'); // Prevents conflict with homeeasev2 session
session_start();
if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit;
}
$adminName  = htmlspecialchars($_SESSION['user_name'] ?? 'Admin');
$adminEmail = htmlspecialchars($_SESSION['user_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>HomeEase — Admin Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    /* ══════════════════════════════════════
       VARIABLES & RESET
    ══════════════════════════════════════ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --teal:        #0d9488;
      --teal-dark:   #0f766e;
      --teal-light:  #f0fdfa;
      --teal-mid:    #ccfbf1;
      --slate:       #0f172a;
      --slate-mid:   #1e293b;
      --slate-light: #334155;
      --white:       #ffffff;
      --bg:          #f1f5f9;
      --card-bg:     #ffffff;
      --border:      #e2e8f0;
      --txt:         #1e293b;
      --txt-muted:   #64748b;
      --txt-light:   #94a3b8;
      --success:     #10b981;
      --warn:        #f59e0b;
      --danger:      #ef4444;
      --info:        #3b82f6;
      --purple:      #8b5cf6;
      --sb-width:    240px;
      --top-height:  64px;
    }
    body.dark {
      --bg:        #0a1628;
      --card-bg:   #0f2035;
      --border:    #1e3a5f;
      --txt:       #e2e8f0;
      --txt-muted: #94a3b8;
      --txt-light: #64748b;
      --teal-light:#042f2e;
      --teal-mid:  #134e4a;
      --slate:     #020b18;
      --slate-mid: #0a1628;
    }
    html, body { height: 100%; font-family: 'Nunito', sans-serif; font-size: 14px; background: var(--bg); color: var(--txt); }
    a { text-decoration: none; color: inherit; }
    button { font-family: inherit; cursor: pointer; }
    input, select, textarea { font-family: inherit; }

    /* ══════════════════════════════════════
       LAYOUT
    ══════════════════════════════════════ */
    .layout {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* ── Sidebar ── */
    .sidebar {
      width: var(--sb-width);
      background: var(--slate);
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      position: relative;
      z-index: 10;
    }
    .sidebar::after {
      content: '';
      position: absolute;
      right: 0; top: 0; bottom: 0;
      width: 1px;
      background: rgba(255,255,255,.06);
    }

    .sb-logo {
      padding: 20px 20px 16px;
      border-bottom: 1px solid rgba(255,255,255,.07);
    }
    .logo-row {
      display: flex; align-items: center; gap: 10px;
    }
    .logo-ico {
      width: 38px; height: 38px;
      background: var(--teal);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(13,148,136,.4);
    }
    .logo-ico svg { width: 22px; height: 22px; }
    .logo-nm {
      font-family: 'Poppins', sans-serif;
      font-size: 17px; font-weight: 800;
      color: #fff;
    }
    .logo-nm span { color: #5eead4; }
    .logo-tag {
      font-size: 10px; color: #475569;
      font-weight: 700; letter-spacing: .04em;
      text-transform: uppercase;
      margin-top: 6px;
    }

    .sb-nav {
      flex: 1;
      padding: 12px 10px;
      overflow-y: auto;
    }
    .sb-section-label {
      font-size: 10px; font-weight: 800;
      color: #475569; letter-spacing: .08em;
      text-transform: uppercase;
      padding: 16px 10px 6px;
    }
    .nav-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px;
      border-radius: 10px;
      color: #94a3b8;
      font-size: 13px; font-weight: 700;
      cursor: pointer;
      transition: background .15s, color .15s;
      margin-bottom: 2px;
      user-select: none;
    }
    .nav-item i { font-size: 16px; width: 20px; text-align: center; flex-shrink: 0; }
    .nav-item:hover { background: rgba(255,255,255,.07); color: #e2e8f0; }
    .nav-item.active {
      background: rgba(13,148,136,.18);
      color: #5eead4;
    }
    .nav-item.active i { color: var(--teal); }
    .nav-badge {
      margin-left: auto;
      background: var(--teal);
      color: #fff;
      font-size: 10px; font-weight: 800;
      padding: 2px 7px;
      border-radius: 100px;
    }

    .sb-footer {
      padding: 14px;
      border-top: 1px solid rgba(255,255,255,.07);
    }
    .admin-pill {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px;
      background: rgba(255,255,255,.05);
      border-radius: 10px;
      margin-bottom: 8px;
    }
    .admin-avatar {
      width: 34px; height: 34px;
      background: linear-gradient(135deg, var(--teal), var(--teal-dark));
      border-radius: 9px;
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; font-weight: 800; color: #fff;
      flex-shrink: 0;
    }
    .admin-info .admin-nm { font-size: 13px; font-weight: 800; color: #e2e8f0; }
    .admin-info .admin-role { font-size: 10px; color: #5eead4; font-weight: 700; text-transform: uppercase; }
    .sb-actions {
      display: flex; gap: 6px;
    }
    .sb-action-btn {
      flex: 1;
      padding: 8px;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 8px;
      color: #94a3b8;
      font-size: 12px;
      display: flex; align-items: center; justify-content: center; gap: 5px;
      transition: all .15s;
    }
    .sb-action-btn:hover { background: rgba(255,255,255,.12); color: #e2e8f0; }
    .sb-action-btn.danger:hover { background: rgba(239,68,68,.15); color: #fca5a5; border-color: rgba(239,68,68,.3); }

    /* ── Main area ── */
    .main {
      flex: 1;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /* ── Topbar ── */
    .topbar {
      height: var(--top-height);
      background: var(--card-bg);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center;
      padding: 0 28px;
      gap: 16px;
      flex-shrink: 0;
    }
    .tb-breadcrumb {
      flex: 1;
    }
    .tb-section {
      font-size: 11px; color: var(--txt-muted);
      font-weight: 700; text-transform: uppercase;
      letter-spacing: .05em;
    }
    .tb-title {
      font-family: 'Poppins', sans-serif;
      font-size: 20px; font-weight: 800;
      color: var(--txt);
      line-height: 1.1;
    }
    .tb-right { display: flex; align-items: center; gap: 10px; }
    .tb-btn {
      width: 38px; height: 38px;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      color: var(--txt-muted); font-size: 16px;
      cursor: pointer; transition: all .15s;
    }
    .tb-btn:hover { background: var(--teal-light); color: var(--teal); border-color: var(--teal-mid); }
    .tb-search {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 8px 14px;
      color: var(--txt-muted);
    }
    .tb-search input {
      border: none; background: none; outline: none;
      font-size: 13px; color: var(--txt);
      width: 180px;
    }
    .tb-search input::placeholder { color: var(--txt-muted); }

    /* ── Content area ── */
    .content {
      flex: 1;
      overflow-y: auto;
      padding: 28px;
    }

    /* ── Panels ── */
    .panel { display: none; }
    .panel.active { display: block; }

    /* ══════════════════════════════════════
       COMPONENTS
    ══════════════════════════════════════ */

    /* KPI Cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }
    .kpi-card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 20px;
      display: flex; align-items: center; gap: 16px;
      cursor: pointer;
      transition: box-shadow .2s, transform .2s, border-color .2s;
    }
    .kpi-card:hover {
      box-shadow: 0 6px 24px rgba(0,0,0,.08);
      transform: translateY(-2px);
      border-color: var(--teal);
    }
    .kpi-icon {
      width: 52px; height: 52px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; flex-shrink: 0;
    }
    .kpi-icon.teal { background: #f0fdfa; color: var(--teal); }
    .kpi-icon.green { background: #f0fdf4; color: #16a34a; }
    .kpi-icon.amber { background: #fffbeb; color: #d97706; }
    .kpi-icon.blue  { background: #eff6ff; color: #2563eb; }
    .kpi-icon.purple{ background: #faf5ff; color: #7c3aed; }
    .kpi-val {
      font-family: 'Poppins', sans-serif;
      font-size: 26px; font-weight: 800;
      color: var(--txt);
      line-height: 1;
    }
    .kpi-lbl { font-size: 12px; color: var(--txt-muted); font-weight: 700; margin-top: 4px; }

    /* Cards */
    .card {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
    }
    .card-header {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .card-title {
      font-family: 'Poppins', sans-serif;
      font-size: 15px; font-weight: 700;
      color: var(--txt);
    }
    .card-body { padding: 20px; }

    /* Grid layouts */
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px; }

    /* Tables */
    .tbl-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; }
    thead tr { background: var(--bg); }
    th {
      padding: 12px 16px;
      text-align: left;
      font-size: 11px; font-weight: 800;
      color: var(--txt-muted);
      text-transform: uppercase;
      letter-spacing: .05em;
      white-space: nowrap;
    }
    td {
      padding: 13px 16px;
      border-top: 1px solid var(--border);
      font-size: 13px;
      color: var(--txt);
      vertical-align: middle;
    }
    tr:hover td { background: var(--teal-light); }

    /* Badges */
    .badge {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 3px 10px;
      border-radius: 100px;
      font-size: 11px; font-weight: 800;
      white-space: nowrap;
    }
    .badge.pending   { background: #fffbeb; color: #d97706; }
    .badge.progress  { background: #eff6ff; color: #2563eb; }
    .badge.done      { background: #f0fdf4; color: #16a34a; }
    .badge.cancelled { background: #fef2f2; color: #dc2626; }
    .badge.active    { background: #f0fdf4; color: #16a34a; }
    .badge.inactive  { background: #f8fafc; color: #94a3b8; }
    .badge.available { background: #f0fdf4; color: #16a34a; }
    .badge.busy      { background: #eff6ff; color: #2563eb; }
    .badge.unavailable{background: #fef2f2; color: #dc2626; }

    /* Action buttons */
    .act-row { display: flex; gap: 6px; align-items: center; }
    .act-btn {
      width: 30px; height: 30px;
      border-radius: 8px;
      border: 1px solid var(--border);
      background: var(--card-bg);
      color: var(--txt-muted);
      font-size: 13px;
      display: flex; align-items: center; justify-content: center;
      transition: all .15s;
    }
    .act-btn:hover { border-color: var(--teal); color: var(--teal); background: var(--teal-light); }
    .act-btn.del:hover { border-color: var(--danger); color: var(--danger); background: #fef2f2; }
    .act-btn.tog:hover { border-color: var(--info); color: var(--info); background: #eff6ff; }

    /* Search + filter bar */
    .filter-bar {
      display: flex; gap: 10px; align-items: center;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .search-wrap {
      display: flex; align-items: center; gap: 8px;
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 9px 14px;
      flex: 1; min-width: 200px;
    }
    .search-wrap i { color: var(--txt-muted); font-size: 14px; }
    .search-wrap input {
      border: none; background: none; outline: none;
      font-size: 13px; color: var(--txt); width: 100%;
    }
    .search-wrap input::placeholder { color: var(--txt-muted); }

    .tab-pills { display: flex; gap: 6px; }
    .tab-pill {
      padding: 8px 14px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: var(--card-bg);
      font-size: 12px; font-weight: 700;
      color: var(--txt-muted);
      cursor: pointer;
      transition: all .15s;
      white-space: nowrap;
    }
    .tab-pill:hover { border-color: var(--teal); color: var(--teal); }
    .tab-pill.active {
      background: var(--teal);
      color: #fff;
      border-color: var(--teal);
    }

    .btn-primary {
      padding: 9px 18px;
      background: var(--teal);
      color: #fff;
      border: none; border-radius: 10px;
      font-size: 13px; font-weight: 700;
      display: flex; align-items: center; gap: 6px;
      transition: background .15s, box-shadow .15s;
      white-space: nowrap;
    }
    .btn-primary:hover { background: var(--teal-dark); box-shadow: 0 4px 14px rgba(13,148,136,.3); }
    .btn-outline {
      padding: 9px 18px;
      background: var(--card-bg);
      color: var(--txt-muted);
      border: 1px solid var(--border);
      border-radius: 10px;
      font-size: 13px; font-weight: 700;
      display: flex; align-items: center; gap: 6px;
      transition: all .15s;
      white-space: nowrap;
    }
    .btn-outline:hover { border-color: var(--teal); color: var(--teal); }

    /* Empty state */
    .empty-state {
      text-align: center; padding: 60px 20px;
      color: var(--txt-muted);
    }
    .empty-state i { font-size: 48px; opacity: .3; display: block; margin-bottom: 14px; }
    .empty-state p { font-size: 14px; font-weight: 600; }

    /* Loading */
    .loading-row td {
      text-align: center; padding: 40px;
      color: var(--txt-muted); font-weight: 600;
    }

    /* Avatar */
    .av {
      width: 34px; height: 34px;
      border-radius: 9px;
      background: linear-gradient(135deg, var(--teal), var(--teal-dark));
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-size: 13px; font-weight: 800;
      flex-shrink: 0;
    }
    .av.purple { background: linear-gradient(135deg, #8b5cf6, #6d28d9); }
    .av.amber  { background: linear-gradient(135deg, #f59e0b, #d97706); }

    /* Donut chart */
    .donut-section {
      display: flex; gap: 20px; align-items: center;
    }
    .donut-svg-wrap { flex-shrink: 0; }
    .donut-svg { width: 120px; height: 120px; transform: rotate(-90deg); }
    .donut-legend { flex: 1; display: flex; flex-direction: column; gap: 8px; }
    .legend-row {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; font-weight: 700; color: var(--txt-muted);
    }
    .legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .legend-val { margin-left: auto; font-weight: 800; color: var(--txt); }

    /* Analytics KPI block */
    .analytics-block {
      background: var(--card-bg);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 22px;
      margin-bottom: 20px;
    }
    .analytics-block h3 {
      font-family: 'Poppins', sans-serif;
      font-size: 13px; font-weight: 700;
      color: var(--txt-muted);
      text-transform: uppercase; letter-spacing: .04em;
      margin-bottom: 14px;
    }
    .rev-track {
      background: var(--border);
      border-radius: 100px; height: 7px;
      overflow: hidden; margin-top: 5px;
    }
    .rev-fill {
      height: 100%; background: var(--teal);
      border-radius: 100px;
      transition: width .5s ease;
    }

    /* ══════════════════════════════════════
       MODAL
    ══════════════════════════════════════ */
    .modal-overlay {
      position: fixed; inset: 0; z-index: 1000;
      background: rgba(15,23,42,.55);
      display: flex; align-items: center; justify-content: center;
      opacity: 0; pointer-events: none;
      transition: opacity .2s;
      backdrop-filter: blur(3px);
    }
    .modal-overlay.open { opacity: 1; pointer-events: all; }
    .modal-box {
      background: var(--card-bg);
      border-radius: 20px;
      width: 100%; max-width: 520px;
      max-height: 90vh;
      display: flex; flex-direction: column;
      box-shadow: 0 25px 60px rgba(0,0,0,.2);
      transform: translateY(20px) scale(.97);
      transition: transform .2s;
      overflow: hidden;
    }
    .modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 24px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .modal-title {
      font-family: 'Poppins', sans-serif;
      font-size: 16px; font-weight: 800;
      color: var(--txt);
    }
    .modal-close {
      width: 32px; height: 32px;
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      color: var(--txt-muted); font-size: 15px;
      cursor: pointer; transition: all .15s;
    }
    .modal-close:hover { background: #fef2f2; color: var(--danger); border-color: #fecaca; }
    .modal-body {
      padding: 24px;
      overflow-y: auto;
      flex: 1;
    }
    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid var(--border);
      display: flex; gap: 10px; justify-content: flex-end;
      flex-shrink: 0;
    }

    /* Form elements inside modal */
    .fg { margin-bottom: 16px; }
    .fg-row { display: grid; gap: 12px; margin-bottom: 16px; }
    .fg-row.col2 { grid-template-columns: 1fr 1fr; }
    .fg-row.col3 { grid-template-columns: 1fr 1fr 80px; }
    .fl {
      display: block; font-size: 12px; font-weight: 800;
      color: var(--txt-muted); text-transform: uppercase;
      letter-spacing: .04em; margin-bottom: 6px;
    }
    .fi {
      width: 100%; padding: 10px 14px;
      border: 1.5px solid var(--border);
      border-radius: 9px;
      font-family: 'Nunito', sans-serif;
      font-size: 13px; color: var(--txt);
      background: var(--bg);
      outline: none; transition: border-color .2s, box-shadow .2s;
    }
    .fi:focus {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(13,148,136,.1);
      background: var(--card-bg);
    }
    select.fi { cursor: pointer; }

    .toggle-group {
      display: flex; gap: 6px; flex-wrap: wrap;
    }
    .toggle-opt {
      padding: 7px 14px;
      border-radius: 8px;
      border: 1.5px solid var(--border);
      background: var(--bg);
      font-size: 12px; font-weight: 700;
      color: var(--txt-muted);
      cursor: pointer; transition: all .15s;
      text-transform: capitalize;
    }
    .toggle-opt.on {
      background: var(--teal);
      color: #fff; border-color: var(--teal);
    }

    /* Detail view in modal */
    .detail-grid {
      display: grid; grid-template-columns: 140px 1fr;
      gap: 0;
    }
    .detail-grid .dl { padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 12px; font-weight: 800; color: var(--txt-muted); text-transform: uppercase; letter-spacing: .04em; }
    .detail-grid .dv { padding: 10px 0; border-bottom: 1px solid var(--border); font-size: 13px; color: var(--txt); font-weight: 600; }

    /* ══════════════════════════════════════
       TOAST
    ══════════════════════════════════════ */
    #toastBox {
      position: fixed; bottom: 28px; right: 28px;
      z-index: 9999;
      display: flex; flex-direction: column; gap: 10px;
    }
    .toast-n {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 18px;
      border-radius: 12px;
      font-size: 13px; font-weight: 700;
      box-shadow: 0 8px 24px rgba(0,0,0,.15);
      animation: slideUp .3s ease;
    }
    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .toast-n.s { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .toast-n.e { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .toast-n.i { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

    /* ══════════════════════════════════════
       SCROLLBAR
    ══════════════════════════════════════ */
    ::-webkit-scrollbar { width: 6px; height: 6px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 100px; }
    ::-webkit-scrollbar-thumb:hover { background: var(--txt-muted); }

    /* ══════════════════════════════════════
       OVERVIEW SPECIFIC
    ══════════════════════════════════════ */
    .service-icon-cell { font-size: 20px; }
    .price-tag { font-family: 'Poppins', sans-serif; font-weight: 800; color: var(--teal); }
    .customer-cell { display: flex; align-items: center; gap: 10px; }
    .status-mini-grid {
      display: grid; grid-template-columns: repeat(2,1fr); gap: 12px;
    }
    .status-mini {
      background: var(--bg);
      border-radius: 12px; padding: 14px;
      text-align: center;
    }
    .status-mini .v {
      font-family: 'Poppins', sans-serif;
      font-size: 22px; font-weight: 800;
    }
    .status-mini .l { font-size: 11px; font-weight: 700; color: var(--txt-muted); margin-top: 2px; }

    /* Responsive */
    @media (max-width: 1100px) {
      .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 900px) {
      .sidebar { width: 60px; }
      .sidebar .logo-nm, .sidebar .logo-tag, .sidebar .nav-item span,
      .sidebar .sb-section-label, .sidebar .admin-info,
      .sidebar .sb-action-btn span { display: none; }
      .nav-item { justify-content: center; padding: 12px; }
      .admin-pill { padding: 8px; justify-content: center; }
    }
  </style>
</head>
<body>
<div id="toastBox"></div>

<!-- ══ MODAL ══ -->
<div class="modal-overlay" id="modalOverlay" onclick="handleOverlayClick(event)">
  <div class="modal-box">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Modal</div>
      <button class="modal-close" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer" id="modalFooter"></div>
  </div>
</div>

<!-- ══ LAYOUT ══ -->
<div class="layout">

  <!-- ── Sidebar ── -->
  <aside class="sidebar">
    <div class="sb-logo">
      <div class="logo-row">
        <div class="logo-ico">
          <svg viewBox="0 0 54 54" fill="none">
            <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white"/>
            <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)"/>
          </svg>
        </div>
        <div>
          <div class="logo-nm">Home<span>Ease</span></div>
        </div>
      </div>
      <div class="logo-tag">Admin Dashboard</div>
    </div>

    <nav class="sb-nav">
      <div class="sb-section-label">Main</div>
      <div class="nav-item active" onclick="goPanel('overview')" id="nav-overview">
        <i class="bi bi-grid-fill"></i><span>Overview</span>
      </div>
      <div class="nav-item" onclick="goPanel('bookings')" id="nav-bookings">
        <i class="bi bi-calendar-check-fill"></i><span>Bookings</span>
        <span class="nav-badge" id="navBadgePending" style="display:none;"></span>
      </div>
      <div class="nav-item" onclick="goPanel('users')" id="nav-users">
        <i class="bi bi-people-fill"></i><span>Users</span>
      </div>
      <div class="nav-item" onclick="goPanel('technicians')" id="nav-technicians">
        <i class="bi bi-person-badge-fill"></i><span>Technicians</span>
      </div>

      <div class="sb-section-label">Manage</div>
      <div class="nav-item" onclick="goPanel('services')" id="nav-services">
        <i class="bi bi-grid-3x3-gap-fill"></i><span>Services</span>
      </div>
      <div class="nav-item" onclick="goPanel('offers')" id="nav-offers">
        <i class="bi bi-tag-fill"></i><span>Special Offers</span>
      </div>
      <div class="nav-item" onclick="goPanel('analytics')" id="nav-analytics">
        <i class="bi bi-bar-chart-fill"></i><span>Analytics</span>
      </div>
    </nav>

    <div class="sb-footer">
      <div class="admin-pill">
        <div class="admin-avatar"><?= strtoupper(substr($adminName, 0, 1)) ?></div>
        <div class="admin-info">
          <div class="admin-nm"><?= $adminName ?></div>
          <div class="admin-role">Administrator</div>
        </div>
      </div>
      <div class="sb-actions">
        <button class="sb-action-btn" onclick="toggleDark()" title="Toggle dark mode">
          <i class="bi bi-moon-fill" id="dmIcon"></i><span>Theme</span>
        </button>
        <a href="logout.php" class="sb-action-btn danger" title="Logout">
          <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
      </div>
    </div>
  </aside>

  <!-- ── Main ── -->
  <div class="main">
    <!-- Topbar -->
    <header class="topbar">
      <div class="tb-breadcrumb">
        <div class="tb-section" id="tbSection">Dashboard</div>
        <div class="tb-title" id="tbTitle">Overview</div>
      </div>
      <div class="tb-right">
        <button class="tb-btn" onclick="refreshCurrent()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
        <div id="tbActions"></div>
      </div>
    </header>

    <!-- Content -->
    <div class="content">

      <!-- ════════════ OVERVIEW PANEL ════════════ -->
      <div class="panel active" id="panel-overview">
        <div class="kpi-grid">
          <div class="kpi-card" onclick="goPanel('bookings')">
            <div class="kpi-icon teal"><i class="bi bi-calendar-check-fill"></i></div>
            <div><div class="kpi-val" id="kBk">—</div><div class="kpi-lbl">Total Bookings</div></div>
          </div>
          <div class="kpi-card" onclick="goPanel('users')">
            <div class="kpi-icon green"><i class="bi bi-people-fill"></i></div>
            <div><div class="kpi-val" id="kUsers">—</div><div class="kpi-lbl">Registered Users</div></div>
          </div>
          <div class="kpi-card" onclick="goPanel('technicians')">
            <div class="kpi-icon amber"><i class="bi bi-person-badge-fill"></i></div>
            <div><div class="kpi-val" id="kTechs">—</div><div class="kpi-lbl">Active Technicians</div></div>
          </div>
          <div class="kpi-card" onclick="goPanel('analytics')">
            <div class="kpi-icon blue"><i class="bi bi-currency-exchange"></i></div>
            <div><div class="kpi-val" id="kRev">—</div><div class="kpi-lbl">Revenue (Done)</div></div>
          </div>
        </div>

        <div class="grid-2">
          <!-- Recent bookings table -->
          <div class="card" style="grid-column:span 1;">
            <div class="card-header">
              <div class="card-title">Recent Bookings</div>
              <button class="btn-outline" style="padding:6px 12px;font-size:12px;" onclick="goPanel('bookings')">View all →</button>
            </div>
            <div class="tbl-wrap">
              <table id="recentBkTable">
                <thead><tr>
                  <th>Service</th><th>Customer</th><th>Date</th><th>Status</th><th>Price</th>
                </tr></thead>
                <tbody id="recentBkBody">
                  <tr class="loading-row"><td colspan="5"><i class="bi bi-arrow-clockwise"></i> Loading...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Status breakdown + donut -->
          <div class="card">
            <div class="card-header">
              <div class="card-title">Booking Status</div>
            </div>
            <div class="card-body">
              <div class="donut-section">
                <div class="donut-svg-wrap">
                  <svg class="donut-svg" viewBox="0 0 36 36" id="donutSvg">
                    <circle cx="18" cy="18" r="15.9155" fill="transparent" stroke="var(--border)" stroke-width="3.5"/>
                  </svg>
                </div>
                <div class="donut-legend" id="donutLegend"></div>
              </div>
              <div class="status-mini-grid" style="margin-top:16px;" id="statusGrid">
                <div class="status-mini"><div class="v" style="color:#f59e0b;" id="sPending">—</div><div class="l">Pending</div></div>
                <div class="status-mini"><div class="v" style="color:#3b82f6;" id="sProgress">—</div><div class="l">In Progress</div></div>
                <div class="status-mini"><div class="v" style="color:#10b981;" id="sDone">—</div><div class="l">Done</div></div>
                <div class="status-mini"><div class="v" style="color:#ef4444;" id="sCancelled">—</div><div class="l">Cancelled</div></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════ BOOKINGS PANEL ════════════ -->
      <div class="panel" id="panel-bookings">
        <div class="filter-bar">
          <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="bkSearch" placeholder="Search by customer, service, address..." oninput="loadBookings()">
          </div>
          <div class="tab-pills" id="bkTabPills">
            <div class="tab-pill active" onclick="setBkTab('all',this)">All</div>
            <div class="tab-pill" onclick="setBkTab('pending',this)">Pending</div>
            <div class="tab-pill" onclick="setBkTab('progress',this)">In Progress</div>
            <div class="tab-pill" onclick="setBkTab('done',this)">Done</div>
            <div class="tab-pill" onclick="setBkTab('cancelled',this)">Cancelled</div>
          </div>
        </div>
        <div class="card">
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>#</th><th>Service</th><th>Customer</th><th>Technician</th>
                <th>Date</th><th>Status</th><th>Price</th><th>Actions</th>
              </tr></thead>
              <tbody id="bkBody">
                <tr class="loading-row"><td colspan="8"><i class="bi bi-arrow-clockwise"></i> Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════ USERS PANEL ════════════ -->
      <div class="panel" id="panel-users">
        <div class="filter-bar">
          <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" id="userSearch" placeholder="Search users by name or email..." oninput="loadUsers()">
          </div>
        </div>
        <div class="card">
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>User</th><th>Email</th><th>Phone</th>
                <th>Bookings</th><th>Status</th><th>Joined</th><th>Actions</th>
              </tr></thead>
              <tbody id="usersBody">
                <tr class="loading-row"><td colspan="7"><i class="bi bi-arrow-clockwise"></i> Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════ TECHNICIANS PANEL ════════════ -->
      <div class="panel" id="panel-technicians">
        <div class="filter-bar">
          <div class="search-wrap" style="max-width:340px;">
            <i class="bi bi-search"></i>
            <input type="text" id="techSearch" placeholder="Search technicians..." oninput="filterTechs()">
          </div>
          <button class="btn-primary" onclick="openTechForm(null)">
            <i class="bi bi-plus-lg"></i> Add Technician
          </button>
        </div>
        <div class="card">
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>Name</th><th>Specialty</th><th>Phone</th>
                <th>Availability</th><th>Rating</th><th>Jobs Done</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="techBody">
                <tr class="loading-row"><td colspan="8"><i class="bi bi-arrow-clockwise"></i> Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════ SERVICES PANEL ════════════ -->
      <div class="panel" id="panel-services">
        <div class="filter-bar">
          <div style="flex:1;"></div>
          <button class="btn-primary" onclick="openSvcForm(null)">
            <i class="bi bi-plus-lg"></i> Add Service
          </button>
        </div>
        <div class="card">
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>Service</th><th>Description</th><th>Flat Rate</th>
                <th>Hourly Rate</th><th>Min Hours</th><th>Pricing</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="svcBody">
                <tr class="loading-row"><td colspan="8"><i class="bi bi-arrow-clockwise"></i> Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════ OFFERS PANEL ════════════ -->
      <div class="panel" id="panel-offers">
        <div class="filter-bar">
          <div style="flex:1;"></div>
          <button class="btn-primary" onclick="openOfferForm(null)">
            <i class="bi bi-plus-lg"></i> Add Offer
          </button>
        </div>
        <div class="card">
          <div class="tbl-wrap">
            <table>
              <thead><tr>
                <th>Title</th><th>Code</th><th>Discount</th><th>Min Spend</th>
                <th>Uses</th><th>Expires</th><th>Status</th><th>Actions</th>
              </tr></thead>
              <tbody id="offersBody">
                <tr class="loading-row"><td colspan="8"><i class="bi bi-arrow-clockwise"></i> Loading...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ════════════ ANALYTICS PANEL ════════════ -->
      <div class="panel" id="panel-analytics">
        <div id="analyticsBody"></div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /layout -->

<script>
/* ══════════════════════════════════════
   CONFIG & UTILITIES
══════════════════════════════════════ */
const API = 'admin_api.php';
let curPanel = 'overview', curBkTab = 'all';
let _allTechs = [];

// Dark mode
(function() {
  if (localStorage.getItem('he_admin_dark') === '1') {
    document.body.classList.add('dark');
    document.getElementById('dmIcon').className = 'bi bi-sun-fill';
  }
})();
function toggleDark() {
  document.body.classList.toggle('dark');
  const on = document.body.classList.contains('dark');
  localStorage.setItem('he_admin_dark', on ? '1' : '0');
  document.getElementById('dmIcon').className = on ? 'bi bi-sun-fill' : 'bi bi-moon-fill';
}

async function api(params, postData = null) {
  const qs = new URLSearchParams(params).toString();
  let opts = {};
  if (postData) {
    const body = new URLSearchParams();
    if (params.action) body.append('action', params.action);
    Object.entries(postData).forEach(([k,v]) => body.append(k, v ?? ''));
    opts = { method: 'POST', body };
  }
  try {
    const res = await fetch(API + '?' + qs, opts);
    const text = await res.text();
    try { return JSON.parse(text); }
    catch(e) { console.error('Non-JSON:', text); return {success:false,message:'Server error'}; }
  } catch(e) { return {success:false,message:e.message}; }
}

function toast(msg, type='s') {
  const t = document.createElement('div');
  t.className = `toast-n ${type}`;
  const icon = type==='s' ? 'check-circle-fill' : type==='e' ? 'exclamation-circle-fill' : 'info-circle-fill';
  t.innerHTML = `<i class="bi bi-${icon}"></i>${msg}`;
  document.getElementById('toastBox').appendChild(t);
  setTimeout(() => { t.style.opacity='0'; t.style.transition='opacity .3s'; setTimeout(()=>t.remove(),300); }, 2800);
}

function esc(s) {
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ══════════════════════════════════════
   NAVIGATION
══════════════════════════════════════ */
const PANEL_META = {
  overview:    { section:'Dashboard', title:'Overview' },
  bookings:    { section:'Manage',    title:'Bookings' },
  users:       { section:'Manage',    title:'Users' },
  technicians: { section:'Manage',    title:'Technicians' },
  services:    { section:'Manage',    title:'Services & Pricing' },
  offers:      { section:'Manage',    title:'Special Offers' },
  analytics:   { section:'Business',  title:'Analytics' },
};

function goPanel(name) {
  document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));
  document.getElementById('panel-' + name)?.classList.add('active');
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  document.getElementById('nav-' + name)?.classList.add('active');
  const meta = PANEL_META[name] || {};
  document.getElementById('tbSection').textContent = meta.section || name;
  document.getElementById('tbTitle').textContent   = meta.title   || name;
  document.getElementById('tbActions').innerHTML   = '';
  curPanel = name;
  if (name === 'overview')     { loadStats(); loadRecentBookings(); }
  if (name === 'bookings')     loadBookings();
  if (name === 'users')        loadUsers();
  if (name === 'technicians')  loadTechnicians();
  if (name === 'services')     loadServices();
  if (name === 'offers')       loadOffers();
  if (name === 'analytics')    loadAnalytics();
}

function refreshCurrent() { goPanel(curPanel); }

/* ══════════════════════════════════════
   MODAL
══════════════════════════════════════ */
function openModal(title, bodyHtml, footerHtml='') {
  document.getElementById('modalTitle').textContent = title;
  document.getElementById('modalBody').innerHTML = bodyHtml;
  document.getElementById('modalFooter').innerHTML = footerHtml;
  document.getElementById('modalOverlay').classList.add('open');
}
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }
function handleOverlayClick(e) { if (e.target === document.getElementById('modalOverlay')) closeModal(); }

function setToggleOpt(el, hiddenId, val) {
  el.closest('.toggle-group').querySelectorAll('.toggle-opt').forEach(o=>o.classList.remove('on'));
  el.classList.add('on');
  document.getElementById(hiddenId).value = val;
}

/* ══════════════════════════════════════
   STATS
══════════════════════════════════════ */
async function loadStats() {
  const d = await api({action:'stats'});
  if (!d.success) return;
  const s = d.stats;
  document.getElementById('kBk').textContent    = s.total_bookings;
  document.getElementById('kUsers').textContent = s.total_users;
  document.getElementById('kTechs').textContent = s.total_techs;
  document.getElementById('kRev').textContent   = '₱' + parseFloat(s.total_revenue).toLocaleString('en-PH',{maximumFractionDigits:0});
  document.getElementById('sPending').textContent   = s.pending;
  document.getElementById('sProgress').textContent  = s.in_progress;
  document.getElementById('sDone').textContent      = s.done;
  document.getElementById('sCancelled').textContent = s.cancelled;
  if (s.pending > 0) {
    document.getElementById('navBadgePending').style.display = '';
    document.getElementById('navBadgePending').textContent = s.pending;
  }
  renderDonut([
    {val:s.done,        color:'#0d9488', label:'Done',       count:s.done},
    {val:s.in_progress, color:'#3b82f6', label:'In Progress',count:s.in_progress},
    {val:s.pending,     color:'#f59e0b', label:'Pending',    count:s.pending},
    {val:s.cancelled,   color:'#ef4444', label:'Cancelled',  count:s.cancelled},
  ], s.total_bookings||1);
}

function renderDonut(segs, total) {
  const svg = document.getElementById('donutSvg');
  const leg = document.getElementById('donutLegend');
  let offset=25, html='<circle cx="18" cy="18" r="15.9155" fill="transparent" stroke="var(--border)" stroke-width="3.5"/>';
  segs.forEach(s=>{
    const dash=(s.val/total)*100;
    if(dash>0){
      html+=`<circle cx="18" cy="18" r="15.9155" fill="transparent" stroke="${s.color}" stroke-width="3.5" stroke-dasharray="${dash} ${100-dash}" stroke-dashoffset="${-(offset-100)}"/>`;
      offset+=dash;
    }
  });
  svg.innerHTML=html;
  leg.innerHTML=segs.map(s=>`
    <div class="legend-row">
      <div class="legend-dot" style="background:${s.color};"></div>
      <span>${s.label}</span>
      <span class="legend-val">${s.count}</span>
    </div>`).join('');
}

/* ══════════════════════════════════════
   BOOKINGS
══════════════════════════════════════ */
const SVC_IC = {'Cleaning':'🧹','Plumbing':'🔧','Electrical':'⚡','Painting':'🖌️','Appliance Repair':'🔩','Gardening':'🌿'};

async function loadRecentBookings() {
  const tbody = document.getElementById('recentBkBody');
  tbody.innerHTML = '<tr class="loading-row"><td colspan="5">Loading...</td></tr>';
  const d = await api({action:'get_bookings', status:'all', search:''});
  if (!d.success || !d.bookings.length) {
    tbody.innerHTML = '<tr class="loading-row"><td colspan="5"><i class="bi bi-calendar-x"></i> No bookings yet</td></tr>'; return;
  }
  tbody.innerHTML = d.bookings.slice(0,8).map(b=>`
    <tr onclick="openBkDetail(${b.id})" style="cursor:pointer;">
      <td><span style="font-size:18px;">${SVC_IC[b.service]||'🏠'}</span> <strong>${esc(b.service)}</strong></td>
      <td>${esc(b.customer_name||'—')}</td>
      <td>${esc(b.date)}</td>
      <td><span class="badge ${b.status}">${b.status}</span></td>
      <td class="price-tag">₱${parseFloat(b.price||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
    </tr>`).join('');
}

function setBkTab(s, el) {
  document.querySelectorAll('#bkTabPills .tab-pill').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  curBkTab = s;
  loadBookings();
}

async function loadBookings() {
  const tbody = document.getElementById('bkBody');
  const q = document.getElementById('bkSearch')?.value || '';
  tbody.innerHTML = '<tr class="loading-row"><td colspan="8">Loading...</td></tr>';
  const d = await api({action:'get_bookings', status:curBkTab, search:q});
  if (!d.success || !d.bookings.length) {
    tbody.innerHTML = '<tr class="loading-row"><td colspan="8"><i class="bi bi-calendar-x"></i> No bookings found</td></tr>'; return;
  }
  tbody.innerHTML = d.bookings.map(b=>`
    <tr>
      <td style="color:var(--txt-muted);font-size:12px;">#${b.id}</td>
      <td><span style="font-size:17px;">${SVC_IC[b.service]||'🏠'}</span> <strong>${esc(b.service)}</strong></td>
      <td>
        <div class="customer-cell">
          <div class="av" style="width:28px;height:28px;font-size:11px;">${(b.customer_name||'?')[0].toUpperCase()}</div>
          ${esc(b.customer_name||'—')}
        </div>
      </td>
      <td>${esc(b.technician_name||'—')}</td>
      <td>${esc(b.date)} <span style="color:var(--txt-muted);font-size:11px;">${esc(b.time_slot||'')}</span></td>
      <td><span class="badge ${b.status}">${b.status}</span></td>
      <td class="price-tag">₱${parseFloat(b.price||0).toLocaleString('en-PH',{minimumFractionDigits:2})}</td>
      <td>
        <div class="act-row">
          <button class="act-btn" onclick="openBkDetail(${b.id})" title="View"><i class="bi bi-eye-fill"></i></button>
          <button class="act-btn" onclick="openBkEdit(${b.id})" title="Edit"><i class="bi bi-pencil-fill"></i></button>
          <button class="act-btn del" onclick="deleteBk(${b.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

async function openBkDetail(id) {
  const d = await api({action:'get_bookings', status:'all', search:''});
  const b = d.bookings?.find(x=>x.id==id); if(!b) return;
  const price = parseFloat(b.price||0).toLocaleString('en-PH',{minimumFractionDigits:2});
  openModal(`Booking #${b.id} — ${b.service}`,
    `<div style="font-size:32px;margin-bottom:16px;">${SVC_IC[b.service]||'🏠'}
      <span class="badge ${b.status}" style="font-size:12px;vertical-align:middle;">${b.status}</span>
    </div>
    <div class="detail-grid">
      <div class="dl">Customer</div><div class="dv">${esc(b.customer_name||'—')}</div>
      <div class="dl">Service</div><div class="dv">${esc(b.service)}</div>
      <div class="dl">Date</div><div class="dv">${esc(b.date)} ${esc(b.time_slot||'')}</div>
      <div class="dl">Address</div><div class="dv">${esc(b.address||'—')}</div>
      <div class="dl">Technician</div><div class="dv">${esc(b.technician_name||'Not assigned')}</div>
      <div class="dl">Pricing</div><div class="dv">${b.pricing_type==='hourly'?(b.hours+'hr'):'Flat rate'} · ₱${price}</div>
      ${b.notes?`<div class="dl">Notes</div><div class="dv">${esc(b.notes)}</div>`:''}
    </div>`,
    `<button class="btn-outline" onclick="closeModal()">Close</button>
     <button class="btn-outline" onclick="closeModal();openBkEdit(${b.id})"><i class="bi bi-pencil-fill"></i> Edit</button>
     <button class="btn-primary" style="background:#ef4444;" onclick="closeModal();deleteBk(${b.id})"><i class="bi bi-trash3-fill"></i> Delete</button>`
  );
}

async function openBkEdit(id) {
  const [bd, td] = await Promise.all([
    api({action:'get_bookings', status:'all', search:''}),
    api({action:'get_technicians'})
  ]);
  const b = bd.bookings?.find(x=>x.id==id); if(!b) return;
  const techs = td.technicians || [];
  openModal(`Edit Booking #${id}`,
    `<div class="fg"><label class="fl">Status</label>
      <div class="toggle-group">
        ${['pending','progress','done','cancelled'].map(s=>`<div class="toggle-opt${b.status===s?' on':''}" onclick="setToggleOpt(this,'bkSt','${s}')">${s}</div>`).join('')}
      </div><input type="hidden" id="bkSt" value="${b.status}">
    </div>
    <div class="fg"><label class="fl">Technician</label>
      <select class="fi" id="bkTech">
        <option value="">— No technician —</option>
        ${techs.map(t=>`<option value="${t.id}"${b.technician_id==t.id?' selected':''}>${esc(t.name)} (${esc(t.specialty)})</option>`).join('')}
      </select>
    </div>
    <div class="fg-row col2">
      <div class="fg"><label class="fl">Price (₱)</label><input class="fi" id="bkPrice" type="number" value="${b.price}"></div>
      <div class="fg"><label class="fl">Notes</label><input class="fi" id="bkNotes" value="${esc(b.notes||'')}"></div>
    </div>`,
    `<button class="btn-outline" onclick="closeModal()">Cancel</button>
     <button class="btn-primary" onclick="saveBk(${id})"><i class="bi bi-check-lg"></i> Save Changes</button>`
  );
}

async function saveBk(id) {
  const ok = await api({action:'update_booking'},{
    id, status:document.getElementById('bkSt').value,
    price:document.getElementById('bkPrice').value,
    notes:document.getElementById('bkNotes').value,
    technician_id:document.getElementById('bkTech').value
  });
  if (ok.success) { toast('Booking updated','s'); closeModal(); loadBookings(); loadRecentBookings(); loadStats(); }
  else toast('Update failed','e');
}

async function deleteBk(id) {
  if (!confirm('Delete this booking? This cannot be undone.')) return;
  const ok = await api({action:'delete_booking'},{id});
  if (ok.success) { toast('Booking deleted','s'); loadBookings(); loadRecentBookings(); loadStats(); }
  else toast('Delete failed','e');
}

/* ══════════════════════════════════════
   USERS
══════════════════════════════════════ */
async function loadUsers() {
  const tbody = document.getElementById('usersBody');
  const q = document.getElementById('userSearch')?.value || '';
  tbody.innerHTML = '<tr class="loading-row"><td colspan="7">Loading...</td></tr>';
  const d = await api({action:'get_users', search:q});
  if (!d.success || !d.users.length) {
    tbody.innerHTML = '<tr class="loading-row"><td colspan="7"><i class="bi bi-people"></i> No users found</td></tr>'; return;
  }
  tbody.innerHTML = d.users.map(u=>`
    <tr>
      <td>
        <div class="customer-cell">
          <div class="av">${(u.name||'U')[0].toUpperCase()}</div>
          <div><div style="font-weight:700;">${esc(u.name)}</div><div style="font-size:11px;color:var(--txt-muted);">${u.role==='admin'?'<span style="color:var(--teal);font-weight:800;">admin</span>':'user'}</div></div>
        </div>
      </td>
      <td>${esc(u.email)}</td>
      <td>${esc(u.phone||'—')}</td>
      <td><strong>${u.booking_count}</strong></td>
      <td><span class="badge ${u.status}">${u.status}</span></td>
      <td style="color:var(--txt-muted);font-size:12px;">${u.created_at?.split(' ')[0]||'—'}</td>
      <td>
        <div class="act-row">
          <button class="act-btn tog" onclick="toggleUser(${u.id})" title="Toggle status">
            <i class="bi bi-toggle-${u.status==='active'?'on':'off'}" style="font-size:15px;"></i>
          </button>
          <button class="act-btn del" onclick="deleteUser(${u.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

async function toggleUser(id) {
  const ok = await api({action:'toggle_user'},{id});
  if (ok.success) { toast('User status updated','s'); loadUsers(); loadStats(); }
  else toast('Failed','e');
}
async function deleteUser(id) {
  if (!confirm('Delete this user? All their bookings will remain.')) return;
  const ok = await api({action:'delete_user'},{id});
  if (ok.success) { toast('User deleted','s'); loadUsers(); loadStats(); }
  else toast('Failed','e');
}

/* ══════════════════════════════════════
   TECHNICIANS
══════════════════════════════════════ */
async function loadTechnicians() {
  const tbody = document.getElementById('techBody');
  tbody.innerHTML = '<tr class="loading-row"><td colspan="8">Loading...</td></tr>';
  const d = await api({action:'get_technicians'});
  if (!d.success) { tbody.innerHTML=`<tr class="loading-row"><td colspan="8">${d.message}</td></tr>`; return; }
  _allTechs = d.technicians || [];
  renderTechs(_allTechs);
}

function filterTechs() {
  const q = document.getElementById('techSearch')?.value.toLowerCase()||'';
  renderTechs(_allTechs.filter(t=>(t.name+t.specialty).toLowerCase().includes(q)));
}

function renderTechs(techs) {
  const tbody = document.getElementById('techBody');
  if (!techs.length) {
    tbody.innerHTML='<tr class="loading-row"><td colspan="8"><i class="bi bi-person-badge"></i> No technicians found</td></tr>'; return;
  }
  tbody.innerHTML = techs.map(t=>`
    <tr>
      <td>
        <div class="customer-cell">
          <div class="av purple">${(t.name||'T')[0]}</div>
          <strong>${esc(t.name)}</strong>
        </div>
      </td>
      <td>${esc(t.specialty)}</td>
      <td>${esc(t.phone||'—')}</td>
      <td><span class="badge ${t.availability}">${t.availability}</span></td>
      <td>⭐ ${parseFloat(t.rating||0).toFixed(1)}</td>
      <td>${t.jobs_done||0}</td>
      <td><span class="badge ${t.status}">${t.status}</span></td>
      <td>
        <div class="act-row">
          <button class="act-btn" onclick="openTechForm(${t.id})" title="Edit"><i class="bi bi-pencil-fill"></i></button>
          <button class="act-btn del" onclick="deleteTech(${t.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

async function openTechForm(id) {
  let t = null;
  if (id) { const d = await api({action:'get_technicians'}); t=d.technicians?.find(x=>x.id==id); }
  const svcResp = await api({action:'get_services'});
  let specialties = (svcResp.services||[]).map(s=>s.name);
  if (!specialties.length) specialties = ['Cleaning','Plumbing','Electrical','Painting','Appliance Repair','Gardening','Carpentry','Aircon Service'];
  if (t && t.specialty && !specialties.includes(t.specialty)) specialties.unshift(t.specialty);

  openModal(id ? 'Edit Technician' : 'Add Technician',
    `<div class="fg"><label class="fl">Full Name</label>
       <input class="fi" id="tName" value="${t?esc(t.name):''}" placeholder="Technician full name">
    </div>
    <div class="fg-row col2">
      <div class="fg"><label class="fl">Specialty</label>
        <select class="fi" id="tSpec">
          <option value="">— Select —</option>
          ${specialties.map(s=>`<option value="${s}"${t&&t.specialty===s?' selected':''}>${s}</option>`).join('')}
        </select>
      </div>
      <div class="fg"><label class="fl">Custom Specialty</label>
        <input class="fi" id="tSpecCustom" placeholder="Or type here">
      </div>
    </div>
    <div class="fg-row col2">
      <div class="fg"><label class="fl">Phone</label>
        <input class="fi" id="tPhone" value="${t?esc(t.phone||''):''}" placeholder="09XXXXXXXXX">
      </div>
      <div class="fg"><label class="fl">Status</label>
        <select class="fi" id="tStatus">
          <option value="active"${!t||t.status==='active'?' selected':''}>Active</option>
          <option value="inactive"${t&&t.status==='inactive'?' selected':''}>Inactive</option>
        </select>
      </div>
    </div>
    <div class="fg"><label class="fl">Availability</label>
      <div class="toggle-group">
        ${['available','busy','unavailable'].map(s=>`<div class="toggle-opt${(!t&&s==='available')||(t&&t.availability===s)?' on':''}" onclick="setToggleOpt(this,'tAv','${s}')">${s}</div>`).join('')}
      </div><input type="hidden" id="tAv" value="${t?t.availability:'available'}">
    </div>`,
    `<button class="btn-outline" onclick="closeModal()">Cancel</button>
     <button class="btn-primary" onclick="saveTech(${id||'null'})"><i class="bi bi-check-lg"></i> Save</button>`
  );
}

async function saveTech(id) {
  const name = document.getElementById('tName').value.trim();
  if (!name) { toast('Name is required','e'); return; }
  const custom = document.getElementById('tSpecCustom')?.value.trim();
  const specialty = custom || document.getElementById('tSpec').value;
  if (!specialty) { toast('Please select or enter a specialty','e'); return; }
  const isNew = (!id || id==='null');
  const ok = await api({action:'save_technician'},{
    id: isNew?0:id, name, specialty,
    phone:document.getElementById('tPhone').value.trim(),
    availability:document.getElementById('tAv').value,
    status:document.getElementById('tStatus').value,
  });
  if (ok.success) { toast(isNew?'Technician added ✅':'Technician updated ✅','s'); closeModal(); loadTechnicians(); loadStats(); }
  else toast(ok.message||'Failed','e');
}

async function deleteTech(id) {
  if (!confirm('Remove this technician?')) return;
  const ok = await api({action:'delete_technician'},{id});
  if (ok.success) { toast('Removed','s'); loadTechnicians(); loadStats(); }
  else toast('Failed','e');
}

/* ══════════════════════════════════════
   SERVICES
══════════════════════════════════════ */
async function loadServices() {
  const tbody = document.getElementById('svcBody');
  tbody.innerHTML='<tr class="loading-row"><td colspan="8">Loading...</td></tr>';
  const d = await api({action:'get_services'});
  if (!d.success || !d.services.length) {
    tbody.innerHTML='<tr class="loading-row"><td colspan="8"><i class="bi bi-grid"></i> No services yet</td></tr>'; return;
  }
  tbody.innerHTML = d.services.map(s=>`
    <tr style="${s.active?'':'opacity:.55'}">
      <td>
        <div class="customer-cell">
          <div style="font-size:24px;">${s.icon}</div>
          <strong>${esc(s.name)}</strong>
        </div>
      </td>
      <td style="color:var(--txt-muted);font-size:12px;max-width:180px;">${esc(s.description||'—')}</td>
      <td class="price-tag">₱${parseFloat(s.flat_rate).toLocaleString()}</td>
      <td class="price-tag">₱${parseFloat(s.hourly_rate).toLocaleString()}/hr</td>
      <td>${s.min_hours}h</td>
      <td style="text-transform:capitalize;">${s.pricing_type}</td>
      <td><span class="badge ${s.active?'active':'inactive'}">${s.active?'Active':'Inactive'}</span></td>
      <td>
        <div class="act-row">
          <button class="act-btn" onclick="openSvcForm(${s.id})" title="Edit"><i class="bi bi-pencil-fill"></i></button>
          <button class="act-btn tog" onclick="toggleSvc(${s.id})" title="Toggle"><i class="bi bi-toggle-${s.active?'on':'off'}" style="font-size:15px;"></i></button>
          <button class="act-btn del" onclick="deleteSvc(${s.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </td>
    </tr>`).join('');
}

async function openSvcForm(id) {
  let s = null;
  if (id) { const d=await api({action:'get_services'}); s=d.services?.find(x=>x.id==id); }
  openModal(id?'Edit Service':'Add Service',
    `<div class="fg-row" style="grid-template-columns:1fr 70px; display:grid; gap:12px;">
       <div class="fg"><label class="fl">Service Name</label><input class="fi" id="sName" value="${s?esc(s.name):''}" placeholder="e.g. Cleaning"></div>
       <div class="fg"><label class="fl">Icon</label><input class="fi" id="sIcon" value="${s?s.icon:'🔧'}" style="font-size:20px;text-align:center;padding:8px;"></div>
    </div>
    <div class="fg"><label class="fl">Description</label><input class="fi" id="sDesc" value="${s?esc(s.description||''):''}" placeholder="Short description"></div>
    <div class="fg"><label class="fl">Pricing Type</label>
      <div class="toggle-group">
        ${['flat','hourly','both'].map(p=>`<div class="toggle-opt${(!s&&p==='both')||(s&&s.pricing_type===p)?' on':''}" onclick="setToggleOpt(this,'sPtype','${p}')">${p}</div>`).join('')}
      </div><input type="hidden" id="sPtype" value="${s?s.pricing_type:'both'}">
    </div>
    <div class="fg-row col3">
      <div class="fg"><label class="fl">Flat Rate (₱)</label><input class="fi" id="sFlat" type="number" value="${s?s.flat_rate:0}"></div>
      <div class="fg"><label class="fl">Hourly Rate (₱)</label><input class="fi" id="sHourly" type="number" value="${s?s.hourly_rate:0}"></div>
      <div class="fg"><label class="fl">Min Hrs</label><input class="fi" id="sMinH" type="number" value="${s?s.min_hours:1}" min="1" max="12"></div>
    </div>
    <div class="fg"><label class="fl">Status</label>
      <div class="toggle-group">
        ${[['Active','1'],['Inactive','0']].map(([l,v])=>`<div class="toggle-opt${(!s&&v==='1')||(s&&(s.active?'1':'0')===v)?' on':''}" onclick="setToggleOpt(this,'sActive','${v}')">${l}</div>`).join('')}
      </div><input type="hidden" id="sActive" value="${s?(s.active?'1':'0'):'1'}">
    </div>`,
    `<button class="btn-outline" onclick="closeModal()">Cancel</button>
     <button class="btn-primary" onclick="saveSvc(${id||'null'})"><i class="bi bi-check-lg"></i> Save</button>`
  );
}

async function saveSvc(id) {
  const name = document.getElementById('sName').value.trim();
  if (!name) { toast('Service name is required','e'); return; }
  const isNew = (!id||id==='null');
  const ok = await api({action:'save_service'},{
    id:isNew?0:id, name,
    icon:document.getElementById('sIcon').value.trim()||'🔧',
    description:document.getElementById('sDesc').value.trim(),
    pricing_type:document.getElementById('sPtype').value,
    flat_rate:document.getElementById('sFlat').value||0,
    hourly_rate:document.getElementById('sHourly').value||0,
    min_hours:document.getElementById('sMinH').value||1,
    active:document.getElementById('sActive').value,
  });
  if (ok.success) { toast(isNew?'Service added ✅':'Service updated ✅','s'); closeModal(); loadServices(); }
  else toast(ok.message||'Failed','e');
}

async function toggleSvc(id) {
  const ok = await api({action:'toggle_service'},{id});
  if (ok.success) { toast('Status toggled','s'); loadServices(); }
  else toast('Failed','e');
}
async function deleteSvc(id) {
  if (!confirm('Delete this service?')) return;
  const ok = await api({action:'delete_service'},{id});
  if (ok.success) { toast('Deleted','s'); loadServices(); }
  else toast('Failed','e');
}

/* ══════════════════════════════════════
   OFFERS
══════════════════════════════════════ */
async function loadOffers() {
  const tbody = document.getElementById('offersBody');
  tbody.innerHTML='<tr class="loading-row"><td colspan="8">Loading...</td></tr>';
  const d = await api({action:'get_offers'});
  if (!d.success) { tbody.innerHTML=`<tr class="loading-row"><td colspan="8">${d.message||'Failed'}</td></tr>`; return; }
  if (!d.offers.length) {
    tbody.innerHTML='<tr class="loading-row"><td colspan="8"><i class="bi bi-tag"></i> No offers yet</td></tr>'; return;
  }
  const now = new Date();
  tbody.innerHTML = d.offers.map(o=>{
    const exp = o.expires_at ? new Date(o.expires_at) : null;
    const expired = exp && exp < now;
    const disc = o.discount_type==='percent' ? `${o.discount_value}% OFF` : `₱${parseFloat(o.discount_value).toLocaleString()} OFF`;
    const statusLabel = expired?'Expired':o.active?'Active':'Inactive';
    const statusClass = expired?'inactive':o.active?'active':'inactive';
    return `<tr style="${!o.active||expired?'opacity:.6':''}">
      <td><strong>${esc(o.title)}</strong><div style="font-size:11px;color:var(--txt-muted);">${esc(o.description||'')}</div></td>
      <td><span style="background:var(--teal-mid);color:var(--teal);padding:3px 10px;border-radius:8px;font-weight:800;font-size:12px;font-family:'Poppins',sans-serif;">${esc(o.code)}</span></td>
      <td><strong style="color:var(--teal);">${disc}</strong></td>
      <td>${o.min_booking_price>0?'₱'+parseFloat(o.min_booking_price).toLocaleString():'Any'}</td>
      <td>${o.used_count}${o.max_uses>0?'/'+o.max_uses:''}</td>
      <td style="font-size:12px;color:var(--txt-muted);">${o.expires_at?o.expires_at.split(' ')[0]:'Never'}</td>
      <td><span class="badge ${statusClass}">${statusLabel}</span></td>
      <td>
        <div class="act-row">
          <button class="act-btn" onclick="openOfferForm(${o.id})" title="Edit"><i class="bi bi-pencil-fill"></i></button>
          <button class="act-btn tog" onclick="toggleOffer(${o.id})" title="Toggle"><i class="bi bi-toggle-${o.active?'on':'off'}" style="font-size:15px;"></i></button>
          <button class="act-btn del" onclick="deleteOffer(${o.id})" title="Delete"><i class="bi bi-trash3-fill"></i></button>
        </div>
      </td>
    </tr>`;
  }).join('');
}

async function openOfferForm(id) {
  let o = null;
  if (id) { const d=await api({action:'get_offers'}); o=d.offers?.find(x=>x.id==id); }
  openModal(id?'Edit Offer':'Add Special Offer',
    `<div class="fg-row col2">
       <div class="fg"><label class="fl">Offer Title</label><input class="fi" id="oTitle" value="${o?esc(o.title):''}" placeholder="e.g. Summer Promo"></div>
       <div class="fg"><label class="fl">Promo Code</label><input class="fi" id="oCode" value="${o?o.code:''}" placeholder="SUMMER20" style="text-transform:uppercase;" oninput="this.value=this.value.toUpperCase()"></div>
    </div>
    <div class="fg"><label class="fl">Description</label><input class="fi" id="oDesc" value="${o?esc(o.description||''):''}" placeholder="Short description for customers"></div>
    <div class="fg"><label class="fl">Discount Type</label>
      <div class="toggle-group">
        ${[['percent','% Percent'],['flat','₱ Fixed']].map(([v,l])=>`<div class="toggle-opt${(!o&&v==='percent')||(o&&o.discount_type===v)?' on':''}" onclick="setToggleOpt(this,'oType','${v}')">${l}</div>`).join('')}
      </div><input type="hidden" id="oType" value="${o?o.discount_type:'percent'}">
    </div>
    <div class="fg-row col2">
      <div class="fg"><label class="fl">Discount Value</label><input class="fi" id="oVal" type="number" value="${o?o.discount_value:10}" min="0"></div>
      <div class="fg"><label class="fl">Min Booking (₱)</label><input class="fi" id="oMin" type="number" value="${o?o.min_booking_price:0}" min="0" placeholder="0 = any"></div>
    </div>
    <div class="fg-row col2">
      <div class="fg"><label class="fl">Max Uses (0=unlimited)</label><input class="fi" id="oMaxUse" type="number" value="${o?o.max_uses:0}" min="0"></div>
      <div class="fg"><label class="fl">Expires (optional)</label><input class="fi" id="oExp" type="date" value="${o&&o.expires_at?o.expires_at.split(' ')[0]:''}"></div>
    </div>
    <div class="fg"><label class="fl">Status</label>
      <div class="toggle-group">
        ${[['Active','1'],['Inactive','0']].map(([l,v])=>`<div class="toggle-opt${(!o&&v==='1')||(o&&(o.active?'1':'0')===v)?' on':''}" onclick="setToggleOpt(this,'oActive','${v}')">${l}</div>`).join('')}
      </div><input type="hidden" id="oActive" value="${o?(o.active?'1':'0'):'1'}">
    </div>`,
    `<button class="btn-outline" onclick="closeModal()">Cancel</button>
     <button class="btn-primary" onclick="saveOffer(${id||'null'})"><i class="bi bi-check-lg"></i> Save Offer</button>`
  );
}

async function saveOffer(id) {
  const title = document.getElementById('oTitle').value.trim();
  const code  = document.getElementById('oCode').value.trim().toUpperCase();
  if (!title||!code) { toast('Title and code are required','e'); return; }
  const isNew = (!id||id==='null');
  const ok = await api({action:'save_offer'},{
    id:isNew?0:id, title, code,
    description:document.getElementById('oDesc').value.trim(),
    discount_type:document.getElementById('oType').value,
    discount_value:document.getElementById('oVal').value||0,
    min_booking_price:document.getElementById('oMin').value||0,
    max_uses:document.getElementById('oMaxUse').value||0,
    expires_at:document.getElementById('oExp').value||'',
    active:document.getElementById('oActive').value,
  });
  if (ok.success) { toast(isNew?'Offer created ✅':'Offer updated ✅','s'); closeModal(); loadOffers(); }
  else toast(ok.message||'Failed','e');
}

async function toggleOffer(id) {
  const ok = await api({action:'toggle_offer'},{id});
  if (ok.success) { toast('Offer toggled','s'); loadOffers(); }
  else toast('Failed','e');
}
async function deleteOffer(id) {
  if (!confirm('Delete this offer?')) return;
  const ok = await api({action:'delete_offer'},{id});
  if (ok.success) { toast('Offer deleted','s'); loadOffers(); }
  else toast('Failed','e');
}

/* ══════════════════════════════════════
   ANALYTICS
══════════════════════════════════════ */
async function loadAnalytics() {
  const el = document.getElementById('analyticsBody');
  el.innerHTML='<div class="empty-state"><i class="bi bi-arrow-clockwise"></i><p>Loading analytics...</p></div>';
  const [sd, bd] = await Promise.all([api({action:'stats'}), api({action:'get_bookings',status:'all',search:''})]);
  if (!sd.success) { el.innerHTML='<div class="empty-state"><p>Failed to load</p></div>'; return; }
  const s = sd.stats, bookings = bd.bookings||[];
  const svcCounts={};
  bookings.forEach(b=>{ svcCounts[b.service]=(svcCounts[b.service]||0)+1; });
  const topSvcs = Object.entries(svcCounts).sort((a,b)=>b[1]-a[1]).slice(0,6);
  const maxSvc  = topSvcs[0]?.[1]||1;
  const avgRevenue = s.done>0 ? (s.total_revenue/s.done) : 0;

  el.innerHTML=`
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-icon teal"><i class="bi bi-currency-exchange"></i></div>
      <div><div class="kpi-val">₱${parseFloat(s.total_revenue).toLocaleString('en-PH',{maximumFractionDigits:0})}</div><div class="kpi-lbl">Total Revenue</div></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon green"><i class="bi bi-receipt"></i></div>
      <div><div class="kpi-val">₱${parseFloat(avgRevenue).toLocaleString('en-PH',{maximumFractionDigits:0})}</div><div class="kpi-lbl">Avg per Booking</div></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon blue"><i class="bi bi-graph-up-arrow"></i></div>
      <div><div class="kpi-val">${s.done>0?(((s.done/s.total_bookings)*100).toFixed(1)+'%'):'0%'}</div><div class="kpi-lbl">Completion Rate</div></div>
    </div>
    <div class="kpi-card">
      <div class="kpi-icon purple"><i class="bi bi-people-fill"></i></div>
      <div><div class="kpi-val">${s.total_users}</div><div class="kpi-lbl">Registered Users</div></div>
    </div>
  </div>
  <div class="grid-2">
    <div class="analytics-block">
      <h3>📊 Booking Breakdown</h3>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;">
        ${[['✅ Done',s.done,'#0d9488'],['🔵 Progress',s.in_progress,'#3b82f6'],['⏳ Pending',s.pending,'#f59e0b'],['❌ Cancelled',s.cancelled,'#ef4444']].map(([l,v,c])=>`
          <div class="status-mini">
            <div class="v" style="color:${c};">${v}</div>
            <div class="l">${l}</div>
          </div>`).join('')}
      </div>
    </div>
    ${topSvcs.length?`<div class="analytics-block">
      <h3>🏆 Top Services by Bookings</h3>
      ${topSvcs.map((sv,i)=>`
        <div style="margin-bottom:14px;">
          <div style="display:flex;justify-content:space-between;margin-bottom:5px;">
            <span style="font-size:13px;font-weight:700;">${['🥇','🥈','🥉','4️⃣','5️⃣','6️⃣'][i]} ${sv[0]}</span>
            <span style="font-size:12px;font-weight:800;color:var(--teal);">${sv[1]} bookings</span>
          </div>
          <div class="rev-track"><div class="rev-fill" style="width:${(sv[1]/maxSvc*100).toFixed(0)}%;"></div></div>
        </div>`).join('')}
    </div>`:''}
  </div>`;
}

/* ══════════════════════════════════════
   INIT
══════════════════════════════════════ */
loadStats();
loadRecentBookings();
</script>
</body>
</html>

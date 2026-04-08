<?php
if (!isset($activePage)) $activePage = '';
?>
<nav class="admin-sidebar">
  <ul>
    <li class="<?= $activePage === 'overview' ? 'active' : '' ?>"><a href="overview.php"><i class="bi bi-grid-1x2-fill"></i> Overview</a></li>
    <li class="<?= $activePage === 'analytics' ? 'active' : '' ?>"><a href="analytics.php"><i class="bi bi-graph-up"></i> Analytics</a></li>
    <li class="<?= $activePage === 'bookings' ? 'active' : '' ?>"><a href="bookings.php"><i class="bi bi-calendar-check-fill"></i> Bookings</a></li>
    <li class="<?= $activePage === 'workers' ? 'active' : '' ?>"><a href="workers.php"><i class="bi bi-person-badge-fill"></i> Workers</a></li>
    <li class="<?= $activePage === 'users' ? 'active' : '' ?>"><a href="users.php"><i class="bi bi-people-fill"></i> Users</a></li>
    <li class="<?= $activePage === 'more' ? 'active' : '' ?>"><a href="more.php"><i class="bi bi-grid-fill"></i> More</a></li>
  </ul>
</nav>
<style>
.admin-sidebar { position:fixed;left:0;top:0;bottom:0;width:210px;background:var(--bg-card);padding:30px 0 0 0;z-index:100;box-shadow:2px 0 12px rgba(0,0,0,.04); }
.admin-sidebar ul { list-style:none;padding:0;margin:0; }
.admin-sidebar li { margin-bottom:6px; }
.admin-sidebar a { display:flex;align-items:center;gap:13px;padding:13px 24px;font-size:15px;font-weight:700;color:var(--txt-muted);text-decoration:none;border-radius:12px 0 0 12px;transition:background .18s,color .18s; }
.admin-sidebar li.active a, .admin-sidebar a:hover { background:var(--teal-bg);color:var(--teal); }
@media (max-width:900px) { .admin-sidebar { display:none; } }
body { margin-left:210px!important; }
</style>

<?php
$activePage = 'overview';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main style="padding:32px 24px 0 0;">
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
      <button class="hdr-btn" onclick="loadOverview()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
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
        <a href="bookings.php" style="font-size:12px;font-weight:700;color:var(--teal);">See all</a>
      </div>
      <div class="card" id="recentBookings">
        <div class="empty-state"><i class="bi bi-arrow-clockwise" style="animation:w-spin .9s linear infinite;"></i>
          <p>Loading...</p>
        </div>
      </div>
    </div>
    <div style="height:20px;"></div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
// ... JS for overview page (copy from admindashboard.js)
</script>

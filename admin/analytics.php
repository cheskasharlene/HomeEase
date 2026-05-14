<?php
$activePage = 'analytics';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main style="padding:32px 24px 0 0;">
  <div class="a-hdr">
    <div>
      <div class="a-greet">Business</div>
      <div class="a-ttl">Analytics</div>
    </div>
    <div class="a-hdr-right">
      <button class="hdr-btn" onclick="loadAnalytics()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
  </div>
  <div class="a-scroll" id="analytics-scroll" style="padding-top:8px;padding-bottom:90px;">
    <div class="an-cards-grid">
      <div class="an-chart-card service-dist-card">
        <div class="an-chart-ttl">Service Distribution</div>
        <div class="an-chart-sub">Bookings by service category</div>
        <div class="an-chart-canvas service-dist-canvas"><canvas id="chartServiceDist" height="220"></canvas></div>
      </div>
      <div class="an-chart-card weekly-revenue-card">
        <div class="an-chart-ttl">Weekly Revenue</div>
        <div class="an-chart-sub">Revenue from completed bookings (last 8 weeks)</div>
        <div class="an-chart-canvas"><canvas id="chartRevenue" height="220"></canvas></div>
      </div>
      <div class="an-chart-card full-span">
        <div class="an-chart-ttl">Top Performing Workers</div>
        <div class="an-chart-sub">Ranked by total jobs completed</div>
        <div id="anTopWorkers"></div>
      </div>
    </div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
// ... JS for analytics page (copy from admindashboard.js)
</script>

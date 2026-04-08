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
  <div class="a-scroll" id="analytics-scroll" style="padding-top:14px;padding-bottom:90px;">
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
      <div class="an-chart-card">
        <div class="an-chart-ttl">Booking Trends</div>
        <div class="an-chart-sub">Daily bookings over the last 30 days</div>
        <div class="an-chart-canvas"><canvas id="chartBookingTrend" height="180"></canvas></div>
      </div>
      <div class="an-chart-card">
        <div class="an-chart-ttl">Service Distribution</div>
        <div class="an-chart-sub">Bookings by service category</div>
        <div class="an-chart-canvas" style="max-width:280px;margin:0 auto;"><canvas id="chartServiceDist" height="220"></canvas></div>
      </div>
      <div class="an-chart-card full-span">
        <div class="an-chart-ttl">Weekly Revenue</div>
        <div class="an-chart-sub">Revenue from completed bookings (last 8 weeks)</div>
        <div class="an-chart-canvas"><canvas id="chartRevenue" height="180"></canvas></div>
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

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
async function loadOverview() {
  try {
    const res = await fetch('../api/admin_api.php?section=stats');
    const data = await res.json();
    if (!data.success) return;
    const s = data.stats;

    document.getElementById('st-users').textContent = s.total_users;
    document.getElementById('st-bookings').textContent = s.total_bookings;
    
    // Revenue formatting: e.g. ₱42.1k or ₱0.00
    const floatVal = parseFloat(s.total_revenue) || 0;
    if (floatVal >= 1000) {
      document.getElementById('st-revenue').textContent = '₱' + (floatVal / 1000).toFixed(1) + 'k';
    } else {
      document.getElementById('st-revenue').textContent = '₱' + floatVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    
    document.getElementById('st-workers').textContent = s.active_workers;
    document.getElementById('revTotal').textContent = '₱' + floatVal.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Revenue chart
    const chart = document.getElementById('revChart');
    const revRows = s.revenue_chart || [];
    if (revRows.length) {
      const max = Math.max(...revRows.map(r => parseFloat(r.rev)), 1);
      chart.innerHTML = revRows.map(r => {
        const h = Math.max(4, Math.round((parseFloat(r.rev) / max) * 60));
        return `<div class="rev-bar-item">
          <div class="rev-bar-fill" style="height:${h}px;" title="₱${parseFloat(r.rev).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}"></div>
          <div class="rev-bar-lbl">${r.mo}</div>
        </div>`;
      }).join('');
    } else {
      chart.innerHTML = '<div style="font-size:12px;color:var(--txt-muted);text-align:center;width:100%;padding:20px 0;">No revenue data yet</div>';
    }

    // Recent Bookings
    const recentBks = document.getElementById('recentBookings');
    if (recentBks && s.recent_bookings) {
      recentBks.innerHTML = s.recent_bookings.map(b => {
        const initial = (b.user_name || '?')[0].toUpperCase();
        return `
          <div class="list-item">
            <div class="li-av" style="font-size:13px; font-weight:800; background:var(--teal-mid); color:var(--teal-dark);">${initial}</div>
            <div class="li-body">
              <div class="li-name">${b.service}</div>
              <div class="li-sub">${b.user_name} · ${b.date}</div>
            </div>
            <div class="li-right" style="text-align: right; margin-right: 12px;">
              <div style="font-size:13px; font-weight:800; color:var(--teal);">Price: ₱${parseFloat(b.price).toLocaleString()}</div>
            </div>
            <div style="flex-shrink:0;">
              ${bookingStatusPill(b.status)}
            </div>
          </div>
        `;
      }).join('');
    }
  } catch (err) {
    console.error('Failed to load overview data: ', err);
  }
}

function bookingStatusPill(s) {
  const key = String(s || '').toLowerCase();
  const map = {
    pending: 'badge-amber',
    awaiting_payment: 'badge-blue',
    progress: 'badge-blue',
    done: 'badge-green',
    cancelled: 'badge-red'
  };
  const labels = {
    pending: 'Pending',
    awaiting_payment: 'Awaiting Payment',
    progress: 'In Progress',
    done: 'Done',
    cancelled: 'Cancelled'
  };
  return `<span class="${map[key] || 'badge-gray'}" style="font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 8px;">${labels[key] || key}</span>`;
}

window.addEventListener('DOMContentLoaded', () => {
  loadOverview();
});
</script>

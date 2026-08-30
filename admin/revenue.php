<?php
$activePage = 'revenue';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main style="padding:32px 24px 0 0;">
  <div class="a-hdr">
    <div>
      <div class="a-greet">Monitor</div>
      <div class="a-ttl">Revenue</div>
    </div>
    <div class="a-hdr-right">
      <button class="hdr-btn" onclick="initRevenueChart()" title="Refresh"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
  </div>

  <div class="a-scroll" id="revenue-scroll" style="padding:0 0 90px; overflow-x:hidden;">
    <!-- Metric summary cards -->
    <div class="stat-grid" style="margin-bottom:14px;">
      <div class="stat-card">
        <div class="stat-ic amber"><i class="bi bi-piggy-bank-fill"></i></div>
        <div>
          <div class="stat-val">₱42.1k</div>
          <div class="stat-lbl">Total Revenue</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-ic teal"><i class="bi bi-calendar3"></i></div>
        <div>
          <div class="stat-val">₱18.2k</div>
          <div class="stat-lbl">This Month</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-ic blue"><i class="bi bi-calendar2-week-fill"></i></div>
        <div>
          <div class="stat-val">₱4.3k</div>
          <div class="stat-lbl">This Week</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-ic green"><i class="bi bi-calendar-event-fill"></i></div>
        <div>
          <div class="stat-val">₱850.00</div>
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
            <div class="stat-val">₱3,400.00</div>
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
            <span style="font-size:12px; font-weight:800; color:var(--txt-primary);">₱38,750.00</span>
          </div>
          <div style="width:100%; height:8px; background:var(--bg-input); border-radius:4px; overflow:hidden;">
            <div style="width:92%; height:100%; background:var(--teal); border-radius:4px;"></div>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--txt-muted); margin-top:4px;">
            <span>10% commission fee per booking</span>
            <span>91.9% of total</span>
          </div>
        </div>
        <div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <div style="display:flex; align-items:center; gap:8px;">
              <div style="width:12px; height:12px; border-radius:50%; background:#2563eb;"></div>
              <span style="font-size:12px; font-weight:700; color:var(--txt-primary);">Platform Convenience Fees</span>
            </div>
            <span style="font-size:12px; font-weight:800; color:var(--txt-primary);">₱3,400.00</span>
          </div>
          <div style="width:100%; height:8px; background:var(--bg-input); border-radius:4px; overflow:hidden;">
            <div style="width:8%; height:100%; background:#2563eb; border-radius:4px;"></div>
          </div>
          <div style="display:flex; justify-content:space-between; font-size:10px; color:var(--txt-muted); margin-top:4px;">
            <span>Fixed service/booking fees</span>
            <span>8.1% of total</span>
          </div>
        </div>
      </div>
    </div>

    <!-- History -->
    <div class="sec-pad">
      <div class="sec-hdr">
        <div class="sec-ttl">Recent Revenue History</div>
      </div>
      <div class="card" id="revenueHistoryList">
        <!-- populated via js -->
      </div>
    </div>
  </div>
</main>

<style>
.stat-ic.red { background:#fee2e2; color:#dc2626; }
body.dark .stat-ic.red { background:#4c0519; color:#f43f5e; }
.rev-filter-btn { border: none; background: transparent; color: var(--txt-muted); font-size: 10px; font-weight: 700; padding: 5px 8px; border-radius: 8px; cursor: pointer; transition: all 0.15s; }
.rev-filter-btn.active { background: var(--bg-card); color: var(--teal); box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
// JavaScript code for desktop-view page
let revenueChartInstance = null;

function loadRevenue() {
  initRevenueChart();
  initRevenueHistory();
}

function initRevenueChart() {
  const ctx = document.getElementById('revenueAnalyticsChart');
  if (!ctx) return;

  const chartData = getRevenueChartData('daily');

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
}

function createChartGradient(ctx, isDark) {
  const c = ctx.getContext('2d');
  const gradient = c.createLinearGradient(0, 0, 0, 180);
  gradient.addColorStop(0, 'rgba(245, 166, 35, 0.4)');
  gradient.addColorStop(1, 'rgba(245, 166, 35, 0)');
  return gradient;
}

function getRevenueChartData(filter) {
  switch (filter) {
    case 'daily':
      return {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        data: [450, 750, 500, 950, 800, 1200, 850]
      };
    case 'weekly':
      return {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        data: [3800, 4200, 4800, 4350]
      };
    case 'monthly':
      return {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug'],
        data: [12500, 14000, 15800, 13200, 16500, 18250, 17100, 19400]
      };
    case 'yearly':
      return {
        labels: ['2023', '2024', '2025', '2026'],
        data: [120000, 185000, 245000, 310000]
      };
  }
}

function updateRevenueChart(filter, btn) {
  const buttons = btn.parentElement.querySelectorAll('.rev-filter-btn');
  buttons.forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  if (!revenueChartInstance) return;

  const chartData = getRevenueChartData(filter);
  revenueChartInstance.data.labels = chartData.labels;
  revenueChartInstance.data.datasets[0].data = chartData.data;
  revenueChartInstance.update();
}

function initRevenueHistory() {
  const listContainer = document.getElementById('revenueHistoryList');
  if (!listContainer) return;

  const dummyHistory = [
    { date: '2026-08-30', worker: 'Lance Austria', service: 'House Cleaner', amount: 1450, revenue: 145, status: 'paid' },
    { date: '2026-08-29', worker: 'John Doe', service: 'Plumber', amount: 800, revenue: 80, status: 'paid' },
    { date: '2026-08-28', worker: 'Maria Santos', service: 'Laundry', amount: 600, revenue: 60, status: 'paid' },
    { date: '2026-08-28', worker: 'Lance Austria', service: 'House Cleaner', amount: 750, revenue: 75, status: 'pending' },
    { date: '2026-08-27', worker: 'Juan Dela Cruz', service: 'Electrician', amount: 1200, revenue: 120, status: 'paid' },
    { date: '2026-08-26', worker: 'Jane Austria', service: 'House Cleaner', amount: 1450, revenue: 145, status: 'paid' }
  ];

  listContainer.innerHTML = dummyHistory.map(item => {
    const initial = (item.worker || '?')[0].toUpperCase();
    const commissionLabel = '₱' + item.revenue.toFixed(2);
    const totalLabel = 'Price: ₱' + item.amount.toLocaleString();

    return `
      <div class="list-item">
        <div class="li-av" style="font-size:13px; font-weight:800; background:var(--teal-mid); color:var(--teal-dark);">${initial}</div>
        <div class="li-body">
          <div class="li-name">${item.worker}</div>
          <div class="li-sub">${item.service} · ${item.date}</div>
        </div>
        <div class="li-right" style="text-align: right; margin-right: 12px;">
          <div style="font-size:13px; font-weight:800; color:var(--teal);">${commissionLabel}</div>
          <div style="font-size:10px; color:var(--txt-muted);">${totalLabel}</div>
        </div>
        <div style="flex-shrink:0;">
          ${revenueStatusPill(item.status)}
        </div>
      </div>
    `;
  }).join('');
}

function revenueStatusPill(s) {
  const key = String(s || '').toLowerCase();
  const map = {
    pending: 'badge-amber',
    submitted: 'badge-blue',
    paid: 'badge-green',
    collected: 'badge-green',
    overdue: 'badge-red',
    completed: 'badge-green'
  };
  const label = key === 'paid' ? 'Collected' : (key === 'pending' ? 'Pending' : key.charAt(0).toUpperCase() + key.slice(1));
  return `<span class="${map[key] || 'badge-gray'}" style="font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 8px;">${label}</span>`;
}

// Initial load
window.addEventListener('DOMContentLoaded', () => {
  loadRevenue();
});
</script>

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
            <span>10% commission fee per booking</span>
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

async function loadRevenue() {
  try {
    // 1. Load summary metrics
    const summaryRes = await fetch('../api/admin_api.php?section=revenue&action=summary');
    const summaryData = await summaryRes.json();
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
    }

    // 2. Load chart data
    const activeBtn = document.querySelector('.rev-filter-btn.active');
    const activeFilter = activeBtn ? activeBtn.getAttribute('onclick').match(/'([^']+)'/)[1] : 'daily';
    await fetchAndDrawChart(activeFilter);

    // 3. Load history
    await initRevenueHistory();
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
    const res = await fetch(`../api/admin_api.php?section=revenue&action=chart&filter=${filter}`);
    const chartData = await res.json();
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

async function initRevenueHistory() {
  const listContainer = document.getElementById('revenueHistoryList');
  if (!listContainer) return;

  try {
    const res = await fetch('../api/admin_api.php?section=revenue&action=history');
    const data = await res.json();
    if (!data.success || !data.history || data.history.length === 0) {
      listContainer.innerHTML = `
        <div class="empty-state" style="padding: 30px; text-align: center; color: var(--txt-muted);">
          <i class="bi bi-wallet2" style="font-size: 24px;"></i>
          <p style="margin-top: 8px; font-weight: bold;">No revenue transactions found.</p>
        </div>
      `;
      return;
    }

    listContainer.innerHTML = data.history.map(item => {
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
  } catch (err) {
    console.error('Failed to load history: ', err);
    listContainer.innerHTML = `
      <div class="empty-state" style="padding: 30px; text-align: center; color: var(--txt-muted);">
        <i class="bi bi-exclamation-triangle" style="font-size: 24px; color: red;"></i>
        <p style="margin-top: 8px; font-weight: bold;">Error loading revenue history.</p>
      </div>
    `;
  }
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

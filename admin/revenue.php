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
    const activeBtn = document.querySelector('.rev-filter-btn.active');
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

// Initial load
window.addEventListener('DOMContentLoaded', () => {
  loadRevenue();
});
</script>

<?php
$activePage = 'bookings';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main style="padding:32px 24px 0 0;">
  <div class="a-hdr">
    <div>
      <div class="a-greet">Manage</div>
      <div class="a-ttl">Bookings</div>
    </div>
    <div class="a-hdr-right">
      <button class="hdr-btn" id="bkFilterToggle" onclick="toggleBkFilters()" title="Filters"><i class="bi bi-funnel-fill"></i></button>
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
  <div id="bkFiltersPanel" style="display:none;background:var(--bg-card);border-bottom:1px solid var(--border-col);padding:12px 18px;">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
      <div>
        <label class="fl" style="font-size:10px;">Date From</label>
        <input class="fi" type="date" id="bkDateFrom" onchange="loadBookings()" style="padding:7px 10px;font-size:12px;">
      </div>
      <div>
        <label class="fl" style="font-size:10px;">Date To</label>
        <input class="fi" type="date" id="bkDateTo" onchange="loadBookings()" style="padding:7px 10px;font-size:12px;">
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
    <button onclick="resetBkFilters()" style="margin-top:10px;width:100%;padding:7px;border-radius:10px;border:1.5px solid var(--border-col);background:transparent;color:var(--txt-muted);font-size:12px;font-weight:700;cursor:pointer;">Reset Filters</button>
  </div>
  <div class="a-scroll" id="bk-scroll" style="padding:12px 18px 80px;">
    <div id="bkList">
      <div class="empty-state">
        <p>Loading...</p>
      </div>
    </div>
    <div id="bkPagination"></div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
// ... JS for bookings page (copy from admindashboard.js)
</script>

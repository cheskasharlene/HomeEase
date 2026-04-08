<?php
$activePage = 'workers';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main style="padding:32px 24px 0 0;">
  <div class="a-hdr">
    <div>
      <div class="a-greet">Manage</div>
      <div class="a-ttl">Workers</div>
    </div>
    <div class="a-hdr-right">
      <button class="hdr-btn" onclick="loadWorkers()"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
  </div>
  <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="wkSearch"
      placeholder="Search workers..." oninput="debounce(loadWorkers,400)()"></div>
  <div class="status-tabs" id="wkStatusTabs" style="margin-top:-5px; margin-bottom: 5px;">
    <div class="stab on" id="wkFilterAll" onclick="setWkFilter('')">All Workers</div>
    <div class="stab" id="wkFilterLow" onclick="setWkFilter('low_rated')">Low Rated (&lt; 3.0)</div>
  </div>
  <div id="wkFilterNote" class="wk-filter-note">Showing: Pending Verification</div>
  <div class="a-scroll" id="wk-scroll" style="padding:12px 18px 80px;">
    <div id="wkList">
      <div class="empty-state">
        <p>Loading...</p>
      </div>
    </div>
    <div id="wkPagination"></div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
// ... JS for workers page (copy from admindashboard.js)
</script>

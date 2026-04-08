<?php
$activePage = 'users';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main style="padding:32px 24px 0 0;">
  <div class="a-hdr">
    <div>
      <div class="a-greet">Manage</div>
      <div class="a-ttl">Users</div>
    </div>
    <div class="a-hdr-right">
      <button class="hdr-btn" onclick="loadUsers()"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
  </div>
  <div class="search-bar"><i class="bi bi-search"></i><input type="text" id="usSearch"
      placeholder="Search users..." oninput="debounce(loadUsers,400)()"></div>
  <div class="a-scroll" id="us-scroll" style="padding:12px 18px 80px;">
    <div id="usList">
      <div class="empty-state">
        <p>Loading...</p>
      </div>
    </div>
    <div id="usPagination"></div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
// ... JS for users page (copy from admindashboard.js)
</script>

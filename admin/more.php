<?php
$activePage = 'more';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<main style="padding:32px 24px 0 0;">
  <div class="a-hdr">
    <div>
      <div class="a-greet">Admin</div>
      <div class="a-ttl">More</div>
    </div>
    <div class="a-hdr-right"></div>
  </div>
  <div class="a-scroll" id="more-scroll">
    <div class="sec-pad">
      <div class="sec-hdr">
        <div class="sec-ttl">Services</div>
        <button onclick="openSvcSheet(null)"
          style="background:var(--teal);color:#fff;border:none;border-radius:20px;padding:5px 13px;font-size:12px;font-weight:700;cursor:pointer;"><i
            class="bi bi-plus-lg"></i> Add</button>
      </div>
      <div class="card" id="svcList">
        <div class="empty-state">
          <p>Loading...</p>
        </div>
      </div>
    </div>
    <div class="sec-pad" style="margin-top:14px;">
      <div class="sec-ttl">Admin</div>
      <div class="card">
        <div class="more-row" onclick="openReviewSheet()">
          <div class="more-ic" style="background:#e0e7ff;color:#4f46e5;"><i class="bi bi-star-fill"></i></div>
          <div>
            <div class="more-nm" style="color:#4f46e5;">Manage Reviews</div>
            <div class="more-sub">Monitor and moderate user feedback</div>
          </div>
          <i class="bi bi-chevron-right more-arrow"></i>
        </div>
        <div class="more-row" onclick="openLogoutConfirm()">
          <div class="more-ic" style="background:#fee2e2;color:#dc2626;"><i class="bi bi-box-arrow-right"></i>
          </div>
          <div>
            <div class="more-nm" style="color:#dc2626;">Logout</div>
            <div class="more-sub">Sign out of admin portal</div>
          </div>
          <i class="bi bi-chevron-right more-arrow"></i>
        </div>
      </div>
    </div>
    <div style="height:20px;"></div>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
// ... JS for more page (copy from admindashboard.js)
</script>

<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}

$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');
$providerEmail = htmlspecialchars($_SESSION['provider_email'] ?? 'No email set');
$providerPhone = htmlspecialchars($_SESSION['provider_phone'] ?? 'No phone set');
$providerAddress = htmlspecialchars($_SESSION['provider_address'] ?? 'Service area not set');
$providerRating = '4.9';
$jobsCompleted = '24';
$yearsExperience = '6';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase - Provider Profile</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link href="../assets/css/profile.css" rel="stylesheet">
</head>

<body>
  <div class="shell" id="app">
    <div id="ml">
      <div class="ml-wrap">
        <div class="ml-box"><svg viewBox="0 0 54 54" fill="none">
            <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white" />
            <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" />
          </svg></div>
        <div class="ml-name">Home<span>Ease</span></div>
        <div class="ml-dots">
          <div class="ml-dot"></div>
          <div class="ml-dot"></div>
          <div class="ml-dot"></div>
        </div>
      </div>
    </div>

    <div class="screen" id="profile">
      <div class="p-scroll">
        <div class="p-hdr">
          <div class="p-hdr-back" onclick="goPage('provider_home.php')"><i class="bi bi-arrow-left"></i></div>
          <img class="p-avatar" src="../assets/images/default-profile.png" alt="Avatar">
          <div class="p-name"><?= $providerName ?></div>
          <div class="p-email"><?= $providerEmail ?></div>
          <div class="p-badges">
            <div class="p-badge"><i class="bi bi-patch-check-fill"></i> Verified Provider</div>
            <div class="p-badge"><i class="bi bi-star-fill"></i> <?= $providerRating ?> Rating</div>
          </div>
        </div>

        <div class="p-stats">
          <div class="p-stat">
            <div class="p-stat-val"><?= $jobsCompleted ?></div>
            <div class="p-stat-lbl">Jobs Done</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val"><?= $providerRating ?></div>
            <div class="p-stat-lbl">Rating</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val"><?= $yearsExperience ?></div>
            <div class="p-stat-lbl">Years Exp.</div>
          </div>
        </div>

        <div class="p-body">
          <div class="p-sec">
            <div class="p-sec-ttl">Contact & Availability</div>
            <div class="p-row">
              <div class="p-row-ic"><i class="bi bi-telephone-fill" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Phone</div>
                <div class="p-row-sub"><?= $providerPhone ?></div>
              </div>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><i class="bi bi-geo-alt-fill" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Service Area</div>
                <div class="p-row-sub"><?= $providerAddress ?></div>
              </div>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><i class="bi bi-clock-fill" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Working Hours</div>
                <div class="p-row-sub">Mon-Sat, 8:00 AM - 6:00 PM</div>
              </div>
            </div>
          </div>

          <div class="p-sec">
            <div class="p-sec-ttl">Service Information</div>
            <div class="p-row">
              <div class="p-row-ic"><i class="bi bi-tools" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Services Offered</div>
                <div class="p-row-sub">Plumbing, Electrical Repair, Home Maintenance</div>
              </div>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><i class="bi bi-cash-coin" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Starting Rate</div>
                <div class="p-row-sub">From $40 per service</div>
              </div>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><i class="bi bi-award-fill" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Certifications</div>
                <div class="p-row-sub">TESDA NC II, Safety Compliance Certified</div>
              </div>
            </div>
          </div>

          <div class="p-sec">
            <div class="p-sec-ttl">Reviews & Portfolio</div>
            <div class="p-row" onclick="goPage('provider_notifications.php')">
              <div class="p-row-ic"><i class="bi bi-chat-square-text-fill" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Customer Reviews</div>
                <div class="p-row-sub">See feedback and ratings from recent clients</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row" onclick="goPage('provider_services.php')">
              <div class="p-row-ic"><i class="bi bi-images" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Work Samples</div>
                <div class="p-row-sub">Manage photos of completed jobs</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><i class="bi bi-person-vcard-fill" style="color:#0D9488"></i></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Bio</div>
                <div class="p-row-sub">Professional provider focused on reliable, high-quality service.</div>
              </div>
            </div>
          </div>

          <button class="logout-btn" onclick="location.href='../logout.php'">
            <i class="bi bi-box-arrow-right" style="margin-right:8px;"></i>Log Out
          </button>
        </div>
      </div>

      <div id="navContainer"></div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();

    document.getElementById('navContainer').innerHTML = `
      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-calendar-check"></i><span class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_services.php')"><div class="nb-c"><i class="bi bi-plus-lg"></i></div></div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span class="nl">Alerts</span></div>
        <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
      </div>`;
  </script>
</body>

</html>

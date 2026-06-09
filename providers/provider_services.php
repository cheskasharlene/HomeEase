<?php /* provider_services.php */
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
enforceProviderSectionAccess('services', $conn);

$providerId = (int)($_SESSION['provider_id'] ?? 0);
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');

$serviceBaseFees = [
  'House Cleaner' => 500,
  'Helper' => 400,
  'Laundry Worker' => 300,
  'Plumber' => 500,
  'Carpenter' => 600,
  'Appliance Technician' => 500
];

// Fetch provider's active service details directly via service_id
$stmt = $conn->prepare("
  SELECT s.id, s.name, s.description, s.icon
  FROM service_providers sp
  LEFT JOIN services s ON s.id = sp.service_id
  WHERE sp.provider_id = ?
");
$stmt->bind_param('i', $providerId);
$stmt->execute();
$result = $stmt->get_result();
$providerService = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – My Services</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_services.css">
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

    <div class="screen">
      <div class="p-scroll">
        <div class="p-hdr">
          <div class="p-hdr-back" onclick="goPage('provider_profile.php')"><i class="bi bi-arrow-left"></i></div>
          <div class="p-hdr-main">
            <div class="p-hdr-ttl">My Services</div>
            <div class="p-hdr-sub">Manage what you offer</div>
          </div>
        </div>

        <div class="svc-card" style="margin: 16px 18px 0; font-size: 12px; color: #8E8E93; line-height: 1.5;">
          <i class="bi bi-info-circle" style="color: #D4790A; margin-right: 6px; font-size: 14px; vertical-align: -1px;"></i>
          Under HomeEase policy, each provider is registered for exactly one service specialty. Please contact support if you need to update your registered service category.
        </div>

        <div class="svc-list" id="servicesList">
          <?php if (empty($providerService) || empty($providerService['name'])): ?>
            <div style="text-align: center; padding: 40px 20px; color: #8E8E93;">
              <p>No active service assigned to your account.</p>
              <p style="font-size: 12px; margin-top: 8px;">Please contact support to assign a specialty.</p>
            </div>
          <?php else: ?>
            <div class="svc-card" data-service-id="<?= $providerService['id'] ?>" data-service-name="<?= htmlspecialchars($providerService['name']) ?>">
              <div class="svc-top">
                <div class="svc-ic"><?= htmlspecialchars($providerService['icon'] ?? '🔧') ?></div>
                <div>
                  <div class="svc-nm"><?= htmlspecialchars($providerService['name']) ?></div>
                  <div class="svc-desc"><?= htmlspecialchars($providerService['description'] ?? '') ?></div>
                </div>
                <?php $baseFee = $serviceBaseFees[$providerService['name']] ?? null; ?>
                <div class="svc-price"><?= $baseFee !== null ? '₱' . number_format($baseFee, 0) : 'Pricing set on booking' ?></div>
              </div>
              <div class="svc-footer">
                <div class="btn-edit" style="text-align: center; cursor: default; pointer-events: none;">
                  <i class="bi bi-shield-check" style="margin-right: 5px;"></i> Active Registered Specialty
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span
          class="nl">Earnings</span></div>
        <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span
            class="nl">Profile</span></div>
      </div>
    </div>
  </div>
  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();

    const providerId = <?= $providerId ?>;

    function editService(serviceId, serviceName) {
      showToast('Edit feature coming soon for: ' + serviceName, 'info');
    }

    function openAddServiceModal() {
      showToast('Add service feature coming soon', 'info');
    }

    function showToast(msg, type = 'info') {
      const toast = document.createElement('div');
      toast.style.cssText = 'position:fixed;bottom:100px;left:50%;transform:translateX(-50%);background:#F5A623;color:#fff;padding:12px 20px;border-radius:8px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 2px 8px rgba(0,0,0,0.15);animation:slideUp 0.3s ease-out;';
      if (type === 'error') toast.style.background = '#EF4444';
      if (type === 'success') toast.style.background = '#10B981';
      
      toast.textContent = msg;
      document.body.appendChild(toast);
      
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
      }, 2500);
    }
  </script>
</body>

</html>
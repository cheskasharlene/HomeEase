<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';
$access = enforceProviderSectionAccess('profile', $conn);
$isVerified = $access['is_verified'];
$verificationState = $access['state'];
$isPendingUi = in_array($verificationState, ['pending', 'approval_ready'], true);
$profileUiState = $isVerified ? 'verified' : ($isPendingUi ? 'pending' : 'not-verified');
$name = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');
$email = htmlspecialchars($_SESSION['provider_email'] ?? '');
$phone = htmlspecialchars($_SESSION['provider_phone'] ?? 'Not set');
$address = htmlspecialchars($_SESSION['provider_address'] ?? 'Not set');
$specialty = htmlspecialchars($_SESSION['provider_specialty'] ?? 'General Services');

$rawName = trim((string)($_SESSION['provider_name'] ?? 'Service Provider'));
$rawEmail = trim((string)($_SESSION['provider_email'] ?? ''));
$rawPhone = trim((string)($_SESSION['provider_phone'] ?? ''));
$rawAddress = trim((string)($_SESSION['provider_address'] ?? ''));
$rawSpecialty = trim((string)($_SESSION['provider_specialty'] ?? 'General Services'));
$providerId = (int)($_SESSION['provider_id'] ?? 0);

$availabilityStatus = $isVerified ? 'online' : 'offline';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Provider Profile</title>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link href="../assets/css/profile.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_profile.css">
</head>

<body class="<?= $profileUiState ?>">
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
          <div class="p-hdr-settings" onclick="openSettingsScreen()"><i class="bi bi-gear-fill"></i></div>
          <img
            id="profileAvatar"
            class="p-avatar"
            src="https://ui-avatars.com/api/?name=Provider&background=FDECC8&color=E8820C&size=200"
            alt="Profile Avatar">
          <div class="p-name" id="profileName"><?= $name ?></div>
          <div class="p-email" id="profileEmail"><?= $email ?></div>
          <div class="p-badges">
            <div class="p-badge"><i class="bi <?= $isVerified ? 'bi-patch-check-fill' : 'bi-hourglass-split' ?>" style="font-size:11px;"></i> <?= $isVerified ? 'Verified Provider' : ('Verification: ' . ucfirst(str_replace('_', ' ', $verificationState))) ?></div>
            <div class="p-badge service-badge"><i class="bi bi-tools" style="font-size:11px;"></i> <span id="profileSpecialty"><?= $specialty ?></span></div>
          </div>
          <div class="p-status-row" id="profileStatusRow">
            <div class="p-status-text">Status: <span id="profileAvailLabel"><?= ($isVerified && $availabilityStatus === 'online') ? 'Online' : 'Offline' ?></span></div>
            <label class="p-status-switch <?= $isVerified ? '' : 'disabled' ?>" id="profileStatusSwitchWrap">
              <input type="checkbox" id="profileAvailToggle" <?= ($isVerified && $availabilityStatus === 'online') ? 'checked' : '' ?> disabled>
              <span class="p-status-slider"></span>
            </label>
          </div>
        </div>

     


        <div class="p-body">
        
          <div class="p-sec">
            <div class="p-sec-ttl">Contact & Availability</div>
            <div class="p-row actionable" onclick="openActionSheet('phone')">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path
                    d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 11.18 19.79 19.79 0 01.12 2.57 2 2 0 012.11.39h3A2 2 0 017.1 2.07c.36 1.07.83 2.1 1.38 3.07a2 2 0 01-.46 2.31L6.29 9A16 16 0 0015 17.71l1.55-1.73a2 2 0 012.31-.46c.97.55 2 1.02 3.07 1.38a2 2 0 011.07 1.02z"
                    stroke="#F5A623" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Phone</div>
                <div class="p-row-sub" id="profilePhoneValue"><?= $phone ?></div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row actionable" onclick="openActionSheet('service-area')">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 2a8 8 0 00-8 8c0 5.5 8 13 8 13s8-7.5 8-13a8 8 0 00-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z"
                    stroke="#F5A623" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Address</div>
                <div class="p-row-sub" id="profileAddressValue"><?= $address ?></div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
          </div>

          <?php if ($isVerified): ?>
            <div class="p-sec">
              <div class="p-sec-ttl">Services & Portfolio</div>

              <div class="p-row" onclick="goPage('provider_job_history.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <path d="M9 11l3 3L22 4" stroke="#F5A623" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round" />
                    <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11" stroke="#F5A623" stroke-width="2"
                      stroke-linecap="round" />
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Job History</div>
                  <div class="p-row-sub">View completed jobs</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
              <!-- Earnings removed: now accessible via bottom navigation -> provider_earnings.php -->
              <div class="p-row" onclick="goPage('provider_reviews.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"
                      stroke="#F5A623" stroke-width="2" stroke-linejoin="round"/>
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Reviews</div>
                  <div class="p-row-sub">View customer feedback</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
            </div>
          <?php else: ?>
            <div class="p-sec">
              <div class="p-sec-ttl">Access Locked</div>
              <div class="p-row" onclick="goPage('provider_home.php')">
                <div class="p-row-ic"><i class="bi bi-shield-lock" style="font-size:20px;color:#F5A623;"></i></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Requests, Schedule, and Earnings are locked</div>
                  <div class="p-row-sub">Go to Home and submit verification requirements to unlock provider tools.</div>
                </div>
              </div>
          <?php endif; ?>

        </div>
      </div>

      <div class="bnav">
        <?php if ($isVerified): ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
          <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span class="nl">Earnings</span></div>
          <div class="ni ni-bell" onclick="goPage('provider_notifications.php')">
            <div class="ni-bell-wrap"><i class="bi bi-bell-fill"></i><span class="ni-badge" id="navBellBadge" style="display:none;"></span></div>
            <span class="nl">Notifs</span>
          </div>
          <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni ni-bell" onclick="goPage('provider_notifications.php')">
            <div class="ni-bell-wrap"><i class="bi bi-bell-fill"></i><span class="ni-badge" id="navBellBadge" style="display:none;"></span></div>
            <span class="nl">Notifs</span>
          </div>
          <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php endif; ?>
      </div>
    </div>

 
    <div id="settingsScreen">
      <div class="st-hdr">
        <div class="st-back" onclick="closeSettingsScreen()"><i class="bi bi-arrow-left"></i></div>
        <div>
          <div class="st-hdr-title">Settings</div>
          <div class="st-hdr-sub">Manage your provider account</div>
        </div>
      </div>
      <div class="st-scroll">
        <div class="st-sec">
          <div class="st-sec-ttl">Account</div>
          <div class="st-row" onclick="openActionSheet('edit-profile')">
            <div class="st-ic orange"><i class="bi bi-person-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Edit Profile</div>
              <div class="st-row-sub" id="settingsEditProfileSub">Name, phone, service area</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row" onclick="openActionSheet('change-password')">
            <div class="st-ic blue"><i class="bi bi-shield-lock-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Change Password</div>
              <div class="st-row-sub">Update your password</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <?php if ($isVerified): ?>
          <div class="st-row" onclick="openQrChangeScreen()" id="qrChangeSettingsRow">
            <div class="st-ic" style="background:#d1fae5;color:#059669;"><i class="bi bi-qr-code"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">GCash/Bank Transfer Change Request</div>
              <div class="st-row-sub" id="qrChangeSettingsSub">Request a QR code change</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <?php endif; ?>
        </div>


        <div class="st-sec">
          <div class="st-sec-ttl">Support</div>
          <div class="st-row" onclick="openHelpCenter()">
            <div class="st-ic orange"><i class="bi bi-question-circle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Help Center</div>
              <div class="st-row-sub">FAQs & guides</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row" onclick="openTermsModal()">
            <div class="st-ic orange"><i class="bi bi-file-earmark-text-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Terms and Conditions</div>
              <div class="st-row-sub">Read provider terms & policies</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <?php if ($isVerified): ?>
          <div class="st-row" onclick="openReportModal()">
            <div class="st-ic orange"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Report an Issue</div>
              <div class="st-row-sub">Submit a concern or bug</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <?php endif; ?>
        </div>
        <div class="st-sec">
          <div class="st-sec-ttl">Session</div>
          <div class="st-row" onclick="openLogoutConfirm()">
            <div class="st-ic red"><i class="bi bi-box-arrow-right"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl" style="color:#ef4444;">Log Out</div>
              <div class="st-row-sub">Sign out of your account</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal-overlay" id="helpCenterModal" onclick="if(event.target===this)closeHelpCenter()" style="display:none;">
      <div class="modal-card">
        <div style="padding:28px 24px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
          <div style="font-size:20px;font-weight:800;color:#0f172a;">Service Provider Help Center</div>
          <button onclick="closeHelpCenter()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#6b7280;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div style="max-height:70vh;overflow-y:auto;padding:24px;">
          <!-- Account Section -->
          <div style="margin-bottom:28px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:16px;font-size:16px;border-bottom:2px solid #e8820c;padding-bottom:8px;">Account</div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>How do I register as a service provider?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Download the app, select "Register as Provider," and submit your personal details and required documents for verification.
              </div>
            </div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>Can I edit my profile?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Yes, go to account settings to update your profile anytime.
              </div>
            </div>
          </div>

          <!-- Verification Section -->
          <div style="margin-bottom:28px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:16px;font-size:16px;border-bottom:2px solid #e8820c;padding-bottom:8px;">Verification</div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>What documents are required?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Valid ID, proof of address, and supporting documents related to your service (if applicable).
              </div>
            </div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>How long does verification take?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Usually 24–72 hours depending on the review.
              </div>
            </div>
          </div>

          <!-- Booking & Jobs Section -->
          <div style="margin-bottom:28px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:16px;font-size:16px;border-bottom:2px solid #e8820c;padding-bottom:8px;">Booking & Jobs</div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>How do I receive bookings?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                You will get notifications when a customer requests your service.
              </div>
            </div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>Can I accept or decline bookings?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Yes, you can choose based on your availability.
              </div>
            </div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>What happens after I accept a booking?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                You proceed to the customer's location and complete the service.
              </div>
            </div>
          </div>

          <!-- Earnings & Payments Section -->
          <div style="margin-bottom:28px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:16px;font-size:16px;border-bottom:2px solid #e8820c;padding-bottom:8px;">Earnings & Payments</div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>How do I get paid?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Payments are released after service completion via selected payout methods.
              </div>
            </div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>Are there deductions or fees?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Yes, a service/platform fee may apply.
              </div>
            </div>
          </div>

          <!-- Conduct & Responsibilities Section -->
          <div style="margin-bottom:28px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:16px;font-size:16px;border-bottom:2px solid #e8820c;padding-bottom:8px;">Conduct & Responsibilities</div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>What are my responsibilities?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                <ul style="margin:0;padding:0 0 0 20px;">
                  <li style="margin-bottom:6px;">Provide quality service</li>
                  <li style="margin-bottom:6px;">Be on time</li>
                  <li style="margin-bottom:6px;">Maintain professionalism</li>
                </ul>
              </div>
            </div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>What happens if I violate policies?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Your account may be suspended or terminated.
              </div>
            </div>
          </div>

          <!-- Ratings & Reviews Section -->
          <div style="margin-bottom:28px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:16px;font-size:16px;border-bottom:2px solid #e8820c;padding-bottom:8px;">Ratings & Reviews</div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>Can customers rate me?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Yes, ratings help build your reputation.
              </div>
            </div>
          </div>

          <!-- Support Section -->
          <div style="margin-bottom:28px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:16px;font-size:16px;border-bottom:2px solid #e8820c;padding-bottom:8px;">Support</div>
            <div class="faq-item">
              <div class="faq-question" onclick="toggleFaq(this)" style="cursor:pointer;padding:12px;background:#f8fafc;border-radius:8px;margin-bottom:8px;font-weight:600;color:#0f172a;display:flex;justify-content:space-between;align-items:center;">
                <span>Who do I contact for issues?</span>
                <i class="bi bi-chevron-down" style="font-size:14px;transition:transform .3s;"></i>
              </div>
              <div class="faq-answer" style="display:none;padding:12px 12px 12px 16px;background:#fafbfc;border-left:3px solid #e8820c;color:#4b5563;line-height:1.6;font-size:14px;">
                Use the in-app support feature.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal-overlay" id="termsModal" onclick="if(event.target===this)closeTermsModal()" style="display:none;">
      <div class="modal-card">
        <div style="padding:28px 24px;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;">
          <div style="font-size:20px;font-weight:800;color:#0f172a;">Terms and Conditions</div>
          <button onclick="closeTermsModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#6b7280;"><i class="bi bi-x-lg"></i></button>
        </div>
        <div style="max-height:70vh;overflow-y:auto;padding:24px;line-height:1.7;color:#4b5563;font-size:14px;">
          <!-- 1. Introduction -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">1. Introduction</div>
            <p style="margin:0;">These Terms and Conditions govern the use of the HomeEase platform by individuals registering as Service Providers. By registering, submitting requirements, and continuing to use the platform, you acknowledge that you have read, understood, and agreed to comply with these terms.</p>
          </div>

          <!-- 2. Eligibility -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">2. Eligibility</div>
            <p style="margin:0 0 8px 0;">To qualify as a Service Provider on HomeEase, you must:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">Be at least eighteen (18) years old;</li>
              <li style="margin-bottom:6px;">Provide accurate, complete, and truthful information;</li>
              <li style="margin-bottom:6px;">Submit the required verification documents requested by HomeEase;</li>
              <li style="margin-bottom:6px;">Have the legal capacity to enter into this agreement.</li>
            </ul>
            <p style="margin:8px 0 0 0;">HomeEase reserves the right to approve, reject, suspend, or terminate any application at its discretion.</p>
          </div>

          <!-- 3. Verification Requirements -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">3. Verification Requirements</div>
            <p style="margin:0 0 8px 0;">Service Providers agree to submit authentic and valid documents during the verification process.</p>
            <p style="margin:0 0 8px 0;">Submission of falsified, altered, misleading, or expired documents may result in:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">Rejection of the application;</li>
              <li style="margin-bottom:6px;">Suspension of the account; or</li>
              <li style="margin-bottom:6px;">Permanent removal from the platform.</li>
            </ul>
            <p style="margin:8px 0 0 0;">Verification status may be reviewed periodically by HomeEase.</p>
          </div>

          <!-- 4. Professional Conduct -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">4. Professional Conduct</div>
            <p style="margin:0 0 8px 0;">Service Providers agree to:</p>
            <ul style="margin:8px 0 8px 20px;padding:0;">
              <li style="margin-bottom:6px;">Treat clients with courtesy, honesty, and professionalism;</li>
              <li style="margin-bottom:6px;">Respect clients and their property;</li>
              <li style="margin-bottom:6px;">Communicate appropriately at all times;</li>
              <li style="margin-bottom:6px;">Provide quality services to the best of their abilities;</li>
              <li style="margin-bottom:6px;">Follow all applicable laws and regulations.</li>
            </ul>
            <p style="margin:8px 0 8px 0;">The following behaviors are strictly prohibited:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">Harassment or discrimination;</li>
              <li style="margin-bottom:6px;">Threatening behavior;</li>
              <li style="margin-bottom:6px;">Fraudulent activities;</li>
              <li style="margin-bottom:6px;">Dishonesty or misrepresentation;</li>
              <li style="margin-bottom:6px;">Any conduct that may compromise the safety and trust of the HomeEase community.</li>
            </ul>
          </div>

          <!-- 5. Materials, Supplies, and Equipment Responsibility -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">5. Materials, Supplies, and Equipment Responsibility</div>
            <p style="margin:0 0 8px 0;">Service Providers are solely responsible for bringing and providing all necessary tools, equipment, materials, and supplies required to perform the services they offer through HomeEase.</p>
            <p style="margin:0 0 8px 0;">The following conditions apply:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">Homeowners or clients are not required to purchase, prepare, or provide materials needed for the service.</li>
              <li style="margin-bottom:6px;">Standard materials, supplies, and equipment necessary to complete the service are already considered included in the service fee displayed on the platform.</li>
              <li style="margin-bottom:6px;">Service Providers shall not request or collect additional payment from clients for standard materials and supplies required to perform the booked service.</li>
              <li style="margin-bottom:6px;">All tools, equipment, and materials used must be safe, functional, clean, and suitable for their intended purpose.</li>
              <li style="margin-bottom:6px;">Failure to bring the necessary supplies or equipment that affects service quality may result in complaints, warnings, suspension, or termination.</li>
            </ul>
          </div>

          <!-- 6. Bookings and Service Completion -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">6. Bookings and Service Completion</div>
            <p style="margin:0 0 8px 0;">Service Providers may receive booking requests through the HomeEase platform based on their availability status.</p>
            <p style="margin:0 0 8px 0;">Once a booking has been accepted and assigned, the Service Provider agrees to:</p>
            <ul style="margin:8px 0 8px 20px;padding:0;">
              <li style="margin-bottom:6px;">Perform the requested service responsibly and professionally;</li>
              <li style="margin-bottom:6px;">Arrive at the client's location within the agreed schedule;</li>
              <li style="margin-bottom:6px;">Complete the service to the best of their ability;</li>
              <li style="margin-bottom:6px;">Use the "Mark as Done" feature only after the service has been fully completed.</li>
            </ul>
            <p style="margin:8px 0 8px 0;">The "Mark as Done" button serves as confirmation that the requested service has been successfully rendered.</p>
            <p style="margin:8px 0 8px 0;">Service Providers must not falsely mark bookings as completed when the service has not been fully performed.</p>
            <p style="margin:8px 0 0 0;">Any misuse of the service completion process may result in client complaints, investigations, warnings, suspension, or permanent removal from the platform.</p>
          </div>

          <!-- 7. Payments and Fees -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">7. Payments and Fees</div>
            <p style="margin:0 0 8px 0;">Service Providers agree that:</p>
            <ul style="margin:8px 0 8px 20px;padding:0;">
              <li style="margin-bottom:6px;">Payments shall be processed in accordance with HomeEase policies and system procedures;</li>
              <li style="margin-bottom:6px;">The service fees displayed by the platform shall be followed;</li>
              <li style="margin-bottom:6px;">Unauthorized charges outside the HomeEase platform are prohibited;</li>
              <li style="margin-bottom:6px;">Service Providers shall not solicit additional payments from clients beyond what is officially reflected within the system.</li>
            </ul>
            <p style="margin:8px 0 0 0;">Violation of this provision may lead to disciplinary action.</p>
          </div>

          <!-- 8. Ratings and Reviews -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">8. Ratings and Reviews</div>
            <p style="margin:0 0 8px 0;">Clients may submit ratings and reviews based on their experience.</p>
            <p style="margin:0 0 8px 0;">Service Providers acknowledge that:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">Ratings contribute to their reputation within HomeEase;</li>
              <li style="margin-bottom:6px;">Reviews may be used to assess service quality;</li>
              <li style="margin-bottom:6px;">Attempts to manipulate ratings or submit false reviews are prohibited;</li>
              <li style="margin-bottom:6px;">Consistently poor performance may result in administrative review.</li>
            </ul>
          </div>

          <!-- 9. Reports and Complaints -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">9. Reports and Complaints</div>
            <p style="margin:0 0 8px 0;">HomeEase may investigate reports submitted by clients concerning fraud, misconduct, negligence, unprofessional behavior, or other violations.</p>
            <p style="margin:0 0 8px 0;">Service Providers agree to:</p>
            <ul style="margin:8px 0 8px 20px;padding:0;">
              <li style="margin-bottom:6px;">Cooperate during your investigations;</li>
              <li style="margin-bottom:6px;">Provide truthful information when requested;</li>
              <li style="margin-bottom:6px;">Respect the investigation process.</li>
            </ul>
            <p style="margin:8px 0 0 0;">Failure to cooperate may result in disciplinary action.</p>
          </div>

          <!-- 10. Suspension and Termination -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">10. Suspension and Termination</div>
            <p style="margin:0 0 8px 0;">HomeEase reserves the right to suspend, restrict, deactivate, or permanently terminate a Service Provider account for reasons including, but not limited to:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">Submission of fraudulent documents;</li>
              <li style="margin-bottom:6px;">Violation of these Terms and Conditions;</li>
              <li style="margin-bottom:6px;">Repeated complaints from clients;</li>
              <li style="margin-bottom:6px;">Fraudulent activities or scams;</li>
              <li style="margin-bottom:6px;">Unprofessional conduct;</li>
              <li style="margin-bottom:6px;">Unauthorized charges;</li>
              <li style="margin-bottom:6px;">Falsely marking services as completed;</li>
              <li style="margin-bottom:6px;">Actions that compromise the safety, trust, and integrity of the platform.</li>
            </ul>
          </div>

          <!-- 11. Limitation of Liability -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">11. Limitation of Liability</div>
            <p style="margin:0 0 8px 0;">HomeEase acts solely as a platform that connects homeowners and service providers.</p>
            <p style="margin:0 0 8px 0;">HomeEase shall not be held liable for:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">Damages arising from improper execution of services;</li>
              <li style="margin-bottom:6px;">Losses caused by the negligence of Service Providers;</li>
              <li style="margin-bottom:6px;">Misuse of tools, equipment, or materials by Service Providers;</li>
              <li style="margin-bottom:6px;">Disputes resulting from violations committed by either party.</li>
            </ul>
          </div>

          <!-- 12. Amendments -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">12. Amendments</div>
            <p style="margin:0 0 8px 0;">HomeEase reserves the right to revise or update these Terms and Conditions at any time.</p>
            <p style="margin:0 0 8px 0;">Any modifications shall become effective upon posting within the platform.</p>
            <p style="margin:0;">Continued use of HomeEase constitutes acceptance of the updated terms.</p>
          </div>

          <!-- 13. Governing Law -->
          <div style="margin-bottom:20px;">
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">13. Governing Law</div>
            <p style="margin:0 0 8px 0;">These Terms and Conditions shall be governed by and interpreted in accordance with the laws of the Republic of the Philippines.</p>
            <p style="margin:0;">Any dispute arising from these Terms and Conditions shall be subject to the jurisdiction of the appropriate courts within the Philippines.</p>
          </div>

          <!-- 14. Acknowledgment and Agreement -->
          <div>
            <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;border-bottom:2px solid #e8820c;padding-bottom:8px;text-transform:uppercase;">14. Acknowledgment and Agreement</div>
            <p style="margin:0 0 8px 0;">By selecting "I Agree," submitting verification requirements, registering as a Service Provider, or continuing to use the HomeEase platform, you acknowledge and agree that:</p>
            <ul style="margin:8px 0 0 20px;padding:0;">
              <li style="margin-bottom:6px;">You have read and understood these Terms and Conditions;</li>
              <li style="margin-bottom:6px;">You voluntarily agree to comply with all provisions stated herein;</li>
              <li style="margin-bottom:6px;">You understand your responsibilities as a Service Provider;</li>
              <li style="margin-bottom:6px;">You agree to uphold professionalism and integrity while using the platform; and</li>
              <li style="margin-bottom:6px;">You understand that violations of these Terms and Conditions may result in warnings, suspension, deactivation, or permanent termination of your HomeEase account.</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <div class="logout-confirm-ol" id="logoutConfirmOl" onclick="if(event.target===this)closeLogoutConfirm()">
      <div class="logout-confirm-card">
        <div class="logout-confirm-ic"><i class="bi bi-box-arrow-right"></i></div>
        <div class="logout-confirm-ttl">Log out?</div>
        <div class="logout-confirm-sub">You will be signed out of your provider account.</div>
        <div class="logout-confirm-actions">
          <button class="logout-confirm-btn cancel" onclick="closeLogoutConfirm()">Cancel</button>
          <button class="logout-confirm-btn ok" onclick="confirmLogout()">Log out</button>
        </div>
      </div>
    </div>

    <div class="edit-modal-ol" id="actionSheetOl" onclick="actionSheetBg(event)">
      <div class="edit-modal-card" role="dialog" aria-modal="true" aria-labelledby="actionSheetTitle">
        <div class="edit-sheet-handle-zone">
          <div class="edit-sheet-handle"></div>
        </div>
        <div class="edit-sheet-header">
          <div>
            <div class="edit-modal-ttl" id="actionSheetTitle">Edit Profile</div>
            <div class="edit-modal-sub" id="actionSheetSubTitle">Update your account details</div>
          </div>
          <button class="edit-sheet-close" type="button" onclick="closeActionSheet()"><i class="bi bi-x-lg"></i></button>
        </div>

        <div class="edit-modal-content">
          <div id="sheetAlert" class="sheet-alert"></div>

          <div class="sheet-section" id="sheetPhone">
            <div class="edit-modal-form">
              <div class="edit-fg">
                <label class="edit-flbl">Phone Number</label>
                <input class="edit-fin" id="sheetPhoneInput" type="tel" placeholder="09xx xxx xxxx">
              </div>
            </div>
          </div>

          <div class="sheet-section" id="sheetServiceArea">
            <div class="edit-modal-form">
              <div class="edit-fg">
                <label class="edit-flbl">Address</label>
                <input class="edit-fin" id="sheetServiceAreaInput" type="text" placeholder="Enter address">
              </div>
            </div>
          </div>

          <div class="sheet-section" id="sheetEditProfile">
            <div class="sheet-profile-avatar-wrap">
              <input id="sheetProfileAvatarInput" type="file" accept="image/*" hidden>
              <button type="button" class="sheet-profile-avatar-btn" onclick="triggerProfilePhotoPicker()" aria-label="Upload profile photo">
                <img id="sheetProfileAvatar" src="https://ui-avatars.com/api/?name=Provider&background=FDECC8&color=E8820C&size=200" alt="Edit profile photo">
                <span class="sheet-profile-avatar-cam"><i class="bi bi-camera-fill"></i></span>
              </button>
              <button type="button" class="sheet-profile-avatar-label" onclick="triggerProfilePhotoPicker()">Tap to change photo</button>
            </div>
            <div class="edit-modal-form">
              <div class="edit-fg">
                <label class="edit-flbl">Name</label>
                <input class="edit-fin" id="sheetProfileName" type="text" placeholder="Full name">
              </div>
              <div class="edit-fg">
                <label class="edit-flbl">Email</label>
                <input class="edit-fin" id="sheetProfileEmail" type="email" placeholder="you@email.com">
              </div>
              <div class="edit-fg">
                <label class="edit-flbl">Phone</label>
                <input class="edit-fin" id="sheetProfilePhone" type="tel" placeholder="09xx xxx xxxx">
              </div>
              <div class="edit-fg">
                <label class="edit-flbl">Address</label>
                <input class="edit-fin" id="sheetProfileAddress" type="text" placeholder="Service area or address">
              </div>
            </div>
          </div>

          <div class="sheet-section" id="sheetChangePassword">
            <div class="edit-modal-form">
              <div class="edit-fg">
                <label class="edit-flbl">Current Password</label>
                <input class="edit-fin" id="sheetCurrentPassword" type="password" placeholder="Current password">
              </div>
              <div class="edit-fg">
                <label class="edit-flbl">New Password</label>
                <input class="edit-fin" id="sheetNewPassword" type="password" placeholder="New password" oninput="updateProviderPwdStrength(this.value)">
                <!-- Password strength meter -->
                <div id="provPwdStrength" style="display:none;margin-top:7px;">
                  <div style="display:flex;gap:4px;margin-bottom:5px;">
                    <div id="ppbar1" style="flex:1;height:4px;border-radius:4px;background:#EDE8E0;transition:background 0.25s;"></div>
                    <div id="ppbar2" style="flex:1;height:4px;border-radius:4px;background:#EDE8E0;transition:background 0.25s;"></div>
                    <div id="ppbar3" style="flex:1;height:4px;border-radius:4px;background:#EDE8E0;transition:background 0.25s;"></div>
                    <div id="ppbar4" style="flex:1;height:4px;border-radius:4px;background:#EDE8E0;transition:background 0.25s;"></div>
                  </div>
                  <div style="display:flex;flex-direction:column;gap:3px;">
                    <div id="preq-len" style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#8E8E93;transition:color 0.2s;"><i class="bi bi-circle-fill" style="font-size:6px;"></i> At least 8 characters</div>
                    <div id="preq-upper" style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#8E8E93;transition:color 0.2s;"><i class="bi bi-circle-fill" style="font-size:6px;"></i> At least one uppercase letter (A-Z)</div>
                    <div id="preq-lower" style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#8E8E93;transition:color 0.2s;"><i class="bi bi-circle-fill" style="font-size:6px;"></i> At least one lowercase letter (a-z)</div>
                    <div id="preq-num" style="display:flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#8E8E93;transition:color 0.2s;"><i class="bi bi-circle-fill" style="font-size:6px;"></i> At least one number (0-9)</div>
                  </div>
                </div>
              </div>
              <div class="edit-fg">
                <label class="edit-flbl">Confirm Password</label>
                <input class="edit-fin" id="sheetConfirmPassword" type="password" placeholder="Confirm new password">
              </div>
            </div>
          </div>

          <div class="sheet-section" id="sheetManageServices">
            <div class="edit-modal-form">
              <div class="sheet-service-list" id="sheetServicesList"></div>
            </div>
          </div>
        </div>

        <div class="edit-modal-actions">
          <button class="edit-modal-btn cancel" id="actionSheetCancelBtn" type="button" onclick="closeActionSheet()">Cancel</button>
          <button class="edit-modal-btn save" id="actionSheetSaveBtn" type="button" onclick="saveActionSheet()">Save</button>
        </div>
      </div>
    </div>

    <!-- Report Issue Modal Overlay -->
    <div class="report-modal-ol" id="reportModalOl" onclick="if(event.target===this)closeReportModal()">
      <div class="report-modal-card">
        <div class="report-modal-hdr">
          <div class="report-modal-ttl">Report an Issue</div>
          <button class="report-modal-close" onclick="closeReportModal()"><i class="bi bi-x-lg"></i></button>
        </div>
        
        <div class="report-modal-body">
          <div class="report-fg">
            <label class="report-lbl" for="reportCategory">Category</label>
            <div class="report-select-wrapper">
              <select class="report-select" id="reportCategory">
                <option value="" disabled selected>Select a category</option>
                <option value="Scam/Fraud">Scam/Fraud</option>
                <option value="Payment Issue">Payment Issue</option>
                <option value="Booking Issue">Booking Issue</option>
                <option value="Service Quality Concern">Service Quality Concern</option>
                <option value="App Bug/Technical Issue">App Bug/Technical Issue</option>
                <option value="Harassment">Harassment</option>
                <option value="Other">Other</option>
              </select>
              <i class="bi bi-chevron-down report-select-arrow"></i>
            </div>
          </div>
          
          <div class="report-fg">
            <label class="report-lbl" for="reportDescription">Description</label>
            <textarea class="report-textarea" id="reportDescription" placeholder="Provide detailed information about the issue..."></textarea>
          </div>
                  <div class="report-fg">
            <label class="report-lbl">Evidence (Optional)</label>
            <div class="report-upload-area" onclick="triggerReportEvidencePicker()">
              <div id="reportUploadDefault" style="display:flex; flex-direction:column; align-items:center;">
                <i class="bi bi-cloud-arrow-up report-upload-icon"></i>
                <span class="report-upload-text" id="reportUploadText">Upload image or screenshot</span>
                <span class="report-upload-subtext">JPEG, PNG, WEBP up to 5MB</span>
              </div>
              <div id="reportUploadChip" style="display:none; align-items:center; gap:8px; background:#fff; border:1px solid var(--border-col); padding:6px 12px; border-radius:20px; max-width:90%; box-shadow: 0 2px 6px rgba(0,0,0,0.05); margin: 4px 0;">
                <i class="bi bi-paperclip" style="color:var(--teal,#e8820c); font-size:14px;"></i>
                <span id="reportUploadFileName" style="font-size:12px; font-weight:700; color:var(--txt-primary); text-overflow:ellipsis; overflow:hidden; white-space:nowrap; max-width:180px;"></span>
                <button type="button" onclick="event.stopPropagation(); clearReportEvidence()" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:14px; padding:0; display:flex; align-items:center;"><i class="bi bi-trash3-fill"></i></button>
              </div>
              <input type="file" id="reportEvidenceInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="handleReportEvidenceSelected(this)">
              <img id="reportEvidencePreview" src="" alt="Evidence Preview" style="display:none;">
            </div>
          </div>
        </div>
        
        <div class="report-modal-actions">
          <button class="report-btn cancel" onclick="closeReportModal()">Cancel</button>
          <button class="report-btn submit" id="reportSubmitBtn" onclick="submitReportForm()">Submit Report</button>
        </div>
      </div>
    </div>

    <!-- Report Success Modal Overlay -->
    <div class="report-success-ol" id="reportSuccessOl" onclick="if(event.target===this)closeReportSuccess()">
      <div class="report-success-card">
        <div class="report-success-icon"><i class="bi bi-check-circle-fill"></i></div>
        <div class="report-success-ttl">Report Submitted</div>
        <div class="report-success-sub">Your concern has been submitted and will be reviewed by the admin team.</div>
        <div class="report-success-actions">
          <button class="report-success-btn ok" onclick="closeReportSuccess()">OK</button>
        </div>
      </div>
    </div>

  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    if (typeof initTheme === 'function') {
      initTheme();
    }
    const backendProfileState = <?= json_encode($profileUiState) ?>;
    const backendIsVerified = <?= json_encode($isVerified) ?>;
    const backendAvailability = <?= json_encode($availabilityStatus) ?>;
    const providerId = <?= json_encode($providerId) ?>;
    const providerAvatarStorageKey = 'he_provider_avatar_' + String(providerId || 'default');
    const providerUiState = {
      name: <?= json_encode($rawName) ?>,
      email: <?= json_encode($rawEmail) ?>,
      phone: <?= json_encode($rawPhone) ?>,
      serviceArea: <?= json_encode($rawAddress) ?>,
      workingStart: '08:00',
      workingEnd: '18:00',
      specialty: <?= json_encode($rawSpecialty) ?>,
      avatarUrl: '',
      services: []
    };
    const providerServicesCatalog = [
      'General Services',
      'Home House Cleaner',
      'Plumbing',
      'Electrical Repair',
      'Carpentry',
      'Appliance Repair'
    ];
    providerUiState.services = providerServicesCatalog.filter(function (svc) {
      return svc === providerUiState.specialty;
    });
    if (!providerUiState.services.length) {
      providerUiState.services = [providerUiState.specialty || 'General Services'];
      if (providerServicesCatalog.indexOf(providerUiState.services[0]) === -1) {
        providerServicesCatalog.push(providerUiState.services[0]);
      }
    }

    function applyProfileUiState(state) {
      document.body.classList.remove('not-verified', 'pending', 'verified');
      document.body.classList.add(state);

      const toggle = document.getElementById('profileAvailToggle');
      const lbl = document.getElementById('profileAvailLabel');
      const wrap = document.getElementById('profileStatusSwitchWrap');
      const allowToggle = false;

      if (toggle && lbl && wrap) {
        toggle.disabled = true;
        wrap.classList.add('disabled');
        lbl.textContent = state === 'verified' ? 'Online' : 'Offline';
        toggle.checked = state === 'verified';
      }
    }

    const profileAvailToggle = document.getElementById('profileAvailToggle');
    const profileAvailLabel = document.getElementById('profileAvailLabel');
    let isSavingAvailability = false;

    function applyAvailability(availability) {
      const isOnline = String(availability || '').toLowerCase() === 'online';
      if (profileAvailToggle) profileAvailToggle.checked = isOnline;
      if (profileAvailLabel) profileAvailLabel.textContent = isOnline ? 'Online' : 'Offline';
    }

    async function syncAvailabilityFromServer() {
      if (!backendIsVerified) {
        applyAvailability('offline');
        return;
      }
      try {
        const res = await fetch('../api/provider_availability_api.php', { cache: 'no-store' });
        const data = await res.json();
        if (data.success) {
          applyAvailability(data.availability || 'offline');
        }
      } catch (e) {
        applyAvailability(backendAvailability);
      }
    }

    if (profileAvailToggle && profileAvailLabel) {
      profileAvailToggle.addEventListener('change', async function () {
        if (!backendIsVerified || isSavingAvailability) {
          applyAvailability('offline');
          return;
        }
        isSavingAvailability = true;
        const desired = this.checked ? 'online' : 'offline';
        const previous = this.checked ? 'offline' : 'online';
        try {
          const fd = new FormData();
          fd.append('availability', desired);
          const res = await fetch('../api/provider_availability_api.php', { method: 'POST', body: fd });
          const data = await res.json();
          if (!data.success) {
            applyAvailability(previous);
            return;
          }
          applyAvailability(data.availability || desired);
        } catch (e) {
          applyAvailability(previous);
        } finally {
          isSavingAvailability = false;
        }
      });
    }

    applyProfileUiState(backendProfileState);
    if (backendProfileState === 'verified') {
      syncAvailabilityFromServer();
    } else {
      applyAvailability('offline');
    }

    function openSettingsScreen() { document.getElementById('settingsScreen').classList.add('on'); }
    function closeSettingsScreen() { document.getElementById('settingsScreen').classList.remove('on'); }
    function openLogoutConfirm() { document.getElementById('logoutConfirmOl').classList.add('on'); }
    function closeLogoutConfirm() { document.getElementById('logoutConfirmOl').classList.remove('on'); }
    
    function openHelpCenter() {
      document.getElementById('helpCenterModal').style.display = 'flex';
      document.getElementById('helpCenterModal').style.flexDirection = 'column';
      document.getElementById('helpCenterModal').style.justifyContent = 'center';
    }

    function closeHelpCenter() {
      document.getElementById('helpCenterModal').style.display = 'none';
    }

    function toggleFaq(element) {
      const answer = element.nextElementSibling;
      const icon = element.querySelector('.bi-chevron-down');
      const isOpen = answer.style.display !== 'none';
      answer.style.display = isOpen ? 'none' : 'block';
      if (icon) {
        icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
      }
    }
    function confirmLogout() {
      closeLogoutConfirm();
      window.location.href = '../logout.php';
    }

    function to12Hour(timeValue) {
      if (!timeValue || String(timeValue).indexOf(':') === -1) return '8:00 AM';
      const parts = String(timeValue).split(':');
      const hour = Number(parts[0]);
      const mins = Number(parts[1]);
      const suffix = hour >= 12 ? 'PM' : 'AM';
      const hour12 = ((hour + 11) % 12) + 1;
      const minText = String(mins).padStart(2, '0');
      return hour12 + ':' + minText + ' ' + suffix;
    }

    function getPhoneText() {
      return providerUiState.phone || 'Not set';
    }

    function getAddressText() {
      return providerUiState.serviceArea || 'Not set';
    }

    function getManageServicesSummary() {
      const count = providerUiState.services.length;
      if (!count) return 'No services selected';
      if (count === 1) return providerUiState.services[0];
      return count + ' services selected';
    }

    function getDefaultAvatarUrl() {
      const encodedName = encodeURIComponent(providerUiState.name || 'Provider');
      return 'https://ui-avatars.com/api/?name=' + encodedName + '&background=FDECC8&color=E8820C&size=200';
    }

    function applyAvatarEverywhere(url) {
      const profileAvatar = document.getElementById('profileAvatar');
      const sheetAvatar = document.getElementById('sheetProfileAvatar');
      if (profileAvatar) profileAvatar.src = url;
      if (sheetAvatar) sheetAvatar.src = url;

      document.querySelectorAll('[data-provider-avatar-id="' + providerId + '"]').forEach(function (node) {
        if (node.tagName === 'IMG') {
          node.src = url;
        } else {
          node.style.backgroundImage = 'url("' + url + '")';
          node.style.backgroundSize = 'cover';
          node.style.backgroundPosition = 'center';
        }
      });
    }

    function loadSavedAvatar() {
      let savedAvatar = '';
      try {
        savedAvatar = localStorage.getItem(providerAvatarStorageKey) || '';
      } catch (e) {
        savedAvatar = '';
      }
      providerUiState.avatarUrl = savedAvatar || getDefaultAvatarUrl();
      applyAvatarEverywhere(providerUiState.avatarUrl);
    }

    function triggerProfilePhotoPicker() {
      const input = document.getElementById('sheetProfileAvatarInput');
      if (input) input.click();
    }

    function handleProfilePhotoPick(event) {
      const file = event && event.target && event.target.files ? event.target.files[0] : null;
      if (!file) return;
      if (!String(file.type || '').startsWith('image/')) {
        setSheetAlert('Please choose an image file.', 'err');
        return;
      }
      const reader = new FileReader();
      reader.onload = function (loadEvent) {
        const nextAvatar = String(loadEvent && loadEvent.target ? loadEvent.target.result || '' : '');
        if (!nextAvatar) return;
        providerUiState.avatarUrl = nextAvatar;
        applyAvatarEverywhere(nextAvatar);
        try {
          localStorage.setItem(providerAvatarStorageKey, nextAvatar);
        } catch (e) {
          // Ignore storage limit errors and keep in-memory preview.
        }
        const sheetAvatar = document.getElementById('sheetProfileAvatar');
        if (sheetAvatar) {
          sheetAvatar.classList.remove('avatar-pop');
          void sheetAvatar.offsetWidth;
          sheetAvatar.classList.add('avatar-pop');
        }
        setSheetAlert('Profile photo updated.', 'ok');
      };
      reader.readAsDataURL(file);
      event.target.value = '';
    }

    function renderServicesChecklist() {
      const wrap = document.getElementById('sheetServicesList');
      if (!wrap) return;
      wrap.innerHTML = providerServicesCatalog.map(function (service) {
        const checked = providerUiState.services.indexOf(service) !== -1 ? 'checked' : '';
        return '' +
          '<label class="sheet-service-item">' +
            '<span>' + service + '</span>' +
            '<input type="checkbox" class="sheet-service-check" value="' + service.replace(/"/g, '&quot;') + '" ' + checked + '>' +
          '</label>';
      }).join('');
    }

    function refreshProviderUi() {
      const nameEl = document.getElementById('profileName');
      const emailEl = document.getElementById('profileEmail');
      const phoneEl = document.getElementById('profilePhoneValue');
      const areaEl = document.getElementById('profileAddressValue');
      const specialtyEl = document.getElementById('profileSpecialty');
      const editSubEl = document.getElementById('settingsEditProfileSub');
      const serviceSubEl = document.getElementById('settingsManageServicesSub');

      if (nameEl) nameEl.textContent = providerUiState.name || 'Service Provider';
      if (emailEl) emailEl.textContent = providerUiState.email || '';
      if (phoneEl) phoneEl.textContent = getPhoneText();
      if (areaEl) areaEl.textContent = getAddressText();
      if (specialtyEl) specialtyEl.textContent = providerUiState.services[0] || providerUiState.specialty || 'General Services';
      if (editSubEl) editSubEl.textContent = (providerUiState.name || 'Name') + ', ' + getPhoneText() + ', ' + getAddressText();
      if (serviceSubEl) serviceSubEl.textContent = getManageServicesSummary();
      if (!providerUiState.avatarUrl || String(providerUiState.avatarUrl).indexOf('ui-avatars.com/api/?name=') !== -1) {
        providerUiState.avatarUrl = getDefaultAvatarUrl();
      }
      applyAvatarEverywhere(providerUiState.avatarUrl);
    }

    let activeSheetAction = 'edit-profile';

    function setSheetAlert(message, type) {
      const alert = document.getElementById('sheetAlert');
      if (!alert) return;
      if (!message) {
        alert.className = 'sheet-alert';
        alert.textContent = '';
        return;
      }
      alert.className = 'sheet-alert on ' + (type || 'ok');
      alert.textContent = message;
    }

    function populateActionFields(action) {
      if (action === 'phone') {
        document.getElementById('sheetPhoneInput').value = providerUiState.phone || '';
      }
      if (action === 'service-area') {
        document.getElementById('sheetServiceAreaInput').value = providerUiState.serviceArea || '';
      }
      if (action === 'edit-profile') {
        document.getElementById('sheetProfileName').value = providerUiState.name || '';
        document.getElementById('sheetProfileEmail').value = providerUiState.email || '';
        document.getElementById('sheetProfilePhone').value = providerUiState.phone || '';
        document.getElementById('sheetProfileAddress').value = providerUiState.serviceArea || '';
      }
      if (action === 'change-password') {
        document.getElementById('sheetCurrentPassword').value = '';
        document.getElementById('sheetNewPassword').value = '';
        document.getElementById('sheetConfirmPassword').value = '';
      }
      if (action === 'manage-services') {
        renderServicesChecklist();
      }
    }

    function openActionSheet(action) {
      const configMap = {
        phone: { title: 'Update Phone Number', sub: 'Update your contact number', save: 'Update' },
        'service-area': { title: 'Edit Address', sub: 'Update your address', save: 'Save' },
        'edit-profile': { title: 'Edit Profile', sub: 'Update profile information', save: 'Save' },
        'change-password': { title: 'Change Password', sub: 'Set a stronger password', save: 'Save' },
        'manage-services': { title: 'Manage Services', sub: 'Choose services you currently offer', save: 'Save' }
      };
      activeSheetAction = action;
      setSheetAlert('', 'ok');
      const config = configMap[action] || configMap['edit-profile'];
      document.getElementById('actionSheetTitle').textContent = config.title;
      document.getElementById('actionSheetSubTitle').textContent = config.sub;
      document.getElementById('actionSheetSaveBtn').textContent = config.save;
      document.querySelectorAll('.sheet-section').forEach(function (sectionEl) {
        sectionEl.classList.remove('on');
      });
      const targetByAction = {
        phone: 'sheetPhone',
        'service-area': 'sheetServiceArea',
        'edit-profile': 'sheetEditProfile',
        'change-password': 'sheetChangePassword',
        'manage-services': 'sheetManageServices'
      };
      const target = document.getElementById(targetByAction[action]);
      if (target) target.classList.add('on');
      populateActionFields(action);
      document.getElementById('actionSheetOl').classList.add('on');
    }

    function closeActionSheet() {
      document.getElementById('actionSheetOl').classList.remove('on');
      setSheetAlert('', 'ok');
    }

    function actionSheetBg(event) {
      if (event.target === document.getElementById('actionSheetOl')) {
        closeActionSheet();
      }
    }

    function saveActionSheet() {
      if (activeSheetAction === 'phone') {
        providerUiState.phone = document.getElementById('sheetPhoneInput').value.trim();
      }
      if (activeSheetAction === 'service-area') {
        providerUiState.serviceArea = document.getElementById('sheetServiceAreaInput').value.trim();
      }

      if (activeSheetAction === 'edit-profile') {
        providerUiState.name = document.getElementById('sheetProfileName').value.trim();
        providerUiState.email = document.getElementById('sheetProfileEmail').value.trim();
        providerUiState.phone = document.getElementById('sheetProfilePhone').value.trim();
        providerUiState.serviceArea = document.getElementById('sheetProfileAddress').value.trim();
      }
      if (activeSheetAction === 'change-password') {
        const current = document.getElementById('sheetCurrentPassword').value;
        const next = document.getElementById('sheetNewPassword').value;
        const confirm = document.getElementById('sheetConfirmPassword').value;
        if (!current || !next || !confirm) {
          setSheetAlert('Please complete all password fields.', 'err');
          return;
        }
        if (next.length < 8) {
          setSheetAlert('New password must be at least 8 characters long.', 'err');
          return;
        }
        if (!/[A-Z]/.test(next)) {
          setSheetAlert('New password must contain at least one uppercase letter.', 'err');
          return;
        }
        if (!/[a-z]/.test(next)) {
          setSheetAlert('New password must contain at least one lowercase letter.', 'err');
          return;
        }
        if (!/[0-9]/.test(next)) {
          setSheetAlert('New password must contain at least one number.', 'err');
          return;
        }
        if (next !== confirm) {
          setSheetAlert('New password and confirmation do not match.', 'err');
          return;
        }
      }
      if (activeSheetAction === 'manage-services') {
        const selected = Array.from(document.querySelectorAll('.sheet-service-check:checked')).map(function (node) {
          return node.value;
        });
        if (!selected.length) {
          setSheetAlert('Select at least one service.', 'err');
          return;
        }
        providerUiState.services = selected;
        providerUiState.specialty = selected[0];
      }

      refreshProviderUi();
      setSheetAlert('Saved successfully.', 'ok');
      setTimeout(function () {
        closeActionSheet();
      }, 700);
    }

    // Report Issue Functions
    function openReportModal() {
      document.getElementById('reportModalOl').classList.add('on');
    }

    function closeReportModal() {
      document.getElementById('reportModalOl').classList.remove('on');
      document.getElementById('reportCategory').value = '';
      document.getElementById('reportDescription').value = '';
      clearReportEvidence();
    }

    function triggerReportEvidencePicker() {
      document.getElementById('reportEvidenceInput').click();
    }

    function handleReportEvidenceSelected(input) {
      const file = input.files && input.files[0];
      if (!file) return;
      
      const allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (allowed.indexOf(file.type) === -1) {
        alert('Only JPG, PNG, or WEBP images are allowed.');
        input.value = '';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        alert('File must not exceed 5 MB.');
        input.value = '';
        return;
      }
      
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('reportEvidencePreview').src = e.target.result;
        document.getElementById('reportUploadDefault').style.display = 'none';
        document.getElementById('reportUploadFileName').textContent = file.name;
        document.getElementById('reportUploadChip').style.display = 'flex';
      };
      reader.readAsDataURL(file);
    }

    function clearReportEvidence() {
      document.getElementById('reportEvidenceInput').value = '';
      document.getElementById('reportEvidencePreview').src = '';
      document.getElementById('reportUploadDefault').style.display = 'flex';
      document.getElementById('reportUploadChip').style.display = 'none';
      document.getElementById('reportUploadFileName').textContent = '';
    }

    function submitReportForm() {
      const category = document.getElementById('reportCategory').value;
      const desc = document.getElementById('reportDescription').value.trim();
      if (!category) {
        alert('Please select a report category.');
        return;
      }
      if (!desc) {
        alert('Please provide a description of the issue.');
        return;
      }
      
      const btn = document.getElementById('reportSubmitBtn');
      btn.disabled = true;
      btn.textContent = 'Submitting...';

      const formData = new FormData();
      formData.append('category', category);
      formData.append('description', desc);
      
      const fileInput = document.getElementById('reportEvidenceInput');
      if (fileInput.files && fileInput.files[0]) {
        formData.append('evidence', fileInput.files[0]);
      }

      fetch('../api/submit_report.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        btn.textContent = 'Submit Report';
        if (data.success) {
          closeReportModal();
          openReportSuccess();
        } else {
          alert(data.message || 'Failed to submit report.');
        }
      })
      .catch(err => {
        btn.disabled = false;
        btn.textContent = 'Submit Report';
        alert('Network error. Please try again.');
      });
    }

    function openReportSuccess() {
      document.getElementById('reportSuccessOl').classList.add('on');
    }

    function closeReportSuccess() {
      document.getElementById('reportSuccessOl').classList.remove('on');
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeActionSheet();
        closeReportModal();
        closeReportSuccess();
        closeTermsModal();
      }
    });

    function openTermsModal() {
      document.getElementById('termsModal').style.display = 'flex';
      document.getElementById('termsModal').style.flexDirection = 'column';
      document.getElementById('termsModal').style.justifyContent = 'center';
    }

    function closeTermsModal() {
      document.getElementById('termsModal').style.display = 'none';
    }

    const sheetProfileAvatarInput = document.getElementById('sheetProfileAvatarInput');
    if (sheetProfileAvatarInput) {
      sheetProfileAvatarInput.addEventListener('change', handleProfilePhotoPick);
    }

    function updateProviderPwdStrength(val) {
      const wrap = document.getElementById('provPwdStrength');
      wrap.style.display = val.length > 0 ? 'block' : 'none';
      const hasLen   = val.length >= 8;
      const hasUpper = /[A-Z]/.test(val);
      const hasLower = /[a-z]/.test(val);
      const hasNum   = /[0-9]/.test(val);
      const score = [hasLen, hasUpper, hasLower, hasNum].filter(Boolean).length;
      const barColor = score <= 1 ? '#ef4444' : score === 2 ? '#f59e0b' : score === 3 ? '#10b981' : '#059669';
      for (let i = 1; i <= 4; i++) {
        document.getElementById('ppbar' + i).style.background = i <= score ? barColor : '#EDE8E0';
      }
      function setReq(id, met) {
        const el = document.getElementById(id);
        el.style.color = met ? '#059669' : '#8E8E93';
        const ic = el.querySelector('i');
        if (ic) ic.className = met ? 'bi bi-check-circle-fill' : 'bi bi-circle-fill';
      }
      setReq('preq-len',   hasLen);
      setReq('preq-upper', hasUpper);
      setReq('preq-lower', hasLower);
      setReq('preq-num',   hasNum);
    }

    loadSavedAvatar();
    refreshProviderUi();
  </script>

  <!-- ══════════════════════════════════════════════════════════════
       QR CHANGE REQUEST SCREEN
  ══════════════════════════════════════════════════════════════ -->
  <div id="qrChangeScreen" style="display:none;position:absolute;inset:0;background:var(--bg-screen,#f8fafc);z-index:150;flex-direction:column;overflow:hidden;">

    <!-- Header -->
    <div style="display:flex;align-items:center;gap:12px;padding:52px 18px 16px;background:var(--bg-screen,#f8fafc);flex-shrink:0;border-bottom:1px solid var(--border-col,#e5e7eb);">
      <button onclick="closeQrChangeScreen()" style="width:36px;height:36px;border-radius:50%;border:none;background:var(--bg-card,#fff);color:var(--txt-muted,#64748b);font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-arrow-left"></i>
      </button>
      <div>
        <div style="font-family:'Poppins',sans-serif;font-size:18px;font-weight:800;color:var(--txt-primary,#0f172a);">QR Change Request</div>
        <div style="font-size:11px;color:var(--txt-muted,#64748b);font-weight:600;">GCash / Bank Transfer</div>
      </div>
    </div>

    <!-- Scrollable body -->
    <div style="flex:1;overflow-y:auto;padding:18px 18px 100px;">

      <!-- Alert banner -->
      <div id="qrChangeAlert" style="display:none;border-radius:12px;padding:11px 14px;font-size:13px;font-weight:700;margin-bottom:14px;"></div>

      <!-- Info notice -->
      <div style="background:linear-gradient(135deg,rgba(5,150,105,.08),rgba(16,185,129,.04));border:1.5px solid rgba(5,150,105,.2);border-radius:14px;padding:14px 16px;margin-bottom:18px;display:flex;gap:12px;align-items:flex-start;">
        <i class="bi bi-info-circle-fill" style="color:#059669;font-size:18px;flex-shrink:0;margin-top:1px;"></i>
        <div style="font-size:12px;color:#065f46;line-height:1.55;font-weight:600;">To update your GCash or Bank Transfer QR code, fill out this form. An admin will review your request before the change takes effect. This process protects your account from unauthorized payment changes.</div>
      </div>

      <!-- Current Payment Method (read-only) -->
      <div style="background:var(--bg-card,#fff);border-radius:16px;border:1.5px solid var(--border-col,#e5e7eb);padding:16px;margin-bottom:16px;">
        <div style="font-size:11px;font-weight:800;color:var(--txt-muted,#64748b);text-transform:uppercase;letter-spacing:.4px;margin-bottom:12px;">Current Payment Method</div>
        <div id="qrCurrentInfo">
          <div style="display:flex;align-items:center;gap:8px;color:var(--txt-muted,#64748b);font-size:13px;font-weight:600;">
            <i class="bi bi-arrow-clockwise" style="animation:qr-spin .9s linear infinite;"></i> Loading...
          </div>
        </div>
      </div>

      <!-- Form -->
      <div style="background:var(--bg-card,#fff);border-radius:16px;border:1.5px solid var(--border-col,#e5e7eb);padding:16px;margin-bottom:16px;">
        <div style="font-size:11px;font-weight:800;color:var(--txt-muted,#64748b);text-transform:uppercase;letter-spacing:.4px;margin-bottom:14px;">Change Request Details</div>

        <!-- Reason -->
        <div style="margin-bottom:16px;">
          <label style="display:block;font-size:12px;font-weight:700;color:var(--txt-primary,#0f172a);margin-bottom:6px;">Reason for Change <span style="color:#ef4444;">*</span></label>
          <textarea id="qrChangeReason" rows="4" placeholder="Explain why you need to change your QR code (e.g., account blocked, limit reached, account migration)..." style="width:100%;border:1.5px solid var(--border-col,#e5e7eb);border-radius:12px;padding:11px 13px;font-family:'Nunito',sans-serif;font-size:13px;color:var(--txt-primary,#0f172a);background:var(--bg-screen,#f8fafc);resize:vertical;outline:none;box-sizing:border-box;line-height:1.55;transition:border-color .2s;"></textarea>
        </div>

        <!-- New QR Upload -->
        <div>
          <label style="display:block;font-size:12px;font-weight:700;color:var(--txt-primary,#0f172a);margin-bottom:6px;">Upload New QR Code <span style="color:#ef4444;">*</span></label>
          <input type="file" id="qrNewFileInput" accept="image/jpeg,image/png,image/webp" style="display:none;" onchange="onQrFileSelected(this)">
          <div id="qrUploadArea" onclick="document.getElementById('qrNewFileInput').click()" style="border:2px dashed var(--border-col,#e5e7eb);border-radius:14px;padding:24px 16px;text-align:center;cursor:pointer;transition:all .2s;background:var(--bg-screen,#f8fafc);">
            <i class="bi bi-qr-code" style="font-size:32px;color:#cbd5e1;display:block;margin-bottom:8px;"></i>
            <div style="font-size:13px;font-weight:700;color:var(--txt-muted,#64748b);">Tap to upload QR image</div>
            <div style="font-size:11px;color:#94a3b8;margin-top:4px;">JPG, PNG, WEBP · max 5 MB</div>
          </div>
          <!-- Preview -->
          <div id="qrPreviewWrap" style="display:none;margin-top:12px;position:relative;">
            <img id="qrPreviewImg" src="" alt="QR Preview" style="width:100%;max-height:220px;object-fit:contain;border-radius:12px;border:1.5px solid var(--border-col,#e5e7eb);background:#f8fafc;">
            <button onclick="clearQrFile()" style="position:absolute;top:8px;right:8px;width:28px;height:28px;border-radius:50%;border:none;background:#ef4444;color:#fff;font-size:14px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
              <i class="bi bi-x-lg"></i>
            </button>
            <div id="qrFileName" style="font-size:11px;color:var(--txt-muted,#64748b);margin-top:6px;text-align:center;font-weight:600;"></div>
          </div>
        </div>
      </div>

      <!-- Submit button -->
      <button id="qrSubmitBtn" onclick="openQrConfirmModal()" style="width:100%;padding:15px;border-radius:50px;border:none;background:linear-gradient(135deg,#059669,#10b981);color:#fff;font-family:'Poppins',sans-serif;font-size:15px;font-weight:800;cursor:pointer;box-shadow:0 8px 20px rgba(5,150,105,.3);transition:all .2s;margin-bottom:20px;">
        <i class="bi bi-send-fill" style="margin-right:6px;"></i> Submit Request
      </button>

      <!-- Request History -->
      <div style="background:var(--bg-card,#fff);border-radius:16px;border:1.5px solid var(--border-col,#e5e7eb);overflow:hidden;">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border-col,#e5e7eb);display:flex;align-items:center;justify-content:space-between;">
          <div style="font-size:13px;font-weight:800;color:var(--txt-primary,#0f172a);">My Past Requests</div>
          <button onclick="loadMyQrRequests()" style="background:none;border:none;color:var(--txt-muted,#64748b);font-size:16px;cursor:pointer;"><i class="bi bi-arrow-clockwise"></i></button>
        </div>
        <div id="qrHistoryList" style="padding:10px 0;">
          <div style="text-align:center;padding:20px;color:var(--txt-muted,#64748b);font-size:13px;font-weight:600;">
            <i class="bi bi-arrow-clockwise" style="animation:qr-spin .9s linear infinite;"></i> Loading...
          </div>
        </div>
      </div>

    </div><!-- /scroll -->
  </div><!-- /qrChangeScreen -->

  <!-- QR Submit Confirmation Modal -->
  <div id="qrConfirmModal" style="display:none;position:absolute;inset:0;background:rgba(15,23,42,.52);z-index:200;align-items:center;justify-content:center;padding:20px;">
    <div style="width:100%;max-width:340px;background:var(--bg-card,#fff);border-radius:22px;padding:22px;box-shadow:0 20px 50px rgba(0,0,0,.18);transform:translateY(0);">
      <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,#d1fae5,#a7f3d0);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
        <i class="bi bi-qr-code" style="font-size:22px;color:#059669;"></i>
      </div>
      <div style="font-family:'Poppins',sans-serif;font-size:17px;font-weight:800;color:var(--txt-primary,#0f172a);text-align:center;margin-bottom:8px;">Submit QR Change Request?</div>
      <div style="font-size:12px;color:var(--txt-muted,#64748b);text-align:center;line-height:1.6;margin-bottom:18px;">Are you sure you want to submit a request to change your GCash/Bank Transfer QR code? This request will be reviewed by an administrator before it takes effect.</div>
      <div style="display:flex;gap:10px;">
        <button onclick="closeQrConfirmModal()" style="flex:1;padding:12px;border-radius:12px;border:1.5px solid var(--border-col,#e5e7eb);background:transparent;color:var(--txt-muted,#64748b);font-family:'Poppins',sans-serif;font-size:13px;font-weight:700;cursor:pointer;">Cancel</button>
        <button id="qrConfirmOkBtn" onclick="submitQrChangeRequest()" style="flex:1;padding:12px;border-radius:12px;border:none;background:linear-gradient(135deg,#059669,#10b981);color:#fff;font-family:'Poppins',sans-serif;font-size:13px;font-weight:800;cursor:pointer;box-shadow:0 6px 14px rgba(5,150,105,.28);">Yes, Submit</button>
      </div>
    </div>
  </div>

  <style>
    @keyframes qr-spin { to { transform: rotate(360deg); } }
    #qrChangeReason:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,.16); }
    #qrUploadArea:hover { border-color: #10b981; background: rgba(16,185,129,.04); }
    .qr-hist-item { display:flex;align-items:flex-start;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border-col,#e5e7eb); }
    .qr-hist-item:last-child { border-bottom:none; }
    .qr-status-pill { display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:10px;font-weight:800;letter-spacing:.2px; }
    .qr-status-pill.pending  { background:#fff7ed;color:#c2410c; }
    .qr-status-pill.approved { background:#d1fae5;color:#065f46; }
    .qr-status-pill.rejected { background:#fee2e2;color:#b91c1c; }
  </style>

  <script>
    var qrSelectedFile = null;

    // Re-parent QR overlays into the #app shell so they respect mobile layout
    (function() {
      var appShell = document.getElementById('app');
      var qrScreen = document.getElementById('qrChangeScreen');
      var qrModal  = document.getElementById('qrConfirmModal');
      if (appShell && qrScreen) appShell.appendChild(qrScreen);
      if (appShell && qrModal)  appShell.appendChild(qrModal);
    })();

    function openQrChangeScreen() {
      closeSettingsScreen();
      var scr = document.getElementById('qrChangeScreen');
      scr.style.display = 'flex';
      loadCurrentQrInfo();
      loadMyQrRequests();
      updateQrSettingsSub();
    }

    function closeQrChangeScreen() {
      document.getElementById('qrChangeScreen').style.display = 'none';
    }

    function showQrAlert(msg, type) {
      var el = document.getElementById('qrChangeAlert');
      if (!el) return;
      if (!msg) { el.style.display = 'none'; return; }
      el.style.display = 'block';
      var isErr = type === 'err';
      el.style.background = isErr ? '#fee2e2' : '#d1fae5';
      el.style.color = isErr ? '#b91c1c' : '#065f46';
      el.style.border = '1.5px solid ' + (isErr ? '#fca5a5' : '#6ee7b7');
      el.textContent = msg;
    }

    function loadCurrentQrInfo() {
      var box = document.getElementById('qrCurrentInfo');
      if (!box) return;
      box.innerHTML = '<div style="display:flex;align-items:center;gap:8px;color:var(--txt-muted,#64748b);font-size:13px;font-weight:600;"><i class="bi bi-arrow-clockwise" style="animation:qr-spin .9s linear infinite;"></i> Loading...</div>';
      fetch('../api/qr_change_api.php?action=current_qr', { cache: 'no-store' })
        .then(function(r){ return r.json(); })
        .then(function(data) {
          if (!data.success) { box.innerHTML = '<div style="color:#64748b;font-size:13px;">Could not load QR info.</div>'; return; }
          var html = '';
          if (data.has_gcash || data.has_bank) {
            if (data.has_gcash) {
              html += '<div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">';
              html += '<img src="../' + escHtml(data.gcash_qr) + '" alt="GCash QR" style="width:64px;height:64px;object-fit:contain;border-radius:10px;border:1.5px solid #e5e7eb;background:#f8fafc;">';
              html += '<div><div style="font-size:13px;font-weight:700;color:#0f172a;">GCash QR</div><div style="font-size:11px;color:#64748b;">Active</div></div></div>';
            }
            if (data.has_bank) {
              html += '<div style="display:flex;align-items:center;gap:12px;">';
              html += '<img src="../' + escHtml(data.bank_qr) + '" alt="Bank QR" style="width:64px;height:64px;object-fit:contain;border-radius:10px;border:1.5px solid #e5e7eb;background:#f8fafc;">';
              html += '<div><div style="font-size:13px;font-weight:700;color:#0f172a;">Bank Transfer QR</div><div style="font-size:11px;color:#64748b;">Active</div></div></div>';
            }
          } else {
            html = '<div style="display:flex;align-items:center;gap:8px;color:#94a3b8;font-size:13px;font-weight:600;"><i class="bi bi-qr-code"></i> No QR code configured yet.</div>';
          }
          box.innerHTML = html;
        })
        .catch(function() {
          box.innerHTML = '<div style="color:#64748b;font-size:13px;">Could not load QR info.</div>';
        });
    }

    function onQrFileSelected(input) {
      var file = input.files && input.files[0];
      if (!file) return;
      var allowed = ['image/jpeg','image/png','image/webp'];
      if (allowed.indexOf(file.type) === -1) {
        showQrAlert('Only JPG, PNG, or WEBP images are allowed.', 'err');
        input.value = '';
        return;
      }
      if (file.size > 5 * 1024 * 1024) {
        showQrAlert('File must not exceed 5 MB.', 'err');
        input.value = '';
        return;
      }
      qrSelectedFile = file;
      var reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('qrPreviewImg').src = e.target.result;
        document.getElementById('qrFileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('qrUploadArea').style.display = 'none';
        document.getElementById('qrPreviewWrap').style.display = 'block';
      };
      reader.readAsDataURL(file);
      showQrAlert('', '');
    }

    function clearQrFile() {
      qrSelectedFile = null;
      document.getElementById('qrNewFileInput').value = '';
      document.getElementById('qrPreviewWrap').style.display = 'none';
      document.getElementById('qrUploadArea').style.display = 'block';
      document.getElementById('qrPreviewImg').src = '';
    }

    function openQrConfirmModal() {
      showQrAlert('', '');
      var reason = (document.getElementById('qrChangeReason').value || '').trim();
      if (!reason) {
        showQrAlert('Please enter a reason for the change.', 'err');
        document.getElementById('qrChangeReason').focus();
        return;
      }
      if (!qrSelectedFile) {
        showQrAlert('Please upload your new QR code image.', 'err');
        return;
      }
      var modal = document.getElementById('qrConfirmModal');
      modal.style.display = 'flex';
    }

    function closeQrConfirmModal() {
      document.getElementById('qrConfirmModal').style.display = 'none';
    }

    function submitQrChangeRequest() {
      var btn = document.getElementById('qrConfirmOkBtn');
      btn.disabled = true;
      btn.textContent = 'Submitting...';

      var fd = new FormData();
      fd.append('action', 'submit');
      fd.append('reason', document.getElementById('qrChangeReason').value.trim());
      fd.append('new_qr', qrSelectedFile);

      fetch('../api/qr_change_api.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
          closeQrConfirmModal();
          btn.disabled = false;
          btn.textContent = 'Yes, Submit';
          if (data.success) {
            showQrAlert('Request submitted successfully! Pending admin review.', 'ok');
            document.getElementById('qrChangeReason').value = '';
            clearQrFile();
            loadMyQrRequests();
            updateQrSettingsSub();
          } else {
            showQrAlert(data.message || 'Submission failed. Please try again.', 'err');
          }
        })
        .catch(function() {
          closeQrConfirmModal();
          btn.disabled = false;
          btn.textContent = 'Yes, Submit';
          showQrAlert('Network error. Please try again.', 'err');
        });
    }

    function loadMyQrRequests() {
      var list = document.getElementById('qrHistoryList');
      if (!list) return;
      list.innerHTML = '<div style="text-align:center;padding:20px;color:var(--txt-muted,#64748b);font-size:13px;font-weight:600;"><i class="bi bi-arrow-clockwise" style="animation:qr-spin .9s linear infinite;"></i> Loading...</div>';
      fetch('../api/qr_change_api.php?action=my_requests', { cache: 'no-store' })
        .then(function(r){ return r.json(); })
        .then(function(data) {
          if (!data.success || !data.requests.length) {
            list.innerHTML = '<div style="text-align:center;padding:24px 16px;color:#94a3b8;font-size:13px;font-weight:600;"><i class="bi bi-inbox" style="display:block;font-size:26px;margin-bottom:6px;"></i>No requests submitted yet.</div>';
            return;
          }
          list.innerHTML = data.requests.map(function(req) {
            var statusClass = req.status;
            var statusLabel = req.status === 'pending' ? 'Pending Review' : req.status.charAt(0).toUpperCase() + req.status.slice(1);
            var date = req.submitted_at ? req.submitted_at.substring(0,10) : '–';
            var reviewedInfo = '';
            if (req.status === 'rejected' && req.admin_remarks) {
              reviewedInfo = '<div style="font-size:11px;color:#b91c1c;margin-top:4px;font-weight:600;">Remarks: ' + escHtml(req.admin_remarks) + '</div>';
            }
            if (req.status === 'approved') {
              reviewedInfo = '<div style="font-size:11px;color:#059669;margin-top:4px;font-weight:600;">Approved – new QR is now active.</div>';
            }
            return '<div class="qr-hist-item">' +
              '<div style="flex-shrink:0;width:42px;height:42px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">' +
                '<i class="bi bi-qr-code" style="font-size:18px;color:#059669;"></i>' +
              '</div>' +
              '<div style="flex:1;min-width:0;">' +
                '<div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">' +
                  '<span class="qr-status-pill ' + statusClass + '">' + statusLabel + '</span>' +
                  '<span style="font-size:10px;color:#94a3b8;font-weight:600;">' + escHtml(date) + '</span>' +
                '</div>' +
                '<div style="font-size:12px;color:var(--txt-primary,#0f172a);font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(req.reason) + '</div>' +
                reviewedInfo +
              '</div>' +
            '</div>';
          }).join('');
        })
        .catch(function() {
          list.innerHTML = '<div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">Could not load request history.</div>';
        });
    }

    function updateQrSettingsSub() {
      fetch('../api/qr_change_api.php?action=my_requests', { cache: 'no-store' })
        .then(function(r){ return r.json(); })
        .then(function(data) {
          var sub = document.getElementById('qrChangeSettingsSub');
          if (!sub) return;
          var pending = data.requests && data.requests.filter(function(r){ return r.status==='pending'; });
          if (pending && pending.length) {
            sub.textContent = '1 request pending review';
            sub.style.color = '#c2410c';
          } else {
            sub.textContent = 'Request a QR code change';
            sub.style.color = '';
          }
        })
        .catch(function(){});
    }

    function escHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
    }

    // Initialise sub-label on page load
    updateQrSettingsSub();
  </script>

</body>

</html>
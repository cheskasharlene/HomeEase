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

     
        <div class="p-stats">
          <div class="p-stat">
            <div class="p-stat-val">24</div>
            <div class="p-stat-lbl">Jobs Done</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val">4.9</div>
            <div class="p-stat-lbl">Rating</div>
          </div>
          <div class="p-stat">
            <div class="p-stat-val">6</div>
            <div class="p-stat-lbl">Yrs Exp.</div>
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
                <div class="p-row-lbl">Service Area</div>
                <div class="p-row-sub" id="profileServiceAreaValue"><?= $address ?></div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
            <div class="p-row actionable" onclick="openActionSheet('working-hours')">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="#F5A623" stroke-width="2" />
                  <path d="M12 6v6l4 2" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Working Hours</div>
                <div class="p-row-sub" id="profileWorkingHoursValue">Mon-Sat, 8:00 AM - 6:00 PM</div>
              </div>
              <i class="bi bi-chevron-right p-row-arrow"></i>
            </div>
          </div>

          <?php if ($isVerified): ?>
            <div class="p-sec">
              <div class="p-sec-ttl">Services & Portfolio</div>
              <div class="p-row" onclick="goPage('provider_services.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <path
                      d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"
                      stroke="#F5A623" stroke-width="2" />
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">My Services</div>
                  <div class="p-row-sub">Manage what you offer</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
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
              <div class="p-row" onclick="goPage('provider_earnings.php')">
                <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                    <rect x="2" y="7" width="20" height="14" rx="2" stroke="#F5A623" stroke-width="2" />
                    <path d="M16 7v-2a2 2 0 00-2-2h-4a2 2 0 00-2 2v2M7 11h10M7 15h10" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                  </svg></div>
                <div class="p-row-info">
                  <div class="p-row-lbl">Earnings</div>
                  <div class="p-row-sub">Track your income</div>
                </div>
                <i class="bi bi-chevron-right p-row-arrow"></i>
              </div>
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
            </div>
          <?php endif; ?>
        </div>
      </div>

      <div class="bnav">
        <?php if ($isVerified): ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
          <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span
            class="nl">Calendar</span></div>
          <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
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
        </div>
        
        <div class="st-sec">
          <div class="st-sec-ttl">Notifications</div>
          <div class="st-row">
            <div class="st-ic orange"><i class="bi bi-bell-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">New Job Requests</div>
              <div class="st-row-sub">Get notified of new requests</div>
            </div>
            <div class="st-toggle on" onclick="this.classList.toggle('on')"></div>
          </div>
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

    <div class="modal-overlay" id="helpCenterModal" onclick="if(event.target===this)closeHelpCenter()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;overflow-y:auto;">
      <div class="modal-card" style="background:#fff;border-radius:20px;max-width:650px;margin:40px auto;box-shadow:0 10px 40px rgba(0,0,0,.15);">
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
                <label class="edit-flbl">Service Area</label>
                <input class="edit-fin" id="sheetServiceAreaInput" type="text" placeholder="Enter service area">
              </div>
            </div>
          </div>

          <div class="sheet-section" id="sheetWorkingHours">
            <div class="edit-modal-form sheet-time-grid">
              <div class="edit-fg">
                <label class="edit-flbl">Start Time</label>
                <input class="edit-fin" id="sheetStartTime" type="time" value="08:00">
              </div>
              <div class="edit-fg">
                <label class="edit-flbl">End Time</label>
                <input class="edit-fin" id="sheetEndTime" type="time" value="18:00">
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
                <input class="edit-fin" id="sheetNewPassword" type="password" placeholder="New password">
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
      'Home Cleaning',
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

    function formatWorkingHoursText() {
      return 'Mon-Sat, ' + to12Hour(providerUiState.workingStart) + ' - ' + to12Hour(providerUiState.workingEnd);
    }

    function getPhoneText() {
      return providerUiState.phone || 'Not set';
    }

    function getServiceAreaText() {
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
      const areaEl = document.getElementById('profileServiceAreaValue');
      const hoursEl = document.getElementById('profileWorkingHoursValue');
      const specialtyEl = document.getElementById('profileSpecialty');
      const editSubEl = document.getElementById('settingsEditProfileSub');
      const serviceSubEl = document.getElementById('settingsManageServicesSub');

      if (nameEl) nameEl.textContent = providerUiState.name || 'Service Provider';
      if (emailEl) emailEl.textContent = providerUiState.email || '';
      if (phoneEl) phoneEl.textContent = getPhoneText();
      if (areaEl) areaEl.textContent = getServiceAreaText();
      if (hoursEl) hoursEl.textContent = formatWorkingHoursText();
      if (specialtyEl) specialtyEl.textContent = providerUiState.services[0] || providerUiState.specialty || 'General Services';
      if (editSubEl) editSubEl.textContent = (providerUiState.name || 'Name') + ', ' + getPhoneText() + ', ' + getServiceAreaText();
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
      if (action === 'working-hours') {
        document.getElementById('sheetStartTime').value = providerUiState.workingStart || '08:00';
        document.getElementById('sheetEndTime').value = providerUiState.workingEnd || '18:00';
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
        'service-area': { title: 'Edit Service Area', sub: 'Set where you can accept jobs', save: 'Save' },
        'working-hours': { title: 'Update Working Hours', sub: 'Choose your available time window', save: 'Save' },
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
        'working-hours': 'sheetWorkingHours',
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
      if (activeSheetAction === 'working-hours') {
        const start = document.getElementById('sheetStartTime').value;
        const end = document.getElementById('sheetEndTime').value;
        if (!start || !end) {
          setSheetAlert('Please select both start and end time.', 'err');
          return;
        }
        if (start >= end) {
          setSheetAlert('End time must be later than start time.', 'err');
          return;
        }
        providerUiState.workingStart = start;
        providerUiState.workingEnd = end;
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
        if (next.length < 6) {
          setSheetAlert('New password must be at least 6 characters.', 'err');
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

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') closeActionSheet();
    });

    const sheetProfileAvatarInput = document.getElementById('sheetProfileAvatarInput');
    if (sheetProfileAvatarInput) {
      sheetProfileAvatarInput.addEventListener('change', handleProfilePhotoPick);
    }

    loadSavedAvatar();
    refreshProviderUi();
  </script>
</body>

</html>
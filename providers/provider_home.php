<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}

require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/provider_access.php';

$access = enforceProviderSectionAccess('home', $conn);
$verificationState = $access['state'];
$isVerified = $access['is_verified'];

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 18 ? 'Good Afternoon' : 'Good Evening');
$providerName = htmlspecialchars($_SESSION['provider_name'] ?? 'Provider');
$providerPhone = htmlspecialchars($_SESSION['provider_phone'] ?? '');
$providerAddress = htmlspecialchars($_SESSION['provider_address'] ?? '');
$providerSpecialty = htmlspecialchars($_SESSION['provider_specialty'] ?? 'Service Provider');
$providerId = (int) ($_SESSION['provider_id'] ?? 0);

$availabilityStatus = 'offline';
if ($isVerified) {
  $availabilityStmt = $conn->prepare('SELECT COALESCE(availability_status, "offline") AS availability_status FROM service_providers WHERE provider_id = ? LIMIT 1');
  if ($availabilityStmt) {
    $availabilityStmt->bind_param('i', $providerId);
    $availabilityStmt->execute();
    $availabilityRow = $availabilityStmt->get_result()->fetch_assoc();
    $availabilityStmt->close();
    $currentAvailability = strtolower(trim((string) ($availabilityRow['availability_status'] ?? 'offline')));
    $availabilityStatus = in_array($currentAvailability, ['available', 'online'], true) ? 'online' : 'offline';
  }
}

require_once __DIR__ . '/provider_dashboard_data.php';
$dashboardSummary = providerDashboardSummary();
$dashboardReviews = providerDashboardReviews();
$reviewPreview = $dashboardReviews[0] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase - Provider Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="../assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/provider_home.css">
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

    <div class="screen" id="home">
      <div class="ph-scroll" id="phScroll">
        <div class="ph-hdr">
          <div class="ph-top">
            <div style="display:flex;align-items:center;gap:12px;position:relative;z-index:1;">
              <div style="width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;color:#fff;border:2px solid rgba(255,255,255,.4);">
                <?= strtoupper(substr($providerName, 0, 1)) ?>
              </div>
              <div class="ph-info">
                <div class="ph-greet"><?= $greeting ?></div>
                <div class="ph-name"><?= $providerName ?></div>
                <div class="ph-badge"><i class="bi bi-tools" style="font-size:9px;margin-right:4px;"></i><?= $providerSpecialty ?></div>
                <div class="verified-pill" id="verifiedPill" style="<?= $isVerified ? '' : 'display:none;' ?>"><i class="bi bi-patch-check-fill"></i> Verified Provider</div>
              </div>
            </div>
            <div class="ph-right">
            </div>
          </div>
          <div class="avail-wrap" id="availWrap" style="<?= $isVerified ? '' : 'display:none;' ?>">
            <div class="avail-lbl">Status:</div>
            <label class="avail-sw">
              <input type="checkbox" id="availToggle" <?= ($isVerified && $availabilityStatus === 'online') ? 'checked' : '' ?> <?= $isVerified ? '' : 'disabled' ?> >
              <span class="avail-slider"></span>
            </label>
            <div class="avail-status" id="availLabel"><?= ($isVerified && $availabilityStatus === 'online') ? 'Online' : 'Offline' ?></div>
          </div>
        </div>

        <?php if (isset($_GET['restricted'])): ?>
          <div class="restriction-note"><i class="bi bi-lock-fill"></i> Verify your account first to unlock Requests, Schedule, and Earnings.</div>
        <?php endif; ?>

        <div class="verify-flow" id="verifyFlow">
          <section class="verify-panel not-verified" id="panelNotVerified">
            <div class="verify-card">
              <div class="verify-card-icon"><i class="bi bi-shield-lock-fill"></i></div>
              <h2>Become a Verified Provider</h2>
              <p>Submit the requirements below. Service-specific fields update automatically after you choose your service.</p>
            </div>

            <div class="verify-card">
              <div class="verify-subttl">Service Type</div>
              <div class="section-divider"></div>
              <input type="text" class="vinput" value="<?= $providerSpecialty ?>" readonly style="background:#f7f7f7;cursor:not-allowed;">
              <input type="hidden" name="selected_service" id="selectedService" value="<?= $providerSpecialty ?>">
            </div>

            <div class="verify-card">
              <div class="verify-subttl">General Requirements</div>
              <div class="section-divider"></div>
              <ul class="verify-checklist">
                <li><i class="bi bi-check-circle-fill"></i> Valid Government ID</li>
                <li><i class="bi bi-check-circle-fill"></i> Selfie Verification</li>
                <li><i class="bi bi-check-circle-fill"></i> Proof of Address</li>
                <li><i class="bi bi-check-circle-fill"></i> Basic Profile Info</li>
                <li><i class="bi bi-check-circle-fill"></i> Selected Service</li>
              </ul>
              <div class="upload-grid" style="margin-top:12px;">
                <!-- Valid Government ID (Required) -->
                <div class="upload-wrapper" id="wrapUploadIdDoc">
                  <label class="upload-slot" for="uploadIdDoc">
                    <input type="file" id="uploadIdDoc" accept="image/*,application/pdf" />
                    <i class="bi bi-card-image"></i>
                    <div>
                      <span>Valid Government ID <span class="req-asterisk">*</span></span>
                      <small id="fileNameUploadIdDoc">Tap to upload</small>
                    </div>
                  </label>
                  <div class="upload-feedback" id="feedbackUploadIdDoc"></div>
                  <div class="upload-preview" id="previewUploadIdDoc"></div>
                </div>

                <!-- Selfie Verification (Required) -->
                <div class="upload-wrapper" id="wrapUploadSelfieDoc">
                  <label class="upload-slot" for="uploadSelfieDoc">
                    <input type="file" id="uploadSelfieDoc" accept="image/*" />
                    <i class="bi bi-person-bounding-box"></i>
                    <div>
                      <span>Selfie Verification <span class="req-asterisk">*</span></span>
                      <small id="fileNameUploadSelfieDoc">Tap to upload</small>
                    </div>
                  </label>
                  <div class="upload-feedback" id="feedbackUploadSelfieDoc"></div>
                  <div class="upload-preview" id="previewUploadSelfieDoc"></div>
                </div>

                <!-- Proof of Address (Required) -->
                <div class="upload-wrapper" id="wrapUploadAddressDoc">
                  <label class="upload-slot" for="uploadAddressDoc">
                    <input type="file" id="uploadAddressDoc" accept="image/*,application/pdf" />
                    <i class="bi bi-house-check-fill"></i>
                    <div>
                      <span>Proof of Address <span class="req-asterisk">*</span></span>
                      <small id="fileNameUploadAddressDoc">Tap to upload</small>
                    </div>
                  </label>
                  <div class="upload-feedback" id="feedbackUploadAddressDoc"></div>
                  <div class="upload-preview" id="previewUploadAddressDoc"></div>
                </div>

                <!-- Barangay Clearance (Optional) -->
                <div class="upload-wrapper" id="wrapUploadCertification">
                  <label class="upload-slot" for="uploadCertification">
                    <input type="file" id="uploadCertification" accept="image/*,application/pdf" />
                    <i class="bi bi-award-fill"></i>
                    <div>
                      <span id="serviceCertLabel">Barangay Clearance</span>
                      <small id="fileNameUploadCertification">Tap to upload (optional)</small>
                    </div>
                  </label>
                  <div class="upload-feedback" id="feedbackUploadCertification"></div>
                  <div class="upload-preview" id="previewUploadCertification"></div>
                </div>
              </div>
              <div class="profile-grid">
                <div>
                  <label class="vlabel">Full Name</label>
                  <input id="profileName" class="vinput" type="text" value="<?= $providerName ?>">
                </div>
                <div>
                  <label class="vlabel">Phone</label>
                  <input id="profilePhone" class="vinput" type="text" value="<?= $providerPhone ?>">
                </div>
                <div class="full">
                  <label class="vlabel">Address</label>
                  <input id="profileAddress" class="vinput" type="text" value="<?= $providerAddress ?>">
                </div>
              </div>
            </div>

            <div class="verify-card" id="serviceSpecificSection">
              <div class="verify-subttl">Service-Specific Requirements</div>
              <div class="section-divider"></div>

              <div class="mini-section-title">Tools & Kits</div>
              <div class="upload-grid">
                <div class="upload-wrapper" id="wrapUploadServiceProof">
                  <label class="upload-slot" for="uploadServiceProof">
                    <input type="file" id="uploadServiceProof" accept="image/*,application/pdf" />
                    <i class="bi bi-images"></i>
                    <div>
                      <span id="serviceProofLabel">Tools & Kits <span class="req-asterisk">*</span></span>
                      <small id="fileNameUploadServiceProof">Tap to upload</small>
                    </div>
                  </label>
                  <div class="upload-feedback" id="feedbackUploadServiceProof"></div>
                  <div class="upload-preview" id="previewUploadServiceProof"></div>
                </div>
              </div>

              <div class="mini-section-title">Working Experience <span class="req-asterisk">*</span></div>
              <textarea id="experienceDescription" class="vtextarea" rows="4" placeholder="Describe your experience, work samples, and years in this service."></textarea>
              <div class="validation-message error" id="experienceValidation" style="display:none;">Experience description is required</div>
            </div>

            <div class="verify-card">
              <div class="verify-subttl">Submit Section</div>
              <div class="section-divider"></div>
              <div id="dynamicServiceRequirements" class="dynamic-reqs"></div>
              <button class="verify-primary-btn" id="submitRequirementsBtn">Submit Requirements</button>
              <p class="submit-note">Your application will be reviewed by admin.</p>
            </div>
          </section>

          <section class="verify-panel pending" id="panelPending" style="display:none;">
            <div class="verify-card status-card">
              <div class="status-icon pending"><i class="bi bi-hourglass-split"></i></div>
              <h2>Verification in Progress</h2>
              <p id="pendingMessage">Please wait for admin approval.</p>
              <div class="pending-actions">
                <button class="verify-primary-btn" id="simulateApprovalBtn" onclick="simulateApprovalNotification()">Simulate Approval Notification</button>
              </div>
            </div>
          </section>
        </div>

        <div id="verifiedDashboard" style="display:none;">
          <div class="sec-row">
            <div class="sec-ttl">Incoming Requests</div>
            <span class="sec-lnk" onclick="goPage('provider_requests.php')">See all -></span>
          </div>
          <div class="req-list">
            <?php
            $incomingRequests = providerIncomingRequests($conn, $providerId, 2);
            ?>
            <?php if (empty($incomingRequests)): ?>
              <div class="req-card">
                <div class="req-ic">—</div>
                <div class="req-body">
                  <div class="req-type">No incoming requests</div>
                  <div class="req-meta">Check back later for new jobs.</div>
                </div>
              </div>
            <?php else: ?>
              <?php foreach ($incomingRequests as $req):
                $service = htmlspecialchars((string) ($req['service'] ?? 'Service'));
                $customer = htmlspecialchars((string) ($req['customer_name'] ?? 'Homeowner'));
                $address = htmlspecialchars((string) ($req['address'] ?? ''));
                $date = htmlspecialchars((string) ($req['date'] ?? ''));
                $time = htmlspecialchars((string) ($req['time_slot'] ?? ''));
                $price = number_format((float) ($req['fixed_price'] ?? 0), 0);
                $words = preg_split('/\s+/', trim($service));
                $initials = '';
                if (!empty($words[0])) { $initials .= strtoupper(substr($words[0], 0, 1)); }
                if (!empty($words[1])) { $initials .= strtoupper(substr($words[1], 0, 1)); }
                if ($initials === '' && $service !== '') { $initials = strtoupper(substr($service, 0, 2)); }
                $timeLabel = $date !== '' ? $date : 'TBD';
                if ($time !== '') { $timeLabel .= ', ' . $time; }
                $bid = (int) ($req['booking_id'] ?? 0);
              ?>
                <div class="req-card" data-booking-id="<?= $bid ?>" onclick="goPage('provider_requests.php?booking_id=<?= $bid ?>')" style="cursor:pointer;">
                  <div class="req-ic"><?= $initials ?></div>
                  <div class="req-body">
                    <div class="req-type"><?= $service ?></div>
                    <div class="req-name"><?= $customer ?></div>
                    <div class="req-meta">Address: <?= $address ?: '—' ?><br>Time: <?= $timeLabel ?></div>
                  </div>
                  <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px;flex-shrink:0;">
                    <div class="req-price">PHP <?= $price ?></div>
                    <div class="req-btns">
                      <button class="btn-accept" onclick="event.stopPropagation();">Accept</button>
                      <button class="btn-decline" onclick="event.stopPropagation();">Decline</button>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>



          <div class="sec-row">
            <div class="sec-ttl">Earnings This Month</div>
          </div>
          <div class="earn-card clickable" onclick="goPage('provider_earnings.php')" role="button" tabindex="0" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();goPage('provider_earnings.php');}">
            <div class="earn-total">PHP <?= number_format((int) ($dashboardSummary['this_month'] ?? 0)) ?></div>
            <div class="earn-lbl"><?= (int) ($dashboardSummary['jobs_completed'] ?? 0) ?> jobs completed - Goal:
              PHP <?= number_format((int) ($dashboardSummary['monthly_goal'] ?? 0)) ?></div>
            <div class="earn-bar-track">
              <?php
              $goal = max(1, (int) ($dashboardSummary['monthly_goal'] ?? 1));
              $progress = min(100, (int) round(((int) ($dashboardSummary['this_month'] ?? 0) / $goal) * 100));
              ?>
              <div class="earn-bar-fill" style="width:<?= $progress ?>%;"></div>
            </div>
          </div>

          <div class="sec-row">
            <div class="sec-ttl">Recent Reviews</div>
            <span class="sec-lnk" onclick="goPage('provider_reviews.php')">See all -></span>
          </div>
          <div class="rev-list">
            <?php if ($reviewPreview): ?>
              <?php
              $previewName = htmlspecialchars($reviewPreview['customer_name']);
              $previewComment = htmlspecialchars($reviewPreview['comment']);
              $previewRating = (float) ($reviewPreview['rating'] ?? 0);
              $previewStars = str_repeat('*', (int) round($previewRating));
              ?>
              <div class="rev-card clickable" onclick="goPage('provider_reviews.php')" role="button" tabindex="0"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();goPage('provider_reviews.php');}">
                <div class="rev-top">
                  <div class="rev-avatar"><?= strtoupper(substr($previewName, 0, 1)) ?></div>
                  <div>
                    <div class="rev-name"><?= $previewName ?></div>
                    <div class="rev-stars"><?= $previewStars ?></div>
                  </div>
                </div>
                <div class="rev-text"><?= $previewComment ?></div>
              </div>
            <?php else: ?>
              <div class="rev-card">
                <div class="rev-text">No reviews yet.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="bnav" id="providerNav">
        <?php if ($isVerified): ?>
          <div class="ni on" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span class="nl">Requests</span></div>
          <div class="ni" onclick="goPage('provider_earnings.php')"><i class="bi bi-cash-stack"></i><span class="nl">Earnings</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php else: ?>
          <div class="ni on" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span class="nl">Home</span></div>
          <div class="ni" onclick="goPage('provider_profile.php')"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="verify-intro-ol" id="verifiedIntroOl" onclick="if(event.target===this)closeVerifiedIntro()">
      <div class="verify-intro-card">
        <div class="verify-intro-handle"></div>
        <button class="verify-intro-close" type="button" onclick="closeVerifiedIntro()"><i class="bi bi-x-lg"></i></button>
        <div class="verify-intro-icon"><i class="bi bi-patch-check-fill"></i></div>
        <div class="verify-intro-title">You're Now Verified! 🎉</div>
        <div class="verify-intro-msg">Your account has been approved. You now have full access to accept bookings and provide services.</div>
        <div class="verify-intro-help">Your dashboard has been updated. You can now view requests, manage your schedule, and track your earnings.</div>
        <button class="verify-intro-cta" type="button" onclick="goToDashboardFromIntro()">Go to Dashboard</button>
      </div>
    </div>

    <div class="privacy-consent-ol" id="privacyConsentModal" aria-hidden="true">
      <div class="privacy-consent-card" role="dialog" aria-modal="true" aria-labelledby="privacyConsentTitle" aria-describedby="privacyConsentDesc">
        <div class="privacy-consent-head">
          <div class="privacy-consent-icon"><i class="bi bi-shield-check"></i></div>
          <div>
            <h2 id="privacyConsentTitle">Data Privacy Consent Agreement</h2>
            <p id="privacyConsentDesc">Please review and agree before accessing requirements submission.</p>
          </div>
        </div>

        <div class="privacy-consent-body">
          <p>By registering as a Service Provider in HomeEase, you agree to the collection and processing of your personal data under the following terms:</p>

          <h3>1. Consent to Data Collection</h3>
          <p>You voluntarily provide your personal information, including: Full name, contact details, identification documents, location data, and service-related credentials.</p>

          <h3>2. Purpose of Data Processing</h3>
          <p>Your data will be used for: account verification, matching with customers, booking management, payment processing, and performance monitoring.</p>

          <h3>3. Data Sharing</h3>
          <p>You agree that your data may be shared with: customers (limited to necessary booking details), payment processors, and legal authorities when required.</p>

          <h3>4. Data Protection</h3>
          <p>HomeEase commits to protecting your data through: secure storage systems, controlled access, and data encryption.</p>

          <h3>5. Rights of the Provider</h3>
          <p>You have the right to: access your personal data, request corrections, withdraw consent, and request account deletion.</p>

          <h3>6. Data Retention</h3>
          <p>Your data will be stored only as long as necessary for operational and legal purposes.</p>

          <h3>7. Withdrawal of Consent</h3>
          <p>You may withdraw consent anytime by requesting account deletion. However, this may affect your ability to use the platform.</p>

          <h3>8. Agreement</h3>
          <p>By clicking "I Agree" or continuing registration, you confirm that you have read and understood this agreement and consent to the collection and use of your data.</p>
        </div>

        <label class="privacy-consent-check" for="privacyConsentCheckbox">
          <input type="checkbox" id="privacyConsentCheckbox">
          <span>I have read and agree to the Data Privacy Consent Agreement</span>
        </label>

        <div class="privacy-consent-actions">
          <button type="button" class="verify-primary-btn privacy-consent-btn" id="privacyAgreeBtn">I Agree</button>
        </div>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    // Call initTheme if it exists, otherwise delay and retry
    if (typeof initTheme === 'function') {
      initTheme();
    } else {
      setTimeout(() => {
        if (typeof initTheme === 'function') {
          initTheme();
        }
      }, 100);
    }

    function showNotice(message, type = 'error') {
      const old = document.getElementById('homeToast');
      if (old) old.remove();

      const toast = document.createElement('div');
      toast.id = 'homeToast';
      toast.textContent = message;
      toast.style.position = 'fixed';
      toast.style.left = '50%';
      toast.style.bottom = '98px';
      toast.style.transform = 'translateX(-50%)';
      toast.style.width = 'min(92%, 420px)';
      toast.style.zIndex = '9999';
      toast.style.padding = '12px 14px';
      toast.style.borderRadius = '12px';
      toast.style.fontSize = '13px';
      toast.style.fontWeight = '800';
      toast.style.boxShadow = '0 10px 26px rgba(0,0,0,.16)';
      toast.style.border = type === 'success' ? '1px solid #86efac' : '1px solid #fecaca';
      toast.style.background = type === 'success' ? '#dcfce7' : '#fef2f2';
      toast.style.color = type === 'success' ? '#166534' : '#991b1b';
      toast.style.textAlign = 'center';
      document.body.appendChild(toast);

      setTimeout(() => {
        toast.style.transition = 'opacity .25s ease';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 260);
      }, 2200);
    }

    const backendVerificationState = <?= json_encode($verificationState) ?>;
    const backendIsVerified = <?= json_encode($isVerified) ?>;
    const backendAvailability = <?= json_encode($availabilityStatus) ?>;
    const providerId = <?= json_encode($providerId) ?>;
    const localStateKey = 'he_provider_last_state_' + providerId;
    const localOnboardingSeenKey = 'he_provider_verified_seen_' + providerId;
    const localVerifiedNotifKey = 'he_provider_notifs_' + providerId;
    const localPrivacyConsentKey = 'he_provider_privacy_consent_' + providerId;

      // SERVICE_CONFIG and all tools/skills/checkboxes logic removed as not needed anymore

    const selectedServiceInput = document.getElementById('selectedService');
    const serviceProofLabel = document.getElementById('serviceProofLabel');
    const serviceCertLabel = document.getElementById('serviceCertLabel');
    const dynamicServiceRequirements = document.getElementById('dynamicServiceRequirements');
    const serviceSpecificSection = document.getElementById('serviceSpecificSection');
    const serviceSpecificNotes = document.getElementById('serviceSpecificNotes');
    const privacyConsentModal = document.getElementById('privacyConsentModal');
    const privacyConsentCheckbox = document.getElementById('privacyConsentCheckbox');
    const privacyAgreeBtn = document.getElementById('privacyAgreeBtn');

    function hasAcceptedPrivacyConsent() {
      try {
        return localStorage.getItem(localPrivacyConsentKey) === '1';
      } catch (e) {
        return false;
      }
    }

    function markPrivacyConsentAccepted() {
      try {
        localStorage.setItem(localPrivacyConsentKey, '1');
      } catch (e) {
        // Ignore storage errors.
      }
    }

    function openPrivacyConsentModal() {
      if (!privacyConsentModal) return;
      privacyConsentModal.classList.add('on');
      privacyConsentModal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('privacy-consent-open');
      if (privacyConsentCheckbox) privacyConsentCheckbox.checked = false;
      window.requestAnimationFrame(() => {
        const scrollBox = privacyConsentModal.querySelector('.privacy-consent-body');
        if (scrollBox) scrollBox.scrollTop = 0;
      });
    }

    function closePrivacyConsentModal() {
      if (!privacyConsentModal) return;
      privacyConsentModal.classList.remove('on');
      privacyConsentModal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('privacy-consent-open');
    }

    function shouldShowPrivacyConsentGate() {
      const isRequirementsState = backendVerificationState !== 'pending' && backendVerificationState !== 'approval_ready' && backendVerificationState !== 'verified';
      return isRequirementsState && !hasAcceptedPrivacyConsent();
    }

    function initPrivacyConsentGate() {
      if (privacyAgreeBtn) {
        privacyAgreeBtn.addEventListener('click', function () {
          if (!privacyConsentCheckbox || !privacyConsentCheckbox.checked) {
            showNotice('Please confirm the agreement checkbox before continuing.');
            return;
          }
          markPrivacyConsentAccepted();
          closePrivacyConsentModal();
          const verifyFlow = document.getElementById('verifyFlow');
          if (verifyFlow) {
            verifyFlow.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        });
      }

      if (shouldShowPrivacyConsentGate()) {
        setTimeout(openPrivacyConsentModal, 120);
      }
    }

    // Service selection and tools/skills logic removed

    /**
     * Enhanced upload field handler with preview and success indicators
     */
    function setupUploadFields() {
      // Define all upload fields with their corresponding element IDs
      const uploadFields = [
        { inputId: 'uploadIdDoc', isRequired: true },
        { inputId: 'uploadSelfieDoc', isRequired: true },
        { inputId: 'uploadAddressDoc', isRequired: true },
        { inputId: 'uploadCertification', isRequired: false },
        { inputId: 'uploadServiceProof', isRequired: true }
      ];

      uploadFields.forEach(field => {
        const input = document.getElementById(field.inputId);
        if (!input) {
          console.warn('Upload input not found:', field.inputId);
          return;
        }

        input.addEventListener('change', function () {
          const file = this.files && this.files[0];
          
          // Get related elements - construct IDs consistently with HTML (fileNameUploadIdDoc pattern)
          // Extract suffix after 'upload' (e.g., 'uploadIdDoc' -> 'IdDoc')
          const suffix = field.inputId.substring(6);  // Remove 'upload' (6 chars)
          const fileNameId = 'fileNameUpload' + suffix;
          const feedbackId = 'feedbackUpload' + suffix;
          const previewId = 'previewUpload' + suffix;
          
          const fileNameEl = document.getElementById(fileNameId);
          const feedbackEl = document.getElementById(feedbackId);
          const previewEl = document.getElementById(previewId);

          if (!file) {
            // File cleared
            if (fileNameEl) fileNameEl.textContent = field.isRequired ? 'Tap to upload' : 'Tap to upload (optional)';
            if (feedbackEl) feedbackEl.classList.remove('success');
            if (previewEl) {
              previewEl.classList.remove('active');
              previewEl.innerHTML = '';
            }
            return;
          }

          // Update filename
          if (fileNameEl) fileNameEl.textContent = file.name;

          // Show success feedback
          if (feedbackEl) {
            feedbackEl.classList.add('success');
            feedbackEl.textContent = 'Uploaded successfully';
          }

          // Handle preview
          handleFilePreview(file, previewEl, field.inputId);
        });
      });
    }

    /**
     * Generate preview for uploaded file
     */
    function handleFilePreview(file, previewEl, fieldId) {
      if (!previewEl) {
        console.warn('Preview element not found for:', fieldId);
        return;
      }

      // Check if it's an image
      const isImage = file.type.startsWith('image/') || /\.(jpg|jpeg|png|gif|webp|bmp)$/i.test(file.name.toLowerCase());
      
      if (isImage) {
        try {
          const reader = new FileReader();
          reader.onload = function (e) {
            previewEl.innerHTML = `<img src="${e.target.result}" alt="${file.name}" />`;
            previewEl.classList.add('active');
          };
          reader.onerror = function () {
            console.error('Failed to read file:', file.name);
            showFileTypePreview(previewEl, file);
          };
          reader.readAsDataURL(file);
        } catch (err) {
          console.error('Error creating preview:', err);
          showFileTypePreview(previewEl, file);
        }
      } else {
        showFileTypePreview(previewEl, file);
      }
    }

    /**
     * Show file type icon for non-image files
     */
    function showFileTypePreview(previewEl, file) {
      if (!previewEl) return;

      const isPdf = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf');
      let html = '<div style="padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 12px; background: #f3f4f6;justify-content: center;min-height:120px;">';
      
      if (isPdf) {
        html += '<i class="bi bi-file-pdf" style="font-size: 40px; color: #ef4444;"></i>';
        html += '<div style="font-size: 12px; color: #6b7280; text-align: center; font-weight: 700;">PDF File Uploaded</div>';
      } else {
        html += '<i class="bi bi-file-earmark" style="font-size: 40px; color: #6b7280;"></i>';
        html += '<div style="font-size: 12px; color: #6b7280; text-align: center; font-weight: 700;">File Uploaded</div>';
      }
      
      html += '</div>';
      previewEl.innerHTML = html;
      previewEl.classList.add('active');
    }

    // Initialize upload fields when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', setupUploadFields);
    } else {
      setupUploadFields();
    }

    const toggle = document.getElementById('availToggle');
    const lbl = document.getElementById('availLabel');
    let isSavingAvailability = false;
    let lastAvailabilityState = backendAvailability;

    function applyAvailability(availability) {
      const isOnline = String(availability || '').toLowerCase() === 'online';
      lastAvailabilityState = isOnline ? 'online' : 'offline';
      if (toggle) toggle.checked = isOnline;
      if (lbl) lbl.textContent = isOnline ? 'Online' : 'Offline';
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

    if (toggle && lbl) {
      toggle.addEventListener('change', async function () {
        if (!backendIsVerified || isSavingAvailability) {
          applyAvailability('offline');
          return;
        }
        isSavingAvailability = true;
        const desired = this.checked ? 'online' : 'offline';
        const previous = lastAvailabilityState;
        applyAvailability(desired);
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

    function setProviderUiState(state) {
      const notVerified = document.getElementById('panelNotVerified');
      const pending = document.getElementById('panelPending');
      const verifiedDashboard = document.getElementById('verifiedDashboard');
      const availWrap = document.getElementById('availWrap');
      const verifiedPill = document.getElementById('verifiedPill');

      document.body.classList.remove('not-verified', 'pending', 'verified');
      document.body.classList.add(state);

      notVerified.style.display = state === 'not-verified' ? 'block' : 'none';
      pending.style.display = state === 'pending' ? 'block' : 'none';
      verifiedDashboard.style.display = state === 'verified' ? 'block' : 'none';
      availWrap.style.display = state === 'verified' ? 'flex' : 'none';
      verifiedPill.style.display = state === 'verified' ? 'inline-flex' : 'none';

      if (toggle && lbl) {
        if (state === 'verified') {
          toggle.disabled = false;
          applyAvailability(backendAvailability);
        } else {
          toggle.disabled = true;
          applyAvailability('offline');
        }
      }
    }

    function getTimestampLabel(dateString) {
      try {
        const d = new Date(dateString);
        return d.toLocaleString([], { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
      } catch (e) {
        return 'Just now';
      }
    }

    function getStoredProviderNotifs() {
      try {
        const raw = localStorage.getItem(localVerifiedNotifKey);
        const parsed = JSON.parse(raw || '[]');
        return Array.isArray(parsed) ? parsed : [];
      } catch (e) {
        return [];
      }
    }

    function saveStoredProviderNotifs(list) {
      try {
        localStorage.setItem(localVerifiedNotifKey, JSON.stringify(Array.isArray(list) ? list : []));
      } catch (e) {
        // Ignore storage errors.
      }
    }

    function ensureAccountVerifiedNotification() {
      const notifs = getStoredProviderNotifs();
      const existing = notifs.find(n => n && n.id === 'account_verified');
      if (existing) return;
      const createdAt = new Date().toISOString();
      notifs.unshift({
        id: 'account_verified',
        type: 'account_verified',
        title: 'Account Verified',
        msg: 'You can now start accepting bookings.',
        createdAt,
        time: getTimestampLabel(createdAt),
        read: false,
        icon: 'verified'
      });
      saveStoredProviderNotifs(notifs);
    }

    function openVerifiedIntro() {
      const intro = document.getElementById('verifiedIntroOl');
      if (intro) intro.classList.add('on');
    }

    function closeVerifiedIntro() {
      const intro = document.getElementById('verifiedIntroOl');
      if (intro) intro.classList.remove('on');
      try {
        localStorage.setItem(localOnboardingSeenKey, '1');
      } catch (e) {
        // Ignore storage errors.
      }
    }

    function goToDashboardFromIntro() {
      closeVerifiedIntro();
      const scrollHost = document.getElementById('phScroll');
      if (scrollHost) scrollHost.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function statusClass(statusRaw) {
      const s = String(statusRaw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'completed';
      if (s === 'progress' || s === 'confirmed' || s === 'active') return 'confirmed';
      return 'pending';
    }

    function statusLabel(statusRaw) {
      const s = String(statusRaw || '').toLowerCase();
      if (s === 'done' || s === 'completed') return 'Completed';
      if (s === 'progress' || s === 'confirmed' || s === 'active') return 'Confirmed';
      return 'Pending';
    }

    function renderTodaySchedule(bookings) {
      const list = document.getElementById('schedList');
      if (!list) return;

      const today = new Date().toISOString().slice(0, 10);
      const todayItems = bookings
        .filter(b => b.date === today)
        .sort((a, b) => String(a.time || '').localeCompare(String(b.time || '')));

      if (!todayItems.length) {
        list.innerHTML = `
          <div class="sched-card">
            <div class="sched-dot" style="background:linear-gradient(135deg,#94a3b8,#64748b);box-shadow:0 2px 6px rgba(100,116,139,.3);"></div>
            <div class="sched-card-main">
              <div class="sched-time" style="color:#64748b;">No bookings today</div>
              <div class="sched-title">You're all caught up for now</div>
              <div class="sched-sub">Tap Full calendar to plan upcoming jobs</div>
            </div>
          </div>`;
        return;
      }

      list.innerHTML = todayItems.slice(0, 3).map(item => {
        const cls = statusClass(item.status_raw || item.status);
        const lblText = statusLabel(item.status_raw || item.status);
        return `
          <div class="sched-card" onclick="goPage('provider_schedule.php?date=${encodeURIComponent(item.date)}')">
            <div class="sched-dot"></div>
            <div class="sched-card-main">
              <div class="sched-time">${item.time || 'All day'}</div>
              <div class="sched-top-row">
                <div class="sched-title">${item.service} with ${item.client_name || 'Client'}</div>
                <span class="sched-status ${cls}">${lblText}</span>
              </div>
              <div class="sched-sub">${item.address || 'Address not specified'}</div>
            </div>
          </div>`;
      }).join('');
    }

    function loadTodaySchedule() {
      if (!backendIsVerified) return;
      fetch('../api/provider_schedule_api.php')
        .then(r => r.json())
        .then(data => {
          if (!data.success) {
            renderTodaySchedule([]);
            return;
          }
          renderTodaySchedule(Array.isArray(data.bookings) ? data.bookings : []);
        })
        .catch(() => renderTodaySchedule([]));
    }

    /**
     * Validate form and show validation messages
     * Returns true if valid, false otherwise
     */
    function validateVerificationForm() {
      let isValid = true;
      const errors = [];

      // Check required uploads
      const idDoc = document.getElementById('uploadIdDoc').files[0];
      const selfieDoc = document.getElementById('uploadSelfieDoc').files[0];
      const addressDoc = document.getElementById('uploadAddressDoc').files[0];
      const serviceProof = document.getElementById('uploadServiceProof').files[0];

      if (!idDoc) {
        errors.push('Valid Government ID is required');
        isValid = false;
      }
      if (!selfieDoc) {
        errors.push('Selfie Verification is required');
        isValid = false;
      }
      if (!addressDoc) {
        errors.push('Proof of Address is required');
        isValid = false;
      }
      if (!serviceProof) {
        errors.push('Tools & Kits image is required');
        isValid = false;
      }

      // Check experience textarea
      const experienceField = document.getElementById('experienceDescription');
      const experienceValidation = document.getElementById('experienceValidation');
      const experienceValue = (experienceField.value || '').trim();

      if (!experienceValue) {
        errors.push('Working Experience description is required');
        if (experienceValidation) {
          experienceValidation.style.display = 'block';
          experienceValidation.textContent = 'Experience description is required';
        }
        isValid = false;
      } else {
        if (experienceValidation) {
          experienceValidation.style.display = 'none';
        }
      }

      // Display all errors if any
      if (!isValid) {
        const errorMessage = errors.join('\n• ');
        showNotice('• ' + errorMessage);
      }

      return isValid;
    }

    // Add real-time validation to experience textarea
    const experienceField = document.getElementById('experienceDescription');
    if (experienceField) {
      experienceField.addEventListener('input', function () {
        const experienceValidation = document.getElementById('experienceValidation');
        if (!experienceValidation) return;

        const value = (this.value || '').trim();
        if (value.length > 0) {
          experienceValidation.style.display = 'none';
        }
      });
    }

    async function submitRequirements() {
      // Validate form first
      if (!validateVerificationForm()) {
        return;
      }

      const selectedService = selectedServiceInput.value.trim();
      if (!selectedService) {
        showNotice('Please select a service first.');
        return;
      }

      const idDoc = document.getElementById('uploadIdDoc').files[0];
      const selfieDoc = document.getElementById('uploadSelfieDoc').files[0];
      const addressDoc = document.getElementById('uploadAddressDoc').files[0];
      const certDoc = document.getElementById('uploadCertification').files[0] || null;
      const serviceProof = document.getElementById('uploadServiceProof').files[0];

      const fd = new FormData();
      fd.append('action', 'upload_documents');
      fd.append('selected_service', selectedService);
      fd.append('profile_name', document.getElementById('profileName').value || '');
      fd.append('profile_phone', document.getElementById('profilePhone').value || '');
      fd.append('profile_address', document.getElementById('profileAddress').value || '');
      fd.append('experience_description', document.getElementById('experienceDescription').value || '');
      
      // Map old field names to new API field names
      fd.append('valid_id', idDoc);
      fd.append('barangay_clearance', certDoc);  // Barangay clearance was in certificate field
      fd.append('selfie', selfieDoc);
      fd.append('proof_of_address', addressDoc);
      if (serviceProof) fd.append('tools_kits', serviceProof);  // Tools & kits was proof_of_experience

      try {
        const res = await fetch('../api/provider_documents_api.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          showNotice(data.message || 'Failed to submit requirements.');
          return;
        }
        setProviderUiState('pending');
        showNotice('Requirements submitted. Please wait for admin approval.', 'success');
      } catch (e) {
        console.error('Error:', e);
        showNotice('Could not submit requirements right now.');
      }
    }

    const submitBtn = document.getElementById('submitRequirementsBtn');
    if (submitBtn) {
      submitBtn.addEventListener('click', submitRequirements);
    }

    function updatePendingStateDetails() {
      const pendingMessage = document.getElementById('pendingMessage');
      const simulateApprovalBtn = document.getElementById('simulateApprovalBtn');
      if (!pendingMessage || !simulateApprovalBtn) return;

      if (backendVerificationState === 'approval_ready') {
        pendingMessage.textContent = 'Admin approved your application. Your dashboard has been unlocked.';
        simulateApprovalBtn.style.display = 'none';
      } else {
        pendingMessage.textContent = 'Please wait for admin approval.';
        simulateApprovalBtn.style.display = 'block';
      }
    }

    async function simulateApprovalNotification() {
      try {
        const fd = new FormData();
        fd.append('action', 'simulate_approval_ready');
        const res = await fetch('../api/provider_verification.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          showNotice(data.message || 'Could not simulate approval.');
          return;
        }
        goPage('provider_home.php');
      } catch (error) {
        showNotice('Could not simulate approval right now.');
      }
    }

    if (backendVerificationState === 'verified') {
      setProviderUiState('verified');
      syncAvailabilityFromServer();
    } else if (backendVerificationState === 'pending' || backendVerificationState === 'approval_ready') {
      setProviderUiState('pending');
      updatePendingStateDetails();
    } else {
      setProviderUiState('not-verified');
    }

    let previousState = '';
    let onboardingSeen = false;
    try {
      previousState = localStorage.getItem(localStateKey) || '';
      onboardingSeen = localStorage.getItem(localOnboardingSeenKey) === '1';
      localStorage.setItem(localStateKey, backendVerificationState);
    } catch (e) {
      previousState = '';
      onboardingSeen = false;
    }

    const justBecameVerified = backendVerificationState === 'verified' && previousState !== 'verified';
    if (backendVerificationState === 'verified' && (justBecameVerified || !onboardingSeen)) {
      ensureAccountVerifiedNotification();
      openVerifiedIntro();
    }

    // Today's Schedule removed from home — no periodic schedule fetch required.

    initPrivacyConsentGate();

    // Dynamic greeting update
    function updateGreeting() {
      const hour = new Date().getHours();
      let greeting = 'Good Morning';
      if (hour >= 12 && hour < 18) {
        greeting = 'Good Afternoon';
      } else if (hour >= 18) {
        greeting = 'Good Evening';
      }
      const greetingElement = document.querySelector('.ph-greet');
      if (greetingElement) {
        greetingElement.textContent = greeting;
      }
    }

    updateGreeting();
    setInterval(updateGreeting, 60000); // Update every 60 seconds
  </script>
</body>

</html>

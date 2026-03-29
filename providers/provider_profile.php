<?php
session_start();
if (empty($_SESSION['provider_id'])) {
  header('Location: provider_index.php');
  exit;
}
$name = htmlspecialchars($_SESSION['provider_name'] ?? 'Service Provider');
$email = htmlspecialchars($_SESSION['provider_email'] ?? '');
$phone = htmlspecialchars($_SESSION['provider_phone'] ?? 'Not set');
$address = htmlspecialchars($_SESSION['provider_address'] ?? 'Not set');
$specialty = htmlspecialchars($_SESSION['provider_specialty'] ?? 'General Services');
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
          <div class="p-hdr-settings" onclick="openSettingsScreen()"><i class="bi bi-gear-fill"></i></div>
          <div
            style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,rgba(255,255,255,.25),rgba(255,255,255,.1));border:3px solid rgba(255,255,255,.5);display:flex;align-items:center;justify-content:center;font-family:'Poppins',sans-serif;font-size:32px;font-weight:800;color:#fff;margin:0 auto 12px;box-shadow:0 8px 24px rgba(0,0,0,.12);">
            <?= strtoupper(substr($name, 0, 1)) ?>
          </div>
          <div class="p-name"><?= $name ?></div>
          <div class="p-email"><?= $email ?></div>
          <div class="p-badges">
            <div class="p-badge"><i class="bi bi-patch-check-fill" style="font-size:11px;"></i> Verified Provider</div>
            <div class="p-badge"><i class="bi bi-tools" style="font-size:11px;"></i> <?= $specialty ?></div>
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
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path
                    d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 11.18 19.79 19.79 0 01.12 2.57 2 2 0 012.11.39h3A2 2 0 017.1 2.07c.36 1.07.83 2.1 1.38 3.07a2 2 0 01-.46 2.31L6.29 9A16 16 0 0015 17.71l1.55-1.73a2 2 0 012.31-.46c.97.55 2 1.02 3.07 1.38a2 2 0 011.07 1.02z"
                    stroke="#F5A623" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Phone</div>
                <div class="p-row-sub"><?= $phone ?></div>
              </div>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <path d="M12 2a8 8 0 00-8 8c0 5.5 8 13 8 13s8-7.5 8-13a8 8 0 00-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z"
                    stroke="#F5A623" stroke-width="2" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Service Area</div>
                <div class="p-row-sub"><?= $address ?></div>
              </div>
            </div>
            <div class="p-row">
              <div class="p-row-ic"><svg viewBox="0 0 24 24" fill="none">
                  <circle cx="12" cy="12" r="10" stroke="#F5A623" stroke-width="2" />
                  <path d="M12 6v6l4 2" stroke="#F5A623" stroke-width="2" stroke-linecap="round" />
                </svg></div>
              <div class="p-row-info">
                <div class="p-row-lbl">Working Hours</div>
                <div class="p-row-sub">Mon–Sat, 8:00 AM – 6:00 PM</div>
              </div>
            </div>
          </div>

          <!-- Services -->
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
        </div>
      </div>

      <div class="bnav">
        <div class="ni" onclick="goPage('provider_home.php')"><i class="bi bi-house-fill"></i><span
            class="nl">Home</span></div>
        <div class="ni" onclick="goPage('provider_requests.php')"><i class="bi bi-clipboard-check-fill"></i><span
            class="nl">Requests</span></div>
        <div class="ni" onclick="goPage('provider_schedule.php')"><i class="bi bi-calendar3"></i><span
            class="nl">Calendar</span></div>
        <div class="ni" onclick="goPage('provider_notifications.php')"><i class="bi bi-bell-fill"></i><span
            class="nl">Notifications</span></div>
        <div class="ni on"><i class="bi bi-person-fill"></i><span class="nl">Profile</span></div>
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
          <div class="st-row">
            <div class="st-ic orange"><i class="bi bi-person-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Edit Profile</div>
              <div class="st-row-sub">Name, phone, service area</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row">
            <div class="st-ic blue"><i class="bi bi-shield-lock-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Change Password</div>
              <div class="st-row-sub">Update your password</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row">
            <div class="st-ic green"><i class="bi bi-geo-alt-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Service Area</div>
              <div class="st-row-sub"><?= $address ?></div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
        </div>
        
        <div class="st-sec">
          <div class="st-sec-ttl">General Verification Requirements</div>
          
          <div class="st-row" onclick="document.getElementById('id_picture').click()">
            <div class="st-ic blue"><i class="bi bi-person-vcard-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Valid Government ID</div>
              <div class="st-row-sub" id="id_status">Not Uploaded</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>

          <div class="st-row" onclick="document.getElementById('selfie_verification').click()">
            <div class="st-ic purple"><i class="bi bi-person-bounding-box"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Selfie Verification</div>
              <div class="st-row-sub" id="selfie_status">Not Uploaded (Clear face photo)</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>

          <div class="st-row" onclick="document.getElementById('proof_of_address').click()">
            <div class="st-ic red"><i class="bi bi-house-check-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Proof of Address</div>
              <div class="st-row-sub" id="address_status">Not Uploaded (e.g., Utility Bill)</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
        </div>

        <div class="st-sec">
          <div class="st-sec-ttl"><?= $specialty ?> Specific Requirements</div>
          
          <?php
            $certLbl = "Certification";
            $expLbl = "Proof of Experience";
            if ($specialty === 'Cleaner') {
                $certLbl = "Barangay Clearance";
                $expLbl = "Photos of past cleaning work";
            } elseif ($specialty === 'Helper') {
                $certLbl = "NBI / Police Clearance";
                $expLbl = "2 Character References (Document)";
            } elseif ($specialty === 'Laundry Worker') {
                $certLbl = "Plantsa / Dryer Tools Declaration";
                $expLbl = "Photos of laundry work";
            } elseif ($specialty === 'Plumber') {
                $certLbl = "TESDA / Plumbing Certification";
                $expLbl = "Tools Declaration";
            } elseif ($specialty === 'Carpenter') {
                $certLbl = "Tools Declaration";
                $expLbl = "Photos of previous woodwork";
            } elseif ($specialty === 'Appliance Technician') {
                $certLbl = "Technical Certification";
                $expLbl = "Tools Declaration";
            }
          ?>

          <div class="st-row" onclick="document.getElementById('certificates').click()">
            <div class="st-ic green"><i class="bi bi-award-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl"><?= $certLbl ?></div>
              <div class="st-row-sub" id="cert_status">Not Uploaded</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          
          <div class="st-row" onclick="document.getElementById('proof_of_experience').click()">
            <div class="st-ic orange"><i class="bi bi-briefcase-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl"><?= $expLbl ?></div>
              <div class="st-row-sub" id="proof_status">Not Uploaded</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          
          <div style="padding: 10px 18px 20px;">
              <button onclick="uploadVerificationDocs()" style="width:100%;background:linear-gradient(135deg, #E8820C, #F5A623);color:#fff;border:none;padding:12px;border-radius:12px;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 4px 12px rgba(232,130,12,0.3);">Submit Documents</button>
          </div>
          
          <form id="verifyForm" style="display:none;">
              <input type="file" id="id_picture" name="id_picture" accept="image/*,application/pdf" onchange="document.getElementById('id_status').innerText='File Selected.';" />
              <input type="file" id="selfie_verification" name="selfie_verification" accept="image/*" onchange="document.getElementById('selfie_status').innerText='File Selected.';" />
              <input type="file" id="proof_of_address" name="proof_of_address" accept="image/*,application/pdf" onchange="document.getElementById('address_status').innerText='File Selected.';" />
              <input type="file" id="certificates" name="certificates" accept="image/*,application/pdf" onchange="document.getElementById('cert_status').innerText='File Selected.';" />
              <input type="file" id="proof_of_experience" name="proof_of_experience" accept="image/*,application/pdf" onchange="document.getElementById('proof_status').innerText='File Selected.';" />
          </form>
        </div>
        

        <div class="st-sec">
          <div class="st-sec-ttl">Appearance</div>
          <div class="st-row">
            <div class="st-ic gray"><i class="bi bi-moon-stars-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Dark Mode</div>
              <div class="st-row-sub">Switch to dark theme</div>
            </div>
            <div class="st-toggle" id="stDarkToggle" onclick="toggleDarkMode()"></div>
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
          <div class="st-row">
            <div class="st-ic orange"><i class="bi bi-question-circle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">Help Center</div>
              <div class="st-row-sub">FAQs & guides</div>
            </div><i class="bi bi-chevron-right st-row-arrow"></i>
          </div>
          <div class="st-row">
            <div class="st-ic gray"><i class="bi bi-info-circle-fill"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl">App Version</div>
              <div class="st-row-sub">HomeEase for Providers</div>
            </div><span style="font-size:12px;color:var(--tm);font-weight:700;margin-right:6px;">v3.2.0</span>
          </div>
        </div>
        <div class="st-sec">
          <div class="st-sec-ttl">Session</div>
          <div class="st-row" onclick="location.href='../logout.php'">
            <div class="st-ic red"><i class="bi bi-box-arrow-right"></i></div>
            <div class="st-row-info">
              <div class="st-row-lbl" style="color:#ef4444;">Log Out</div>
              <div class="st-row-sub">Sign out of your account</div>
            </div>
          </div>
        </div>
        <div class="st-version">HomeEase v3.2.0 · Service Provider Edition</div>
      </div>
    </div>
  </div>

  <script src="../assets/js/app.js"></script>
  <script>
    initTheme();
    function openSettingsScreen() { document.getElementById('settingsScreen').classList.add('on'); syncDark(); }
    function closeSettingsScreen() { document.getElementById('settingsScreen').classList.remove('on'); }
    function syncDark() {
      const d = document.body.classList.contains('dark');
      const stDarkToggle = document.getElementById('stDarkToggle');
      if (stDarkToggle) stDarkToggle.classList.toggle('on', d);
    }
    function toggleDarkMode() { toggleDark(); syncDark(); }
    syncDark();

    async function uploadVerificationDocs() {
      const form = document.getElementById('verifyForm');
      const formData = new FormData(form);
      formData.append('action', 'upload_documents');
      
      try {
          const res = await fetch('../api/provider_verification.php', {
              method: 'POST',
              body: formData
          });
          const data = await res.json();
          if (data.success) {
              alert('Documents uploaded successfully! Waiting for admin approval.');
              loadVerificationStatus();
          } else {
              alert('Upload failed: ' + data.message);
          }
      } catch (err) {
          alert('Error uploading documents.');
      }
    }

    async function loadVerificationStatus() {
        try {
            const res = await fetch('../api/provider_verification.php?action=status');
            const data = await res.json();
            if(data.success && data.verification) {
                const v = data.verification;
                if(v.id_picture) document.getElementById('id_status').innerText = 'Uploaded';
                if(v.selfie_verification) document.getElementById('selfie_status').innerText = 'Uploaded';
                if(v.proof_of_address) document.getElementById('address_status').innerText = 'Uploaded';
                if(v.certificates) document.getElementById('cert_status').innerText = 'Uploaded';
                if(v.proof_of_experience) document.getElementById('proof_status').innerText = 'Uploaded';
            }
        } catch(e) {}
    }

    loadVerificationStatus();
  </script>
</body>

</html>
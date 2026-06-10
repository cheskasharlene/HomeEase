<?php
session_start();

// If user is already logged in, redirect them
if (!empty($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}
if (!empty($_SESSION['provider_id'])) {
    header('Location: providers/provider_home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Forgot Password</title>
  <meta name="description" content="Reset your HomeEase account password." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    /* ── Page shell ── */
    .fp-shell {
      width: 100%;
      max-width: 420px;
      min-height: 100dvh;
      background: var(--bg-shell);
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
    }

    @media (min-width: 600px) {
      body { padding: 20px; }
      .fp-shell {
        min-height: unset;
        height: min(760px, 100dvh);
        border-radius: 44px;
        box-shadow:
          0 40px 80px var(--shadow-col),
          0 8px 32px rgba(232,130,12,0.08),
          0 0 0 1px rgba(245,166,35,0.1);
      }
    }

    /* ── Header banner ── */
    .fp-hdr {
      padding: 48px 24px 28px;
      background:
        radial-gradient(ellipse at 80% 0%, rgba(255,200,80,0.5) 0%, transparent 50%),
        radial-gradient(ellipse at 5% 90%, rgba(200,90,0,0.12) 0%, transparent 45%),
        linear-gradient(160deg,
          rgba(216,100,8,0.88)   0%,
          rgba(232,130,12,0.70) 35%,
          rgba(245,166,35,0.45) 65%,
          rgba(255,183,107,0.15) 85%,
          transparent 100%);
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
    }
    .fp-hdr::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: radial-gradient(circle at 1px 1px, rgba(255,255,255,0.06) 1px, transparent 0);
      background-size: 22px 22px;
      pointer-events: none;
    }
    .fp-back {
      width: 36px; height: 36px;
      border-radius: 50%;
      border: none;
      background: rgba(255,255,255,0.22);
      backdrop-filter: blur(8px);
      color: #fff;
      font-size: 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      position: relative;
      z-index: 1;
      transition: background 0.2s, transform 0.15s;
    }
    .fp-back:hover { background: rgba(255,255,255,0.35); transform: scale(1.08); }

    .fp-hdr-ic {
      width: 56px; height: 56px;
      border-radius: 16px;
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(8px);
      border: 1.5px solid rgba(255,255,255,0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 24px;
      margin-bottom: 14px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.1);
      position: relative;
      z-index: 1;
    }
    .fp-hdr-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #1A1A2E;
      position: relative;
      z-index: 1;
      margin-bottom: 4px;
    }
    .fp-hdr-sub {
      font-size: 13px;
      color: rgba(26,26,46,0.65);
      font-weight: 600;
      position: relative;
      z-index: 1;
      line-height: 1.45;
    }

    /* ── Body ── */
    .fp-body {
      flex: 1;
      overflow-y: auto;
      padding: 28px 24px 36px;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    .fp-body::-webkit-scrollbar { display: none; }

    /* ── Steps ── */
    .fp-step { display: none; }
    .fp-step.active { display: block; }

    /* ── Step indicator ── */
    .fp-steps-row {
      display: flex;
      align-items: center;
      gap: 6px;
      margin-bottom: 24px;
    }
    .fp-step-dot {
      width: 8px; height: 8px;
      border-radius: 50%;
      background: var(--border-col);
      transition: all 0.3s;
    }
    .fp-step-dot.active {
      background: var(--g-start);
      width: 24px;
      border-radius: 4px;
    }
    .fp-step-dot.done { background: var(--success); }

    /* ── Alert ── */
    .fp-alert {
      display: none;
      align-items: center;
      gap: 8px;
      padding: 11px 14px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 16px;
      border: 1.5px solid transparent;
      animation: alertIn 0.22s ease;
    }
    @keyframes alertIn { from { opacity:0; transform:translateY(-6px); } to { opacity:1; transform:translateY(0); } }
    .fp-alert.show { display: flex; }
    .fp-alert.error   { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .fp-alert.success { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }

    /* ── Form fields ── */
    .fp-fg { margin-bottom: 16px; }
    .fp-lbl {
      font-size: 12px;
      font-weight: 700;
      color: var(--txt-primary);
      margin-bottom: 6px;
      display: block;
    }
    .fp-iw { position: relative; }
    .fp-fi {
      width: 100%;
      padding: 13px 42px 13px 15px;
      border: 2px solid var(--border-col);
      border-radius: 13px;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      outline: none;
      transition: border-color 0.2s, box-shadow 0.2s;
      background: var(--bg-input);
      color: var(--txt-primary);
    }
    .fp-fi:focus {
      border-color: var(--g-start);
      box-shadow: 0 0 0 4px rgba(232,130,12,0.1);
      background: var(--bg-card);
    }
    .fp-fi-icon {
      position: absolute;
      right: 14px; top: 50%;
      transform: translateY(-50%);
      color: var(--tm);
      font-size: 15px;
      pointer-events: none;
    }
    .fp-eye {
      position: absolute;
      right: 14px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: var(--tm);
      font-size: 15px; cursor: pointer; padding: 4px;
      display: flex; align-items: center;
    }

    /* ── Password strength (reuse shared pattern) ── */
    .pwd-strength-wrap { margin-top: 6px; }
    .pwd-bars { display: flex; gap: 4px; margin-bottom: 6px; }
    .pwd-bar {
      flex: 1; height: 4px; border-radius: 4px;
      background: var(--border-col);
      transition: background 0.25s;
    }
    .pwd-reqs { display: flex; flex-direction: column; gap: 4px; }
    .pwd-req {
      display: flex; align-items: center; gap: 5px;
      font-size: 11px; font-weight: 600;
      color: var(--tm);
      transition: color 0.2s;
    }
    .pwd-req.met { color: #059669; }
    .pwd-req i { font-size: 6px; }

    /* ── Button ── */
    .fp-btn {
      width: 100%;
      padding: 15px;
      background: linear-gradient(135deg, #E8820C 0%, #F5A623 55%, #FFB347 100%);
      color: #fff;
      border: none;
      border-radius: 50px;
      font-family: 'Poppins', sans-serif;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.22s;
      box-shadow: 0 8px 24px rgba(232,130,12,0.3);
      margin-top: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      position: relative;
      overflow: hidden;
    }
    .fp-btn::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(255,255,255,0.12) 0%, transparent 60%);
      pointer-events: none;
    }
    .fp-btn:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(232,130,12,0.4); }
    .fp-btn:active { transform: translateY(0); }
    .fp-btn:disabled { opacity: 0.65; pointer-events: none; }
    .fp-btn .btn-spinner {
      width: 16px; height: 16px;
      border: 2.5px solid rgba(255,255,255,.35);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none;
    }
    .fp-btn.loading .btn-spinner { display: block; }
    .fp-btn.loading .btn-label  { display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Info note ── */
    .fp-note {
      font-size: 12px;
      color: var(--tm);
      text-align: center;
      margin-top: 16px;
      line-height: 1.5;
    }
    .fp-note a {
      color: var(--g-start);
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
    }
    .fp-note a:hover { text-decoration: underline; }

    /* ── Success screen ── */
    .fp-success-wrap {
      text-align: center;
      padding: 20px 0;
      animation: successPop 0.4s cubic-bezier(.34,1.4,.64,1) both;
    }
    @keyframes successPop { from { opacity:0; transform:scale(0.85); } to { opacity:1; transform:scale(1); } }

    .fp-success-ic {
      width: 90px; height: 90px;
      border-radius: 50%;
      background: linear-gradient(135deg, #dcfce7, #bbf7d0);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      color: #059669;
      margin: 0 auto 20px;
      box-shadow: 0 12px 36px rgba(16,185,129,0.2);
    }
    .fp-success-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: var(--txt-primary);
      margin-bottom: 8px;
    }
    .fp-success-sub {
      font-size: 13px;
      color: var(--tm);
      line-height: 1.55;
      margin-bottom: 28px;
    }

    /* ── Account type toggle ── */
    .fp-acct-row { display: flex; gap: 10px; margin-bottom: 18px; }
    .fp-acct-btn {
      flex: 1; padding: 11px 8px; border-radius: 13px;
      border: 2px solid var(--border-col);
      background: var(--bg-input); cursor: pointer;
      display: flex; flex-direction: column; align-items: center; gap: 5px;
      font-family: 'Nunito', sans-serif; font-size: 12px; font-weight: 700;
      color: var(--tm); transition: all 0.22s;
    }
    .fp-acct-btn i { font-size: 20px; }
    .fp-acct-btn.active {
      border-color: var(--g-start);
      background: var(--teal-bg);
      color: var(--g-start);
      box-shadow: 0 0 0 4px rgba(232,130,12,0.1);
    }
  </style>
</head>

<body>
  <div class="fp-shell">

    <!-- Header -->
    <div class="fp-hdr">
      <button class="fp-back" onclick="window.location.href='index.php'" aria-label="Back to login">
        <i class="bi bi-arrow-left"></i>
      </button>
      <div class="fp-hdr-ic"><i class="bi bi-shield-lock-fill"></i></div>
      <div class="fp-hdr-ttl">Forgot Password?</div>
      <div class="fp-hdr-sub">Don't worry — we'll help you get back in.</div>
    </div>

    <!-- Body -->
    <div class="fp-body">

      <!-- Step dots -->
      <div class="fp-steps-row">
        <div class="fp-step-dot active" id="dot1"></div>
        <div class="fp-step-dot" id="dot2"></div>
        <div class="fp-step-dot" id="dot3"></div>
      </div>

      <!-- Shared alert -->
      <div class="fp-alert" id="fpAlert">
        <i class="bi bi-exclamation-circle-fill"></i>
        <span id="fpAlertTxt"></span>
      </div>

      <!-- ─── Step 1: Enter email & account type ─── -->
      <div class="fp-step active" id="step1">
        <div style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:var(--txt-primary);margin-bottom:4px;">
          Verify your account
        </div>
        <p style="font-size:12px;color:var(--tm);margin-bottom:20px;line-height:1.5;">
          Enter your registered email address and select your account type.
        </p>

        <div style="margin-bottom:12px;">
          <span style="font-size:11px;font-weight:800;color:var(--tm);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;display:block;">Account type</span>
          <div class="fp-acct-row">
            <div class="fp-acct-btn active" id="acctUser" onclick="setAcctType('user')">
              <i class="bi bi-person-fill"></i>
              Homeowner
            </div>
            <div class="fp-acct-btn" id="acctProvider" onclick="setAcctType('provider')">
              <i class="bi bi-tools"></i>
              Provider
            </div>
          </div>
        </div>

        <div class="fp-fg">
          <label class="fp-lbl">Email Address</label>
          <div class="fp-iw">
            <input type="email" class="fp-fi" id="fpEmail" placeholder="you@example.com"
              autocomplete="email" oninput="clearAlert()" />
            <i class="bi bi-envelope-fill fp-fi-icon"></i>
          </div>
        </div>

        <button class="fp-btn" id="btnStep1" onclick="doStep1()">
          <span class="btn-spinner"></span>
          <span class="btn-label"><i class="bi bi-arrow-right-circle-fill" style="margin-right:4px;"></i>Continue</span>
        </button>

        <p class="fp-note">
          Remembered your password? <a onclick="window.location.href='index.php'">Sign in instead</a>
        </p>
      </div>

      <!-- ─── Step 2: Set new password ─── -->
      <div class="fp-step" id="step2">
        <div style="font-family:'Poppins',sans-serif;font-size:16px;font-weight:700;color:var(--txt-primary);margin-bottom:4px;">
          Set a new password
        </div>
        <p style="font-size:12px;color:var(--tm);margin-bottom:20px;line-height:1.5;">
          Choose a strong password for <strong id="emailConfirmDisplay"></strong>.
        </p>

        <div class="fp-fg">
          <label class="fp-lbl">New Password</label>
          <div class="fp-iw">
            <input type="password" class="fp-fi" id="fpNewPwd" placeholder="Min. 8 characters"
              autocomplete="new-password" oninput="updateFpStrength(this.value)" />
            <button class="fp-eye" type="button" onclick="toggleFpPwd('fpNewPwd', this)">
              <i class="bi bi-eye-fill"></i>
            </button>
          </div>

          <!-- Strength meter -->
          <div class="pwd-strength-wrap" id="fpPwdStrength" style="display:none;">
            <div class="pwd-bars">
              <div class="pwd-bar" id="fpbar1"></div>
              <div class="pwd-bar" id="fpbar2"></div>
              <div class="pwd-bar" id="fpbar3"></div>
              <div class="pwd-bar" id="fpbar4"></div>
            </div>
            <div class="pwd-reqs">
              <div class="pwd-req" id="fpreq-len"><i class="bi bi-circle-fill"></i> At least 8 characters</div>
              <div class="pwd-req" id="fpreq-upper"><i class="bi bi-circle-fill"></i> At least one uppercase letter (A–Z)</div>
              <div class="pwd-req" id="fpreq-lower"><i class="bi bi-circle-fill"></i> At least one lowercase letter (a–z)</div>
              <div class="pwd-req" id="fpreq-num"><i class="bi bi-circle-fill"></i> At least one number (0–9)</div>
            </div>
          </div>
        </div>

        <div class="fp-fg">
          <label class="fp-lbl">Confirm New Password</label>
          <div class="fp-iw">
            <input type="password" class="fp-fi" id="fpConfirmPwd" placeholder="Re-enter new password"
              autocomplete="new-password" oninput="clearAlert()" />
            <button class="fp-eye" type="button" onclick="toggleFpPwd('fpConfirmPwd', this)">
              <i class="bi bi-eye-fill"></i>
            </button>
          </div>
        </div>

        <button class="fp-btn" id="btnStep2" onclick="doStep2()">
          <span class="btn-spinner"></span>
          <span class="btn-label"><i class="bi bi-shield-check-fill" style="margin-right:4px;"></i>Reset Password</span>
        </button>

        <p class="fp-note">
          <a onclick="goBack()"><i class="bi bi-arrow-left"></i> Go back</a>
        </p>
      </div>

      <!-- ─── Step 3: Success ─── -->
      <div class="fp-step" id="step3">
        <div class="fp-success-wrap">
          <div class="fp-success-ic"><i class="bi bi-check-lg"></i></div>
          <div class="fp-success-ttl">Password Reset!</div>
          <p class="fp-success-sub">
            Your password has been updated successfully.<br>
            You can now sign in with your new password.
          </p>
          <button class="fp-btn" onclick="window.location.href='index.php'" style="max-width:240px;margin:0 auto;">
            <span class="btn-label"><i class="bi bi-box-arrow-in-right" style="margin-right:4px;"></i>Sign In Now</span>
          </button>
        </div>
      </div>

    </div>
  </div>

  <script>
    let acctType   = 'user';
    let verifiedEmail = '';
    let currentStep = 1;

    function setAcctType(type) {
      acctType = type;
      document.getElementById('acctUser').classList.toggle('active', type === 'user');
      document.getElementById('acctProvider').classList.toggle('active', type === 'provider');
      clearAlert();
    }

    function showAlert(msg, type = 'error') {
      const el  = document.getElementById('fpAlert');
      const txt = document.getElementById('fpAlertTxt');
      txt.textContent = msg;
      el.className = 'fp-alert show ' + type;
    }
    function clearAlert() {
      document.getElementById('fpAlert').className = 'fp-alert';
    }

    function setStepLoading(btnId, on) {
      const btn = document.getElementById(btnId);
      btn.classList.toggle('loading', on);
      btn.disabled = on;
    }

    function goToStep(n) {
      [1, 2, 3].forEach(i => {
        document.getElementById('step' + i).classList.toggle('active', i === n);
        const dot = document.getElementById('dot' + i);
        if (dot) {
          dot.classList.remove('active', 'done');
          if (i === n)       dot.classList.add('active');
          else if (i < n)    dot.classList.add('done');
        }
      });
      currentStep = n;
      clearAlert();
    }

    function goBack() {
      if (currentStep > 1) goToStep(currentStep - 1);
    }

    /* ── Step 1: Verify email exists ── */
    async function doStep1() {
      const email = document.getElementById('fpEmail').value.trim();
      if (!email) { showAlert('Please enter your email address.'); return; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showAlert('Please enter a valid email address.'); return; }

      setStepLoading('btnStep1', true);
      try {
        const fd = new FormData();
        fd.append('action', 'verify_email');
        fd.append('email', email);
        fd.append('acct_type', acctType);

        const res  = await fetch('api/forgot_password_api.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          verifiedEmail = email;
          document.getElementById('emailConfirmDisplay').textContent = email;
          goToStep(2);
        } else {
          showAlert(data.message || 'No account found with that email.');
        }
      } catch (e) {
        showAlert('Network error. Please try again.');
      } finally {
        setStepLoading('btnStep1', false);
      }
    }

    /* ── Step 2: Set new password ── */
    async function doStep2() {
      const newPwd  = document.getElementById('fpNewPwd').value;
      const confirm = document.getElementById('fpConfirmPwd').value;

      if (!newPwd || !confirm) { showAlert('Please fill in both password fields.'); return; }
      if (newPwd.length < 8)   { showAlert('Password must be at least 8 characters long.'); return; }
      if (!/[A-Z]/.test(newPwd)) { showAlert('Password must contain at least one uppercase letter (A–Z).'); return; }
      if (!/[a-z]/.test(newPwd)) { showAlert('Password must contain at least one lowercase letter (a–z).'); return; }
      if (!/[0-9]/.test(newPwd)) { showAlert('Password must contain at least one number (0–9).'); return; }
      if (newPwd !== confirm)    { showAlert('Passwords do not match. Please try again.'); return; }

      setStepLoading('btnStep2', true);
      try {
        const fd = new FormData();
        fd.append('action', 'reset_password');
        fd.append('email', verifiedEmail);
        fd.append('acct_type', acctType);
        fd.append('new_password', newPwd);

        const res  = await fetch('api/forgot_password_api.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
          goToStep(3);
        } else {
          showAlert(data.message || 'Could not reset password. Please try again.');
        }
      } catch (e) {
        showAlert('Network error. Please try again.');
      } finally {
        setStepLoading('btnStep2', false);
      }
    }

    /* ── Password strength meter ── */
    function updateFpStrength(val) {
      const wrap = document.getElementById('fpPwdStrength');
      wrap.style.display = val.length > 0 ? 'block' : 'none';

      const hasLen   = val.length >= 8;
      const hasUpper = /[A-Z]/.test(val);
      const hasLower = /[a-z]/.test(val);
      const hasNum   = /[0-9]/.test(val);
      const score    = [hasLen, hasUpper, hasLower, hasNum].filter(Boolean).length;

      const met   = '#059669';
      const unmet = '#8E8E93';
      document.getElementById('fpreq-len').style.color   = hasLen   ? met : unmet;
      document.getElementById('fpreq-upper').style.color = hasUpper ? met : unmet;
      document.getElementById('fpreq-lower').style.color = hasLower ? met : unmet;
      document.getElementById('fpreq-num').style.color   = hasNum   ? met : unmet;

      const barClass = score <= 1 ? '#ef4444' : score === 2 ? '#f59e0b' : score === 3 ? '#10b981' : '#059669';
      for (let i = 1; i <= 4; i++) {
        document.getElementById('fpbar' + i).style.background = i <= score ? barClass : '#EDE8E0';
      }

      clearAlert();
    }

    /* ── Toggle password visibility ── */
    function toggleFpPwd(id, btn) {
      const inp  = document.getElementById(id);
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.querySelector('i').className = show ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
    }

    /* ── Enter key support ── */
    document.addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      if (currentStep === 1) doStep1();
      else if (currentStep === 2) doStep2();
    });
  </script>
</body>

</html>

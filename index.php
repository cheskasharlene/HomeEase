<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!empty($_SESSION['user_id'])) {
  $dest = $_SESSION['user_role'] === 'admin' ? 'admin/admindashboard.php' : 'home.php';
  header("Location: $dest");
  exit;
}
if (!empty($_SESSION['provider_id'])) {
  header("Location: providers/provider_home.php");
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/main.css?v=<?php echo filemtime('assets/css/main.css'); ?>">
  <link rel="stylesheet" href="assets/css/index.css?v=<?php echo filemtime('assets/css/index.css'); ?>">
  <style>
    /* ── Login page critical styles (inline to prevent cache issues) ── */
    .logo-hdr {
      padding: 48px 28px 22px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 10px;
      flex-shrink: 0;
    }
    .logo-box {
      width: 62px;
      height: 62px;
      border-radius: 18px;
      background: linear-gradient(135deg, #E8820C 0%, #F5A623 55%, #FFB347 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 28px rgba(232,130,12,0.35), 0 4px 10px rgba(245,166,35,0.2);
      flex-shrink: 0;
    }
    .logo-box svg { width: 36px; height: 36px; }
    .logo-nm {
      font-family: 'Poppins', sans-serif;
      font-size: 24px;
      font-weight: 800;
      color: var(--txt-primary, #1A1A2E);
      letter-spacing: -0.4px;
      line-height: 1;
    }
    .logo-nm span { color: var(--teal, #F5A623); font-weight: 400; }
    .logo-tag {
      font-size: 12px;
      color: var(--txt-muted, #8E8E93);
      font-weight: 600;
      text-align: center;
    }
    .tab-row {
      display: flex;
      gap: 4px;
      margin: 0 22px 16px;
      background: var(--teal-bg, #FFF8F0);
      border-radius: 50px;
      padding: 4px;
      flex-shrink: 0;
    }
    .tab {
      flex: 1;
      text-align: center;
      padding: 10px;
      border-radius: 50px;
      font-family: 'Nunito', sans-serif;
      font-size: 13px;
      font-weight: 700;
      color: var(--txt-muted, #8E8E93);
      cursor: pointer;
      transition: background 0.2s, color 0.2s;
      user-select: none;
    }
    .tab.on {
      background: var(--teal, #F5A623);
      color: #fff;
      box-shadow: 0 3px 10px rgba(245,166,35,0.35);
    }
    .form-body {
      flex: 1;
      overflow-y: auto;
      padding: 0 22px 28px;
    }
    .panel { display: none; }
    .panel.on { display: block; }
    .btn-main {
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
      margin-top: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .btn-main:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(232,130,12,0.4); }
    .btn-main:active { transform: translateY(0); }
    .btn-main .btn-spinner {
      width: 16px; height: 16px;
      border: 2.5px solid rgba(255,255,255,.35);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none;
    }
    .btn-main.loading .btn-spinner { display: block; }
    .btn-main.loading .btn-label { display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .alert {
      display: none;
      align-items: center;
      gap: 8px;
      padding: 11px 14px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 14px;
      border: 1.5px solid transparent;
    }
    .alert.show { display: flex; }
    .alert.error { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .alert.success { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .fi-wrap { position: relative; }
    .fi-wrap .fi { padding-right: 42px; }
    .fi-icon {
      position: absolute;
      right: 14px; top: 50%;
      transform: translateY(-50%);
      color: var(--txt-muted, #8E8E93);
      font-size: 15px;
      pointer-events: none;
    }
    .eye-btn {
      position: absolute;
      right: 14px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: var(--txt-muted, #8E8E93);
      font-size: 15px; cursor: pointer; padding: 4px;
      display: flex; align-items: center;
    }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 0 12px; }
    .row-sp { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; gap: 8px; }
    .check-label { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--txt-muted, #8E8E93); cursor: pointer; user-select: none; }
    .check-label input[type=checkbox] { accent-color: var(--teal, #F5A623); width: 14px; height: 14px; }
    .link-btn { background: none; border: none; color: var(--teal, #F5A623); font-size: 12px; font-weight: 700; cursor: pointer; padding: 0; font-family: 'Nunito', sans-serif; }
    .link-btn:hover { text-decoration: underline; }
    .switch-row { text-align: center; margin-top: 18px; font-size: 12px; color: var(--txt-muted, #8E8E93); font-weight: 600; }
    .switch-row a { color: var(--teal, #F5A623); font-weight: 800; cursor: pointer; text-decoration: none; }
    .acct-type-row { display: flex; gap: 10px; margin-bottom: 18px; }
    .acct-type-btn {
      flex: 1; padding: 12px 8px; border-radius: 13px;
      border: 2px solid var(--border-col, #EDE8E0);
      background: #FAFAF8; cursor: pointer;
      display: flex; flex-direction: column; align-items: center; gap: 6px;
      font-family: 'Nunito', sans-serif; font-size: 12px; font-weight: 700;
      color: var(--txt-muted, #8E8E93); transition: all 0.22s;
    }
    .acct-type-btn i { font-size: 22px; }
    .acct-type-btn.active {
      border-color: var(--teal, #F5A623);
      background: var(--teal-bg, #FFF8F0);
      color: var(--teal, #F5A623);
      box-shadow: 0 0 0 4px rgba(245,166,35,0.1);
    }
    .acct-type-lbl { font-size: 11px; font-weight: 800; color: var(--txt-muted, #8E8E93); text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 10px; display: block; }
    #regSpecialtyWrap { display: none; }
  </style>
</head>

<body>
  <div class="shell">
   
    <div class="logo-hdr">
      <div class="logo-box">
        <svg viewBox="0 0 54 54" fill="none">
          <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white" />
          <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" />
        </svg>
      </div>
      <div class="logo-nm">Home<span>Ease</span></div>
      <div class="logo-tag">Book trusted home services in minutes</div>
    </div>


    <div class="tab-row">
      <div class="tab on" id="tabLogin" onclick="switchTab('login')">Sign In</div>
      <div class="tab" id="tabReg" onclick="switchTab('reg')">Create Account</div>
    </div>

    <div class="form-body">

   
      <div class="panel on" id="panelLogin">
        <div class="alert error" id="loginErr"><i class="bi bi-exclamation-circle-fill"></i><span
            id="loginErrTxt"></span></div>
        <div class="alert success" id="loginOk"><i class="bi bi-check-circle-fill"></i><span id="loginOkTxt"></span>
        </div>

        <div class="fg">
          <label class="fl">Email Address</label>
          <div class="fi-wrap">
            <input type="email" class="fi" id="loginEmail" placeholder="you@example.com" autocomplete="email" />
            <i class="bi bi-envelope-fill fi-icon"></i>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Password</label>
          <div class="fi-wrap">
            <input type="password" class="fi" id="loginPwd" placeholder="Enter your password"
              autocomplete="current-password" />
            <button class="eye-btn" type="button" onclick="togglePwd('loginPwd',this)"><i
                class="bi bi-eye-fill"></i></button>
          </div>
        </div>

        <div class="row-sp">
          <label class="check-label"><input type="checkbox" id="rememberMe"> Remember me</label>
          <button class="link-btn" type="button" onclick="goForgot()">Forgot password?</button>
        </div>

        <button class="btn-main" id="btnLogin" onclick="doLogin()">
          <span class="btn-spinner"></span>
          <span class="btn-label">Sign In</span>
        </button>

        <div class="switch-row">Don't have an account? <a onclick="switchTab('reg')">Sign up free</a></div>
      </div>
   


      <div class="panel" id="panelReg">
        <div class="alert error" id="regErr"><i class="bi bi-exclamation-circle-fill"></i><span id="regErrTxt"></span>
        </div>
        <div class="alert success" id="regOk"><i class="bi bi-check-circle-fill"></i><span id="regOkTxt"></span></div>

    
        <span class="acct-type-lbl">I am a…</span>
        <div class="acct-type-row">
          <div class="acct-type-btn active" id="typeUser" onclick="setAccountType('user')">
            <i class="bi bi-person-fill"></i>
            Homeowner
            <span style="font-size:10px;font-weight:600;opacity:.7;">Book services</span>
          </div>
          <div class="acct-type-btn" id="typeProvider" onclick="setAccountType('provider')">
            <i class="bi bi-tools"></i>
            Service Provider
            <span style="font-size:10px;font-weight:600;opacity:.7;">Offer services</span>
          </div>
        </div>

        <div class="two-col">
          <div class="fg">
            <label class="fl">First Name</label>
            <div class="fi-wrap">
              <input type="text" class="fi" id="regFirst" placeholder="Juan" autocomplete="given-name" />
              <i class="bi bi-person-fill fi-icon"></i>
            </div>
          </div>
          <div class="fg">
            <label class="fl">Last Name</label>
            <div class="fi-wrap">
              <input type="text" class="fi" id="regLast" placeholder="Dela Cruz" autocomplete="family-name" />
              <i class="bi bi-person-fill fi-icon"></i>
            </div>
          </div>
        </div>

        <div class="fg">
          <label class="fl">Email Address</label>
          <div class="fi-wrap">
            <input type="email" class="fi" id="regEmail" placeholder="you@example.com" autocomplete="email" />
            <i class="bi bi-envelope-fill fi-icon"></i>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Phone Number</label>
          <div class="fi-wrap">
            <input type="tel" class="fi" id="regPhone" placeholder="+63 9XX XXX XXXX" autocomplete="tel" />
            <i class="bi bi-telephone-fill fi-icon"></i>
          </div>
        </div>
        <div class="fg">
          <label class="fl" id="regAddressLabel">Home Address</label>
          <div class="fi-wrap">
            <input type="text" class="fi" id="regAddress" placeholder="e.g. 123 Mauban, Quezon"
              autocomplete="street-address" />
            <i class="bi bi-house-fill fi-icon"></i>
          </div>
        </div>


        <div class="fg" id="regSpecialtyWrap">
          <label class="fl">Specialty / Service Category</label>
          <div class="fi-wrap">
            <select class="fi" id="regSpecialty">
              <option value="">Select your specialty…</option>
              <option>Cleaner</option>
              <option>Helper</option>
              <option>Laundry Worker</option>
              <option>Plumber</option>
              <option>Carpenter</option>
              <option>Appliance Technician</option>
              <option>Other</option>
            </select>
          </div>
        </div>

        <div class="fg">
          <label class="fl">Password</label>
          <div class="fi-wrap">
            <input type="password" class="fi" id="regPwd" placeholder="Min. 8 characters" autocomplete="new-password" />
            <button class="eye-btn" type="button" onclick="togglePwd('regPwd',this)"><i
                class="bi bi-eye-fill"></i></button>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Confirm Password</label>
          <div class="fi-wrap">
            <input type="password" class="fi" id="regPwd2" placeholder="Re-enter password"
              autocomplete="new-password" />
            <button class="eye-btn" type="button" onclick="togglePwd('regPwd2',this)"><i
                class="bi bi-eye-fill"></i></button>
          </div>
        </div>

        <button class="btn-main" id="btnReg" onclick="doRegister()">
          <span class="btn-spinner"></span>
          <span class="btn-label">Create Account</span>
        </button>
        <div class="switch-row">Already have an account? <a onclick="switchTab('login')">Sign in</a></div>
      </div>


    </div>
  </div>

  <script>
    let accountType = 'user';

    function setAccountType(type) {
      accountType = type;
      document.getElementById('typeUser').classList.toggle('active', type === 'user');
      document.getElementById('typeProvider').classList.toggle('active', type === 'provider');
      document.getElementById('regSpecialtyWrap').style.display = type === 'provider' ? 'block' : 'none';
      document.getElementById('regAddressLabel').textContent = type === 'provider' ? 'Service Area' : 'Home Address';
      document.getElementById('regAddress').placeholder = type === 'provider' ? 'e.g. Quezon Province, Lucena City' : 'e.g. 123 Mauban, Quezon';
    }

    function switchTab(t) {
      document.getElementById('panelLogin').classList.toggle('on', t === 'login');
      document.getElementById('panelReg').classList.toggle('on', t === 'reg');
      document.getElementById('tabLogin').classList.toggle('on', t === 'login');
      document.getElementById('tabReg').classList.toggle('on', t === 'reg');
    }

    function togglePwd(id, btn) {
      const inp = document.getElementById(id);
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.querySelector('i').className = show ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
    }

    function setLoading(btnId, on) {
      const btn = document.getElementById(btnId);
      btn.classList.toggle('loading', on);
      btn.disabled = on;
    }

    function showAlert(id, txtId, text, type) {
      document.getElementById(txtId).textContent = text;
      document.getElementById(id).className = 'alert ' + type + ' show';
    }
    function clearAlert(id) { document.getElementById(id).className = 'alert'; }

    function goForgot() { window.location.href = 'forgot_password.php'; }
    function socialLogin(p) { window.location.href = 'api/oauth.php?provider=' + p; }

    function sanitizeServerMessage(msg) {
      if (!msg || typeof msg !== 'string') return '';
      const cleaned = msg.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
      if (!cleaned) return '';
      const lower = cleaned.toLowerCase();
      if (lower.includes('fatal error') || lower.includes('uncaught') || lower.includes('stack trace') || lower.includes('warning:')) {
        return 'The server encountered an internal error. Please try again in a moment.';
      }
      return cleaned;
    }

    async function parseApiResponse(res) {
      const text = await res.text();
      if (!text) {
        return { success: false, message: 'The server returned an empty response.' };
      }

      try {
        const data = JSON.parse(text);
        return {
          success: !!data.success,
          message: sanitizeServerMessage(data.message) || (res.ok ? '' : 'Request failed.'),
          redirect: data.redirect,
          data
        };
      } catch (err) {
        return {
          success: false,
          message: res.ok
            ? 'The server returned an unexpected response.'
            : 'The server could not process this request right now.'
        };
      }
    }

  
    function doLogin() {
      clearAlert('loginErr'); clearAlert('loginOk');
      const email = document.getElementById('loginEmail').value.trim();
      const pwd = document.getElementById('loginPwd').value;
      if (!email || !pwd) { showAlert('loginErr', 'loginErrTxt', 'Please fill in all fields.', 'error'); return; }
      setLoading('btnLogin', true);
      fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password: pwd })
      })
        .then(async (r) => {
          const result = await parseApiResponse(r);
          return { result, status: r.status };
        })
        .then(({ result, status }) => {
          setLoading('btnLogin', false);
          if (result.success) {
            showAlert('loginOk', 'loginOkTxt', 'Login successful! Redirecting…', 'success');
            setTimeout(() => { window.location.href = result.redirect || 'home.php'; }, 700);
          } else {
            const msg = result.message || (status >= 500 ? 'Server error. Please try again shortly.' : 'Invalid email or password.');
            showAlert('loginErr', 'loginErrTxt', msg, 'error');
          }
        })
        .catch((err) => {
          setLoading('btnLogin', false);
          const msg = err && err.message
            ? 'Could not reach the login service. ' + err.message
            : 'Could not reach the login service. Please try again.';
          showAlert('loginErr', 'loginErrTxt', msg, 'error');
        });
    }


    function doRegister() {
      clearAlert('regErr'); clearAlert('regOk');
      const first = document.getElementById('regFirst').value.trim();
      const last = document.getElementById('regLast').value.trim();
      const email = document.getElementById('regEmail').value.trim();
      const phone = document.getElementById('regPhone').value.trim();
      const address = document.getElementById('regAddress').value.trim();
      const specialty = document.getElementById('regSpecialty').value.trim();
      const pwd = document.getElementById('regPwd').value;
      const pwd2 = document.getElementById('regPwd2').value;

      if (!first || !last || !email || !phone || !pwd || !pwd2) { showAlert('regErr', 'regErrTxt', 'Please fill in all fields.', 'error'); return; }
      if (accountType === 'provider' && !specialty) { showAlert('regErr', 'regErrTxt', 'Please select your specialty.', 'error'); return; }
      if (pwd !== pwd2) { showAlert('regErr', 'regErrTxt', 'Passwords do not match.', 'error'); return; }
      if (pwd.length < 8) { showAlert('regErr', 'regErrTxt', 'Password must be at least 8 characters.', 'error'); return; }

      setLoading('btnReg', true);
      const endpoint = accountType === 'provider' ? 'providers/provider_register.php' : 'api/register.php';
      fetch(endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ first, last, email, phone, address, specialty, password: pwd, account_type: accountType })
      })
        .then(async (r) => {
          const result = await parseApiResponse(r);
          return { result, status: r.status };
        })
        .then(({ result, status }) => {
          setLoading('btnReg', false);
          if (result.success) {
            showAlert('regOk', 'regOkTxt', 'Account created! Redirecting…', 'success');
            setTimeout(() => { window.location.href = result.redirect || 'home.php'; }, 900);
          } else {
            const msg = result.message || (status >= 500 ? 'Server error. Please try again shortly.' : 'Registration failed.');
            showAlert('regErr', 'regErrTxt', msg, 'error');
          }
        })
        .catch((err) => {
          setLoading('btnReg', false);
          const msg = err && err.message
            ? 'Could not reach the registration service. ' + err.message
            : 'Could not reach the registration service. Please try again.';
          showAlert('regErr', 'regErrTxt', msg, 'error');
        });
    }

    document.addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      if (document.getElementById('panelLogin').classList.contains('on')) doLogin();
      else doRegister();
    });
  </script>
</body>

</html>
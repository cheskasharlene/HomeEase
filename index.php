<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!empty($_SESSION['user_id'])) {
  $dest = $_SESSION['user_role'] === 'admin' ? 'admindashboard.php' : 'home.php';
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
  <link rel="stylesheet" href="assets/css/index.css">
  <style>
    .shell {
      animation: fadeUp .45s cubic-bezier(.34, 1.4, .64, 1) both;
    }

    @keyframes fadeUp {
      from {
        opacity: 0;
        transform: translateY(24px) scale(.97)
      }

      to {
        opacity: 1;
        transform: none
      }
    }

    .panel {
      animation: panelIn .25s ease both;
    }

    @keyframes panelIn {
      from {
        opacity: 0;
        transform: translateY(8px)
      }

      to {
        opacity: 1;
        transform: none
      }
    }

    .two-col {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0 12px;
    }

    .row-sp {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
      gap: 8px;
    }

    .check-label {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 12px;
      font-weight: 600;
      color: var(--txt-muted);
      cursor: pointer;
      user-select: none;
    }

    .check-label input[type=checkbox] {
      accent-color: var(--teal);
      width: 14px;
      height: 14px;
    }

    .link-btn {
      background: none;
      border: none;
      color: var(--teal);
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      padding: 0;
      font-family: 'Nunito', sans-serif;
    }

    .link-btn:hover {
      text-decoration: underline;
    }

    .switch-row {
      text-align: center;
      margin-top: 18px;
      font-size: 12px;
      color: var(--txt-muted);
      font-weight: 600;
    }

    .switch-row a {
      color: var(--teal);
      font-weight: 800;
      cursor: pointer;
      text-decoration: none;
    }

    .social-row {
      display: flex;
      gap: 10px;
    }

    .btn-social {
      flex: 1;
      padding: 11px 8px;
      border-radius: 12px;
      border: 2px solid var(--border-col);
      background: #fafafa;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      font-size: 12px;
      font-weight: 700;
      color: var(--txt-primary);
      cursor: pointer;
      font-family: 'Nunito', sans-serif;
      transition: border-color .2s, background .2s;
    }

    .btn-social:hover {
      border-color: var(--teal);
      background: rgba(245, 166, 35, .06);
    }

    .btn-main {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-main .btn-spinner {
      width: 16px;
      height: 16px;
      border: 2.5px solid rgba(255, 255, 255, .35);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none;
    }

    .btn-main.loading .btn-spinner {
      display: block;
    }

    .btn-main.loading .btn-label {
      display: none;
    }

    /* ── Account type selector ── */
    .acct-type-row {
      display: flex;
      gap: 10px;
      margin-bottom: 18px;
    }

    .acct-type-btn {
      flex: 1;
      padding: 12px 8px;
      border-radius: 13px;
      border: 2px solid var(--border-col);
      background: #FAFAF8;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      font-family: 'Nunito', sans-serif;
      font-size: 12px;
      font-weight: 700;
      color: var(--txt-muted);
      transition: all 0.22s;
    }

    .acct-type-btn i {
      font-size: 22px;
    }

    .acct-type-btn.active {
      border-color: var(--teal);
      background: var(--teal-bg);
      color: var(--teal);
      box-shadow: 0 0 0 4px rgba(245, 166, 35, 0.1);
    }

    .acct-type-lbl {
      font-size: 11px;
      font-weight: 800;
      color: var(--txt-muted);
      text-transform: uppercase;
      letter-spacing: 0.6px;
      margin-bottom: 10px;
      display: block;
    }

    #regSpecialtyWrap {
      display: none;
    }
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

        <div class="divider">or continue with</div>
        <div class="social-row">
          <button class="btn-social" onclick="socialLogin('google')">
            <svg width="17" height="17" viewBox="0 0 24 24">
              <path
                d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                fill="#4285F4" />
              <path
                d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                fill="#34A853" />
              <path
                d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"
                fill="#FBBC05" />
              <path
                d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                fill="#EA4335" />
            </svg>
            Google
          </button>
          <button class="btn-social" onclick="socialLogin('facebook')">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="#1877F2">
              <path
                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
            </svg>
            Facebook
          </button>
        </div>
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
              <option>Cleaning</option>
              <option>Plumbing</option>
              <option>Electrical</option>
              <option>Painting</option>
              <option>Appliance Repair</option>
              <option>Gardening</option>
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
        .then(r => r.json())
        .then(d => {
          setLoading('btnLogin', false);
          if (d.success) {
            showAlert('loginOk', 'loginOkTxt', 'Login successful! Redirecting…', 'success');
            setTimeout(() => { window.location.href = d.redirect || 'home.php'; }, 700);
          } else {
            showAlert('loginErr', 'loginErrTxt', d.message || 'Invalid email or password.', 'error');
          }
        })
        .catch(() => { setLoading('btnLogin', false); showAlert('loginErr', 'loginErrTxt', 'Network error. Please try again.', 'error'); });
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
        .then(r => r.json())
        .then(d => {
          setLoading('btnReg', false);
          if (d.success) {
            showAlert('regOk', 'regOkTxt', 'Account created! Redirecting…', 'success');
            setTimeout(() => { window.location.href = d.redirect || 'home.php'; }, 900);
          } else {
            showAlert('regErr', 'regErrTxt', d.message || 'Registration failed.', 'error');
          }
        })
        .catch(() => { setLoading('btnReg', false); showAlert('regErr', 'regErrTxt', 'Network error. Please try again.', 'error'); });
    }

    document.addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      if (document.getElementById('panelLogin').classList.contains('on')) doLogin();
      else doRegister();
    });
  </script>
</body>

</html>
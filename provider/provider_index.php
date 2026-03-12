<?php
if (session_status() === PHP_SESSION_NONE)
  session_start();
// show onboarding once per session if the user is not already authenticated
if (empty($_SESSION['provider_id']) && empty($_SESSION['user_id']) && empty($_SESSION['seen_onboarding'])) {
  $_SESSION['seen_onboarding'] = true;
  header('Location: ../onboarding.php');
  exit;
}
if (!empty($_SESSION['provider_id'])) {
  header('Location: provider_home.php');
  exit;
}
if (!empty($_SESSION['user_id'])) {
  // already logged in as homeowner/client
  header('Location: ../home.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Provider Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/index.css">
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

    .switch-row a:hover {
      text-decoration: underline;
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
      background: rgba(13, 148, 136, .06);
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

    /* PIN OVERLAY */
    .pin-overlay {
      position: fixed;
      inset: 0;
      z-index: 999;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(6, 30, 27, .65);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      opacity: 0;
      pointer-events: none;
      transition: opacity .3s ease;
    }

    .pin-overlay.visible {
      opacity: 1;
      pointer-events: all;
    }

    .pin-phone {
      position: relative;
      width: 100%;
      max-width: 390px;
      height: 100dvh;
      max-height: 844px;
      background: #0b3d38;
      background-image: radial-gradient(circle at 20% 30%, rgba(13, 200, 180, .2) 0%, transparent 55%), radial-gradient(circle at 80% 70%, rgba(5, 120, 110, .25) 0%, transparent 55%);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      box-shadow: 0 40px 100px rgba(0, 0, 0, .55), 0 0 0 1px rgba(255, 255, 255, .06);
      transform: translateY(40px) scale(.97);
      opacity: 0;
      transition: transform .38s cubic-bezier(.34, 1.4, .64, 1), opacity .3s ease;
    }

    .pin-overlay.visible .pin-phone {
      transform: translateY(0) scale(1);
      opacity: 1;
    }

    @media(min-height:860px) {
      .pin-phone {
        border-radius: 44px;
      }
    }

    .pin-phone::before {
      content: '';
      position: absolute;
      inset: 0;
      z-index: 0;
      pointer-events: none;
      background-image: linear-gradient(rgba(255, 255, 255, .04) 1px, transparent 1px), linear-gradient(90deg, rgba(255, 255, 255, .04) 1px, transparent 1px);
      background-size: 55px 55px;
      animation: pgrid 10s linear infinite;
    }

    @keyframes pgrid {
      from {
        transform: translate(0, 0)
      }

      to {
        transform: translate(55px, 55px)
      }
    }

    .pin-topbar {
      display: flex;
      align-items: center;
      padding: 18px 22px 0;
      flex-shrink: 0;
      position: relative;
      z-index: 1;
    }

    .pin-back {
      background: none;
      border: none;
      color: rgba(255, 255, 255, .5);
      cursor: pointer;
      font-size: 22px;
      padding: 8px;
      line-height: 1;
      transition: color .2s;
      -webkit-tap-highlight-color: transparent;
    }

    .pin-back:hover {
      color: #fff;
    }

    .pin-header {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 0 32px 8px;
      min-height: 0;
      position: relative;
      z-index: 1;
    }

    .pin-icon-wrap {
      width: 64px;
      height: 64px;
      border-radius: 18px;
      background: rgba(255, 255, 255, .12);
      border: 1.5px solid rgba(255, 255, 255, .2);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 22px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, .25);
    }

    .pin-icon-wrap i {
      font-size: 26px;
      color: #fff;
    }

    .pin-title {
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: .18em;
      text-transform: uppercase;
      color: #fff;
      text-align: center;
      margin-bottom: 6px;
    }

    .pin-sub {
      font-family: 'Poppins', sans-serif;
      font-size: 9px;
      font-weight: 600;
      letter-spacing: .1em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, .45);
      text-align: center;
    }

    .pin-dots {
      display: flex;
      gap: 18px;
      justify-content: center;
      padding: 32px 0 22px;
      flex-shrink: 0;
      position: relative;
      z-index: 1;
    }

    .pin-dot {
      width: 15px;
      height: 15px;
      border-radius: 50%;
      border: 2px solid rgba(255, 255, 255, .4);
      background: transparent;
      transition: background .18s, border-color .18s, transform .15s;
    }

    .pin-dot.filled {
      background: #fff;
      border-color: #fff;
      transform: scale(1.15);
    }

    .pin-dot.shake {
      animation: pds .42s ease;
      background: #ff6b6b;
      border-color: #ff6b6b;
    }

    @keyframes pds {

      0%,
      100% {
        transform: translateX(0)
      }

      25% {
        transform: translateX(-7px)
      }

      75% {
        transform: translateX(7px)
      }
    }

    .pin-keypad {
      padding: 8px 32px 40px;
      flex-shrink: 0;
      position: relative;
      z-index: 1;
    }

    @supports(padding-bottom:env(safe-area-inset-bottom)) {
      .pin-keypad {
        padding-bottom: calc(40px + env(safe-area-inset-bottom));
      }
    }

    .pin-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      width: 100%;
      max-width: 280px;
      margin: 0 auto;
    }

    .pin-key {
      aspect-ratio: 1;
      border-radius: 50%;
      border: 1.5px solid rgba(255, 255, 255, .22);
      background: rgba(255, 255, 255, .08);
      color: #fff;
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 400;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 2px;
      transition: background .12s, transform .1s, border-color .12s;
      user-select: none;
      -webkit-tap-highlight-color: transparent;
    }

    .pin-key .key-num {
      line-height: 1;
    }

    .pin-key .key-letter {
      font-size: 7.5px;
      font-weight: 700;
      letter-spacing: .12em;
      color: rgba(255, 255, 255, .35);
      text-transform: uppercase;
      line-height: 1;
      min-height: 9px;
    }

    .pin-key:active,
    .pin-key.tap {
      background: rgba(255, 255, 255, .25);
      border-color: rgba(255, 255, 255, .6);
      transform: scale(.9);
    }

    .pin-key.ghost {
      background: none;
      border: none;
      cursor: default;
      pointer-events: none;
    }

    .pin-key.del i {
      font-size: 18px;
      pointer-events: none;
    }
  </style>
</head>

<body>

  <!-- PIN OVERLAY -->
  <div class="pin-overlay" id="pinOverlay">
    <div class="pin-phone">
      <div class="pin-topbar">
        <button class="pin-back" onclick="closePinPad()"><i class="bi bi-arrow-left"></i></button>
      </div>
      <div class="pin-header">
        <div class="pin-icon-wrap"><i class="bi bi-shield-lock-fill"></i></div>
        <div class="pin-title" id="pinTitle">Enter PIN Code</div>
        <div class="pin-sub" id="pinSub">Please enter your 4-digit PIN code</div>
      </div>
      <div class="pin-dots">
        <div class="pin-dot" id="pd0"></div>
        <div class="pin-dot" id="pd1"></div>
        <div class="pin-dot" id="pd2"></div>
        <div class="pin-dot" id="pd3"></div>
      </div>
      <div class="pin-keypad">
        <div class="pin-grid">
          <button class="pin-key" onclick="pinKey(this,'1')"><span class="key-num">1</span><span
              class="key-letter">&nbsp;</span></button>
          <button class="pin-key" onclick="pinKey(this,'2')"><span class="key-num">2</span><span
              class="key-letter">ABC</span></button>
          <button class="pin-key" onclick="pinKey(this,'3')"><span class="key-num">3</span><span
              class="key-letter">DEF</span></button>
          <button class="pin-key" onclick="pinKey(this,'4')"><span class="key-num">4</span><span
              class="key-letter">GHI</span></button>
          <button class="pin-key" onclick="pinKey(this,'5')"><span class="key-num">5</span><span
              class="key-letter">JKL</span></button>
          <button class="pin-key" onclick="pinKey(this,'6')"><span class="key-num">6</span><span
              class="key-letter">MNO</span></button>
          <button class="pin-key" onclick="pinKey(this,'7')"><span class="key-num">7</span><span
              class="key-letter">PQRS</span></button>
          <button class="pin-key" onclick="pinKey(this,'8')"><span class="key-num">8</span><span
              class="key-letter">TUV</span></button>
          <button class="pin-key" onclick="pinKey(this,'9')"><span class="key-num">9</span><span
              class="key-letter">WXYZ</span></button>
          <button class="pin-key ghost"></button>
          <button class="pin-key" onclick="pinKey(this,'0')"><span class="key-num">0</span><span
              class="key-letter">+</span></button>
          <button class="pin-key del" onclick="pinDel()"><i class="bi bi-delete-left-fill"></i></button>
        </div>
      </div>
    </div>
  </div>

  <!-- MAIN SHELL -->
  <div class="shell">

    <!-- LOGO HEADER -->
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

    <!-- TABS -->
    <div class="tab-row">
      <div class="tab on" id="tabLogin" onclick="switchTab('login')">Sign In</div>
      <div class="tab" id="tabReg" onclick="switchTab('reg')">Create Account</div>
    </div>

    <!-- FORM BODY -->
    <div class="form-body">

      <!-- LOGIN PANEL -->
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
      <!-- /LOGIN PANEL -->

      <!-- REGISTER PANEL -->
      <div class="panel" id="panelReg">
        <div class="alert error" id="regErr"><i class="bi bi-exclamation-circle-fill"></i><span id="regErrTxt"></span>
        </div>
        <div class="alert success" id="regOk"><i class="bi bi-check-circle-fill"></i><span id="regOkTxt"></span></div>

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
          <label class="fl">Home Address</label>
          <div class="fi-wrap">
            <input type="text" class="fi" id="regAddress" placeholder="e.g. 123 Mauban, Quezon"
              autocomplete="street-address" />
            <i class="bi bi-house-fill fi-icon"></i>
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
      <!-- /REGISTER PANEL -->

    </div>
  </div>

  <script>
    /* TAB SWITCH */
    function switchTab(t) {
      document.getElementById('panelLogin').classList.toggle('on', t === 'login');
      document.getElementById('panelReg').classList.toggle('on', t === 'reg');
      document.getElementById('tabLogin').classList.toggle('on', t === 'login');
      document.getElementById('tabReg').classList.toggle('on', t === 'reg');
    }

    /* PASSWORD TOGGLE */
    function togglePwd(id, btn) {
      const inp = document.getElementById(id);
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.querySelector('i').className = show ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
    }

    /* LOADING STATE */
    function setLoading(btnId, on) {
      const btn = document.getElementById(btnId);
      btn.classList.toggle('loading', on);
      btn.disabled = on;
    }

    /* ALERTS */
    function showAlert(id, txtId, text, type) {
      document.getElementById(txtId).textContent = text;
      document.getElementById(id).className = 'alert ' + type + ' show';
    }
    function clearAlert(id) {
      document.getElementById(id).className = 'alert';
    }

    /* FORGOT / SOCIAL */
    function goForgot() { window.location.href = '../forgot_password.php'; }
    // socialLogin removed - third-party logins disabled
    function socialLogin(p) { /* no-op */ }

    /* LOGIN - EMAIL/PASSWORD */
    function doLogin() {
      clearAlert('loginErr'); clearAlert('loginOk');
      const email = document.getElementById('loginEmail').value.trim();
      const pwd = document.getElementById('loginPwd').value;
      if (!email || !pwd) {
        showAlert('loginErr', 'loginErrTxt', 'Please fill in all fields.', 'error');
        return;
      }
      setLoading('btnLogin', true);

      // Verify provider credentials
      fetch('../api/provider_login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: email,
          password: pwd
        })
      })
        .then(r => r.json())
        .then(d => {
          if (d.success) {
            setLoading('btnLogin', false);
            showAlert('loginOk', 'loginOkTxt', 'Login successful! Redirecting…', 'success');
            const target = (d.redirect || 'provider_home.php').replace(/^provider\//, '');
            setTimeout(() => { window.location.href = target; }, 700);
          } else {
            setLoading('btnLogin', false);
            showAlert('loginErr', 'loginErrTxt', d.message || 'Invalid email or password. Please try again.', 'error');
          }
        })
        .catch(() => {
          setLoading('btnLogin', false);
          showAlert('loginErr', 'loginErrTxt', 'Network error. Please try again.', 'error');
        });
    }

    /* REGISTER */
    function doRegister() {
      clearAlert('regErr'); clearAlert('regOk');
      const first = document.getElementById('regFirst').value.trim();
      const last = document.getElementById('regLast').value.trim();
      const email = document.getElementById('regEmail').value.trim();
      const phone = document.getElementById('regPhone').value.trim();
      const pwd = document.getElementById('regPwd').value;
      const pwd2 = document.getElementById('regPwd2').value;
      if (!first || !last || !email || !phone || !pwd || !pwd2) {
        showAlert('regErr', 'regErrTxt', 'Please fill in all fields.', 'error');
        return;
      }
      if (pwd !== pwd2) {
        showAlert('regErr', 'regErrTxt', 'Passwords do not match.', 'error');
        return;
      }
      if (pwd.length < 8) {
        showAlert('regErr', 'regErrTxt', 'Password must be at least 8 characters.', 'error');
        return;
      }
      setLoading('btnReg', true);
      const address = document.getElementById('regAddress').value.trim();
      openPinPad('register', first + ' ' + last, { first, last, email, phone, address, password: pwd });
    }

    /* SUBMIT REGISTER AFTER PIN */
    function submitReg(pin) {
      fetch('../api/provider_register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ..._regData, pin })
      })
        .then(r => r.json())
        .then(d => {
          closePinPad();
          setLoading('btnReg', false);
          if (d.success) {
            showAlert('regOk', 'regOkTxt', 'Account created! Redirecting…', 'success');
            const target = (d.redirect || 'provider_home.php').replace(/^provider\//, '');
            setTimeout(() => { window.location.href = target; }, 900);
          } else {
            showAlert('regErr', 'regErrTxt', d.message || 'Registration failed.', 'error');
          }
        })
        .catch((err) => {
          closePinPad();
          setLoading('btnReg', false);
          console.error('Register error:', err);
          showAlert('regErr', 'regErrTxt', 'Network error. Check that register.php exists.', 'error');
        });
    }

    /* ENTER KEY */
    document.addEventListener('keydown', e => {
      if (e.key !== 'Enter') return;
      if (document.getElementById('panelLogin').classList.contains('on')) doLogin();
      else doRegister();
    });

    /* PIN PAD */
    let _pinMode = 'login', _pinStep = 'set', _pinFirst = '', _pinBuf = '', _regData = null, _userName = '';

    function openPinPad(mode, userName, regData = null) {
      _pinMode = mode;
      _pinFirst = '';
      _pinBuf = '';
      _userName = userName;
      _regData = regData;

      if (mode === 'login') {
        _pinStep = 'verify';
        document.getElementById('pinTitle').textContent = 'Verify PIN Code';
        document.getElementById('pinSub').textContent = 'Enter your 4-digit PIN to complete login';
      } else {
        _pinStep = 'set';
        document.getElementById('pinTitle').textContent = 'Set Up PIN Code';
        document.getElementById('pinSub').textContent = 'Please enter your 4-digit PIN code';
      }

      syncDots();
      document.getElementById('pinOverlay').classList.add('visible');
    }

    function closePinPad() {
      document.getElementById('pinOverlay').classList.remove('visible');
      _pinBuf = '';
      _pinFirst = '';
      _pinMode = 'login';
      _pinStep = 'set';
      setLoading('btnReg', false);
    }

    function syncDots() {
      for (let i = 0; i < 4; i++) {
        const d = document.getElementById('pd' + i);
        d.classList.toggle('filled', i < _pinBuf.length);
        d.classList.remove('shake');
      }
    }

    function shakeError() {
      for (let i = 0; i < 4; i++) document.getElementById('pd' + i).classList.add('shake');
      setTimeout(() => { _pinBuf = ''; syncDots(); }, 500);
    }

    function pinKey(btn, d) {
      if (_pinBuf.length >= 4) return;
      btn.classList.add('tap');
      setTimeout(() => btn.classList.remove('tap'), 140);
      _pinBuf += d;
      syncDots();
      if (_pinBuf.length === 4) setTimeout(onPinFull, 220);
    }

    function pinDel() {
      if (_pinBuf.length) {
        _pinBuf = _pinBuf.slice(0, -1);
        syncDots();
      }
    }

    function onPinFull() {
      if (_pinMode === 'login') {
        // Login no longer uses PIN verification.
        closePinPad();
        _pinBuf = '';
        syncDots();
      } else if (_pinMode === 'register') {
        // For registration, follow the two-step PIN process
        if (_pinStep === 'set') {
          _pinFirst = _pinBuf;
          _pinBuf = '';
          _pinStep = 'confirm';
          document.getElementById('pinTitle').textContent = 'Confirm PIN Code';
          document.getElementById('pinSub').textContent = 'Re-enter your 4-digit PIN to confirm';
          syncDots();
        } else {
          if (_pinBuf === _pinFirst) {
            submitReg(_pinBuf);
          } else {
            shakeError();
            setTimeout(() => {
              _pinStep = 'set';
              _pinFirst = '';
              document.getElementById('pinTitle').textContent = 'Set Up PIN Code';
              document.getElementById('pinSub').textContent = "PINs didn't match — please try again";
            }, 560);
          }
        }
      }
    }
  </script>
</body>

</html>
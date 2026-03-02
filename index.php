<?php

if (session_status() === PHP_SESSION_NONE)
  session_start();
if (!empty($_SESSION['user_id'])) {
  $dest = $_SESSION['user_role'] === 'admin' ? 'admindashboard.php' : 'home.php';
  header("Location: $dest");
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
      <div class="logo-tag">Home services, simplified</div>
    </div>

    <div class="tab-row">
      <div class="tab on" id="tabLogin" onclick="switchTab('login')">Login</div>
      <div class="tab" id="tabReg" onclick="switchTab('register')">Sign Up</div>
    </div>

    <div class="form-body">

      <div class="panel on" id="panelLogin">
        <div class="alert error" id="loginErr"><i class="bi bi-exclamation-circle-fill"></i><span
            id="loginErrMsg"></span></div>
        <div class="alert success" id="loginOk"><i class="bi bi-check-circle-fill"></i><span id="loginOkMsg">Login
            successful! Redirecting...</span></div>

        <div class="fg">
          <label class="fl">Email Address</label>
          <div class="fi-wrap">
            <input class="fi" id="loginEmail" type="email" placeholder="your@email.com" autocomplete="email">
            <i class="bi bi-envelope fi-icon"></i>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Password</label>
          <div class="fi-wrap">
            <input class="fi" id="loginPass" type="password" placeholder="Your password"
              autocomplete="current-password">
            <button class="eye-btn" type="button" onclick="togglePwd('loginPass',this)"><i
                class="bi bi-eye-fill"></i></button>
          </div>
        </div>
        <button class="btn-main" id="btnLogin" onclick="doLogin()">Login</button>
      </div>

      <div class="panel" id="panelReg">
        <div class="alert error" id="regErr"><i class="bi bi-exclamation-circle-fill"></i><span id="regErrMsg"></span>
        </div>
        <div class="alert success" id="regOk"><i class="bi bi-check-circle-fill"></i><span id="regOkMsg">Account
            created! Redirecting...</span></div>

        <div class="fg">
          <label class="fl">Full Name</label>
          <div class="fi-wrap">
            <input class="fi" id="regName" type="text" placeholder="Juan dela Cruz" autocomplete="name">
            <i class="bi bi-person fi-icon"></i>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Email Address</label>
          <div class="fi-wrap">
            <input class="fi" id="regEmail" type="email" placeholder="your@email.com" autocomplete="email">
            <i class="bi bi-envelope fi-icon"></i>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Phone (optional)</label>
          <div class="fi-wrap">
            <input class="fi" id="regPhone" type="tel" placeholder="09XXXXXXXXX">
            <i class="bi bi-phone fi-icon"></i>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Password</label>
          <div class="fi-wrap">
            <input class="fi" id="regPass" type="password" placeholder="Min. 6 characters" autocomplete="new-password">
            <button class="eye-btn" type="button" onclick="togglePwd('regPass',this)"><i
                class="bi bi-eye-fill"></i></button>
          </div>
        </div>
        <div class="fg">
          <label class="fl">Confirm Password</label>
          <div class="fi-wrap">
            <input class="fi" id="regPass2" type="password" placeholder="Repeat password" autocomplete="new-password">
            <button class="eye-btn" type="button" onclick="togglePwd('regPass2',this)"><i
                class="bi bi-eye-fill"></i></button>
          </div>
        </div>
        <button class="btn-main" id="btnReg" onclick="doRegister()">Create Account</button>
      </div>

    </div>
  </div>

  <script>
    function switchTab(tab) {
      document.getElementById('panelLogin').classList.toggle('on', tab === 'login');
      document.getElementById('panelReg').classList.toggle('on', tab === 'register');
      document.getElementById('tabLogin').classList.toggle('on', tab === 'login');
      document.getElementById('tabReg').classList.toggle('on', tab === 'register');
    }

    function togglePwd(id, btn) {
      const inp = document.getElementById(id);
      const isPass = inp.type === 'password';
      inp.type = isPass ? 'text' : 'password';
      btn.querySelector('i').className = isPass ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
    }

    function showAlert(id, msgId, msg, type) {
      const el = document.getElementById(id);
      const msg_el = document.getElementById(msgId);
      el.className = `alert ${type} show`;
      if (msg_el) msg_el.textContent = msg;
    }
    function hideAlert(id) { document.getElementById(id).classList.remove('show'); }

    function setLoading(btnId, loading) {
      const btn = document.getElementById(btnId);
      btn.disabled = loading;
      btn.innerHTML = loading
        ? '<span class="spin"></span>Please wait...'
        : (btnId === 'btnLogin' ? 'Login' : 'Create Account');
    }

    async function doLogin() {
      hideAlert('loginErr'); hideAlert('loginOk');
      const email = document.getElementById('loginEmail').value.trim();
      const pass = document.getElementById('loginPass').value.trim();

      if (!email || !pass) {
        showAlert('loginErr', 'loginErrMsg', 'Please fill in all fields.', 'error');
        return;
      }

      setLoading('btnLogin', true);

      const form = new FormData();
      form.append('email', email);
      form.append('password', pass);

      try {
        const res = await fetch('api/login.php', { method: 'POST', body: form });
        const data = await res.json();

        if (data.success) {
          showAlert('loginOk', 'loginOkMsg', 'Login successful! Redirecting...', 'success');

          setTimeout(() => {
            window.location.href = data.user.role === 'admin' ? 'admindashboard.php' : 'home.php';
          }, 1000);
        } else {
          showAlert('loginErr', 'loginErrMsg', data.message, 'error');
          setLoading('btnLogin', false);
        }
      } catch (e) {
        showAlert('loginErr', 'loginErrMsg', 'Could not connect to server. Check XAMPP is running.', 'error');
        setLoading('btnLogin', false);
      }
    }

    async function doRegister() {
      hideAlert('regErr'); hideAlert('regOk');
      const name = document.getElementById('regName').value.trim();
      const email = document.getElementById('regEmail').value.trim();
      const phone = document.getElementById('regPhone').value.trim();
      const pass = document.getElementById('regPass').value.trim();
      const pass2 = document.getElementById('regPass2').value.trim();

      if (!name || !email || !pass) {
        showAlert('regErr', 'regErrMsg', 'Name, email and password are required.', 'error');
        return;
      }
      if (pass !== pass2) {
        showAlert('regErr', 'regErrMsg', 'Passwords do not match.', 'error');
        return;
      }
      if (pass.length < 6) {
        showAlert('regErr', 'regErrMsg', 'Password must be at least 6 characters.', 'error');
        return;
      }

      setLoading('btnReg', true);

      const form = new FormData();
      form.append('name', name);
      form.append('email', email);
      form.append('phone', phone);
      form.append('password', pass);

      try {
        const res = await fetch('api/register.php', { method: 'POST', body: form });
        const data = await res.json();

        if (data.success) {
          showAlert('regOk', 'regOkMsg', 'Account created! Redirecting...', 'success');
          setTimeout(() => { window.location.href = 'home.php'; }, 1000);
        } else {
          showAlert('regErr', 'regErrMsg', data.message, 'error');
          setLoading('btnReg', false);
        }
      } catch (e) {
        showAlert('regErr', 'regErrMsg', 'Could not connect to server. Check XAMPP is running.', 'error');
        setLoading('btnReg', false);
      }
    }

    document.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        const activePanel = document.querySelector('.panel.on').id;
        if (activePanel === 'panelLogin') doLogin();
        else doRegister();
      }
    });
  </script>
</body>

</html>
<?php
session_name('homeease_admin'); // Prevents conflict with homeeasev2 session
session_start();
if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $loggedIn = false;

        // Hardcoded super admin (matches your existing login.php)
        if ($email === 'cheska@admin.com' && $password === 'admin1234') {
            $_SESSION['user_id']    = 0;
            $_SESSION['user_name']  = 'Admin';
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role']  = 'admin';
            $loggedIn = true;
        }

        // DB-based admin users
        if (!$loggedIn) {
            $dbfile = file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : null;
            if ($dbfile) {
                require_once $dbfile;
                $stmt = $conn->prepare(
                    "SELECT id,name,email,password,role FROM users WHERE email=? AND role='admin'"
                );
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_name']  = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role']  = 'admin';
                    $loggedIn = true;
                }
            }
        }

        if ($loggedIn) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid admin credentials. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>HomeEase Admin — Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --teal:       #0d9488;
      --teal-dark:  #0f766e;
      --teal-light: #ccfbf1;
      --slate:      #0f172a;
      --slate-mid:  #1e293b;
      --txt:        #1e293b;
      --txt-muted:  #64748b;
      --border:     #e2e8f0;
      --bg:         #f1f5f9;
      --white:      #ffffff;
      --danger:     #ef4444;
    }

    html, body {
      height: 100%;
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
    }

    body {
      display: grid;
      grid-template-columns: 1fr 1fr;
      min-height: 100vh;
    }

    /* ── Left panel ── */
    .left-panel {
      background: var(--slate);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 60px;
      position: relative;
      overflow: hidden;
    }

    .left-panel::before {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      background: radial-gradient(circle, rgba(13,148,136,.35) 0%, transparent 70%);
      top: -100px; left: -100px;
      border-radius: 50%;
    }
    .left-panel::after {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      background: radial-gradient(circle, rgba(13,148,136,.2) 0%, transparent 70%);
      bottom: -80px; right: -80px;
      border-radius: 50%;
    }

    .lp-content { position: relative; z-index: 1; text-align: center; max-width: 380px; }

    .logo-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 14px;
      margin-bottom: 48px;
    }
    .logo-icon {
      width: 56px; height: 56px;
      background: var(--teal);
      border-radius: 16px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 8px 24px rgba(13,148,136,.5);
    }
    .logo-icon svg { width: 32px; height: 32px; }
    .logo-text { font-family: 'Poppins', sans-serif; font-size: 28px; font-weight: 800; color: #fff; }
    .logo-text span { color: var(--teal); }

    .hero-badge {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(13,148,136,.2);
      border: 1px solid rgba(13,148,136,.4);
      border-radius: 100px;
      padding: 6px 16px;
      color: #5eead4;
      font-size: 12px; font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      margin-bottom: 24px;
    }
    .hero-badge i { font-size: 11px; }

    .hero-title {
      font-family: 'Poppins', sans-serif;
      font-size: 36px; font-weight: 800;
      color: #fff; line-height: 1.2;
      margin-bottom: 16px;
    }
    .hero-title span { color: #5eead4; }

    .hero-sub {
      color: #94a3b8;
      font-size: 14px; line-height: 1.7;
      margin-bottom: 48px;
    }

    .stats-row {
      display: grid; grid-template-columns: repeat(3, 1fr);
      gap: 16px;
    }
    .stat-bubble {
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 16px;
      padding: 20px 12px;
      text-align: center;
    }
    .stat-bubble .val {
      font-family: 'Poppins', sans-serif;
      font-size: 24px; font-weight: 800;
      color: var(--teal);
      display: block;
    }
    .stat-bubble .lbl {
      font-size: 11px; color: #94a3b8;
      font-weight: 600; margin-top: 4px;
    }

    /* ── Right panel ── */
    .right-panel {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 60px;
      background: var(--white);
    }

    .form-card { width: 100%; max-width: 400px; }

    .form-header { margin-bottom: 36px; }
    .form-header h2 {
      font-family: 'Poppins', sans-serif;
      font-size: 28px; font-weight: 800;
      color: var(--txt);
      margin-bottom: 6px;
    }
    .form-header p { color: var(--txt-muted); font-size: 14px; }

    .error-box {
      display: flex; align-items: center; gap: 10px;
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 24px;
      color: var(--danger);
      font-size: 13px; font-weight: 600;
      animation: shake .4s ease;
    }
    @keyframes shake {
      0%,100%{transform:translateX(0)}
      25%{transform:translateX(-6px)}
      75%{transform:translateX(6px)}
    }

    .fg { margin-bottom: 20px; }
    .fl {
      display: block;
      font-size: 13px; font-weight: 700;
      color: var(--txt);
      margin-bottom: 7px;
    }
    .fi-wrap { position: relative; }
    .fi {
      width: 100%;
      padding: 13px 44px 13px 16px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-family: 'Nunito', sans-serif;
      font-size: 14px;
      color: var(--txt);
      background: #f8fafc;
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }
    .fi:focus {
      border-color: var(--teal);
      box-shadow: 0 0 0 3px rgba(13,148,136,.12);
      background: #fff;
    }
    .fi-icon {
      position: absolute; right: 14px; top: 50%;
      transform: translateY(-50%);
      color: var(--txt-muted); font-size: 15px;
      pointer-events: none;
    }
    .eye-btn {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none; cursor: pointer;
      color: var(--txt-muted); font-size: 15px;
      padding: 4px;
    }
    .eye-btn:hover { color: var(--teal); }

    .btn-submit {
      width: 100%;
      padding: 14px;
      background: var(--teal);
      color: #fff;
      border: none; border-radius: 10px;
      font-family: 'Poppins', sans-serif;
      font-size: 15px; font-weight: 700;
      cursor: pointer;
      transition: background .2s, transform .1s, box-shadow .2s;
      display: flex; align-items: center; justify-content: center; gap: 8px;
      margin-top: 8px;
    }
    .btn-submit:hover {
      background: var(--teal-dark);
      box-shadow: 0 6px 20px rgba(13,148,136,.35);
      transform: translateY(-1px);
    }
    .btn-submit:active { transform: translateY(0); }
    .btn-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }

    .form-footer {
      margin-top: 28px;
      text-align: center;
      padding-top: 24px;
      border-top: 1px solid var(--border);
    }
    .form-footer a {
      color: var(--teal);
      font-size: 13px; font-weight: 700;
      text-decoration: none;
    }
    .form-footer a:hover { text-decoration: underline; }
    .form-footer p { color: var(--txt-muted); font-size: 13px; }

    .spin {
      display: inline-block;
      width: 16px; height: 16px;
      border: 2px solid rgba(255,255,255,.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .6s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    @media (max-width: 768px) {
      body { grid-template-columns: 1fr; }
      .left-panel { display: none; }
      .right-panel { padding: 40px 24px; }
    }
  </style>
</head>
<body>

<!-- Left decorative panel -->
<div class="left-panel">
  <div class="lp-content">
    <div class="logo-wrap">
      <div class="logo-icon">
        <svg viewBox="0 0 54 54" fill="none">
          <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white"/>
          <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)"/>
        </svg>
      </div>
      <div class="logo-text">Home<span>Ease</span></div>
    </div>

    <div class="hero-badge"><i class="bi bi-shield-lock-fill"></i> Admin Portal</div>
    <h1 class="hero-title">Manage your<br><span>entire platform</span><br>from here.</h1>
    <p class="hero-sub">Full control over bookings, users, technicians, services, and business analytics — all in one place.</p>

    <div class="stats-row">
      <div class="stat-bubble">
        <span class="val">100%</span>
        <div class="lbl">Control</div>
      </div>
      <div class="stat-bubble">
        <span class="val">Live</span>
        <div class="lbl">Real-time Data</div>
      </div>
      <div class="stat-bubble">
        <span class="val">6+</span>
        <div class="lbl">Panels</div>
      </div>
    </div>
  </div>
</div>

<!-- Right login panel -->
<div class="right-panel">
  <div class="form-card">
    <div class="form-header">
      <h2>Welcome back 👋</h2>
      <p>Sign in with your admin credentials to continue.</p>
    </div>

    <?php if ($error): ?>
    <div class="error-box">
      <i class="bi bi-exclamation-circle-fill"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm">
      <div class="fg">
        <label class="fl" for="email">Email Address</label>
        <div class="fi-wrap">
          <input class="fi" type="email" name="email" id="email"
                 placeholder="admin@homeease.com"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 autocomplete="email" required>
          <i class="bi bi-envelope fi-icon"></i>
        </div>
      </div>

      <div class="fg">
        <label class="fl" for="password">Password</label>
        <div class="fi-wrap">
          <input class="fi" type="password" name="password" id="password"
                 placeholder="Your admin password"
                 autocomplete="current-password" required>
          <button class="eye-btn" type="button" onclick="togglePwd()">
            <i class="bi bi-eye-fill" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button class="btn-submit" type="submit" id="btnSubmit">
        <i class="bi bi-box-arrow-in-right"></i> Sign In to Dashboard
      </button>
    </form>

    <div class="form-footer">
      <p>Not an admin? <a href="../index.php">Go to user login →</a></p>
    </div>
  </div>
</div>

<script>
  function togglePwd() {
    const inp = document.getElementById('password');
    const ic  = document.getElementById('eyeIcon');
    const isPass = inp.type === 'password';
    inp.type = isPass ? 'text' : 'password';
    ic.className = isPass ? 'bi bi-eye-slash-fill' : 'bi bi-eye-fill';
  }

  document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spin"></span> Signing in...';
  });
</script>
</body>
</html>

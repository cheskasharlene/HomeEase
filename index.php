<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/main.css" rel="stylesheet">
  <style>

    #splash {
      background: var(--teal);
    }

    [data-theme="dark"] #onboard {
      background: var(--bg-shell);
    }

    [data-theme="dark"] .ob-title {
      color: var(--text-main);
    }

    .bubble {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, .1);
      animation: float 4s ease-in-out infinite;
    }

    .s-logo {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 14px;
      animation: pop .7s cubic-bezier(.175, .885, .32, 1.275) forwards;
    }

    .s-icon {
      width: 88px;
      height: 88px;
      background: rgba(255, 255, 255, .18);
      border-radius: 26px;
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(8px);
    }

    .s-icon svg {
      width: 52px;
      height: 52px;
    }

    .s-name {
      font-family: 'Poppins', sans-serif;
      font-size: 30px;
      font-weight: 800;
      color: #fff;
      letter-spacing: -.5px;
    }

    .s-name span {
      color: rgba(255, 255, 255, .65);
      font-weight: 400;
    }

    .s-dots {
      position: absolute;
      bottom: 55px;
      display: flex;
      gap: 6px;
      animation: fadeUp .5s .8s ease forwards;
      opacity: 0;
    }

    .s-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, .4);
      animation: pulse 1.2s infinite ease-in-out;
    }

    .s-dot:nth-child(2) {
      animation-delay: .2s;
    }

    .s-dot:nth-child(3) {
      animation-delay: .4s;
    }

    /* ── Onboarding ── */
    #onboard {
      background: #fff;
      justify-content: flex-start;
    }

    body.dark #onboard {
      background: var(--bg-shell);
    }

    body.dark .ob-title {
      color: var(--td);
    }

    .ob-hero {
      width: 100%;
      height: 55%;
      border-radius: 0 0 50% 50%/0 0 35% 35%;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
      flex-shrink: 0;
      background: #000;
    }

    .ob-hero img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: .85;
    }

    .ob-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to bottom, rgba(13, 148, 136, .3), rgba(13, 148, 136, .6));
    }

    .ob-badge {
      position: absolute;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%);
      background: rgba(255, 255, 255, .15);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, .3);
      border-radius: 50px;
      padding: 8px 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .ob-badge-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #fff;
    }

    .ob-badge-txt {
      font-size: 12px;
      font-weight: 700;
      color: #fff;
    }

    .ob-content {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 28px 36px;
      text-align: center;
      gap: 14px;
    }

    .ob-title {
      font-family: 'Poppins', sans-serif;
      font-size: 21px;
      font-weight: 700;
      color: var(--td);
      line-height: 1.35;
    }

    .ob-dots {
      display: flex;
      gap: 6px;
    }

    .ob-dot {
      width: 8px;
      height: 8px;
      border-radius: 4px;
      background: #b2f0e8;
      transition: all .3s;
    }

    .ob-dot.on {
      width: 24px;
      background: var(--teal);
    }

    #setupPin {
      background: #fff;
      justify-content: flex-start;
    }

    body.dark #setupPin {
      background: var(--bg-shell);
    }

    body.dark .pin-ttl {
      color: var(--td);
    }

    body.dark .pin-sb {
      color: var(--tm);
    }

    body.dark .pk {
      background: var(--pbg);
      color: var(--td);
    }

    body.dark .pk:hover {
      background: var(--tab-bg);
    }

    body.dark .pk.del {
      color: var(--teal);
    }

    body.dark .pin-d {
      border-color: var(--border-col);
    }

    .pin-hdr {
      width: 100%;
      padding: 48px 26px 0;
      flex-shrink: 0;
    }

    .pin-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 0 36px 40px;
    }

    .pin-ic {
      width: 80px;
      height: 80px;
      background: var(--tbg);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
    }

    .pin-ic svg {
      width: 40px;
      height: 40px;
    }

    .pin-ttl {
      font-family: 'Poppins', sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: var(--td);
      margin-bottom: 8px;
      text-align: center;
    }

    .pin-sb {
      font-size: 13px;
      color: var(--tm);
      margin-bottom: 32px;
      text-align: center;
    }

    .pin-dots {
      display: flex;
      gap: 14px;
      margin-bottom: 36px;
    }

    .pin-d {
      width: 17px;
      height: 17px;
      border-radius: 50%;
      border: 2px solid #d1d5db;
      background: transparent;
      transition: all .2s;
    }

    .pin-d.f {
      background: var(--teal);
      border-color: var(--teal);
    }

    .pin-d.err {
      background: var(--danger);
      border-color: var(--danger);
      animation: shake .4s ease;
    }

    .pin-pad {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 13px;
      width: 100%;
      max-width: 270px;
    }

    .pk {
      aspect-ratio: 1;
      border-radius: 50%;
      border: none;
      background: var(--tbg);
      font-family: 'Poppins', sans-serif;
      font-size: 21px;
      font-weight: 700;
      color: var(--td);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background .15s, transform .1s;
    }

    .pk:hover {
      background: #b2f0e8;
    }

    .pk:active {
      transform: scale(.92);
    }

    .pk.del {
      background: transparent;
      color: var(--teal);
      font-size: 18px;
    }

    .pk.emp {
      background: transparent;
      pointer-events: none;
    }
  </style>
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

    <div class="screen" id="splash">
      <div class="bubble" style="width:80px;height:80px;top:10%;left:5%;"></div>
      <div class="bubble" style="width:50px;height:50px;top:20%;right:8%;animation-delay:.8s;"></div>
      <div class="bubble" style="width:110px;height:110px;bottom:15%;right:-20px;animation-delay:1.4s;"></div>
      <div class="bubble" style="width:55px;height:55px;bottom:26%;left:5%;animation-delay:.4s;"></div>
      <div class="s-logo">
        <div class="s-icon">
          <svg viewBox="0 0 54 54" fill="none">
            <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white" />
            <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" />
            <rect x="20" y="34" width="14" height="12" rx="3" fill="rgba(255,255,255,.6)" />
          </svg>
        </div>
        <div class="s-name">Home<span>Ease</span></div>
      </div>
      <div class="s-dots">
        <div class="s-dot"></div>
        <div class="s-dot"></div>
        <div class="s-dot"></div>
      </div>
    </div>

    <div class="screen hidden" id="onboard">
      <div class="ob-hero" id="obHero">
        <img id="obImg" src="https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=500&q=80" alt="Service">
        <div class="ob-overlay"></div>
        <div class="ob-badge">
          <div class="ob-badge-dot"></div>
          <div class="ob-badge-txt" id="obBadge">Professional Cleaning</div>
        </div>
      </div>
      <div class="ob-content">
        <h2 class="ob-title" id="obTitle">We provide professional service at a friendly price</h2>
        <div class="ob-dots" id="obDots">
          <div class="ob-dot on"></div>
          <div class="ob-dot"></div>
          <div class="ob-dot"></div>
        </div>
        <button class="btn-p" id="obBtn" onclick="nextOb()">Next</button>
      </div>
    </div>

    <div class="screen top hidden" id="signin">
      <div class="a-hdr"><button class="bk" onclick="showScreen('onboard')"><i class="bi bi-arrow-left"></i></button>
      </div>
      <div class="a-scroll">
        <div
          style="width:90px;height:90px;background:var(--tbg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
          <svg viewBox="0 0 54 54" fill="none" width="46" height="46">
            <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="#0D9488" />
            <circle cx="34" cy="20" r="8" fill="#5EEAD4" />
          </svg>
        </div>
        <h1 class="a-title">Let's you in</h1>
        <p class="a-sub">Welcome back! Sign in to continue.</p>
        <button class="soc-btn" onclick="goPage('home.php')"><?= FB_SVG_PHP() ?> Continue with Facebook</button>
        <button class="soc-btn" onclick="goPage('home.php')"><?= GG_SVG_PHP() ?> Continue with Google</button>
        <button class="soc-btn" onclick="goPage('home.php')"><?= AP_SVG_PHP() ?> Continue with Apple</button>
        <div class="div-or">or</div>
        <button class="btn-p" onclick="showScreen('signinForm')" style="margin-bottom:12px;">Sign in with
          password</button>
        <div class="sw">Don't have an account? <a href="#" onclick="showScreen('signup')">Sign up</a></div>
      </div>
    </div>

    <div class="screen top hidden" id="signinForm">
      <div class="a-hdr"><button class="bk" onclick="showScreen('signin')"><i class="bi bi-arrow-left"></i></button>
      </div>
      <div class="a-scroll">
        <div
          style="width:90px;height:90px;background:var(--tbg);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
          <svg viewBox="0 0 32 32" fill="none" width="42" height="42">
            <rect x="6" y="14" width="20" height="14" rx="3" stroke="#0D9488" stroke-width="2" />
            <path d="M11 14v-4a5 5 0 0110 0v4" stroke="#0D9488" stroke-width="2" stroke-linecap="round" />
            <circle cx="16" cy="21" r="2" fill="#0D9488" />
          </svg>
        </div>
        <h1 class="a-title">Welcome Back</h1>
        <p class="a-sub">Sign in to your HomeEase account</p>
        <div class="fg"><label class="fl">Email</label>
          <div class="iw"><i class="bi bi-envelope-fill ico"></i><input type="email" class="fi"
              placeholder="your@email.com" onkeydown="if(event.key==='Enter')document.getElementById('si_p').focus()">
          </div>
        </div>
        <div class="fg"><label class="fl">Password</label>
          <div class="iw"><i class="bi bi-lock-fill ico"></i><input type="password" class="fi" id="si_p"
              placeholder="••••••••" onkeydown="if(event.key==='Enter')goPage('home.php')"><i class="bi bi-eye-fill eye"
              onclick="tPwd('si_p',this)"></i></div>
        </div>
        <div class="rem-row"><input type="checkbox" id="rem" checked><label for="rem">Remember me</label><a href="#"
            style="margin-left:auto;font-size:12px;color:var(--teal);font-weight:700;text-decoration:none;">Forgot?</a>
        </div>
        <button class="btn-p" onclick="goPage('home.php')">Sign In</button>
        <div class="div-or" style="margin-top:16px;">or continue with</div>
        <div class="mini-socs">
          <button class="mini-soc" onclick="goPage('home.php')"><?= FB_SVG_PHP() ?></button>
          <button class="mini-soc" onclick="goPage('home.php')"><?= GG_SVG_PHP() ?></button>
          <button class="mini-soc" onclick="goPage('home.php')"><?= AP_SVG_PHP() ?></button>
        </div>
        <div class="sw" style="margin-top:14px;">Don't have an account? <a href="#" onclick="showScreen('signup')">Sign
            up</a></div>
      </div>
    </div>

    <div class="screen top hidden" id="signup">
      <div class="a-hdr"><button class="bk" onclick="showScreen('signin')"><i class="bi bi-arrow-left"></i></button>
      </div>
      <div class="a-scroll">
        <h1 class="a-title">Create your<br>Account</h1>
        <p class="a-sub">Join thousands of happy homeowners</p>
        <div class="fg"><label class="fl">Full Name</label>
          <div class="iw"><i class="bi bi-person-fill ico"></i><input type="text" class="fi" id="su_n"
              placeholder="Juan dela Cruz" onkeydown="if(event.key==='Enter')document.getElementById('su_e').focus()">
          </div>
        </div>
        <div class="fg"><label class="fl">Email</label>
          <div class="iw"><i class="bi bi-envelope-fill ico"></i><input type="email" class="fi" id="su_e"
              placeholder="your@email.com" onkeydown="if(event.key==='Enter')document.getElementById('su_p').focus()">
          </div>
        </div>
        <div class="fg"><label class="fl">Password</label>
          <div class="iw"><i class="bi bi-lock-fill ico"></i><input type="password" class="fi" id="su_p"
              placeholder="Create a password"
              onkeydown="if(event.key==='Enter')document.getElementById('su_cp').focus()"><i class="bi bi-eye-fill eye"
              onclick="tPwd('su_p',this)"></i></div>
        </div>
        <div class="fg"><label class="fl">Confirm Password</label>
          <div class="iw"><i class="bi bi-lock-fill ico"></i><input type="password" class="fi" id="su_cp"
              placeholder="Repeat your password" onkeydown="if(event.key==='Enter')showScreen('setupPin')"><i
              class="bi bi-eye-fill eye" onclick="tPwd('su_cp',this)"></i></div>
        </div>
        <div class="rem-row"><input type="checkbox" id="terms"><label for="terms" style="font-size:11px;">I agree to the
            <a href="#" style="color:var(--teal);font-weight:700;text-decoration:none;">Terms & Privacy
              Policy</a></label></div>
        <button class="btn-p" onclick="showScreen('setupPin')">Create Account</button>
        <div class="div-or" style="margin-top:16px;">or continue with</div>
        <div class="mini-socs">
          <button class="mini-soc" onclick="showScreen('setupPin')"><?= FB_SVG_PHP() ?></button>
          <button class="mini-soc" onclick="showScreen('setupPin')"><?= GG_SVG_PHP() ?></button>
          <button class="mini-soc" onclick="showScreen('setupPin')"><?= AP_SVG_PHP() ?></button>
        </div>
        <div class="sw" style="margin-top:14px;">Already have an account? <a href="#"
            onclick="showScreen('signin')">Sign in</a></div>
      </div>
    </div>

    <div class="screen hidden" id="setupPin">
      <div class="pin-hdr"><button class="bk" onclick="showScreen('signup')"><i class="bi bi-arrow-left"></i></button>
      </div>
      <div class="pin-body">
        <div class="pin-ic">
          <svg viewBox="0 0 40 40" fill="none">
            <rect width="40" height="40" rx="20" fill="#F0FDFA" />
            <rect x="10" y="18" width="20" height="14" rx="3" fill="#0D9488" />
            <path d="M14 18v-4a6 6 0 0112 0v4" stroke="#0D9488" stroke-width="2.5" stroke-linecap="round" />
            <circle cx="20" cy="25" r="2.5" fill="white" />
          </svg>
        </div>
        <div class="pin-ttl" id="pinTtl">Set Up Your PIN</div>
        <div class="pin-sb" id="pinSb">Create a 4-digit PIN to secure your account</div>
        <div class="pin-dots" id="pinDots">
          <div class="pin-d"></div>
          <div class="pin-d"></div>
          <div class="pin-d"></div>
          <div class="pin-d"></div>
        </div>
        <div class="pin-pad">
          <button class="pk" onclick="PP(1)">1</button><button class="pk" onclick="PP(2)">2</button><button class="pk"
            onclick="PP(3)">3</button>
          <button class="pk" onclick="PP(4)">4</button><button class="pk" onclick="PP(5)">5</button><button class="pk"
            onclick="PP(6)">6</button>
          <button class="pk" onclick="PP(7)">7</button><button class="pk" onclick="PP(8)">8</button><button class="pk"
            onclick="PP(9)">9</button>
          <button class="pk emp"></button><button class="pk" onclick="PP(0)">0</button><button class="pk del"
            onclick="PD()"><i class="bi bi-backspace-fill"></i></button>
        </div>
      </div>
    </div>

  </div>

  <script src="assets/js/app.js"></script>
  <script>initTheme();</script>
  <script>
    const OB_SLIDES = [
      { img: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=500&q=80', badge: 'Professional Cleaning', title: 'We provide professional service at a friendly price', btn: 'Next' },
      { img: 'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=500&q=80', badge: 'Expert Technicians', title: 'The best results and your satisfaction is our top priority', btn: 'Next' },
      { img: 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=500&q=80', badge: 'Transform Your Home', title: "Let's make awesome changes to your home", btn: 'Get Started' },
    ];
    let curSlide = 0;

    function nextOb() {
      if (curSlide < OB_SLIDES.length - 1) { curSlide++; updOb(); }
      else showScreen('signin');
    }
    function updOb() {
      const s = OB_SLIDES[curSlide];
      const img = document.getElementById('obImg');
      img.style.opacity = '0';
      setTimeout(() => { img.src = s.img; img.style.opacity = '0.85'; }, 200);
      document.getElementById('obBadge').textContent = s.badge;
      document.getElementById('obTitle').textContent = s.title;
      document.getElementById('obBtn').textContent = s.btn;
      document.querySelectorAll('.ob-dot').forEach((d, i) => d.classList.toggle('on', i === curSlide));
    }

    let _screenCur = 'splash';
    function showScreen(id) {
      const loader = document.getElementById('ml');
      loader.classList.add('on');
      setTimeout(() => {
        const prev = document.getElementById(_screenCur);
        const next = document.getElementById(id);
        if (!next) { loader.classList.remove('on'); return; }
        prev.classList.add('out');
        setTimeout(() => { prev.classList.remove('out'); prev.classList.add('hidden'); }, 350);
        next.classList.remove('hidden');
        _screenCur = id;
        if (id === 'setupPin') { pinStep = 'set'; firstPin = ''; curPin = ''; document.getElementById('pinTtl').textContent = 'Set Up Your PIN'; document.getElementById('pinSb').textContent = 'Create a 4-digit PIN to secure your account'; updPinDots(); }
        setTimeout(() => loader.classList.remove('on'), 300);
      }, 320);
    }
    setTimeout(() => showScreen('onboard'), 2200);

    function goPage(file) {
      const loader = document.getElementById('ml');
      loader.classList.add('on');
      setTimeout(() => window.location.href = file, 320);
    }
  </script>
</body>

</html>

<?php
function FB_SVG_PHP()
{
  return '<svg viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.413c0-3.026 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.97h-1.514c-1.491 0-1.956.93-1.956 1.883v2.268h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>';
}
function GG_SVG_PHP()
{
  return '<svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>';
}
function AP_SVG_PHP()
{
  return '<svg viewBox="0 0 24 24" fill="#000"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>';
}
?>
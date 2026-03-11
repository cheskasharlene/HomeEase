<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['user_id'])) {
  header('Location: home.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no" />
  <title>HomeEase – Welcome</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700;800&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/onboarding.css">
</head>
<body>
  <div class="splash-screen">
    <div class="splash-logo">
      <svg viewBox="0 0 54 54" xmlns="http://www.w3.org/2000/svg">
        <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" />
        <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" />
      </svg>
    </div>
    <div class="splash-name">HomeEase</div>
    <div class="splash-loader"><div class="spinner"></div></div>
  </div>

  <div class="onboarding-container">
    <div class="slides">
      <div class="slide">
        <div class="slide-card">
          <h2>We provide professional service at a friendly price</h2>
          <div class="illustration">
            <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
              <rect x="10" y="20" width="100" height="80" fill="var(--teal)" rx="12" />
            </svg>
          </div>
        </div>
      </div>
      <div class="slide">
        <div class="slide-card">
          <h2>The best results and your satisfaction is our top priority</h2>
          <div class="illustration">
            <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
              <circle cx="60" cy="60" r="50" fill="var(--teal)" />
            </svg>
          </div>
        </div>
      </div>
      <div class="slide">
        <div class="slide-card">
          <h2>Let’s make awesome changes to your home</h2>
          <div class="illustration">
            <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg">
              <polygon points="60,10 90,110 30,110" fill="var(--teal)" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <div class="dots">
      <div class="dot active"></div>
      <div class="dot"></div>
      <div class="dot"></div>
    </div>

    <button id="onboardBtn">Next</button>
  </div>

  <div class="user-type-container" id="userType">
    <div class="user-type-header">
      <div class="user-type-logo">
        <svg viewBox="0 0 54 54" xmlns="http://www.w3.org/2000/svg">
          <path d="M8 28L27 10L46 28V46H34V34H20V46H8V28Z" fill="white"/>
          <circle cx="34" cy="20" r="8" fill="rgba(255,255,255,.35)" />
        </svg>
      </div>
      <div class="user-type-name">HomeEase</div>
    </div>
    <h2>Choose how you want to use HomeEase</h2>
    <button class="user-type-button homeowner" id="btnHomeowner">
      I am a Homeowner
      <span class="sub">I need a service</span>
    </button>
    <button class="user-type-button service" id="btnProvider">
      I am a Service Provider
      <span class="sub">I offer services</span>
    </button>
  </div>

  <script src="assets/js/onboarding.js"></script>
</body>
</html>
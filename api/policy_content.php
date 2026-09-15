<?php
// api/policy_content.php
// Single source of truth for HomeEase Privacy Policy & Terms of Service content
// Also handles policy acceptance submission

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the canonical Privacy Policy and Terms of Service markup
 */
function getPolicyContent()
{
    static $content = null;
    if ($content !== null) {
        return $content;
    }

    $termsHtml = <<<HTML
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">1. INTRODUCTION</div>
  <p style="margin:0;">This Terms of Service Agreement ("Agreement") governs the use of HOME EASE services. By using the platform, the User agrees to comply with all terms and conditions stated herein.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">2. DEFINITIONS</div>
  <p style="margin:0 0 8px 0;"><strong>User:</strong> Any individual or entity using HOME EASE services.</p>
  <p style="margin:0 0 8px 0;"><strong>Services:</strong> Booking of household services and GPS tracking features.</p>
  <p style="margin:0 0 8px 0;"><strong>Content:</strong> Reviews, ratings, text, images, or materials submitted by users.</p>
  <p style="margin:0 0 8px 0;"><strong>Provider:</strong> HOME EASE platform operator.</p>
  <p style="margin:0;"><strong>Agreement:</strong> This Terms of Service and its future updates.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">3. ACCEPTANCE OF TERMS</div>
  <p style="margin:0;">Users must be at least 18 years old or have guardian consent. By using the app, users agree to all terms. Continued use means acceptance of updates.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">4. USER OBLIGATIONS</div>
  <p style="margin:0;">Users must secure their account credentials and avoid prohibited activities such as fraud, illegal use, impersonation, data scraping, or system interference.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">5. INTELLECTUAL PROPERTY</div>
  <p style="margin:0;">All platform rights belong to HOME EASE. User content grants the platform a license to use and display content for service operation.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">6. DISCLAIMER</div>
  <p style="margin:0;">Services are provided "as is." HOME EASE does not guarantee uninterrupted or error-free service. Third-party links are used at user's own risk.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">7. TERMINATION</div>
  <p style="margin:0;">HOME EASE may suspend or terminate accounts violating terms without prior notice.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">8. MODIFICATIONS</div>
  <p style="margin:0;">Terms may be updated anytime. Continued use means acceptance of changes.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">9. GOVERNING LAW</div>
  <p style="margin:0;">This agreement is governed by the laws of the Philippines.</p>
</div>
<div style="margin-bottom:20px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">10. CONTACT</div>
  <p style="margin:0;">Users may contact support via the app or official channels.</p>
</div>
<div>
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:15px;">11. ACCEPTANCE</div>
  <p style="margin:0;">By clicking "I Agree," users confirm acceptance of all terms.</p>
</div>
HTML;

    $privacyHtml = <<<HTML
<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">1. Introduction</div>
  <p style="margin:0;">HomeEase respects your privacy and is committed to protecting your personal data. This Privacy Policy explains how we collect, use, store, and protect your information when you use our platform.</p>
</div>

<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">2. Information We Collect</div>

  <div style="margin-bottom:14px;">
    <div style="font-weight:700;color:#0f172a;margin-bottom:8px;font-size:14px;">2.1 Personal Information</div>
    <p style="margin:0;">We may collect the following:</p>
    <ul style="margin:8px 0 0 20px;padding:0;">
      <li style="margin-bottom:4px;">Full Name</li>
      <li style="margin-bottom:4px;">Contact Number</li>
      <li style="margin-bottom:4px;">Email Address</li>
      <li style="margin-bottom:4px;">Home Address</li>
      <li style="margin-bottom:4px;">Profile Photo (optional)</li>
    </ul>
  </div>

  <div style="margin-bottom:14px;">
    <div style="font-weight:700;color:#0f172a;margin-bottom:8px;font-size:14px;">2.2 Location Data</div>
    <p style="margin:0;">Real-time GPS location (for booking and tracking services).</p>
  </div>

  <div style="margin-bottom:14px;">
    <div style="font-weight:700;color:#0f172a;margin-bottom:8px;font-size:14px;">2.3 Account Information</div>
    <p style="margin:0;">Username and password, booking history, preferences and saved data.</p>
  </div>

  <div style="margin-bottom:14px;">
    <div style="font-weight:700;color:#0f172a;margin-bottom:8px;font-size:14px;">2.4 Payment Information</div>
    <p style="margin:0;">Payment method details (excluding sensitive banking credentials).</p>
  </div>

  <div style="margin-bottom:14px;">
    <div style="font-weight:700;color:#0f172a;margin-bottom:8px;font-size:14px;">2.5 Device Information</div>
    <p style="margin:0;">Device type, IP address, and app usage data.</p>
  </div>
</div>

<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">3. How We Use Your Information</div>
  <p style="margin:0 0 8px 0;">We use your data to:</p>
  <ul style="margin:0 0 0 20px;padding:0;">
    <li style="margin-bottom:4px;">Process and manage bookings</li>
    <li style="margin-bottom:4px;">Match users with service providers</li>
    <li style="margin-bottom:4px;">Enable real-time GPS tracking</li>
    <li style="margin-bottom:4px;">Improve app performance and user experience</li>
    <li style="margin-bottom:4px;">Send notifications and updates</li>
    <li style="margin-bottom:4px;">Prevent fraud and ensure platform security</li>
  </ul>
</div>

<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">4. Sharing of Information</div>
  <p style="margin:0 0 8px 0;">We may share your data with:</p>
  <ul style="margin:0 0 8px 20px;padding:0;">
    <li style="margin-bottom:4px;">Service providers (for booking fulfillment)</li>
    <li style="margin-bottom:4px;">Payment partners (for transaction processing)</li>
    <li style="margin-bottom:4px;">Legal authorities (if required by law)</li>
  </ul>
  <p style="margin:0;"><strong>We do NOT sell your personal data.</strong></p>
</div>

<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">5. Data Storage and Security</div>
  <p style="margin:0 0 8px 0;">We implement appropriate security measures to protect your data, including:</p>
  <ul style="margin:0 0 8px 20px;padding:0;">
    <li style="margin-bottom:4px;">Encryption</li>
    <li style="margin-bottom:4px;">Secure servers</li>
    <li style="margin-bottom:4px;">Restricted access</li>
  </ul>
  <p style="margin:0;"><em>However, no system is 100% secure.</em></p>
</div>

<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">6. Your Rights</div>
  <p style="margin:0 0 8px 0;">You have the right to:</p>
  <ul style="margin:0 0 0 20px;padding:0;">
    <li style="margin-bottom:4px;">Access your personal data</li>
    <li style="margin-bottom:4px;">Update or correct your information</li>
    <li style="margin-bottom:4px;">Request deletion of your account</li>
    <li style="margin-bottom:4px;">Withdraw consent</li>
  </ul>
</div>

<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">7. Data Retention</div>
  <p style="margin:0 0 8px 0;">We retain your data only as long as necessary for:</p>
  <ul style="margin:0 0 0 20px;padding:0;">
    <li style="margin-bottom:4px;">Service delivery</li>
    <li style="margin-bottom:4px;">Legal compliance</li>
    <li style="margin-bottom:4px;">Dispute resolution</li>
  </ul>
</div>

<div style="margin-bottom:24px;">
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">8. Changes to Policy</div>
  <p style="margin:0;">We may update this Privacy Policy anytime. Continued use of the app means you accept the changes.</p>
</div>

<div>
  <div style="font-weight:800;color:#0f172a;margin-bottom:12px;font-size:16px;">9. Contact Us</div>
  <p style="margin:0;">For concerns, contact us through the app or official support channels.</p>
</div>
HTML;

    $content = [
        'privacy_policy' => [
            'title' => 'Privacy Policy',
            'html'  => $privacyHtml
        ],
        'terms_of_service' => [
            'title' => 'Terms of Service',
            'html'  => $termsHtml
        ]
    ];

    return $content;
}

// Handle direct HTTP API calls
$isDirectApiCall = (!empty($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__));

if ($isDirectApiCall) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }

    require_once __DIR__ . '/db.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $userId = (int)($_SESSION['user_id'] ?? $input['user_id'] ?? 0);
        if ($userId <= 0) {
            respond(false, 'Unauthorized. Please log in first.');
        }

        // Update database with acceptance flag and current timestamp
        $stmt = $conn->prepare("UPDATE users SET policy_accepted = 1, policy_accepted_at = NOW() WHERE id = ?");
        if (!$stmt) {
            respond(false, 'Database error: ' . $conn->error);
        }
        $stmt->bind_param("i", $userId);
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            $_SESSION['policy_accepted'] = 1;
            $_SESSION['policy_accepted_at'] = date('Y-m-d H:i:s');
            respond(true, 'Privacy Policy and Terms of Service accepted successfully.');
        } else {
            respond(false, 'Failed to update policy acceptance status.');
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $content = getPolicyContent();
        respond(true, 'Policy content loaded.', [
            'privacy_policy' => $content['privacy_policy'],
            'terms_of_service' => $content['terms_of_service']
        ]);
    }

    respond(false, 'Invalid request method.');
}

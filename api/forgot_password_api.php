<?php
/**
 * forgot_password_api.php
 *
 * Handles two actions:
 *   verify_email  – checks that the email exists in the correct table
 *   reset_password – validates new password rules and updates the hash
 */

session_start();

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$action    = trim($_POST['action']    ?? '');
$email     = trim($_POST['email']     ?? '');
$acctType  = trim($_POST['acct_type'] ?? 'user');   // 'user' | 'provider'
$newPass   = $_POST['new_password']   ?? '';

// ── Helpers ──────────────────────────────────────────────────────────────────

function isProviderAcct(string $type): bool
{
    return $type === 'provider';
}

function validatePasswordRules(string $pass): ?string
{
    if (strlen($pass) < 8) {
        return 'Password must be at least 8 characters long.';
    }
    if (!preg_match('/[A-Z]/', $pass)) {
        return 'Password must contain at least one uppercase letter (A–Z).';
    }
    if (!preg_match('/[a-z]/', $pass)) {
        return 'Password must contain at least one lowercase letter (a–z).';
    }
    if (!preg_match('/[0-9]/', $pass)) {
        return 'Password must contain at least one number (0–9).';
    }
    return null;
}

// ── Action: verify_email ─────────────────────────────────────────────────────

if ($action === 'verify_email') {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Please enter a valid email address.');
    }

    if (isProviderAcct($acctType)) {
        $stmt = $conn->prepare(
            "SELECT provider_id FROM service_providers WHERE email = ? LIMIT 1"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE email = ? AND role != 'admin' LIMIT 1"
        );
    }

    if (!$stmt) {
        respond(false, 'Database error. Please try again.');
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    $found = $stmt->num_rows > 0;
    $stmt->close();

    if (!$found) {
        respond(false, 'No account was found with that email address.');
    }

    respond(true, 'Email verified.');
}

// ── Action: reset_password ───────────────────────────────────────────────────

if ($action === 'reset_password') {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid request.');
    }

    // Server-side password validation
    $err = validatePasswordRules($newPass);
    if ($err !== null) {
        respond(false, $err);
    }

    $hashed = password_hash($newPass, PASSWORD_BCRYPT);

    if (isProviderAcct($acctType)) {
        // Confirm the account still exists before updating
        $chk = $conn->prepare(
            "SELECT provider_id FROM service_providers WHERE email = ? LIMIT 1"
        );
        if (!$chk) { respond(false, 'Database error.'); }
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) { $chk->close(); respond(false, 'Account not found.'); }
        $chk->close();

        $stmt = $conn->prepare(
            "UPDATE service_providers SET password = ? WHERE email = ?"
        );
    } else {
        $chk = $conn->prepare(
            "SELECT id FROM users WHERE email = ? AND role != 'admin' LIMIT 1"
        );
        if (!$chk) { respond(false, 'Database error.'); }
        $chk->bind_param('s', $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) { $chk->close(); respond(false, 'Account not found.'); }
        $chk->close();

        $stmt = $conn->prepare(
            "UPDATE users SET password = ? WHERE email = ? AND role != 'admin'"
        );
    }

    if (!$stmt) {
        respond(false, 'Database error. Please try again.');
    }

    $stmt->bind_param('ss', $hashed, $email);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $stmt->close();
        respond(true, 'Password reset successfully.');
    }

    $stmt->close();
    respond(false, 'Could not update password. Please try again.');
}

respond(false, 'Unknown action.');

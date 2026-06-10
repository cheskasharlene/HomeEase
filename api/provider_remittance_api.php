<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/db.php';
ensureNormalizationSchema($conn);

if (empty($_SESSION['provider_id'])) {
    respond(false, 'Not logged in.');
}

// Retrieve provider ID (consistent with provider session setup)
$providerId = (int)$_SESSION['provider_id'];

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

// Ensure worker's remittances are calculated and synced
ensureRemittancesForProvider($conn, $providerId);

if ($method === 'GET' && $action === 'list') {
    // 1. Get current active/pending/overdue remittance
    $stmt = $conn->prepare("SELECT id, reference_no, amount_due, amount_paid, status, due_date, date_remitted, submitted_at, payment_method, receipt_path 
                            FROM remittances 
                            WHERE provider_id = ? 
                            ORDER BY due_date DESC, id DESC");
    if (!$stmt) {
        respond(false, 'Database error: ' . $conn->error);
    }
    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Map rows for consistent front-end format
    $remittances = [];
    foreach ($rows as $r) {
        $remittances[] = [
            'id' => (int)$r['id'],
            'reference_no' => $r['reference_no'],
            'amount_due' => (float)$r['amount_due'],
            'amount_paid' => (float)$r['amount_paid'],
            'status' => $r['status'],
            'due_date' => $r['due_date'],
            'date_remitted' => $r['date_remitted'],
            'submitted_at' => $r['submitted_at'],
            'payment_method' => $r['payment_method'] ?? '-',
            'receipt_path' => $r['receipt_path']
        ];
    }

    respond(true, 'Remittances retrieved.', ['remittances' => $remittances]);
}

if ($method === 'POST' && $action === 'submit_payment') {
    $remitId = (int)($_POST['remittance_id'] ?? 0);
    if ($remitId <= 0) {
        respond(false, 'Invalid remittance ID.');
    }

    // Verify remittance belongs to provider and is in a payable state
    $stmt = $conn->prepare("SELECT id, amount_due, status, due_date FROM remittances WHERE id = ? AND provider_id = ? LIMIT 1");
    $stmt->bind_param("ii", $remitId, $providerId);
    $stmt->execute();
    $remit = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$remit) {
        respond(false, 'Remittance not found.');
    }

    if ($remit['status'] === 'paid') {
        respond(false, 'This remittance has already been paid.');
    }

    // Handle receipt file upload
    if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'Receipt image upload is required.');
    }

    $uploadDir = __DIR__ . '/../uploads/remittance/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileTmp = $_FILES['receipt']['tmp_name'];
    $fileName = basename($_FILES['receipt']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($ext, $allowed)) {
        respond(false, 'Invalid file type. Only JPG, PNG, and WEBP images are allowed.');
    }

    $newFileName = 'remit_receipt_' . $remitId . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $newFileName;
    $dbPath = 'uploads/remittance/' . $newFileName;

    if (!move_uploaded_file($fileTmp, $destPath)) {
        respond(false, 'Failed to save receipt image.');
    }

    // Update remittance status to submitted
    $amountDue = (float)$remit['amount_due'];
    $updateStmt = $conn->prepare("UPDATE remittances SET status = 'submitted', amount_paid = ?, payment_method = 'GCash', receipt_path = ?, submitted_at = NOW() WHERE id = ?");
    $updateStmt->bind_param("dsi", $amountDue, $dbPath, $remitId);
    $ok = $updateStmt->execute();
    $updateStmt->close();

    if (!$ok) {
        respond(false, 'Failed to update remittance record.');
    }

    // Get provider's name
    $provRes = $conn->query("SELECT full_name FROM service_providers WHERE provider_id = $providerId");
    $provName = ($provRes && $prow = $provRes->fetch_assoc()) ? $prow['full_name'] : 'Worker';

    // Notify Admin
    $notifTitle = 'Remittance Payment Submitted';
    $notifMsg = 'Worker ' . $provName . ' has submitted a remittance payment of ₱' . number_format($amountDue, 2) . ' for due date ' . date('M d, Y', strtotime($remit['due_date'])) . '.';
    
    $notifStmt = $conn->prepare("INSERT INTO admin_notifications (type, title, message, remittance_id, provider_id, is_read, created_at) VALUES ('remittance', ?, ?, ?, ?, 0, NOW())");
    $notifStmt->bind_param("ssii", $notifTitle, $notifMsg, $remitId, $providerId);
    $notifStmt->execute();
    $notifStmt->close();

    respond(true, 'Remittance payment submitted successfully for verification.');
}

respond(false, 'Invalid action or request method.');

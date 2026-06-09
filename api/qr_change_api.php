<?php
/**
 * QR Change Request API
 * Handles GCash/Bank Transfer QR code change requests.
 *
 * Provider actions (require provider_id session):
 *   GET  ?action=my_requests           — list own requests
 *   GET  ?action=current_qr            — get current active QR paths
 *   POST ?action=submit                — submit a new request (with file upload)
 *
 * Admin actions (require admin session):
 *   GET  ?action=list                  — list all requests
 *   GET  ?action=pending_count         — count of pending requests
 *   POST ?action=approve               — approve a request
 *   POST ?action=reject                — reject a request
 */

ob_start();
ini_set('display_errors', 0);
error_reporting(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? ''));

// ── Ensure table exists ─────────────────────────────────────────────────────
ensureQrChangeRequestsTable($conn);

// ── Route: Admin endpoints ──────────────────────────────────────────────────
$isAdmin = !empty($_SESSION['admin_id']) || (
    !empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin'
);

if ($action === 'list' || $action === 'pending_count' || $action === 'approve' || $action === 'reject') {
    if (!$isAdmin) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required.']);
        exit;
    }

    if ($method === 'GET' && $action === 'pending_count') {
        $r = $conn->query("SELECT COUNT(*) FROM qr_change_requests WHERE status='pending'");
        $count = $r ? (int)$r->fetch_row()[0] : 0;
        ob_end_clean();
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    if ($method === 'GET' && $action === 'list') {
        $statusFilter = trim($_GET['status'] ?? 'all');
        $where = $statusFilter !== 'all' ? "WHERE q.status = '" . $conn->real_escape_string($statusFilter) . "'" : '';
        $sql = "SELECT q.id, q.provider_id, q.reason, q.current_qr_path, q.new_qr_path,
                       q.status, q.admin_id, q.admin_remarks, q.submitted_at, q.reviewed_at,
                       sp.full_name AS provider_name, s.name AS service_category, sp.contact_number,
                       sp.qr_gcash, sp.qr_bank
                FROM qr_change_requests q
                LEFT JOIN service_providers sp ON sp.provider_id = q.provider_id
                LEFT JOIN services s ON s.id = sp.service_id
                $where
                ORDER BY q.submitted_at DESC
                LIMIT 200";
        $res = $conn->query($sql);
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        ob_end_clean();
        echo json_encode(['success' => true, 'requests' => $rows]);
        exit;
    }

    if ($method === 'POST' && $action === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit;
        }
        $adminId = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);

        // Fetch the request
        $stmt = $conn->prepare("SELECT * FROM qr_change_requests WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$req) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit;
        }
        if ($req['status'] !== 'pending') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Request already reviewed.']);
            exit;
        }

        $providerId = (int)$req['provider_id'];
        $newQrPath = $req['new_qr_path'];

        $conn->begin_transaction();
        try {
            // Update request status
            $upd = $conn->prepare(
                "UPDATE qr_change_requests SET status='approved', admin_id=?, reviewed_at=NOW() WHERE id=?"
            );
            $upd->bind_param('ii', $adminId, $id);
            $upd->execute();
            $upd->close();

            // Determine QR type from file path or use a general approach:
            // Replace both qr_gcash and update provider_documents for gcash_qr and bank_qr
            // We store new QR path to both fields so the provider's payment receives the new QR.
            // The provider submitted one QR image; apply it based on what was their current active channel.
            // Strategy: upsert provider_documents for gcash_qr type (primary channel).
            // Also update sp.qr_gcash column as fallback.
            $conn->query(
                "UPDATE service_providers SET qr_gcash = '" . $conn->real_escape_string($newQrPath) . "' WHERE provider_id = $providerId"
            );

            // Upsert into provider_documents
            $conn->query("INSERT INTO provider_documents (provider_id, document_type, file_path, verified_status, uploaded_at)
                VALUES ($providerId, 'gcash_qr', '" . $conn->real_escape_string($newQrPath) . "', 'approved', NOW())
                ON DUPLICATE KEY UPDATE file_path = VALUES(file_path), verified_status = 'approved', uploaded_at = NOW()");

            // Notify provider
            ensureProviderNotificationsTable($conn);
            $msg = 'Your GCash/Bank Transfer QR code change request has been approved. Your new QR code is now active.';
            $conn->query("INSERT INTO provider_notifications (provider_id, title, message, icon, is_read, created_at)
                VALUES ($providerId, 'QR Change Approved', '" . $conn->real_escape_string($msg) . "', 'qr-code', 0, NOW())");

            // Audit log to admin_notifications (ensure table)
            $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL DEFAULT 'general',
                title VARCHAR(200) NOT NULL,
                message TEXT,
                reference_id INT NULL,
                provider_id INT NULL,
                report_id VARCHAR(20) NULL,
                qr_change_request_id INT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_notif_provider (provider_id),
                INDEX idx_admin_notif_report (report_id),
                INDEX idx_admin_notif_qr (qr_change_request_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $conn->commit();
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Request approved. Provider QR updated.']);
        } catch (Throwable $e) {
            $conn->rollback();
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($method === 'POST' && $action === 'reject') {
        $id = (int)($_POST['id'] ?? 0);
        $remarks = trim($_POST['remarks'] ?? '');
        if ($id <= 0) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit;
        }
        if ($remarks === '') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Rejection remarks are required.']);
            exit;
        }
        $adminId = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);

        $stmt = $conn->prepare("SELECT * FROM qr_change_requests WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $req = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$req) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Request not found.']);
            exit;
        }
        if ($req['status'] !== 'pending') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Request already reviewed.']);
            exit;
        }

        $providerId = (int)$req['provider_id'];

        $upd = $conn->prepare(
            "UPDATE qr_change_requests SET status='rejected', admin_id=?, admin_remarks=?, reviewed_at=NOW() WHERE id=?"
        );
        $upd->bind_param('isi', $adminId, $remarks, $id);
        $upd->execute();
        $upd->close();

        // Notify provider
        ensureProviderNotificationsTable($conn);
        $msg = 'Your GCash/Bank Transfer QR code change request has been rejected. Reason: ' . $remarks;
        $conn->query("INSERT INTO provider_notifications (provider_id, title, message, icon, is_read, created_at)
            VALUES ($providerId, 'QR Change Rejected', '" . $conn->real_escape_string($msg) . "', 'qr-code', 0, NOW())");

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Request rejected.']);
        exit;
    }
}

// ── Route: Provider endpoints ───────────────────────────────────────────────
if (empty($_SESSION['provider_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in as provider.']);
    exit;
}

$providerId = (int)$_SESSION['provider_id'];

// GET current QR info
if ($method === 'GET' && $action === 'current_qr') {
    $stmt = $conn->prepare(
        "SELECT sp.qr_gcash, sp.qr_bank,
                MAX(CASE WHEN pd.document_type='gcash_qr' THEN pd.file_path END) AS doc_gcash,
                MAX(CASE WHEN pd.document_type='bank_qr'  THEN pd.file_path END) AS doc_bank
         FROM service_providers sp
         LEFT JOIN provider_documents pd ON pd.provider_id = sp.provider_id
         WHERE sp.provider_id = ?
         GROUP BY sp.provider_id, sp.qr_gcash, sp.qr_bank
         LIMIT 1"
    );
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $gcash = $row['doc_gcash'] ?? $row['qr_gcash'] ?? null;
    $bank  = $row['doc_bank']  ?? $row['qr_bank']  ?? null;

    ob_end_clean();
    echo json_encode([
        'success'   => true,
        'gcash_qr'  => $gcash,
        'bank_qr'   => $bank,
        'has_gcash' => !empty($gcash),
        'has_bank'  => !empty($bank),
    ]);
    exit;
}

// GET provider's own request history
if ($method === 'GET' && $action === 'my_requests') {
    $stmt = $conn->prepare(
        "SELECT id, reason, current_qr_path, new_qr_path, status, admin_remarks, submitted_at, reviewed_at
         FROM qr_change_requests
         WHERE provider_id = ?
         ORDER BY submitted_at DESC
         LIMIT 20"
    );
    $stmt->bind_param('i', $providerId);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    ob_end_clean();
    echo json_encode(['success' => true, 'requests' => $rows]);
    exit;
}

// POST submit a new request
if ($method === 'POST' && $action === 'submit') {
    $reason = trim($_POST['reason'] ?? '');

    if ($reason === '') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Reason for change is required.']);
        exit;
    }

    // Check no existing pending request
    $chk = $conn->prepare("SELECT id FROM qr_change_requests WHERE provider_id = ? AND status = 'pending' LIMIT 1");
    $chk->bind_param('i', $providerId);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($existing) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'You already have a pending QR change request. Please wait for it to be reviewed before submitting another.']);
        exit;
    }

    // Handle file upload
    if (!isset($_FILES['new_qr']) || $_FILES['new_qr']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'New QR code image is required.']);
        exit;
    }

    $file     = $_FILES['new_qr'];
    $maxBytes = 5 * 1024 * 1024; // 5 MB
    if ($file['size'] > $maxBytes) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'File size must not exceed 5 MB.']);
        exit;
    }

    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.']);
        exit;
    }

    // Validate MIME type as additional check
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid image file. Use JPG, PNG, or WEBP.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/qr_changes/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFileName = 'qr_' . $providerId . '_' . time() . '.' . $ext;
    $newFilePath = $uploadDir . $newFileName;

    if (!move_uploaded_file($file['tmp_name'], $newFilePath)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
        exit;
    }

    $newQrStorePath = 'uploads/qr_changes/' . $newFileName;

    // Get current QR path for reference
    $r = $conn->query("SELECT qr_gcash FROM service_providers WHERE provider_id = $providerId LIMIT 1");
    $spRow = $r ? $r->fetch_assoc() : [];
    // Also check provider_documents
    $docRow = null;
    $dstmt  = $conn->prepare("SELECT file_path FROM provider_documents WHERE provider_id=? AND document_type='gcash_qr' LIMIT 1");
    $dstmt->bind_param('i', $providerId);
    $dstmt->execute();
    $docRow = $dstmt->get_result()->fetch_assoc();
    $dstmt->close();
    $currentQr = $docRow['file_path'] ?? $spRow['qr_gcash'] ?? null;

    // Insert request
    $ins = $conn->prepare(
        "INSERT INTO qr_change_requests (provider_id, reason, current_qr_path, new_qr_path, status, submitted_at)
         VALUES (?, ?, ?, ?, 'pending', NOW())"
    );
    $ins->bind_param('isss', $providerId, $reason, $currentQr, $newQrStorePath);
    $ins->execute();
    $newId = $conn->insert_id;
    $ins->close();

    // Notify provider (confirmation)
    ensureProviderNotificationsTable($conn);
    $conn->query("INSERT INTO provider_notifications (provider_id, title, message, icon, is_read, created_at)
        VALUES ($providerId, 'QR Change Request Submitted',
        'Your GCash/Bank Transfer QR code change request has been submitted and is pending admin review.',
        'qr-code', 0, NOW())");

    // Notify admin
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL DEFAULT 'general',
        title VARCHAR(200) NOT NULL,
        message TEXT,
        reference_id INT NULL,
        provider_id INT NULL,
        report_id VARCHAR(20) NULL,
        qr_change_request_id INT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_admin_notif_provider (provider_id),
        INDEX idx_admin_notif_report (report_id),
        INDEX idx_admin_notif_qr (qr_change_request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $provName = $conn->real_escape_string($_SESSION['provider_name'] ?? 'A provider');
    $conn->query("INSERT INTO admin_notifications (type, title, message, reference_id, qr_change_request_id, is_read, created_at)
        VALUES ('qr_change', 'New QR Change Request',
        '$provName submitted a GCash/Bank Transfer QR code change request.',
        $newId, $newId, 0, NOW())");

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Your request has been submitted and is pending admin review.', 'request_id' => $newId]);
    exit;
}

// Fallback
ob_end_clean();
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);

// ── Helper functions ────────────────────────────────────────────────────────

function ensureQrChangeRequestsTable($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS qr_change_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        reason TEXT NOT NULL,
        current_qr_path VARCHAR(512) NULL,
        new_qr_path VARCHAR(512) NOT NULL,
        status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        admin_id INT NULL,
        admin_remarks TEXT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at DATETIME NULL,
        INDEX idx_qr_requests_provider (provider_id),
        INDEX idx_qr_requests_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function ensureProviderNotificationsTable($conn)
{
    $conn->query("CREATE TABLE IF NOT EXISTS provider_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        title VARCHAR(120) NOT NULL,
        message TEXT,
        icon VARCHAR(32) DEFAULT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_provider_read (provider_id, is_read),
        INDEX idx_provider_created (provider_id, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

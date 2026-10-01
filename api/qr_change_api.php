<?php
















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
$action = trim((string)($_GET['action'] ?? $_POST['action'] ?? $_REQUEST['action'] ?? ''));


ensureQrChangeRequestsTable($conn);
ensureProviderColumns($conn);


$isAdmin = !empty($_SESSION['admin_id']) ||
           (!empty($_SESSION['user_id']) && (
               ($_SESSION['user_role'] ?? '') === 'admin' ||
               ($_SESSION['role'] ?? '') === 'admin' ||
               ($_SESSION['admin_role'] ?? '') === 'admin'
           )) ||
           (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') ||
           (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') ||
           (!empty($_SESSION['admin_role']) && $_SESSION['admin_role'] === 'admin') ||
           !empty($_SESSION['admin_name']);

if ($action === 'list' || $action === 'pending_count' || $action === 'approve' || $action === 'reject') {
    if (!$isAdmin) {
        ob_end_clean();
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin access required.']);
        exit;
    }

    if ($action === 'pending_count') {
        $r = $conn->query("SELECT COUNT(*) FROM qr_change_requests WHERE status='pending'");
        $count = $r ? (int)$r->fetch_row()[0] : 0;
        ob_end_clean();
        echo json_encode(['success' => true, 'count' => $count]);
        exit;
    }

    if ($action === 'list') {
        $statusFilter = trim($_GET['status'] ?? $_POST['status'] ?? 'all');
        $where = $statusFilter !== 'all' ? "WHERE q.status = '" . $conn->real_escape_string($statusFilter) . "'" : '';
        $sql = "SELECT q.id, q.provider_id, q.qr_type, q.reason, q.current_qr_path, q.new_qr_path,
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

    if ($action === 'approve') {
        if (!isset($_POST['id']) && !isset($_GET['id']) && !isset($_REQUEST['id'])) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit;
        }
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? $_REQUEST['id']);
        if ($id < 0) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit;
        }
        $adminId = (int)($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? 0);

        ensureProviderColumns($conn);

        $stmt = $conn->prepare("SELECT * FROM qr_change_requests WHERE id = ? LIMIT 1");
        if (!$stmt) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Query error: ' . $conn->error]);
            exit;
        }
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
        $newQrPath  = $req['new_qr_path'];
        $qrType     = strtolower(trim((string)($req['qr_type'] ?? 'gcash')));
        if ($qrType !== 'bank') {
            $qrType = 'gcash';
        }

        $conn->begin_transaction();
        try {
            $upd = $conn->prepare("UPDATE qr_change_requests SET status='approved', admin_id=?, reviewed_at=NOW() WHERE id=?");
            if (!$upd) {
                throw new Exception("Update error: " . $conn->error);
            }
            $upd->bind_param('ii', $adminId, $id);
            if (!$upd->execute()) {
                throw new Exception("Execution error: " . $upd->error);
            }
            $upd->close();

            $escapedPath = $conn->real_escape_string($newQrPath);

            if ($qrType === 'bank') {
                @$conn->query("UPDATE service_providers SET qr_bank = '$escapedPath' WHERE provider_id = $providerId");
                @$conn->query("UPDATE service_providers SET bank_qr = '$escapedPath' WHERE provider_id = $providerId");

                @$conn->query("INSERT INTO provider_documents (provider_id, document_type, file_path, verified_status, uploaded_at)
                    VALUES ($providerId, 'bank_qr', '$escapedPath', 'approved', NOW())
                    ON DUPLICATE KEY UPDATE file_path = '$escapedPath', verified_status = 'approved', uploaded_at = NOW()");

                $notifMsg = 'Your Bank Transfer QR code change request has been approved. Your new Bank QR code is now active.';
            } else {
                @$conn->query("UPDATE service_providers SET qr_gcash = '$escapedPath' WHERE provider_id = $providerId");
                @$conn->query("UPDATE service_providers SET gcash_qr = '$escapedPath' WHERE provider_id = $providerId");

                @$conn->query("INSERT INTO provider_documents (provider_id, document_type, file_path, verified_status, uploaded_at)
                    VALUES ($providerId, 'gcash_qr', '$escapedPath', 'approved', NOW())
                    ON DUPLICATE KEY UPDATE file_path = '$escapedPath', verified_status = 'approved', uploaded_at = NOW()");

                $notifMsg = 'Your GCash QR code change request has been approved. Your new GCash QR code is now active.';
            }

            @$conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
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

            if (function_exists('sendProviderNotification')) {
                sendProviderNotification($conn, $providerId, 'qr_change_approved', 'QR Change Approved', $notifMsg, 'bi-qr-code-scan', $id);
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Request approved. Provider QR updated.']);
        } catch (Throwable $e) {
            $conn->rollback();
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'reject') {
        if (!isset($_POST['id']) && !isset($_GET['id']) && !isset($_REQUEST['id'])) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid request ID.']);
            exit;
        }
        $id = (int)($_POST['id'] ?? $_GET['id'] ?? $_REQUEST['id']);
        $remarks = trim((string)($_POST['remarks'] ?? $_GET['remarks'] ?? $_REQUEST['remarks'] ?? ''));
        if ($id < 0) {
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

        sendProviderNotification($conn, $providerId, 'qr_change_rejected', 'QR Change Request Rejected', 'Your QR code change request was rejected. Reason: ' . $remarks, 'bi-x-circle', $id);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Request rejected.']);
        exit;
    }
}


if (empty($_SESSION['provider_id'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not logged in as provider.']);
    exit;
}

$providerId = (int)$_SESSION['provider_id'];


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


if ($method === 'GET' && $action === 'my_requests') {
    $stmt = $conn->prepare(
        "SELECT id, qr_type, reason, current_qr_path, new_qr_path, status, admin_remarks, submitted_at, reviewed_at
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


if ($method === 'POST' && $action === 'submit') {
    $qrType = strtolower(trim((string)($_POST['qr_type'] ?? 'gcash')));
    if (!in_array($qrType, ['gcash', 'bank'], true)) {
        $qrType = 'gcash';
    }

    $reason = trim($_POST['reason'] ?? '');

    if ($reason === '') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Reason for change is required.']);
        exit;
    }

    
    $chk = $conn->prepare("SELECT id FROM qr_change_requests WHERE provider_id = ? AND qr_type = ? AND status = 'pending' LIMIT 1");
    $chk->bind_param('is', $providerId, $qrType);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($existing) {
        $qrTypeName = $qrType === 'bank' ? 'Bank Transfer' : 'GCash';
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => "You already have a pending $qrTypeName QR change request. Please wait for it to be reviewed before submitting another."]);
        exit;
    }

    
    if (!isset($_FILES['new_qr']) || $_FILES['new_qr']['error'] !== UPLOAD_ERR_OK) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'New QR code image is required.']);
        exit;
    }

    $file     = $_FILES['new_qr'];
    $maxBytes = 5 * 1024 * 1024; 
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

    
    $docType = $qrType === 'bank' ? 'bank_qr' : 'gcash_qr';
    $spCol   = $qrType === 'bank' ? 'qr_bank' : 'qr_gcash';

    $r = $conn->query("SELECT $spCol FROM service_providers WHERE provider_id = $providerId LIMIT 1");
    $spRow = $r ? $r->fetch_assoc() : [];
    
    $dstmt  = $conn->prepare("SELECT file_path FROM provider_documents WHERE provider_id=? AND document_type=? LIMIT 1");
    $dstmt->bind_param('is', $providerId, $docType);
    $dstmt->execute();
    $docRow = $dstmt->get_result()->fetch_assoc();
    $dstmt->close();
    $currentQr = $docRow['file_path'] ?? $spRow[$spCol] ?? null;

    
    $ins = $conn->prepare(
        "INSERT INTO qr_change_requests (provider_id, qr_type, reason, current_qr_path, new_qr_path, status, submitted_at)
         VALUES (?, ?, ?, ?, ?, 'pending', NOW())"
    );
    $ins->bind_param('issss', $providerId, $qrType, $reason, $currentQr, $newQrStorePath);
    $ins->execute();
    $newId = $conn->insert_id;
    $ins->close();

    
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
    $qrLabel  = $qrType === 'bank' ? 'Bank Transfer' : 'GCash';
    $conn->query("INSERT INTO admin_notifications (type, title, message, reference_id, qr_change_request_id, is_read, created_at)
        VALUES ('qr_change', 'New QR Change Request',
        '$provName submitted a $qrLabel QR code change request.',
        $newId, $newId, 0, NOW())");

    sendProviderNotification($conn, $providerId, 'qr_change_submitted', 'QR Change Request Submitted', "Your $qrLabel QR code change request has been submitted and is pending admin review.", 'bi-qr-code-scan', $newId);

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => 'Your request has been submitted and is pending admin review.', 'request_id' => $newId]);
    exit;
}


ob_end_clean();
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid action.']);



function ensureQrChangeRequestsTable($conn)
{
    if (!$conn || !($conn instanceof mysqli)) return;

    @$conn->query("CREATE TABLE IF NOT EXISTS qr_change_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        provider_id INT NOT NULL,
        qr_type VARCHAR(20) NOT NULL DEFAULT 'gcash',
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

    $check = $conn->query("SHOW COLUMNS FROM qr_change_requests LIKE 'qr_type'");
    if ($check && $check->num_rows === 0) {
        @$conn->query("ALTER TABLE qr_change_requests ADD COLUMN qr_type VARCHAR(20) NOT NULL DEFAULT 'gcash' AFTER provider_id");
    }

    
    $zeroRes = @$conn->query("SELECT COUNT(*) FROM qr_change_requests WHERE id = 0");
    if ($zeroRes) {
        $zeroCount = (int)($zeroRes->fetch_row()[0] ?? 0);
        if ($zeroCount > 0) {
            $maxRes = $conn->query("SELECT COALESCE(MAX(id), 0) FROM qr_change_requests WHERE id > 0");
            $maxId = $maxRes ? (int)($maxRes->fetch_row()[0] ?? 0) : 0;
            $rowsRes = $conn->query("SELECT provider_id, submitted_at FROM qr_change_requests WHERE id = 0");
            if ($rowsRes) {
                while ($r = $rowsRes->fetch_assoc()) {
                    $maxId++;
                    $pid = (int)$r['provider_id'];
                    $sub = $conn->real_escape_string($r['submitted_at'] ?? '');
                    @$conn->query("UPDATE qr_change_requests SET id = $maxId WHERE id = 0 AND provider_id = $pid LIMIT 1");
                }
            }
        }
    }

    
    $pkCheck = @$conn->query("SHOW COLUMNS FROM qr_change_requests WHERE Field = 'id'");
    if ($pkCheck && ($col = $pkCheck->fetch_assoc())) {
        $extra = strtolower((string)($col['Extra'] ?? ''));
        $key   = strtolower((string)($col['Key'] ?? ''));
        if (strpos($extra, 'auto_increment') === false || strpos($key, 'pri') === false) {
            @$conn->query("ALTER TABLE qr_change_requests MODIFY COLUMN id INT AUTO_INCREMENT PRIMARY KEY");
        }
    }
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

function ensureProviderColumns($conn)
{
    if (!$conn || !($conn instanceof mysqli)) return;
    $res = $conn->query("SHOW COLUMNS FROM service_providers");
    $cols = [];
    if ($res) {
        while ($c = $res->fetch_assoc()) {
            $cols[] = $c['Field'];
        }
    }
    if (!in_array('qr_gcash', $cols, true)) {
        @$conn->query("ALTER TABLE service_providers ADD COLUMN qr_gcash VARCHAR(500)");
    }
    if (!in_array('qr_bank', $cols, true)) {
        @$conn->query("ALTER TABLE service_providers ADD COLUMN qr_bank VARCHAR(500)");
    }
    if (!in_array('gcash_qr', $cols, true)) {
        @$conn->query("ALTER TABLE service_providers ADD COLUMN gcash_qr VARCHAR(500)");
    }
    if (!in_array('bank_qr', $cols, true)) {
        @$conn->query("ALTER TABLE service_providers ADD COLUMN bank_qr VARCHAR(500)");
    }
}

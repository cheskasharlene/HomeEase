<?php
/**
 * Admin Verification Documents API
 * Manages document verification and approval for service providers
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
ensureNormalizationSchema($conn);

// Check if user is admin
if (empty($_SESSION['admin_id']) && empty($_SESSION['is_admin'])) {
    http_response_code(401);
    respond(false, 'Unauthorized. Admin access required.');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

/**
 * GET: Retrieve all pending verification documents
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'pending_verifications') {
    $stmt = $conn->prepare(
        "SELECT 
            sp.provider_id,
            sp.full_name,
            sp.service_category,
            sp.contact_number,
            sp.email,
            sp.verification_status,
            pd.document_type,
            pd.file_path,
            pd.verified_status
        FROM service_providers sp
        LEFT JOIN provider_documents pd ON pd.provider_id = sp.provider_id
        WHERE sp.verification_status IN ('submitted', 'partial')
        ORDER BY sp.provider_id DESC
        LIMIT 500"
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $providers = [];
    
    while ($row = $result->fetch_assoc()) {
        $pid = (int)($row['provider_id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }

        if (!isset($providers[$pid])) {
            $providers[$pid] = [
                'id' => $pid,
                'name' => $row['full_name'],
                'service_category' => $row['service_category'],
                'contact_number' => $row['contact_number'],
                'email' => $row['email'],
                'documents' => []
            ];
        }

        if (!empty($row['document_type']) && !empty($row['file_path'])) {
            $providers[$pid]['documents'][] = [
                'type' => $row['document_type'],
                'file_path' => $row['file_path'],
                'verified_status' => $row['verified_status'] ?: 'submitted',
                'label' => ucwords(str_replace('_', ' ', (string)$row['document_type']))
            ];
        }
    }
    $stmt->close();

    respond(true, '', ['providers' => array_values($providers)]);
}

/**
 * GET: Retrieve documents for a specific provider
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'provider_documents') {
    $provider_id = (int)($_GET['provider_id'] ?? 0);
    
    if (!$provider_id) {
        respond(false, 'Provider ID required');
    }

    $stmt = $conn->prepare(
        "SELECT document_type, file_path, verified_status, uploaded_at, verified_at, verification_notes
         FROM provider_documents
         WHERE provider_id = ?"
    );
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $existsStmt = $conn->prepare("SELECT provider_id FROM service_providers WHERE provider_id = ? LIMIT 1");
    $existsStmt->bind_param('i', $provider_id);
    $existsStmt->execute();
    $providerExists = $existsStmt->get_result()->fetch_assoc();
    $existsStmt->close();
    if (!$providerExists) {
        respond(false, 'Provider not found');
    }

    $documents = [];
    foreach ($result as $doc) {
        $dtype = (string)($doc['document_type'] ?? '');
        if ($dtype === '') {
            continue;
        }
        if (!isset($documents[$dtype])) {
            $documents[$dtype] = [];
        }
        $documents[$dtype][] = [
            'file_path' => $doc['file_path'],
            'type' => $dtype,
            'label' => ucwords(str_replace('_', ' ', $dtype)),
            'verified_status' => $doc['verified_status'],
            'uploaded_at' => $doc['uploaded_at'],
            'verified_at' => $doc['verified_at'],
            'verification_notes' => $doc['verification_notes']
        ];
    }

    respond(true, '', ['documents' => $documents]);
}

/**
 * POST: Approve a document (document type for provider)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve_document') {
    $provider_id = (int)($_POST['provider_id'] ?? 0);
    $doc_type = $_POST['doc_type'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (!$provider_id || !$doc_type) {
        respond(false, 'Provider ID and document type required');
    }

    $normalizedType = ($doc_type === 'selfie') ? 'selfie_verification' : $doc_type;
    $stmt = $conn->prepare(
        "UPDATE provider_documents
         SET verified_status = 'approved', verified_at = NOW(), verification_notes = ?
         WHERE provider_id = ? AND document_type = ?"
    );
    $stmt->bind_param('sis', $notes, $provider_id, $normalizedType);
    $stmt->execute();
    $stmt->close();

    $statusStmt = $conn->prepare("UPDATE service_providers SET verification_status = 'approved', verification_approved_at = NOW(), is_verified = 1 WHERE provider_id = ?");
    $statusStmt->bind_param('i', $provider_id);
    $statusStmt->execute();
    $statusStmt->close();

    respond(true, 'Document approved');
}

/**
 * POST: Reject a document (clear the file path for a document type)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reject_document') {
    $provider_id = (int)($_POST['provider_id'] ?? 0);
    $doc_type = $_POST['doc_type'] ?? '';
    $reason = trim($_POST['reason'] ?? 'Document does not meet requirements');

    if (!$provider_id || !$doc_type) {
        respond(false, 'Provider ID and document type required');
    }

    // Map document type to column
    $column_map = [
        'valid_id' => 'valid_id',
        'barangay_clearance' => 'barangay_clearance',
        'selfie' => 'selfie_verification',
        'proof_of_address' => 'proof_of_address',
        'tools_kits' => 'tools_&_kits',
        'gcash_qr' => 'qr_gcash',
        'bank_qr' => 'qr_bank'
    ];

    $db_column = $column_map[$doc_type] ?? null;
    if (!$db_column) {
        respond(false, 'Invalid document type');
    }

    // Get current file path
    $get_stmt = $conn->prepare("SELECT `" . $db_column . "` FROM service_providers WHERE provider_id = ?");
    $get_stmt->bind_param('i', $provider_id);
    $get_stmt->execute();
    $doc_result = $get_stmt->get_result()->fetch_assoc();
    $get_stmt->close();

    if (!$doc_result) {
        respond(false, 'Provider not found');
    }

    $file_path = $doc_result[$db_column];
    
    // Delete file
    if ($file_path) {
        $file_full_path = __DIR__ . '/../' . $file_path;
        if (file_exists($file_full_path)) {
            unlink($file_full_path);
        }
    }

    // Clear the document from database
    $del_stmt = $conn->prepare("UPDATE service_providers SET `" . $db_column . "` = NULL WHERE provider_id = ?");
    $del_stmt->bind_param('i', $provider_id);
    $del_stmt->execute();
    $del_stmt->close();

    // Update normalized document status to rejected
    $normalizedType = ($doc_type === 'selfie') ? 'selfie_verification' : $doc_type;
    $docUpdate = $conn->prepare("UPDATE provider_documents SET verified_status='rejected', verified_at=NOW(), verification_notes=? WHERE provider_id=? AND document_type=?");
    if ($docUpdate) {
        $docUpdate->bind_param('sis', $reason, $provider_id, $normalizedType);
        $docUpdate->execute();
        $docUpdate->close();
    }

    // Update status to rejected
    $status_stmt = $conn->prepare("UPDATE service_providers SET verification_status = 'rejected' WHERE provider_id = ?");
    $status_stmt->bind_param('i', $provider_id);
    $status_stmt->execute();
    $status_stmt->close();

    // Add notification
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL DEFAULT 'general',
        title VARCHAR(200) NOT NULL,
        message TEXT,
        reference_id INT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $notif_title = 'Document Rejected';
    $notif_msg = 'One of your verification documents was rejected: ' . $reason;

    $notif_stmt = $conn->prepare(
        "INSERT INTO admin_notifications (type, title, message, reference_id, created_at) 
         VALUES ('verification_rejected', ?, ?, ?, NOW())"
    );
    $notif_stmt->bind_param('ssi', $notif_title, $notif_msg, $provider_id);
    $notif_stmt->execute();
    $notif_stmt->close();

    respond(true, 'Document rejected and notification sent to provider');
}

/**
 * POST: Approve all documents for a provider
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve_provider') {
    $provider_id = (int)($_POST['provider_id'] ?? 0);

    if (!$provider_id) {
        respond(false, 'Provider ID required');
    }

    // Update service_providers verification status
    $update_stmt = $conn->prepare(
        "UPDATE service_providers 
         SET is_verified = 1, verification_status = 'approved', verification_approved_at = NOW()
         WHERE provider_id = ?"
    );
    $update_stmt->bind_param('i', $provider_id);
    
    if (!$update_stmt->execute()) {
        respond(false, 'Failed to approve documents');
    }
    $update_stmt->close();

    respond(true, 'Provider verified successfully');
}

/**
 * POST: Reject verification for a provider
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reject_provider') {
    $provider_id = (int)($_POST['provider_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'No reason provided');

    if (!$provider_id) {
        respond(false, 'Provider ID required');
    }

    // Update service_providers verification status to rejected
    $update_stmt = $conn->prepare(
        "UPDATE service_providers 
         SET verification_status = 'rejected', rejection_reason = ?
         WHERE provider_id = ?"
    );
    $update_stmt->bind_param('si', $reason, $provider_id);
    
    if (!$update_stmt->execute()) {
        respond(false, 'Failed to reject documents');
    }
    $update_stmt->close();

    respond(true, 'Provider verification rejected');
}

/**
 * GET: Get verification statistics
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'statistics') {
    $pending_stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT provider_id) as count FROM service_providers WHERE verification_status IN ('submitted', 'partial')"
    );
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result()->fetch_assoc();
    $pending_stmt->close();

    $approved_stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT provider_id) as count FROM service_providers WHERE verification_status = 'approved'"
    );
    $approved_stmt->execute();
    $approved_result = $approved_stmt->get_result()->fetch_assoc();
    $approved_stmt->close();

    $total_docs_stmt = $conn->prepare(
        "SELECT COUNT(*) as count FROM provider_documents"
    );
    $total_docs_stmt->execute();
    $total_docs_result = $total_docs_stmt->get_result()->fetch_assoc();
    $total_docs_stmt->close();

    respond(true, '', [
        'pending_providers' => $pending_result['count'] ?? 0,
        'approved_providers' => $approved_result['count'] ?? 0,
        'total_documents' => $total_docs_result['count'] ?? 0
    ]);
}

respond(false, 'Invalid request');

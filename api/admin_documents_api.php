<?php
/**
 * Admin Verification Documents API
 * Manages document verification and approval for service providers
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

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
            sp.valid_id,
            sp.barangay_clearance,
            sp.selfie_verification,
            sp.proof_of_address,
            sp.`tools_&_kits`,
            COALESCE(sp.qr_gcash, sp.gcash_qr) AS gcash_qr,
            COALESCE(sp.qr_bank, sp.bank_qr) AS bank_qr,
            sp.verification_status
        FROM service_providers sp
        WHERE sp.verification_status IN ('submitted', 'partial')
        ORDER BY sp.provider_id DESC
        LIMIT 100"
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $providers = [];
    
    while ($row = $result->fetch_assoc()) {
        // Get all documents for this provider
        $documents = [];
        if ($row['valid_id']) {
            $documents[] = [
                'type' => 'valid_id',
                'file_path' => $row['valid_id'],
                'label' => 'Valid Government ID'
            ];
        }
        if ($row['barangay_clearance']) {
            $documents[] = [
                'type' => 'barangay_clearance',
                'file_path' => $row['barangay_clearance'],
                'label' => 'Barangay Clearance'
            ];
        }
        if ($row['selfie_verification']) {
            $documents[] = [
                'type' => 'selfie',
                'file_path' => $row['selfie_verification'],
                'label' => 'Selfie Verification'
            ];
        }
        if ($row['proof_of_address']) {
            $documents[] = [
                'type' => 'proof_of_address',
                'file_path' => $row['proof_of_address'],
                'label' => 'Proof of Address'
            ];
        }
        if ($row['tools_&_kits']) {
            $documents[] = [
                'type' => 'tools_kits',
                'file_path' => $row['tools_&_kits'],
                'label' => 'Tools & Kits'
            ];
        }
        if ($row['gcash_qr']) {
            $documents[] = [
                'type' => 'gcash_qr',
                'file_path' => $row['gcash_qr'],
                'label' => 'GCash QR Code'
            ];
        }
        if ($row['bank_qr']) {
            $documents[] = [
                'type' => 'bank_qr',
                'file_path' => $row['bank_qr'],
                'label' => 'Bank QR Code'
            ];
        }
        
        if (!empty($documents)) {
            $providers[$row['provider_id']] = [
                'id' => $row['provider_id'],
                'name' => $row['full_name'],
                'service_category' => $row['service_category'],
                'contact_number' => $row['contact_number'],
                'email' => $row['email'],
                'documents' => $documents
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
        "SELECT 
            valid_id,
            barangay_clearance,
            selfie_verification,
            proof_of_address,
            `tools_&_kits`,
            COALESCE(qr_gcash, gcash_qr) AS gcash_qr,
            COALESCE(qr_bank, bank_qr) AS bank_qr,
            verification_status
        FROM service_providers
        WHERE provider_id = ?"
    );
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$result) {
        respond(false, 'Provider not found');
    }

    $documents = [];
    if ($result['valid_id']) {
        $documents['valid_id'][] = [
            'file_path' => $result['valid_id'],
            'type' => 'valid_id',
            'label' => 'Valid Government ID'
        ];
    }
    if ($result['barangay_clearance']) {
        $documents['barangay_clearance'][] = [
            'file_path' => $result['barangay_clearance'],
            'type' => 'barangay_clearance',
            'label' => 'Barangay Clearance'
        ];
    }
    if ($result['selfie_verification']) {
        $documents['selfie'][] = [
            'file_path' => $result['selfie_verification'],
            'type' => 'selfie',
            'label' => 'Selfie Verification'
        ];
    }
    if ($result['proof_of_address']) {
        $documents['proof_of_address'][] = [
            'file_path' => $result['proof_of_address'],
            'type' => 'proof_of_address',
            'label' => 'Proof of Address'
        ];
    }
    if ($result['tools_&_kits']) {
        $documents['tools_kits'][] = [
            'file_path' => $result['tools_&_kits'],
            'type' => 'tools_kits',
            'label' => 'Tools & Kits'
        ];
    }
    if ($result['gcash_qr']) {
        $documents['gcash_qr'][] = [
            'file_path' => $result['gcash_qr'],
            'type' => 'gcash_qr',
            'label' => 'GCash QR Code'
        ];
    }
    if ($result['bank_qr']) {
        $documents['bank_qr'][] = [
            'file_path' => $result['bank_qr'],
            'type' => 'bank_qr',
            'label' => 'Bank QR Code'
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

    // Document has been approved - update provider status if all required docs are now approved
    $stmt = $conn->prepare(
        "UPDATE service_providers 
         SET verification_status = 'approved'
         WHERE provider_id = ? AND verification_status IN ('submitted', 'partial')"
    );
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $stmt->close();

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
        'tools_kits' => 'tools_&_kits'
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
        "SELECT COUNT(*) as count FROM service_providers WHERE (valid_id IS NOT NULL OR barangay_clearance IS NOT NULL OR selfie_verification IS NOT NULL OR proof_of_address IS NOT NULL OR `tools_&_kits` IS NOT NULL OR COALESCE(qr_gcash, gcash_qr) IS NOT NULL OR COALESCE(qr_bank, bank_qr) IS NOT NULL)"
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

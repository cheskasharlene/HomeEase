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
            pvi.id as doc_id,
            pvi.provider_id,
            pvi.image_type,
            pvi.file_path,
            pvi.uploaded_at,
            pvi.is_approved,
            sp.full_name,
            sp.service_category,
            sp.contact_number,
            sp.email,
            COUNT(*) OVER (PARTITION BY pvi.provider_id) as provider_doc_count
        FROM provider_verification_images pvi
        LEFT JOIN service_providers sp ON pvi.provider_id = sp.provider_id
        WHERE pvi.is_approved = 0
        ORDER BY pvi.uploaded_at DESC
        LIMIT 100"
    );
    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    $providers = [];
    
    while ($row = $result->fetch_assoc()) {
        $doc = [
            'id' => $row['doc_id'],
            'provider_id' => $row['provider_id'],
            'type' => $row['image_type'],
            'file_path' => $row['file_path'],
            'uploaded_at' => $row['uploaded_at'],
            'is_approved' => (bool)$row['is_approved']
        ];
        
        if (!isset($providers[$row['provider_id']])) {
            $providers[$row['provider_id']] = [
                'id' => $row['provider_id'],
                'name' => $row['full_name'],
                'service_category' => $row['service_category'],
                'contact_number' => $row['contact_number'],
                'email' => $row['email'],
                'documents' => []
            ];
        }
        
        $providers[$row['provider_id']]['documents'][] = $doc;
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
            id,
            image_type,
            file_path,
            original_filename,
            file_size,
            mime_type,
            uploaded_at,
            verified_at,
            verification_notes,
            is_approved
        FROM provider_verification_images
        WHERE provider_id = ?
        ORDER BY image_type ASC, uploaded_at DESC"
    );
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $documents = [];
    while ($row = $result->fetch_assoc()) {
        $documents[$row['image_type']][] = [
            'id' => $row['id'],
            'file_path' => $row['file_path'],
            'original_filename' => $row['original_filename'],
            'file_size' => $row['file_size'],
            'mime_type' => $row['mime_type'],
            'uploaded_at' => $row['uploaded_at'],
            'verified_at' => $row['verified_at'],
            'verification_notes' => $row['verification_notes'],
            'is_approved' => (bool)$row['is_approved']
        ];
    }
    $stmt->close();

    respond(true, '', ['documents' => $documents]);
}

/**
 * POST: Approve a document
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'approve_document') {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if (!$doc_id) {
        respond(false, 'Document ID required');
    }

    $stmt = $conn->prepare(
        "UPDATE provider_verification_images 
         SET is_approved = 1, verified_at = NOW(), verification_notes = ?
         WHERE id = ?"
    );
    $stmt->bind_param('si', $notes, $doc_id);
    
    if (!$stmt->execute()) {
        respond(false, 'Failed to approve document');
    }
    $stmt->close();

    respond(true, 'Document approved');
}

/**
 * POST: Reject a document
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reject_document') {
    $doc_id = (int)($_POST['doc_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'Document does not meet requirements');

    if (!$doc_id) {
        respond(false, 'Document ID required');
    }

    // Get document info before deletion
    $get_stmt = $conn->prepare("SELECT file_path, provider_id FROM provider_verification_images WHERE id = ?");
    $get_stmt->bind_param('i', $doc_id);
    $get_stmt->execute();
    $doc_result = $get_stmt->get_result()->fetch_assoc();
    $get_stmt->close();

    if (!$doc_result) {
        respond(false, 'Document not found');
    }

    // Delete file
    $file_path = __DIR__ . '/../' . $doc_result['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete from database
    $del_stmt = $conn->prepare("DELETE FROM provider_verification_images WHERE id = ?");
    $del_stmt->bind_param('i', $doc_id);
    $del_stmt->execute();
    $del_stmt->close();

    // Add notification for provider
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
    $provider_id = $doc_result['provider_id'];

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

    $stmt = $conn->prepare(
        "UPDATE provider_verification_images 
         SET is_approved = 1, verified_at = NOW()
         WHERE provider_id = ?"
    );
    $stmt->bind_param('i', $provider_id);
    
    if (!$stmt->execute()) {
        respond(false, 'Failed to approve documents');
    }
    $stmt->close();

    // Update service_providers verification status
    $update_stmt = $conn->prepare(
        "UPDATE service_providers 
         SET is_verified = 1, verification_status = 'approved', verification_approved_at = NOW()
         WHERE provider_id = ?"
    );
    $update_stmt->bind_param('i', $provider_id);
    $update_stmt->execute();
    $update_stmt->close();

    respond(true, 'Provider verified successfully');
}

/**
 * GET: Get verification statistics
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'statistics') {
    $pending_stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT provider_id) as count FROM provider_verification_images WHERE is_approved = 0"
    );
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result()->fetch_assoc();
    $pending_stmt->close();

    $approved_stmt = $conn->prepare(
        "SELECT COUNT(DISTINCT provider_id) as count FROM provider_verification_images WHERE is_approved = 1"
    );
    $approved_stmt->execute();
    $approved_result = $approved_stmt->get_result()->fetch_assoc();
    $approved_stmt->close();

    $total_docs_stmt = $conn->prepare(
        "SELECT COUNT(*) as count FROM provider_verification_images"
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

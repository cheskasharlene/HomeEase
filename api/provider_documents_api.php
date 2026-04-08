<?php
/**
 * Provider Documents Upload API
 * Handles document submission, validation, storage, and verification
 */

session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (empty($_SESSION['provider_id'])) {
    http_response_code(401);
    respond(false, 'Unauthorized. Please log in as a provider.');
}

$provider_id = (int)$_SESSION['provider_id'];
$action = $_POST['action'] ?? '';

/**
 * Document type mapping with folder paths
 */
$DOCUMENT_TYPES = [
    'valid_id' => [
        'folder' => 'id',
        'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
        'max_size' => 5242880, // 5MB
        'label' => 'Valid Government ID'
    ],
    'barangay_clearance' => [
        'folder' => 'brgy',
        'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
        'max_size' => 5242880, // 5MB
        'label' => 'Barangay Clearance'
    ],
    'selfie' => [
        'folder' => 'selfie',
        'allowed_types' => ['image/jpeg', 'image/png'],
        'max_size' => 3145728, // 3MB
        'label' => 'Selfie (Identity Confirmation)'
    ],
    'proof_of_address' => [
        'folder' => 'address',
        'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
        'max_size' => 5242880, // 5MB
        'label' => 'Proof of Address'
    ],
    'tools_kits' => [
        'folder' => 'tools',
        'allowed_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'max_size' => 5242880, // 5MB
        'label' => 'Tools & Kits'
    ]
];

/**
 * Initialize database - ensure verification columns exist in service_providers
 */
function initializeTables($conn) {
    // Add verification fields to service_providers if needed
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM service_providers");
    if ($result) {
        while ($col = $result->fetch_assoc()) {
            $columns[] = $col['Field'];
        }
    }

    // Add verification status fields
    if (!in_array('verification_status', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN verification_status VARCHAR(50) DEFAULT 'not_submitted'");
    }
    if (!in_array('verification_submitted_at', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN verification_submitted_at TIMESTAMP NULL");
    }
    if (!in_array('verification_approved_at', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN verification_approved_at TIMESTAMP NULL");
    }

    // Add document columns if they don't exist
    if (!in_array('valid_id', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN valid_id VARCHAR(500)");
    }
    if (!in_array('selfie_verification', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN selfie_verification VARCHAR(500)");
    }
    if (!in_array('proof_of_address', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN proof_of_address VARCHAR(500)");
    }
    if (!in_array('barangay_clearance', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN barangay_clearance VARCHAR(500)");
    }
    if (!in_array('tools_&_kits', $columns)) {
        $conn->query("ALTER TABLE service_providers ADD COLUMN `tools_&_kits` VARCHAR(500)");
    }
}

/**
 * Ensure upload directories exist
 */
function ensureUploadDirectories() {
    $base_dir = __DIR__ . '/../assets/images/registration';
    $subdirs = ['id', 'brgy', 'selfie', 'address', 'tools'];
    
    if (!is_dir($base_dir)) {
        mkdir($base_dir, 0755, true);
    }
    
    foreach ($subdirs as $subdir) {
        $dir = $base_dir . '/' . $subdir;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Create .htaccess to prevent script execution in upload folders
        $htaccess = $dir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "deny from all\n");
        }
    }
}

/**
 * Validate file upload
 */
function validateFile($file, $document_type, $DOCUMENT_TYPES) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'valid' => false,
            'error' => 'File upload error: ' . ($file['error'] ?? 'Unknown error')
        ];
    }

    $doc_config = $DOCUMENT_TYPES[$document_type] ?? null;
    if (!$doc_config) {
        return ['valid' => false, 'error' => 'Invalid document type'];
    }

    // Check file size
    if ($file['size'] > $doc_config['max_size']) {
        return [
            'valid' => false,
            'error' => $doc_config['label'] . ' exceeds maximum size of ' . 
                      intval($doc_config['max_size'] / 1048576) . 'MB'
        ];
    }

    // Check MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, $doc_config['allowed_types'])) {
        return [
            'valid' => false,
            'error' => $doc_config['label'] . ' has invalid file type. Allowed: ' . 
                      implode(', ', array_map(function($m) { 
                          return explode('/', $m)[1]; 
                      }, $doc_config['allowed_types']))
        ];
    }

    // For images, validate dimensions
    if (strpos($mime, 'image/') === 0 && $document_type === 'selfie') {
        $image_info = getimagesize($file['tmp_name']);
        if ($image_info === false) {
            return ['valid' => false, 'error' => 'Invalid image file'];
        }
        if ($image_info[0] < 320 || $image_info[1] < 240) {
            return [
                'valid' => false,
                'error' => 'Selfie image is too small. Minimum 320x240 pixels required'
            ];
        }
    }

    return [
        'valid' => true,
        'mime' => $mime,
        'size' => $file['size'],
        'filename' => $file['name']
    ];
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($original_filename, $provider_id, $document_type) {
    $ext = pathinfo($original_filename, PATHINFO_EXTENSION);
    $timestamp = time();
    $random = bin2hex(random_bytes(4));
    $filename = sprintf('%d_%s_%d_%s.%s', 
        $provider_id, 
        $document_type, 
        $timestamp, 
        $random, 
        $ext
    );
    return $filename;
}

/**
 * Handle single document upload
 */
function uploadDocument($file, $document_type, $provider_id, &$file_path) {
    global $DOCUMENT_TYPES;

    // Validate file
    $validation = validateFile($file, $document_type, $DOCUMENT_TYPES);
    if (!$validation['valid']) {
        return ['valid' => false, 'error' => $validation['error']];
    }

    // Ensure directories exist
    ensureUploadDirectories();

    $doc_config = $DOCUMENT_TYPES[$document_type];
    $base_dir = __DIR__ . '/../assets/images/registration/' . $doc_config['folder'];
    
    // Generate unique filename
    $filename = generateUniqueFilename($validation['filename'], $provider_id, $document_type);
    $file_path_full = $base_dir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $file_path_full)) {
        return ['valid' => false, 'error' => 'Failed to save file on server'];
    }

    // Set proper permissions
    chmod($file_path_full, 0644);

    // Return relative path for database storage
    $file_path = 'assets/images/registration/' . $doc_config['folder'] . '/' . $filename;
    
    return [
        'valid' => true,
        'file_path' => $file_path,
        'mime' => $validation['mime'],
        'size' => $validation['size'],
        'filename' => $filename
    ];
}

/**
 * Map document type to database column name
 */
function getColumnNameForDocType($doc_type) {
    $mapping = [
        'valid_id' => 'valid_id',
        'barangay_clearance' => 'barangay_clearance',
        'selfie' => 'selfie_verification',
        'proof_of_address' => 'proof_of_address',
        'tools_kits' => 'tools_&_kits'
    ];
    return $mapping[$doc_type] ?? null;
}

/**
 * Store document info in database (direct to service_providers)
 */
function storeDocumentInfo($conn, $provider_id, $document_type, $file_path, $original_filename, $file_size, $mime_type) {
    $column_name = getColumnNameForDocType($document_type);
    
    if (!$column_name) {
        return ['success' => false, 'error' => 'Invalid document type'];
    }

    // Use UPDATE to set the column directly for this provider
    $query = "UPDATE service_providers SET `" . $column_name . "` = ? WHERE provider_id = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        return ['success' => false, 'error' => 'Database error: ' . $conn->error];
    }

    $stmt->bind_param('si', $file_path, $provider_id);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'error' => 'Failed to store document info'];
    }

    $stmt->close();
    return ['success' => true];
}

/**
 * POST: Upload documents
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_documents') {
    initializeTables($conn);

    $required_docs = ['valid_id', 'selfie', 'proof_of_address', 'tools_kits'];
    $optional_docs = ['barangay_clearance'];
    
    $uploaded_docs = [];
    $errors = [];

    // Process required documents
    foreach ($required_docs as $doc_type) {
        if (!isset($_FILES[$doc_type]) || $_FILES[$doc_type]['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = $DOCUMENT_TYPES[$doc_type]['label'] . ' is required';
            continue;
        }

        $result = uploadDocument($_FILES[$doc_type], $doc_type, $provider_id, $file_path);
        if (!$result['valid']) {
            $errors[] = $DOCUMENT_TYPES[$doc_type]['label'] . ': ' . $result['error'];
            continue;
        }

        $store_result = storeDocumentInfo(
            $conn, 
            $provider_id, 
            $doc_type, 
            $result['file_path'],
            $result['filename'],
            $result['size'],
            $result['mime']
        );

        if (!$store_result['success']) {
            $errors[] = $store_result['error'];
            continue;
        }

        $uploaded_docs[] = $doc_type;
    }

    // Process optional documents
    foreach ($optional_docs as $doc_type) {
        if (!isset($_FILES[$doc_type]) || $_FILES[$doc_type]['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $result = uploadDocument($_FILES[$doc_type], $doc_type, $provider_id, $file_path);
        if (!$result['valid']) {
            $errors[] = $DOCUMENT_TYPES[$doc_type]['label'] . ': ' . $result['error'];
            continue;
        }

        $store_result = storeDocumentInfo(
            $conn, 
            $provider_id, 
            $doc_type, 
            $result['file_path'],
            $result['filename'],
            $result['size'],
            $result['mime']
        );

        if (!$store_result['success']) {
            $errors[] = $store_result['error'];
            continue;
        }

        $uploaded_docs[] = $doc_type;
    }

    // Check if all required documents were uploaded
    if (count($errors) > 0 && count($uploaded_docs) < count($required_docs)) {
        respond(false, 'Upload failed. ' . implode(' | ', $errors));
    }

    // Update provider verification status to 'pending' so admin can see it in "For Verification"
    $verification_status = count($uploaded_docs) >= count($required_docs) ? 'pending' : 'partial';
    $stmt = $conn->prepare("UPDATE service_providers SET verification_status = ?, verification_submitted_at = NOW() WHERE provider_id = ?");
    $stmt->bind_param('si', $verification_status, $provider_id);
    $stmt->execute();
    $stmt->close();

    // Notify admin
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL DEFAULT 'general',
        title VARCHAR(200) NOT NULL,
        message TEXT,
        reference_id INT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $provider_stmt = $conn->prepare("SELECT full_name FROM service_providers WHERE provider_id = ?");
    $provider_stmt->bind_param('i', $provider_id);
    $provider_stmt->execute();
    $provider_res = $provider_stmt->get_result()->fetch_assoc();
    $provider_stmt->close();

    $provider_name = $provider_res['full_name'] ?? 'A service provider';
    $notif_title = 'New Verification Documents Submitted';
    $notif_message = $provider_name . ' has submitted ' . count($uploaded_docs) . ' verification document(s).';

    $notif_stmt = $conn->prepare("INSERT INTO admin_notifications (type, title, message, reference_id, created_at) VALUES ('verification', ?, ?, ?, NOW())");
    $notif_stmt->bind_param('ssi', $notif_title, $notif_message, $provider_id);
    $notif_stmt->execute();
    $notif_stmt->close();

    $message = 'Documents uploaded successfully (' . count($uploaded_docs) . '/' . (count($required_docs) + count($optional_docs)) . ').';
    if (count($errors) > 0) {
        $message .= ' Warnings: ' . implode(' | ', $errors);
    }

    respond(true, $message, [
        'uploaded' => count($uploaded_docs),
        'total_required' => count($required_docs),
        'status' => $verification_status
    ]);
}

/**
 * GET: Retrieve document information for a provider
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'get_documents') {
    initializeTables($conn);

    $stmt = $conn->prepare(
        "SELECT valid_id, barangay_clearance, selfie_verification, proof_of_address, `tools_&_kits`, 
                verification_status, verification_submitted_at, verification_approved_at
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

    // Convert to document type structure
    $documents = [];
    if ($result['valid_id']) $documents['valid_id'] = ['file_path' => $result['valid_id'], 'type' => 'valid_id'];
    if ($result['barangay_clearance']) $documents['barangay_clearance'] = ['file_path' => $result['barangay_clearance'], 'type' => 'barangay_clearance'];
    if ($result['selfie_verification']) $documents['selfie'] = ['file_path' => $result['selfie_verification'], 'type' => 'selfie'];
    if ($result['proof_of_address']) $documents['proof_of_address'] = ['file_path' => $result['proof_of_address'], 'type' => 'proof_of_address'];
    if ($result['tools_&_kits']) $documents['tools_kits'] = ['file_path' => $result['tools_&_kits'], 'type' => 'tools_kits'];

    respond(true, '', ['documents' => $documents]);
}

/**
 * POST: Delete a document (clear the file path for a document type)
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete_document') {
    initializeTables($conn);

    $doc_type = $_POST['doc_type'] ?? '';
    
    if (!$doc_type) {
        respond(false, 'Invalid document type');
    }

    $column_name = getColumnNameForDocType($doc_type);
    if (!$column_name) {
        respond(false, 'Invalid document type');
    }

    // Get current file path to delete the file
    $get_stmt = $conn->prepare("SELECT `" . $column_name . "` FROM service_providers WHERE provider_id = ?");
    $get_stmt->bind_param('i', $provider_id);
    $get_stmt->execute();
    $doc_result = $get_stmt->get_result()->fetch_assoc();
    $get_stmt->close();

    if (!$doc_result) {
        respond(false, 'Provider not found');
    }

    $file_path = $doc_result[$column_name];
    
    // Delete file from filesystem
    if ($file_path) {
        $file_full_path = __DIR__ . '/../' . $file_path;
        if (file_exists($file_full_path)) {
            unlink($file_full_path);
        }
    }

    // Clear the database column
    $delete_stmt = $conn->prepare("UPDATE service_providers SET `" . $column_name . "` = NULL WHERE provider_id = ?");
    $delete_stmt->bind_param('i', $provider_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    respond(true, 'Document deleted successfully');
}

/**
 * GET: Check verification status
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'check_status') {
    initializeTables($conn);

    $stmt = $conn->prepare(
        "SELECT verification_status, verification_submitted_at, verification_approved_at,
                IF(valid_id IS NOT NULL, 1, 0) as has_valid_id,
                IF(barangay_clearance IS NOT NULL, 1, 0) as has_barangay_clearance,
                IF(selfie_verification IS NOT NULL, 1, 0) as has_selfie,
                IF(proof_of_address IS NOT NULL, 1, 0) as has_proof_of_address,
                IF(`tools_&_kits` IS NOT NULL, 1, 0) as has_tools_kits
         FROM service_providers WHERE provider_id = ?"
    );
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Count documents
    $document_count = 0;
    if ($result['has_valid_id']) $document_count++;
    if ($result['has_barangay_clearance']) $document_count++;
    if ($result['has_selfie']) $document_count++;
    if ($result['has_proof_of_address']) $document_count++;
    if ($result['has_tools_kits']) $document_count++;

    respond(true, '', [
        'status' => $result['verification_status'] ?? 'not_submitted',
        'submitted_at' => $result['verification_submitted_at'],
        'approved_at' => $result['verification_approved_at'],
        'document_count' => $document_count
    ]);
}

respond(false, 'Invalid request');

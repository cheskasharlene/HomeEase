<?php
/**
 * Provider Document Handler Utility
 * Provides helper functions for document management
 */

/**
 * Get all required and optional documents
 */
function getDocumentRequirements() {
    return [
        'required' => [
            'valid_id' => [
                'label' => 'Valid Government ID',
                'icon' => 'bi-card-image',
                'folder' => 'id'
            ],
            'barangay_clearance' => [
                'label' => 'Barangay Clearance',
                'icon' => 'bi-certificate',
                'folder' => 'brgy'
            ],
            'selfie' => [
                'label' => 'Selfie (Identity Confirmation)',
                'icon' => 'bi-person-bounding-box',
                'folder' => 'selfie'
            ],
            'proof_of_address' => [
                'label' => 'Proof of Address',
                'icon' => 'bi-house-check',
                'folder' => 'address'
            ]
        ],
        'optional' => [
            'tools_kits' => [
                'label' => 'Tools & Kits',
                'icon' => 'bi-toolbox',
                'folder' => 'tools'
            ]
        ]
    ];
}

/**
 * Get document status for provider
 */
function getProviderDocumentStatus($conn, $provider_id) {
    $reqs = getDocumentRequirements();
    $status = [
        'required' => [],
        'optional' => [],
        'all_required_submitted' => false,
        'total_submitted' => 0
    ];

    // Map document type to DB column
    $column_map = [
        'valid_id' => 'valid_id',
        'barangay_clearance' => 'barangay_clearance',
        'selfie' => 'selfie_verification',
        'proof_of_address' => 'proof_of_address',
        'tools_kits' => 'tools_&_kits'
    ];

    // Get provider data
    $stmt = $conn->prepare("SELECT valid_id, barangay_clearance, selfie_verification, proof_of_address, `tools_&_kits` FROM service_providers WHERE provider_id = ?");
    $stmt->bind_param('i', $provider_id);
    $stmt->execute();
    $provider_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$provider_data) {
        return $status;
    }

    foreach ($reqs['required'] as $doc_type => $info) {
        $db_column = $column_map[$doc_type] ?? null;
        $is_submitted = $db_column && !empty($provider_data[$db_column]);
        $status['required'][$doc_type] = $is_submitted;
        if ($is_submitted) $status['total_submitted']++;
    }

    foreach ($reqs['optional'] as $doc_type => $info) {
        $db_column = $column_map[$doc_type] ?? null;
        $is_submitted = $db_column && !empty($provider_data[$db_column]);
        $status['optional'][$doc_type] = $is_submitted;
        if ($is_submitted) $status['total_submitted']++;
    }

    $status['all_required_submitted'] = !in_array(false, $status['required']);

    return $status;
}

/**
 * Is document type required
 */
function isDocumentRequired($doc_type) {
    $reqs = getDocumentRequirements();
    return isset($reqs['required'][$doc_type]);
}

/**
 * Get document info by type
 */
function getDocumentInfo($doc_type) {
    $reqs = getDocumentRequirements();
    return $reqs['required'][$doc_type] ?? $reqs['optional'][$doc_type] ?? null;
}

/**
 * Format file size for display
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $k = 1024;
    $sizes = array('B', 'KB', 'MB');
    $i = floor(log($bytes, $k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

/**
 * Check if file is an image
 */
function isImageFile($mime_type) {
    return strpos($mime_type, 'image/') === 0;
}

/**
 * Get readable document type name
 */
function getDocumentTypeName($doc_type) {
    $info = getDocumentInfo($doc_type);
    return $info['label'] ?? ucfirst(str_replace('_', ' ', $doc_type));
}

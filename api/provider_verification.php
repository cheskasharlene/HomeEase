<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

if (empty($_SESSION['provider_id'])) {
    respond(false, 'Unauthorized. Please log in as a provider.');
}

$provider_id = (int)$_SESSION['provider_id'];
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload_documents') {
    $uploadDir = __DIR__ . '/../assets/uploads/documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $updates = [];
    $types = "";
    $params = [];
    
    $filesToUpload = ['id_picture', 'selfie_verification', 'proof_of_address', 'certificates', 'proof_of_experience'];
    
    foreach ($filesToUpload as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES[$field]['tmp_name'];
            $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
            $fileName = $provider_id . '_' . $field . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $fileName;
            
            if (move_uploaded_file($tmpName, $dest)) {
                $dbPath = 'assets/uploads/documents/' . $fileName;
                $updates[] = "$field = ?";
                $params[] = $dbPath;
                $types .= "s";
            }
        }
    }
    
    if (count($updates) > 0) {
        $sql = "UPDATE service_providers SET " . implode(", ", $updates) . ", status = IF(status='inactive', 'inactive', 'active') WHERE provider_id = ?";
        $params[] = $provider_id;
        $types .= "i";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $ok = $stmt->affected_rows >= 0;
        $stmt->close();
        
        if ($ok) {
            // ── Notify admin about new document submission ──
            $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL DEFAULT 'general',
                title VARCHAR(200) NOT NULL,
                message TEXT,
                reference_id INT NULL,
                is_read TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Get provider name for the notification
            $nameStmt = $conn->prepare("SELECT full_name FROM service_providers WHERE provider_id = ?");
            $nameStmt->bind_param("i", $provider_id);
            $nameStmt->execute();
            $nameRes = $nameStmt->get_result()->fetch_assoc();
            $nameStmt->close();
            $providerName = $nameRes['full_name'] ?? 'A worker';

            $docCount = count($updates);
            $notifTitle = 'New Verification Submission';
            $notifMsg = $providerName . ' has submitted ' . $docCount . ' document(s) for verification review.';

            $notifStmt = $conn->prepare("INSERT INTO admin_notifications (type, title, message, reference_id, is_read, created_at) VALUES ('verification', ?, ?, ?, 0, NOW())");
            $notifStmt->bind_param("ssi", $notifTitle, $notifMsg, $provider_id);
            $notifStmt->execute();
            $notifStmt->close();

            respond(true, 'Documents uploaded successfully.');
        } else {
            respond(false, 'Database update failed.');
        }
    } else {
        respond(false, 'No valid files were uploaded.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'status') {
    $stmt = $conn->prepare("SELECT is_verified, id_picture, selfie_verification, proof_of_address, certificates, proof_of_experience FROM service_providers WHERE provider_id = ?");
    $stmt->bind_param("i", $provider_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    respond(true, '', ['verification' => $res]);
}

respond(false, 'Unknown request.');

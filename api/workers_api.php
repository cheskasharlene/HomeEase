<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? 'list';

if ($action === 'pros') {
    $sql = "SELECT provider_id AS id, full_name AS name, service_category AS specialty, availability_status AS availability, jobs_done, rating, is_verified
            FROM service_providers
                        WHERE status = 'active'
                            AND LOWER(availability_status) IN ('available', 'online')
            ORDER BY jobs_done DESC
            LIMIT 6";

    $result = $conn->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Query failed: ' . $conn->error]);
        exit;
    }

    $pros = [];
    while ($r = $result->fetch_assoc()) {
        $pros[] = [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'specialty' => $r['specialty'] ?? '',
            'availability' => $r['availability'] ?? 'offline',
            'jobs_done' => (int) $r['jobs_done'],
            'rating' => (float) $r['rating'],
            'top' => (int) $r['jobs_done'] >= 100,
            'is_verified' => (bool) $r['is_verified'],
            'img' => 'https://ui-avatars.com/api/?name=' . urlencode($r['name']) . '&background=ccfbf1&color=0d9488&size=128'
        ];
    }

    echo json_encode(['success' => true, 'pros' => $pros]);
    exit;
}

// ── LIST (for workers.php full listing) ───────────────────────────
if ($action === 'list') {

    $search = trim($_GET['search'] ?? '');
    $filter = trim($_GET['filter'] ?? 'all');
    $orderBy = 'jobs_done DESC, name ASC';

    $conditions = ["t.status = 'active'"];
    $params = [];
    $types = '';

    if ($filter === 'available') {
        $conditions[] = "t.availability_status = 'available'";
    } elseif ($filter !== 'all') {
        $conditions[] = "(t.service_category = ? OR t.service_category LIKE ?)";
        $params[] = $filter;
        $params[] = '%' . $filter . '%';
        $types .= 'ss';
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $conditions[] = "(t.full_name LIKE ? OR t.service_category LIKE ?)";
        $params[] = $like;
        $params[] = $like;
        $types .= 'ss';
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $sql = "SELECT t.provider_id AS id, t.full_name AS name, t.service_category AS role, t.availability_status AS status,
                   t.jobs_done AS jobs, t.contact_number AS phone, t.rating, t.is_verified,
                   t.valid_id, t.barangay_clearance, t.selfie_verification, t.proof_of_address, t.`tools_&_kits`,
                   t.gcash_qr, t.bank_qr, t.qr_gcash, t.qr_bank, t.verification_status
            FROM service_providers t $where ORDER BY $orderBy";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
        exit;
    }

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $workers = array_map(function ($r) {
        return [
            'id' => (int) $r['id'],
            'name' => $r['name'] ?? '',
            'role' => $r['role'] ?? '',
            'skills' => [],
            'img' => '',
            'status' => $r['status'] ?? 'offline',
            'jobs' => (int) ($r['jobs'] ?? 0),
            'location' => '',
            'top' => false,
            'rating' => (float) ($r['rating'] ?? 0),
            'phone' => $r['phone'] ?? '',
            'is_verified' => (bool) $r['is_verified'],
            'valid_id' => $r['valid_id'] ?? '',
            'barangay_clearance' => $r['barangay_clearance'] ?? '',
            'selfie_verification' => $r['selfie_verification'] ?? '',
            'proof_of_address' => $r['proof_of_address'] ?? '',
            'tools_&_kits' => $r['tools_&_kits'] ?? '',
            'gcash_qr' => $r['gcash_qr'] ?? ($r['qr_gcash'] ?? ''),
            'bank_qr' => $r['bank_qr'] ?? ($r['qr_bank'] ?? ''),
            'verification_status' => $r['verification_status'] ?? '',
        ];
    }, $rows);

    echo json_encode(['success' => true, 'workers' => $workers, 'total' => count($workers)]);
    exit;
}

if ($action === 'profile') {
    $id = (int) ($_GET['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Provider ID is required']);
        exit;
    }

    $stmt = $conn->prepare("SELECT provider_id AS id, full_name AS name, service_category AS specialty, availability_status AS availability, contact_number AS phone, address, profile_image, created_at, rating, jobs_done, status, is_verified,
                                   valid_id, barangay_clearance, selfie_verification, proof_of_address, `tools_&_kits`, gcash_qr, bank_qr, qr_gcash, qr_bank, verification_status
                            FROM service_providers WHERE provider_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $provider = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$provider) {
        echo json_encode(['success' => false, 'message' => 'Provider not found']);
        exit;
    }

    // Get recent reviews
    $stmt = $conn->prepare("SELECT r.rating, r.comment, r.created_at, u.name AS user_name FROM provider_reviews r LEFT JOIN users u ON r.user_id = u.id WHERE r.provider_id = ? ORDER BY r.created_at DESC LIMIT 10");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'provider' => $provider, 'reviews' => $reviews]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;

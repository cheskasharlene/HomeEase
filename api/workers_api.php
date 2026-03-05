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
    $sql = "SELECT id, name, specialty, availability, jobs_done, rating
            FROM technicians
            WHERE status = 'active'
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
            'id'           => (int) $r['id'],
            'name'         => $r['name'],
            'specialty'    => $r['specialty'] ?? '',
            'availability' => $r['availability'] ?? 'offline',
            'jobs_done'    => (int) $r['jobs_done'],
            'rating'       => (float) $r['rating'],
            'top'          => (int) $r['jobs_done'] >= 100,
            'img'          => 'https://ui-avatars.com/api/?name=' . urlencode($r['name']) . '&background=ccfbf1&color=0d9488&size=128'
        ];
    }

    echo json_encode(['success' => true, 'pros' => $pros]);
    exit;
}

if ($action === 'list') {

    $search  = trim($_GET['search'] ?? '');
    $filter  = trim($_GET['filter'] ?? 'all');
    $orderBy = 'jobs_done DESC, name ASC';

    $conditions = ["t.status = 'active'"];
    $params     = [];
    $types      = '';

    if ($filter === 'available') {
        $conditions[] = "t.availability = 'available'";
    } elseif ($filter !== 'all') {
        $conditions[] = "(t.specialty = ? OR t.specialty LIKE ?)";
        $params[]     = $filter;
        $params[]     = '%' . $filter . '%';
        $types       .= 'ss';
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $conditions[] = "(t.name LIKE ? OR t.specialty LIKE ?)";
        $params[]     = $like;
        $params[]     = $like;
        $types       .= 'ss';
    }

    $where = 'WHERE ' . implode(' AND ', $conditions);

    $sql = "SELECT t.id, t.name, t.specialty AS role, t.availability AS status,
                   t.jobs_done AS jobs, t.phone, t.rating
            FROM technicians t $where ORDER BY $orderBy";

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
            'id'       => (int)  $r['id'],
            'name'     => $r['name']   ?? '',
            'role'     => $r['role']   ?? '',
            'skills'   => [],
            'img'      => '',
            'status'   => $r['status'] ?? 'offline',
            'jobs'     => (int) ($r['jobs'] ?? 0),
            'location' => '',
            'top'      => false,
            'rating'   => (float) ($r['rating'] ?? 0),
            'phone'    => $r['phone']  ?? '',
        ];
    }, $rows);

    echo json_encode(['success' => true, 'workers' => $workers, 'total' => count($workers)]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;

<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/db.php';

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

$uid = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($method === 'GET' && $action === 'services') {
    $result = $conn->query("SELECT * FROM services WHERE active = 1 ORDER BY name ASC");
    if (!$result) {
        echo json_encode(['success' => true, 'services' => _defaultServices()]);
        exit;
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'services' => $rows ?: _defaultServices()]);
    exit;
}

if ($method === 'GET' && $action === 'technicians') {
    $specialty = trim($_GET['specialty'] ?? '');
    if ($specialty) {
        $like = "%$specialty%";
        $stmt = $conn->prepare("SELECT id, name, specialty, phone, availability, rating, jobs_done, status FROM technicians WHERE status='active' AND specialty LIKE ? ORDER BY name ASC");
        $stmt->bind_param("s", $like);
    } else {
        $stmt = $conn->prepare("SELECT id, name, specialty, phone, availability, rating, jobs_done, status FROM technicians WHERE status='active' ORDER BY name ASC");
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode(['success' => true, 'technicians' => $rows]);
    exit;
}

if ($method === 'GET' && $action === 'offers') {
    $conn->query("CREATE TABLE IF NOT EXISTS special_offers (
        id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(120) NOT NULL,
        code VARCHAR(50) NOT NULL UNIQUE, description TEXT,
        discount_type ENUM('percent','flat') NOT NULL DEFAULT 'percent',
        discount_value DECIMAL(10,2) NOT NULL DEFAULT 0,
        min_booking_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        max_uses INT NOT NULL DEFAULT 0, used_count INT NOT NULL DEFAULT 0,
        expires_at DATETIME NULL, active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $now = date('Y-m-d H:i:s');
    $result = $conn->query("SELECT * FROM special_offers WHERE active=1 AND (max_uses=0 OR used_count<max_uses) AND (expires_at IS NULL OR expires_at>'$now') ORDER BY created_at DESC");
    echo json_encode(['success' => true, 'offers' => $result ? $result->fetch_all(MYSQLI_ASSOC) : []]);
    exit;
}

if ($method === 'GET' && $action === '') {

    $cols = [];
    $cr = $conn->query("SHOW COLUMNS FROM bookings");
    if ($cr) {
        while ($c = $cr->fetch_assoc())
            $cols[] = $c['Field'];
    }

    if (in_array('technician_id', $cols)) {
        $stmt = $conn->prepare(
            "SELECT b.*, t.name AS technician_name, t.phone AS tech_phone
             FROM bookings b
             LEFT JOIN technicians t ON b.technician_id = t.id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT *, NULL AS technician_name, NULL AS tech_phone
             FROM bookings
             WHERE user_id = ?
             ORDER BY created_at DESC"
        );
    }

    if (!$stmt) {
        echo json_encode(['success' => true, 'bookings' => []]);
        exit;
    }
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode(['success' => true, 'bookings' => $rows]);
    exit;
}

if ($method === 'POST' && $action === '') {
    $service = trim($_POST['service'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time_slot = trim($_POST['time_slot'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $pricing_type = trim($_POST['pricing_type'] ?? 'flat');
    $hours = max(1, (int) ($_POST['hours'] ?? 1));
    $price = floatval($_POST['price'] ?? 0);
    $tech_id = intval($_POST['technician_id'] ?? 0) ?: null;

    if (!$service || !$date || !$address) {
        echo json_encode(['success' => false, 'message' => 'Service, date, and address are required.']);
        exit;
    }

    $bcols = [];
    $br = $conn->query("SHOW COLUMNS FROM bookings");
    if ($br) {
        while ($c = $br->fetch_assoc())
            $bcols[] = $c['Field'];
    }

    $col_list = "user_id, service, date, address, price, status, created_at";
    $val_list = "?, ?, ?, ?, ?, 'pending', NOW()";
    $types = "isssd";
    $params = [$uid, $service, $date, $address, $price];

    if (in_array('time_slot', $bcols)) {
        $col_list .= ", time_slot";
        $val_list .= ", ?";
        $types .= "s";
        $params[] = $time_slot;
    }
    if (in_array('notes', $bcols)) {
        $col_list .= ", notes";
        $val_list .= ", ?";
        $types .= "s";
        $params[] = $notes;
    }
    if (in_array('pricing_type', $bcols)) {
        $col_list .= ", pricing_type";
        $val_list .= ", ?";
        $types .= "s";
        $params[] = $pricing_type;
    }
    if (in_array('hours', $bcols)) {
        $col_list .= ", hours";
        $val_list .= ", ?";
        $types .= "i";
        $params[] = $hours;
    }
    if (in_array('technician_id', $bcols)) {
        $col_list .= ", technician_id";
        $val_list .= ", ?";
        $types .= "i";
        $params[] = $tech_id;
    }

    $stmt = $conn->prepare("INSERT INTO bookings ($col_list) VALUES ($val_list)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn->error]);
        exit;
    }

    $bind = array_merge([$types], $params);
    $refs = [];
    foreach ($bind as $i => $v) {
        $refs[$i] = &$bind[$i];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);

    if ($stmt->execute()) {
        $bid = $conn->insert_id;
        $stmt->close();
        $msg = "Your $service booking on $date has been received.";
        $icon = _svcIcon($service);
        $ns = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Received', ?, ?, 0, NOW())");
        if ($ns) {
            $ns->bind_param("iss", $uid, $msg, $icon);
            $ns->execute();
            $ns->close();
        }
        echo json_encode(['success' => true, 'booking_id' => $bid]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Insert failed: ' . $conn->error]);
    }
    exit;
}

if ($method === 'POST' && $action === 'cancel') {
    $id = intval($_POST['id'] ?? 0);
    $stmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=? AND status='pending'");
    $stmt->bind_param("ii", $id, $uid);
    $ok = $stmt->execute() && $stmt->affected_rows > 0;
    $stmt->close();
    echo json_encode(['success' => $ok, 'message' => $ok ? 'Cancelled.' : 'Could not cancel.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown request.']);

function _svcIcon($s)
{
    $m = [
        'Cleaning' => 'cleaning',
        'Plumbing' => 'plumbing',
        'Electrical' => 'electrical',
        'Painting' => 'painting',
        'Appliance Repair' => 'appliance',
        'Gardening' => 'gardening'
    ];
    return $m[$s] ?? 'cleaning';
}
function _defaultServices()
{
    return [
        ['id' => 1, 'name' => 'Cleaning', 'icon' => '🧹', 'description' => 'Deep home & office cleaning', 'hourly_rate' => 200, 'flat_rate' => 599, 'min_hours' => 2, 'pricing_type' => 'both', 'active' => 1],
        ['id' => 2, 'name' => 'Plumbing', 'icon' => '🔧', 'description' => 'Pipe repair, clogs & more', 'hourly_rate' => 250, 'flat_rate' => 450, 'min_hours' => 1, 'pricing_type' => 'both', 'active' => 1],
        ['id' => 3, 'name' => 'Electrical', 'icon' => '⚡', 'description' => 'Wiring, outlets & installs', 'hourly_rate' => 300, 'flat_rate' => 750, 'min_hours' => 1, 'pricing_type' => 'both', 'active' => 1],
        ['id' => 4, 'name' => 'Painting', 'icon' => '🖌️', 'description' => 'Interior & exterior painting', 'hourly_rate' => 220, 'flat_rate' => 800, 'min_hours' => 3, 'pricing_type' => 'both', 'active' => 1],
        ['id' => 5, 'name' => 'Appliance Repair', 'icon' => '🔩', 'description' => 'Fix any home appliance', 'hourly_rate' => 280, 'flat_rate' => 650, 'min_hours' => 1, 'pricing_type' => 'both', 'active' => 1],
        ['id' => 6, 'name' => 'Gardening', 'icon' => '🌿', 'description' => 'Landscaping & garden care', 'hourly_rate' => 180, 'flat_rate' => 850, 'min_hours' => 2, 'pricing_type' => 'both', 'active' => 1],
    ];
}

<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

$_dbfile = file_exists(__DIR__ . '/api/db.php') ? __DIR__ . '/api/db.php'
    : (file_exists(__DIR__ . '/db.php') ? __DIR__ . '/db.php' : null);
if (!$_dbfile) {
    echo json_encode(['success' => false, 'message' => 'db.php not found']);
    exit;
}
require_once $_dbfile;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'ping') {
    echo json_encode(['success' => true, 'message' => 'admin_api.php reachable', 'time' => date('Y-m-d H:i:s')]);
    exit;
}

if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

function tableColumns($conn, $table)
{
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($r) {
        while ($c = $r->fetch_assoc())
            $cols[] = $c['Field'];
    }
    return $cols;
}

if ($action === 'stats') {
    $stats = [];
    $queries = [
        'total_bookings' => "SELECT COUNT(*) FROM bookings",
        'total_users' => "SELECT COUNT(*) FROM users",
        'total_techs' => "SELECT COUNT(*) FROM technicians WHERE status='active'",
        'total_revenue' => "SELECT COALESCE(SUM(price),0) FROM bookings WHERE status='done'",
        'pending' => "SELECT COUNT(*) FROM bookings WHERE status='pending'",
        'in_progress' => "SELECT COUNT(*) FROM bookings WHERE status='progress'",
        'done' => "SELECT COUNT(*) FROM bookings WHERE status='done'",
        'cancelled' => "SELECT COUNT(*) FROM bookings WHERE status='cancelled'",
    ];
    foreach ($queries as $k => $q) {
        $r = $conn->query($q);
        $stats[$k] = $r ? (float) $r->fetch_row()[0] : 0;
    }
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if ($action === 'get_bookings') {
    $status = $_GET['status'] ?? 'all';
    $search = '%' . trim($_GET['search'] ?? '') . '%';

    // Only JOIN technicians if technician_id column exists
    $bcols = tableColumns($conn, 'bookings');
    $hasTech = in_array('technician_id', $bcols);

    if ($hasTech) {
        $sql = "SELECT b.*, u.name AS customer_name, t.name AS technician_name
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN technicians t ON b.technician_id = t.id";
    } else {
        $sql = "SELECT b.*, u.name AS customer_name, NULL AS technician_name
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id";
    }

    if ($status !== 'all')
        $sql .= " WHERE b.status = " . $conn->real_escape_string("'$status'");
    $searchTerm = trim($_GET['search'] ?? '');
    if ($searchTerm !== '') {
        $sql .= ($status !== 'all' ? ' AND' : ' WHERE');
        $sql .= " (u.name LIKE ? OR b.service LIKE ? OR b.address LIKE ?)";
        $stmt = $conn->prepare($sql . " ORDER BY b.created_at DESC");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
        $stmt->bind_param("sss", $search, $search, $search);
    } else {
        $stmt = $conn->prepare($sql . " ORDER BY b.created_at DESC");
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => $conn->error]);
            exit;
        }
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'bookings' => $rows]);
    exit;
}

if ($action === 'update_booking') {
    $id = intval($_POST['id']);
    $status = $_POST['status'] ?? '';
    $price = floatval($_POST['price'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $tech_id = intval($_POST['technician_id'] ?? 0) ?: null;

    $bcols = tableColumns($conn, 'bookings');
    $hasTech = in_array('technician_id', $bcols);
    $hasNotes = in_array('notes', $bcols);

    if ($hasTech && $hasNotes) {
        $stmt = $conn->prepare("UPDATE bookings SET status=?, price=?, notes=?, technician_id=? WHERE id=?");
        $stmt->bind_param("sdsii", $status, $price, $notes, $tech_id, $id);
    } elseif ($hasTech) {
        $stmt = $conn->prepare("UPDATE bookings SET status=?, price=?, technician_id=? WHERE id=?");
        $stmt->bind_param("sdii", $status, $price, $tech_id, $id);
    } else {
        $stmt = $conn->prepare("UPDATE bookings SET status=?, price=? WHERE id=?");
        $stmt->bind_param("sdi", $status, $price, $id);
    }
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'delete_booking') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM bookings WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'get_users') {
    $search = '%' . trim($_GET['search'] ?? '') . '%';
    $stmt = $conn->prepare(
        "SELECT id, name, email, phone, role, status, created_at,
         (SELECT COUNT(*) FROM bookings WHERE user_id=users.id) AS booking_count
         FROM users WHERE name LIKE ? OR email LIKE ?
         ORDER BY created_at DESC"
    );
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    echo json_encode(['success' => true, 'users' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'toggle_user') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'delete_user') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'get_technicians') {
    $result = $conn->query("SELECT * FROM technicians ORDER BY status='active' DESC, name ASC");
    if (!$result) {
        echo json_encode(['success' => false, 'message' => $conn->error, 'technicians' => []]);
        exit;
    }
    echo json_encode(['success' => true, 'technicians' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'save_technician') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $spec = trim($_POST['specialty'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $avail = $_POST['availability'] ?? 'available';
    $status = $_POST['status'] ?? 'active';
    if (!$name || !$spec) {
        echo json_encode(['success' => false, 'message' => 'Name and specialty required']);
        exit;
    }
    if ($id) {
        $stmt = $conn->prepare("UPDATE technicians SET name=?,specialty=?,phone=?,availability=?,status=? WHERE id=?");
        $stmt->bind_param("sssssi", $name, $spec, $phone, $avail, $status, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO technicians (name,specialty,phone,availability,status) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $spec, $phone, $avail, $status);
    }
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok, 'id' => $conn->insert_id ?: $id]);
    exit;
}

if ($action === 'delete_technician') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM technicians WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'get_services') {
    $result = $conn->query("SELECT * FROM services ORDER BY name ASC");
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Services table not found', 'services' => []]);
        exit;
    }
    echo json_encode(['success' => true, 'services' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'save_service') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? '🔧');
    $desc = trim($_POST['description'] ?? '');
    $hourly = floatval($_POST['hourly_rate'] ?? 0);
    $flat = floatval($_POST['flat_rate'] ?? 0);
    $min_h = max(1, intval($_POST['min_hours'] ?? 1));
    $ptype = $_POST['pricing_type'] ?? 'both';
    $active = intval($_POST['active'] ?? 1);
    if (!$name) {
        echo json_encode(['success' => false, 'message' => 'Name required']);
        exit;
    }
    if ($id) {
        $stmt = $conn->prepare("UPDATE services SET name=?,icon=?,description=?,hourly_rate=?,flat_rate=?,min_hours=?,pricing_type=?,active=? WHERE id=?");
        $stmt->bind_param("sssddisii", $name, $icon, $desc, $hourly, $flat, $min_h, $ptype, $active, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO services (name,icon,description,hourly_rate,flat_rate,min_hours,pricing_type,active) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("sssddisi", $name, $icon, $desc, $hourly, $flat, $min_h, $ptype, $active);
    }
    $ok = $stmt->execute();
    echo json_encode(['success' => $ok, 'id' => $conn->insert_id ?: $id]);
    exit;
}

if ($action === 'toggle_service') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE services SET active = IF(active=1,0,1) WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'delete_service') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}


$conn->query("CREATE TABLE IF NOT EXISTS special_offers (
  id                INT AUTO_INCREMENT PRIMARY KEY,
  title             VARCHAR(120) NOT NULL,
  code              VARCHAR(50)  NOT NULL UNIQUE,
  description       TEXT,
  discount_type     ENUM('percent','flat') NOT NULL DEFAULT 'percent',
  discount_value    DECIMAL(10,2) NOT NULL DEFAULT 0,
  min_booking_price DECIMAL(10,2) NOT NULL DEFAULT 0,
  max_uses          INT NOT NULL DEFAULT 0,
  used_count        INT NOT NULL DEFAULT 0,
  expires_at        DATETIME NULL,
  active            TINYINT(1) NOT NULL DEFAULT 1,
  created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if ($action === 'get_offers') {
    $result = $conn->query("SELECT * FROM special_offers ORDER BY created_at DESC");
    if (!$result) {
        echo json_encode(['success' => false, 'message' => $conn->error, 'offers' => []]);
        exit;
    }
    echo json_encode(['success' => true, 'offers' => $result->fetch_all(MYSQLI_ASSOC)]);
    exit;
}

if ($action === 'save_offer') {
    $id = intval($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $desc = trim($_POST['description'] ?? '');
    $dtype = in_array($_POST['discount_type'] ?? '', ['percent', 'flat']) ? $_POST['discount_type'] : 'percent';
    $dval = floatval($_POST['discount_value'] ?? 0);
    $minprice = floatval($_POST['min_booking_price'] ?? 0);
    $maxuses = intval($_POST['max_uses'] ?? 0);
    $expires = trim($_POST['expires_at'] ?? '') ?: null;
    $active = intval($_POST['active'] ?? 1);
    if (!$title || !$code) {
        echo json_encode(['success' => false, 'message' => 'Title and code required']);
        exit;
    }
    if ($id) {
        $stmt = $conn->prepare(
            "UPDATE special_offers SET title=?,code=?,description=?,discount_type=?,discount_value=?,min_booking_price=?,max_uses=?,expires_at=?,active=? WHERE id=?"
        );
        $stmt->bind_param("ssssddisii", $title, $code, $desc, $dtype, $dval, $minprice, $maxuses, $expires, $active, $id);
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO special_offers (title,code,description,discount_type,discount_value,min_booking_price,max_uses,expires_at,active) VALUES (?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param("ssssddisi", $title, $code, $desc, $dtype, $dval, $minprice, $maxuses, $expires, $active);
    }
    $ok = $stmt->execute();
    if (!$ok) {
        echo json_encode(['success' => false, 'message' => $conn->error]);
        exit;
    }
    echo json_encode(['success' => true, 'id' => $conn->insert_id ?: $id]);
    exit;
}

if ($action === 'toggle_offer') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("UPDATE special_offers SET active = IF(active=1,0,1) WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

if ($action === 'delete_offer') {
    $id = intval($_POST['id']);
    $stmt = $conn->prepare("DELETE FROM special_offers WHERE id=?");
    $stmt->bind_param("i", $id);
    echo json_encode(['success' => $stmt->execute()]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
?>
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/db.php';

$section = $_GET['section'] ?? $_POST['section'] ?? '';
$action  = $_GET['action']  ?? $_POST['action']  ?? 'list';
$method  = $_SERVER['REQUEST_METHOD'];

if ($section === 'auth' && $action === 'login' && $method === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = trim($_POST['password'] ?? '');
    if (!$email || !$pass) { respond(false, 'Email and password required.'); }

    $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $pwOk = password_verify($pass, $user['password']) || $pass === $user['password'];
    if (!$user || !$pwOk) {
        respond(false, 'Invalid credentials or not an admin account.');
    }

    if ($pass === $user['password'] && strpos($user['password'], '$2y$') !== 0) {
        $hashed = password_hash($pass, PASSWORD_BCRYPT);
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hashed, $user['id']);
        $upd->execute();
        $upd->close();
    }

    $_SESSION['admin_id']   = $user['id']; $_SESSION['user_id']   = $user['id'];
    $_SESSION['admin_name'] = $user['name']; $_SESSION['user_name'] = $user['name'];
    $_SESSION['admin_role'] = $user['role']; $_SESSION['user_role'] = $user['role'];
    respond(true, 'Login successful.', ['name' => $user['name']]);
}

if ((empty($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') && (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin')) {
    respond(false, 'Unauthorized.');
}

if ($section === 'auth' && $action === 'logout') {
    session_unset(); session_destroy();
    respond(true, 'Logged out.');
}

if ($section === 'stats') {
    $stats = [];

    $r = $conn->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
    $stats['total_users'] = (int)($r ? $r->fetch_row()[0] : 0);

    $r = $conn->query("SELECT COUNT(*) FROM bookings");
    $stats['total_bookings'] = (int)($r ? $r->fetch_row()[0] : 0);

    $r = $conn->query("SELECT COALESCE(SUM(price),0) FROM bookings WHERE status='done'");
    $stats['total_revenue'] = (float)($r ? $r->fetch_row()[0] : 0);

    $r = $conn->query("SELECT COUNT(*) FROM service_providers WHERE status='active'");
    $stats['active_workers'] = (int)($r ? $r->fetch_row()[0] : 0);

    $r = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='pending'");
    $stats['pending_bookings'] = (int)($r ? $r->fetch_row()[0] : 0);

    $r = $conn->query("SELECT COUNT(*) FROM bookings WHERE status='progress'");
    $stats['in_progress'] = (int)($r ? $r->fetch_row()[0] : 0);

    $revRows = [];
    $res = $conn->query("SELECT DATE_FORMAT(created_at,'%b') AS mo, COALESCE(SUM(price),0) AS rev
        FROM bookings WHERE status='done' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY YEAR(created_at), MONTH(created_at), mo ORDER BY YEAR(created_at), MONTH(created_at)");
    if ($res) while ($rr = $res->fetch_assoc()) $revRows[] = $rr;
    $stats['revenue_chart'] = $revRows;

    $breakdown = [];
    $res = $conn->query("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status");
    if ($res) while ($rr = $res->fetch_assoc()) $breakdown[$rr['status']] = (int)$rr['cnt'];
    $stats['breakdown'] = $breakdown;

    $topSvc = [];
    $res = $conn->query("SELECT service, COUNT(*) AS cnt FROM bookings GROUP BY service ORDER BY cnt DESC LIMIT 5");
    if ($res) while ($rr = $res->fetch_assoc()) $topSvc[] = $rr;
    $stats['top_services'] = $topSvc;

    // Recent bookings (last 5)
    $recent = [];
    $res = $conn->query("SELECT b.id, b.service, b.status, b.price, b.date, u.name AS user_name
        FROM bookings b LEFT JOIN users u ON b.user_id=u.id
        ORDER BY b.created_at DESC LIMIT 5");
    if ($res) while ($rr = $res->fetch_assoc()) $recent[] = $rr;
    $stats['recent_bookings'] = $recent;

    respond(true, '', ['stats' => $stats]);
}

if ($section === 'users') {
    if ($method === 'GET' && $action === 'list') {
        $search = trim($_GET['search'] ?? '');
        $params = []; $types = ''; $where = "WHERE role != 'admin'";
        if ($search) {
            $like = "%$search%";
            $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $params = [$like, $like, $like]; $types = 'sss';
        }
        $stmt = $conn->prepare("SELECT id, name, email, phone, address, role,
            (SELECT COUNT(*) FROM bookings WHERE user_id=users.id) AS booking_count,
            (SELECT COUNT(*) FROM bookings WHERE user_id=users.id AND status='done') AS done_count,
            (CASE WHEN EXISTS(SELECT 1 FROM users u2 WHERE u2.id=users.id AND u2.password IS NOT NULL) THEN 1 ELSE 0 END) AS active
            FROM users $where ORDER BY id DESC LIMIT 100");
        if (!$stmt) respond(false, 'DB error: '.$conn->error);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $colRes = $conn->query("SHOW COLUMNS FROM users LIKE 'disabled'");
        $hasDisabled = $colRes && $colRes->num_rows > 0;

        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['booking_count'] = (int)$row['booking_count'];
            $row['done_count'] = (int)$row['done_count'];
            $row['disabled'] = $hasDisabled ? (bool)($row['disabled'] ?? false) : false;
        }
        respond(true, '', ['users' => $rows]);
    }

    if ($method === 'POST' && $action === 'toggle_disable') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid user ID.');
    
        $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS disabled TINYINT(1) NOT NULL DEFAULT 0");
        $stmt = $conn->prepare("UPDATE users SET disabled = NOT disabled WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        // Return new state
        $r = $conn->query("SELECT disabled FROM users WHERE id = $id");
        $dis = (bool)($r ? $r->fetch_row()[0] : false);
        respond(true, $dis ? 'User disabled.' : 'User enabled.', ['disabled' => $dis]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid user ID.');
        $conn->query("DELETE FROM bookings WHERE user_id = $id");
        $conn->query("DELETE FROM notifications WHERE user_id = $id");
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("i", $id); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close();
        respond($ok, $ok ? 'User deleted.' : 'Could not delete user.');
    }

}


if ($section === 'workers') {
    if ($method === 'GET' && $action === 'list') {
        $search = trim($_GET['search'] ?? '');
        $filter = trim($_GET['filter'] ?? '');
        $verificationFilter = trim($_GET['verification_filter'] ?? '');
        $where  = []; $params = []; $types = '';

        $hasVerificationStatus = false;
        $colCheck = $conn->query("SHOW COLUMNS FROM service_providers LIKE 'verification_status'");
        if ($colCheck && $colCheck->num_rows > 0) $hasVerificationStatus = true;
        
        if ($search) {
            $like = "%$search%";
            $where[] = "(full_name LIKE ? OR service_category LIKE ?)";
            $params = [$like, $like]; $types = 'ss';
        }
        if ($filter === 'low_rated') {
            $where[] = "(rating > 0 AND rating < 3.0)";
        }

        if ($verificationFilter === 'pending') {
            $where[] = "(COALESCE(id_picture,'') <> '' OR COALESCE(selfie_verification,'') <> '' OR COALESCE(proof_of_address,'') <> '' OR COALESCE(certificates,'') <> '' OR COALESCE(proof_of_experience,'') <> '')";
            if ($hasVerificationStatus) {
                $where[] = "(verification_status = 'pending' OR verification_status = 'pending_review')";
            } else {
                $where[] = "(is_verified = 0)";
            }
        }
        
        $whereClause = count($where) ? "WHERE " . implode(" AND ", $where) : "";
        $verificationSelect = $hasVerificationStatus
            ? "CASE WHEN verification_status='pending_review' THEN 'pending' WHEN verification_status IS NULL OR verification_status='' THEN CASE WHEN is_verified=1 THEN 'verified' ELSE 'pending' END ELSE verification_status END AS verification_status"
            : "CASE WHEN is_verified=1 THEN 'verified' ELSE 'pending' END AS verification_status";
        $stmt = $conn->prepare("SELECT provider_id AS id, full_name AS name, service_category AS specialty, contact_number AS phone, availability_status AS availability, status, rating, jobs_done, is_verified, $verificationSelect, id_picture, selfie_verification, proof_of_address, certificates, proof_of_experience FROM service_providers $whereClause ORDER BY provider_id DESC");
        
        if (!$stmt) respond(false, $conn->error);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        respond(true, '', ['workers' => $rows]);
    }

    if ($method === 'POST' && ($action === 'edit')) {
        $id           = (int)($_POST['id'] ?? 0);
        $name         = trim($_POST['name'] ?? '');
        $specialty    = trim($_POST['specialty'] ?? '');
        $phone        = trim($_POST['phone'] ?? '');
        $availability = trim($_POST['availability'] ?? 'available');
        $status       = trim($_POST['status'] ?? 'active');
        $rating       = floatval($_POST['rating'] ?? 5.0);
        $jobs_done    = intval($_POST['jobs_done'] ?? 0);

        if (!$name || !$specialty) respond(false, 'Name and specialty required.');
        $validAvail = ['available','busy','offline'];
        $validStat  = ['active','inactive'];
        if (!in_array($availability, $validAvail)) $availability = 'available';
        if (!in_array($status, $validStat)) $status = 'active';

        if (!$id) respond(false, 'Worker ID required.');
        
        $stmt = $conn->prepare("UPDATE service_providers SET full_name=?, service_category=?, contact_number=?, availability_status=?, status=?, rating=?, jobs_done=? WHERE provider_id=?");
        $stmt->bind_param("sssssdii", $name, $specialty, $phone, $availability, $status, $rating, $jobs_done, $id);
        $stmt->execute(); 
        $ok = $stmt->affected_rows >= 0; 
        $stmt->close();
        respond($ok, 'Worker updated.');
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid ID.');
        $stmt = $conn->prepare("DELETE FROM service_providers WHERE provider_id=?");
        $stmt->bind_param("i", $id); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close();
        respond($ok, $ok ? 'Worker deleted.' : 'Not found.');
    }

    if ($method === 'POST' && $action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid ID.');
        $r = $conn->query("SELECT status FROM service_providers WHERE provider_id=$id");
        $cur = $r ? $r->fetch_row()[0] : '';
        $new = $cur === 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE service_providers SET status='$new' WHERE provider_id=$id");
        respond(true, $new === 'active' ? 'Worker activated.' : 'Worker deactivated.', ['status' => $new]);
    }

    if ($method === 'POST' && $action === 'toggle_verification') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid ID.');
        $r = $conn->query("SELECT is_verified FROM service_providers WHERE provider_id=$id");
        $cur = $r ? (int)$r->fetch_row()[0] : 0;
        $new = $cur ? 0 : 1;
        $conn->query("UPDATE service_providers SET is_verified=$new WHERE provider_id=$id");
        respond(true, $new ? 'Worker verified.' : 'Worker unverified.', ['is_verified' => $new]);
    }
}

if ($section === 'services') {
    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        icon VARCHAR(20) DEFAULT '🔧',
        description TEXT,
        hourly_rate DECIMAL(10,2) DEFAULT 0,
        flat_rate DECIMAL(10,2) DEFAULT 0,
        min_hours INT DEFAULT 1,
        pricing_type VARCHAR(20) DEFAULT 'both',
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ($method === 'GET' && $action === 'list') {
        $rows = $conn->query("SELECT * FROM services ORDER BY id ASC");
        $svcs = $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];
        respond(true, '', ['services' => $svcs]);
    }

    if ($method === 'POST' && ($action === 'add' || $action === 'edit')) {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $icon        = trim($_POST['icon'] ?? '🔧');
        $desc        = trim($_POST['description'] ?? '');
        $hourly      = floatval($_POST['hourly_rate'] ?? 0);
        $flat        = floatval($_POST['flat_rate'] ?? 0);
        $min_h       = max(1, intval($_POST['min_hours'] ?? 1));
        $ptype       = trim($_POST['pricing_type'] ?? 'both');
        $active      = intval($_POST['active'] ?? 1);

        if (!$name) respond(false, 'Service name required.');

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO services (name, icon, description, hourly_rate, flat_rate, min_hours, pricing_type, active) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param("sssdddsi", $name, $icon, $desc, $hourly, $flat, $min_h, $ptype, $active);
            $stmt->execute(); $newId = $conn->insert_id; $ok = $stmt->affected_rows > 0; $stmt->close();
            respond($ok, $ok ? 'Service added.' : 'Insert failed.', ['id' => $newId]);
        } else {
            if (!$id) respond(false, 'Service ID required.');
            $stmt = $conn->prepare("UPDATE services SET name=?,icon=?,description=?,hourly_rate=?,flat_rate=?,min_hours=?,pricing_type=?,active=? WHERE id=?");
            $stmt->bind_param("sssdddsi i", $name, $icon, $desc, $hourly, $flat, $min_h, $ptype, $active, $id);
            $stmt->close();
            $stmt = $conn->prepare("UPDATE services SET name=?,icon=?,description=?,hourly_rate=?,flat_rate=?,min_hours=?,pricing_type=?,active=? WHERE id=?");
            $stmt->bind_param("sssddisi", $name, $icon, $desc, $hourly, $flat, $min_h, $ptype, $active);
            $stmt->close();
            $conn->query("UPDATE services SET name='".mysqli_real_escape_string($conn,$name)."',
                icon='".mysqli_real_escape_string($conn,$icon)."',
                description='".mysqli_real_escape_string($conn,$desc)."',
                hourly_rate=$hourly, flat_rate=$flat, min_hours=$min_h,
                pricing_type='".mysqli_real_escape_string($conn,$ptype)."',
                active=$active WHERE id=$id");
            respond(true, 'Service updated.');
        }
    }

    if ($method === 'POST' && $action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid ID.');
        $r = $conn->query("SELECT active FROM services WHERE id=$id");
        $cur = $r ? (int)$r->fetch_row()[0] : 0;
        $new = $cur ? 0 : 1;
        $conn->query("UPDATE services SET active=$new WHERE id=$id");
        respond(true, $new ? 'Service enabled.' : 'Service disabled.', ['active' => (bool)$new]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM services WHERE id=$id");
        respond(true, 'Service deleted.');
    }
}

if ($section === 'offers') {
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

    if ($method === 'GET' && $action === 'list') {
        $rows = $conn->query("SELECT * FROM special_offers ORDER BY created_at DESC");
        respond(true, '', ['offers' => $rows ? $rows->fetch_all(MYSQLI_ASSOC) : []]);
    }

    if ($method === 'POST' && ($action === 'add' || $action === 'edit')) {
        $id       = (int)($_POST['id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        $code     = strtoupper(trim($_POST['code'] ?? ''));
        $desc     = trim($_POST['description'] ?? '');
        $dtype    = trim($_POST['discount_type'] ?? 'percent');
        $dval     = floatval($_POST['discount_value'] ?? 0);
        $minprice = floatval($_POST['min_booking_price'] ?? 0);
        $maxuses  = intval($_POST['max_uses'] ?? 0);
        $expires  = trim($_POST['expires_at'] ?? '') ?: null;
        $active   = intval($_POST['active'] ?? 1);

        if (!$title || !$code) respond(false, 'Title and code required.');
        if (!in_array($dtype, ['percent','flat'])) $dtype = 'percent';

        $esc = fn($v) => mysqli_real_escape_string($conn, $v);
        $expVal = $expires ? "'".mysqli_real_escape_string($conn,$expires)."'" : "NULL";

        if ($action === 'add') {
            $conn->query("INSERT INTO special_offers (title,code,description,discount_type,discount_value,min_booking_price,max_uses,expires_at,active)
                VALUES ('".$esc($title)."','".$esc($code)."','".$esc($desc)."','".$esc($dtype)."',$dval,$minprice,$maxuses,$expVal,$active)");
            $ok = $conn->affected_rows > 0;
            respond($ok, $ok ? 'Offer added.' : ($conn->error ?: 'Duplicate code?'));
        } else {
            if (!$id) respond(false, 'Offer ID required.');
            $conn->query("UPDATE special_offers SET title='".$esc($title)."',code='".$esc($code)."',
                description='".$esc($desc)."',discount_type='".$esc($dtype)."',discount_value=$dval,
                min_booking_price=$minprice,max_uses=$maxuses,expires_at=$expVal,active=$active WHERE id=$id");
            respond(true, 'Offer updated.');
        }
    }

    if ($method === 'POST' && $action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("UPDATE special_offers SET active = NOT active WHERE id=$id");
        $r = $conn->query("SELECT active FROM special_offers WHERE id=$id");
        $new = (bool)($r ? $r->fetch_row()[0] : false);
        respond(true, $new ? 'Offer activated.' : 'Offer deactivated.', ['active' => $new]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $conn->query("DELETE FROM special_offers WHERE id=$id");
        respond(true, 'Offer deleted.');
    }
}

// ── BOOKINGS (admin) ─────────────────────────────────────────────────────────
if ($section === 'bookings') {

    // Ensure technician_id column exists on bookings
    $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS technician_id INT NULL DEFAULT NULL");

    if ($method === 'GET' && $action === 'list') {
        $search    = trim($_GET['search']    ?? '');
        $status    = trim($_GET['status']    ?? 'all');
        $dateFrom  = trim($_GET['date_from'] ?? '');
        $dateTo    = trim($_GET['date_to']   ?? '');
        $service   = trim($_GET['service']   ?? '');
        $workerId  = intval($_GET['worker_id'] ?? 0);

        $where = []; $params = []; $types = '';

        if ($status && $status !== 'all') {
            $where[] = 'b.status = ?'; $params[] = $status; $types .= 's';
        }
        if ($search) {
            $like = "%$search%";
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR b.service LIKE ? OR b.address LIKE ?)';
            $params = array_merge($params, [$like,$like,$like,$like]); $types .= 'ssss';
        }
        if ($dateFrom) { $where[] = 'b.date >= ?'; $params[] = $dateFrom; $types .= 's'; }
        if ($dateTo)   { $where[] = 'b.date <= ?'; $params[] = $dateTo;   $types .= 's'; }
        if ($service)  { $where[] = 'b.service = ?'; $params[] = $service; $types .= 's'; }
        if ($workerId) { $where[] = 'b.technician_id = ?'; $params[] = $workerId; $types .= 'i'; }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT b.id, b.service, b.date, b.time_slot, b.address, b.price, b.status,
                       b.notes, b.technician_id, b.created_at,
                       u.id AS user_id, u.name AS user_name, u.email AS user_email, u.phone AS user_phone,
                       t.provider_id AS tech_id, t.full_name AS technician_name, t.contact_number AS tech_phone,
                       t.service_category AS tech_specialty, t.rating AS tech_rating
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                LEFT JOIN service_providers t ON b.technician_id = t.provider_id
                $whereClause
                ORDER BY b.created_at DESC LIMIT 200";

        $stmt = $conn->prepare($sql);
        if (!$stmt) respond(false, 'DB error: ' . $conn->error);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        respond(true, '', ['bookings' => $rows]);
    }

    if ($method === 'POST' && $action === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        $valid  = ['pending','progress','done','cancelled'];
        if (!$id || !in_array($status, $valid)) respond(false, 'Invalid data.');
        $conn->query("UPDATE bookings SET status='$status' WHERE id=$id");
        respond(true, 'Status updated.');
    }

    if ($method === 'POST' && $action === 'assign_worker') {
        $bookingId = (int)($_POST['booking_id'] ?? 0);
        $workerId  = (int)($_POST['worker_id']  ?? 0);
        if (!$bookingId || !$workerId) respond(false, 'Booking and worker IDs required.');

        // Ensure column exists
        $conn->query("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS technician_id INT NULL DEFAULT NULL");

        $stmt = $conn->prepare("UPDATE bookings SET technician_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $workerId, $bookingId);
        $stmt->execute(); $ok = $stmt->affected_rows >= 0; $stmt->close();

        // Also update booking_requests if exists
        $conn->query("UPDATE booking_requests SET status='accepted' WHERE booking_id=$bookingId AND provider_id=$workerId AND status='pending'");
        $conn->query("UPDATE booking_requests SET status='closed' WHERE booking_id=$bookingId AND provider_id<>$workerId AND status='pending'");

        // Fetch worker name for response
        $r = $conn->query("SELECT full_name AS name, contact_number AS phone, service_category AS specialty, rating FROM service_providers WHERE provider_id=$workerId");
        $worker = $r ? $r->fetch_assoc() : null;
        respond($ok, $ok ? 'Worker assigned.' : 'Failed.', ['worker' => $worker]);
    }

    if ($method === 'POST' && $action === 'cancel') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid booking ID.');
        $conn->query("UPDATE bookings SET status='cancelled' WHERE id=$id");
        respond(true, 'Booking cancelled.');
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid booking ID.');
        $conn->query("DELETE FROM bookings WHERE id=$id");
        respond(true, 'Booking deleted.');
    }
}

if ($section === 'reviews') {
    if ($method === 'GET' && $action === 'list') {
        $stmt = $conn->prepare("
            SELECT r.id, r.rating, r.comment, r.created_at, 
                   u.name AS user_name, u.email AS user_email, 
                   sp.full_name AS provider_name
            FROM provider_reviews r
            LEFT JOIN users u ON r.user_id = u.id
            LEFT JOIN service_providers sp ON r.provider_id = sp.provider_id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        respond(true, '', ['reviews' => $rows]);
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid review ID.');
        
        // Find provider_id before deleting
        $stmt = $conn->prepare("SELECT provider_id FROM provider_reviews WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$r) respond(false, 'Review not found.');
        $provider_id = $r['provider_id'];

        // Delete review
        $stmt2 = $conn->prepare("DELETE FROM provider_reviews WHERE id=?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $ok = $stmt2->affected_rows > 0;
        $stmt2->close();

        if ($ok) {
            // Recalculate rating
            $upStmt = $conn->prepare("
                UPDATE service_providers sp 
                LEFT JOIN (
                    SELECT provider_id, ROUND(AVG(rating), 1) as avg_rating 
                    FROM provider_reviews 
                    WHERE provider_id = ?
                ) AS r ON r.provider_id = sp.provider_id 
                SET sp.rating = COALESCE(r.avg_rating, 0)
                WHERE sp.provider_id = ?
            ");
            if ($upStmt) {
                $upStmt->bind_param("ii", $provider_id, $provider_id);
                $upStmt->execute();
                $upStmt->close();
            }
        }
        
        respond($ok, $ok ? 'Review deleted.' : 'Failed to delete review.');
    }
}

// ── ANALYTICS ─────────────────────────────────────────────────────────────────
if ($section === 'analytics') {
    $data = [];

    // 1. Daily bookings trend (last 30 days)
    $dailyBookings = [];
    $res = $conn->query("
        SELECT DATE(created_at) AS day, COUNT(*) AS cnt
        FROM bookings
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY day ORDER BY day ASC
    ");
    // Fill in missing days with 0
    $dayMap = [];
    if ($res) while ($r = $res->fetch_assoc()) $dayMap[$r['day']] = (int)$r['cnt'];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $dailyBookings[] = ['day' => date('M d', strtotime($d)), 'count' => $dayMap[$d] ?? 0];
    }
    $data['daily_bookings'] = $dailyBookings;

    // 2. Service distribution (pie/doughnut)
    $svcDist = [];
    $res = $conn->query("SELECT service, COUNT(*) AS cnt FROM bookings GROUP BY service ORDER BY cnt DESC");
    if ($res) while ($r = $res->fetch_assoc()) $svcDist[] = ['name' => $r['service'], 'count' => (int)$r['cnt']];
    $data['service_distribution'] = $svcDist;

    // 3. Weekly revenue (last 8 weeks)
    $weeklyRev = [];
    $res = $conn->query("
        SELECT YEARWEEK(created_at, 1) AS yw,
               MIN(DATE(created_at)) AS week_start,
               COALESCE(SUM(price), 0) AS rev,
               COUNT(*) AS cnt
        FROM bookings
        WHERE status = 'done' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
        GROUP BY yw ORDER BY yw ASC
    ");
    if ($res) while ($r = $res->fetch_assoc()) {
        $weeklyRev[] = [
            'week' => date('M d', strtotime($r['week_start'])),
            'revenue' => (float)$r['rev'],
            'bookings' => (int)$r['cnt']
        ];
    }
    $data['weekly_revenue'] = $weeklyRev;

    // 4. Top workers by jobs done
    $topWorkers = [];
    $res = $conn->query("
        SELECT full_name AS name, jobs_done, rating, service_category AS specialty
        FROM service_providers
        WHERE status = 'active'
        ORDER BY jobs_done DESC LIMIT 8
    ");
    if ($res) while ($r = $res->fetch_assoc()) {
        $topWorkers[] = [
            'name' => $r['name'],
            'jobs' => (int)$r['jobs_done'],
            'rating' => round((float)$r['rating'], 1),
            'specialty' => $r['specialty']
        ];
    }
    $data['top_workers'] = $topWorkers;

    // 5. Status breakdown for current month vs last month
    $thisMonth = [];
    $res = $conn->query("
        SELECT status, COUNT(*) AS cnt
        FROM bookings
        WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
        GROUP BY status
    ");
    if ($res) while ($r = $res->fetch_assoc()) $thisMonth[$r['status']] = (int)$r['cnt'];

    $lastMonth = [];
    $res = $conn->query("
        SELECT status, COUNT(*) AS cnt
        FROM bookings
        WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
          AND YEAR(created_at)  = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
        GROUP BY status
    ");
    if ($res) while ($r = $res->fetch_assoc()) $lastMonth[$r['status']] = (int)$r['cnt'];

    $thisTotal = array_sum($thisMonth);
    $lastTotal = array_sum($lastMonth);
    $data['monthly_comparison'] = [
        'this_month' => $thisMonth,
        'last_month' => $lastMonth,
        'this_total' => $thisTotal,
        'last_total' => $lastTotal,
        'growth_pct' => $lastTotal > 0 ? round(($thisTotal - $lastTotal) / $lastTotal * 100, 1) : ($thisTotal > 0 ? 100 : 0)
    ];

    // 6. Hourly booking heatmap (which hours are busiest)
    $hourly = [];
    $res = $conn->query("
        SELECT HOUR(created_at) AS hr, COUNT(*) AS cnt
        FROM bookings
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY hr ORDER BY hr ASC
    ");
    $hrMap = array_fill(0, 24, 0);
    if ($res) while ($r = $res->fetch_assoc()) $hrMap[(int)$r['hr']] = (int)$r['cnt'];
    for ($h = 6; $h <= 21; $h++) {
        $hourly[] = ['hour' => sprintf('%02d:00', $h), 'count' => $hrMap[$h]];
    }
    $data['hourly_heatmap'] = $hourly;

    respond(true, '', ['analytics' => $data]);
}

// ── ADMIN NOTIFICATIONS ───────────────────────────────────────────────────────
if ($section === 'admin_notifications') {
    // Ensure table exists
    $conn->query("CREATE TABLE IF NOT EXISTS admin_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL DEFAULT 'general',
        title VARCHAR(200) NOT NULL,
        message TEXT,
        reference_id INT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if ($method === 'GET' && $action === 'list') {
        $rows = $conn->query("
            SELECT an.*, sp.full_name AS provider_name, sp.service_category, sp.is_verified
            FROM admin_notifications an
            LEFT JOIN service_providers sp ON an.reference_id = sp.provider_id AND an.type = 'verification'
            ORDER BY an.created_at DESC
            LIMIT 50
        ");
        $notifications = $rows ? $rows->fetch_all(MYSQLI_ASSOC) : [];

        $unreadRes = $conn->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
        $unread = $unreadRes ? (int)$unreadRes->fetch_row()[0] : 0;

        respond(true, '', ['notifications' => $notifications, 'unread_count' => $unread]);
    }

    if ($method === 'GET' && $action === 'count') {
        $unreadRes = $conn->query("SELECT COUNT(*) FROM admin_notifications WHERE is_read = 0");
        $unread = $unreadRes ? (int)$unreadRes->fetch_row()[0] : 0;
        respond(true, '', ['unread_count' => $unread]);
    }

    if ($method === 'POST' && $action === 'mark_read') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE id = $id");
        } else {
            $conn->query("UPDATE admin_notifications SET is_read = 1 WHERE is_read = 0");
        }
        respond(true, 'Marked as read.');
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $conn->query("DELETE FROM admin_notifications WHERE id = $id");
            respond(true, 'Notification deleted.');
        }
        respond(false, 'Invalid ID.');
    }
}

respond(false, 'Unknown request.');

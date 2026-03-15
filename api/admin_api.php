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

    $r = $conn->query("SELECT COUNT(*) FROM technicians WHERE status='active'");
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

    if ($method === 'POST' && $action === 'send_notification') {
        $uid   = (int)($_POST['user_id'] ?? 0); // 0 = all users
        $title = trim($_POST['title'] ?? '');
        $msg   = trim($_POST['message'] ?? '');
        $icon  = trim($_POST['icon'] ?? 'cleaning');
        if (!$title || !$msg) respond(false, 'Title and message required.');

        if ($uid > 0) {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?,?,?,?,0,NOW())");
            $stmt->bind_param("isss", $uid, $title, $msg, $icon); $stmt->execute(); $cnt = $stmt->affected_rows; $stmt->close();
        } else {
            $uids = $conn->query("SELECT id FROM users WHERE role != 'admin'");
            $cnt = 0;
            if ($uids) {
                $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?,?,?,?,0,NOW())");
                while ($u = $uids->fetch_row()) {
                    $stmt->bind_param("isss", $u[0], $title, $msg, $icon); $stmt->execute(); $cnt++;
                }
                $stmt->close();
            }
        }
        respond(true, "Notification sent to $cnt user(s).");
    }
}

if ($section === 'bookings') {
    if ($method === 'GET' && $action === 'list') {
        $status = trim($_GET['status'] ?? '');
        $search = trim($_GET['search'] ?? '');
        $where = ''; $params = []; $types = '';

        $conditions = [];
        if ($status && $status !== 'all') { $conditions[] = "b.status = ?"; $params[] = $status; $types .= 's'; }
        if ($search) {
            $like = "%$search%";
            $conditions[] = "(u.name LIKE ? OR b.service LIKE ? OR b.address LIKE ?)";
            $params = array_merge($params, [$like, $like, $like]); $types .= 'sss';
        }
        if ($conditions) $where = 'WHERE ' . implode(' AND ', $conditions);

        $colRes = $conn->query("SHOW COLUMNS FROM bookings");
        $bcols = [];
        if ($colRes) while ($c = $colRes->fetch_assoc()) $bcols[] = $c['Field'];

        $extraSel = '';
        if (in_array('time_slot', $bcols))    $extraSel .= ', b.time_slot';
        if (in_array('notes', $bcols))        $extraSel .= ', b.notes';
        if (in_array('pricing_type', $bcols)) $extraSel .= ', b.pricing_type';
        if (in_array('hours', $bcols))        $extraSel .= ', b.hours';
        $techJoin = in_array('technician_id', $bcols)
            ? "LEFT JOIN technicians t ON b.technician_id = t.id" : "";
        $techSel  = in_array('technician_id', $bcols)
            ? ", t.name AS technician_name, b.technician_id" : ", NULL AS technician_name, NULL AS technician_id";

        $sql = "SELECT b.id, b.user_id, b.service, b.date, b.address, b.price, b.status, b.created_at,
                u.name AS user_name, u.phone AS user_phone$extraSel$techSel
                FROM bookings b
                LEFT JOIN users u ON b.user_id = u.id
                $techJoin $where
                ORDER BY b.created_at DESC LIMIT 200";

        $stmt = $conn->prepare($sql);
        if (!$stmt) respond(false, 'DB: '.$conn->error);
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
        if (!$id || !in_array($status, $valid)) respond(false, 'Invalid parameters.');
        $stmt = $conn->prepare("UPDATE bookings SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close();
 
        if ($ok) {
            $r = $conn->query("SELECT user_id, service FROM bookings WHERE id=$id");
            if ($row = ($r ? $r->fetch_assoc() : null)) {
                $labels = ['pending'=>'received','progress'=>'started','done'=>'completed','cancelled'=>'cancelled'];
                $notifMsg = "Your {$row['service']} booking has been {$labels[$status]}.";
                $ns = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Update', ?, 'cleaning', 0, NOW())");
                $ns->bind_param("is", $row['user_id'], $notifMsg); $ns->execute(); $ns->close();
            }
        }
        respond($ok, $ok ? 'Status updated.' : 'Update failed.');
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid ID.');
        $stmt = $conn->prepare("DELETE FROM bookings WHERE id=?");
        $stmt->bind_param("i", $id); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close();
        respond($ok, $ok ? 'Booking deleted.' : 'Not found.');
    }
}

if ($section === 'workers') {
    if ($method === 'GET' && $action === 'list') {
        $search = trim($_GET['search'] ?? '');
        $where  = ''; $params = []; $types = '';
        if ($search) {
            $like = "%$search%";
            $where = "WHERE name LIKE ? OR specialty LIKE ?";
            $params = [$like, $like]; $types = 'ss';
        }
        $stmt = $conn->prepare("SELECT * FROM technicians $where ORDER BY id DESC");
        if (!$stmt) respond(false, $conn->error);
        if ($params) $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        respond(true, '', ['workers' => $rows]);
    }

    if ($method === 'POST' && ($action === 'add' || $action === 'edit')) {
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

        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO technicians (name, specialty, phone, availability, status, rating, jobs_done) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("sssssdi", $name, $specialty, $phone, $availability, $status, $rating, $jobs_done);
            $stmt->execute(); $newId = $conn->insert_id; $ok = $stmt->affected_rows > 0; $stmt->close();
            respond($ok, $ok ? 'Worker added.' : 'Insert failed.', ['id' => $newId]);
        } else {
            if (!$id) respond(false, 'Worker ID required.');
            $stmt = $conn->prepare("UPDATE technicians SET name=?, specialty=?, phone=?, availability=?, status=?, rating=?, jobs_done=? WHERE id=?");
            $stmt->bind_param("sssssdi i", $name, $specialty, $phone, $availability, $status, $rating, $jobs_done, $id);
 
            $stmt->close();
            $stmt = $conn->prepare("UPDATE technicians SET name=?, specialty=?, phone=?, availability=?, status=?, rating=?, jobs_done=? WHERE id=?");
            $stmt->bind_param("sssssdii", $name, $specialty, $phone, $availability, $status, $rating, $jobs_done, $id);
            $stmt->execute(); $ok = $stmt->affected_rows >= 0; $stmt->close();
            respond($ok, 'Worker updated.');
        }
    }

    if ($method === 'POST' && $action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid ID.');
        $stmt = $conn->prepare("DELETE FROM technicians WHERE id=?");
        $stmt->bind_param("i", $id); $stmt->execute(); $ok = $stmt->affected_rows > 0; $stmt->close();
        respond($ok, $ok ? 'Worker deleted.' : 'Not found.');
    }

    if ($method === 'POST' && $action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) respond(false, 'Invalid ID.');
        $r = $conn->query("SELECT status FROM technicians WHERE id=$id");
        $cur = $r ? $r->fetch_row()[0] : '';
        $new = $cur === 'active' ? 'inactive' : 'active';
        $conn->query("UPDATE technicians SET status='$new' WHERE id=$id");
        respond(true, $new === 'active' ? 'Worker activated.' : 'Worker deactivated.', ['status' => $new]);
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

respond(false, 'Unknown request.');

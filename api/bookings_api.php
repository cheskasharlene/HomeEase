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

// Ensure services table is seeded with correct data
_seedServices($conn);

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
        $stmt = $conn->prepare("SELECT provider_id AS id, full_name AS name, service_category AS specialty, contact_number AS phone, address, availability_status AS availability, rating, jobs_done, status FROM service_providers WHERE status='active' AND service_category LIKE ? ORDER BY full_name ASC");
        $stmt->bind_param("s", $like);
    } else {
        $stmt = $conn->prepare("SELECT provider_id AS id, full_name AS name, service_category AS specialty, contact_number AS phone, address, availability_status AS availability, rating, jobs_done, status FROM service_providers WHERE status='active' ORDER BY full_name ASC");
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

    $conn->query("CREATE TABLE IF NOT EXISTS provider_reviews (
        id INT AUTO_INCREMENT PRIMARY KEY, booking_id INT NOT NULL,
        provider_id INT NOT NULL, user_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_unique_booking_review (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    if (in_array('technician_id', $cols)) {
        $stmt = $conn->prepare(
            "SELECT b.*, t.full_name AS technician_name, t.contact_number AS tech_phone,
             (CASE WHEN r.id IS NOT NULL THEN 1 ELSE 0 END) AS has_reviewed
             FROM bookings b
             LEFT JOIN service_providers t ON b.technician_id = t.provider_id
             LEFT JOIN provider_reviews r ON b.id = r.booking_id
             WHERE b.user_id = ?
             ORDER BY b.created_at DESC"
        );
    } else {
        $stmt = $conn->prepare(
            "SELECT *, NULL AS technician_name, NULL AS tech_phone, 0 AS has_reviewed
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
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? $address);
    $pricing_type = 'flat';
    $hours = 1;
    $tech_id = isset($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
    if ($tech_id <= 0) $tech_id = null;

    if (!$service || !$date || !$address) {
        echo json_encode(['success' => false, 'message' => 'Service, date, and address are required.']);
        exit;
    }

    $svcStmt = $conn->prepare("SELECT name, flat_rate, description, min_hours FROM services WHERE active = 1 AND name = ? LIMIT 1");
    if (!$svcStmt) {
        echo json_encode(['success' => false, 'message' => 'Could not validate service.']);
        exit;
    }
    $svcStmt->bind_param("s", $service);
    $svcStmt->execute();
    $serviceRow = $svcStmt->get_result()->fetch_assoc();
    $svcStmt->close();

    if (!$serviceRow) {
        echo json_encode(['success' => false, 'message' => 'Selected service is unavailable.']);
        exit;
    }

    $computed = _computeFixedPrice($service, $_POST);
    $price = (float) ($computed['total'] ?? 0);
    if ($price <= 0) {
        echo json_encode(['success' => false, 'message' => 'Could not compute fixed price for selected options.']);
        exit;
    }

    $optionSummary = _summarizeSelectedOptions($service, $_POST);
    if ($optionSummary !== '') {
        $notes = trim($notes) === ''
            ? $optionSummary
            : ($notes . "\n\n" . $optionSummary);
    }

    $serviceInclusions = trim((string) ($serviceRow['description'] ?? ''));
    $estimatedDuration = max(1, (int) ($serviceRow['min_hours'] ?? 1));

    if ($customer_name === '' || $customer_phone === '') {
        $uStmt = $conn->prepare("SELECT name, phone, address FROM users WHERE id = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param('i', $uid);
            $uStmt->execute();
            $u = $uStmt->get_result()->fetch_assoc();
            $uStmt->close();
            if ($customer_name === '') {
                $customer_name = (string) ($u['name'] ?? '');
            }
            if ($customer_phone === '') {
                $customer_phone = (string) ($u['phone'] ?? '');
            }
            if ($customer_address === '') {
                $customer_address = (string) ($u['address'] ?? $address);
            }
        }
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

        ensureBookingRequestsTable($conn);

        $providers = [];
        if ($tech_id) {
            $providers = [['id' => $tech_id, 'name' => 'Selected Technician']];
        } else {
            $providerStmt = $conn->prepare(
                "SELECT provider_id AS id, full_name AS name FROM service_providers
                 WHERE status = 'active'
                   AND LOWER(availability_status) <> 'unavailable'
                   AND LOWER(service_category) LIKE ?
                 ORDER BY rating DESC, jobs_done DESC, provider_id ASC
                 LIMIT 5"
            );
            $specialtyLike = '%' . strtolower($service) . '%';
            if ($providerStmt) {
                $providerStmt->bind_param('s', $specialtyLike);
                $providerStmt->execute();
                $providers = $providerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $providerStmt->close();
            }

            if (empty($providers)) {
                $fallbackStmt = $conn->prepare(
                    "SELECT provider_id AS id, full_name AS name FROM service_providers
                     WHERE status = 'active' AND LOWER(availability_status) <> 'unavailable'
                     ORDER BY rating DESC, jobs_done DESC, provider_id ASC
                     LIMIT 5"
                );
                if ($fallbackStmt) {
                    $fallbackStmt->execute();
                    $providers = $fallbackStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    $fallbackStmt->close();
                }
            }
        }

        if (!empty($providers)) {
            $reqStmt = $conn->prepare(
                "INSERT INTO booking_requests
                (booking_id, provider_id, service, fixed_price, date, time_slot, address, details, customer_name, customer_phone, customer_address, status, created_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 5 MINUTE))"
            );
            if ($reqStmt) {
                foreach ($providers as $p) {
                    $pid = (int) ($p['id'] ?? 0);
                    if ($pid <= 0) {
                        continue;
                    }
                    $reqStmt->bind_param(
                        'iisdsssssss',
                        $bid,
                        $pid,
                        $service,
                        $price,
                        $date,
                        $time_slot,
                        $address,
                        $notes,
                        $customer_name,
                        $customer_phone,
                        $customer_address
                    );
                    $reqStmt->execute();
                }
                $reqStmt->close();
            }
        }

        $msg = "Your $service booking on $date has been received.";
        $icon = _svcIcon($service);
        $ns = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Received', ?, ?, 0, NOW())");
        if ($ns) {
            $ns->bind_param("iss", $uid, $msg, $icon);
            $ns->execute();
            $ns->close();
        }
        echo json_encode([
            'success' => true,
            'booking_id' => $bid,
            'fixed_price' => $price,
            'status' => 'pending',
            'matched_providers' => count($providers),
            'waiting_message' => 'Waiting for a provider to accept your booking…',
            'service_inclusions' => $serviceInclusions,
            'estimated_duration' => $estimatedDuration,
            'price_breakdown' => $computed['breakdown']
        ]);
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

function _seedServices(mysqli $conn)
{
    // Create services table if it doesn't exist
    $conn->query("CREATE TABLE IF NOT EXISTS services (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL UNIQUE,
        icon VARCHAR(10),
        description TEXT,
        hourly_rate DECIMAL(10,2),
        flat_rate DECIMAL(10,2),
        min_hours INT,
        pricing_type VARCHAR(20),
        active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    
    // Check if we already have the new services
    $checkStmt = $conn->query("SELECT COUNT(*) as cnt FROM services WHERE name = 'Cleaner' AND active = 1");
    if ($checkStmt) {
        $row = $checkStmt->fetch_assoc();
        if ($row && $row['cnt'] > 0) {
            return; // Already seeded with new services
        }
    }
    
    // Delete old services and insert new ones
    $conn->query("DELETE FROM services");
    $conn->query("ALTER TABLE services AUTO_INCREMENT = 1");
    
    $services = [
        ['Cleaner', '🧹', 'Complete home & office cleaning', 400, 500, 1, 'flat'],
        ['Helper', '🧑‍🤝‍🧑', 'All-around household helping', 400, 400, 1, 'flat'],
        ['Laundry Worker', '🧺', 'Washing, drying & folding', 300, 300, 1, 'flat'],
        ['Plumber', '🔧', 'Pipe repair, clogs & installs', 400, 500, 1, 'flat'],
        ['Carpenter', '🔨', 'Furniture making & wood repairs', 600, 600, 1, 'flat'],
        ['Appliance Technician', '🔩', 'Appliance repairs & diagnostics', 400, 500, 1, 'flat'],
    ];
    
    $stmt = $conn->prepare("INSERT INTO services (name, icon, description, hourly_rate, flat_rate, min_hours, pricing_type, active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    if ($stmt) {
        foreach ($services as $svc) {
            $name = $svc[0];
            $icon = $svc[1];
            $desc = $svc[2];
            $hrate = $svc[3];
            $frate = $svc[4];
            $mhours = $svc[5];
            $ptype = $svc[6];
            $stmt->bind_param("sssddis", $name, $icon, $desc, $hrate, $frate, $mhours, $ptype);
            if (!$stmt->execute()) {
                error_log("Failed to insert service $name: " . $stmt->error);
            }
        }
        $stmt->close();
    }
}

function _svcIcon($s)
{
    $m = [
        'Cleaner' => 'cleaner',
        'Helper' => 'helper',
        'Laundry Worker' => 'laundry',
        'Plumber' => 'plumber',
        'Carpenter' => 'carpenter',
        'Appliance Technician' => 'appliance'
    ];
    return $m[$s] ?? 'cleaner';
}

function _defaultServices()
{
    return [
        ['id' => 1, 'name' => 'Cleaner', 'icon' => '🧹', 'description' => 'Complete home & office cleaning', 'hourly_rate' => 400, 'flat_rate' => 500, 'min_hours' => 1, 'pricing_type' => 'flat', 'active' => 1],
        ['id' => 2, 'name' => 'Helper', 'icon' => '🧑‍🤝‍🧑', 'description' => 'All-around household helping', 'hourly_rate' => 400, 'flat_rate' => 400, 'min_hours' => 1, 'pricing_type' => 'flat', 'active' => 1],
        ['id' => 3, 'name' => 'Laundry Worker', 'icon' => '🧺', 'description' => 'Washing, drying & folding', 'hourly_rate' => 300, 'flat_rate' => 300, 'min_hours' => 1, 'pricing_type' => 'flat', 'active' => 1],
        ['id' => 4, 'name' => 'Plumber', 'icon' => '🔧', 'description' => 'Pipe repair, clogs & installs', 'hourly_rate' => 400, 'flat_rate' => 500, 'min_hours' => 1, 'pricing_type' => 'flat', 'active' => 1],
        ['id' => 5, 'name' => 'Carpenter', 'icon' => '🔨', 'description' => 'Furniture making & wood repairs', 'hourly_rate' => 600, 'flat_rate' => 600, 'min_hours' => 1, 'pricing_type' => 'flat', 'active' => 1],
        ['id' => 6, 'name' => 'Appliance Technician', 'icon' => '🔩', 'description' => 'Appliance repairs & diagnostics', 'hourly_rate' => 400, 'flat_rate' => 500, 'min_hours' => 1, 'pricing_type' => 'flat', 'active' => 1],
    ];
}

function ensureBookingRequestsTable(mysqli $conn)
{
    $sql = "CREATE TABLE IF NOT EXISTS booking_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        provider_id INT NOT NULL,
        service VARCHAR(120) NOT NULL,
        fixed_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        date DATE NULL,
        time_slot VARCHAR(32) NULL,
        address VARCHAR(255) NULL,
        details TEXT NULL,
        customer_name VARCHAR(120) NULL,
        customer_phone VARCHAR(40) NULL,
        customer_address VARCHAR(255) NULL,
        status ENUM('pending','accepted','declined','closed') NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL,
        responded_at DATETIME NULL,
        INDEX idx_provider_status (provider_id, status),
        INDEX idx_booking (booking_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

function _asInt($value, $fallback = 0)
{
    if (!is_numeric($value)) {
        return (int) $fallback;
    }
    return (int) $value;
}

function _parseCsvValues($value)
{
    if (is_array($value)) {
        return array_values(array_filter(array_map('trim', $value), static function ($v) {
            return $v !== '';
        }));
    }

    $raw = trim((string) $value);
    if ($raw === '') {
        return [];
    }

    $parts = explode(',', $raw);
    return array_values(array_filter(array_map('trim', $parts), static function ($v) {
        return $v !== '';
    }));
}

function _computeFixedPrice($service, $data)
{
    $total = 0;
    $breakdown = [];

    if ($service === 'Cleaner') {
        $total = 500;
        $breakdown[] = 'Base: 500';

        $typeAdd = ['General' => 0, 'Deep Cleaning' => 500, 'Move-in/out' => 700];
        $propertyAdd = ['Condo/Apartment' => 0, 'House' => 200];

        $cleanType = (string) ($data['cleaning_type'] ?? 'General');
        $propertyType = (string) ($data['property_type'] ?? 'Condo/Apartment');
        $rooms = max(0, _asInt($data['num_rooms'] ?? 1, 1));
        $bathrooms = max(0, _asInt($data['num_bathrooms'] ?? 1, 1));

        $total += ($typeAdd[$cleanType] ?? 0);
        $total += ($propertyAdd[$propertyType] ?? 0);
        $total += $rooms * 100;
        $total += $bathrooms * 150;
    } elseif ($service === 'Helper') {
        $total = 400;
        $breakdown[] = 'Base: 400';

        $tasks = _parseCsvValues($data['helper_tasks'] ?? []);
        $hours = max(1, _asInt($data['helper_hours'] ?? 4, 4));

        $taskAdd = 0;
        foreach($tasks as $t) {
            $taskAdd += (['Cleaning' => 100, 'Cooking' => 150, 'Childcare' => 200, 'General Errands' => 100][$t] ?? 0);
        }
        $total += $taskAdd;
        $total += ($hours > 4) ? (($hours-4)*100) : 0; // extra hours over 4
    } elseif ($service === 'Laundry Worker') {
        $total = 300;
        $breakdown[] = 'Base: 300';
        
        $tasks = _parseCsvValues($data['laundry_services'] ?? []);
        $taskAdd = 0;
        foreach($tasks as $t) {
            $taskAdd += (['Wash & Dry' => 100, 'Fold' => 100, 'Iron' => 150][$t] ?? 0);
        }
        
        $kilos = (string)($data['laundry_kilos'] ?? 'Under 5kg');
        $kiloAdd = (['Under 5kg' => 0, '5-10kg' => 200, 'Over 10kg' => 400][$kilos] ?? 0);
        
        $total += $taskAdd + $kiloAdd;
    } elseif ($service === 'Plumber') {
        $total = 500;
        $breakdown[] = 'Base: 500';

        $issue = (string) ($data['issue_type'] ?? 'Leak');
        $location = (string) ($data['issue_location'] ?? 'Kitchen');
        $urgency = (string) ($data['urgency'] ?? 'Normal');

        $total += (['Leak' => 300, 'Clog' => 300, 'Installation' => 800][$issue] ?? 0);
        $total += (['Kitchen' => 0, 'Bathroom' => 100, 'Outdoor' => 150][$location] ?? 0);
        $total += ($urgency === 'Urgent') ? 300 : 0;
    } elseif ($service === 'Carpenter') {
        $total = 600;
        $breakdown[] = 'Base: 600';

        $task = (string) ($data['carpentry_task'] ?? 'Repairs');
        $complexity = (string)($data['complexity'] ?? 'Simple');

        $total += (['Repairs' => 0, 'Furniture Making' => 500, 'Installation' => 300][$task] ?? 0);
        $total += (['Simple' => 0, 'Complex' => 500][$complexity] ?? 0);
    } elseif ($service === 'Appliance Technician') {
        $total = 500;
        $breakdown[] = 'Base: 500';

        $appliance = (string) ($data['appliance_type'] ?? 'TV');
        $severity = (string) ($data['problem_severity'] ?? 'Minor');
        $urgency = (string) ($data['urgency_level'] ?? 'Normal');

        $total += (['Aircon' => 500, 'Ref' => 400, 'Washing Machine' => 400, 'TV' => 300, 'Other' => 200][$appliance] ?? 0);
        $total += (['Minor' => 300, 'Major' => 800][$severity] ?? 0);
        $total += ($urgency === 'Urgent') ? 300 : 0;
    }

    return [
        'total' => max(0, (float) $total),
        'breakdown' => $breakdown
    ];
}

function _summarizeSelectedOptions($service, $data)
{
    $pairs = [];

    if ($service === 'Cleaner') {
        $pairs[] = 'Cleaning Type: ' . ((string) ($data['cleaning_type'] ?? 'General'));
        $pairs[] = 'Property Type: ' . ((string) ($data['property_type'] ?? 'Condo/Apartment'));
        $pairs[] = 'Rooms: ' . max(0, _asInt($data['num_rooms'] ?? 1, 1));
        $pairs[] = 'Bathrooms: ' . max(0, _asInt($data['num_bathrooms'] ?? 1, 1));
    } elseif ($service === 'Helper') {
        $tasks = _parseCsvValues($data['helper_tasks'] ?? []);
        $pairs[] = 'Tasks: ' . (empty($tasks) ? 'None' : implode(', ', $tasks));
        $pairs[] = 'Hours: ' . max(1, _asInt($data['helper_hours'] ?? 4, 4));
    } elseif ($service === 'Laundry Worker') {
        $tasks = _parseCsvValues($data['laundry_services'] ?? []);
        $pairs[] = 'Tasks: ' . (empty($tasks) ? 'None' : implode(', ', $tasks));
        $pairs[] = 'Load size: ' . ((string) ($data['laundry_kilos'] ?? 'Under 5kg'));
    } elseif ($service === 'Plumber') {
        $pairs[] = 'Issue Type: ' . ((string) ($data['issue_type'] ?? 'Leak'));
        $pairs[] = 'Location: ' . ((string) ($data['issue_location'] ?? 'Kitchen'));
        $pairs[] = 'Urgency: ' . ((string) ($data['urgency'] ?? 'Normal'));
    } elseif ($service === 'Carpenter') {
        $pairs[] = 'Task: ' . ((string) ($data['carpentry_task'] ?? 'Repairs'));
        $pairs[] = 'Complexity: ' . ((string) ($data['complexity'] ?? 'Simple'));
    } elseif ($service === 'Appliance Technician') {
        $pairs[] = 'Appliance: ' . ((string) ($data['appliance_type'] ?? 'TV'));
        $pairs[] = 'Severity: ' . ((string) ($data['problem_severity'] ?? 'Minor'));
        $pairs[] = 'Urgency: ' . ((string) ($data['urgency_level'] ?? 'Normal'));
    }

    if (empty($pairs)) {
        return '';
    }

    return "Selected Options:\n- " . implode("\n- ", $pairs);
}

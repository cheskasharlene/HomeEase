<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

define('GROQ_API_KEY', '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');
define('GROQ_MODEL', 'llama-3.3-70b-versatile');

require_once __DIR__ . '/db.php';

$uid = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? [];

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Empty message.']);
    exit;
}

function getColumns($conn, $table)
{
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($r)
        while ($c = $r->fetch_assoc())
            $cols[] = $c['Field'];
    return $cols;
}

$user = ['name' => 'User', 'email' => '', 'phone' => '', 'address' => ''];
try {
    $uStmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
    if ($uStmt) {
        $uStmt->bind_param("i", $uid);
        $uStmt->execute();
        $row = $uStmt->get_result()->fetch_assoc();
        if ($row)
            $user = $row;
        $uStmt->close();
    }
} catch (Exception $e) {
}

$bookings = [];
try {
    $bCols = getColumns($conn, 'bookings');
    $select = ['b.id', 'b.service', 'b.date', 'b.status', 'b.price', 'b.address'];
    if (in_array('time_slot', $bCols))
        $select[] = 'b.time_slot';
    if (in_array('notes', $bCols))
        $select[] = 'b.notes';
    if (in_array('pricing_type', $bCols))
        $select[] = 'b.pricing_type';
    if (in_array('hours', $bCols))
        $select[] = 'b.hours';
    if (in_array('technician_id', $bCols)) {
        $select[] = 't.name AS technician_name';
        $join = "LEFT JOIN technicians t ON b.technician_id = t.id";
    } else {
        $join = "";
        $select[] = "NULL AS technician_name";
    }
    $selectStr = implode(', ', $select);
    $sql = "SELECT $selectStr FROM bookings b $join WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 20";
    $bStmt = $conn->prepare($sql);
    if ($bStmt) {
        $bStmt->bind_param("i", $uid);
        $bStmt->execute();
        $bookings = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $bStmt->close();
    }
} catch (Exception $e) {
}

$unreadNotifs = 0;
try {
    $nStmt = $conn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    if ($nStmt) {
        $nStmt->bind_param("i", $uid);
        $nStmt->execute();
        $unreadNotifs = (int) $nStmt->get_result()->fetch_row()[0];
        $nStmt->close();
    }
} catch (Exception $e) {
}

$offers = [];
try {
    $now = date('Y-m-d H:i:s');
    $oRes = $conn->query("SELECT title, code, discount_type, discount_value, expires_at FROM special_offers WHERE active=1 AND (max_uses=0 OR used_count<max_uses) AND (expires_at IS NULL OR expires_at>'$now')");
    if ($oRes)
        $offers = $oRes->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
}

if (empty($bookings)) {
    $bookingLines = "No bookings found for this user.";
} else {
    $bookingLines = '';
    foreach ($bookings as $b) {
        $tech = !empty($b['technician_name']) ? "Technician: {$b['technician_name']}" : "No technician assigned";
        $price = '₱' . number_format((float) $b['price'], 2);
        $time = !empty($b['time_slot']) ? $b['time_slot'] : '';
        $bookingLines .= "- ID #{$b['id']}: {$b['service']} | Date: {$b['date']} {$time} | Status: {$b['status']} | Price: {$price} | Address: {$b['address']} | {$tech}\n";
    }
}

$offerLines = empty($offers) ? "No active offers right now." : implode("\n", array_map(
    fn($o) =>
    "- {$o['title']} | Code: {$o['code']} | " .
        ($o['discount_type'] === 'percent' ? "{$o['discount_value']}% OFF" : "₱{$o['discount_value']} OFF") .
        ($o['expires_at'] ? " | Expires: {$o['expires_at']}" : " | No expiry"),
    $offers
));

$systemPrompt = "You are HomeEase Customer Service — a friendly AI assistant for HomeEase, a home services booking app in the Philippines.\n\n"
    . "CURRENT USER:\n"
    . "- Name: {$user['name']}\n"
    . "- Email: {$user['email']}\n"
    . "- Phone: " . ($user['phone'] ?: 'Not set') . "\n"
    . "- Address: " . ($user['address'] ?: 'Not set') . "\n"
    . "- Unread notifications: {$unreadNotifs}\n\n"
    . "USER'S BOOKINGS (LIVE FROM DATABASE):\n{$bookingLines}\n\n"
    . "ACTIVE SPECIAL OFFERS:\n{$offerLines}\n\n"
    . "SERVICES & PRICING:\n"
    . "- Cleaning: ₱200/hr or ₱599 flat (min. 2hrs)\n"
    . "- Plumbing: ₱250/hr or ₱450 flat (min. 1hr)\n"
    . "- Electrical: ₱300/hr or ₱750 flat (min. 1hr)\n"
    . "- Painting: ₱220/hr or ₱800 flat (min. 3hrs)\n"
    . "- Appliance Repair: ₱280/hr or ₱650 flat (min. 1hr)\n"
    . "- Gardening: ₱180/hr or ₱850 flat (min. 2hrs)\n\n"
    . "CAPABILITIES:\n"
    . "1. Answer questions using the real user data above.\n"
    . "2. Cancel a PENDING booking — ask for confirmation first, then execute after user says YES.\n"
    . "3. Explain booking statuses, pricing, how to book.\n"
    . "4. Show active promos and how to use them.\n\n"
    . "CANCELLATION RULES:\n"
    . "- Only bookings with status=pending can be cancelled. Bookings with status=progress, done, or cancelled CANNOT be cancelled.\n"
    . "- If a user asks to cancel an in-progress or completed booking, tell them it cannot be cancelled because it is already in progress or completed.\n"
    . "- When user wants to cancel a pending booking: identify which one, then ask: Are you sure you want to cancel your [service] booking on [date]? Reply YES to confirm.\n"
    . "- If user already said YES/oo/confirm in the previous message AND a pending booking was identified, output the cancel action.\n\n"
    . "EMOJI RULES:\n"
    . "- Use NO emojis unless absolutely necessary for clarity.\n"
    . "- Maximum 1 emoji per response, only for success/error states (e.g. a checkmark for confirmed cancellation).\n"
    . "- Never use emojis in greetings, questions, or general replies.\n\n"
    . "IMPORTANT - RESPONSE FORMAT:\n"
    . "ALWAYS return a raw JSON object (no markdown, no code blocks). Format:\n"
    . "{\"reply\": \"your message\", \"action\": null}\n"
    . "OR for cancellation after YES confirmation:\n"
    . "{\"reply\": \"your confirmation message\", \"action\": {\"type\": \"cancel_booking\", \"booking_id\": 123}}\n"
    . "Never return plain text. Only return the JSON object.";


$messages = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($history as $msg) {
    if (empty($msg['text']) || empty($msg['role']))
        continue;
    $messages[] = ['role' => ($msg['role'] === 'model' ? 'assistant' : 'user'), 'content' => $msg['text']];
}
$messages[] = ['role' => 'user', 'content' => $message];

$payload = json_encode([
    'model' => GROQ_MODEL,
    'messages' => $messages,
    'temperature' => 0.4,
    'max_tokens' => 500,
    'response_format' => ['type' => 'json_object'],
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_TIMEOUT => 20,
]);

$raw = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($curlErr) {
    echo json_encode(['success' => true, 'reply' => "I'm having trouble connecting. Please try again."]);
    exit;
}

$resp = json_decode($raw, true);
$rawText = $resp['choices'][0]['message']['content'] ?? null;

if (!$rawText) {
    $errCode = $resp['error']['code'] ?? '';
    if ($httpCode === 429 || $errCode === 'rate_limit_exceeded') {
        $msg = "I'm a little busy right now! Please try again in a few seconds.";
    } elseif ($httpCode === 401 || $httpCode === 403) {
        $msg = "API key issue — please check your Groq API key in groq_chat.php.";
    } else {
        $msg = "Something went wrong. Please try again.";
    }
    echo json_encode(['success' => true, 'reply' => $msg]);
    exit;
}

$parsed = json_decode($rawText, true);
if (!$parsed || !isset($parsed['reply'])) {
    echo json_encode(['success' => true, 'reply' => strip_tags($rawText)]);
    exit;
}

$reply = trim($parsed['reply']);
$action = $parsed['action'] ?? null;

if ($action && ($action['type'] ?? '') === 'cancel_booking' && !empty($action['booking_id'])) {
    $bid = (int) $action['booking_id'];
    $cStmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=? AND status='pending'");
    $cStmt->bind_param("ii", $bid, $uid);
    $cStmt->execute();
    $affected = $cStmt->affected_rows;
    $cStmt->close();

    if ($affected > 0) {
        try {
            $notifMsg = "Your booking #$bid has been cancelled via chat.";
            $nIns = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Cancelled', ?, 'cleaning', 0, NOW())");
            if ($nIns) {
                $nIns->bind_param("is", $uid, $notifMsg);
                $nIns->execute();
                $nIns->close();
            }
        } catch (Exception $e) {
        }

        echo json_encode(['success' => true, 'reply' => $reply, 'action_result' => 'cancelled', 'booking_id' => $bid]);
    } else {
        echo json_encode(['success' => true, 'reply' => "I wasn't able to cancel that booking. It may already be cancelled or not in pending status."]);
    }
    exit;
}

echo json_encode(['success' => true, 'reply' => $reply]);

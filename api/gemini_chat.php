<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(0);

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

define('GEMINI_API_KEY', 'AIzaSyC6yIzpIB6NxNfOiPYwp3iEV8IYUjGq2ZMAIzaSyC4ib_kdFNxMVWc18iAtzdME4iIIXShxKY');
// Fallback chain — tried in order; first 200 OK wins
define('GEMINI_MODELS', [
    'gemini-2.5-flash',       // best quality; may 503 if overloaded
    'gemini-2.5-flash-lite',  // lighter variant, confirmed working
    'gemini-flash-latest',    // alias, confirmed working
]);

require_once __DIR__ . '/db.php';

$uid = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);
$message = trim($input['message'] ?? '');
$history = $input['history'] ?? [];

if (!$message) {
    echo json_encode(['success' => false, 'message' => 'Empty message.']);
    exit;
}

/* ─── helpers ───────────────────────────────────────────────────── */
function getColumns(mysqli $conn, string $table): array
{
    $cols = [];
    $r = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($r)
        while ($c = $r->fetch_assoc())
            $cols[] = $c['Field'];
    return $cols;
}

/* ─── fetch user ─────────────────────────────────────────────────── */
$user = ['name' => 'User', 'email' => '', 'phone' => '', 'address' => ''];
$uStmt = $conn->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
if ($uStmt) {
    $uStmt->bind_param("i", $uid);
    $uStmt->execute();
    $row = $uStmt->get_result()->fetch_assoc();
    if ($row)
        $user = $row;
    $uStmt->close();
}

/* ─── fetch bookings ─────────────────────────────────────────────── */
$bookings = [];
try {
    $bCols = getColumns($conn, 'bookings');
    $select = ['b.id', 'b.service', 'b.date', 'b.status', 'b.address'];
    if (in_array('price', $bCols))
        $select[] = 'b.price';
    if (in_array('time_slot', $bCols))
        $select[] = 'b.time_slot';
    if (in_array('notes', $bCols))
        $select[] = 'b.notes';
    if (in_array('pricing_type', $bCols))
        $select[] = 'b.pricing_type';
    if (in_array('created_at', $bCols))
        $select[] = 'b.created_at';

    // Join accepted provider name if available
    $joinSql = "LEFT JOIN booking_requests br ON br.booking_id = b.id AND br.status = 'accepted'
                LEFT JOIN service_providers sp ON sp.provider_id = br.provider_id";
    $select[] = "COALESCE(sp.full_name, 'No provider yet') AS provider_name";

    $sql = "SELECT " . implode(', ', $select) . " FROM bookings b $joinSql WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 20";
    $bStmt = $conn->prepare($sql);
    if ($bStmt) {
        $bStmt->bind_param("i", $uid);
        $bStmt->execute();
        $bookings = $bStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $bStmt->close();
    }
} catch (Exception $e) {
}

/* ─── fetch available services ───────────────────────────────────── */
$services = [];
try {
    $sRes = $conn->query("SELECT name, flat_rate, description FROM services WHERE active = 1 ORDER BY name ASC");
    if ($sRes)
        $services = $sRes->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
}

/* ─── fetch unread notifications ─────────────────────────────────── */
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

/* ─── fetch active providers (for booking command) ───────────────── */
$availableProviders = [];
try {
    $pRes = $conn->query("SELECT provider_id, full_name, service_category, availability_status FROM service_providers WHERE status='active' AND LOWER(availability_status) <> 'unavailable' ORDER BY rating DESC LIMIT 30");
    if ($pRes)
        $availableProviders = $pRes->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
}

/* ─── build context strings ─────────────────────────────────────── */
if (empty($bookings)) {
    $bookingLines = "Wala pang bookings ang user na ito. / No bookings found for this user.";
} else {
    $bookingLines = '';
    foreach ($bookings as $b) {
        $price = isset($b['price']) ? '₱' . number_format((float) $b['price'], 2) : 'N/A';
        $time = !empty($b['time_slot']) ? " " . $b['time_slot'] : '';
        $prov = $b['provider_name'] ?? 'No provider yet';
        $bookingLines .= "- ID #{$b['id']}: {$b['service']} | Date: {$b['date']}{$time} | Status: {$b['status']} | Price: {$price} | Address: {$b['address']} | Provider: {$prov}\n";
    }
}

if (empty($services)) {
    // fallback hardcoded services
    $serviceLines = "- Cleaner: ₱500 flat\n- Helper: ₱400 flat\n- Laundry Worker: ₱300 flat\n- Plumber: ₱500 flat\n- Carpenter: ₱600 flat\n- Appliance Technician: ₱500 flat";
} else {
    $serviceLines = implode("\n", array_map(
        fn($s) => "- {$s['name']}: ₱" . number_format((float) ($s['flat_rate'] ?? 0), 0) . " flat — {$s['description']}",
        $services
    ));
}

$providerLines = empty($availableProviders)
    ? "Walang available na provider ngayon. / No providers currently available."
    : implode("\n", array_map(
        fn($p) => "- {$p['full_name']} ({$p['service_category']}) — {$p['availability_status']}",
        $availableProviders
    ));

/* ─── system prompt ──────────────────────────────────────────────── */
$systemPrompt = <<<PROMPT
Ikaw ay ang HomeEase AI Assistant — isang matalinong customer service chatbot para sa HomeEase, isang home services booking app sa Pilipinas.

MAHALAGA: Sumagot sa parehong Tagalog at English. Kung ang user ay nagsalita sa Tagalog, sumagot sa Tagalog. Kung English, sumagot sa English. Maaari ring mag-mix ng dalawa (Taglish) kung natural.

KASALUKUYANG USER:
- Pangalan: {$user['name']}
- Email: {$user['email']}
- Phone: {$user['phone']}
- Address: {$user['address']}
- Hindi pa nababasang notipikasyon: {$unreadNotifs}

MGA BOOKING NG USER (LIVE MULA SA DATABASE):
{$bookingLines}

MGA AVAILABLE NA SERBISYO AT PRESYO:
{$serviceLines}

MGA AVAILABLE NA PROVIDER NGAYON:
{$providerLines}

MGA KAKAYAHAN MO:
1. Sumagot sa mga katanungan gamit ang tunay na data ng user mula sa database.
2. Mag-cancel ng PENDING na booking — humingi muna ng kumpirmasyon bago i-execute.
3. Lumikha ng bagong booking para sa user — humingi ng serbisyo at address kung hindi pa ibinibigay.
4. Ipaliwanag ang mga booking status, presyo, at proseso ng pag-book.
5. Sumagot sa mga tanong tungkol sa HomeEase app at mga serbisyo nito.
6. Tumugon sa parehong Tagalog at English.

RULES SA PAGKANSELA (CANCEL):
- Tanging PENDING na booking lang ang maaaring i-cancel. Ang mga may status na progress, done, o cancelled ay HINDI na maaaring baguhin.
- Kapag gusto ng user na mag-cancel: tukuyin kung alin, tapos magtanong: "Sigurado ka bang gusto mong i-cancel ang iyong [service] booking? I-reply ang YES para kumpirmahin."
- Kapag sinabi na ng user ang YES/oo/confirm AT natukoy na ang pending booking, ilabas ang cancel action.

RULES SA PAGLIKHA NG BOOKING (CREATE BOOKING):
- Kapag gusto ng user na mag-book: humingi ng (1) serbisyo, (2) address kung hindi pa known.
- Ang address ng user ay: {$user['address']}. Gamitin ito kung hindi nagbigay ng ibang address.
- Kapag handa na ang lahat ng info, ilabas ang create_booking action.
- Ang booking ay real-time (walang date/time selection — ngayon na agad).

RULES SA EMOJI:
- Huwag gumamit ng emoji maliban sa success/error states.
- Maximum 1 emoji per response.

MAHALAGA — FORMAT NG SAGOT:
LAGI kang mag-return ng raw JSON object (walang markdown, walang code block). Format:
{"reply": "iyong mensahe", "action": null}

PARA SA CANCELLATION (pagkatapos ng YES):
{"reply": "iyong confirmation message", "action": {"type": "cancel_booking", "booking_id": 123}}

PARA SA BAGONG BOOKING (kapag kumpleto na ang info):
{"reply": "iyong confirmation message", "action": {"type": "create_booking", "service": "Cleaner", "address": "123 Main St"}}

Huwag mag-return ng plain text. JSON object lang palagi.
PROMPT;

/* ─── build Gemini contents array ───────────────────────────────── */
// Gemini uses "contents" with "parts" instead of OpenAI "messages"
$contents = [];
// Prepend system instruction as first user turn (Gemini Flash supports systemInstruction separately)
foreach ($history as $msg) {
    if (empty($msg['text']) || empty($msg['role']))
        continue;
    $role = ($msg['role'] === 'model') ? 'model' : 'user';
    $contents[] = ['role' => $role, 'parts' => [['text' => $msg['text']]]];
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

/* ─── call Gemini API with model fallback ───────────────────────── */
$payload = json_encode([
    'system_instruction' => [
        'parts' => [['text' => $systemPrompt]]
    ],
    'contents' => $contents,
    'generationConfig' => [
        'temperature' => 0.4,
        'maxOutputTokens' => 800,
        'responseMimeType' => 'application/json',
    ],
]);

$raw = null;
$httpCode = 0;
$curlErr = '';

foreach (GEMINI_MODELS as $modelName) {
    $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
        . $modelName . ':generateContent?key=' . GEMINI_API_KEY;

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $raw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    // Stop on success; retry on transient server errors (5xx) or quota (429)
    if (!$curlErr && $httpCode === 200) {
        break;
    }
}

if ($curlErr) {
    echo json_encode(['success' => true, 'reply' => "Hindi ako makakonekta sa AI ngayon. Pakisubukan muli. / I'm having trouble connecting. Please try again."]);
    exit;
}

$resp = json_decode($raw, true);
$rawText = $resp['candidates'][0]['content']['parts'][0]['text'] ?? null;

if (!$rawText) {
    $errStatus = $resp['error']['status'] ?? '';
    $errMsg = $resp['error']['message'] ?? '';
    if ($httpCode === 429 || $errStatus === 'RESOURCE_EXHAUSTED') {
        $reply = "Naubos na ang libreng quota ng AI. Pakisubukan muli mamaya. / AI quota exhausted. Please try again later.";
    } elseif ($httpCode === 401 || $httpCode === 403) {
        $reply = "May problema sa API key. / API key issue — please try again.";
    } elseif ($httpCode === 404) {
        $reply = "AI model hindi mahanap. / AI model not found.";
    } elseif ($httpCode >= 500) {
        $reply = "Ang AI server ay pababa ngayon. Pakisubukan muli mamaya. / AI server is temporarily down. Please try again in a moment.";
    } else {
        $reply = "May nangyaring mali (HTTP $httpCode). Pakisubukan muli. / Something went wrong. Please try again.";
    }
    echo json_encode(['success' => true, 'reply' => $reply]);
    exit;
}

/* ─── parse JSON from Gemini text ───────────────────────────────── */
// Strip possible markdown fences
$cleanText = trim($rawText);
if (str_starts_with($cleanText, '```')) {
    $cleanText = preg_replace('/^```(?:json)?\s*/i', '', $cleanText);
    $cleanText = preg_replace('/\s*```\s*$/', '', $cleanText);
}

$parsed = json_decode($cleanText, true);
if (!$parsed || !isset($parsed['reply'])) {
    echo json_encode(['success' => true, 'reply' => strip_tags($cleanText)]);
    exit;
}

$reply = trim($parsed['reply']);
$action = $parsed['action'] ?? null;

/* ─── handle CANCEL action ──────────────────────────────────────── */
if ($action && ($action['type'] ?? '') === 'cancel_booking' && !empty($action['booking_id'])) {
    $bid = (int) $action['booking_id'];
    $cStmt = $conn->prepare("UPDATE bookings SET status='cancelled' WHERE id=? AND user_id=? AND status='pending'");
    $cStmt->bind_param("ii", $bid, $uid);
    $cStmt->execute();
    $affected = $cStmt->affected_rows;
    $cStmt->close();

    if ($affected > 0) {
        // Insert cancellation notification
        try {
            $notifMsg = "Ang iyong booking #{$bid} ay na-cancel sa pamamagitan ng chat. / Your booking #{$bid} has been cancelled via chat.";
            $nIns = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Cancelled', ?, 'cleaner', 0, NOW())");
            if ($nIns) {
                $nIns->bind_param("is", $uid, $notifMsg);
                $nIns->execute();
                $nIns->close();
            }
        } catch (Exception $e) {
        }

        echo json_encode(['success' => true, 'reply' => $reply, 'action_result' => 'cancelled', 'booking_id' => $bid]);
    } else {
        echo json_encode(['success' => true, 'reply' => "Hindi ko na-cancel ang booking na iyon. Maaaring hindi na ito pending o hindi ito sa'yo. / I wasn't able to cancel that booking. It may not be in pending status."]);
    }
    exit;
}

/* ─── handle CREATE BOOKING action ──────────────────────────────── */
if ($action && ($action['type'] ?? '') === 'create_booking' && !empty($action['service'])) {
    $service = trim($action['service']);
    $address = trim($action['address'] ?? $user['address'] ?? '');
    $date = date('Y-m-d');
    $timeSlot = date('g:i A');
    $notes = trim($action['notes'] ?? '');

    if (!$address) {
        echo json_encode(['success' => true, 'reply' => "Ano ang iyong address para sa booking? / What is your address for the booking?"]);
        exit;
    }

    // Get service details
    $svcStmt = $conn->prepare("SELECT name, flat_rate, description FROM services WHERE active=1 AND name=? LIMIT 1");
    $svcStmt->bind_param("s", $service);
    $svcStmt->execute();
    $svcRow = $svcStmt->get_result()->fetch_assoc();
    $svcStmt->close();

    if (!$svcRow) {
        echo json_encode(['success' => true, 'reply' => "Hindi ko makita ang serbisyong '{$service}'. Pakipili mula sa aming mga available na serbisyo. / Service '{$service}' not found. Please choose from our available services."]);
        exit;
    }

    $price = (float) ($svcRow['flat_rate'] ?? 0);
    if ($price <= 0)
        $price = 500;

    // Get user info
    $customerName = $user['name'] ?? '';
    $customerPhone = $user['phone'] ?? '';

    // Insert booking
    $bCols = getColumns($conn, 'bookings');
    $colList = "user_id, service, date, address, price, status, created_at";
    $valList = "?, ?, ?, ?, ?, 'pending', NOW()";
    $types = "isssd";
    $params = [$uid, $service, $date, $address, $price];

    if (in_array('time_slot', $bCols)) {
        $colList .= ", time_slot";
        $valList .= ", ?";
        $types .= "s";
        $params[] = $timeSlot;
    }
    if (in_array('notes', $bCols)) {
        $colList .= ", notes";
        $valList .= ", ?";
        $types .= "s";
        $params[] = $notes;
    }
    if (in_array('pricing_type', $bCols)) {
        $colList .= ", pricing_type";
        $valList .= ", ?";
        $types .= "s";
        $params[] = 'flat';
    }
    if (in_array('hours', $bCols)) {
        $colList .= ", hours";
        $valList .= ", ?";
        $types .= "i";
        $params[] = 1;
    }

    $insStmt = $conn->prepare("INSERT INTO bookings ($colList) VALUES ($valList)");
    if (!$insStmt) {
        echo json_encode(['success' => true, 'reply' => "Hindi ako makapag-book ngayon. Pakisubukan muli. / Could not create booking right now. Please try again."]);
        exit;
    }

    $bind = array_merge([$types], $params);
    $refs = [];
    foreach ($bind as $i => $v)
        $refs[$i] = &$bind[$i];
    call_user_func_array([$insStmt, 'bind_param'], $refs);

    if (!$insStmt->execute()) {
        echo json_encode(['success' => true, 'reply' => "May error sa pag-book. / Booking insert failed."]);
        exit;
    }
    $newBid = $conn->insert_id;
    $insStmt->close();

    // Broadcast to matching providers
    try {
        $providerStmt = $conn->prepare(
            "SELECT provider_id AS id, full_name, service_category FROM service_providers
             WHERE status='active' AND LOWER(availability_status) <> 'unavailable'
               AND LOWER(service_category) LIKE ? ORDER BY rating DESC"
        );
        $like = '%' . strtolower($service) . '%';
        $providerStmt->bind_param('s', $like);
        $providerStmt->execute();
        $matchedProviders = $providerStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $providerStmt->close();

        if (!empty($matchedProviders)) {
            $reqStmt = $conn->prepare(
                "INSERT INTO booking_requests
                 (booking_id, provider_id, service, fixed_price, date, time_slot, address, details, customer_name, customer_phone, customer_address, status, created_at, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
            );
            if ($reqStmt) {
                foreach ($matchedProviders as $p) {
                    $pid = (int) ($p['id'] ?? 0);
                    if ($pid <= 0)
                        continue;
                    $reqStmt->bind_param('iisdsssssss', $newBid, $pid, $service, $price, $date, $timeSlot, $address, $notes, $customerName, $customerPhone, $address);
                    $reqStmt->execute();
                }
                $reqStmt->close();
            }
        }
    } catch (Exception $e) {
    }

    // Notify user
    try {
        $notifMsg = "Ang iyong {$service} booking ay natanggap at hihintayin ang provider.";
        $icon = strtolower(str_replace(' ', '_', $service));
        $nIns = $conn->prepare("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES (?, 'Booking Received', ?, ?, 0, NOW())");
        if ($nIns) {
            $nIns->bind_param("iss", $uid, $notifMsg, $icon);
            $nIns->execute();
            $nIns->close();
        }
    } catch (Exception $e) {
    }

    echo json_encode([
        'success' => true,
        'reply' => $reply,
        'action_result' => 'booking_created',
        'booking_id' => $newBid,
        'service' => $service,
        'price' => $price,
    ]);
    exit;
}

/* ─── default reply ──────────────────────────────────────────────── */
echo json_encode(['success' => true, 'reply' => $reply]);

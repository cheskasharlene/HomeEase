<?php









error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';


class Colors {
    const RESET = "\033[0m";
    const GREEN = "\033[92m";
    const RED = "\033[91m";
    const YELLOW = "\033[93m";
    const BLUE = "\033[94m";
}

$isWeb = !empty($_SERVER['HTTP_HOST']);
$testResults = [];

function log_test($name, $passed, $details = '') {
    global $isWeb, $testResults;
    
    $status = $passed ? '✓ PASS' : '✗ FAIL';
    $color = $passed ? Colors::GREEN : Colors::RED;
    
    if ($isWeb) {
        $bgColor = $passed ? '#dcfce7' : '#fee2e2';
        $textColor = $passed ? '#166534' : '#991b1b';
        echo "<div style='background: {$bgColor}; color: {$textColor}; padding: 8px; margin: 4px 0; border-radius: 4px;'>";
        echo "<strong>{$status}:</strong> {$name}";
        if ($details) echo " ({$details})";
        echo "</div>";
    } else {
        echo "{$color}[{$status}]{Colors::RESET} {$name}";
        if ($details) echo " ({$details})";
        echo "\n";
    }
    
    $testResults[] = ['name' => $name, 'passed' => $passed, 'details' => $details];
}

function section_header($title) {
    global $isWeb;
    if ($isWeb) {
        echo "<h2 style='margin-top: 16px; color: #1a1a2e; border-bottom: 2px solid #e8820c; padding-bottom: 8px;'>{$title}</h2>";
    } else {
        echo "\n" . Colors::BLUE . "=== {$title} ===" . Colors::RESET . "\n";
    }
}



section_header("Database Connection Test");

if ($conn->connect_error) {
    log_test("Database Connection", false, $conn->connect_error);
    exit;
} else {
    log_test("Database Connection", true, "Connected to " . DB_NAME);
}



section_header("Payments Table Tests");

$result = ensurePaymentsTable($conn);
log_test("Create/Verify Payments Table", $result);


$tableCheck = $conn->query("SHOW TABLES LIKE 'payments'");
log_test("Payments Table Exists", $tableCheck && $tableCheck->num_rows > 0);


$expectedColumns = [
    'id', 'booking_id', 'user_id', 'payment_method', 'payment_status',
    'payment_reference', 'amount', 'transaction_id', 'notes', 'created_at', 'updated_at'
];

$columnCheck = $conn->query("DESCRIBE payments");
$actualColumns = [];
if ($columnCheck) {
    while ($col = $columnCheck->fetch_assoc()) {
        $actualColumns[] = $col['Field'];
    }
}

$columnsPresent = true;
foreach ($expectedColumns as $col) {
    if (!in_array($col, $actualColumns)) {
        $columnsPresent = false;
        log_test("Column '{$col}' exists", false);
    }
}
if ($columnsPresent) {
    log_test("All Required Columns Present", true, count($expectedColumns) . " columns");
}



section_header("Payment Data Validation Tests");

$tests = [
    ['cash', null, true, 'Cash payment (no reference)'],
    ['gcash', '09123456789', true, 'Valid GCash format'],
    ['gcash', '9123456789', false, 'Invalid GCash format (no 0)'],
    ['gcash', '09123456', false, 'Invalid GCash format (too short)'],
    ['gcash', null, false, 'GCash without number'],
    ['bank', '12345678901234', true, 'Valid Bank format'],
    ['bank', '1234567', false, 'Bank number too short'],
    ['bank', 'ABC-123-456', false, 'Bank number with non-numeric'],
    ['bank', null, false, 'Bank without account number'],
    ['invalid', null, false, 'Invalid payment method'],
];

foreach ($tests as $test) {
    [$method, $ref, $expectedValid, $label] = $test;
    $validation = validatePaymentData($method, $ref);
    $passed = $validation['valid'] === $expectedValid;
    log_test("Validation: {$label}", $passed, $validation['message']);
}



section_header("Payment Save/Retrieve Tests");


$testUserId = 999;
$testBookingId = 9999;


$conn->query("DELETE FROM payments WHERE user_id = {$testUserId}");
$conn->query("DELETE FROM bookings WHERE id = {$testBookingId} AND user_id = {$testUserId}");


$bookingStmt = $conn->prepare("INSERT INTO bookings (id, user_id, service, date, address, price, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
if ($bookingStmt) {
    $testDate = date('Y-m-d');
    $testService = 'Test Service';
    $testAddress = 'Test Address';
    $testPrice = 1500.00;
    $testStatus = 'pending';
    
    $bookingStmt->bind_param('isssdss', $testBookingId, $testUserId, $testService, $testDate, $testAddress, $testPrice, $testStatus);
    
    if ($bookingStmt->execute()) {
        log_test("Create Test Booking", true, "Booking #{$testBookingId}");
        
        
        $saveResult = savePayment($conn, $testBookingId, $testUserId, 'gcash', '09123456789', 1500, 'pending');
        log_test("Save Payment", $saveResult['success'], $saveResult['message']);
        
        if ($saveResult['success']) {
            $paymentId = $saveResult['payment_id'];
            log_test("Transaction ID Generated", !empty($saveResult['transaction_id']), $saveResult['transaction_id']);
            
            
            $payment = getPaymentByBooking($conn, $testUserId, $testBookingId);
            log_test("Retrieve Payment", $payment !== null);
            
            if ($payment) {
                log_test("Payment Amount", $payment['amount'] == 1500.00, "₱{$payment['amount']}");
                log_test("Payment Method", $payment['payment_method'] === 'gcash');
                log_test("Payment Reference", $payment['payment_reference'] === '09123456789');
            }
        }
    } else {
        log_test("Create Test Booking", false, $bookingStmt->error);
    }
    $bookingStmt->close();
} else {
    log_test("Create Test Booking", false, "Could not prepare statement");
}



section_header("User Data Isolation Tests");

$testUser1 = 1001;
$testUser2 = 1002;
$testBooking1 = 10001;
$testBooking2 = 10002;


$conn->query("DELETE FROM payments WHERE user_id IN ({$testUser1}, {$testUser2})");
$conn->query("DELETE FROM bookings WHERE id IN ({$testBooking1}, {$testBooking2})");


$for1 = true;
for ($i = 0; $i < 2; $i++) {
    $userId = $for1 ? $testUser1 : $testUser2;
    $bookingId = $for1 ? $testBooking1 : $testBooking2;
    $for1 = false;
    
    $stmt = $conn->prepare("INSERT INTO bookings (id, user_id, service, date, address, price, status) VALUES (?, ?, 'Test', ?, 'Test', ?, ?)");
    if ($stmt) {
        $date = date('Y-m-d');
        $price = 1000;
        $status = 'pending';
        $stmt->bind_param('isisss', $bookingId, $userId, $date, $price, $status);
        $stmt->execute();
        $stmt->close();
        
        
        $saveResult = savePayment($conn, $bookingId, $userId, 'cash', null, 1000, 'pending');
    }
}


$user1Payments = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE user_id = ?");
if ($user1Payments) {
    $uid = $testUser1;
    $user1Payments->bind_param('i', $uid);
    $user1Payments->execute();
    $result = $user1Payments->get_result()->fetch_assoc();
    $user1Count = $result['count'];
    $user1Payments->close();
    
    log_test("User Isolation", $user1Count === 1, "User {$testUser1} sees {$user1Count} payment(s)");
}



section_header("API Endpoint Tests");


if ($payment) {
    $detail = getPaymentByBooking($conn, $testUserId, $testBookingId);
    $isArray = is_array($detail);
    $hasExpectedFields = $isArray && !empty($detail['transaction_id']);
    log_test("API: Get Payment Detail", $isArray && $hasExpectedFields);
}



section_header("Test Summary");

$passed = array_sum(array_map(fn($t) => $t['passed'] ? 1 : 0, $testResults));
$total = count($testResults);
$percentage = ($total > 0) ? round(($passed / $total) * 100) : 0;

if ($isWeb) {
    echo "<div style='background: linear-gradient(135deg, #fef3c7, #fef9c3); padding: 16px; border-radius: 8px; margin-top: 16px;'>";
    echo "<div style='font-size: 18px; font-weight: 700; color: #92400e;'>{$passed}/{$total} Tests Passed ({$percentage}%)</div>";
    if ($passed === $total) {
        echo "<div style='color: #166534; margin-top: 8px;'>✓ All tests passed! Payment system is ready to use.</div>";
    } else {
        echo "<div style='color: #991b1b; margin-top: 8px;'>✗ Some tests failed. Please review the errors above.</div>";
    }
    echo "</div>";
} else {
    $allPassed = $passed === $total;
    $color = $allPassed ? Colors::GREEN : Colors::RED;
    echo "\n{$color}=== SUMMARY ==={Colors::RESET}\n";
    echo "{$color}Total: {$passed}/{$total} Tests Passed ({$percentage}%){Colors::RESET}\n";
    if ($allPassed) {
        echo "{$color}✓ All tests passed! Payment system is ready to use.{Colors::RESET}\n";
    } else {
        echo "{$color}✗ Some tests failed. Please review the errors above.{Colors::RESET}\n";
    }
}


$conn->query("DELETE FROM payments WHERE user_id IN ({$testUserId}, {$testUser1}, {$testUser2})");
$conn->query("DELETE FROM bookings WHERE id IN ({$testBookingId}, {$testBooking1}, {$testBooking2}) AND user_id IN ({$testUserId}, {$testUser1}, {$testUser2})");

?>

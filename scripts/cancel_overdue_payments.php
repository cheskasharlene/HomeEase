<?php

require_once __DIR__ . '/../api/db.php';


$res = $conn->query("SELECT p.id, p.booking_id, p.user_id, p.receiver_provider_id FROM payments p WHERE p.expected_until IS NOT NULL AND p.expected_until < NOW() AND p.payment_status IN ('pending','submitted')");
if (!$res) exit;
while ($row = $res->fetch_assoc()) {
    $pid = (int)$row['id'];
    $bid = (int)$row['booking_id'];
    $prov = (int)$row['receiver_provider_id'];
    $uid = (int)$row['user_id'];

    $conn->begin_transaction();
    try {
        
        $conn->query("UPDATE payments SET payment_status='cancelled', updated_at=NOW() WHERE id = {$pid}");
        
        $conn->query("UPDATE bookings SET status='cancelled' WHERE id = {$bid}");
        
        $conn->query("UPDATE booking_requests SET status='closed', responded_at=NOW() WHERE booking_id = {$bid}");
        
        if ($uid > 0) {
            $conn->query("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES ({$uid}, 'Booking Cancelled', 'Your booking was cancelled because payment was not completed in time.', 'clock', 0, NOW())");
        }

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
    }
}

echo "OK\n";

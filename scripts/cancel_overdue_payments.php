<?php
// Run this script every minute via cron or task scheduler to cancel bookings with unpaid expected payments
require_once __DIR__ . '/../api/db.php';

// Find payments that expired and still pending/submitted
$res = $conn->query("SELECT p.id, p.booking_id, p.user_id, p.receiver_provider_id FROM payments p WHERE p.expected_until IS NOT NULL AND p.expected_until < NOW() AND p.payment_status IN ('pending','submitted')");
if (!$res) exit;
while ($row = $res->fetch_assoc()) {
    $pid = (int)$row['id'];
    $bid = (int)$row['booking_id'];
    $prov = (int)$row['receiver_provider_id'];
    $uid = (int)$row['user_id'];

    $conn->begin_transaction();
    try {
        // Mark payment cancelled
        $conn->query("UPDATE payments SET payment_status='cancelled', updated_at=NOW() WHERE id = {$pid}");
        // Cancel booking
        $conn->query("UPDATE bookings SET status='cancelled' WHERE id = {$bid}");
        // Close related booking_requests
        $conn->query("UPDATE booking_requests SET status='closed', responded_at=NOW() WHERE booking_id = {$bid}");
        // Notify user and provider
        if ($uid > 0) {
            $conn->query("INSERT INTO notifications (user_id, title, message, icon, is_read, created_at) VALUES ({$uid}, 'Booking Cancelled', 'Your booking was cancelled because payment was not completed in time.', 'clock', 0, NOW())");
        }
        if ($prov > 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS provider_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY, provider_id INT NOT NULL, title VARCHAR(120) NOT NULL, message TEXT, icon VARCHAR(32) DEFAULT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $conn->query("INSERT INTO provider_notifications (provider_id, title, message, icon, is_read, created_at) VALUES ({$prov}, 'Booking Cancelled', 'Booking cancelled due to non-payment by client.', 'warning', 0, NOW())");
        }
        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();
    }
}

echo "OK\n";

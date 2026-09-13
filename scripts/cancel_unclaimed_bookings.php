<?php
// Run this script periodically via cron or task scheduler to automatically cancel unclaimed pending bookings older than 3 minutes.
require_once __DIR__ . '/../api/db.php';

$cancelled = cancelExpiredMatchingBookings($conn);
echo "Cancelled {$cancelled} unclaimed booking(s).\n";

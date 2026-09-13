<?php

require_once __DIR__ . '/../api/db.php';

$cancelled = cancelExpiredMatchingBookings($conn);
echo "Cancelled {$cancelled} unclaimed booking(s).\n";

<?php

require_once __DIR__ . '/../api/db.php';

ensureNormalizationSchema($conn);

// 1. Fetch all provider IDs
$res = $conn->query("SELECT provider_id FROM service_providers");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pid = (int)$row['provider_id'];
        ensureRemittancesForProvider($conn, $pid);
    }
}

// 2. Sync online statuses (which forces restricted providers offline)
syncProviderOnlineStatuses($conn);

echo "Remittance grace periods and restrictions checked successfully.\n";

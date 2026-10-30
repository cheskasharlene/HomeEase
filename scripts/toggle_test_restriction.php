<?php
/**
 * Test Helper: Toggle Overdue Remittance Restriction for Provider 11
 * Usage via CLI: php scripts/toggle_test_restriction.php [enable|disable]
 */
require_once __DIR__ . '/../api/db.php';

$action = $argv[1] ?? 'enable';
$providerId = 11; // Lance Austria (active session)

if ($action === 'disable') {
    $conn->query("DELETE FROM remittances WHERE provider_id = {$providerId} AND reference_no = 'REF-TEST-OVERDUE'");
    echo "Test remittance removed for Provider {$providerId}. Restriction is now DISABLED.\n";
} else {
    // Check if test remittance already exists
    $check = $conn->query("SELECT id FROM remittances WHERE provider_id = {$providerId} AND reference_no = 'REF-TEST-OVERDUE'");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $conn->query("UPDATE remittances SET status = 'overdue', grace_period_expires_at = '2026-10-20 00:00:00' WHERE id = {$row['id']}");
        echo "Existing test remittance updated (ID: {$row['id']}).\n";
    } else {
        $conn->query("INSERT INTO remittances (provider_id, reference_no, amount_due, status, due_date, overdue_notified_at, grace_period_expires_at) 
                      VALUES ({$providerId}, 'REF-TEST-OVERDUE', 180.00, 'overdue', '2026-10-20', '2026-10-20 00:00:00', '2026-10-21 00:00:00')");
        echo "New test overdue remittance created for Provider {$providerId}.\n";
    }

    $conn->query("UPDATE service_providers SET availability_status = 'offline' WHERE provider_id = {$providerId}");
    echo "Provider availability set to OFFLINE.\n";
    echo "Restriction check result: ";
    $res = isProviderRestrictedFromOnline($conn, $providerId);
    if ($res) {
        echo "SUCCESS - Account is now RESTRICTED (Due: ₱" . number_format($res['amount_due'], 2) . ").\n";
    } else {
        echo "FAILED - Not restricted.\n";
    }
}

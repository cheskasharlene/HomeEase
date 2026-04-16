<?php
// Quick check of valid_id paths in database
require_once __DIR__ . '/api/db.php';

$result = $conn->query("SELECT provider_id, full_name, valid_id, selfie_verification FROM service_providers WHERE valid_id IS NOT NULL AND valid_id != '' LIMIT 5");

if ($result) {
    echo "Database paths stored:\n";
    echo str_repeat("=", 80) . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "Provider ID: {$row['provider_id']}\n";
        echo "Name: {$row['full_name']}\n";
        echo "Valid ID Path: {$row['valid_id']}\n";
        echo "Selfie Path: {$row['selfie_verification']}\n";
        echo str_repeat("-", 80) . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>

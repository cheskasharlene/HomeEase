<?php
/**
 * Database Migration Script
 * Consolidates verification images to service_providers table
 */

require_once 'api/db.php';

// Check database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Starting database migration...\n";

// Step 1: Rename columns in service_providers table
echo "Step 1: Renaming columns in service_providers table...\n";

$queries = [
    "ALTER TABLE service_providers CHANGE COLUMN id_picture valid_id VARCHAR(500)",
    "ALTER TABLE service_providers CHANGE COLUMN certificates barangay_clearance VARCHAR(500)",
    "ALTER TABLE service_providers CHANGE COLUMN proof_of_experience `tools_&_kits` VARCHAR(500)",
    "DROP TABLE IF EXISTS provider_verification_images"
];

foreach ($queries as $query) {
    echo "Executing: $query\n";
    if ($conn->query($query) === TRUE) {
        echo "✓ Success\n";
    } else {
        echo "✗ Error: " . $conn->error . "\n";
        die("Migration failed!\n");
    }
}

echo "\n✓ Database migration completed successfully!\n";
echo "- Columns renamed: id_picture → valid_id, certificates → barangay_clearance, proof_of_experience → tools_&_kits\n";
echo "- Table deleted: provider_verification_images\n";

$conn->close();
?>

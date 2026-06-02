<?php
require 'e:/xampp/htdocs/homeeasee/api/db.php';
$sql = "SELECT provider_id AS id, full_name AS name, service_category AS specialty, availability_status AS availability, jobs_done, rating, is_verified, profile_photo FROM service_providers WHERE status = 'active' AND availability_status = 'online' ORDER BY jobs_done DESC LIMIT 6";
$res = $conn->query($sql);
if (!$res) echo "Error: " . $conn->error; else echo "Success!";

<?php
require 'e:/xampp/htdocs/homeeasee/api/db.php';
$res = $conn->query("SHOW COLUMNS FROM service_providers");
while ($r = $res->fetch_assoc()) echo $r['Field'] . "\n";

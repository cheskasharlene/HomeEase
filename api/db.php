<?php
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "homeease_db");

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB connection failed: " . $conn->connect_error]);
    exit;
}

$conn->set_charset("utf8mb4");

function respond($success, $message = "", $data = []) {
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(array_merge(["success" => $success, "message" => $message], $data));
    exit;
}

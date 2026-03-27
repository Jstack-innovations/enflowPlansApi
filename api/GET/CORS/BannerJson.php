<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$file = __DIR__ . "/../JSON/banner.json";

// Check if file exists
if (!file_exists($file)) {
    http_response_code(404);
    echo json_encode(["error" => "banner not found"]);
    exit;
}

// Read file safely
$content = file_get_contents($file);
if (!$content) {
    http_response_code(500);
    echo json_encode(["error" => "banner empty or unreadable"]);
    exit;
}

// Return JSON
echo $content;

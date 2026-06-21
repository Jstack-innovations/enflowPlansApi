<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); exit(); }

require_once __DIR__ . '/../../SECURE/config.php';

$body = json_decode(file_get_contents("php://input"), true);
$localUrl = trim($body["local_server_url"] ?? "");

if (!$localUrl) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "local_server_url required"]);
    exit;
}

$stmt = $pdo->prepare("
    SELECT status, trial_started_at, trial_ends_at, renewal_date
    FROM subscriptions
    WHERE REPLACE(local_server_url, 'http://', 'https://') = REPLACE(:url, 'http://', 'https://')
    LIMIT 1
");
$stmt->execute([":url" => $localUrl]);
$sub = $stmt->fetch();

if (!$sub) {
    http_response_code(200);
    echo json_encode(["status" => "expired", "remaining_days" => 0, "remaining_hours" => 0, "remaining_minutes" => 0]);
    exit;
}

$endsAt = $sub["trial_ends_at"];

if ($sub["status"] === "active") {
    $endsAt = $sub["renewal_date"];
}

$remainingSeconds = strtotime($endsAt) - time();

if ($remainingSeconds <= 0) {
    echo json_encode(["status" => "expired", "remaining_days" => 0, "remaining_hours" => 0, "remaining_minutes" => 0]);
    exit;
}

echo json_encode([
    "status"            => "active",
    "remaining_days"    => floor($remainingSeconds / 86400),
    "remaining_hours"   => floor(($remainingSeconds % 86400) / 3600),
    "remaining_minutes" => floor(($remainingSeconds % 3600) / 60),
]);

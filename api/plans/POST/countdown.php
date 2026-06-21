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
    WHERE local_server_url = :url
    LIMIT 1
");
$stmt->execute([":url" => $localUrl]);
$sub = $stmt->fetch();

if (!$sub) {
    // TEMP DEBUG
    $check = $pdo->query("SELECT local_server_url FROM subscriptions")->fetchAll(PDO::FETCH_COLUMN);
    http_response_code(200);
    echo json_encode([
        "status" => "expired",
        "debug_received" => $localUrl,
        "debug_all_urls" => $check
    ]);
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

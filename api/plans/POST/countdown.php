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

// No matching row at all
if (!$sub) {
    http_response_code(200);
    echo json_encode(["status" => "expired", "elapsed" => true, "remaining_days" => 0, "remaining_hours" => 0, "remaining_minutes" => 0]);
    exit;
}

$status = $sub["status"];

// Cancelled / suspended — contact team, no date math needed
if ($status === "cancelled" || $status === "suspended") {
    echo json_encode(["status" => $status, "elapsed" => true]);
    exit;
}

// Status already stored as expired in DB
if ($status === "expired") {
    echo json_encode(["status" => "expired", "elapsed" => true, "remaining_days" => 0, "remaining_hours" => 0, "remaining_minutes" => 0]);
    exit;
}

// status is "trial" or "active" — check the relevant date
$endsAt = ($status === "active") ? $sub["renewal_date"] : $sub["trial_ends_at"];

if (!$endsAt) {
    // no date set yet — treat as elapsed so frontend shows the upgrade message instead of a broken countdown
    echo json_encode(["status" => $status, "elapsed" => true, "remaining_days" => 0, "remaining_hours" => 0, "remaining_minutes" => 0]);
    exit;
}

$remainingSeconds = strtotime($endsAt) - time();

if ($remainingSeconds <= 0) {
    // trial or active period has elapsed — status stays "trial"/"active", just flagged elapsed
    echo json_encode(["status" => $status, "elapsed" => true, "remaining_days" => 0, "remaining_hours" => 0, "remaining_minutes" => 0]);
    exit;
}

echo json_encode([
    "status"            => $status, // "trial" or "active"
    "elapsed"           => false,
    "remaining_days"    => floor($remainingSeconds / 86400),
    "remaining_hours"   => floor(($remainingSeconds % 86400) / 3600),
    "remaining_minutes" => floor(($remainingSeconds % 3600) / 60),
]);

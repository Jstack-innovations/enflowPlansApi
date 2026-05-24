<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../SECURE/config.php";

$body  = json_decode(file_get_contents("php://input"), true);
$email = trim($body["email"] ?? "");

if (!$email) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "No email provided"]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT status, trial_ends_at, renewal_date, plan, zara_credits
    FROM subscriptions
    WHERE LOWER(email) = LOWER(:email)
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([":email" => $email]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

$now     = date("Y-m-d H:i:s");
$allowed = false;

if ($sub) {
    if ($sub["status"] === "trial" && $sub["trial_ends_at"] > $now) {
        $allowed = true;
    } elseif ($sub["status"] === "active" && $sub["renewal_date"] >= date("Y-m-d")) {
        $allowed = true;
    }
}

if (!$allowed) {
    http_response_code(403);
    echo json_encode(["status" => "inactive", "message" => "Subscription expired or not found"]);
    exit();
}

echo json_encode([
    "status"        => "active",
    "plan"          => $sub["plan"],
    "zara_credits"  => $sub["zara_credits"],
    "renewal_date"  => $sub["renewal_date"],
    "trial_ends_at" => $sub["trial_ends_at"],
]);

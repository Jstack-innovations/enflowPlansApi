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
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No email provided"]);
    exit();
}

$now = new DateTime();

$stmt = $pdo->prepare("
    SELECT fullname AS name, email, plan, status,
           trial_started_at, trial_ends_at,
           renewal_date, subscription_code,
           zara_credits, zara_credits_used, created_at
    FROM subscriptions
    WHERE LOWER(email) = LOWER(:email)
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([":email" => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "User not found."]);
    exit();
}

$status = $user["status"];

if ($status === "trial" && $user["trial_ends_at"]) {
    $trialEnd = new DateTime($user["trial_ends_at"]);
    if ($trialEnd <= $now) {
        $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE email = :email")
            ->execute([":email" => $email]);
        $status = "expired";
    }
}

if ($status === "active" && $user["renewal_date"]) {
    $renewalDate = new DateTime($user["renewal_date"]);
    if ($renewalDate <= $now) {
        $pdo->prepare("UPDATE subscriptions SET status = 'expired' WHERE email = :email")
            ->execute([":email" => $email]);
        $status = "expired";
    }
}

echo json_encode([
    "name"               => $user["name"],
    "email"              => $user["email"],
    "plan"               => $user["plan"],
    "status"             => $status,
    "trial_started_at"   => $user["trial_started_at"],
    "trial_ends_at"      => $user["trial_ends_at"],
    "subscription_start" => $user["created_at"],
    "subscription_end"   => $user["renewal_date"],
    "subscription_code"  => $user["subscription_code"],
    "zara_credits"       => (int) $user["zara_credits"],
    "zara_credits_used"  => (int) $user["zara_credits_used"],
]);

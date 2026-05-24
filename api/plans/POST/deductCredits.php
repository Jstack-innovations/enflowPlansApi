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

$body   = json_decode(file_get_contents("php://input"), true);
$email  = trim($body["email"] ?? "");
$amount = (int)($body["credits"] ?? 1);

if (!$email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No email provided"]);
    exit();
}

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET zara_credits_used = zara_credits_used + :amount
    WHERE LOWER(email) = LOWER(:email)
    AND (zara_credits - zara_credits_used) >= :amount2
");
$stmt->execute([
    ":amount"  => $amount,
    ":amount2" => $amount,
    ":email"   => $email,
]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["status" => "error", "message" => "Insufficient Zara credits"]);
    exit();
}

echo json_encode(["status" => "success"]);

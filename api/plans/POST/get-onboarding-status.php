<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(200); exit(); }
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed."]);
    exit();
}

require_once __DIR__ . '/../../SECURE/config.php';

$body  = json_decode(file_get_contents("php://input"), true);
$token = trim($body["onboarding_token"] ?? "");

if (!$token) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Onboarding token is required."]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, fullname AS name, email, phone, plan, onboarding_step
    FROM subscriptions
    WHERE onboarding_token = :token
    LIMIT 1
");
$stmt->execute([":token" => $token]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Invalid or expired onboarding session."]);
    exit();
}

echo json_encode([
    "status" => "ok",
    "user" => [
        "id"    => $row["id"],
        "name"  => $row["name"],
        "email" => $row["email"],
        "phone" => $row["phone"],
    ],
    "plan"            => $row["plan"],
    "onboarding_step" => (int)$row["onboarding_step"],
]);


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

$stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE onboarding_token = :token LIMIT 1");
$stmt->execute([":token" => $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Invalid onboarding token."]);
    exit();
}

// All optional — just save whatever they selected
$tools = [
    "pos"        => $body["pos"]        ?? [],
    "whatsapp"   => $body["whatsapp"]   ?? false,
    "social"     => $body["social"]     ?? [],
    "delivery"   => $body["delivery"]   ?? [],
    "accounting" => $body["accounting"] ?? [],
];

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET connected_tools = :tools,
        onboarding_step = 6
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":tools" => json_encode($tools),
    ":token" => $token,
]);

echo json_encode([
    "status"  => "ok",
    "message" => "Connected tools saved.",
]);

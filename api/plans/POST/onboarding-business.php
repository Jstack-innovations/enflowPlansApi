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

$businessName  = trim($body["business_name"] ?? "");
$businessType  = trim($body["business_type"] ?? "");
$country       = trim($body["country"] ?? "");
$currency      = trim($body["currency"] ?? "");
$website       = trim($body["website"] ?? "");
$numLocations  = (int)($body["num_locations"] ?? 0);
$numStaff      = (int)($body["num_staff"] ?? 0);

if (!$businessName || !$businessType || !$country || !$currency) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Business name, type, country and currency are required."]);
    exit();
}

$validTypes = ["restaurant", "fast_food", "lounge_bar", "hotel", "clinic", "ticketing_events"];
if (!in_array($businessType, $validTypes)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Invalid business type."]);
    exit();
}

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET business_name   = :business_name,
        business_type   = :business_type,
        country         = :country,
        currency        = :currency,
        website         = :website,
        num_locations   = :num_locations,
        num_staff       = :num_staff,
        onboarding_step = 4
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":business_name"  => $businessName,
    ":business_type"  => $businessType,
    ":country"        => $country,
    ":currency"       => $currency,
    ":website"        => $website ?: null,
    ":num_locations"  => $numLocations,
    ":num_staff"      => $numStaff,
    ":token"          => $token,
]);

echo json_encode([
    "status"  => "ok",
    "message" => "Business details saved.",
]);

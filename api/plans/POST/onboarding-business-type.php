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

$businessType     = trim($body["business_type"] ?? "");
$businessSubtypes = $body["business_subtypes"] ?? [];

$validTypes = ["restaurant", "fast_food", "lounge_bar", "hotel", "clinic", "ticketing_events"];
if (!$businessType || !in_array($businessType, $validTypes)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "A valid business type is required."]);
    exit();
}

$subtypeRequired = ["restaurant", "fast_food", "lounge_bar"];
$validSubtypes   = ["dine_in", "takeaway", "delivery", "cloud_kitchen", "multi_branch"];

// Ensure it's an array and filter to valid values only
if (!is_array($businessSubtypes)) {
    $businessSubtypes = [];
}
$subtypesToSave = array_values(array_filter($businessSubtypes, fn($s) => in_array($s, $validSubtypes)));

if (in_array($businessType, $subtypeRequired) && count($subtypesToSave) === 0) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "At least one sub-type is required for this business type."]);
    exit();
}

$subtypeJson = count($subtypesToSave) > 0 ? json_encode($subtypesToSave) : null;

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET business_type    = :business_type,
        business_subtype = :business_subtype,
        onboarding_step  = 6
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":business_type"    => $businessType,
    ":business_subtype" => $subtypeJson,
    ":token"            => $token,
]);

echo json_encode([
    "status"  => "ok",
    "message" => "Business type saved.",
]);

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

$businessType    = trim($body["business_type"] ?? "");
$businessSubtype = trim($body["business_subtype"] ?? "");

$validTypes = ["restaurant", "fast_food", "lounge_bar", "hotel", "clinic", "ticketing_events"];
if (!$businessType || !in_array($businessType, $validTypes)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "A valid business type is required."]);
    exit();
}

// Subtype is only valid for types that support it
$validSubtypes = ["dine_in", "takeaway", "delivery", "cloud_kitchen", "multi_branch"];
$subtypeToSave = null;
if ($businessSubtype && in_array($businessSubtype, $validSubtypes)) {
    $subtypeToSave = $businessSubtype;
}

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET business_type    = :business_type,
        business_subtype = :business_subtype,
        onboarding_step  = 6
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":business_type"    => $businessType,
    ":business_subtype" => $subtypeToSave,
    ":token"            => $token,
]);

echo json_encode([
    "status"  => "ok",
    "message" => "Business type saved.",
]);


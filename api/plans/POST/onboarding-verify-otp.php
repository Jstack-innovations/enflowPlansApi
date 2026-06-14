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
$otp   = trim($body["otp"] ?? "");

if (!$token || !$otp) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Token and OTP are required."]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, email_otp, email_otp_expires
    FROM subscriptions
    WHERE onboarding_token = :token
    LIMIT 1
");
$stmt->execute([":token" => $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Invalid onboarding token."]);
    exit();
}

if ($user["email_otp"] !== $otp) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Incorrect OTP. Please try again."]);
    exit();
}

if (strtotime($user["email_otp_expires"]) < time()) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "OTP has expired. Please request a new one."]);
    exit();
}

// Clear OTP + mark email verified
$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET email_otp         = NULL,
        email_otp_expires = NULL,
        email_status      = 'verified',
        onboarding_step   = 3
    WHERE onboarding_token = :token
");
$stmt->execute([":token" => $token]);

echo json_encode([
    "status"  => "ok",
    "message" => "Email verified successfully.",
]);

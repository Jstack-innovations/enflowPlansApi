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

$body     = json_decode(file_get_contents("php://input"), true);
$token    = trim($body["onboarding_token"] ?? "");
$password = trim($body["password"] ?? "");

if (!$token || !$password) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Token and password are required."]);
    exit();
}

if (strlen($password) < 8) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters."]);
    exit();
}

// Validate token
$stmt = $pdo->prepare("SELECT id, email, fullname FROM subscriptions WHERE onboarding_token = :token LIMIT 1");
$stmt->execute([":token" => $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Invalid or used onboarding token."]);
    exit();
}

// Generate OTP
$otp        = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);
$otpExpires = date("Y-m-d H:i:s", strtotime("+10 minutes"));
$hashed     = password_hash($password, PASSWORD_BCRYPT);

// Update password + OTP
$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET password          = :password,
        email_otp         = :otp,
        email_otp_expires = :expires,
        email_status      = 'pending',
        onboarding_step   = 2
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":password" => $hashed,
    ":otp"      => $otp,
    ":expires"  => $otpExpires,
    ":token"    => $token,
]);

// Send OTP email via Resend
$emailPayload = json_encode([
    "from"    => "Enflow <noreply@getenflowai.online>",
    "to"      => [$user["email"]],
    "subject" => "Verify your email — " . $otp,
    "html"    => "
        <div style='background:#080502;padding:40px;font-family:sans-serif;color:#dddddd;'>
            <h2 style='color:#d6a86a;'>Verify your email</h2>
            <p>Hi " . htmlspecialchars($user["fullname"]) . ",</p>
            <p>Your verification code is:</p>
            <div style='font-size:36px;font-weight:bold;letter-spacing:12px;color:#ffffff;margin:24px 0;'>" . $otp . "</div>
            <p style='color:#888;font-size:12px;'>This code expires in 10 minutes.</p>
        </div>
    ",
]);

$ch = curl_init("https://api.resend.com/emails");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $emailPayload,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer " . RESEND_API_KEY,
        "Content-Type: application/json",
    ],
]);
curl_exec($ch);
curl_close($ch);

echo json_encode([
    "status"  => "ok",
    "message" => "Password set. OTP sent to " . $user["email"],
]);

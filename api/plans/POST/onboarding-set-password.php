cat > onboarding-set-password.php << 'EOF'
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
require_once __DIR__ . '/../../SECURE/resendMail.php';

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

$stmt = $pdo->prepare("SELECT id, email, fullname FROM subscriptions WHERE onboarding_token = :token LIMIT 1");
$stmt->execute([":token" => $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Invalid or used onboarding token."]);
    exit();
}

$otp        = str_pad(random_int(0, 999999), 6, "0", STR_PAD_LEFT);
$otpExpires = date("Y-m-d H:i:s", strtotime("+10 minutes"));
$hashed     = password_hash($password, PASSWORD_BCRYPT);

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

$emailResult = sendEmail(
    $user["email"],
    "Verify your email — Your Enflow OTP",
    "
    <div style='background:#080502;padding:40px;font-family:sans-serif;color:#dddddd;'>
        <h2 style='color:#d6a86a;margin-bottom:8px;'>Verify your email</h2>
        <p style='margin-bottom:16px;'>Hi " . htmlspecialchars($user["fullname"]) . ",</p>
        <p>Your verification code is:</p>
        <div style='font-size:40px;font-weight:bold;letter-spacing:16px;color:#ffffff;margin:28px 0;'>" . $otp . "</div>
        <p style='color:#888;font-size:12px;'>This code expires in 10 minutes. Do not share it with anyone.</p>
    </div>
    "
);

if ($emailResult["status"] !== "success") {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Password saved but email failed to send. Try resending."]);
    exit();
}

echo json_encode([
    "status"  => "ok",
    "message" => "Password set. OTP sent to " . $user["email"],
]);

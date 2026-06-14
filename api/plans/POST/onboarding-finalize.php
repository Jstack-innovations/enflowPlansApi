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

$body  = json_decode(file_get_contents("php://input"), true);
$token = trim($body["onboarding_token"] ?? "");

if (!$token) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Onboarding token is required."]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, fullname, email, business_name, plan,
           onboarding_step, email_status
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

// Guard — email must be verified
if ($user["email_status"] !== "verified") {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Email not verified. Please complete OTP verification."]);
    exit();
}

// Guard — must have completed all steps
if ((int)$user["onboarding_step"] < 8) {
    http_response_code(422);
    echo json_encode([
        "status"  => "error",
        "message" => "Onboarding incomplete. Please complete all steps first.",
        "current_step" => (int)$user["onboarding_step"],
    ]);
    exit();
}

// Activate account + clear token
$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET status            = 'active',
        onboarding_token  = NULL,
        onboarding_step   = 9
    WHERE onboarding_token = :token
");
$stmt->execute([":token" => $token]);

// Send welcome email
sendEmail(
    $user["email"],
    "Welcome to EnflowAI — You're all set!",
    "
    <div style='background:#080502;padding:40px;font-family:sans-serif;color:#dddddd;'>
        <h2 style='color:#d6a86a;margin-bottom:8px;'>You're live on Enflow 🎉</h2>
        <p style='margin-bottom:16px;'>Hi " . htmlspecialchars($user["fullname"]) . ",</p>
        <p style='margin-bottom:12px;'>
            <strong style='color:#ffffff;'>" . htmlspecialchars($user["business_name"]) . "</strong> 
            is now active on EnflowAI.
        </p>
        <p style='margin-bottom:24px;color:#aaaaaa;'>
            Your trial is running. Log in to your dashboard and let Zara get to work.
        </p>
        <a href='https://getenflowai.online/login' style='display:inline-block;background:linear-gradient(135deg,#d6a86a,#b8864a);color:#0c0602;padding:14px 32px;border-radius:100px;font-weight:700;font-size:13px;letter-spacing:2px;text-decoration:none;text-transform:uppercase;'>
            Go to Dashboard →
        </a>
        <a href='http://localhost:5173/login' style='display:inline-block;background:linear-gradient(135deg,#d6a86a,#b8864a);color:#0c0602;padding:14px 32px;border-radius:100px;font-weight:700;font-size:13px;letter-spacing:2px;text-decoration:none;text-transform:uppercase;'>
            Go to Dashboard →
        </a>
        <p style='margin-top:32px;color:#555;font-size:11px;'>© 2026 jSTack Innovations · EnflowAI</p>
    </div>
    "
);

echo json_encode([
    "status"  => "ok",
    "message" => "Onboarding complete. Account is now active.",
    "user"    => [
        "id"            => $user["id"],
        "name"          => $user["fullname"],
        "email"         => $user["email"],
        "business_name" => $user["business_name"],
        "plan"          => $user["plan"],
        "status"        => "active",
    ],
]);

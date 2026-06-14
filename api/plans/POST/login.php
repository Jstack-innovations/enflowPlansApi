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
$email    = trim(strtolower($body["email"]    ?? ""));
$password = trim($body["password"] ?? "");

if (!$email || !$password) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Email and password are required."]);
    exit();
}

$stmt = $pdo->prepare("
    SELECT id, fullname, email, phone, password, status,
           trial_ends_at, plan, business_name, business_type,
           logo_url, country, currency, subscription_code,
           zara_credits, zara_credits_used
    FROM subscriptions
    WHERE LOWER(email) = :email
    LIMIT 1
");
$stmt->execute([":email" => $email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    exit();
}

if (!password_verify($password, $user["password"])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    exit();
}

// Check account status
if ($user["status"] === "suspended") {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Your account has been suspended. Please contact support."]);
    exit();
}

if ($user["status"] === "trial") {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Onboarding incomplete. Please complete your setup."]);
    exit();
}

// Check trial expiry
if ($user["trial_ends_at"] && strtotime($user["trial_ends_at"]) < time()) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Your trial has expired. Please upgrade to continue."]);
    exit();
}

// Generate Bearer token
$token       = bin2hex(random_bytes(32));
$tokenExpiry = date("Y-m-d H:i:s", strtotime("+30 days"));

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET auth_token        = :token,
        auth_token_expiry = :expiry
    WHERE id = :id
");
$stmt->execute([
    ":token"  => $token,
    ":expiry" => $tokenExpiry,
    ":id"     => $user["id"],
]);

echo json_encode([
    "status" => "ok",
    "message" => "Login successful.",
    "token"  => $token,
    "expires" => $tokenExpiry,
    "user"   => [
        "id"                => $user["id"],
        "fullname"          => $user["fullname"],
        "email"             => $user["email"],
        "phone"             => $user["phone"],
        "plan"              => $user["plan"],
        "status"            => $user["status"],
        "trial_ends_at"     => $user["trial_ends_at"],
        "business_name"     => $user["business_name"],
        "business_type"     => $user["business_type"],
        "logo_url"          => $user["logo_url"],
        "country"           => $user["country"],
        "currency"          => $user["currency"],
        "subscription_code" => $user["subscription_code"],
        "zara_credits"      => $user["zara_credits"],
        "zara_credits_used" => $user["zara_credits_used"],
    ],
]);

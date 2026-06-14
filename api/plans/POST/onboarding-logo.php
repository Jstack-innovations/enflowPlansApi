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

$token = trim($_POST["onboarding_token"] ?? "");

if (!$token) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Onboarding token is required."]);
    exit();
}

$stmt = $pdo->prepare("SELECT id, subscription_code FROM subscriptions WHERE onboarding_token = :token LIMIT 1");
$stmt->execute([":token" => $token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Invalid onboarding token."]);
    exit();
}

if (!isset($_FILES["logo"]) || $_FILES["logo"]["error"] !== UPLOAD_ERR_OK) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Logo file is required."]);
    exit();
}

$file     = $_FILES["logo"];
$maxSize  = 2 * 1024 * 1024; // 2MB
$allowed  = ["image/jpeg", "image/png", "image/webp", "image/svg+xml"];
$finfo    = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

if ($file["size"] > $maxSize) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Logo must be under 2MB."]);
    exit();
}

if (!in_array($mimeType, $allowed)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Only JPG, PNG, WEBP or SVG allowed."]);
    exit();
}

$ext      = pathinfo($file["name"], PATHINFO_EXTENSION);
$filename = "logo_" . $user["subscription_code"] . "." . $ext;
$uploadDir = __DIR__ . "/../../uploads/logos/";

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destination = $uploadDir . $filename;

if (!move_uploaded_file($file["tmp_name"], $destination)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to save logo. Please try again."]);
    exit();
}

$logoUrl = "/uploads/logos/" . $filename;

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET logo_url        = :logo_url,
        onboarding_step = 5
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":logo_url" => $logoUrl,
    ":token"    => $token,
]);

echo json_encode([
    "status"   => "ok",
    "message"  => "Logo uploaded successfully.",
    "logo_url" => $logoUrl,
]);

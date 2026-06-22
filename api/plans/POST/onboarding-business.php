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

$businessName = trim($_POST["business_name"] ?? "");
$country      = trim($_POST["country"] ?? "");
$currency     = trim($_POST["currency"] ?? "");
$website      = trim($_POST["website"] ?? "");
$numLocations = (int)($_POST["num_locations"] ?? 0);
$numStaff     = (int)($_POST["num_staff"] ?? 0);

if (!$businessName || !$country || !$currency) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Business name, country and currency are required."]);
    exit();
}

// Handle optional logo upload
$logoUrl = null;

if (isset($_FILES["logo"]) && $_FILES["logo"]["error"] === UPLOAD_ERR_OK) {
    $file    = $_FILES["logo"];
    $maxSize = 2 * 1024 * 1024;
    $allowed = ["image/jpeg", "image/png", "image/webp", "image/svg+xml"];

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

    $cloudName = $_ENV["CLOUDINARY_CLOUD_NAME"] ?? getenv("CLOUDINARY_CLOUD_NAME");
    $apiKey    = $_ENV["CLOUDINARY_API_KEY"]    ?? getenv("CLOUDINARY_API_KEY");
    $apiSecret = $_ENV["CLOUDINARY_API_SECRET"] ?? getenv("CLOUDINARY_API_SECRET");
    $publicId  = "logo_" . $user["subscription_code"];
    $timestamp = time();

    $signature = sha1(
        "overwrite=true&public_id=" . $publicId . "&timestamp=" . $timestamp . $apiSecret
    );

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_POSTFIELDS     => [
            "file"      => new CURLFile($file["tmp_name"], $mimeType, $file["name"]),
            "api_key"   => $apiKey,
            "timestamp" => $timestamp,
            "public_id" => $publicId,
            "overwrite" => "true",
            "signature" => $signature,
        ],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (!isset($result["secure_url"])) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Logo upload to Cloudinary failed."]);
        exit();
    }

    $logoUrl = $result["secure_url"];
}

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET business_name   = :business_name,
        country         = :country,
        currency        = :currency,
        website         = :website,
        num_locations   = :num_locations,
        num_staff       = :num_staff,
        logo_url        = COALESCE(:logo_url, logo_url),
        onboarding_step = 4
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":business_name"  => $businessName,
    ":country"        => $country,
    ":currency"       => $currency,
    ":website"        => $website ?: null,
    ":num_locations"  => $numLocations,
    ":num_staff"      => $numStaff,
    ":logo_url"       => $logoUrl,
    ":token"          => $token,
]);

echo json_encode([
    "status"   => "ok",
    "message"  => "Business details saved.",
    "logo_url" => $logoUrl,
]);

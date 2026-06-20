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

$brandVoice  = trim($body["brand_voice"]      ?? "");
$primaryLang = trim($body["primary_language"] ?? "");
$alsoSpeaks  = $body["also_speaks"]     ?? [];   // array — dropdown picks + free text entries
$topGoals    = $body["top_goals"]       ?? [];   // array — multi-select
$hours       = $body["operating_hours"] ?? [];   // array — multi-select (e.g. days/blocks)

if (!$brandVoice || !$primaryLang) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Brand voice and primary language are required."]);
    exit();
}

$validVoices = ["friendly", "professional", "playful", "casual", "formal", "fun", "pidgin"];
if (!in_array($brandVoice, $validVoices)) {
    http_response_code(422);
    echo json_encode(["status" => "error", "message" => "Invalid brand voice."]);
    exit();
}

if (!is_array($alsoSpeaks)) { $alsoSpeaks = []; }
if (!is_array($topGoals))   { $topGoals   = []; }
if (!is_array($hours))      { $hours      = []; }

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET zara_brand_voice  = :brand_voice,
        zara_primary_lang = :primary_lang,
        zara_also_speaks  = :also_speaks,
        zara_top_goals    = :top_goals,
        zara_hours        = :hours,
        onboarding_step   = 8
    WHERE onboarding_token = :token
");
$stmt->execute([
    ":brand_voice"   => $brandVoice,
    ":primary_lang"  => $primaryLang,
    ":also_speaks"   => json_encode($alsoSpeaks),
    ":top_goals"     => json_encode($topGoals),
    ":hours"         => json_encode($hours),
    ":token"         => $token,
]);

echo json_encode([
    "status"  => "ok",
    "message" => "Zara personalization saved.",
]);

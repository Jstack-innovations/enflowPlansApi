<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../SECURE/config.php";

// ── 1. Auth ────────────────────────────────────────────────
$email = $_SESSION["admin_email"] ?? "";

if (!$email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "step" => "auth", "message" => "Not authenticated."]);
    exit();
}

// ── 2. Read body ───────────────────────────────────────────
$body           = json_decode(file_get_contents("php://input"), true);
$tx_ref         = trim($body["tx_ref"]         ?? "");
$transaction_id = trim($body["transaction_id"] ?? "");
$pack_id        = trim($body["pack_id"]        ?? "");

if (!$tx_ref || !$transaction_id || !$pack_id) {
    http_response_code(400);
    echo json_encode([
        "status"  => "error",
        "step"    => "body",
        "message" => "Missing fields.",
        "got"     => ["tx_ref" => $tx_ref, "transaction_id" => $transaction_id, "pack_id" => $pack_id],
    ]);
    exit();
}

// ── 3. Pack registry ───────────────────────────────────────
$PACKS = [
    "starter"    => ["credits" => 500,   "price" => 52250],
    "basic"      => ["credits" => 1000,  "price" => 101200],
    "standard"   => ["credits" => 2500,  "price" => 242000],
    "popular"    => ["credits" => 3000,  "price" => 280500],
    "pro"        => ["credits" => 5000,  "price" => 451000],
    "business"   => ["credits" => 7000,  "price" => 600600],
    "enterprise" => ["credits" => 10000, "price" => 825000],
];

if (!isset($PACKS[$pack_id])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "step" => "pack", "message" => "Invalid pack: $pack_id"]);
    exit();
}

$expectedCredits = $PACKS[$pack_id]["credits"];
$expectedPrice   = $PACKS[$pack_id]["price"];

// ── 4. Duplicate check ─────────────────────────────────────
try {
    $dupCheck = $pdo->prepare("SELECT id FROM zara_topup_logs WHERE transaction_id = :transaction_id LIMIT 1");
    $dupCheck->execute([":transaction_id" => $transaction_id]);
    if ($dupCheck->fetch()) {
        http_response_code(409);
        echo json_encode(["status" => "error", "step" => "duplicate", "message" => "Already processed."]);
        exit();
    }
} catch (Exception $e) {
    // Table might not exist yet
    http_response_code(500);
    echo json_encode(["status" => "error", "step" => "duplicate_check", "message" => $e->getMessage()]);
    exit();
}

// ── 5. Get Flutterwave secret key ──────────────────────────
ob_start();
include __DIR__ . '/../../SECURE/flutterwave-key.php';
$keyOutput  = ob_get_clean();
$keyData    = json_decode($keyOutput, true);
$FLW_SECRET = $keyData['secretKey'] ?? '';

if (!$FLW_SECRET) {
    http_response_code(500);
    echo json_encode(["status" => "error", "step" => "secret_key", "message" => "Secret key not found."]);
    exit();
}

// ── 6. Verify with Flutterwave ─────────────────────────────
$ch = curl_init("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$FLW_SECRET}",
        "Content-Type: application/json",
    ],
]);
$res      = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
    http_response_code(502);
    echo json_encode([
        "status"    => "error",
        "step"      => "flw_verify_request",
        "http_code" => $httpCode,
        "curl_err"  => $curlErr,
    ]);
    exit();
}

$flw    = json_decode($res, true);
$txData = $flw["data"] ?? null;

// ── 7. Validate every field ────────────────────────────────
$checks = [
    "flw_status"       => ($flw["status"]             ?? "") === "success",
    "tx_status" => in_array($txData["status"] ?? "", ["successful", "completed"]),
    "tx_ref_match"     => ($txData["tx_ref"]           ?? "") === $tx_ref,
    "email_match"      => strtolower($txData["customer"]["email"] ?? "") === strtolower($email),
    "amount_ok"        => (float)($txData["amount"]    ?? 0)  >= $expectedPrice,
    "currency_ok"      => strtoupper($txData["currency"] ?? "") === "NGN",
];

$allPassed = !in_array(false, $checks, true);

if (!$allPassed) {
    http_response_code(402);
    echo json_encode([
        "status"  => "error",
        "step"    => "flw_validation",
        "checks"  => $checks,
        "flw_raw" => [
            "status"   => $flw["status"]              ?? null,
            "tx_status"=> $txData["status"]            ?? null,
            "tx_ref"   => $txData["tx_ref"]            ?? null,
            "email"    => $txData["customer"]["email"] ?? null,
            "amount"   => $txData["amount"]            ?? null,
            "currency" => $txData["currency"]          ?? null,
            "expected_price"  => $expectedPrice,
            "session_email"   => $email,
        ],
    ]);
    exit();
}

// ── 8. Credit user ─────────────────────────────────────────
$pdo->beginTransaction();

try {
    $update = $pdo->prepare("
        UPDATE subscriptions
        SET zara_credits = zara_credits + :credits
        WHERE LOWER(email) = LOWER(:email)
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $update->execute([
        ":credits" => $expectedCredits,
        ":email"   => $email,
    ]);

    if ($update->rowCount() === 0) {
        throw new Exception("No subscription row found for email: $email");
    }

    $log = $pdo->prepare("
        INSERT INTO zara_topup_logs (email, transaction_id, pack_id, credits, amount)
        VALUES (:email, :transaction_id, :pack_id, :credits, :amount)
    ");
    $log->execute([
        ":email"          => $email,
        ":transaction_id" => $transaction_id,
        ":pack_id"        => $pack_id,
        ":credits"        => $expectedCredits,
        ":amount"         => $txData["amount"],
    ]);

    $pdo->commit();

    echo json_encode([
        "status"  => "success",
        "message" => "Credits added successfully.",
        "credits" => $expectedCredits,
        "pack"    => $pack_id,
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["status" => "error", "step" => "db", "message" => $e->getMessage()]);
}

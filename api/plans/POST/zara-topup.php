<?php
// POST /zara/topup/verify
require_once __DIR__ . "/../../SECURE/authGuard.php";
require_once __DIR__ . "/../../SECURE/config.php";

// ── 1. Auth — session only, never trust frontend ───────────
$email = $_SESSION["admin_email"] ?? "";

if (!$email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Not authenticated."]);
    exit();
}

// ── 2. Read body — only tx_ref + transaction_id needed ─────
//    pack_id is accepted but credits count is ALWAYS taken
//    from the server-side $PACKS table, never from the client.
$body           = json_decode(file_get_contents("php://input"), true);
$tx_ref         = trim($body["tx_ref"]         ?? "");
$transaction_id = trim($body["transaction_id"] ?? "");
$pack_id        = trim($body["pack_id"]        ?? "");

if (!$tx_ref || !$transaction_id || !$pack_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

// ── 3. Server-side pack registry — source of truth ─────────
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
    echo json_encode(["status" => "error", "message" => "Invalid pack."]);
    exit();
}

$expectedCredits = $PACKS[$pack_id]["credits"];
$expectedPrice   = $PACKS[$pack_id]["price"];

// ── 4. Duplicate transaction guard ─────────────────────────
$dupCheck = $pdo->prepare("
    SELECT id FROM zara_topup_logs
    WHERE transaction_id = :transaction_id
    LIMIT 1
");
$dupCheck->execute([":transaction_id" => $transaction_id]);
if ($dupCheck->fetch()) {
    http_response_code(409);
    echo json_encode(["status" => "error", "message" => "Transaction already processed."]);
    exit();
}

// ── 5. Verify with Flutterwave — all server-side ───────────
ob_start();
include __DIR__ . '/../../SECURE/flutterwave-key.php';
$keyOutput  = ob_get_clean();
$keyData    = json_decode($keyOutput, true);
$FLW_SECRET = $keyData['secretKey'] ?? '';

if (!$FLW_SECRET) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Secret key not found."]);
    exit();
}

$ch = curl_init("https://api.flutterwave.com/v3/transactions/{$transaction_id}/verify");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer {$FLW_SECRET}",
        "Content-Type: application/json",
    ],
]);
$res  = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($info["http_code"] !== 200) {
    http_response_code(502);
    echo json_encode(["status" => "error", "message" => "Could not reach Flutterwave."]);
    exit();
}

$flw    = json_decode($res, true);
$txData = $flw["data"] ?? null;

// Every field verified server-side:
// - Flutterwave status must be successful
// - tx_ref must match what we sent
// - customer email must match the session email
// - amount paid must be >= expected pack price
// - currency must be NGN
if (
    !$txData ||
    ($flw["status"]   ?? "")                              !== "success"     ||
    ($txData["status"] ?? "")                             !== "successful"  ||
    ($txData["tx_ref"] ?? "")                             !== $tx_ref       ||
    strtolower($txData["customer"]["email"] ?? "")        !== strtolower($email) ||
    (float) ($txData["amount"] ?? 0)                      < $expectedPrice  ||
    strtoupper($txData["currency"] ?? "")                 !== "NGN"
) {
    http_response_code(402);
    echo json_encode(["status" => "error", "message" => "Payment verification failed."]);
    exit();
}

// ── 6. Credit user inside subscriptions table ──────────────
$pdo->beginTransaction();

try {
    // Add credits to the user's most recent subscription row
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
        throw new Exception("No subscription row found for this email.");
    }

    // Log to prevent replays
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
    echo json_encode(["status" => "error", "message" => "DB error: " . $e->getMessage()]);
}

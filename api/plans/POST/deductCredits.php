<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../SECURE/config.php";

$body   = json_decode(file_get_contents("php://input"), true);
$email  = trim($body["email"] ?? "");
$amount = (int)($body["credits"] ?? 1);

if (!$email) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "No email provided"]);
    exit();
}

$stmt = $pdo->prepare("
    UPDATE subscriptions
    SET zara_credits_used = zara_credits_used + :amount
    WHERE LOWER(email) = LOWER(:email)
    AND (zara_credits - zara_credits_used) >= :amount2
");
$stmt->execute([
    ":amount"  => $amount,
    ":amount2" => $amount,
    ":email"   => $email,
]);

if ($stmt->rowCount() === 0) {
    echo json_encode(["status" => "error", "message" => "Insufficient Zara credits"]);
    exit();
}

// Check remaining
$check = $pdo->prepare("
    SELECT zara_credits,
           (zara_credits - zara_credits_used) AS remaining,
           low_credit_alert_sent
    FROM subscriptions
    WHERE LOWER(email) = LOWER(:email)
    ORDER BY created_at DESC
    LIMIT 1
");
$check->execute([":email" => $email]);
$row       = $check->fetch(PDO::FETCH_ASSOC);
$remaining = (int)($row["remaining"] ?? 0);
$total     = (int)($row["zara_credits"] ?? 0);
$alertSent = (int)($row["low_credit_alert_sent"] ?? 0);
$usedPct   = $total > 0 ? round((($total - $remaining) / $total) * 100) : 0;
$lowCredit = $usedPct >= 80;

if ($lowCredit && !$alertSent) {
    $pdo->prepare("
        UPDATE subscriptions SET low_credit_alert_sent = 1
        WHERE LOWER(email) = LOWER(:email)
    ")->execute([":email" => $email]);

    require_once __DIR__ . '/../../SECURE/resendMail.php';

    $emailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8">
<style>body{margin:0;padding:0;background:#eae6de;font-family:'DM Sans',Arial,sans-serif;}</style>
</head>
<body>
<table width="100%" bgcolor="#eae6de" cellpadding="0" cellspacing="0">
<tr><td align="center" style="padding:40px 16px;">
<table width="600" style="max-width:600px;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(26,24,20,0.10);">
  <tr><td align="center" style="background:#1a1814;padding:48px 48px 40px;">
    <img src="https://plans.getenflowai.online/logo.png" alt="EnflowAI" width="130" style="display:block;height:auto;margin-bottom:28px;" />
    <div style="display:inline-block;background:rgba(248,113,113,0.12);border:1px solid rgba(248,113,113,0.3);border-radius:100px;padding:7px 18px;font-size:11px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:#f87171;margin-bottom:24px;">⚠️ &nbsp; Low Credits Warning</div>
    <h1 style="margin:0;font-family:'DM Serif Display',Georgia,serif;font-size:30px;font-weight:400;color:rgba(255,255,255,0.90);line-height:1.3;">Your Zara credits<br><em style="color:#f87171;font-style:italic;">are running low</em></h1>
  </td></tr>
  <tr><td style="background:#ffffff;padding:44px 48px;">
    <p style="margin:0 0 24px;font-size:15px;color:#444;line-height:1.8;">You've used <strong style="color:#f87171;">{$usedPct}%</strong> of your Zara AI credits. You have <strong style="color:#1a1814;">{$remaining} credits</strong> remaining.</p>
    <table width="100%" style="border-radius:12px;overflow:hidden;border:1px solid rgba(26,24,20,0.08);margin-bottom:32px;">
      <tr><td style="padding:16px 20px;background:#faf9f7;border-bottom:1px solid rgba(26,24,20,0.06);">
        <p style="margin:0 0 4px;font-size:11px;color:#9e9589;text-transform:uppercase;letter-spacing:0.08em;">Credits Remaining</p>
        <strong style="font-size:15px;color:#f87171;">{$remaining} credits</strong>
      </td></tr>
      <tr><td style="padding:16px 20px;background:#faf9f7;">
        <p style="margin:0 0 4px;font-size:11px;color:#9e9589;text-transform:uppercase;letter-spacing:0.08em;">Usage</p>
        <strong style="font-size:15px;color:#1a1814;">{$usedPct}% used</strong>
      </td></tr>
    </table>
    <table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
      <a href="https://plans.getenflowai.online" style="display:inline-block;background:#1a1814;color:#ede9e1;text-decoration:none;padding:16px 36px;border-radius:10px;font-size:14px;font-weight:500;">Subscribe &nbsp;→</a>
    </td></tr></table>
  </td></tr>
  <tr><td align="center" style="background:#f5f2ec;border-top:1px solid rgba(26,24,20,0.07);padding:28px 40px;">
    <p style="margin:0 0 6px;font-size:12px;color:#b8b0a4;">Questions? <a href="mailto:hello@getenflowai.online" style="color:#a07848;text-decoration:none;">hello@getenflowai.online</a></p>
    <p style="margin:0;font-size:11px;color:#c8c2b8;">© 2026 EnflowAI. All rights reserved.</p>
  </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;

    sendEmail($email, "⚠️ Your Zara Credits Are Running Low", $emailBody);
}

echo json_encode([
    "status"    => "success",
    "remaining" => $remaining,
    "low"       => $lowCredit,
]);

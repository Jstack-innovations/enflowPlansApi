<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../SECURE/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$fullname     = $data['fullname'] ?? '';
$username     = $data['username'] ?? '';
$email        = $data['email'] ?? '';
$phone        = $data['phone'] ?? '';
$country      = $data['country'] ?? '';
$dob          = $data['dob'] ?? '';
$gender       = $data['gender'] ?? '';
$businessType = $data['businessType'] ?? '';
$businessName = $data['businessName'] ?? '';
$plan         = $data['plan'] ?? '';
$tx_id        = $data['transaction_id'] ?? '';

if (!$fullname || !$email || !$phone || !$plan || !$tx_id) {
    echo json_encode(["status" => "error", "message" => "Missing required fields"]);
    exit;
}

/* ===== GET SECRET KEY ===== */
ob_start();
include __DIR__ . '/../../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();

$keyData   = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode(["status" => "error", "message" => "Secret key not found"]);
    exit;
}

/* ===== VERIFY PAYMENT ===== */
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$tx_id/verify",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => "GET",
    CURLOPT_HTTPHEADER     => [
        "Authorization: Bearer $secretKey",
        "Content-Type: application/json"
    ],
]);

$response = curl_exec($curl);

if (curl_errno($curl)) {
    echo json_encode(["status" => "error", "message" => "Payment gateway error"]);
    exit;
}

curl_close($curl);

$result = json_decode($response, true);

if (
    !$result ||
    $result['status'] !== 'success' ||
    $result['data']['status'] !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}

/* ===== TRUST FLUTTERWAVE AMOUNT ===== */
$amount = (float)$result['data']['amount'];

/* ===== DUPLICATE CHECK ===== */
$dup = $conn->prepare("SELECT id FROM subscriptions WHERE transaction_id = ?");
$dup->bind_param("s", $tx_id);
$dup->execute();
$dup->store_result();

if ($dup->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Already processed"]);
    exit;
}

/* ===== GENERATE SUB CODE ===== */
$subscriptionCode = "SUB-" . strtoupper(substr(md5(uniqid()), 0, 10));

/* ===== RENEWAL DATE ===== */
if (stripos($plan, "annual") !== false) {
    $renewalDate = date("Y-m-d", strtotime("+1 year"));
} else {
    $renewalDate = date("Y-m-d", strtotime("+1 month"));
}


/* ===== ZARA CREDITS BASED ON PLAN ===== */
if (stripos($plan, "zara + app") !== false || stripos($plan, "zara+app") !== false) {
    $zaraCredits = 1500;
} elseif (stripos($plan, "enterprise") !== false) {
    $zaraCredits = 3000;
} elseif (stripos($plan, "zara") !== false) {
    $zaraCredits = 1000;
} else {
    $zaraCredits = 0; // Web only — no Zara
}


/* ===== CHECK IF USER ALREADY EXISTS (was on trial) ===== */
$checkStmt = $conn->prepare("SELECT id FROM subscriptions WHERE LOWER(email) = LOWER(?) LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    // ── Existing user (trial → paid) — UPDATE their row ──
    $updateStmt = $conn->prepare("
        UPDATE subscriptions SET
            fullname          = ?,
            username          = ?,
            phone             = ?,
            country           = ?,
            dob               = ?,
            gender            = ?,
            business_type     = ?,
            business_name     = ?,
            plan              = ?,
            amount            = ?,
            transaction_id    = ?,
            subscription_code = ?,
            status            = 'active',
            renewal_date      = ?,
            zara_credits      = ?,
            zara_credits_used = 0
        WHERE LOWER(email) = LOWER(?)
    ");
$updateStmt->bind_param(
    "sssssssssdsssis",  // ← 15 chars, removed one s
    $fullname,
    $username,
    $phone,
    $country,
    $dob,
    $gender,
    $businessType,
    $businessName,
    $plan,
    $amount,
    $tx_id,
    $subscriptionCode,
    $renewalDate,
    $zaraCredits,
    $email
);

    if (!$updateStmt->execute()) {
        echo json_encode(["status" => "error", "message" => $updateStmt->error]);
        exit;
    }

} else {
    // ── Brand new user — INSERT fresh row ──
    $stmt = $conn->prepare("
        INSERT INTO subscriptions (
            fullname, username, email, phone, country,
            dob, gender, business_type, business_name,
            plan, amount, transaction_id, subscription_code,
            status, renewal_date, zara_credits
        )
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
    ");
    $stmt->bind_param(
        "ssssssssssdsssi",
        $fullname, $username, $email, $phone, $country,
        $dob, $gender, $businessType, $businessName,
        $plan, $amount, $tx_id, $subscriptionCode,
        $renewalDate, $zaraCredits
    );

    if (!$stmt->execute()) {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
        exit;
    }
}

/* ===== SEND NOTIFICATIONS ===== */
$subscriptionCode = $subscriptionCode;
$renewalDate = $renewalDate;
$zaraCredits = $zaraCredits;

// ── TELEGRAM ──
$botToken = getenv("TELEGRAM_BOT_TOKEN");
$chatId   = getenv("TELEGRAM_CHAT_ID");

$message = "
💳 *New Payment Received!*

👤 *Name:* {$fullname}
🏢 *Business:* {$businessName}
📧 *Email:* {$email}
📞 *Phone:* {$phone}
📦 *Plan:* {$plan}
💰 *Amount:* ₦{$amount}
🔑 *Sub Code:* {$subscriptionCode}
📅 *Renewal:* {$renewalDate}
⚡ *Zara Credits:* {$zaraCredits}
";

$url = "https://api.telegram.org/bot{$botToken}/sendMessage";
$payload = http_build_query([
    "chat_id"    => $chatId,
    "text"       => $message,
    "parse_mode" => "Markdown"
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// ── EMAIL ──
require_once __DIR__ . '/../../SECURE/resendMail.php';

$firstName = explode(' ', trim($fullname))[0];

$emailBody = "
<!DOCTYPE html>
<html lang='en'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>Welcome to EnflowAI</title>
</head>
<body style='margin:0;padding:0;background:#eae6de;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0'>
<tr><td align='center' style='padding:40px 16px;'>
<table width='600' cellpadding='0' cellspacing='0' style='max-width:600px;border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.10);'>

  <!-- HEADER -->
  <tr>
    <td align='center' style='background:#1a1814;padding:48px 40px 40px;'>
      <img src='https://getenflowai.online/assets/logo.png' width='140' style='height:auto;margin-bottom:28px;' />
      <div style='background:rgba(160,120,72,0.12);border:1px solid rgba(201,168,112,0.28);border-radius:100px;padding:7px 18px;display:inline-block;font-size:11px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:#c9a870;margin-bottom:24px;'>
        ✦ &nbsp; Subscription Activated
      </div>
      <h1 style='margin:0 0 12px;font-size:32px;font-weight:400;color:rgba(255,255,255,0.90);line-height:1.2;'>
        Welcome aboard, <em style='font-style:italic;color:#c9a870;'>{$firstName}</em>
      </h1>
      <p style='margin:0;font-size:15px;color:rgba(255,255,255,0.42);font-weight:300;max-width:380px;line-height:1.75;'>
        Your payment was successful. You now have full access to EnflowAI.
      </p>
    </td>
  </tr>

  <!-- PLAN DETAILS -->
  <tr>
    <td style='background:#ffffff;padding:48px 40px;'>
      <p style='margin:0 0 8px;font-size:11px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#a07848;'>Your Subscription</p>
      <h2 style='margin:0 0 28px;font-size:22px;font-weight:400;color:#1a1814;'>Plan Details</h2>

      <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
        <tr>
          <td style='padding:14px 16px;background:#faf9f7;border:1px solid rgba(26,24,20,0.08);border-radius:10px 10px 0 0;border-bottom:none;'>
            <span style='font-size:12px;color:#9e9589;'>Plan</span><br>
            <strong style='font-size:15px;color:#1a1814;'>{$plan}</strong>
          </td>
        </tr>
        <tr>
          <td style='padding:14px 16px;background:#faf9f7;border:1px solid rgba(26,24,20,0.08);border-bottom:none;'>
            <span style='font-size:12px;color:#9e9589;'>Amount Paid</span><br>
            <strong style='font-size:15px;color:#1a1814;'>₦{$amount}</strong>
          </td>
        </tr>
        <tr>
          <td style='padding:14px 16px;background:#faf9f7;border:1px solid rgba(26,24,20,0.08);border-bottom:none;'>
            <span style='font-size:12px;color:#9e9589;'>Subscription Code</span><br>
            <strong style='font-size:15px;color:#1a1814;'>{$subscriptionCode}</strong>
          </td>
        </tr>
        <tr>
          <td style='padding:14px 16px;background:#faf9f7;border:1px solid rgba(26,24,20,0.08);border-bottom:none;'>
            <span style='font-size:12px;color:#9e9589;'>Renewal Date</span><br>
            <strong style='font-size:15px;color:#1a1814;'>{$renewalDate}</strong>
          </td>
        </tr>
        <tr>
          <td style='padding:14px 16px;background:#faf9f7;border:1px solid rgba(26,24,20,0.08);border-radius:0 0 10px 10px;'>
            <span style='font-size:12px;color:#9e9589;'>Zara AI Credits</span><br>
            <strong style='font-size:15px;color:#1a1814;'>{$zaraCredits} credits</strong>
          </td>
        </tr>
      </table>

      <!-- CTA -->
      <table width='100%' cellpadding='0' cellspacing='0' style='margin-bottom:32px;'>
        <tr><td align='center'>
          <a href='https://getenflowai.online' style='display:inline-block;background:#1a1814;color:#ede9e1;text-decoration:none;padding:16px 36px;border-radius:10px;font-size:14px;font-weight:500;letter-spacing:0.02em;'>
            Access Your Dashboard &nbsp;→
          </a>
        </td></tr>
      </table>

      <!-- Brand strip -->
      <table width='100%' cellpadding='0' cellspacing='0'>
        <tr><td style='background:#1a1814;border-radius:14px;padding:28px 30px;text-align:center;'>
          <p style='margin:0 0 8px;font-size:16px;font-style:italic;color:rgba(255,255,255,0.70);line-height:1.7;'>\"Built for Nigeria's food industry — adaptive, intelligent, and designed to scale with you.\"</p>
          <p style='margin:0;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#c9a870;font-weight:500;'>The EnflowAI Team</p>
        </td></tr>
      </table>
    </td>
  </tr>

  <!-- FOOTER -->
  <tr>
    <td align='center' style='background:#f5f2ec;border-top:1px solid rgba(26,24,20,0.07);padding:32px 40px;'>
      <p style='margin:0 0 6px;font-size:12px;color:#b8b0a4;font-weight:300;'>Questions? Reply to this email or reach us at <a href='mailto:hello@getenflowai.online' style='color:#a07848;text-decoration:none;'>hello@getenflowai.online</a></p>
      <p style='margin:8px 0 0;font-size:11px;color:#c8c2b8;'>© 2026 EnflowAI. All rights reserved.</p>
    </td>
  </tr>

</table>
</td></tr>
</table>
</body>
</html>
";

sendEmail(
    $email,
    "Your EnflowAI Subscription is Active 🎉",
    $emailBody
);

echo json_encode([
    "status"            => "success",
    "subscription_code" => $subscriptionCode,
    "renewal_date"      => $renewalDate,
    "zara_credits"      => $zaraCredits,
]);

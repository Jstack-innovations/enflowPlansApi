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
    $zaraCredits = 0;
}

/* ===== CHECK IF USER ALREADY EXISTS ===== */
$checkStmt = $conn->prepare("SELECT id FROM subscriptions WHERE LOWER(email) = LOWER(?) LIMIT 1");
$checkStmt->bind_param("s", $email);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
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
        "sssssssssdsssis",
        $fullname, $username, $phone, $country, $dob,
        $gender, $businessType, $businessName, $plan,
        $amount, $tx_id, $subscriptionCode, $renewalDate,
        $zaraCredits, $email
    );

    if (!$updateStmt->execute()) {
        echo json_encode(["status" => "error", "message" => $updateStmt->error]);
        exit;
    }

} else {
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

/* ===== TELEGRAM ===== */
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

/* ===== EMAIL ===== */
require_once __DIR__ . '/../../SECURE/resendMail.php';

$firstName = explode(' ', trim($fullname))[0];

$emailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>Your EnflowAI Subscription is Active</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap');
  * { box-sizing: border-box; }
  body { margin: 0; padding: 0; background-color: #eae6de; font-family: 'DM Sans', Arial, sans-serif; -webkit-font-smoothing: antialiased; }
  @media only screen and (max-width: 620px) {
    .email-wrapper { padding: 16px 12px !important; }
    .main-card { border-radius: 16px !important; }
    .hero-block { padding: 40px 24px 36px !important; }
    .body-block { padding: 36px 24px 32px !important; }
    .footer-block { padding: 24px 20px !important; }
    .hero-title { font-size: 26px !important; line-height: 34px !important; }
  }
</style>
</head>
<body style="margin:0; padding:0; background-color:#eae6de;">
<table width="100%" border="0" cellspacing="0" cellpadding="0" bgcolor="#eae6de">
<tr>
<td class="email-wrapper" align="center" style="padding:40px 16px;">
  <table class="main-card" width="600" border="0" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;border-radius:20px;overflow:hidden;box-shadow:0 4px 6px rgba(26,24,20,0.04),0 20px 60px rgba(26,24,20,0.10);">

    <!-- HERO -->
    <tr>
      <td class="hero-block" align="center" style="background:#1a1814;padding:52px 48px 48px;">
        <table border="0" cellspacing="0" cellpadding="0" style="margin-bottom:32px;">
          <tr><td align="center">
            <img src="https://getenflowai.online/assets/logo.png" alt="EnflowAI" width="140" style="display:block;height:auto;max-height:48px;width:auto;max-width:140px;object-fit:contain;" />
          </td></tr>
        </table>
        <table border="0" cellspacing="0" cellpadding="0" width="48" style="margin:0 auto 28px;">
          <tr><td height="1" style="background:linear-gradient(90deg,transparent,rgba(201,168,112,0.6),transparent);line-height:1px;font-size:1px;">&nbsp;</td></tr>
        </table>
        <table border="0" cellspacing="0" cellpadding="0" style="margin:0 auto 24px;">
          <tr><td align="center" style="background:rgba(160,120,72,0.12);border:1px solid rgba(201,168,112,0.28);border-radius:100px;padding:7px 18px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.14em;text-transform:uppercase;color:#c9a870;">
            ✦ &nbsp; Payment Confirmed
          </td></tr>
        </table>
        <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:32px;">
          <tr><td align="center" style="border-radius:14px;overflow:hidden;line-height:0;">
            <img src="https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&q=80&auto=format&fit=crop" alt="Restaurant" width="504" style="display:block;width:100%;max-width:504px;height:auto;border-radius:14px;filter:brightness(0.75) saturate(0.9);" />
          </td></tr>
        </table>
        <h1 class="hero-title" style="margin:0 0 14px;font-family:'DM Serif Display',Georgia,serif;font-size:34px;font-weight:400;line-height:1.2;color:rgba(255,255,255,0.90);letter-spacing:-0.01em;">
          Welcome aboard,<br><em style="font-style:italic;color:#c9a870;">{$firstName}</em>
        </h1>
        <p style="margin:0 auto;font-family:'DM Sans',Arial,sans-serif;font-size:15px;line-height:1.75;color:rgba(255,255,255,0.42);font-weight:300;max-width:380px;">
          Your payment was successful and your subscription is now active. You have full access to EnflowAI.
        </p>
      </td>
    </tr>

    <!-- BODY -->
    <tr>
      <td class="body-block" style="background:#ffffff;padding:48px 48px 44px;">

        <p style="margin:0 0 10px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#a07848;">Your Subscription</p>
        <h2 style="margin:0 0 24px;font-family:'DM Serif Display',Georgia,serif;font-size:24px;font-weight:400;line-height:1.4;color:#1a1814;">Plan Details</h2>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;border-radius:12px;overflow:hidden;border:1px solid rgba(26,24,20,0.08);">
          <tr><td style="padding:16px 20px;background:#faf9f7;border-bottom:1px solid rgba(26,24,20,0.06);">
            <p style="margin:0 0 4px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;color:#9e9589;text-transform:uppercase;letter-spacing:0.08em;">Plan</p>
            <strong style="font-family:'DM Sans',Arial,sans-serif;font-size:15px;color:#1a1814;font-weight:500;">{$plan}</strong>
          </td></tr>
          <tr><td style="padding:16px 20px;background:#faf9f7;border-bottom:1px solid rgba(26,24,20,0.06);">
            <p style="margin:0 0 4px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;color:#9e9589;text-transform:uppercase;letter-spacing:0.08em;">Amount Paid</p>
            <strong style="font-family:'DM Sans',Arial,sans-serif;font-size:15px;color:#1a1814;font-weight:500;">&#8358;{$amount}</strong>
          </td></tr>
          <tr><td style="padding:16px 20px;background:#faf9f7;border-bottom:1px solid rgba(26,24,20,0.06);">
            <p style="margin:0 0 4px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;color:#9e9589;text-transform:uppercase;letter-spacing:0.08em;">Subscription Code</p>
            <strong style="font-family:'DM Sans',Arial,sans-serif;font-size:15px;color:#1a1814;font-weight:500;">{$subscriptionCode}</strong>
          </td></tr>
          <tr><td style="padding:16px 20px;background:#faf9f7;border-bottom:1px solid rgba(26,24,20,0.06);">
            <p style="margin:0 0 4px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;color:#9e9589;text-transform:uppercase;letter-spacing:0.08em;">Next Renewal</p>
            <strong style="font-family:'DM Sans',Arial,sans-serif;font-size:15px;color:#1a1814;font-weight:500;">{$renewalDate}</strong>
          </td></tr>
          <tr><td style="padding:16px 20px;background:#faf9f7;">
            <p style="margin:0 0 4px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;color:#9e9589;text-transform:uppercase;letter-spacing:0.08em;">Zara AI Credits</p>
            <strong style="font-family:'DM Sans',Arial,sans-serif;font-size:15px;color:#a07848;font-weight:500;">{$zaraCredits} credits</strong>
          </td></tr>
        </table>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;"><tr><td height="1" style="background:rgba(26,24,20,0.07);line-height:1px;font-size:1px;">&nbsp;</td></tr></table>

        <p style="margin:0 0 10px;font-family:'DM Sans',Arial,sans-serif;font-size:11px;font-weight:600;letter-spacing:0.16em;text-transform:uppercase;color:#a07848;">What you get</p>
        <h2 style="margin:0 0 20px;font-family:'DM Serif Display',Georgia,serif;font-size:22px;font-weight:400;line-height:1.4;color:#1a1814;">Everything included in your plan</h2>

        <table border="0" cellspacing="0" cellpadding="0" width="100%" style="margin-bottom:28px;"><tr><td align="center" style="border-radius:12px;overflow:hidden;line-height:0;">
          <img src="https://images.unsplash.com/photo-1551218808-94e220e084d2?w=800&q=80&auto=format&fit=crop" alt="Restaurant Technology" width="504" style="display:block;width:100%;max-width:504px;height:200px;object-fit:cover;border-radius:12px;filter:brightness(0.88) saturate(0.85);" />
        </td></tr></table>

        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
          <tr><td style="padding-bottom:14px;"><table border="0" cellspacing="0" cellpadding="0"><tr>
            <td valign="top" style="padding-right:14px;padding-top:2px;"><div style="width:22px;height:22px;background:rgba(160,120,72,0.10);border:1px solid rgba(160,120,72,0.25);border-radius:50%;text-align:center;line-height:22px;font-size:10px;color:#a07848;">✓</div></td>
            <td><p style="margin:0;font-family:'DM Sans',Arial,sans-serif;font-size:14px;font-weight:500;color:#1a1814;line-height:1.5;">Full platform access from day one</p><p style="margin:4px 0 0;font-family:'DM Sans',Arial,sans-serif;font-size:13px;color:#9e9589;font-weight:300;line-height:1.6;">Smart automation, real-time insights, and universal integrations.</p></td>
          </tr></table></td></tr>
          <tr><td style="padding-bottom:14px;"><table border="0" cellspacing="0" cellpadding="0"><tr>
            <td valign="top" style="padding-right:14px;padding-top:2px;"><div style="width:22px;height:22px;background:rgba(160,120,72,0.10);border:1px solid rgba(160,120,72,0.25);border-radius:50%;text-align:center;line-height:22px;font-size:10px;color:#a07848;">✓</div></td>
            <td><p style="margin:0;font-family:'DM Sans',Arial,sans-serif;font-size:14px;font-weight:500;color:#1a1814;line-height:1.5;">Zara AI assistant — {$zaraCredits} credits loaded</p><p style="margin:4px 0 0;font-family:'DM Sans',Arial,sans-serif;font-size:13px;color:#9e9589;font-weight:300;line-height:1.6;">Your intelligent business assistant ready to work from today.</p></td>
          </tr></table></td></tr>
          <tr><td style="padding-bottom:14px;"><table border="0" cellspacing="0" cellpadding="0"><tr>
            <td valign="top" style="padding-right:14px;padding-top:2px;"><div style="width:22px;height:22px;background:rgba(160,120,72,0.10);border:1px solid rgba(160,120,72,0.25);border-radius:50%;text-align:center;line-height:22px;font-size:10px;color:#a07848;">✓</div></td>
            <td><p style="margin:0;font-family:'DM Sans',Arial,sans-serif;font-size:14px;font-weight:500;color:#1a1814;line-height:1.5;">Priority support &amp; feature previews</p><p style="margin:4px 0 0;font-family:'DM Sans',Arial,sans-serif;font-size:13px;color:#9e9589;font-weight:300;line-height:1.6;">Direct access to our team and first look at new features.</p></td>
          </tr></table></td></tr>
          <tr><td><table border="0" cellspacing="0" cellpadding="0"><tr>
            <td valign="top" style="padding-right:14px;padding-top:2px;"><div style="width:22px;height:22px;background:rgba(160,120,72,0.10);border:1px solid rgba(160,120,72,0.25);border-radius:50%;text-align:center;line-height:22px;font-size:10px;color:#a07848;">✓</div></td>
            <td><p style="margin:0;font-family:'DM Sans',Arial,sans-serif;font-size:14px;font-weight:500;color:#1a1814;line-height:1.5;">Private operator community</p><p style="margin:4px 0 0;font-family:'DM Sans',Arial,sans-serif;font-size:13px;color:#9e9589;font-weight:300;line-height:1.6;">Connect with other food business owners and grow together.</p></td>
          </tr></table></td></tr>
        </table>

        <!-- CTA -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="margin-bottom:36px;">
          <tr><td align="center">
            <a href="https://getenflowai.online" style="display:inline-block;background:#1a1814;color:#ede9e1;text-decoration:none;padding:16px 36px;border-radius:10px;font-family:'DM Sans',Arial,sans-serif;font-size:14.5px;font-weight:500;letter-spacing:0.02em;">
              Access Your Dashboard &nbsp;→
            </a>
          </td></tr>
        </table>

        <!-- Brand strip -->
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
          <tr><td style="background:#1a1814;border-radius:14px;padding:28px 30px;text-align:center;">
            <img src="https://getenflowai.online/assets/logo.png" alt="" style="display:block;margin:0 auto 14px;width:150px;height:auto;opacity:0.6;" />
            <p style="margin:0;font-family:'DM Serif Display',Georgia,serif;font-size:16px;font-style:italic;color:rgba(255,255,255,0.70);line-height:1.7;">"Built for Nigeria's food industry — adaptive, intelligent, and designed to scale with you."</p>
            <p style="margin:12px 0 0;font-family:'DM Sans',Arial,sans-serif;font-size:11px;letter-spacing:0.12em;text-transform:uppercase;color:#c9a870;font-weight:500;">The EnflowAI Team</p>
          </td></tr>
        </table>
      </td>
    </tr>

    <!-- FOOTER -->
    <tr>
      <td class="footer-block" align="center" style="background:#f5f2ec;border-top:1px solid rgba(26,24,20,0.07);padding:32px 40px;">
        <table border="0" cellspacing="0" cellpadding="0" style="margin:0 auto 20px;">
          <tr><td align="center">
            <img src="https://getenflowai.online/assets/icon.png" alt="EnflowAI" width="90" style="display:block;height:auto;max-height:30px;width:auto;max-width:90px;opacity:0.45;" />
          </td></tr>
        </table>
        <p style="margin:0 0 6px;font-family:'DM Sans',Arial,sans-serif;font-size:12.5px;line-height:1.75;color:#b8b0a4;font-weight:300;">You're receiving this because you subscribed to EnflowAI.</p>
        <p style="margin:0 0 16px;font-family:'DM Sans',Arial,sans-serif;font-size:12.5px;line-height:1.75;color:#b8b0a4;font-weight:300;">Questions? Reach us at <a href="mailto:hello@getenflowai.online" style="color:#a07848;text-decoration:none;">hello@getenflowai.online</a></p>
        <table border="0" cellspacing="0" cellpadding="0" style="margin:0 auto 16px;">
          <tr>
            <td style="padding:0 8px;"><a href="#" style="font-family:'DM Sans',Arial,sans-serif;font-size:12px;color:#b8b0a4;text-decoration:none;">Privacy Policy</a></td>
            <td style="color:#d9d3c7;font-size:12px;">·</td>
            <td style="padding:0 8px;"><a href="#" style="font-family:'DM Sans',Arial,sans-serif;font-size:12px;color:#b8b0a4;text-decoration:none;">Terms</a></td>
          </tr>
        </table>
        <p style="margin:0;font-family:'DM Sans',Arial,sans-serif;font-size:11.5px;color:#c8c2b8;font-weight:300;letter-spacing:0.02em;">© 2026 EnflowAI. All rights reserved.</p>
      </td>
    </tr>

  </table>
</td>
</tr>
</table>
</body>
</html>
HTML;

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

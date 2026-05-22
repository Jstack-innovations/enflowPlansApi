<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../SECURE/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$fullname     = trim($data['fullname']       ?? '');
$username     = trim($data['username']       ?? '');
$email        = trim($data['email']          ?? '');
$phone        = trim($data['phone']          ?? '');
$country      = trim($data['country']        ?? '');
$dob          = trim($data['dob']            ?? '');
$gender       = trim($data['gender']         ?? '');
$businessType = trim($data['businessType']   ?? '');
$businessName = trim($data['businessName']   ?? '');
$plan         = trim($data['plan']           ?? '');
$amount       = (float)($data['amount']      ?? 0);   // ← cast to float for 'd' bind
$tx_id        = trim($data['transaction_id'] ?? '');

if (!$tx_id || !$email || $amount <= 0) {
    echo json_encode(["status" => "error", "message" => "Missing required data"]);
    exit;
}


#################################################
# 🔐 FETCH SECRET KEY
#################################################

ob_start();
include __DIR__ . '/../../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();

$keyData   = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode(["status" => "error", "message" => "Payment configuration error"]);
    exit;
}


#################################################
# 🔐 VERIFY TRANSACTION WITH FLUTTERWAVE (backend only)
#################################################

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
    echo json_encode(["status" => "error", "message" => "Payment gateway error: " . curl_error($curl)]);
    curl_close($curl);
    exit;
}

curl_close($curl);

$result = json_decode($response, true);

// Check Flutterwave transaction status
if (
    ($result['status'] ?? '')         !== 'success' ||
    ($result['data']['status'] ?? '') !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment verification failed"]);
    exit;
}

// Check currency
if (($result['data']['currency'] ?? '') !== 'NGN') {
    echo json_encode(["status" => "error", "message" => "Invalid payment currency"]);
    exit;
}

// Check amount — allow tiny float rounding differences
$flw_amount = (float)$result['data']['amount'];
if (abs($flw_amount - $amount) > 0.01) {
    echo json_encode(["status" => "error", "message" => "Amount mismatch", "expected" => $amount, "received" => $flw_amount]);
    exit;
}


#################################################
# 🔁 DUPLICATE TRANSACTION GUARD
#################################################

$dupCheck = $conn->prepare("SELECT id FROM subscriptions WHERE transaction_id = ?");
$dupCheck->bind_param("s", $tx_id);
$dupCheck->execute();
$dupCheck->store_result();

if ($dupCheck->num_rows > 0) {
    $dupCheck->close();
    echo json_encode(["status" => "error", "message" => "Transaction already processed"]);
    exit;
}
$dupCheck->close();


#################################################
# 🔑 GENERATE 21-DIGIT SUBSCRIPTION CODE
#################################################

function generateSubscriptionCode(int $length = 21): string {
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= random_int(0, 9);
    }
    return $code;
}

$subscriptionCode = generateSubscriptionCode();


#################################################
# 📅 RENEWAL DATE CALCULATION
#################################################

if (stripos($plan, "annual") !== false) {
    $renewalDate = date("Y-m-d", strtotime("+1 year"));
} elseif (stripos($plan, "monthly") !== false) {
    $renewalDate = date("Y-m-d", strtotime("+1 month"));
} else {
    $renewalDate = null;
}


#################################################
# 💾 INSERT INTO DATABASE
#################################################

$status = "active";

/*
  Type string breakdown (15 params = 15 chars):
  fullname      → s
  username      → s
  email         → s
  phone         → s
  country       → s
  dob           → s
  gender        → s
  business_type → s
  business_name → s
  plan          → s
  amount        → d  ← float/double
  transaction_id→ s
  subscription_code → s
  status        → s
  renewal_date  → s  ← pass null as string-compatible; column must be NULL-able
*/

$stmt = $conn->prepare("
    INSERT INTO subscriptions
        (fullname, username, email, phone, country, dob, gender,
         business_type, business_name, plan, amount, transaction_id,
         subscription_code, status, renewal_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param(
    "ssssssssssdssss",   // 15 chars: 10×s, 1×d, 4×s
    $fullname,
    $username,
    $email,
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
    $status,
    $renewalDate         // null is fine here — ensure column is NULL-able in DB
);

if (!$stmt->execute()) {
    echo json_encode([
        "status"  => "error",
        "message" => "Database insert failed: " . $stmt->error,
        "errno"   => $stmt->errno
    ]);
    exit;
}

$stmt->close();

echo json_encode([
    "status"            => "success",
    "subscription_code" => $subscriptionCode,
    "renewal_date"      => $renewalDate
]);

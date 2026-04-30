<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$file = __DIR__ . '/../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;



$data = json_decode(file_get_contents("php://input"), true);

$session_code   = $data['session_code']   ?? '';
$ref            = $data['transaction_id'] ?? '';
$total          = $data['amount']         ?? 0;



/* ===== VALIDATION ===== */

if (!$session_code || !$ref) {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
    exit;
}




ob_start();
include __DIR__ . '/../SECURE/flutterwave-key.php';
$keyOutput = ob_get_clean();
$keyData   = json_decode($keyOutput, true);
$secretKey = $keyData['secretKey'] ?? '';

if (!$secretKey) {
    echo json_encode([
        "status"  => "error",
        "message" => "Secret key not found"
    ]);
    exit;
}




$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL            => "https://api.flutterwave.com/v3/transactions/$ref/verify",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_CUSTOMREQUEST  => "GET",
  CURLOPT_HTTPHEADER     => array(
    "Authorization: Bearer $secretKey",
    "Content-Type: application/json"
  ),
));

$response = curl_exec($curl);
curl_close($curl);

$result = json_decode($response, true);

if (
    $result['status'] !== 'success' ||
    $result['data']['status'] !== 'successful'
) {
    echo json_encode(["status" => "error", "message" => "Payment not verified"]);
    exit;
}

/* Optional: check amount matches */
if ((float)$result['data']['amount'] !== (float)$total) {
    echo json_encode(["status" => "error", "message" => "Amount mismatch"]);
    exit;
}




$conn->begin_transaction();

try {

    /* ===== UPDATE ORDER: open → paid ===== */
    /* Kitchen gets final confirmation */

    $updateStmt = $conn->prepare("
        UPDATE paid_orders
        SET status = 'paid', payment_ref = ?
        WHERE session_code = ? AND status = 'open'
    ");

    $updateStmt->bind_param("ss", $ref, $session_code);
    $updateStmt->execute();

    if ($updateStmt->affected_rows === 0) {
        throw new Exception("Session not found or already closed");
    }

    /* ===== GET ORDER ID FOR SUCCESS PAGE ===== */

    $fetchStmt = $conn->prepare("
        SELECT id FROM paid_orders WHERE session_code = ? LIMIT 1
    ");

    $fetchStmt->bind_param("s", $session_code);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
    $fetchRow    = $fetchResult->fetch_assoc();
    $order_id    = $fetchRow['id'];

    $conn->commit();

    echo json_encode([
        "status"   => "success",
        "order_id" => $order_id
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status"  => "error",
        "message" => $e->getMessage()
    ]);
}


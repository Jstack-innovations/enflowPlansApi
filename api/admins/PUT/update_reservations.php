<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if($_SERVER['REQUEST_METHOD']=="OPTIONS"){
    http_response_code(200);
    exit;
}

session_start();
//if (!isset($_SESSION['admin_id'])) {
   // http_response_code(401);
  //  echo json_encode(["error" => "Unauthorized"]);
  //  exit;
// }

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

$input = json_decode(file_get_contents("php://input"), true);

if (!$input) {
    echo json_encode(["error" => "No data provided"]);
    exit;
}

if ($input['action'] === 'update') {
    $r = $input['reservation'];
    $sql = "UPDATE reservations SET
        name='{$r['name']}',
        email='{$r['email']}',
        phone='{$r['phone']}',
        booking_date='{$r['booking_date']}',
        transaction_id='{$r['transaction_id']}',
        status='{$r['status']}',
        reservation_code='{$r['reservation_code']}'
        WHERE id='{$r['id']}'";
    $conn->query($sql);
    echo json_encode(["success" => true]);
    exit;
}

if ($input['action'] === 'delete') {
    $id = $input['id'];
    $conn->query("DELETE FROM reservations WHERE id='$id'");
    echo json_encode(["success" => true]);
    exit;
}

echo json_encode(["error" => "Invalid action"]);
?>

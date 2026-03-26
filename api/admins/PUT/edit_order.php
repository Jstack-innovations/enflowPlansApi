<?php
require_once __DIR__ . "/../../SECURE/authGuard.php";

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;


$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing order id"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $order = $conn->query("SELECT * FROM paid_orders WHERE id='$id'")->fetch_assoc();
    echo json_encode($order);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    $stmt = $conn->prepare("UPDATE paid_orders SET 
        name=?, phone=?, table_no=?, order_type=?, total_amount=?, 
        payment_ref=?, order_status=?, full_address=?, plate_order_no=? 
        WHERE id=?");

    $stmt->bind_param(
        "sssssssssi",
        $data['name'],
        $data['phone'],
        $data['table_no'],
        $data['order_type'],
        $data['total_amount'],
        $data['payment_ref'],
        $data['order_status'],
        $data['full_address'],
        $data['plate_order_no'],
        $id
    );

    $stmt->execute();

    echo json_encode(["success" => true]);
}

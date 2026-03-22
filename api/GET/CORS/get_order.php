<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ALWAYS show errors during development (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load DB
$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "db.php not found"]);
    exit;
}

require_once $file;

// Ensure DB connection exists
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

// Get order ID
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Order ID missing"]);
    exit;
}

// Fetch order
$stmt = $conn->prepare("SELECT * FROM paid_orders WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Query failed"]);
    exit;
}

$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Order not found"]);
    exit;
}

// Generate plate number if missing
if (empty($order['plate_order_no'])) {
    $plate_no = "Artisan" . date("Ymd") . "GRILL" . rand(10, 99);

    $updateStmt = $conn->prepare("UPDATE paid_orders SET plate_order_no = ? WHERE id = ?");
    if ($updateStmt) {
        $updateStmt->bind_param("si", $plate_no, $order_id);
        $updateStmt->execute();
    }

    $order['plate_order_no'] = $plate_no;
}

// Fetch items
$itemStmt = $conn->prepare("
    SELECT menu_id, menu_name, quantity 
    FROM paid_order_items 
    WHERE paid_order_id = ?
");

if ($itemStmt) {
    $itemStmt->bind_param("i", $order_id);
    $itemStmt->execute();
    $items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $items = [];
}

$order['items'] = $items;

// Response
echo json_encode([
    "status" => "success",
    "order" => $order
]);

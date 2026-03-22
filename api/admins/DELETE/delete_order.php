<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Content-Type: application/json");

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;

$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(["success" => false]);
    exit;
}

$conn->query("DELETE FROM paid_order_items WHERE paid_order_id='$id'");
$conn->query("DELETE FROM paid_orders WHERE id='$id'");

echo json_encode(["success" => true]);

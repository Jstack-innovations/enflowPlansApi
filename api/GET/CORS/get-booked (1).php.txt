<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header('Content-Type: application/json');

$file = __DIR__ . '/../../SECURE/db.php';

if (!file_exists($file)) {
    die(json_encode(["error" => "db.php not found"]));
}

require_once $file;


$result = $conn->query("SELECT table_id FROM booked_tables WHERE booked = 1");

$booked = [];

while($row = $result->fetch_assoc()){

    $booked[] = (int)$row['table_id'];

}

echo json_encode(["status" => "success", "booked" => $booked]);

?>

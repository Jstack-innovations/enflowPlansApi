<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . "/../../SECURE/db.php";

$range = $_GET['range'] ?? 'today';
$dateCondition = "DATE(created_at) = CURDATE()";

if ($range === "yesterday") {
    $dateCondition = "DATE(created_at) = CURDATE() - INTERVAL 1 DAY";
}
elseif ($range === "this_week") {
    $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)";
}
elseif ($range === "last_week") {
    $dateCondition = "YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1) - 1";
}
elseif ($range === "this_month") {
    $dateCondition = "MONTH(created_at) = MONTH(CURDATE())
                      AND YEAR(created_at) = YEAR(CURDATE())";
}
elseif (!empty($_GET['date'])) {
    $safeDate = $conn->real_escape_string($_GET['date']);
    $dateCondition = "DATE(created_at) = '$safeDate'";
}

$output = [];

/* ================= REVENUE ================= */
$revenueQuery = "
SELECT DATE(created_at) as day,
       SUM(total_amount) as revenue
FROM paid_orders
WHERE $dateCondition
GROUP BY DATE(created_at)
";

$res = $conn->query($revenueQuery);

$output['dailyRevenue'] = [];
while ($row = $res->fetch_assoc()) {
    $output['dailyRevenue'][] = [
        "day" => date("D", strtotime($row['day'])),
        "revenue" => floatval($row['revenue'])
    ];
}

/* ================= HOURLY ================= */
$hourQuery = "
SELECT HOUR(created_at) as hour,
       SUM(total_amount) as revenue
FROM paid_orders
WHERE $dateCondition
GROUP BY HOUR(created_at)
";

$res2 = $conn->query($hourQuery);

$output['hourlyRevenue'] = [];
while ($row = $res2->fetch_assoc()) {
    $output['hourlyRevenue'][] = [
        "hour" => $row['hour'],
        "revenue" => floatval($row['revenue'])
    ];
}

/* ================= ORDER TYPES ================= */
$typeQuery = "
SELECT order_type, COUNT(*) as total
FROM paid_orders
WHERE $dateCondition
GROUP BY order_type
";

$res3 = $conn->query($typeQuery);

$output['orderTypes'] = [];
while ($row = $res3->fetch_assoc()) {
    $output['orderTypes'][] = [
        "name" => $row['order_type'],
        "value" => intval($row['total'])
    ];
}

echo json_encode($output);
